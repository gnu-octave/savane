#!/usr/bin/perl
#
# Copyright (C) 2005 Tobias Toedter
#
# <one line to give a brief idea of what this does.>
# 
# This file is part of Savane.
#
# Savane is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Savane; if not, write to the Free Software Foundation,
# Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

# This script converts a previously dumped savane database into
# UTF-8 and reinserts the contents into the mysql database.

use strict;
use Savannah;
use Getopt::Long;

my $getopt;
my $debug = 0;
my $input = "ISO-8859-1";
my $file = "";

# Import database handler for some specific SQL-queries
our $dbd;

eval {
  $getopt = GetOptions(
    "debug" => \$debug,
    "input=s" => \$input,
    "backup-dir=s" => \$file
  );
};

if ($file eq "")
  {
    print "You must specify the directory which should be used for the conversion.\n";
    print "Example: $0 --backup-dir=database-backup\n";
    exit;
  }

unless (-e $file)
  {
    print "The directory you specified does not exist.\n";
    exit;
  }

# now convert the file with iconv and feed the result into mysql
print "Converting your database from $input into UTF-8\n";
my $dbuser = GetConf("sys_dbuser");
my $dbname = GetConf("sys_dbname");
my $dbpasswd = GetConf("sys_dbpasswd");


unless ($debug) {
    # make sure there's nothing left from previous runs
    system("rm", "-f", "$file/utf8_converted_database.sql");

    # We must convert file after file, and then importing them if everything 
    # when smoothly
    # See bug #2850 for more details
    opendir(SQLDIR, $file);
    while (defined(my $sql = readdir(SQLDIR))) {
	# deal only with .sql files
	next unless $sql =~ m/^.*\.sql$/;
	# ignore files with attachments
	next if ($sql eq 'task_file.sql' or
		 $sql eq 'support_file.sql' or
		 $sql eq 'bugs_file.sql' or
		 $sql eq 'patch_file.sql');
	
	# convert
	# system() returns 0 on success, not 1, so we can't just use "or die" 
	if (system("iconv --from-code=$input --to-code=UTF-8 $file/$sql >> $file/utf8_converted_database.sql") != 0) { die "iconv: $!"; }
    }
    
    # We must not forget attachment, not converted but still to be included
    system("cat $file/task_file.sql >> $file/utf8_converted_database.sql");
    system("cat $file/bugs_file.sql >> $file/utf8_converted_database.sql");
    system("cat $file/support_file.sql >> $file/utf8_converted_database.sql");
    system("cat $file/patch_file.sql >> $file/utf8_converted_database.sql");
    
    # drop current database
    if (system("mysql -e 'DROP DATABASE $dbname'") != 0) { die "mysql drop: $!" };
    # recreate database
    if (system("mysql -e 'CREATE DATABASE $dbname'") != 0) { die "mysql create: $!" };

    # import converted database
    if (system("mysql $dbname < $file/utf8_converted_database.sql") != 0) { die "mysql import: $!" };
}


print "(Ran in debug mode, nothing was actually really executed)\n" if $debug;
