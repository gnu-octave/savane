#!/usr/bin/perl
#
# Copyright (C) 2005 Tobias Toedter
#
# <one line to give a brief idea of what this does.>
# 
# This file is part of Savane.
#
# Savane is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Savane; if not, write to the Free Software Foundation,
# Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

# This script converts all files in the directory etc/site-specific-content
# from the specified input charset into utf-8.
# If not input charset is specified, ISO-8859-1 is assumed.

use strict;
use Savannah;
use File::Basename;
use Getopt::Long;

my $getopt;
my $debug = 0;
my $input = "ISO-8859-1";
my $folder = GetConf("sys_incdir");

my $count_files = 0;
my $count_converted_files = 0;

eval {
  $getopt = GetOptions(
    "debug" => \$debug,
    "input=s" => \$input,
    "folder=s" => \$folder
  );
};

printf "Working on directory $folder\n";
my @files_to_convert = `find $folder -type f`;

# make a backup of the current site-specific files
unless ($debug)
  {
    my $backup = "site-specific-content.tar.gz";
    my $count = 1;
    while (-e $backup)
      {
        $backup = sprintf("site-specific-content_%03d.tar.gz", $count);
        $count += 1;
      }

    if (system "tar --create --gzip --recursion --file=$backup $folder")
      {
        print "Failed to create backup of site-specific files.\n";
        print "Exiting without changing anything.\n";
        exit 1;
      }
  }

foreach my $file (@files_to_convert)
  {
    chop($file);

    # Ignore CVS specific files
    next if (basename($file) eq "Entries" ||
	     basename($file) eq "Repository" ||
	     basename($file) eq "Tag" ||
	     basename($file) eq "Root");


    my $result = system "iconv --from-code=$input --to-code=UTF-8 --output=tmp.txt $file";
    unless ($result)
      {
	  my $outfile = $file;
	  my $notc = 0;

	  # .txt and .html files must be overwrote as they are
	  # localized files (let's assume that's the others) must have
	  # .UTF-8 appended. 
	  my $suffix = (fileparse($file,'\.\w*'))[2];
	  if ($suffix ne ".txt" && 
	      $suffix ne ".html" && 
	      $suffix ne ".bak") {
	      
	      $outfile .= ".UTF-8";
	      $notc = 1;
	  }
	 
	  my $diff = system "diff tmp.txt $file";
	  if ($diff || $notc)
          {
	      system("mv", "tmp.txt", $outfile) unless $debug;
	      # remove the original file if we dont overwrite it
	      # because of a name change
	      system("rm", $file) if (!$debug && $notc);

	      print "Converted ".substr($file, length($folder));
	      print " from $input to UTF-8\n";
	      
	      $count_converted_files += 1;
	  }
      }
    else
      {
        print "Error: iconv failed on ".substr($file, length($folder))."\n";
      }

    $count_files += 1;
    system "rm -f tmp.txt";
  }

printf "Checked %d files, %d converted from $input to UTF-8.\n",
  $count_files, $count_converted_files;
if ($count_converted_files && !$debug)
  {
    print "\n" . "*"x60 . "\n";
    print "WARNING: Don't run this script again!\n";
    print "         Otherwise, your files will end up being unreadable!";
    print "\n" . "*"x60 . "\n\n";
  }

print "(Ran in debug mode, nothing was actually really executed)\n" if $debug;
