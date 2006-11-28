#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: sv_extra_dead_projects.pl 6008 2006-09-29 08:25:54Z yeupou $
#
# Copyright 2003-2006 (C) Mathieu Roy <yeupou--gnu.org>
#                         BBN Technologies Corp
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
#

#use strict;
#use warnings;

use Getopt::Long;
use Savane;
use POSIX qw(strftime);
use Time::Local;
use Date::Calc qw(Add_Delta_YMD);

my $getopt;
my $help;
my $verbose;

my $only_list;
my $only_typeid;

my $delay = "4 month ago";


eval {
    $getopt = GetOptions("help" => \$help,
			 "verbose" => \$verbose,
			 "only-typeid=s" => \$only_typeid,
			 "delay=s" => \$delay);
};

if($help || !$getopt) {
    print STDERR <<EOF;
Usage: $0 [--help] [--verbose]

Identify project that seems to be dead, prompt the admin to set them as
pending.

      --help                 Show this help and exit

    Test conditions: 
      --delay=[string]       Delay in GNU date string (see -d option)
                             Default is $delay 
      --only-typeid=[n]      Check only project of group type n

EOF
 exit(1);
}

# Define epoch date of today
my ($year, $month, $day) = split(",", `date +%Y,%m,%d`);

# Armando L. Caro, Jr. <acaro--at--bbn--dot--com> 2/22/06
# Parse delay string to use perl function of delta calc, which is more portable
# than GNU's "date -d"
my ($num, $unit, $direction) = split(" ", $delay);
if ($unit =~ /week/) {
    $unit = "days";
    $num *= 7;
}
if ($direction =~ "ago") {
    $num = 0 - $num;
}
if ($unit =~ /day/) {
    ($year,$month,$day) = Add_Delta_YMD($year,$month,$day, 0,0,$num);
} elsif ($unit =~ /month/) {
    ($year,$month,$day) = Add_Delta_YMD($year,$month,$day, 0,$num,0);
} elsif ($unit =~ /year/) {
    ($year,$month,$day) = Add_Delta_YMD($year,$month,$day, $num,0,0);
} else {
    print STDERR "Cannot parse delay string: $delay\n";
    exit(1);
}
my $limit = timelocal("0","0","0",$day,($month-1),($year-1900));



# We'll begin by storing project name in list
my @maybe_dead;

# We may have several groups to deal with
my $typeid;
unless ($only_typeid) {
    @typeid = GetDBList("group_type", 0 , "type_id");
} else {
    @typeid = ($only_typeid);
}
foreach my $type (@typeid) {
    # Get group type settings.
    # We'll base our tests on these values.
    # Some projects may not respect group type defaults settings, but
    # it's surely better in this case to skip the test by using a command
    # line option than having this scripts make 6 SQL requests per project
    # more.
    #
    # We can also add more test as if group_type allow download area or not
    # but it's surely better to tell people to use relevant command line.
    my $type_name = GetGroupTypeName($type);


    # Get a list of good candidates
    my $criterion;
    $criterion .= "(short_description='' OR short_description IS NULL) AND type='$type' AND status='A'";
    my @candidates = GetDBList("groups",
			       $criterion,
			       "unix_group_name");
    
    my $count;
    foreach my $project (@candidates) {
	$count++;
	print "candidate $count: $project\n";

	my $project_id = GetGroupSettings($project, "group_id");

	# find out if there are items open 
	my @bugs = GetDBList("bugs", 
			     "group_id='$project_id'",
			     "bug_id");	
	my @task = GetDBList("task", 
			     "group_id='$project_id'",
			     "bug_id");
	my @patch = GetDBList("patch", 
			     "group_id='$project_id'",
			     "bug_id");
	my @support = GetDBList("support", 
			     "group_id='$project_id'",
			     "bug_id");


	# no items ? go deeper and check registration date
	if (@bugs < 1 && 
	    @task < 1 && 
	    @patch < 1 &&
	    @support < 1) {
	    
	    print "\t no items\n";
	    
	    
	    my $register_time = GetGroupSettings($project, "register_time");
	    
	    
	    
	    if ($register_time < $limit) {		
		
		print "\t too old\n";
		push(@maybe_dead, $project);
		
	    }
	}
    }
}


my @inactive;

foreach my $project (@maybe_dead) {
    print "\n$project seems to be dead on Savane. Set to pending?\n";
    print "(you should make sure other tools you provide are also unused)\n";
    print "Type \"y\" to set it as pending: ";
    my $set_to_private;
    chomp($set_to_private = <STDIN>);
    if ($set_to_private eq "y") {
	my $success = SetGroupSettings($project, "status", "P");
	print "-> $project is now pending\n" if $success;
	push(@inactive, $project);
    }
   
}

print "Here's the list of item you set as Inactive" if @inactive > 0;
foreach my $project (@inactive) {
    print $project."\n";
}

# End

