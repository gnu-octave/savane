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

#
# This script was written to correct the ID numbers for the group_id,
# bug_id and task_id records. When initially installing Savannah for
# CERN, the scripts did not correctly create the initial values for
# the groups, bug, and task tables. All Savannah installations I have
# seen seem to suffer from this problem.
# The value 100 has a special meaning in many tables of Savannah
# (usually it contains some sort of "none" value or a default value).
# No tracker item should get an ID < 100.
#
# Since the script fixes an unnatural condition of our data base it
# is tailored at specifically fixing our problems. No effort was made to make
# it a nicely structured or versatile program.
# So, if you want to use it because you have a similar problem, take care,
# and test the resulting data base!
#
# Note: the news tracker items are not treated. The news system has not
# yet been revised and this script is mainly targeted at correcting
# deficiencies in installations of the old structure data base before
# using the migration scripts which will migrate the now corrected
# data to the new data base structure.

use strict;
use DBI;
use Term::ReadKey;
use Getopt::Std;
# only for debugging: the Dumper module
use Data::Dumper;


sub usage {

print <<'EOF' ;

renumber_items.pl

   usage: renumber_items.pl [-c configuration_file] [ -g group offset]

 This script fixes an unnatural condition of the savannah data
 base. The problem arose from the fact that for a long period of time
 some of savannah's database initialization routines were
 incomplete.

 No effort was made to make this a nicely structured or particularly
 versatile program. It is tailored at specifically fixing our problems
 at CERN. So, if you want to use it because you have a similar
 problem, look whether you need to modify it.

 The script will
     - move bug entries with ID < 100 to the top of the stack
     - move task entries with ID < 100 to the top of the stack
     - renumber the group IDs by adding an offset [-g option]to
       them and accordingly update all references

EOF
}
############################
# match_patterns
# usage: test=match_patterns(string,patterns)
#
# input parameters:
#   string    string to be tested
#   patterns  reference to array containing regexp patterns
#
# return values:
# function will return true(1) if string matches any of the
# search patterns, false(0) otherwise
sub match_patterns {
  my $string=shift; # string to search
  my $patterns=shift; # reference to array of search patterns

  my $pattern;
  foreach $pattern (@$patterns) {
    if ($string =~ /$pattern/) {
      return 1;
    }
  }
  return 0;
}

#############################################################
# MAIN

# offset to be added to every group_id
my $groups_offset=100;
# tables to skip when updating references, usually for the
# reason that they will disappear in the migrated database,
# so no need to renumber them. CAREFUL: the strings are
# regular expressions, so "^activity" will match all
# activity_log, activity_log_old, activity_log_old_old.
my @skip_groupid_renumber=( "^groups",
			    "^activity_log",
			    );


my $flag_verbose;
my $conffilename;

# option parsing

my %option=();
getopts("c:g:hv",\%option);

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

if (defined $option{g}) {
  if($option{g} =~ /^\s*\d+\s*$/) {
    $groups_offset=$option{g};
  } else {
    die "ERROR: group offset argument must be a number  $option{g}\n";
  }
}

# source the data base information configuration file
our $src_dbname;
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

my $sth;
my $query;
my @groups;
my $counter;


#############################################################
#############################################################
#############################################################
# RENUMBERING OF THE BUG IDs

print "#################################################\n";
print "moving bugs with ID <= 100 to top of the ID stack\n";

# if there are less than 100 items, create the default item 100
# already now, so that the next inserts get IDs > 100
$sth=$dbh->prepare("SELECT MAX(bug_id) FROM bug");
$sth->execute;
my $entry_max= @{$sth->fetchrow_arrayref}[0];
if($entry_max>100) {
  $entry_max=100;
} else {
  $sth=$dbh->prepare("INSERT INTO bug (bug_id,group_id,status_id) VALUES (100,100,100)");
  $sth->execute;
}


my %bug_id_map; # maps old ids to new ids;

# migrate bug_ids 1-100 to top of the stack (get new id's)
$sth=$dbh->prepare("SELECT * FROM bug WHERE bug_id <= $entry_max");
$sth->execute;
$counter=0;
while (my $bug=$sth->fetchrow_hashref) {
  $counter++;
  my $bug_id=$$bug{bug_id};

  delete $$bug{bug_id};
  my ($field,$fieldlist,$valuelist);
  foreach $field (keys %$bug) {
    if($$bug{$field} eq '') {next};
    $fieldlist.="$field,";
    $valuelist.=$dbh->quote($$bug{$field}).",";
  }
  chop $fieldlist;
  chop $valuelist;

  $query="INSERT INTO bug (".$fieldlist.") VALUES (".$valuelist.")";
  #print $query."\n";

  my $sthbug=$dbh->prepare($query);
  $sthbug->execute;

  # get back the newly assigned bug_id
  $sthbug=$dbh->prepare("SELECT LAST_INSERT_ID()");
  $sthbug->execute;

  my $newid=@{$sthbug->fetchrow_arrayref}[0];
  $bug_id_map{$bug_id}=$newid;

}
#print Dumper(\%bug_id_map);


# for each migrated bug, change the bug_id in the related tables
my $bug_id;
foreach $bug_id (keys %bug_id_map) {

  $sth=$dbh->prepare("UPDATE bug_cc SET bug_id=$bug_id_map{$bug_id} WHERE bug_id=$bug_id");
  $sth->execute;

  $sth=$dbh->prepare("UPDATE bug_file SET bug_id=$bug_id_map{$bug_id} WHERE bug_id=$bug_id");
  $sth->execute;

  $sth=$dbh->prepare("UPDATE bug_history SET bug_id=$bug_id_map{$bug_id} WHERE bug_id=$bug_id");
  $sth->execute;

  $sth=$dbh->prepare("UPDATE bug_task_dependencies SET bug_id=$bug_id_map{$bug_id} WHERE bug_id=$bug_id");
  $sth->execute;

  # dont't update dependencies relating to bug_id=100 !!!

  if($bug_id==100) {next};
  $sth=$dbh->prepare("UPDATE bug_bug_dependencies SET bug_id=$bug_id_map{$bug_id} WHERE bug_id=$bug_id");
  $sth->execute;

  $sth=$dbh->prepare("UPDATE bug_bug_dependencies SET is_dependent_on_bug_id=$bug_id_map{$bug_id} WHERE is_dependent_on_bug_id=$bug_id");
  $sth->execute;

}

# erase all the copied bug entries
foreach $bug_id (keys %bug_id_map) {
  $sth=$dbh->prepare("DELETE FROM bug WHERE bug_id=$bug_id");
  $sth->execute;
}
# if $entry_max was 100, this item has been moved and we need to set
# the correct default initialization value for record 100
if($entry_max==100) {
  $sth=$dbh->prepare("INSERT INTO bug (bug_id,group_id,status_id) VALUES (100,100,100)");
  $sth->execute;
}

print "     moved $counter entries\n";


#############################################################
#############################################################
#############################################################
# RENUMBERING OF THE TASK IDs

print "#################################################\n";
print "moving tasks with ID <= 100 to top of the ID stack\n";

# if there are less than 100 items, create the default item 100
# already now, so that the next inserts get IDs > 100
$sth=$dbh->prepare("SELECT MAX(project_task_id) FROM project_task");
$sth->execute;
my $entry_max= @{$sth->fetchrow_arrayref}[0];
if($entry_max>100) {
  $entry_max=100;
} else {
  $sth=$dbh->prepare("INSERT INTO project_task (project_task_id,status_id) VALUES (100,100)");
  $sth->execute;
}

my %task_id_map; # maps old ids to new ids;

# migrate task_ids 1-100 to top of the stack (get new id's)
$sth=$dbh->prepare("SELECT * FROM project_task WHERE project_task_id <= $entry_max");
$sth->execute;

$counter=0;
while (my $task=$sth->fetchrow_hashref) {
  $counter++;
  my $task_id=$$task{project_task_id};

  delete $$task{project_task_id};
  my ($field,$fieldlist,$valuelist);
  foreach $field (keys %$task) {
    if($$task{$field} eq '') {next};
    $fieldlist.="$field,";
    $valuelist.=$dbh->quote($$task{$field}).",";
  }
  chop $fieldlist;
  chop $valuelist;

  $query="INSERT INTO project_task (".$fieldlist.") VALUES (".$valuelist.")";
  #print $query."\n";

  my $sthtask=$dbh->prepare($query);
  $sthtask->execute;

  # get back the newly assigned task_id
  $sthtask=$dbh->prepare("SELECT LAST_INSERT_ID()");
  $sthtask->execute;

  my $newid=@{$sthtask->fetchrow_arrayref}[0];
  $task_id_map{$task_id}=$newid;

}


# for each migrated task, change the task_id in the related tables
my $task_id;
foreach $task_id (keys %task_id_map) {

  $sth=$dbh->prepare("UPDATE project_assigned_to SET project_task_id=$task_id_map{$task_id} WHERE project_task_id=$task_id");
  $sth->execute;

  $sth=$dbh->prepare("UPDATE project_history SET project_task_id=$task_id_map{$task_id} WHERE project_task_id=$task_id");
  $sth->execute;


  # dont't update dependencies relating to task_id=100 !!!
  if($task_id==100) {next};

  $sth=$dbh->prepare("UPDATE bug_task_dependencies SET is_dependent_on_task_id=$task_id_map{$task_id} WHERE is_dependent_on_task_id=$task_id");
  $sth->execute;

  $sth=$dbh->prepare("UPDATE project_dependencies SET is_dependent_on_task_id=$task_id_map{$task_id} WHERE is_dependent_on_task_id=$task_id");
  $sth->execute;

  $sth=$dbh->prepare("UPDATE project_dependencies SET is_dependent_on_task_id=$task_id_map{$task_id} WHERE is_dependent_on_task_id=$task_id");
  $sth->execute;

}

# erase all the copied task entries
foreach $task_id (keys %task_id_map) {
  $sth=$dbh->prepare("DELETE FROM project_task WHERE project_task_id=$task_id");
  $sth->execute;
}


# if $entry_max was 100, this item has been moved and we need to set
# the correct default initialization value for record 100
if($entry_max==100) {
  $sth=$dbh->prepare("INSERT INTO project_task (project_task_id,status_id) VALUES (100,100)");
  $sth->execute;
}

print "     moved $counter entries\n";

#############################################################
#############################################################
#############################################################
# RENUMBERING OF THE PATCH IDs

print "#################################################\n";
print "moving patch entries with ID <= 100 to top of the ID stack\n";


# if there are less than 100 items, create the default item 100
# already now, so that the next inserts get IDs > 100
$sth=$dbh->prepare("SELECT MAX(patch_id) FROM patch");
$sth->execute;
my $entry_max= @{$sth->fetchrow_arrayref}[0];
if($entry_max>100 or !defined($entry_max)) {
  $entry_max=100;
} else {
  $sth=$dbh->prepare("INSERT INTO patch (patch_id,patch_status_id) VALUES ('100','100')");
  $sth->execute;
}


my %patch_id_map; # maps old ids to new ids
$sth=$dbh->prepare("SELECT * FROM patch WHERE patch_id <= $entry_max");
$sth->execute;

$counter=0;
while (my $patch=$sth->fetchrow_hashref) {
  $counter++;
  my $patch_id=$$patch{patch_id};

  delete $$patch{patch_id};
  my ($field,$fieldlist,$valuelist);
  foreach $field (keys %$patch) {
    if($$patch{$field} eq '') {next};
    $fieldlist.="$field,";
    $valuelist.=$dbh->quote($$patch{$field}).",";
  }
  chop $fieldlist;
  chop $valuelist;

  $query="INSERT INTO patch (".$fieldlist.") VALUES (".$valuelist.")";
  my $sthpatch=$dbh->prepare($query);
  $sthpatch->execute;

  # get back the newly assigned patch_id
  $sthpatch=$dbh->prepare("SELECT LAST_INSERT_ID()");
  $sthpatch->execute;

  my $newid=@{$sthpatch->fetchrow_arrayref}[0];
  $patch_id_map{$patch_id}=$newid;
}

my $patch_id;
# erase all the copied task entries
foreach $patch_id (keys %patch_id_map) {
  $sth=$dbh->prepare("DELETE FROM patch WHERE patch_id=$patch_id");
  $sth->execute;
}

# for each migrated patch entry, change the patch_id in all related tables
foreach $patch_id (keys %patch_id_map) {
  $sth=$dbh->prepare("UPDATE patch_history SET patch_id=$patch_id_map{$patch_id} WHERE patch_id=$patch_id");
  $sth->execute;
}

# if $entry_max was 100, this item has been moved and we need to set
# the correct default initialization value for record 100
if($entry_max==100) {
  $sth=$dbh->prepare("INSERT INTO patch (patch_id,patch_status_id) VALUES ('100','100')");
  $sth->execute;
}

print "     moved $counter entries\n";



#############################################################
#############################################################
#############################################################
# RENUMBER THE GROUPS TABLE

if($groups_offset==0) {
  print "\nSkipping Group Table renumbering (groups_offset=$groups_offset)\n";
  exit;
}

print <<EOF;

######################################################################
RENUMBERING THE GROUP TABLE: offset=$groups_offset

The group ID 100 is reserved for the 'none' group. By convention there
should not be any group IDs below that value.
The following routines will renumber the group IDs by adding an offset
to every ID. Since the group_ids are referenced in many tables, the
script will identify all tables with columns containing group_ids and
change them accordingly.
EOF


# first apply the offset to the groups table
$sth=$dbh->prepare("SELECT group_id FROM groups ORDER BY group_id DESC");
$sth->execute;

print "\nrenumbering groups table...";
$query='UPDATE groups SET group_id=$new_id WHERE group_id=$$group_id[0]';
while(my $group_id = $sth->fetchrow_arrayref) {
  push @groups,$$group_id[0];

  my $new_id=$$group_id[0]+$groups_offset;
  #print eval("sprintf(\"$query\")")."\n";
  my $sthgroup=$dbh->prepare( eval("sprintf(\"$query\")") );
  $sthgroup->execute;
}
print "ok\n";

# now automatically get all tables which contain a column called
# group_id and update them.
$sth=$dbh->prepare("SHOW TABLES");
$sth->execute;

print "\nupdating references to group_id in other tables\n";
my $counter=0;
while (my $tablename = $sth->fetchrow_arrayref) {
  #print "> $$tablename[0]\n";

  #skip the groups table
  if(match_patterns($$tablename[0],\@skip_groupid_renumber)) {
    print "   skipping table $$tablename[0]\n";
    next;
  }

  my $sthcol=$dbh->prepare("DESCRIBE $$tablename[0]");
  $sthcol->execute;
  while (my $cols = $sthcol->fetchrow_hashref) {
    if($$cols{Field} =~ /^group_id$/) {
      $counter++;

      my $sthcount=$dbh->prepare("SELECT COUNT(group_id) FROM $$tablename[0]");
      $sthcount->execute;
      my $row=$sthcount->fetchrow_arrayref;
      my $nofcols=$$row[0];

      print "$counter  $$tablename[0]: $$cols{Field} ($nofcols entries)\n";

      my $query='UPDATE $$tablename[0] SET group_id=$target_id WHERE group_id=$group_id';
      my $group_id;
      foreach $group_id (@groups) {
	my $target_id=$group_id+$groups_offset;
	my $sthmove=$dbh->prepare( eval("sprintf(\"$query\")") );
	$sthmove->execute;
	#print eval("sprintf(\"$query\")") . "\n";
      }


    }
  }
}






$dbh->disconnect;
