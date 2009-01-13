#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$ 
#
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
# This file is part of Savane.
# 
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA#

# This file will make a list of sql clean files, given a nice savane database.
# Indeed, you must have a nice and clean database, which will have the exact
# same structure as the expected one, not a messed up development thing with.
# Make sure the version of Savane lib installed matched the release to 
# produce.
#
# IT SHOULD REALLY BE AN EMPTY DATABASE, WITH NO USER ACCOUNT OR WHATEVER.
# Because we will also export database content, needed for initvalues files.
# If you dont understand any of this, please ask to savane-dev before 
# committing anything.
#
# How it should be used:
#    - create a database from the previous version
#    - run all the update scripts provided in the newcoming release
#    - run this script and commit the new files

use strict;
use warnings;
use Savane;
use POSIX qw(strftime);

our $dbd;
my $dbuser = GetConf("sys_dbuser");
my $dbname = GetConf("sys_dbname");
my $dbpasswd = GetConf("sys_dbpasswd");

my $version = GetVersion();
my $table_prefix="table_";
my $structure_suffix=".structure";
my $initvalues_suffix=".initvalues";


####################################
# Conf to be edited

# Path to the db directory
my $output_path="/usr/src/savane/db/mysql";
# Tables that will have no initvalues in anyway
# Unused for now
#my %table_with_no_initvalues;


####################################
# Build header sub
my $mysqldumpversion = `mysqldump --version`;
chomp($mysqldumpversion);

# Takes as argument:
#  arg0 : the name of the dumpfile
#  arg1 : the suffix of the dumpfile
sub BuildHeader {
    open(SQLDUMP, "> $output_path/$table_prefix".$_[0].$_[1]);
    print SQLDUMP "# This file was generated for Savane $version.
#
# This is an SQL file necessary to create the table $_[0] part of a
# Savane database.
# 
# Check $_[0].README for specifics about this table.
# (if this file does not exist, there is nothing specific)
# 
# Build by $mysqldumpversion
# 
# Go at <https://gna.org/projects/savane> if you need information 
# about Savane.

";
    close(SQLDUMP);
}


####################################
# check every table of the database
my $hop = $dbd->prepare("SHOW TABLES");
$hop->execute();
while (my $table = $hop -> fetchrow_array) {
    # Extract the table structure
    print "Extract $table structure... ";
    BuildHeader($table, $structure_suffix);
    my $dumpfile = "$output_path/".$table_prefix.$table.$structure_suffix;
    `mysqldump --compatible="mysql323,mysql40" --skip-comments --allow-keywords --compact --no-data --complete-insert $dbname $table -S /tmp/savane-mini/mysql/sock >> $dumpfile`;
    print "done\n";

    # Extract the table init values
    #print "Extract $table initvalues... ";
    #BuildHeader($table, $initvalues_suffix);
    #my $dumpfile = "$output_path/".$table_prefix.$table.$initvalues_suffix;
    #`mysqldump --compatible="mysql323,mysql40" --skip-comments --allow-keywords --compact  --no-create-db --no-create-info --complete-insert $dbname $table -S /tmp/savane-mini/mysql/sock >> $dumpfile`;
    #print "done\n";
}


# Now take a look a each file and find out if there is anything but blank 
# lines and comments. If not, trash the file
my @useless_files;
opendir(OUTPUT_PATH, $output_path);
while (defined(my $dumpfile = readdir(OUTPUT_PATH))) {
    # deal only with sql files: structure and initvalues
    next unless $dumpfile =~ m/^.*\.(structure|initvalues)$/;
    
    # open the file and look into it. Set the useful flag only if we found
    # a line which is not a comment or a blank line
    my $is_useful;
    open(DUMPFILE, "< $output_path/$dumpfile");
    while (<DUMPFILE>) {
	next if m/^\#/;
	next if m/^\-\-/;
	next if m/^\/\*\!/;
	next if m/^$/;
	$is_useful = 1;
	last;	
    }
    close(DUMPFILE);
    push(@useless_files, "$output_path/$dumpfile") unless $is_useful;
}

if (scalar(@useless_files)) {
    print "Remove the ".scalar(@useless_files)." useless files... ";
    unlink(@useless_files);
    print "done\n";
}

# EOF

