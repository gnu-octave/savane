#!/bin/sh

# Copyright (C) 2017 Assaf Gordon (assafgordon@gmail.com)
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



# This script can be used to run savannah's webserver locally.
# It requires PHP>=5.4 and php-mysql.
#
# It also requires having access to a demo savane MySQL database.
# The first time the server is run locally, a messsage with
# instructions on how to request access to such database will be displayed.
# (or, read it in ./local2/etc-savane/.savane.conf.php ).
#
# For more information, write to savannah-hackers-public@gnu.org .

die()
{
    BASE=$(basename "$0")
    echo "$BASE: error: $*" >&2
    exit 1
}


# Default host/port to run the local PHP code.
SVHOST=${SVHOST:-127.0.0.1}
SVPORT=${SVPORT:-7890}

# Find the location of the development configuration file
dir=$(dirname "$0")
dir=$(cd "$dir" ; pwd)
confdir="$dir/local2/etc-savane"
conffile="$confdir/.savane.conf.php"
test -e "$conffile" \
    || die "can't find .savane.conf.php file (expecting: $conffile)"


# The PHP code expects to find the directory in the environment
# (on savannah's servers it is set in Apache's configuration SetEnv)
export SAVANE_CONF="$confdir"

# Find the location of the PHP code
phpdir="$dir/frontend/php"
test -d "$phpdir" \
    || die "can't find PHP code (expecting: $phpdir)"

# Ugly hack used in ./local2/local.php.
export SAVANE_PHPROOT="$phpdir"


php -S "$SVHOST:$SVPORT" -t "$phpdir" local2/local.php


