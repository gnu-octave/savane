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

# This script creates a complete dump of the savane database,
# to have a backup in emergency cases.

use strict;
use Savannah;
use Getopt::Long;

my $getopt;
my $debug = 0;
my $folder = "./";

my $dbuser = GetConf("sys_dbuser");
my $dbname = GetConf("sys_dbname");
my $dbpasswd = GetConf("sys_dbpasswd");

our $dbd;

eval {
  $getopt = GetOptions("debug" => \$debug, "folder=s" => \$folder);
};


# determine the file name for the backup
if (substr($folder, -1) ne "/")
  {
    $folder .= "/";
  }
my $backup = "database-backup";
my $count = 1;
while (-e $folder.$backup)
  {
    $backup = sprintf("database-backup_%03d", $count);
    $count += 1;
  }

print "Creating a backup directory of your database:\n";
print "\t\"$folder$backup\"\n";
unless ($debug)
{
    system("mkdir", "-p", "$folder$backup");
    
    open SQLDUMP, "> $folder$backup/README";
    print SQLDUMP "--\n-- This is a backup directory of your existing savane database.\n";
    print SQLDUMP "--\n-- In the unlikely event of an emergency, you can restore\n";
    print SQLDUMP "-- your database by using this directory together with the script\n";
    print SQLDUMP "-- recover_database.pl, specifying the --backup-dir command line switch.\n";
    print SQLDUMP "--\n-- Example: \$ ./recover_database.pl --backup-dir=$folder$backup\n";
    print SQLDUMP "--\n\n";
    
    close SQLDUMP;
    
    # --hex-blob would have been nice, but it only in recent mysqldump
    # So we make a dump for each table and we will select later which one
    # must be avoided because it contains binary content.
#    our $dbd = DBI->connect('DBI:mysql:database='.$dbname,
#			    $dbuser, 
#			    $dbpasswd);
    my $hop = $dbd->prepare("SHOW TABLES");
    $hop->execute();
    while (my $table = $hop -> fetchrow_array) {
	# to noisy; print "\tdump table $table into $folder$backup/$table.sql\n";
	`mysqldump $dbname $table -u$dbuser -p$dbpasswd > $folder$backup/$table.sql`;
    }
    
}

print "(Ran in debug mode, nothing was actually really executed)\n" if $debug;
