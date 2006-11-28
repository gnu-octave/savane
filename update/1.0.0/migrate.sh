#!/bin/sh
#
# Copyright (c) 2003-2004 Derek Feichtinger <derek.feichtinger@cern.ch>
#	                  Yves Perrin <yves.perrin@cern.ch>
#
#   The Savane project is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; either version 2 of the License, or
#   (at your option) any later version.
#
#   The Savane project is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with the Savane project; if not, write to the Free Software
#   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# $Id$

# this script assumes that the configuration file is
# called dbmapper.conf


# get the mysql user name used to manipulate the
# data bases from the configuration file
username=`grep -E '^\\$username' dbmapper.conf|cut -f 2 -d '"'`

echo "username for accessing data base: >$username<"
#############################

echo '######################################################'
echo '######################################################'
echo 'STEP 1: running the data base column mapper script (dbmapper.pl)'
echo '######################################################'
./dbmapper.pl -v -i map.in -o db_migrate.sql -l map.out -c dbmapper.conf
echo '######################################################'

echo '    feeding the generated db_migrate.sql into MySQL'
echo '######################################################'
mysql -u $username -p < db_migrate.sql

echo '######################################################'
echo '######################################################'
echo '######################################################'

echo 'STEP 2: running the tracker configurator script (db_tracker_configurator.pl)'
echo '######################################################'
./db_tracker_configurator.pl -c dbmapper.conf -o db_tracker_configurator.sql

echo '######################################################'

echo '    feeding the generated dbmapspec.sql file into MySQL'
mysql -u $username -p < db_tracker_configurator.sql

echo '######################################################'
echo '######################################################'
echo '######################################################'

echo 'STEP 3: executing the entries migration script (db_move_entries.sql)'
echo '######################################################'
./db_move_entries.pl -v -c dbmapper.conf

echo '######################################################'
echo '######################################################'
echo '######################################################'

echo 'STEP 4: executing ./loose_ends.pl'
./loose_ends.pl

