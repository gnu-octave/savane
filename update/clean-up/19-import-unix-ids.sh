#!/bin/bash
# Fetch user and group IDs for import in the Savane database
# Copyright (C) 2009  Sylvain Beucler
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

# This is meant to be called before disabling the old-school
# replication crons and switching to libnss-mysql authentication.

# Place 'passwd' and 'group' from /etc in the current directory, and
# import the SQL output in MySQL:
# $ sh 19-import-unix-ids.sh | mysql savane

(
echo "CREATE TEMPORARY TABLE temp_table (username varchar(30), uidNumber int);"
echo "INSERT INTO temp_table VALUES "
# ('admin', 65535), ('loic', 65536);
cat passwd | while IFS=: read username pass uid rest; do
  if [ $uid -gt 1000 ]; then
    echo -n "('$username', $uid),";
  fi;
done | sed 's/,$//'
echo ";"
echo "UPDATE user, temp_table
  SET user.uidNumber = temp_table.uidNumber
  WHERE user.user_name = BINARY temp_table.username;"
)
echo "DROP TABLE temp_table;"

(
echo "CREATE TEMPORARY TABLE temp_table (name varchar(30), gidNumber int);"
echo "INSERT INTO temp_table VALUES "
# ('mifluz', 1004), ('figure', 1006);
cat group | while IFS=: read name pass gid rest; do
  if [ $gid -gt 1000 ]; then
    echo -n "('$name', $gid),";
  fi;
done | sed 's/,$//'
echo ";"
echo "UPDATE groups, temp_table
  SET groups.gidNumber = temp_table.gidNumber
  WHERE groups.unix_group_name = BINARY temp_table.name;"
)
echo "DROP TABLE temp_table;"
