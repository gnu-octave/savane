#!/usr/bin/perl
#
# Copyright (c) 2003-2004 Yves Perrin <yves.perrin@cern.ch>
#
#   The Savane project is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   The Savane project is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with the Savane project; if not, write to the Free Software
#   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# $Id$

##################################################################
# db_tracker_configurator.pl
#
# script to transfer the old tracker configurations to the new
# Savannah tracker structure
#
# Author: Yves Perrin <yves.perrin@cern.ch>
##################################################################

use strict;
use DBI;
use Term::ReadKey;
use File::Basename;
use Getopt::Std;

# only for debugging: the Dumper module
use Data::Dumper;


my $sdbh;     # source db handle
my $ssth;     # source statement handle objects

my $tdbh;     # target db handle
my $tsth;     # target statement handle objects

my $query;    # query


sub usage {

print <<"EOF";

$0
    usage: $0 [-c configuration_file] [-o sql-outputfile]
              [-l logfile]

 This script migrates the project specific tracker configurations of
 the old Savannah data base to the new tracker model. It creates a
 file containing SQL commands, which upon execution via mysql will
 transfer the configurations.

EOF
}

############################
# get_source_groups
# usage: values=get_source_groups()
#
# input parameters:
#   none
#
# return values:
#   hash of group_id, group_name pairs

sub get_source_groups {
  my $query;
  my @row;
  my %groups;

  $query="select group_id, group_name from groups";
  $ssth = $sdbh->prepare($query);
  $ssth->execute();

  while(@row = $ssth->fetchrow_array) {
    $groups{$row[0]} = $row[1];
  }
  return (\%groups);
}

############################
# get_source_nb_entries
# usage: values=get_source_nb_entries(src_table_name, entry_id_name,
#                                     group_id_name, group_id)
#
# input parameters:
#   src_table_name       name of source table
#   entry_id_name        name of entry_id field
#   group_id_name        name of group_id field
#   group_id             id of the group concerned
#
# return values:
#   nb of entries found for the specified group

sub get_source_nb_entries {
  my $src_table_name=shift;
  my $entry_id_name=shift;
  my $group_id_name=shift;
  my $group_id=shift;
  my $query;
  my @row;
  my $nb_entries;

  $query="SELECT $entry_id_name FROM $src_table_name WHERE $group_id_name='$group_id'";
  $ssth = $sdbh->prepare($query);
  $ssth->execute();

  $nb_entries=0;
  while(@row = $ssth->fetchrow_array) {
    $nb_entries++;
  }
  return $nb_entries;
}


############################
# get_source_field_values
# usage: values=get_source_field_values(src_field_values_table_name, fvalue_name, fid_name,
#                                       group_id, no_gr_in_src)
#
# input parameters:
#   src_field_values_table_name   name of source field values table
#   fvalue_name                   name of the 'field value' field
#   fid_name                      name of the 'field id' field
#   group_id                      id of the group concerned
#   no_gr_in_src                  field is group independent in source (no group_id in source table)
#
# return values:
#   hash of value, value_id pairs

sub get_source_field_values {
  my $src_field_values_table_name=shift; # name of source field values table
  my $fvalue_name=shift;                 # name of the 'field value' field
  my $fid_name=shift;                    # name of the 'field id' field
  my $group_id=shift;                    # id of the group concerned
  my $no_gr_in_src=shift;                # no group_id in source table
  my $query;
  my @row;
  my %values;

  $query="select $fvalue_name, $fid_name from $src_field_values_table_name";
  if (!$no_gr_in_src) {
    $query.=" WHERE group_id='$group_id'";
  }
  $ssth = $sdbh->prepare($query);
  $ssth->execute();

  $values{None} = 100;   # the awkward design of the Savannah tracker
                         # requires a 'None' category entry for every
                         # project that has modified its tracker categories
                         # from the default configuration

  while(@row = $ssth->fetchrow_array) {
    $values{$row[0]} = $row[1];
  }
  return (\%values);
}

############################
# tracker_set_field_usage
# usage: query=tracker_set_field_usage(tracker_name, field_name, group_id)
#
# input parameters:
#   tracker_name     name of tracker ( bugs / patch / support / task )
#   field_name       field name
#   group_id         id of the group concerned
#
# return values:
#   the sql query command to set this field usage

sub tracker_set_field_usage {
  my $tracker_name=shift;    # name of tracker ( bugs / patch / support / task )
  my $field_name=shift;      # field name
  my $group_id=shift;        # group id
  my $query;
  my @row;
  my $tmpsql;
  my $use_it = 1;
  my $show_on_add = 3;
  my $show_on_add_members = 1;
  my $rank = 10;
  my $lbl = "NULL";
  my $desc = "NULL";
  my $disp_size = "NULL";
  my $empty = "NULL";
  my $keep_hist = 1;
  my $trk_field_usage_table_name=$tracker_name.'_field_usage';

  $tmpsql="SELECT bug_field_id FROM $tracker_name"."_field WHERE field_name='$field_name'";
  $tsth = $tdbh->prepare($tmpsql);
  $tsth->execute();
  @row = $tsth->fetchrow_array;
  my $field_id=$row[0];        # field id

  $tmpsql="SELECT bug_field_id FROM $trk_field_usage_table_name WHERE bug_field_id='$field_id' AND group_id='$group_id'";
  $tsth = $tdbh->prepare($tmpsql);
  $tsth->execute();
  if ($tsth->fetchrow_array) {
    print LOGFILE "\nWARNING - the tracker: $tracker_name already has a usage entry for: $field_name in group: $group_id\n";
  } else {
    $query="INSERT INTO $trk_field_usage_table_name ".
           "(bug_field_id,group_id,use_it,show_on_add,show_on_add_members,place,custom_label,".
           "custom_description,custom_display_size,custom_empty_ok,custom_keep_history) ".
           "VALUES ('$field_id','$group_id','$use_it','$show_on_add',".
           "'$show_on_add_members','$rank',$lbl,$desc,$disp_size,$empty,$keep_hist );";
  }
  return $query;
}

############################
# tracker_add_field_values
# usage: query=tracker_add_field_values(trk_field_values_table_name, field_name, group_id, values)
#
# input parameters:
#   tracker_name     name of tracker ( bugs / patch / support / task )
#   field_name       field name
#   group_id         id of the group concerned
#   values           hash of value, value_id pairs
#
# return values:
#   the sql query command to set this field values

sub tracker_add_field_values {
  my $tracker_name=shift;    # name of tracker ( bugs / patch / support / task )
  my $field_name=shift;      # field name
  my $group_id=shift;                   # group id
  my $values=shift;                     # values
  my $value;
  my $query='';
  my @row;
  my $tmpsql;
  my $order_id = 10;
  my $status = 'A';
  my $trk_field_values_table_name=$tracker_name.'_field_value'; # name of tracker field values table

  $tmpsql="SELECT bug_field_id FROM $tracker_name"."_field WHERE field_name='$field_name'";
  $tsth = $tdbh->prepare($tmpsql);
  $tsth->execute();
  @row = $tsth->fetchrow_array;
  my $field_id=$row[0];        # field id
  my $qvalue;

  foreach $value (keys %$values) {
    $qvalue = $tdbh->quote($value);
    $tmpsql="SELECT bug_field_id FROM $trk_field_values_table_name WHERE bug_field_id='$field_id' ".
    "AND group_id='$group_id' AND value=$qvalue";
    $tsth = $tdbh->prepare($tmpsql);
    $tsth->execute();
    if ($tsth->fetchrow_array) {
      print LOGFILE "\nWARNING - the tracker: $tracker_name already has the $value value entry for: $field_name in group: $group_id\n";
    } else {
      # compose the SQL command to declare the value in the target database
      $query.="INSERT INTO $trk_field_values_table_name ".
             "(bug_field_id,group_id,value_id,value,description,order_id,status) ".
             "VALUES ('$field_id','$group_id','$$values{$value}',$qvalue,$qvalue,'$order_id','$status');\n";
      $order_id += 10;
    }
  }
  return $query;
}

############################
# tracker_configure_field
# usage: query=tracker_configure_field(ttracker_name, tfname, group_id, set_usage, create_values,
#                                      src_field, src_value_table, sfn_name, sfid_name, value_ref, sgr_dep)
#
# input parameters:
#
#   $ttracker_name   tracker name in target
#   tfname           field name in target tracker
#   group_id         id of the group concerned
#   set_usage        set 'use_it' attribute for this field in target
#   create_values    create value entries for this field in target
#   src_field        source table field (full name: tablename.fieldname)
#   src_value_table  related source field value table (name or "" if none)
#   sfn_name         name of 'field name' field in source field value table
#   sfid_name        name of 'field id' field in source field value table
#   value_ref        pointer to value-value_id hash or 0 if values are from src_value_table
#   sgr_indep        field values are global in source (do not depend on group)
#
# return values:
#   the sql query commands to configure this field for this group in this tracker

sub tracker_configure_field {
  my $ttracker_name=shift;
  my $tfname=shift;
  my $group_id=shift;
  my $set_usage=shift;
  my $create_values=shift;
  my $src_value_table=shift;
  my $sfn_name=shift;
  my $sfid_name=shift;
  my $value_ref=shift;
  my $sgr_indep=shift;

  my $values;
  my $field_id;
  my $query="#\n";

  if ($create_values != 0) {
    if ($value_ref == 0) {
      if (($src_value_table eq "") || ($sfn_name eq "") || ($sfid_name eq "")) {
        die "\nERROR - no values hash supplied and one (or more) src field values params missing for field: $sfn_name in tracker: $ttracker_name for group: $group_id\n";
      } else {
        $values=get_source_field_values($src_value_table, $sfn_name, $sfid_name, $group_id, $sgr_indep);
      }
    } else {
      if (($src_value_table ne "") || ($sfn_name ne "") || ($sfid_name ne "")) {
        die "\nERROR - values hash supplied and one (or more) src field values params supplied for field: $sfn_name in tracker: $ttracker_name for group: $group_id\n";
      } else {
        $values=$value_ref;
      }
    }
    $query.=tracker_add_field_values($ttracker_name, $tfname, $group_id, $values);
    $query.="\n#\n";
  }
  if ($set_usage != 0) {
    $query.=tracker_set_field_usage($ttracker_name, $tfname, $group_id);
    $query.="\n#\n";
  }
  return $query;
}

#############################################################
# MAIN
#############################################################

my $logfile="dbmapspec.log";
my $dbcomfile="dbmapspec.sql"; # MySQL output command file
my $flag_verbose;
my $conffilename;

# option parsing

my %option=();
getopts("c:hl:o:v",\%option);

if (defined $option{v}) {
    $flag_verbose=1;
}
if (defined $option{h}) {
  usage();
  exit;
}

if (defined $option{c}) {
  $conffilename=$option{c};
} else {
  $conffilename="dbmapper.conf";
}
if (defined $option{l}) {
  $logfile=$option{l};
} else {
  ($logfile= basename($0)) =~ s/^([^.]*).*/$1.log/;
}
if (defined $option{o}) {
  $dbcomfile=$option{o};
} else {
  ($dbcomfile= basename($0)) =~ s/^([^.]*).*/$1.sql/;
}


# CONFIGURATION FILE SOURCING
# read in the configuration file containing information
# on the data base and input/output files
our $src_dbname;
our $tgt_dbname;
our $dbhost;
our $dbport;
our $username;
our $password;
if (! -e $conffilename) {
  die "ERROR: could not open configuration file $conffilename\n";
}
do $conffilename;

if(!defined($password)) {
  # prompt user for the data base password
  print "DB password for user $username: ";
  ReadMode 'noecho';
  $password=ReadLine 0;
  chomp $password;
  ReadMode 'normal';
  print "\n";
}


# Open the MySQL command file
open (LOGFILE,">$logfile") or die "Error: Could not open log file: $logfile";

# Open the MySQL command file
open (DBFILE,">$dbcomfile") or die "Error: Could not open db command file: $dbcomfile";

print DBFILE "use $tgt_dbname;\n";


# connect to source database
$sdbh = DBI->connect("DBI:mysql:$src_dbname:$dbhost:$dbport",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		   );

# connect to target database
$tdbh = DBI->connect("DBI:mysql:$tgt_dbname:$dbhost:$dbport",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		   );

my $group_id;
my $groups = get_source_groups();
my $cfgd_gr;
my %values;

my $from_src = 0;
my $no_gr_ind = 0;
my $gr_ind = 1;
my $no_set_use = 0;
my $set_use = 1;
my $no_cr_val = 0;
my $cr_val = 1;

print LOGFILE "\n--------- SUPPORT TRACKER CONFIGURATION ----------\n";
$cfgd_gr = 0;

foreach $group_id (keys %$groups) {

  if (get_source_nb_entries('support', 'support_id', 'group_id', $group_id) > 0) {

    $query.=tracker_configure_field('support', 'category_id', $group_id,  $set_use, $cr_val,
				  'support_category', 'category_name',
                                  'support_category_id', $from_src, $no_gr_ind);

    print LOGFILE "\n        > group: $$groups{$group_id} configured\n";
    $cfgd_gr++;

  }
}
print LOGFILE "\n    --> $cfgd_gr groups configured\n";

print LOGFILE "\n--------- TASK TRACKER CONFIGURATION ----------\n";
$cfgd_gr = 0;

foreach $group_id (keys %$groups) {

  if (get_source_nb_entries('project_group_list', 'group_project_id', 'group_id', $group_id) > 0) {

    $query.=tracker_configure_field('task', 'category_id', $group_id,  $set_use, $cr_val,
				  'project_group_list', 'project_name',
                                  'group_project_id', $from_src, $no_gr_ind);

    print LOGFILE "\n        > group: $$groups{$group_id} configured\n";
    $cfgd_gr++;

  }
}
print LOGFILE "\n    --> $cfgd_gr groups configured\n";

print LOGFILE "\n--------- PATCH TRACKER CONFIGURATION ----------\n";
$cfgd_gr = 0;

foreach $group_id (keys %$groups) {

  if (get_source_nb_entries('patch', 'patch_id', 'group_id', $group_id) > 0) {


    $query.=tracker_configure_field('patch', 'category_id', $group_id, $set_use, $cr_val,
		                  'patch_category', 'category_name',
                                  'patch_category_id', $from_src, $no_gr_ind);

    print LOGFILE "\n        > group: $$groups{$group_id} configured\n";
    $cfgd_gr++;
  }
}
print LOGFILE "\n    --> $cfgd_gr groups configured\n";
print LOGFILE "\n\n";

print DBFILE "#\n$query";

# ------------------------------------

$ssth->finish();
# disconnect from source database
$sdbh->disconnect();

$tsth->finish();
# disconnect from target database
$tdbh->disconnect();

close(DBFILE);
close(LOGFILE);

