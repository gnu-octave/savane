#!/usr/bin/perl
#
# Copyright (c) 2003-2004 Derek Feichtinger <derek.feichtinger@cern.ch>
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

# db_move_entries.pl
#
# Routines to access the tracker structures of the old
# Savannah implementation (prior to CERN branch) and
# move the tracker entries (support, task, ...) to
# the new data base structure.

use strict;
use DBI;
use Term::ReadKey;
use Getopt::Std;

# only for debugging: the Dumper module
use Data::Dumper;

my $flag_verbose=0;

sub usage {

print <<'EOF' ;

db_move_entries.pl

   usage: db_move_entries.pl [-c configuration_file]
                             [-l logfile ]

 Routines to access the tracker structures of an old Savannah
 implementation (prior to CERN branch) and move the tracker entries
 (support, task, ...) to the new data base structure.

 The logfile will always contain the last mysql command executed.
 This can be used to find the source of an erroneous mysql
 statement.

EOF
}

#############################################################
# read_old_support_request
#
# usage $ref = read_old_support_request($dbh,$support_id)
#
# returns all associated data of a support entry in a
# nested perl structure. The data of the added messages
# can be accessed via $$ref{messages}
#
# The structure contains these fields:
# 'close_date'
# 'summary'
# 'assigned_to'
# 'support_id'
# 'open_date'
# 'group_id'
# 'support_category_id'
# 'submitted_by'
# 'messages'  => Array of references to message structures
# 'history'   => Array of references to history structures
#
sub read_old_support_request {
  my $dbh=shift; # data base handle
  my $support_id=shift;

  my $query;
  my $sth;

  $query="SELECT * FROM support where support_id=$support_id LIMIT 1";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my $support_row = $sth->fetchrow_hashref;
  if(!defined($support_row)) {
    return $support_row;
  }

  # get all data from the support_messages table
  $query="SELECT * FROM support_messages WHERE support_id=$support_id ORDER BY date";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my @messages;
  while (my $support_message = $sth->fetchrow_hashref) {

    # the logged_in, user and browser information is kept in the
    # body of each message. We extract it into correctly named
    # fields of this hash
    my @bodyarray = split ("\n",$$support_message{body});
    my $line;;
    my $token;

    $line=shift @bodyarray;
    if ( ($token)=$line =~ m/\s*Logged In:\s*([^\s]*)/ ) {
      $$support_message{logged_in}=$token;
    } elsif ($flag_verbose) {
      print stderr "WARNING: No 'logged In' tag found in ".
	"support_message_id=$$support_message{support_message_id}\n";
    }
    if($$support_message{logged_in} eq "YES") {
      $line=shift @bodyarray;
      if ( ($token)=$line =~ m/\s*user_id=\s*(\d*)/ ) {
	$$support_message{user_id}=$token;
      } elsif ($flag_verbose) {
	print stderr "WARNING: No 'user_id' tag found in ".
	  "support_message_id=$support_id\n";
      }
    } else {
      $$support_message{user_id}=100;
    }

    $line=shift @bodyarray;
    if ( ($token)=$line =~ m/\s*Browser:\s*(.*)/ ) {
      chomp $token;
      $$support_message{Browser}=$token;
    } elsif ($flag_verbose) {
      print stderr "WARNING: No 'Browser' tag found in ".
	"support_message_id=$support_id\n";
    }

    # recombine the remaining lines into the new body
    $$support_message{body}=join("\n",@bodyarray);

    push @messages,$support_message;

  }
  $$support_row{messages}=\@messages;


  # get all data from the support_history table
  $query="SELECT * FROM support_history WHERE support_id=$support_id";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my @history;
  while (my $support_history = $sth->fetchrow_hashref) {
    push @history, $support_history;
  }
  $$support_row{history}=\@history;

  return $support_row;
}
#############################################################

############################
# read_old_task_entry
#
# usage $ref = read_old_task_entry($dbh,$task_id)
#
# returns all associated data of a support entry in a
# nested perl structure. The data of the added messages
# can be accessed via $$ref{messages}
sub read_old_task_entry {
  my $dbh=shift; # data base handle
  my $task_id=shift;

  my $query;
  my $sth;

  $query="SELECT * FROM project_task where project_task_id=$task_id LIMIT 1";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my $task = $sth->fetchrow_hashref;
  if(!defined($task)) {
    return $task;
  }

  # the group id of this item can only be obtained by looking up to
  # which group its associated category belongs! We add it as another
  # field to the hash
  $query="SELECT group_id FROM project_group_list WHERE ".
    "group_project_id=$$task{group_project_id}";
  $sth = $dbh->prepare($query);
  $sth->execute;
  my $tmprow=$sth->fetchrow_hashref;
  $$task{group_id}=$$tmprow{group_id};

  # get the assigned_to value from the project_assigned_to table
  $query="SELECT assigned_to_id from project_assigned_to WHERE project_task_id=$task_id";
  $sth = $dbh->prepare($query);
  $sth->execute;
  my $tmprow=$sth->fetchrow_hashref;
  $$task{assigned_to}=$$tmprow{assigned_to_id};


  # get the associated history entries
  $query="SELECT * FROM project_history where project_task_id=$task_id";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my @history;
  while(my $entry = $sth->fetchrow_hashref) {
    push @history, $entry;
  }
  $$task{history}=\@history;


  # get the task to task dependencies
  $query="SELECT * FROM project_dependencies where project_task_id=$task_id";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my @depend;
  while(my $entry = $sth->fetchrow_hashref) {
    push @depend, $entry;
  }
  $$task{dependency}=\@depend;


  return $task;
}

#############################################################
# read_old_patch_entry
#
# usage $ref = read_old_patch_entry($dbh,$patch_id)
#
sub read_old_patch_entry {
  my $dbh=shift; # data base handle
  my $patch_id=shift;

  my $query;
  my $sth;

  $query="SELECT * FROM patch where patch_id=$patch_id LIMIT 1";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my $patch = $sth->fetchrow_hashref;
  if(!defined($patch)) {
    return $patch;
  }


  # get the associated history entries
  $query="SELECT * FROM patch_history where patch_id=$patch_id";
  $sth = $dbh->prepare($query);
  $sth->execute;

  my @history;
  while(my $entry = $sth->fetchrow_hashref) {
    push @history, $entry;
  }
  $$patch{history}=\@history;


  return $patch;
}


#############################################################
# given a table name and a hash containing
#        { column name => perl command }
# pairs, creates a string containing a mysql INSERT
# command that can be obtained in the calling function via an
# "eval"
sub build_sql_insert_string {
  my $tablename=shift; # name of the target table
  my $map=shift; # hash containing the mappings

  my $colnames="";
  my $colvalues="";
  my $colname;
  foreach $colname (keys %$map) {
    $colnames .= "$colname,";
    $colvalues .= '$dbh->quote(' . $$map{$colname} . ').",".';
  }
  chop $colnames;
  $colvalues=substr($colvalues,0,-5);

  my $result = "'INSERT INTO $tablename (" .
    $colnames . ") VALUES ('." . $colvalues . ".')'";

  return $result;
}

#############################################################
# retrieve the savannah user_id based on an email address
# (limitation: just get's the first hit. may be not what
# one wants if user owns multiple accounts).
sub get_userid_from_email {
  my $dbh=shift;
  my $email=shift;

  my $sth = $dbh->prepare("SELECT user_id FROM user WHERE email='$email' LIMIT 1");
  $sth->execute;
  my $row = $sth->fetchrow_hashref;
  if(defined($$row{user_id})) {
    return $$row{user_id};
  } else {
    return 100;
  }
}

sub get_userid_from_username {
  my $dbh=shift;
  my $username=shift;

  my $sth = $dbh->prepare("SELECT user_id FROM user WHERE user_name='$username' LIMIT 1");
  $sth->execute;
  my $row = $sth->fetchrow_hashref;
  if(defined($$row{user_id})) {
    return $$row{user_id};
  } else {
    return 100;
  }
}



#############################################################
# MAIN
#############################################################

my $conffilename;
my $sqlfile;


# option parsing

my %option=();
getopts("c:l:hv",\%option);

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
  $sqlfile=$option{l};
} else {
  $sqlfile="db_move_entries.sql";
}


# source the configuration file
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


if (!defined($password)) {
  # prompt user for the data base password
  print "DB password for user $username: ";
  ReadMode 'noecho';
  $password=ReadLine 0;
  chomp $password;
  ReadMode 'normal';
  print"\n";
}

my $dbh = DBI->connect("DBI:mysql:$src_dbname:$dbhost:$dbport",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		   );

my $dbh_tgt = DBI->connect("DBI:mysql:$tgt_dbname:$dbhost:$dbport",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		   );

############
## some function tests during development:
#my $nid=get_userid_from_email($dbh,'yves.perrin@cern.ch');
#print "user_id: $nid\n";

# two simple examples for testing the functions
#my $support_struct=read_old_support_request($dbh,100080);

# if (!defined($support_struct)) {
#   die "\nERROR: No such support_id\n";
# }
# print "\n\n-------------------------------------------------\n";
#print "Support Request: ".Dumper($support_struct);

# my $task_struct=read_old_task_entry($dbh,30);
# if (!defined($task_struct)) {
#   die "\nERROR: No such task_id\n";
# }
# print "\n\n-------------------------------------------------\n";
# print "Task entry: ".Dumper($task_struct);
#
#my $patch_struct=read_old_patch_entry($dbh,2);
#print "Patch entry:\n".Dumper($patch_struct);
#exit;
#
##################################


my $row;
my $query;
my $sth_mig;

# we don't want output buffering for the debug files
$|=1;


#######################################################
# BUG TRACKER


# move the bug dependencies to the new structure
# CAREFUL: This assumes that the item ids inside the tracker have
# not changed
my $depend;

my %bug_bug_dep_map=('item_id' => '$$depend{bug_id}',
		  'is_dependent_on_item_id' => '$$depend{is_dependent_on_bug_id}',
		  'is_dependent_on_item_id_artifact' => '"bug"'
		 );
my %bug_task_dep_map=('item_id' => '$$depend{bug_id}',
		  'is_dependent_on_item_id' => '$$depend{is_dependent_on_task_id}',
		  'is_dependent_on_item_id_artifact' => '"task"'
		 );


if ($flag_verbose) {
  print "migrating bug to bug dependencies...\n";
}
open SQLOUT,">$sqlfile" or die "Could not open File";

# loop over all bug to bug dependencies
my $counter=0;
my $sth = $dbh->prepare("SELECT bug_id,is_dependent_on_bug_id FROM ".
			"bug_bug_dependencies LIMIT ?,1");
while(1) {
  $sth->execute($counter);
  $counter++;
  $depend=$sth->fetchrow_hashref;

  if(!$depend) {last};

  # don't migrate any erroneous dependencies on item 100. This
  # ID is reserved for the 'none' item.
  if($$depend{is_dependent_on_bug_id}==100) {next};

  my $comm=build_sql_insert_string("bugs_dependencies",\%bug_bug_dep_map);
  $query=eval("sprintf($comm)");
  print SQLOUT "$query;\n";
  $sth_mig=$dbh_tgt->prepare($query);
  $sth_mig->execute;

}

close SQLOUT;
if ($flag_verbose) {
  print "\n       RESULT: migrated $counter items\n";
}



if ($flag_verbose) {
  print "migrating bug to task dependencies...\n";
}
open SQLOUT,">$sqlfile" or die "Could not open File";

# loop over all bug to task dependencies
my $counter=0;
my $sth = $dbh->prepare("SELECT bug_id,is_dependent_on_task_id FROM ".
			"bug_task_dependencies LIMIT ?,1");
while(1) {
  $sth->execute($counter);
  $counter++;
  $depend=$sth->fetchrow_hashref;

  if(!$depend) {last};

  # don't migrate any erroneous dependencies on item 100. This
  # ID is reserved for the 'none' item.
  if($$depend{is_dependent_on_task_id}==100) {next};


  my $comm=build_sql_insert_string("bugs_dependencies",\%bug_task_dep_map);
  $query=eval("sprintf($comm)");
  print SQLOUT "$query;\n";
  $sth_mig=$dbh_tgt->prepare($query);
  $sth_mig->execute;

}

close SQLOUT;
if ($flag_verbose) {
  print "\n       RESULT: migrated $counter items\n";
}


# SUPPORT TRACKER

my $support_id;
my $sr;

# mappings for the status ids  from the old support_status table to
# the new status values
#   1 open =>   1 open
#   2 closed => 3 closed
#   3 deleted => 3 closed + set resolution to 2 'invalid'
my %sup_statusmap= ( 0 => 100,
		     1 => 1,
		     2 => 3,
		     3 => 3,
		   100 => 100);


# for easier readability, the commands for moving the data base
# entries are built from these hashes
my %support_map = ( 'bug_id' => '$support_id',
		    'group_id' => '$$sr{group_id}',
		    'status_id' => '$sup_statusmap{ $$sr{support_status_id} }',
		    'severity' => '"3"',
		    'category_id' => '$$sr{support_category_id}',
		    'priority' => '$$sr{priority}',
		    'submitted_by' => '$$sr{submitted_by}',
		    'assigned_to' => '$$sr{assigned_to}',
		    'date' => '$$sr{open_date}',
		    'summary' => '$$sr{summary}',
		    'close_date' => '$$sr{close_date}',
		    'resolution_id' =>'$$sr{resolution}'
		  );


# we assume the comment 'type' to be of id 100
my %support_message_map =('bug_id' => '$support_id',
			  'field_name' => '"details"',
			  'old_value' => '$$message{body}',
			  'mod_by' => '$$message{user_id}',
			  'date' => '$$message{date}',
			  'type' => '"100"'
			 );

if ($flag_verbose) {
  print "migrating support tracker items...\n";
}

# loop over all support entries and migrate them
my $counter=0;
my $sth = $dbh->prepare("SELECT support_id FROM support LIMIT ?,1");
while(1) {
  $sth->execute($counter);
  $counter++;
  $row=$sth->fetchrow_hashref;

  if(!$row) {last};

  open SQLOUT,">$sqlfile" or die "Could not open File";

  $support_id=$$row{support_id};

  $sr=read_old_support_request($dbh,$support_id);
  if($support_id==100) {
    print stderr "WARNING: cannot move support item 100, because 100 is a ".
      "reserved value in the target data base.\nContents were:\n".
	Dumper($sr);
    close SQLOUT;
    next;
  }

  # if the old status was set to 'deleted' it was decided that this should
  # map now to status 'closed' + resolution 'fixed'
  $$sr{resolution}=100;
  if($$sr{support_status_id}==3) { $$sr{resolution}=2 };

  my $comm=build_sql_insert_string("support",\%support_map);
  $query=eval("sprintf($comm)");
  print SQLOUT "$query;\n";
  $sth_mig=$dbh_tgt->prepare($query);
  $sth_mig->execute;


  # The first entry of the support_messages now goes into the details
  # field of the request. The remaining followup messages go into the
  # support_history table
  # The original support_history entries will have been moved by the
  # dbmapper.pl script.
  my $message;
  $message = shift @{$$sr{messages}};
  $query="UPDATE support SET details=".$dbh->quote($$message{body}).
    " WHERE bug_id=$support_id";
  print SQLOUT "$query;\n";
  $sth_mig=$dbh_tgt->prepare("$query");
  $sth_mig->execute;

  my $comm = build_sql_insert_string("support_history",\%support_message_map);
  while ($message = shift @{$$sr{messages}}) {
#      my $user_id=get_userid_from_email($dbh,$$message{from_email});
#     ## if this failed then try to test whether there exists a
#     ## user with username = first part of mail address
#     if($user_id==100) {
#       my $username = "none";
#       ($username = $$message{from_email}) =~ s/(.*)@.*/$1/;
#       $user_id=get_userid_from_username($dbh,$username);
#       if ($flag_verbose) {
# 	print "  support message $support_id: ".
# 	  "trying to match $$message{from_email}: $username => $user_id\n";
#       }
#     }

    $query=eval("sprintf($comm)");
    print SQLOUT "$query;\n";
    $sth_mig=$dbh_tgt->prepare($query);
    $sth_mig->execute;

  }


  close SQLOUT;
}
if ($flag_verbose) {
  print "\n       RESULT: migrated $counter items\n";
}


# TASK TRACKER

# mappings for the status id from old project_status table to the
# new status values
# 1 open     => 1 open
# 2 closed   => 3 closed
# 3 deleted  => 3 closed set resolution to 2 'invalid'
# 100 none   => 100 none

my %task_statusmap = (1 => 1,
		      2 => 3,
		      3 => 3,
		      100 => 100
		     );

# for easier readability, the commands for moving the data base
# entries are built from these hashes
my %task_map = ( 'bug_id' => '$task_id',
		 'group_id' => '$$task{group_id}',
		 'category_id' => '$$task{group_project_id}',
		 'summary' => '$$task{summary}',
		 'details' => '$$task{details}',
		 'percent_complete' => '$$task{percent_complete}',
		 'priority' => '$$task{priority}',
		 'hours' => '$$task{hours}',
		 'planned_starting_date','$$task{start_date}',
		 'planned_close_date'=>'$$task{end_date}',
		 'submitted_by' => '$$task{created_by}',
		 'assigned_to' => '$$task{assigned_to}',
		 'status_id' => '$task_statusmap{ $$task{status_id} }',
		 'resolution_id' => '$$task{resolution}'
	       );

my %task_history_map=( 'bug_id' => '$task_id',
			'field_name' => '$$hist{field_name}',
			'old_value' => '$$hist{old_value}',
			'mod_by' => '$$hist{mod_by}',
			'date'  => '$$hist{date}',
			'type'  => '$$hist{type}'
			);

my %task_dep_map=('item_id' => '$task_id',
	       'is_dependent_on_item_id' => '$$depend{is_dependent_on_task_id}',
	       'is_dependent_on_item_id_artifact' => '"task"'
	      );


if ($flag_verbose) {
  print "migrating task tracker items...\n";
}
# loop over all task entries and migrate them
my $counter=0;
my $sth = $dbh->prepare("SELECT project_task_id FROM project_task LIMIT ?,1");
while (1) {
  $sth->execute($counter);
  $counter++;
  $row=$sth->fetchrow_hashref;

  if(!$row) {last};

  open SQLOUT,">$sqlfile" or die "Could not open File";

  my $task_id=$$row{project_task_id};

  my $task=read_old_task_entry($dbh,$task_id);
  if($task_id==100) {
    print stderr "WARNING: cannot move task 100, because 100 is a ".
      "reserved value in the target data base.\nContents were:\n".
	Dumper($task);
    close SQLOUT;
    next;
  }

  # if the old status was set to 'deleted' it was decided that this should
  # map now to status 'closed' + resolution 'fixed'
  $$task{resolution}=100;
  if($$task{status_id}==3) { $$task{resolution}=2 };

  my $comm=build_sql_insert_string("task",\%task_map);
  $query=eval("sprintf($comm)");
  print SQLOUT "$query;\n";
  $sth_mig=$dbh_tgt->prepare($query);
  $sth_mig->execute;

  # the project_history can be mapped one to one onto the target table.
  # The additional 'type' field in the new tracker structure identifies
  # the kind of followup comment (only needed for entries with
  # field_name='details') and can be set to its default '100'.
  my $hist;
  foreach $hist ( @{$$task{history}}) {
    if($$hist{field_name} eq 'details') {
      $$hist{type}=100;
    }
    my $comm=build_sql_insert_string("task_history",\%task_history_map);
    $query=eval("sprintf($comm)");
    print SQLOUT "$query;\n";
    $sth_mig=$dbh_tgt->prepare($query);
    $sth_mig->execute;
  }

  # moving the task to task dependencies
  # CAREFUL: This assumes that the item ids inside the tracker did
  # not change
  my $comm = build_sql_insert_string("task_dependencies",\%task_dep_map);
  while( my $depend = shift @{$$task{dependency}}) {
    # don't migrate any erroneous dependencies on item 100. This
    # ID is reserved for the 'none' item.
    if($$depend{is_dependent_on_task_id}==100) {next};

    $query=eval("sprintf($comm)");
    print SQLOUT "$query;\n";
    $sth_mig=$dbh_tgt->prepare($query);
    $sth_mig->execute;

  }


  close SQLOUT;

}
if ($flag_verbose) {
  print "\n       RESULT: migrated $counter items\n";
}


# 1 open     => 1 open
# 2 closed   => 3 closed
# 3 deleted  => 3 closed and set resolution to 2 'invalid'
# 4 postponed => 1 open and set resolution to 4 'later'
# 100 none   => 100 none
my %patch_statusmap = (1 => 1,
		       2 => 3,
		       3 => 3,
		       4 => 1,
		       100 => 100
		       );
my %patch_map = ( 'bug_id' => '$patch_id',
		  'group_id' => '$$patch{group_id}',
		  'status_id' => '$patch_statusmap{$$patch{patch_status_id}}',
		  'category_id' => '$$patch{patch_category_id}',
		  'summary' => '$$patch{summary}',
		  'details' => '$details',
		  'submitted_by' => '$$patch{submitted_by}',
		  'date' => '$$patch{open_date}',
		  'close_date' => '$$patch{close_date}',
		  'assigned_to' => '$$patch{assigned_to}'
		  );
my %patch_history_map=( 'bug_id' => '$patch_id',
			'field_name' => '$$hist{field_name}',
			'old_value' => '$$hist{old_value}',
			'mod_by' => '$$hist{mod_by}',
			'date'  => '$$hist{date}',
			'type'  => '$$hist{type}'
			);

my %patch_file_map=( 'bug_id' => '$patch_id',
		     'submitted_by' => '$$patch{submitted_by}',
		     'date' => '$$patch{open_date}',
		     'description' => '"patch item ${patch_id}"',
		     'file' => '$$patch{code}',
		     'filename' => '"patch_item_${patch_id}.patch"',
		     'filesize' => '$flength',
		     'filetype' => '"text/plain"'
		     );

if ($flag_verbose) {
  print "migrating patch tracker items...\n";
}


# loop over all patch entries and migrate them
my $counter=0;
my $sth = $dbh->prepare("SELECT patch_id FROM patch LIMIT ?,1");
while(1) {
  $sth->execute($counter);
  $counter++;
  $row=$sth->fetchrow_hashref;

  if(!$row) {last};

  open SQLOUT,">$sqlfile" or die "Could not open File";

  my $patch_id=$$row{patch_id};

  my $patch=read_old_patch_entry($dbh,$patch_id);
  if($patch_id==100) {
    print stderr "WARNING: cannot move patch item 100, because 100 is a ".
      "reserved value in the target data base.\nContents were:\n".
	Dumper($patch);
    close SQLOUT;
    next;
  }

  # set the resolution field according to old status (look
  # at comments for the patch_statusmap hash above
  $$patch{resolution}=100;
  if($$patch{patch_status_id}==3) { $$patch{resolution}=2 };
  if($$patch{patch_status_id}==4) { $$patch{resolution}=4 };

  # the details field needs to be filled with the contents of
  # the first patch_history item of field_type 'details'
  my $hist;
  my $details;
  my @history_items;
  while($hist = shift (@{$$patch{history}}) ) {

    if($$hist{field_name} eq 'details') {
      if(!defined($details) ) {
	$details=$$hist{old_value};
	next;
      }
      $$hist{type}=100; # details field gets default type 100
    }
    push @history_items,$hist;
  }

  my $comm=build_sql_insert_string("patch",\%patch_map);
  $query=eval("sprintf($comm)");
  print SQLOUT "$query;\n";
  $sth_mig=$dbh_tgt->prepare($query);
  $sth_mig->execute;


  # migrate the remaining history entries
  my $comm=build_sql_insert_string("patch_history",\%patch_history_map);
  foreach $hist (@history_items) {
    $query=eval("sprintf($comm)");
    print SQLOUT "$query;\n";
    $sth_mig=$dbh_tgt->prepare($query);
    $sth_mig->execute;
  }

  # finally, fill the patch file table with the code, etc.
  my $flength=length($$patch{code});

  my $comm=build_sql_insert_string("patch_file",\%patch_file_map);
  $query=eval("sprintf($comm)");
  print SQLOUT "$query;\n";
  $sth_mig=$dbh_tgt->prepare($query);
  $sth_mig->execute;

  close SQLOUT;
}
if ($flag_verbose) {
  print "\n       RESULT: migrated $counter items\n";
}



#$sth->DESTROY;
$dbh_tgt->disconnect;

