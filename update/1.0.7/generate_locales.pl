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

# This script checks for the UTF-8 enabled version for every supported
# locale. If the locale has not yet an UTF-8-enabled version, that
# locale is automatically generated.

use strict;
use Getopt::Long;

my $getopt;
my $debug = 0;

eval {
    $getopt = GetOptions("debug" => \$debug);
};

my @locales = qw(de_DE en_US fr_FR it_IT ja_JP ko_KR pt_BR ru_RU);

foreach my $locale (@locales)
  {
    print "Checking for locale $locale.UTF-8 ... ";
    if (-e "/usr/lib/locale/$locale.utf8/")
      {
        print "Found\n";
      }
    else
      {
        my $result = system "localedef -c -i $locale -f UTF-8 $locale.UTF-8" unless ($debug);
        if ($result == 0)
          {
            print "Newly generated\n";
          }
        else
          {
            print "Generation of locale failed!\n";
          }
      }
  }

print "(Ran in debug mode, nothing was actually really executed)\n" if $debug;
