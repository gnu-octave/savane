#!/usr/bin/perl
#
# $Id: sv_testing-cleannotifications.pl 498 2005-06-30 16:33:51Z toddy $
#
#  Copyright 2005 (c) Mathieu Roy <yeupou--gnu.org>
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


# Fetch savane-doc userguide and put in in the relevant frontend directory.
# If xmlto fails, you are likely to have a messed up xmlto/sgml installation.
# 
# For the record, I (yeupou) was not able to get this to work on SLC4, while
# it works nicely with Debian Sarge.

use strict;
use Cwd;

my $path = getcwd;

# go in a temporary directory
my $tmp = "$path/buildguide";
system("rm", "-rf", $tmp);
system("mkdir", $tmp);
chdir($tmp);

# Fetch the latest CVS
system("wget", "http://cvs.gna.org/cvsweb/~checkout~/savane-doc/user-guide/savane-function.sgml?content-type=text%2Fplain;cvsroot=savane-doc", "-Ouser-guide.sgml");

# We use xmlto that generates clean 
system("xmlto", "xhtml", "user-guide.sgml");

# Remove old html from the userguide directory
my $userguide = "$path/../frontend/php/userguide/";
system("rm", "-f", "$userguide/*.html");

# Read each xHTML file produced, keep only the body
opendir(TMP, "$path/buildguide");
while (defined(my $file = readdir(TMP))) {
    next unless $file =~ m/.*\.html$/;
    print "Cleaning $file\n";
    open(IN, "< $path/buildguide/$file");
    open(OUT, "> $userguide/$file");
    my $inside_body;
    my $after_body;
    while (<IN>) {
	$inside_body = 1 if s/.*\<body\>//g;
	$after_body = 1 if s/\<\/body\>.*//g;
	next unless $inside_body;
	
	# Make links working inside the PHP interface
	s/\ href\=\"/\ href\=\"\?file\=/g;
	
	# Ignore radical style changes imposed by xmlto
	s/style\=\"clear\:\ both\"//g;

	print OUT $_;
	last if $after_body;
    }
    close(IN);
    close(OUT);
}
closedir(TMP);

# Commit the updated version
print "Now you should add/commit content of $userguide\n";

# Remove tmpdir
system("rm", "-rf", $tmp);
