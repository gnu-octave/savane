#!/usr/bin/perl
# Add missing group history event, useful for very old Savane installation,
# anterior to 1.0.0.
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
# Add a tailing '###' in table user, field authorized_keys

# Previous version of Savane did not add a trailing new line in
# authorized_keys, causing the .ssh/authorized_keys file to be
# recreated at each cron job - with no harm but additional CPU and HD
# usage

use strict;
use Savannah;
use Getopt::Long;

my $getopt;
my $debug = 0;

eval {
    $getopt = GetOptions("debug" => \$debug); 
};


# Get list of group <-> associations
foreach my $line (GetDB('user_group', 'admin_flags <> "P"', 'user_id, group_id')) {
    chomp($line);
    my ($user_id, $group_id) = split(",", $line);
    my $user = GetUserName($user_id);
    # Check if there is a relevant history bit
    unless (GetDB('group_history', 
		  '(field_name="Added User" OR field_name="Approved User") AND old_value="'.$user.'" AND group_id="'.$group_id.'"',
		  'group_history_id'))
    {
	print "No history found for $user ($user_id), member of group $group_id\n" if $debug;
	our $dbd;

	my $sql = "INSERT INTO group_history (group_id,field_name,old_value,date) VALUES (".$group_id.",'Added User','".$user."',0)";
	$dbd->do($sql) unless $debug;

    }
   

}

