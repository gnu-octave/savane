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

##################################################################
# savannah_change_servername.pl
#
# script to change the server name specific information in the
# Savannah MySQL database tables. This makes it easier to move
# from one server to another.
#
#
# Author: Derek Feichtinger <derek.feichtinger@cern.ch>
##################################################################

use strict;
use DBI;
use Term::ReadKey;

#############################################################
#CONFIGURATION VARIABLES

# name of the MySQL Savannah data base
my $dbname="old_savannah";

# MySQL user with read/write access to the DB
my $username="root";

# old server name to be replaced
my $oldname="savannah.cern.ch";
# new server name
my $newname="lcgappdev.cern.ch";
#############################################################



##################################################################
# sub to search and replace a string in tables of a DBI data base
sub TableReplaceStr {
    my $dbh=shift;           #open database handle
    my $table=shift;         #table name
    my $tableID=shift;       #table row containing unique IDs
    my $replacefields=shift;  #field names to be searched (reference to an array
                              #   containing the field names)
    my $searchstr=shift;     #search string
    my $replacestr=shift;    #replace string

    my $sth;   #Statement handle object
    my $rv;    #return value
    my $ref;   #reference for result hash   
    my $query;
    my $name;
    my $counter;


    $query="SELECT $tableID," . join(",",@$replacefields) . " from $table";
    #print "query: $query\n";

    $sth = $dbh->prepare($query);
    $sth->execute();

    $counter=0;
    while ( $ref=$sth->fetchrow_hashref ) {
	foreach $name (keys %$ref) {
	    if ( $ref->{$name} =~ s/$searchstr/$replacestr/ ) {
		$query="UPDATE $table SET $name='$ref->{$name}' WHERE $tableID='$ref->{$tableID}'";
		$rv=$dbh->do($query);
		#print "query: $query\n\n";
		print "   field: $name  ==> $ref->{$name}\n";
		#print ";    DB return value: $rv\n";
		$counter++;
	    }

	}
    }
    print ">> $counter replacements in table $table\n";
}

#############################################################


# Read password
print "DB password for user $username: ";
ReadMode 'noecho';
my $password=ReadLine 0;
chomp $password;
ReadMode 'normal';
print "\n";


my $dbh;


$dbh = DBI->connect("DBI:mysql:$dbname:localhost:3306",
		    $username,$password,
		    { RaiseError => 1, AutoCommit => 1}
		    );

my @fields=("base_host","homepage_host","homepage_url",
	    "download_host","download_url");

TableReplaceStr($dbh,
		"group_type",
		"type_id",
		\@fields,
		$oldname,
		$newname
		);


my @fields=("homepage","html_cvs");

TableReplaceStr($dbh,
		"groups",
		"group_id",
		\@fields,
		$oldname,
		$newname
		);


my @fields=("bookmark_url");

TableReplaceStr($dbh,
                "user_bookmarks",
                "bookmark_id",
                \@fields,
                $oldname,
                $newname
                );


$dbh->disconnect();
