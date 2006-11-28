#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2001-2004 (c) Loic Dachary <loic@gnu.org> (sv_backups)
#                         Mathieu Roy <yeupou@gnu.org>
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


use strict;
use Getopt::Long;
use POSIX qw(strftime);
use Savane::Locks;

my $script = "sv_daily_cvs_tarball";
my $logfile = "/var/log/sv_daily_cvs_tarball.log";

# Configure
my $getopt;
my $help;
my $verbose;
my ($in, $out, $cvs_command);
my %disallow;

eval {
    $getopt = GetOptions("verbose" => \$verbose,
			 "in=s" => \$in,
			 "out=s" => \$out,
			 "cvs-command=s" => \$cvs_command,
			 "help" => \$help);
};

if($help || !$in || !$out) {
    print STDERR <<EOF;
usage: $0 

   Simplistic script that make tarball for any directories that got a 
   CVSROOT in the given path

   You can create a file /etc/daily_cvs_tarball.disallow to disallow
   daily tarball of some repositories, by adding inside the cvs directory
   to disallow, one entry per line.

	--in=/		        Path of the CVS directories
 	--out=/		        Path of the generated tarballs
	--cvs-command           Anonymous cvs command 
	                        (ex: -d:pserver:anonymous\@cvs.gna.org:/cvs)
	--verbose		increase verbosity level
	--help			print this help

Author: loic\@gnu.org, yeupou\@gnu.org
EOF
 exit(1);
}

# Log: Starting logging
open (LOG, ">>$logfile");
print LOG strftime "[$script] %c - starting\n", localtime;


# Locks: There are several sv_db2sys scripts but they should not run
#        concurrently.  So we add a lock
AcquireReplicationLock();


# Get list of forbidden groups, if existing
if (-e "/etc/daily_cvs_tarball.disallow") {
    open(DISALLOW, "< /etc/daily_cvs_tarball.disallow") 
	or die "Internal error, contact the administrators.";
    while (<DISALLOW>) {
	s/\n//g;
	$disallow{$_} = "1";
    }
    close(DISALLOW);
}




die "Not able to write to $out, exiting" unless -w $out;
die "Not able to read $in, exiting" unless -r $in;

# Usefull subs

sub file_mtime {
    my($path) = @_;

    my($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,
                      $atime,$mtime,$ctime,$blksize,$blocks)
	= stat($path);

    return $mtime;
}

#
# Return true if $a is newer than $b
#
sub file_newer {
    my($a, $b) = @_;

    return file_mtime($a) > file_mtime($b);
}


# Run!

opendir(IN, $in)
    or die "Unable to open $in, exiting";

while (defined(my $cvs = readdir(IN))) {
    # Check if the directory looks like a cvs
    next if exists($disallow{$cvs});
    next unless -d $in."/".$cvs;
    next unless -e $in."/".$cvs."/CVSROOT";
    # If so, try to enter it
    my $cvsdir = $in."/".$cvs;
    chdir($cvsdir) 
	or die "cannot chdir $cvsdir : $!";
   
    my $backup = $out."/".$cvs.".tar.gz";
    my $snapshot = $out."/".$cvs."-snapshot.tar.gz";
    my $needed;

    if (! -f $backup) {
	printf STDERR "There is no backup yet\n" if $verbose;
	$needed = 1;
    }

    if (!defined($needed) && -f "CVSROOT/history") {
	#
	# History file my help us figure out if we need to backup
	# the CVS tree
	#
	my $loghistory;
	if (-f "CVSROOT/config") {
	    ($loghistory) = grep(/^\s*LogHistory/, `cat CVSROOT/config`);
	    #
	    #
	    #	T	"Tag" cmd.
	    #	O	"Checkout" cmd.
	    #   E       "Export" cmd.
	    #	F	"Release" cmd.
	    #	W	"Update" cmd - No User file, Remove from Entries file.
	    #	U	"Update" cmd - File was checked out over User file.
	    #	G	"Update" cmd - File was merged successfully.
	    #	C	"Update" cmd - File was merged and shows overlaps.
	    #	M	"Commit" cmd - "Modified" file.
	    #	A	"Commit" cmd - "Added" file.
	    #	R	"Commit" cmd - "Removed" file.
	    #
	    if ($loghistory) {
		print STDERR $loghistory if $verbose;
		$loghistory =~ s/.=//;
	    }
	}
	if ($loghistory && $loghistory !~ /[OEFWUGC]/) {

	    #
	    # If read-only events are not logged, we can rely on its
	    # modification time.
	    #
	    printf STDERR "history file only logs RW events, rely on history file modification time\n" if $verbose;
	    $needed = file_newer($cvsdir."/CVSROOT/history", $backup);

	} else {
	    	    
	    #
	    # Get the date of the last read-write event from the content
	    # of the history file.
	    #
	    my($line) = `grep '^[TMAR]' CVSROOT/history | tail -1`;
	    my($lastrw) = $line;
	    if ($lastrw) {

		$lastrw = hex(substr($lastrw, 1, 8));

		if ($verbose) {
		    printf STDERR $line;
		    printf STDERR "tarball is dated " . localtime(file_mtime($backup)) . " and last history event " . localtime($lastrw) . "\n";
		}

		if ($lastrw > file_mtime($backup)) {
		    printf STDERR "last RW history event more recent than backup\n" if $verbose;
		    $needed = 1;
		} else {
		    printf STDERR "last RW history event tells us we don't need to backup\n" if $verbose;
		    $needed = 0;
		}

	    } else {
		
		#
		# No last RW event, cannot say nothing, maybe history
		# file was reset by hand or something
		#
		
	    }
	}
    }
    
    if (!defined($needed)) {
	#
	# Do it the hard way : walk the tree until we find a file
	# that is more recent than the backup.
	#
	system("find $cvsdir -newer $backup -print | while read file ; do exit 1 ; done");
	$needed = $? != 0;
	print STDERR "the tree " . ($needed ? "" : "DOES NOT ") . "contain a file newer than the backup\n" if $verbose;
    }
    
    die "needed MUST be set at this stage" if !defined($needed);
	
    if ($needed) {
	#
	# Do the tarballs
	#
	# First, the repository
	print STDERR "Make tarball\n" if $verbose;
	print LOG strftime "[$script] %c ---- build $backup\n", localtime;
	`cd $in && /bin/tar -zhcf $backup $cvs 2>/dev/null`;
	# Then, a checkout version, only if cvs-command is set
	if ($cvs_command) {
	    print STDERR "Make snapshot\n" if $verbose;
	    print LOG strftime "[$script] %c ---- build $snapshot\n", localtime;
	    system("rm", "-rf", "/tmp/$cvs") if -e "/tmp/$cvs";
	    system("mkdir", "/tmp/$cvs");
	    `cd /tmp/$cvs && cvs $cvs_command/$cvs export -rHEAD . 2>/dev/null >/dev/null`;
	    `cd /tmp && /bin/tar -zhcf $snapshot $cvs 2>/dev/null`;
	    system("rm", "-rf", "/tmp/$cvs");
	}
    }
}

closedir(IN);  


# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";

# END
