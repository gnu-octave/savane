#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: sv_mailman.pl 4740 2005-09-13 09:39:08Z yeupou $
#
#  Copyright 2006 (c) Mathieu Roy <yeupou--gnu.org> 
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
# Register public lists to mail-archives.com

use strict;
use Getopt::Long;
use Savane;
use POSIX qw(strftime);
use File::Temp qw(tempfile tempdir);

my $script = "sv_mailman_and_mailarchivedotcom";
#my $logfile = "/var/log/sv_database2system.log";
my $cache = "/var/cache/savane/sv_mailman_and_mailarchivedotcom";

# Import
our $sys_mail_domain;
our $sys_cron_mailman;

# Preconfigure
my $getopt;
my $help;
my $debug;
my $cron;

eval {
    $getopt = GetOptions("debug" => \$debug,
			 "cron" => \$cron,
			 "help" => \$help);
};

if($help || !$getopt) {
    print STDERR <<EOF;
Usage: $0 [--user=<user> --password=<password>] [--help] [--verbose]

Register mailman public lists to mail-archives.com. It goes the dirty way
asking to register the mail-archive.com address without checking if the 
address already exists, relying on mailman to do this.

This script is optional and have no purpose if you do not real public lists.

      --help                   Show this help and exit
      --cron                   Option to set when including this script
                               in a crontab


Author: yeupou\@gnu.org
EOF
 exit(1);
}

# Test if we should run, according to conffile
exit if ($cron && ! $sys_cron_mailman);

# Locks: instances should not run concurrently, so we add a lock
AcquireReplicationLock();

my ($tmpcfgfh, $address) = tempfile(UNLINK => 1);
print $tmpcfgfh "archive\@mail-archive.com\n";
close($tmpcfgfh);


# Get the lists where mail-archives was already registered.
# It is safe to re-register several time the same user, mailman will not
# duplicate registration. However, it is very slow. So we first extract
# the list of lists where he was already registered, to be able to skip
# them afterwards
my %already_registered;
open(CURRENTLYREG, "/usr/sbin/find_member archive\@mail-archive\.com |");
while (<CURRENTLYREG>) {
    # Ignore any line mentioned mail-archive.com, it must be warning
    next if m/mail-archive\.com/;

    # Remove useless whitespaces add members adds god knows why
    s/\s//g;

    # Register the address
    $already_registered{$_} = 1;

    print "Already registered: $_\n"
	if $debug;
}
close(CURRENTLYREG);


# Get lists from the database
foreach my $line (GetDB("mail_group_list", 
			"is_public='1' AND status='5'",
			"list_name")) {
    chomp($line);
    my ($name) = split(",", $line);

    # Ignores lists named spam
    next if $name eq "spam";

    # Ignores any list that already go this user registered
    next if $already_registered{$name};

    # Add to the list
    system("/usr/sbin/add_members",
	   "-r",
	   $address,
	   "--admin-notify",
	   "n",
	   $name)
	unless $debug;

    print strftime "[$script] %c - Added to $name.\n", localtime
	if $debug;
}

# END

