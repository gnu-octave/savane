#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: sv_export.pl 4807 2005-09-19 11:11:39Z yeupou $
#
#  Copyright 2005 (c) Mathieu Roy <yeupou--gnu.org>
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
use Getopt::Long;
use Term::ANSIColor qw(:constants);
use POSIX qw(strftime);
use Time::Local;
use File::Basename;
use File::Find::Rule;


# Configure
my $script = "sv_export_cleaner";
my $logfile = "/var/log/sv_export_cleaner.log";

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
 
   script that remove xml exports that were done and (removed by the user
   via the frontend).
   basically, it gets the list of exports known in the database and remove
   files that are not in this list.

   FIXME: it could remove files that are older than 2 weeks, but currently
   it does not.
   FIXME: it could remove empty directories, but currently it does not.
 
        --help                  print this help
        --xml-path=/            path of the generated xml file
 
Author: yves.perrin\@cern.ch, yeupou\@gnu.org
EOF
 exit(1);
}

# Log: Starting logging
open (LOG, ">>$logfile");
print LOG strftime "[$script] %c - starting\n", localtime;

# Obtain the list of exports, build a hash
my $export_table = 'trackers_export';
my $fields = 'export_id';
my $criteria = "1";
my @jobs = GetDB($export_table, $criteria, $fields);
my %known_jobs;
foreach my $job (@jobs) {
    $known_jobs{"$job"} = 1 unless $known_jobs{"$job"};
}

# Look at the list of files
my @current_files = File::Find::Rule->file()
    ->name("*.xml", "*.xsd")
    ->in("$xml_path");

my @to_be_removed;
foreach my $file (@current_files) {
    my ($file, $path, $suffix) = fileparse($file, (".xml", ".xsd"));
    push(@to_be_removed, "$path/$file.xml", "$path/$file.xsd") 
	unless $known_jobs{$file};
}

# Remove orphans
unlink(@to_be_removed);

print LOG strftime "[$script] %c - removed ".scalar(@to_be_removed)." old xml files.\n", localtime;


# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";

# EOF
