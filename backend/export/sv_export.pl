#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: sv_export.pl 4807 2005-09-19 11:11:39Z yeupou $
#
#  Copyright 2005-2006 (c) Yves Perrin <yves.perrin--cern.ch>
#                          BBN Technologies Corp
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
                                                                                
use strict;
use Savane;
use Savane::Mail;
use Getopt::Long;
use Term::ANSIColor qw(:constants);
use POSIX qw(strftime);
use Time::Local;
use Date::Calc qw(Add_Delta_YMD);

use XML::Writer;
use IO::File;

# Import
our $sys_name;
our $sys_https_host;
our $sys_default_domain;
our $sys_url_topdir;
our $dbd;

# Configure
my $script = "sv_export";
my $logfile = "/var/log/sv_export.log";

my $getopt;
my $help;
my $xml_path;
my $debug;

# get options 
eval {
    $getopt = GetOptions("help" => \$help,
                         "xml-path=s" => \$xml_path,
			 "debug" => \$debug);
};
 
if($help) {
    print STDERR <<EOF;
usage: $0
 
   script that searches the database 'export' table for pending
   requests and for each of these, extracts the relevant data,
   produces the corresponding XML representation and
   writes it together with the appropriate XML schema into a file
   the path of which being specified by the xml-path argument
 
        --help                  print this help
        --xml-path=/            path of the generated xml file
 
Author: yves.perrin\@cern.ch, yeupou\@gnu.org
EOF
 exit(1);
}


#Starts only if the xml-path exists
unless (-d $xml_path) {
  print LOG "[$script] $xml_path does not exists or is not a directory\n";
  die "$xml_path does not exists or is not a directory, exiting",
}

#Locks: instances should not run concurrently, so we add a lock
AcquireReplicationLock();

# Log: Starting logging
open (LOG, ">>$logfile");
print LOG strftime "[$script] %c - starting\n", localtime;

# get the current date and time
my $now = time();

# get the pending export requests
# trackers_export table fields:
#    export_id
#    artifact          = tracker
#    unix_group_name   = savane project name
#    user_name         = login name of requestor
#    sql               = sql to retrieve the items ids (only)
#    status            = P  (pending, to be performed)
#                        D  (done, removed by the user with the interface)
#    date              = unix timestamp.
#                        generate xml if current time > date
#    frequency         = if these are set, status is not set to D when
#                        the job has been done; it remains equal to Q
#                        but the date is updated accordingly
#             _day     = values from 1 (monday) to 7 (sunday)
#             _hour    = values from 0 (midnight) to 23
#
# Note: field labels are used in lower-case with blank space replaced by _
# In theory, if a project set two fields labels to exactly match, broken xml
# will be produced. But that problem could be easily solved by renaming the
# incriminated fields.

my $export_table = 'trackers_export';
my $fields = 'export_id, task_id, artifact, unix_group_name, user_name, `sql`, status, date, frequency_day, frequency_hour';
my $criteria = "date < $now AND status='P'";

my %jobs = GetDBHash($export_table, $criteria, $fields);
while (my ($key, $value) = each(%jobs)) {
    my ($req_id, $req_task_id, $tracker, $req_group, $req_user, $req_ids_sql, $req_status, 
	$req_date, $req_frequency_day, $req_frequency_hour) = (@{$value});

    my @item_ids = GetDBAsIs($req_ids_sql);
    my $group_id = GetGroupSettings($req_group, 'group_id');
    my $group_path = $xml_path.'/'.$req_group;

    if (!(-e $group_path)) {
	mkdir($group_path,0755);
    }
    my $user_path = $group_path.'/'.$req_user;
    
    if (!(-e $user_path)) {
	mkdir($user_path,0755);
    }
    my $xmlfile = $user_path.'/'.$req_id.'.xml'; 
    my $xmlschema = $user_path.'/'.$req_id.'.xsd'; 

    my %tr_fields_project;
    my %tr_fields_values;

    # ----------------------
    # to be cleaned
    my ($pos, $tables_val, $fields_val, $criteria_val, $key);
    my ($sel_fields, $criteria_base, $s_no_item, $fn, $fv, $unix_history_date);
    my ($history_date, $sel_fields_hist, $tables_hist, $criteria_hist);
    my ($sel_fields_dep, $tables_dep, $criteria_dep);
    my (%this_item, $thisid, $fd, $id, $item, $label, $d, $f);
    # ----------------------

    # For ALL fields get DEFAULT usage
    my $tables = $tracker.'_field, '.$tracker.'_field_usage';
    my $fields = $tracker.'_field.field_name, '.$tracker.'_field.label, '.$tracker.'_field.bug_field_id, '.$tracker.'_field.display_type, '.$tracker.'_field_usage.use_it';
    my $criteria = $tracker.'_field_usage.group_id=100 AND  '.
	$tracker.'_field.bug_field_id='.
	$tracker.'_field_usage.bug_field_id ';
    
    my %usage = GetDBHash($tables, $criteria, $fields);
    while (my ($key, $value) = each(%usage)) {
	my ($fname, $flabel, $fid, $fdisptype, $fuse) = (@{$value});
	$tr_fields_project{$fname} = ();
	$tr_fields_project{$fname}{'label'} = $flabel;
	$tr_fields_project{$fname}{'id'} = $fid;
	$tr_fields_project{$fname}{'display_type'} = $fdisptype;
	$tr_fields_project{$fname}{'use'} = $fuse;
    }
    

    # Update %tr_fields_project according to project specific usage entries

    $tables = $tracker.'_field, '.$tracker.'_field_usage';
    $fields = $tracker.'_field.field_name, '.$tracker.'_field_usage.use_it';
    $criteria = 'group_id='.$group_id.' AND  '.
	$tracker.'_field.bug_field_id='.
	$tracker.'_field_usage.bug_field_id ';
    
    my %usage = GetDBHash($tables, $criteria, $fields);
    while (my ($key, $value) = each(%usage)) {
	my ($fname, $fuse) = (@{$value});
	$tr_fields_project{$fname}{'use'} = $fuse;
    }

    # Get the field types and keep only the 'USED' fields                                                                               
    my %tr_fields_arr;
    foreach my $line (GetDBDescribe($tracker)) {
	chomp($line);
	my ($tr_field_name,$tr_field_type) = split(",", $line);
	$pos = -1;
	$pos = index($tr_field_type, 'int(');
	if ($pos != -1) {
	    $tr_field_type = 'integer';
	} else {
	    $pos = index($tr_field_type, 'text');;
	    if ($pos != -1) {
		$tr_field_type = 'string';
	    } else {
		$pos = index($tr_field_type, 'char(');;
		if ($pos != -1) {
		    $tr_field_type = 'string';
		} else {
		    $pos = index($tr_field_type, 'float(');;
		    if ($pos != -1) {
			$tr_field_type = 'decimal';
		    }
		}
	    }
	}
	if ($tr_fields_project{$tr_field_name}{'use'} == 1) {
	    $tr_fields_arr{$tr_field_name} = $tr_field_type;
	    if ($tr_fields_project{$tr_field_name}{'display_type'} eq 'SB') {
		# get the field values

		# Look for project specific values first
		$tables_val = $tracker."_field_value ";
		$fields_val = "value_id, value ";
		$criteria_val = "group_id=$group_id ".
		    "AND bug_field_id=$tr_fields_project{$tr_field_name}{'id'} ".
		    "AND  status IN ('A','P') ";

		
		my %values = GetDBHash($tables_val, $criteria_val, $fields_val);
		while (my ($key, $value) = each(%values)) {
		    my ($vid, $val) = (@{$value});
		    $tr_fields_values{$tr_fields_project{$tr_field_name}{'id'}}{$vid} = $val;
		}

		# If no specific value for this group then look for default values
		if (!exists($tr_fields_values{$tr_fields_project{$tr_field_name}{'id'}})) {
		    $tables_val = $tracker."_field_value ";
		    $fields_val = "value_id, value ";
		    $criteria_val = "group_id=100 ".
			"AND bug_field_id=$tr_fields_project{$tr_field_name}{'id'} ".
			"AND  status IN ('A','P') ";
		    
		    
		    %values = GetDBHash($tables_val, $criteria_val, $fields_val);
		    while (my ($key, $value) = each(%values)) {
			my ($vid, $val) = (@{$value});
			
			$tr_fields_values{$tr_fields_project{$tr_field_name}{'id'}}{$vid} = $val;
		    }
		}
	    }
	}
    }
    

    
    # ---------------------------------------
    # generate the xml schema
    #
    # FIXME: how useful the schema is, actually?
    open (XMLSCHEMA, ">$xmlschema");
    print XMLSCHEMA '<?xml version="1.0" encoding="utf-8"?>'."\n".
	'<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">'."\n";
    print XMLSCHEMA '<xs:element name="savaneexport">'."\n";                            
    print XMLSCHEMA '  <xs:complexType>'."\n";
    print XMLSCHEMA '    <xs:element name="item">'."\n";
    print XMLSCHEMA '    <xs:complexType>'."\n";
    print XMLSCHEMA '      <xs:element name="tracker" type="string" />'."\n";
    foreach $key (keys(%tr_fields_arr)) {
	my $element = lc($tr_fields_project{$key}{'label'});
	$element =~ s/\s/_/g;
	$element =~ s/\//_/g;
	$element =~ s/\#//g;
	print XMLSCHEMA '      <xs:element name="'.$element.'" type="'.$tr_fields_arr{$key}.'" />'."\n";
    }

    print XMLSCHEMA '      <xs:element name="depends_on" minOccurs="0">'."\n";
    print XMLSCHEMA '        <xs:complexType>'."\n";
    print XMLSCHEMA '          <xs:element name="item_id" type="integer" />'."\n";
    print XMLSCHEMA '          <xs:element name="tracker" type="string" />'."\n";
    print XMLSCHEMA '        </xs:complexType>'."\n";
    print XMLSCHEMA '      </xs:element>'."\n";
    print XMLSCHEMA '      <xs:element name="history" minOccurs="0" maxOccurs="1">'."\n";
    print XMLSCHEMA '        <xs:complexType>'."\n";
    print XMLSCHEMA '          <xs:element name="event" minOccurs="1">'."\n";
    print XMLSCHEMA '            <xs:complexType>'."\n";
    print XMLSCHEMA '              <xs:element name="date" type="date" />'."\n";
    print XMLSCHEMA '              <xs:element name="field" minOccurs="1">'."\n";
    print XMLSCHEMA '                <xs:complexType>'."\n";
    print XMLSCHEMA '                  <xs:element name="field_name" type="string" />'."\n";
    print XMLSCHEMA '                  <xs:element name="old_value" type="string" />'."\n"; 
    print XMLSCHEMA '                  <xs:element name="new_value" type="string" />'."\n";
    print XMLSCHEMA '                  <xs:element name="modified_by" type="string" />'."\n";
    print XMLSCHEMA '                </xs:complexType>'."\n";
    print XMLSCHEMA '              </xs:element>'."\n";
    print XMLSCHEMA '            </xs:complexType>'."\n";
    print XMLSCHEMA '          </xs:element>'."\n";
    print XMLSCHEMA '      </xs:complexType>'."\n";
    print XMLSCHEMA '      </xs:element>'."\n";
    print XMLSCHEMA '    </xs:complexType>'."\n";
    print XMLSCHEMA '    </xs:element>'."\n";
    print XMLSCHEMA '  </xs:complexType>'."\n";
    print XMLSCHEMA '</xs:element>'."\n";
    print XMLSCHEMA '</xs:schema>'."\n";
    close(XMLSCHEMA);

    # ------------- build the sql command to get all items data --------------
    
    $sel_fields = 'DISTINCT ';
    $tables = $tracker;
    $criteria_base = '';

    $s_no_item = 1;
    
    while (($fn, $fv) = each(%tr_fields_arr)) {
	if (!$s_no_item) {
	    $sel_fields .= ",";
	}
	if (($fn eq 'assigned_to') || ($fn eq 'submitted_by')) {
	    # user names requires some special processing to display the
	    # username instead of the user_id
	    $sel_fields .= " user_$fn.user_name AS $fn";
	    $tables .= ", user user_$fn";
	    $criteria_base .= " AND user_$fn.user_id=".$tracker.".$fn ";
	} else {
	    # otherwise just select this column as is
	    $sel_fields .= " ".$tracker.".$fn";
	}
	$s_no_item = 0;
    }

    # -------------------- Generate the XML data ----------------------

    print LOG strftime "[$script] %c - start writing $xmlfile\n", localtime if $debug;
    my $xml = new IO::File(">$xmlfile");
    # UNSAFE has been turned on because on big installation, some project
    # were able to put content that XML::Writer is not able to convert 
    # properly. We have to think about the issue.
    # In the meantime, our script has been verified to provide valid XML 
    # in the structure and in the content with normal data. So turning the
    # safety checks off is probably the best thing to do for now.
    my $writer = new XML::Writer(OUTPUT => $xml,
				 DATA_MODE => 1,
				 DATA_INDENT => 2,
				 UNSAFE => 1);
    $writer->xmlDecl("UTF-8");
    $writer->comment(strftime "Generated on %c", localtime);
    $writer->comment("A XML Schema is available at the same url, with the suffix .xsd instead of .xml");
    $writer->startTag("savaneexport");

    my @fn = split(",", $sel_fields);
    foreach $id (@item_ids) {

	# --------------------- Get the items history ------------------------

	$sel_fields_hist = 'bug_id, date, field_name, old_value, new_value, user.user_name ';
	$tables_hist = $tracker.'_history, user ';
	$criteria_hist = 'bug_id='.$id.' AND user.user_id='.$tracker.'_history.mod_by ORDER BY date';
        my %history_hash = {}; #hash elements will be hash of array of hashes !
	
	my %hist_row = GetDBHash($tables_hist,$criteria_hist,$sel_fields_hist);
	while (my ($key, $value) = each(%hist_row)) {
	  my ($h_bug_id, $h_date, $h_field, $h_f_old, $h_f_new, $h_f_modby) =
             (@{$value});
	    
	  if (!exists($history_hash{$h_bug_id})) {
	    $history_hash{$h_bug_id} = {}; # possibly many events for same id
	  }
	  if (!exists($history_hash{$h_bug_id}{$h_date})) {
	    $history_hash{$h_bug_id}{$h_date} = (); # possibly many fields for same date
            $history_hash{$h_bug_id}{$h_date}[0] = {
                                      field => $h_field,
                                      old_value  => $h_f_old,
                                      new_value  => $h_f_new,
                                      mod_by     => $h_f_modby };
	  } else {
            my %this_hist_date_event = {};
            $this_hist_date_event{'field'} = $h_field;
            $this_hist_date_event{'old_value'}  = $h_f_old;
            $this_hist_date_event{'new_value'}  = $h_f_new;
            $this_hist_date_event{'mod_by'}     = $h_f_modby;
          
            # push ($history_hash{$h_bug_id}{$h_date}, %this_hist_date_event);
            my $hist_date_index = scalar @{$history_hash{$h_bug_id}{$h_date}};
            # scalar returns length, so last element +1 ... no need to pre-inc
            %{$history_hash{$h_bug_id}{$h_date}[$hist_date_index]} = 
                 %this_hist_date_event;
          }
	}

        # --------------------- Get the items dependencies -------------------
                                                                                
        $sel_fields_dep = 'is_dependent_on_item_id, is_dependent_on_item_id_artifact ';
        $tables_dep = $tracker.'_dependencies ';
        $criteria_dep = 'item_id='.$id;
        my %dep_hash = {}; #hash elements will be array of hashes !!!
                                                                                
        my %dep_row = GetDBHash($tables_dep,$criteria_dep,$sel_fields_dep);
        my $i = 0;
        while (my ($key, $value) = each(%dep_row)) {
          my %this_dep = {};
          ($this_dep{'item_id'}, $this_dep{'tracker'}) = (@{$value});
          if (!exists($dep_hash{$id})) {
            $dep_hash{$id} = ();
          }
          %{$dep_hash{$id}[$i]} = %this_dep;
          $i++;
        }
                                                                                
	# ---------------------- Get the items data --------------------------
	
	$criteria = $tracker.'.bug_id='.$id.$criteria_base;  

	my %item_row = GetDBHash($tables,$criteria,$sel_fields);
	while (my ($key, $value) = each(%item_row)) {
	    my @f = (@{$value});

	    $writer->startTag("item");
	    $writer->startTag("tracker");
	    $writer->characters($tracker);
	    $writer->endTag("tracker");;

	    my $k = 0;
	    foreach my $fn (keys (%tr_fields_arr)) {
		$label = lc($tr_fields_project{$fn}{'label'});
		$label =~ s/\s/_/g;
		$label =~ s/\//_/g;
		$label =~ s/\#//g;
		if ($tr_fields_project{$fn}{'display_type'} eq 'SB') {
		    $writer->startTag($label);
		    if (($fn eq 'submitted_by') || ($fn eq 'assigned_to')) {
			$writer->characters($f[$k]);
		    } else {
			$writer->characters($tr_fields_values{$tr_fields_project{$fn}{'id'}}{$f[$k]});
		    }
		    $writer->endTag($label);
		} else {
		    $writer->startTag($label);
		    $writer->characters($f[$k]);
		    $writer->endTag($label);
		}
		$k++;
	    }

            if (exists($dep_hash{$id})) {
                my $nb_of_dep = scalar @{$dep_hash{$id}};
                my $i = 0;
                while ($i < $nb_of_dep) {
                  $writer->startTag("depends_on");
                    $writer->startTag("item_id");
#                   my $itid = ${$dep_hash{$id}[$i]}{'item_id'};
#                   $writer->characters($itid);
                    $writer->characters(${$dep_hash{$id}[$i]}{'item_id'});
                    $writer->endTag("item_id");
                    $writer->startTag("tracker");
                    $writer->characters(${$dep_hash{$id}[$i]}{'tracker'});
                    $writer->endTag("tracker");
                  $writer->endTag("depends_on");
                  $i++;
                }
            }

	    if (exists($history_hash{$id})) {
		$writer->startTag("history");

		for $d ( sort keys %{$history_hash{$id}}) {
		    $writer->startTag("event");
		    $writer->startTag("date");
		    $writer->characters($d);
		    $writer->endTag("date");

                    my $i = 0;
                    my $nb_of_fields = scalar @{$history_hash{$id}{$d}};
                    while ($i < $nb_of_fields) {
			$writer->startTag("field");

                        $label = $tr_fields_project{$history_hash{$id}{$d}[$i]{'field'}}{'label'};

			$label =~ s/\s/_/g;

			$writer->startTag("field_name");
			$writer->characters($label);
			$writer->endTag("field_name");
			
			$writer->startTag("old_value");
			$writer->characters($history_hash{$id}{$d}[$i]{'old_value'});
			$writer->endTag("old_value");

			$writer->startTag("new_value");
			$writer->characters($history_hash{$id}{$d}[$i]{'new_value'});
			$writer->endTag("new_value");

			$writer->startTag("modified_by");
			$writer->characters($history_hash{$id}{$d}[$i]{'mod_by'});
			$writer->endTag("modified_by");

			$writer->endTag("field");
                        $i++;
		    }
		    $writer->endTag("event");
		}
		$writer->endTag("history");
	    }
	    $writer->endTag("item");
	}
    } 
    $writer->endTag("savaneexport");
    $xml->close();
    
    # --------------------- Notice the change ------------------------
    # should:
    #    - update the status
    #    - update the timestamp in case of a frequent job
    #    - post a comment on the relevant task
    
    print LOG strftime "[$script] %c - export #$req_id processed\n", localtime;
    
    if (!$req_frequency_day && !$req_frequency_hour) {

	# One-time export request: update the status
	SetDBSettings($export_table, "export_id='$req_id'", "status='D'");

    } else {

        # insert completion comment in export task
        my $task_comment = "... Requested items have been exported";
        InsertDB("task_history",
                 "bug_id, field_name, old_value, mod_by, date",
                 "'$req_task_id', 'details', '$task_comment', '100', '$now'");
                                                                                
	# Otherwise, update the timestamp
	# Assume that the script is running the correct day, so we can easily
	# just add 7 days to the current day
	my ($year, $month, $day) = split(",", `date +%Y,%m,%d`);
	($year,$month,$day) = Add_Delta_YMD($year,$month,$day, 0,0,7);
	my $timestamp = timelocal("0","0",$req_frequency_hour,$day,($month-1),($year-1900));
	SetDBSettings($export_table, "export_id='$req_id'", "date='$timestamp'"); 

    }
       
	
}

# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";


# EOF
