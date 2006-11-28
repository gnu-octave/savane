#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2004-2005 (c) Mathieu Roy <yeupou--gnu.org>
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
#

# This script assume that private list are accessed at /private/

use strict;
use Savane::Frontend;

our $sys_unix_group_name;
my $session_hash = cookie("session_hash");
my $session_uid = cookie("session_uid");

my $siteurl = '<a href="http://'.GetConf("sys_default_domain").'">'.GetConf("sys_name").'</a>';

######## Grant access
# Check if a session exists on the client side
PrintExit("Unauthorized Access", "Sorry, you have no access to that area. You must be logged-in at $siteurl first.") 
    unless ($session_hash && $session_uid);

# Check if the session exists on the server side
my @server_session = CheckSession($session_hash, $session_uid);
PrintExit("Unauthorized Access", "Sorry, you have no access to that area. You must be logged-in at $siteurl first.")
    unless (@server_session);

# If valid, set the user_id
my $user_id = $session_uid;

# Find out which file is wanted
# The second part of the request url must be a valid list name
my $list_name = $1 if $ENV{REQUEST_URI} =~ /^\/private\/([A-Za-z1-9-]+)\/.*$/;
	
# Check if the listname was found
# If it was not found, check if by any chance we are not at the address
#   /private/list that should be redirected to /private/list/
if (!$list_name && $ENV{REQUEST_URI} =~ /^\/private\/([A-Za-z1-9-]+)$/) {
    print redirect($ENV{REQUEST_URI}."/"); 
}
PrintExit("Page not found", "No list name passed in the url.")
    unless $list_name;

# Find out to which group the list belongs and if the list exists
my $group_id = GetDBSettings("mail_group_list", 
			     "list_name='$list_name'", 
			     "group_id");

# No group was found, assume that the list does not exists
PrintExit("Page not found", "No list exists with the name found in the url ($list_name).")
    unless $list_name;



# Check if the user belongs to that group, or check if the user
# is member of the site admin group
my $is_valid = GetDBSettings("user_group", 
			     "group_id='$group_id' AND user_id='$user_id' AND admin_flags<>'P'",
			     "user_group_id");
unless ($is_valid) {
    $is_valid = GetDBList("user_group", "group_id='".GetGroupSettings($sys_unix_group_name, "group_id")."' AND admin_flags='A'", "user_group_id");
    
}

# Not valid after checks? Deny access
PrintExit("Unauthorized Access", "Sorry, you have no access to that area. You are not member of the group.")
    unless $is_valid;

		
######## Return real content

# We get here, security checks are ok, now we serve the 
# page.

# Find out which page is wanted
my $file = $1 if $ENV{REQUEST_URI} =~ /^\/private\/$list_name\/(.*)$/;

# If we find .., assume there is something wrong in the page and exit
PrintExit("Unauthorized Access", "Unacceptable characters found in the url.")
    if $file =~ /\.\./;

# If the file is a directory (or do not exists), 
# assume that we want the index within.
if (-d "/var/www/private/$list_name/$file" || ! $file) {
    $file .= "index.html";
}
		    
# Try to open the file
PrintExit("Error", "Unable to find the requested file ($file).")
    unless (-r "/var/www/private/$list_name/$file");
open(FILE, "< /var/www/private/$list_name/$file")
    or PrintExit("Error", "Unable to open the requested file ($file).");

# Print it
print header();
while (<FILE>) {
    print $_;
}
close(FILE);
exit;


# EOF
