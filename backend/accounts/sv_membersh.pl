#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2004-2005 (c) Loic Dachary <loic--gnu.org>
#                         Mathieu Roy <yeupou--gnu.org>
#                         Timothee Besset <ttimo--ttimo.net>
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

# Login shell for people who should only have limited access.
# You probably should add/modify the following option of your sshd_config
# like below (see sshd_config manual for more details):
#     PermitEmptyPasswords no
#     PasswordAuthentication no
#     AllowTcpForwarding no

use strict;

$ENV{PATH}="/usr/local/bin:/usr/bin:/bin";
$ENV{CVSEDITOR}="/bin/false";

# Import conf options
our $use_cvs = "0";
our $bin_cvs = "/usr/bin/cvs";
 
our $use_scp = "0";
our $bin_scp = "/usr/bin/scp";
our $regexp_scp = "^(scp .*-t /upload)|(scp .*-t /var/ftp)";

our $use_sftp = "0";
our $bin_sftp = "/usr/lib/sftp-server";
our $regexp_sftp = "^(/usr/lib/ssh/sftp-server|/usr/lib/sftp-server|/usr/libexec/sftp-server)";

our $use_rsync = "0";
our $bin_rsync = "/usr/bin/rsync";
our $regexp_rsync = "^rsync --server";
our $regexp_dir_rsync = "^(/upload)|(/var/ftp)";

our $use_svn = "0";
our $bin_svn = "/usr/bin/svnserve";
our $regexp_svn = "^svnserve -t";
our @prepend_args_svn = ( '-r', '/svn' );

our $use_git = "0";
our $bin_git = "/usr/bin/git-shell";

# Open configuration file
if (-e "/etc/membersh-conf.pl") {
    do "/etc/membersh-conf.pl" or die "System misconfiguration, contact administrators. Exiting";
} else {
    die "System misconfiguration, contact administrators. Exiting";
} 

# A configuration file /etc/membersh-conf.pl must exists and be executable.
# Here come an example:
#
# $use_cvs = "1";
# $bin_cvs = "/usr/bin/cvs";
# 
# $use_scp = "1";
# $bin_scp = "/usr/bin/scp";
# $regexp_scp = "^scp .*-t (/upload)|(/var/ftp)";

# $use_sftp = "1";
# $bin_sftp = "/usr/lib/sftp-server";
# $regexp_sftp = "^(/usr/lib/ssh/sftp-server|/usr/lib/sftp-server|/usr/libexec/sftp-server)";
#
# $use_rsync = "1";
# $bin_rsync = "/usr/bin/rsync";
# $regexp_rsync = "^rsync --server";
# $regexp_dir_rsync = "^(/upload)|(/var/ftp)";


if ($#ARGV == 1 and $ARGV[0] eq "-c") {
    if ($use_cvs and $ARGV[1] eq 'cvs server') {
	
	# Run a cvs server command
        exec($bin_cvs, 'server') or die("Failed to exec $bin_cvs: $!");

    } elsif ($use_scp and 
	     $ARGV[1] =~ m:$regexp_scp:) {

	# Authorize scp command
        my (@args) = split(' ', $ARGV[1]);
        shift(@args);             
        exec($bin_scp, @args);

    } elsif ($use_sftp and 
	     $ARGV[1] =~ m:$regexp_sftp:) {
	
	# Authorize sftp login
        exec($bin_sftp) or die("Failed to exec $bin_sftp: $!");

    } elsif ($use_rsync and 
	     $ARGV[1] =~ m:$regexp_rsync:) {

	my ($rsync, @rest) = split(' ', $ARGV[1]);
	my ($dir) = $rest[$#rest];

	# Authorize rsync command, if the directory is acceptable
	if ($dir =~ m:$regexp_dir_rsync:) {
            exec($bin_rsync, @rest) or die("Failed to exec $bin_rsync: $!");
        } 
	
    } elsif ($use_svn and
	     $ARGV[1] =~ m:$regexp_svn:) {
	
	# authorize svnserve in tunnel mode, with the svn root prepended
        my (@args) = @prepend_args_svn;
	my (@args_user) = split(' ', $ARGV[1]);
	shift( @args_user );
	push( @args, @args_user );
	exec($bin_svn, @args) or die("Failed to exec $bin_svn: $!");

    } elsif ($use_git and $ARGV[1] =~ m:git-.+:) {
	
	# Delegate filtering to git-shell
        exec($bin_git, @ARGV) or die("Failed to exec $bin_git: $!");

    }
}

unless (-e "/etc/membersh-errormsg") {
    print STDERR "You tried to execute: @ARGV[1..$#ARGV]\n";
    print STDERR "Sorry, you are not allowed to execute that command.\n";
} else {
    open(ERRORMSG, "< /etc/membersh-errormsg");
    while (<ERRORMSG>) {
	print STDERR $_;
    }
    close(ERRORMSG);
}
exit(1);
