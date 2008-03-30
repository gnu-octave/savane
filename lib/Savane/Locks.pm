#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
#  Copyright 2006     (c) Sylvain Beucler <beuc--gnu.org>
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
#
#

use strict "vars";
use Fcntl ':flock';
require Exporter;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(AcquireReplicationLock );
our $version = 1;


## Locking: either for a single script or system-wide
# flock() locks are automatically lost on program termination, however
# that happened (clean, segfault...)
sub AcquireReplicationLock {
    my ($lockfile) = @_;
    my $lockdir = "/var/lock/savane";

    if (! -d $lockdir) {
	mkdir $lockdir or die "Can't create lock directory $lockdir: $!";
    }

    if ($lockfile) {
	# System-wide lock
	# http://www.pathname.com/fhs/pub/fhs-2.3.html#VARLOCKLOCKFILES
	open LOCKFILE, "+>> $lockdir/$lockfile" or die "Failed to ask lock.";
    } else {
	# Script lock
	# http://perl.plover.com/yak/flock/samples/slide006.html
	open LOCKFILE, "< $0" or die "Failed to ask lock.";
    }

    if (flock LOCKFILE, LOCK_EX | LOCK_NB) {
	if ($lockfile) {
	    # HDB lock file format
	    truncate LOCKFILE, 0 or die "Failed to write lock.";
	    printf LOCKFILE "%10d\n", $$;
	}
    } else {
	die "There's a lock $0, exiting";
    }
}



# EOF
