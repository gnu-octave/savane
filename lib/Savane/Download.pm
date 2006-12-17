#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Sylvain Beucler <beuc--beuc.net>
#                          Free Software Foundation, Inc.
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


##
## Desc: any subs related to Download area.
## 

use strict;
use warnings;

require Exporter;
our @ISA = qw(Exporter);
our @EXPORT = qw(DownloadMakeArea);
our $version = 1;

## Make a download area
sub DownloadMakeArea {
    my ($name,$dir_download,$is_public) = @_;
    my $warning = "";

    # %PROJECT is not mandatory, but if it is missing, it may well be 
    # a major misconfiguration.
    # It should only happen if a directory has been set for a specific 
    # project.
    unless ($dir_download =~ s/\%PROJECT/$name/) {
	$warning = " (The string \%PROJECT was not found, there may be a group type serious misconfiguration)";
    }

    unless (-e $dir_download) {
	
	my $mode = $is_public ? 2775 : 2770;
	system("mkdir", "-p", $dir_download);
	system("chmod", $mode, $dir_download);
	system("chgrp", $name, $dir_download);
	return " ".$dir_download.$warning;
    } 
    return;
}
