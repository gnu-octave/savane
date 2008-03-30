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

# This script restores the database from a previously created backup
# copy.

use strict;
use Savannah;
use Getopt::Long;

my $getopt;
my $debug = 0;
my $file = "";

my $dbuser = GetConf("sys_dbuser");
my $dbname = GetConf("sys_dbname");
my $dbpasswd = GetConf("sys_dbpasswd");

eval {
  $getopt = GetOptions(
    "debug" => \$debug,
    "backup-dir=s" => \$file
  );
};

if ($file eq "")
  {
    print "You must specify the file which should be used for the recovery.\n";
    print "Example: $0 --backup-dir=database-backup\n";
    exit;
  }

unless (-d $file)
  {
    print "The file you specified does not exist.\n";
    exit;
  }

print "Recovering your savane database from $file\n";

# drop current database
if (system("mysql -e 'DROP DATABASE $dbname'") != 0) { die "mysql drop: $!" };
# recreate database
if (system("mysql -e 'CREATE DATABASE $dbname'") != 0) { die "mysql create: $!" };

opendir(SQLDIR, $file);
while (defined(my $table = readdir(SQLDIR))) {
    next unless $table =~ m/^.*\.sql$/;
    next if $table eq "utf8_converted_database.sql";
    `mysql $dbname -u$dbuser -p$dbpasswd < $file$table`;
}
closedir(SQLDIR);

print "(Ran in debug mode, nothing was actually really executed)\n" if $debug;
    
