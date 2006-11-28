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
our @EXPORT = qw(DownloadMakeArea DownloadMakeAreaSavannah );
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


## Make a gatekeeper download area at Savannah
## This is temporary
sub DownloadMakeAreaSavannah {
    my ($name,$dir_download,$is_public) = @_;

    if (-e "/srv/download/$name" || !$is_public) {
	return 1;
    }

    # print LOG strftime "[$script] %c ---- created download $name\n", localtime;

    my $dir_public = "/srv/download/$name";
    mkdir ($dir_public);
    chmod (00755, $dir_public);
    system('/bin/chown', "gatekpr:$name", $dir_public);
    system('/bin/chmod', "2775", $dir_public);

    # print LOG strftime "[$script] %c ---- created upload $name\n", localtime;

    # create the incoming ftp dir
    my $gatekpr_prefix = "/var/lib/gatekpr";
    my $dir_upload = "$gatekpr_prefix/upload/incoming/savannah/$name";
    mkdir ($dir_upload);
    chmod (00770, $dir_upload);
    system ('/bin/chown', 'upload:gatekpr', $dir_upload);

    # print LOG strftime "[$script] %c ---- created ftp-in $name\n", localtime;
    # print LOG strftime "[$script] %c ---- created ftp-out $name\n", localtime;

    # ..and the ftp-in tmp dir
    # ..and the ftp-out tmp dir
    my $dir_ftpin = "$gatekpr_prefix/ftp-in/$name";
    my $dir_ftpout = "$gatekpr_prefix/ftp-out/$name";
    mkdir ($dir_ftpin);
    mkdir ($dir_ftpout);
    chmod (00770, $dir_ftpin);
    chmod (00770, $dir_ftpout);
    system ('/bin/chown', 'gatekpr:gatekpr', $dir_ftpin);
    system ('/bin/chown', 'gatekpr:gatekpr', $dir_ftpout);

    return;
}
