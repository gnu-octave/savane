#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# Copyright (C) Vincent Caron <zerodeux@gnu.org>, 2004
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

my $getopt;
my $group;
my $tarball;
my $help;
my $cvsbase = '/var/cvs';

eval {
    $getopt = GetOptions("group=s" => \$group,
			 "tarball=s" => \$tarball,
			 "help" => \$help);
};

if($help || !$getopt || !$group || !$tarball) {
    print STDERR <<EOF;
Usage: $0 [--help] --group=<group> --tarball=<tarball>

Install a CVS repository tarball for a given Savane group,
taking care of painful file attributes and archive format details.

      --group       Savane group
      --tarball     Tarball of a CVS repository
      --help	    Print this help

Author: zerodeux\@gnu.org
EOF
 exit(1);
}

my $cvsroot = "$cvsbase/$group";
my $temp = "/tmp/cvs-import-$group";
sub myexit { system('rm', '-rf', $temp); exit(shift); }

if (! -e $cvsroot) {
    print STDERR "Error: unknown group '$group'\n";
    exit(1);
}
if (system('rm', '-rf', $temp) || !mkdir($temp)) {
    print STDERR "Error: could not create working dir '$temp\n";
    exit(1);
}
chdir($temp) || myexit(1);

my $comp;
$comp = 'z' if $tarball =~ /\.t?gz$/;
$comp = 'j' if $tarball =~ /\.t?bz2$/;
if (!$comp) {
    print STDERR "Error: unknown tarball format (not compressed?)\n";
    myexit(1);
}
system('tar', "-x${comp}f", $tarball);

my $cvsroot_in = `find . -name CVSROOT`; chomp($cvsroot_in);
if (! -d $cvsroot_in) {
    print STDERR "Error: trouble finding CVSROOT control repository ('$cvsroot_in')\n";
    myexit(1); 
}
$cvsroot_in =~ s/\/[^\/]+$//;
chdir($cvsroot_in) || myexit(1);

# Those files will inherit the correct owner and attributes from the
# pre-existing $cvsroot/CVSROOT/* files.
#
print "copying CVSROOT/history...\n"; system('cp', "CVSROOT/history", "$cvsroot/CVSROOT");
print "copying CVSROOT/val-tags...\n"; system('cp', "CVSROOT/val-tags", "$cvsroot/CVSROOT");

my @modules = glob("[!CVSROOT]*");
for my $module (@modules) {
    print "copying module '$module'...\n";
    system('cp', '-r', $module, $cvsroot);

    # File inherit correct ownership from the $cvsroot sticky group bit, however
    # we have to make sure other attributes are correct.
    system('chmod', '-R', 'ug+w', "$cvsroot/$module");
    system('find', "$cvsroot/$module", '-type', 'd', '-exec', 'chmod', 'g+s', '{}', ';');
}

myexit(0);
