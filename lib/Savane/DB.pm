#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2005 (c) Mathieu Roy <yeupou--gnu.org>
#                         Yves Perrin <yves.perrin--cern.ch>
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


##
## Desc: any subs related to database access...
## Usually used in other subs.
##

use strict;
require Exporter;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(GetDBDescribe GetDBSettings GetDBList GetDBAsIs GetDB GetDBAlt GetDBHash GetDBLists GetDBListsRef SetDBSettings DeleteDB InsertDB );
our $version = 1;

# Imports (needed for strict).
our $sys_dbname;
our $sys_dbhost;
our $sys_dbuser;
our $sys_dbpasswd;
our $sys_dbparams;

# Init
our $dbd =DBI->connect('DBI:mysql:database='.$sys_dbname
		       .':host='.$sys_dbhost
		       .':'.$sys_dbparams,
                       $sys_dbuser, $sys_dbpasswd,
                       { RaiseError => 1, AutoCommit => 1});

## Returns the name and type of every field of the table
# arg0 : table
sub GetDBDescribe {
    my $table = $_[0];
    my $hop;
    my @ret;

    $hop = $dbd->prepare("DESCRIBE ".$table);
    $hop->execute;
    while (my (@line) = $hop->fetchrow_array) {
        push(@ret, join(",", map {defined $_ ? $_ : "0"} @line));

    }
    $hop->finish;
    return @ret;
}

## Returns all settings  for a group, user (a row)..
# arg0 : table
# arg1 : criterion
# arg2 : fields to show (* for all)
sub GetDBSettings {
    my $table = $_[0];
    my $criterion;
    my $fields = "*";
 
    $criterion = "WHERE ".$_[1] if $_[1];
    $fields = $_[2] if $_[2];

    return $dbd->selectrow_array("SELECT ".$fields." FROM ".$table." ".$criterion);
}

## Returns a list of entries 
# arg0 : table
# arg1 : criterion
# arg2 : fields to show (* for all)
sub GetDBList {
    my $table = $_[0];
    my $criterion;
    my $fields = "*";

    $criterion = "WHERE ".$_[1] if $_[1];
    $fields = $_[2] if $_[2];

    my $list = $dbd->selectcol_arrayref("SELECT ".$fields." FROM ".$table." ".$criterion);

    return @$list;

}

## Returns a list of entries
# arg0 : sql command
sub GetDBAsIs {
    my $sql = $_[0];
    my $hop;
    my @ret;
                                                                                
    $hop = $dbd->prepare($sql);
    $hop->execute;
    while (my (@line) = $hop->fetchrow_array) {
        push(@ret, join(",", map {defined $_ ? $_ : "0"} @line));
    }
    $hop->finish;
    return @ret;
}

## Returns a list of entries 
# arg0 : table
# arg1 : criterion
# arg2 : fields to show (* for all)
sub GetDB {
    my $table = $_[0];
    my $criterion;
    my $fields = "*";
    my $hop;
    my @ret;

    $criterion = "WHERE ".$_[1] if $_[1];
    $fields = $_[2] if $_[2];

    $hop = $dbd->prepare("SELECT ".$fields." FROM ".$table." ".$criterion);
    $hop->execute;
    while (my (@line) = $hop->fetchrow_array) {
	push(@ret, join(",", map {defined $_ ? $_ : "0"} @line));
	
    }
    $hop->finish;
    return @ret;
}

## Returns a list of entries, like GetDB but with an unusual separator
# arg0 : table
# arg1 : criterion
# arg2 : fields to show (* for all)
sub GetDBAlt {
    my $table = $_[0];
    my $criterion;
    my $fields = "*";
    my $hop;
    my @ret;

    $criterion = "WHERE ".$_[1] if $_[1];
    $fields = $_[2] if $_[2];

    $hop = $dbd->prepare("SELECT ".$fields." FROM ".$table." ".$criterion);
    $hop->execute;
    while (my (@line) = $hop->fetchrow_array) {
	push(@ret, join("------SPLIT------", map {defined $_ ? $_ : "0"} @line));
	
    }
    $hop->finish;
    return @ret;
}

## Returns a hash of lists of entries
# arg0 : table
# arg1 : criterion
# arg2 : fields to show (* for all)
sub GetDBHash {
    my $table = $_[0];
    my $criterion;
    my $fields = "*";
    my $hop;
    my %ret;

    $criterion = "WHERE ".$_[1] if $_[1];
    $fields = $_[2] if $_[2];

    $hop = $dbd->prepare("SELECT ".$fields." FROM ".$table." ".$criterion);
    $hop->execute;
    my $count;
    while (my (@line) = $hop->fetchrow_array) {
	$ret{$count} = [@line];
	$count++;
    }
    $hop->finish;
    return %ret;
}



## Same purpose, but return true list 
# arg0 : table
# arg1 : criterion
# arg2 : fields to show (* for all)
sub GetDBLists {
    return @{GetDBListsRef(@_)};
}

## This one is faster, since it returns a reference to the array and
# hence doesn't make a copy of the array. It is a bit less intuitive
# to manipulate on the calling side, and is not consistent with other
# GetDB* functions, so it is a separate function.
sub GetDBListsRef {
    my $table = $_[0];
    my $criterion;
    my $fields = "*";
    my $hop;
    my @ret;

    $criterion = "WHERE ".$_[1] if $_[1];
    $fields = $_[2] if $_[2];

    $hop = $dbd->prepare("SELECT ".$fields." FROM ".$table." ".$criterion);
    $hop->execute;
    my $ret = $hop->fetchall_arrayref();
    $hop->finish;

    # fetchall_arrayref does not reuse the data pointed by the
    # reference in later calls (unlike fetchrow_arrayref), so it is
    # safe to use it.
    return $ret;
}

## Update settings for a group, user..
# arg0 : table
# arg1 : criterion
# arg2 : new values
sub SetDBSettings { 
    my $table = $_[0];
    my $criterion = $_[1];
    my $value = $_[2];

    return $dbd->do("UPDATE ".$table." SET ".$value." WHERE ".$criterion);
}


## Delete entries in database
# Use carefully
sub DeleteDB {
    my $table = $_[0];
    my $criterion = $_[1];

    unless ($table || $criterion) {
	return 0;
    }
    
    return $dbd->do("DELETE FROM ".$table." WHERE ".$criterion);
}

## Insert in database
# Use carefully
sub InsertDB {
    my $table = $_[0];
    my $fields = $_[1];
    my $values = $_[2];

    return $dbd->do("INSERT INTO ".$table." (".$fields.") VALUES (".$values.")");
}
