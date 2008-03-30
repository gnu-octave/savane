#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Sylvain Beucler <beuc--beuc.net>
#                          Free Software Foundation, Inc.
# 
# This file is part of Savane.
# 
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


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
