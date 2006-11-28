#!/usr/bin/perl
# Add a tailing new line '###' in table user, field authorized_keys
#
# Copyright (C) 2005  Sylvain Beucler
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


my @pairs = GetDB('user', 'authorized_keys not like "%###"', 'user_name, authorized_keys');
my %keys;
foreach my $pair (@pairs) {
    my ($user, @keylist) = split(",", $pair);
    my $allkeys = join("", @keylist);
    $keys{$user} = $allkeys;
    if($allkeys and not $allkeys =~ /\#\#\#$/) {
	print "$user\n" if $debug; # if you want to get a list of fixed users
	# a user can put ' in the SSH key comment and break the SQL command
	SetUserSettings($user, "authorized_keys", $allkeys . "###") unless $debug;
    }
}


print "(Ran in debug mode, nothing was actually really executed)\n" if $debug;
