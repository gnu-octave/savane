#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2003-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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
## Basic module that load any convenient submodules.
##

use strict;
use Term::ANSIColor qw(:constants);
use DBI;
use POSIX qw(strftime);
use File::Basename;
use Text::Wrap qw(&wrap $columns);

# Load modules
use Savane::Version;
use Savane::Conf;
use Savane::DB;
use Savane::Locks;
use Savane::Mail;
use Savane::User;
use Savane::Groups;

return "true";

