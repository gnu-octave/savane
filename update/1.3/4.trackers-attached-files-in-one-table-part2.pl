#!/usr/bin/perl
#
# Copyright (C) 2005  Mathieu Roy
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

# This script will copy files from old deprecated "task_file" an alike tables
# into trackers_file
# It is was strongly inspired by a part of update/1.0.0/renumber_items.pl

use strict;
our $dbd;
use Savane;


foreach my $tracker ("bugs", "task", "support", "patch") {
    my $sth=$dbd->prepare("SELECT * FROM ".$tracker."_file WHERE 1;");
    $sth->execute;
    my $counter=0;

    while (my $bug=$sth->fetchrow_hashref) {
	$counter++;
	my $bug_id=$$bug{bug_id};
	
	my ($field,$fieldlist,$valuelist);
	foreach $field (keys %$bug) {
	    if ($$bug{$field} eq '') 
	    { next; }

	    # Id will be regenerated
	    if ($field eq 'bug_file_id') 
	    { next; }

	    # bug_id has been renamed item_id
	    if ($field eq 'bug_id')
	    {
		$fieldlist.="item_id,";
	    } 
	    else 		
	    {
		$fieldlist.="$field,"; 
	    }
	    $valuelist.=$dbd->quote($$bug{$field}).",";

	}
#	chop $fieldlist;
#	chop $valuelist;
	
	my $query="INSERT INTO trackers_file (".$fieldlist."artifact) VALUES (".$valuelist."'".$tracker."')";

	my $sthinsert=$dbd->prepare($query);
	$sthinsert->execute;
    }
}


print "Files have been moved to the new table. 

If there was no SQL error, take a look at some sample files to see whether they were correctly migrated and available with the interface. 

If everything seems correct, you can remove old tables with the script trackers-attached-files-in-one-table-part3.sql\n";

