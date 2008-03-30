# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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
