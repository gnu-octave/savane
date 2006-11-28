#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2003 (c) Mathieu Roy <yeupou@gnu.org> 
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


##
## This script should be used via a cronjob to update the system
## by reading the database about groups.
##

use strict;
use Savane;
use Savane::Download;
use Savane::Cvs;
use Getopt::Long;
use Term::ANSIColor qw(:constants);
use POSIX qw(strftime);

# Import
our $sys_viewcvs_conffile;
our $sys_cron_viewcvs_forbidden;

my $script = "sv_viewcvs_forbidden";
my $logfile = "/var/log/sv_database2system.log";
my $getopt;
my $help;
my $debug;
my $cron;


# get options
eval {
    $getopt = GetOptions("help" => \$help,
			 "cron" => \$cron,
			 "debug" => \$debug);
};

if($help) {
    print STDERR <<EOF;
Usage: $0 [project] [OPTIONS] 

Update the viewcvs configuration file

  -h, --help                   Show this help and exit
  -d, --debug                  Do nothing, print everything
      --cron                   Option to set when including this script
                               in a crontab
  
Authors: Mathieu Roy <yeupou\@gnu.org>
EOF
exit(1);
}

# Test if we should run, according to conffile
exit if ($cron && ! $sys_cron_viewcvs_forbidden);
exit if (!$sys_viewcvs_conffile);

# Log: Starting logging
open (LOG, ">>$logfile");
print LOG strftime "[$script] %c - starting\n", localtime;


# Locks: This script should not run concurrently
AcquireReplicationLock();

#######################################################################
##
## Grabbing system informations,
## 
## - conffile forbidden items
##
#######################################################################

# $sys_viewcvs_conffile:
#    Get a list of item already in that list
my @etc_forbidden_groups;
open(CONFFILE, "< $sys_viewcvs_conffile") or die "Unable do open $sys_viewcvs_conffile";
while (<CONFFILE>)
{
    # We assume that the forbidden list in only one distinct line
    if (/^forbidden = (.*)$/) 
    {
	@etc_forbidden_groups = (@etc_forbidden_groups, split(", ", $1));
	print "DBG system: get \"$1\" from system\n" if $debug;
    }
}
close(CONFFILE);

print LOG strftime "[$script] %c - system infos grabbed\n", localtime;


#######################################################################
##
## Grabbing database informations, doing comparisons.
## 
## - db_groups items
##
#######################################################################

# db_groups:
#    Create a list of forbidden groups.
#    Additionally, create an hash to find easily which groups are 
#    in the database
#
#    To limit the number of request, we use only one very long SQL request. 
#
# Only groups in Active status will be handled!
my @db_forbidden_groups;
for (GetDB("groups", 
	   "status='A' AND is_public='0'",
	   "unix_group_name")) {
    chomp($_);
    push(@db_forbidden_groups, $_);
    print "DBG db: get $_ from database\n" if $debug;
}

print LOG strftime "[$script] %c - database infos grabbed\n", localtime;

#######################################################################
##
## Compare data in the system and in the database, build a
## nice complete list as one variable for easy inclusion in the
## conffile
##
#######################################################################
my %seen_before;
my $forbidden_groups;
my $forbidden_groups_total;
foreach my $group (sort(@db_forbidden_groups, @etc_forbidden_groups))
{
    unless ($seen_before{$group})
    {
	$seen_before{$group} = 1;
	$forbidden_groups .= " $group,";
	$forbidden_groups_total++;
    }
}

# remove the extra comma at the end of the list
chop($forbidden_groups);
print "DBG compare: final list is $forbidden_groups\n" if $debug;

print LOG strftime "[$script] %c - comparison done\n", localtime;


#######################################################################
##
## Finally, update the system
##
#######################################################################

open(CONFFILE, "< $sys_viewcvs_conffile") or die "Cannot open $sys_viewcvs_conffile for writing";
open(CONFFILENEW, "> $sys_viewcvs_conffile.new") or die "Cannot open $sys_viewcvs_conffile for writing";
while (<CONFFILE>) {
    # We assume that the private groups are private in whatever context,
    # (do not take care of specific cvsroots maybe configured in viewcvs)
    s/^forbidden \= (.*)$/forbidden =$forbidden_groups/g;
    print CONFFILENEW $_;
}
close(CONFFILE);
close(CONFFILENEW);
rename("$sys_viewcvs_conffile.new", "$sys_viewcvs_conffile");

print LOG strftime "[$script] %c ---- $sys_viewcvs_conffile updated ($forbidden_groups_total forbidden groups)\n", localtime;


# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";

# EOF
