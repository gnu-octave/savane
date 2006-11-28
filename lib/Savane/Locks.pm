#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: Version.pm 5540 2006-08-10 21:21:50Z toddy $
#
#  Copyright 2006     (c) Sylvain Beucler <beuc--gnu.org>
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

    if ($lockfile) {
	# System-wide lock
	# http://www.pathname.com/fhs/pub/fhs-2.3.html#VARLOCKLOCKFILES
	open LOCKFILE, "+>> /var/lock/savane/$lockfile" or die "Failed to ask lock.";
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
