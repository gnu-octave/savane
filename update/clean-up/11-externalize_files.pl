#!/usr/bin/perl
# Store files outside of the DB
# Copyright (C) 2008  Sylvain Beucler
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

# At Savannah we have more than 16000 files attached to trackers,
# around 400MB on disk in a single table. This becomes inconvient when
# backing up the database and such, so let's store them on disk
# instead.

use strict;
use Savane;

my $base_dir = '/var/lib/savane/trackers_attachments';
my $httpd_user = 'www-data';

system('mkdir', '-m', '755', '/var/lib/savane');
system('mkdir', '-m', '775', '/var/lib/savane/trackers_attachments');
system('chown', "root:$httpd_user", '/var/lib/savane/trackers_attachments');

our $dbd;
my $hop = $dbd->prepare("SELECT file_id,artifact,item_id,date,filename,filesize,file
	FROM trackers_file");
# Getting one row at a time rather than caching 500MB on the client)
$hop->{"mysql_use_result"} = 1;
$hop->execute;
while (my $row = $hop->fetchrow_hashref) {
    #print $row->{'file_id'} . " ($row->{'filename'})\n";
    my $path = "$base_dir/$row->{'file_id'}";
    open(OUT, ">$path) or die $!;
    print OUT $row->{'file'};
    close(OUT);
    my $stamp = strftime "%Y%m%d%H%M.%S", localtime($row->{'date'});
    system('touch', '-t', $stamp, $path);
}
