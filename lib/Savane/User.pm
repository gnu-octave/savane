#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2005 (c) Mathieu Roy <yeupou--gnu.org>
#                          Sylvain Beucler <beuc--beuc.net>
#                          Free Software Foundation, Inc.
# Copyright (C) 2008  Aleix Conchillo Flaque
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
#
#

##
## Desc: any subs related to users.
##

use strict "vars";
require Exporter;

use Savane::Util;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(GetUserSettings GetUserPrefs PrintUserSettings PrintUserPrefs SetUserSettings SetUserPrefs GetUserGroups GetUserList GetUserName GetUserHome GetUserSSHKeyReal DeleteUser DeleteUsers PrintAliasesList UserAddSSHKey UserAddGPGKey UserStoreGPGKey UserGetStoredGPGKey );
our $version = 1;

# Imports (needed for strict).
our $dbd;
our $qqch;
our $sys_homedir;
our $sys_homedir_subdirs;


#######################################################################
##
## Return infos, basic stuff
##
#######################################################################


#####
##### From the database
##### 

## Get any settings for any users.
# arg0 : which user name
# arg1 : which setting (none for all)
sub GetUserSettings { 
    GetDBSettings("user", "user_name='".SQLStringEscape($_[0])."'", $_[1]);
}

## Get any pref for any user
# arg0 : which user name
# arg1 : which setting (none for all)
sub GetUserPrefs { 
    GetDBSettings("user_preferences", "user_id='".GetUserSettings($_[0], "user_id")."' AND preference_name='".SQLStringEscape($_[1])."'");
}

## Show in a convenient way settings for a user
# arg1 : which user name
sub PrintUserSettings {
    print join " | ", GetUserSettings($_[0]);
}

sub PrintUserPrefs {
    print join " | ", GetUserPrefs($_[0]);
}


## Update in a convenient way settings for a user
# arg1 : which user name
# arg2 : which field
# arg3 : new value
sub SetUserSettings {
    SetDBSettings("user", "user_name='".SQLStringEscape($_[0])."'",
		  "$_[1]='".SQLStringEscape($_[2])."'");
}

## Get any pref for any user
# arg1 : which user name
# arg2 : preference name
# arg3 : new value
sub SetUserPrefs { 
    SetDBSettings("user_preferences", "user_id='".GetUserSettings($_[0], "user_id")."' AND preference_name='".SQLStringEscape($_[1])."'", "preference_value='".SQLStringEscape($_[2])."'");
}


## Get list of groups for a user (contrary of GetGroupUsers
# arg1 : which user name
sub GetUserGroups {
    return GetDBList("user_group", "user_id='".GetUserSettings($_[0], "user_id")."'", "group_id");
}


## Get a list of users.
# arg0 : which criterion
# arg1 : which field to be returned
sub GetUserList { 
    return GetDBList("user", $_[0], $_[1]);
}


## In many case, we have to deal with user_id but I find
## easier to deal directly with user_name
# arg1 : which user id
sub GetUserName {
    return $dbd->selectrow_array("SELECT user_name FROM user WHERE user_id='".$_[0]."'");
}

#####
##### From the system
#####

## Frequently we need the theorical home directory for a user,
## according to the configuration.
## Normally, sv_users should make sure that users got their home
## according to the theorical setting.
# arg1
sub GetUserHome {
    # Get the level of subdir for home
    #   0. Home is like /home/user
    #   1. Home is like /home/u/user
    #   2. Home is like /home/us/user
    my $user = $_[0];
    my $ret = $sys_homedir;
    
    if ($sys_homedir_subdirs && $sys_homedir_subdirs ne '0') {
	$ret .= "/".substr($user, 0, 1);
	if ($sys_homedir_subdirs eq '2') {
	    $ret .= "/".substr($user, 0, 2);
	} 
    }
    # Always in lowercase, even if the database information accepted
    # uppercase.
    return lc($ret."/".$user);
}

## This command will return the content of ~/.ssh/authorized_keys
## for a user.
## It will return nothing if the file is not found.
## Line breaks will be replaced by ###, to conform with the database
## way to store these data.
# arg1 : which user name
sub GetUserSSHKeyReal {
    my $file = GetUserHome($_[0])."/.ssh/authorized_keys";
    my $ret;
    if (-e $file) {
        open(SSH_KEY, "< $file");
        while (<SSH_KEY>) {
            s/\n/###/g; #'
            $ret .= $_;
        }
        close(SSH_KEY);
        # No return? Return a numeric false
        $ret = 0 unless $ret;
        return $ret;
    }
}


#######################################################################
##
## Do a specific task
##
#######################################################################

## Delete a userp account. This function should be used carrefully.
# arg1 : which user name, arg2 : boolean for database deletion
sub DeleteUser {
    # If it exists on the system, delete the account
    if (getpwnam($_[0])) {
	system("userdel", "-r", $_[0]);
    }
    # Remove from the database, if arg 2 = 1    
    $dbd->do("DELETE FROM user WHERE user_name='".SQLStringEscape($_[0])."'") if ($_[0] && $_[1]);

    return 1;
}

## Delete several users accounts. This function should be used carrefully.
# arg1 : a criterion
sub DeleteUsers {    
    return $dbd->do("DELETE FROM user WHERE ".$_[0]) if $_[0];
}


## It's many times usefull to get a list of username:mail for
## usual users, for instance for /etc/aliases
# arg1 : HANDLE
sub PrintAliasesList {
    my $handle = STDOUT;
    $handle = $_[0] if $_[0];

    my %email_by_uid;
    print $handle "# Users Accounts\n";
    foreach my $line (GetDBLists("user", "status='A' ORDER BY user_name", "user_name,email,user_id")) {
	my ($user_name, $email, $user_id) = @$line;
	next unless $email;
	print $handle "$user_name: $email\n";
	$email_by_uid{$user_id} = $email;
    }

    print $handle "# Squad Accounts\n";
    foreach my $line (GetDBLists("user", "status='SQD' ORDER BY user_name", "user_name,user_id")) {
	my ($squad_name, $squad_id) = @$line;
	my $email;
	foreach my $user_id (GetDB("user_squad", "squad_id='$squad_id'", "user_id")) {
	    chomp($user_id);

	    # Get previously found email (if not found, it means
	    # the squad contains non valid members that we will ignore)
	    next unless $email_by_uid{$user_id};

	    $email .= ", " if $email;
	    $email .= $email_by_uid{$user_id};
	}
	
	# skip the squad if it has no members
	next unless $email;

	print $handle "$squad_name: $email\n";	
    }
}


## (Over)Write SSH public key for a user
## Checks of the content of $authorized_key must be done before.
## This function will do what you asked.
# arg1 : username
# arg2 : content
# return the number of keys registered.
sub UserAddSSHKey {
    my $user = $_[0];
    my $home = GetUserHome($user);
    my $authorized_keys = $_[1];
    my $authorized_command = $_[2];
    my $ssh_keys_registered = 0;

    # If the authorized key entry is NULL, it means that we want to actually
    # simply remove the SSH file, so we dont even touch the file
    if ($authorized_keys) {
	open(SSH_KEY, "> $home/.ssh/authorized_keys");
	# In the database, linebreak are ###
	$authorized_keys =~ s/###/\n/g;
	my @ssh_keys = split(/\n/, $authorized_keys);
	$ssh_keys_registered = @ssh_keys;
	foreach (@ssh_keys) {
	    if ($authorized_command ne '') {
		print SSH_KEY 'command="'. $authorized_command. '" ';
	    }
	    print SSH_KEY $_ . "\n";
	}
	close(SSH_KEY);
    }
  


    # If no key where found, simply remove the file
    system("rm", "-f", "$home/.ssh/authorized_keys")
	unless $ssh_keys_registered;

    # Store the information in the database, an interface the frontend could
    # (but does not currently) use to tell the user if all this keys are 
    # registered on the system of pending registration
    SetUserSettings($user, "authorized_keys_count", $ssh_keys_registered);

    return $ssh_keys_registered;
}


## Register a GPG  public key for a user
## Checks of the content of $authorized_key must be done before.
## This function will do what you asked.
# arg1 : username
# arg2 : content
# return the number of keys registered.
sub UserAddGPGKey {
    my ($user, $key) = @_;

    my $home = GetUserHome($user);

    unlink("$home/.gnupg/pubring.gpg");

    return 1 unless ($key);

    my @gpg_args = ("/usr/bin/gpg",
		    "--batch",
		    "--quiet",
		    "--no-tty",
                    "--no-default-keyring",
                    "--keyring",
                    "$home/.gnupg/pubring.gpg",
                    "--import",
                    "-");

    my $pid = open (GPG, "|-");

    if ($pid) {                   # parent
        print GPG $key;
        close (GPG);
        my $ret = $?;
        if ($ret) {
            SetUserSettings($user, "gpg_key_count", 0);
        } else {
            SetUserSettings($user, "gpg_key_count", 1);
        }
        return $ret;
    } else {                      # child
        exec (@gpg_args) || exit 1;
    }

    return 1;
}
## Store ASCII version of the GPG key, for future comparisons
# arg1 : username
# arg2 : content
# If the content is NULL or false, we are asked to remove the stored key
sub UserStoreGPGKey {
    my ($user, $key) = @_;
    my $home = GetUserHome($user);

    if (!$key or $key eq "NULL") {
	unlink("$home/.gnupg/ascii-public-key");
    } else {
	open(STOREFILE, "> $home/.gnupg/ascii-public-key");
	print STOREFILE $key;
	close(STOREFILE);
    }
    return 1;
}

## Provide the stored GPG key, for comparisons
sub UserGetStoredGPGKey {
    my $file = GetUserHome($_[0])."/.gnupg/ascii-public-key";
    my $ret;
    if (-e $file) {
        open(GPG_KEY, "< $file");
        while (<GPG_KEY>) {
            $ret .= $_;
        }
        close(GPG_KEY);
        # No return? Return a numeric false
        $ret = 0 unless $ret;
        return $ret;
    }
    return 0;
}


return 1;
