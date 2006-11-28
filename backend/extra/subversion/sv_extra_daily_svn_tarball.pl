#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2001-2004 (c) Loic Dachary <loic@gnu.org> (sv_backups)
#                         Mathieu Roy <yeupou@gnu.org>
#                         Timothee Besset <ttimo@ttimo.net>
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

# TTimo
# adapt from the cvs version
# no snapshot / --svn-command=<URL> yet, what to select?
#   the root of the dir will have all branches and tags, it might explode
#   trunk then .. that's a bit restrictive though. but I guess cvs does the same
# using the name of the dumpfile to store the revision number of the latest dump
# create symlinks with no version information so it's easy to construct a systematic link?

use strict;
use Getopt::Long;
use POSIX qw(strftime);

my $script = "sv_daily_svn_tarball";
my $logfile = "/var/log/sv_daily_svn_tarball.log";

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

   Simplistic script that make tarball for any svn directories in the given
   path

   You can create a file /etc/daily_svn_tarball.disallow to disallow
   daily tarball of some repositories, one entry per line.

	--in=/		        Path of the SVN directories
 	--out=/		        Path of the generated tarballs
	--verbose		increase verbosity level
	--help			print this help

Author: loic\@gnu.org, yeupou\@gnu.org, ttimo\@ttimo.net
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
if (-e "/etc/daily_svn_tarball.disallow") {
    open(DISALLOW, "< /etc/daily_svn_tarball.disallow") 
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

while (defined(my $svn = readdir(IN))) {
    # do-not-process directories
    next if exists($disallow{$svn});
    # check if the directory looks like an svn
    my $rev = `svnlook youngest $in/$svn 2>/dev/null`;
    next if ( $? != 0 );
    chomp( $rev );
    my $target = $out."/".$svn.".".$rev.".dump.gz";
    next if -e $target;
    print STDERR "create dumpfile $target\n" if $verbose;
    print LOG strftime "[$script] %c ---- create dumpfile $target\n", localtime;
    # remove older dumps
    my $cleancmd = "rm $out/$svn.*.dump.gz 2>/dev/null";
    system( $cleancmd );
    # build this one
    my $dumpcmd = "svnadmin dump $in/$svn 2>/dev/null | gzip > $target";
    system( $dumpcmd );
    # create a symlink for blind retrieval
    my $symcmd = "ln -s $target $out/$svn.dump.gz";
    system( $symcmd );

    # if the dump check decided a dump was needed, consider that we need to build a snapshot too
    $target = $out."/".$svn."-snapshot.tar.gz";
    print STDERR "Make snapshot $target\n" if $verbose;
    print LOG strftime "[$script] %c ---- create snapshot $target\n", localtime;
    system( "rm", "-rf", "/tmp/$svn" ) if -e "/tmp/$svn";
    system( "mkdir", "/tmp/$svn" );
    `cd /tmp/$svn && svn export file://$in/$svn/trunk $svn`;
    `cd /tmp/$svn && /bin/tar -zcf $target $svn`;
    system( "rm", "-rf", "/tmp/$svn" );
}

closedir(IN);  


# Final exit
print LOG strftime "[$script] %c - work finished\n", localtime;
print LOG "[$script] ------------------------------------------------------\n";
unlink($lockfile);

# END
