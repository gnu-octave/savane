#!/usr/bin/env perl
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

# Read in a file supplied via command line and convert
# the Savane comment style into the doxygen comment style.
#
# Example:
#
# ##
# # This will be turned into doxygen
# function test() ...
#
# /*
# * This will be turned into doxygen
# */
# function test() ...

use strict;
use warnings;

open INPUT, "< @ARGV"
    or die "Could not open input file: $!";
open OUTPUT, "> tempfile.tmp"
    or die "Could not open output file: $!";
my $inside_comment = 0;

while (<INPUT>) {
    if (/^##([^#]*)$/) {
        $inside_comment = 1;
        s/##/\/\*\*/;
    }
    if ($inside_comment) {
        if (/^#/) {
            s/#/\*/;
        } elsif (/^function/ or /^class/) {
            s/^/\*\/\n/;
            $inside_comment = 0;
        }
    }
    print OUTPUT;
}

close INPUT;
close OUTPUT;
