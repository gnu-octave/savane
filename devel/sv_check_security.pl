#!/usr/bin/perl
#
# $Id: sv_testing-cleannotifications.pl 498 2005-06-30 16:33:51Z toddy $
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

# Take a look at all frontend php files and determine whether they
# call mysql_is_safe() or not, so we know exactly what remains to be
# done to have Savane working correctly with register globals set to
# off (mysql_is_safe depends on input_is_safe).

use strict;
use Cwd;
use File::Find::Rule;
use Getopt::Long;

my $path = getcwd."/../frontend/php";
my $getopt;

# get options
eval {
    $getopt = GetOptions("path=s" => \$path);
};


my @files = File::Find::Rule->file()
    ->name("*.php", "*.class")
    ->in("$path");

print "The following PHP files does not have mysql_is_safe:\n";
my $use_count;
foreach my $file (@files)
{
    my $does_use;
    my $line_count;
    open(FILE, "< $file");
    while (<FILE>) {
	$does_use = 1 if /^#mysql_is_safe\(\);$/;
	last if $does_use;
	$line_count++;
    }
    
    # ignore the file if it is not longer than 15 lines;
    #$does_use = 1 unless $line_count > 15;
    # Doesn't match anything anyway - license notices are huge ;)

    $use_count++ if $does_use;
    next if $does_use;

    $file = $1 if $file =~ /$path\/(.*)/;
    print "\t".$file."\n";

}

my $total_files = scalar(@files);
if ($total_files > 0) {
    print "\n".$use_count."/".$total_files." files done (".int($use_count/scalar(@files)*100)."%)\n";
} else {
    print "No files found in $path\n";
}
