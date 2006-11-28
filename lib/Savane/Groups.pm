#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2003-20005 (c) Mathieu Roy <yeupou--gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
#

##
## Desc: any subs related to groups.
##

use strict;
require Exporter;

use Savane::Util;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(GetGroupSettings PrintGroupSettings SetGroupSettings GetGroupList DeleteGroup DeleteGroups GetGroupName GetGroupGPGKeyringFile StoreGroupGPGKeyring GroupAddGPGKey GetGroupUsers PrintGroupUsers GetGroupAdmins PrintGroupAdmins GetGroupAdminsMail PrintGroupAdminsMail GetGroupType PrintGroupType GetGroupTypeSettings PrintGroupTypeSettings GetGroupTypeName );
our $version = 1;

# Imports (needed for strict).
our $dbd;


#######################################################################
##
## On Groups
##
#######################################################################


## Get any settings for any users.
# arg0 : group name
# arg1 : setting (none for all)
sub GetGroupSettings { 
    GetDBSettings("groups", "unix_group_name='".SQLStringEscape($_[0])."'", $_[1]);
}


## Show in a convenient way settings for a user
# arg1 : which group name
sub PrintGroupSettings {
    print join " | ", GetGroupSettings($_[0]);
}


## Update in a convenient way settings for a group
# arg1 : which group name
# arg2 : which field
# arg3 : new value
sub SetGroupSettings {    
    SetDBSettings("groups", "unix_group_name='".SQLStringEscape($_[0])."'",
		  "$_[1]='".SQLStringEscape($_[2])."'");
}

## Get a list of groups.
# arg0 : which criterion
# arg1 : which field to be returned
sub GetGroupList { 
    return GetDBList("groups", $_[0], $_[1]);
}


## Delete a group account. This function should be used carrefully.
# arg1 : which group name
# returns: 1 if the group was deleted, 0 if it was not
sub DeleteGroup {    
    return ($dbd->do("DELETE FROM groups WHERE unix_group_name='".SQLStringEscape($_[0])."'") >= 1) if $_[0];
}

## Delete several group accounts. This function should be used carrefully.
# arg1 : a criterion
# returns: the number (real) of deleted rows, undef on error - check
#          the DBI documentation
sub DeleteGroups {    
    return $dbd->do("DELETE FROM groups WHERE ".$_[0]) if $_[0];
}



## In many case, we have to deal with group_id but I find
## easier to deal directly with user_name
# arg1 : which group id
sub GetGroupName {
    return $dbd->selectrow_array("SELECT unix_group_name FROM groups WHERE group_id='".$_[0]."'");
}

## Get path of keyring for a given group
sub GetGroupGPGKeyringFile {
    # Get the level of subdir for home
    #   0. Home is like /home/savane-keyrings/group.gpg
    #   1. Home is like /home/savane-keyrings/g/group.gpg
    #   2. Home is like /home/savane-keyrings/g/gr/group.gpg
    my $group = $_[0];
    my $chroot = $_[1];
    my $ret = $chroot.GetConf("sys_homedir")."/savane-keyrings";
    
    if (GetConf("sys_homedir_subdirs") && GetConf("sys_homedir_subdirs") ne '0') {
	$ret .= "/".substr($group, 0, 1);
	if (GetConf("sys_homedir_subdirs") eq '2') {
	    $ret .= "/".substr($group, 0, 2);
	} 
    }
    # Always in lowercase, even if the database information accepted
    # uppercase.
    return lc($ret."/".$group.".gpg");
}

# Store gpg --list-public-keys in the database so it is possible to make
# it visible on the frontend
sub StoreGroupGPGKeyring {
    my $group = $_[0];
    my $keyring = GetGroupGPGKeyringFile($group);
    
    # If the file does not exist, the database entry must be empty
    unless (-e $keyring) {
	SetDBSettings("groups", "unix_group_name='$group'", "registered_gpg_keys=''");
	DeleteDB("groups_gpg_keyrings", "unix_group_name='$group'");
	return 1;
    }

    # Otherwise, get the list and store it (not in groups, to avoid having
    # enormous blob in this table)
    open(LIST, "gpg --list-public-keys --keyring $keyring |");
    my $line;
    my $content;
    while(<LIST>) {
	$line++;
	# The two first line are useless
	next if $line < 3;
	# Mangle email addresses
	s/\@/-/g;
	$content .= $_;
    }
    close(LIST);
    SetDBSettings("groups", "unix_group_name='$group'", "registered_gpg_keys=".$dbd->quote($content));

    # And get the file and store it
    $content = "";
    open(FILE, "< $keyring");
    while(<FILE>) {
	$content .= $_;
    }
    close(FILE);

    DeleteDB("groups_gpg_keyrings", "unix_group_name='$group'"); 
    InsertDB("groups_gpg_keyrings", "unix_group_name, keyring", "'$group', ".$dbd->quote($content));

    
}

## Register a GPG  public key for a user in a group keyring
# arg1 : group
# arg2 : username
# arg3 : key content
# return the number of keys registered.
sub GroupAddGPGKey {
    my ($group, $key) = @_;
    my $keyring = GetGroupGPGKeyringFile($group);

    # Make sure the keyring dir exists
    system("mkdir", "-p", dirname($keyring)) unless -e dirname($keyring);

    my @gpg_args = ("gpg",
		    "--quiet",
		    "--batch",
		    "--no-tty",
                    "--no-default-keyring",
                    "--keyring",
                    $keyring,
                    '--import',
                    '-');

    my $pid = open (GPG, "|-");

    if ($pid) {                   # parent
        print GPG $key;
        close (GPG);
        return $?;
    } else {                      # child
        exec (@gpg_args) || exit 1;
    }

    return 1;
}


## Get list of group's users
# arg1 : which group name
sub GetGroupUsers {
    return GetDBList("user_group", "group_id='".GetGroupSettings($_[0], "group_id")."' AND admin_flags<>'P'", "user_id");
}

## Show in a convenient way group's users
sub PrintGroupUsers {
    foreach my $user (GetGroupUsers($_[0])) {
	print GetUserName($user)." | ";
    }
}

## Get list of group's admin
# arg1 : which group name
sub GetGroupAdmins {
    return GetDBList("user_group", "group_id='".GetGroupSettings($_[0], "group_id")."' AND admin_flags='A'", "user_id");
}

## Show in a convenient way group's admin
sub PrintGroupAdmins {
    foreach my $user (GetGroupAdmins($_[0])) {
	print GetUserName($user)." | ";
    }
}

## Get list of group's admin emails
# arg1 : which group name
sub GetGroupAdminsMail {
    return GetDBList("user,user_group", "user_group.user_id=user.user_id AND user_group.group_id='".GetGroupSettings($_[0], "group_id")."' AND user_group.admin_flags='A'","user.email");
}

## Show in a convenient way group's admin emails
sub PrintGroupAdminsMail {
    print join " | ", GetGroupAdminsMail($_[0]);
}


#######################################################################
##
## On Group Types
##
#######################################################################


## Get the group type, very frequent.
# arg1 : which group name
sub GetGroupType {
    return GetGroupTypeName(GetGroupSettings($_[0], 'type'));
}

## Show in a convenient way group's type
# arg1 : which group name
sub PrintGroupType {
    print GetGroupTypeName(GetGroupType($_[0]));
}

## Get any settings for any users.
# arg1 : which group name
# arg2 : which setting (none for all)
sub GetGroupTypeSettings { 
    my $arg_group;
    my $arg_field = "*";
 
    if ($_[0] ne '') { 
	unless ($_[0] eq '*') {
	    $arg_group = " WHERE name='".SQLStringEscape($_[0])."'"; 
	}
	if ($_[1] ne '') {
	    $arg_field = $_[1];
	}
    }

    return $dbd->selectrow_array("SELECT ".$arg_field." FROM group_type".$arg_group);
}

## Show in a convenient way settings for a user
# arg1 : which group name
sub PrintGroupTypeSettings {
    print join " | ", GetGroupTypeSettings($_[0]);
}

## In many case, we have to deal with group_id but I find
## easier to deal directly with user_name
# arg1 : which group id
sub GetGroupTypeName {
    return $dbd->selectrow_array("SELECT name FROM group_type WHERE type_id='".$_[0]."'");
}
