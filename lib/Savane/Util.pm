#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2005 (c) Sylvain Beucler <beuc--beuc.net>
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

##
## Desc: any subs that may be useful in very rare cases
##

use strict "vars";
use Fcntl ':flock';
require Exporter;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(SQLStringEscape );

## Escapes data to be sent in a SQL string
## Returns a copy of the string, escaped
# arg0 : the string to escape
sub SQLStringEscape {
    my $str = $_[0];
    $str =~ s/\\/\\\\/g;
    $str =~ s/\'/\'\'/g;
    $str =~ s/\n/\\n/g;
    return $str;
}


