#!/bin/bash
# Properly declare fields as utf8
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


# Currently the database stores manually converted utf8 texts in
# latin1 cells, which messes collation (ordering) and isn't otherwise
# "clean". We'll change the columns definition without altering the
# actual content, which needs to be done for each column.

# Cf. http://dev.mysql.com/doc/refman/4.1/en/charset-conversion.html

set -e

mysql_opts=""
database="savane"

mysql_query()
{
    query_table=$1
    query_str=$2
    #echo $query_table: "$query_str" >&2
    mysql --show-warnings $mysql_opts $query_table -B -e "$query_str"
}

# Database
echo "Declaring database as utf8"
mysql_query information_schema "ALTER DATABASE \`$database\` DEFAULT CHARACTER SET utf8;"
# Tables
mysql_query information_schema "SELECT \`TABLE_NAME\` FROM \`TABLES\` WHERE \`TABLE_SCHEMA\` = '$database';" \
    | tail -n +2 \
    | while read table; do
    echo -n "$table "
    mysql_query $database "ALTER TABLE $table DEFAULT CHARACTER SET utf8;"
    # Columns (only the ones that use charsets, i.e. not integers)
    mysql_query information_schema "SELECT \`COLUMN_NAME\`, \`COLUMN_TYPE\`, \`DATA_TYPE\` FROM COLUMNS WHERE \`TABLE_SCHEMA\` = '$database' AND \`TABLE_NAME\` = '$table' AND CHARACTER_SET_NAME IS NOT NULL;" \
	| tail -n +2 \
	| (
	tobin=""
	toutf8=""
	while read column type data_type; do
	    # Don't convert fixed-length CHAR() types or we'll get trailing \0
	    # Plus they only contain small ASCII strings, no conversion happens
	    if [ "$data_type" != "char" ]; then
		if [ -z "$tobin" ]; then
		    tobin="MODIFY \`$column\` $type CHARACTER SET binary"
		else
		    tobin="$tobin,MODIFY \`$column\` $type CHARACTER SET binary"
		fi
	    fi
	    if [ -z "$toutf8" ]; then
		toutf8="MODIFY \`$column\` $type CHARACTER SET utf8"
	    else
		toutf8="$toutf8,MODIFY \`$column\` $type CHARACTER SET utf8"
	    fi
	done
        # Variant from documentation, to avoid converting primary keys to BLOB, which is forbidden
	mysql_query $database "ALTER TABLE \`$table\` $tobin;"
	mysql_query $database "ALTER TABLE \`$table\` $toutf8;"
	)
done
echo
echo "Done!"


# If you're infortunate enough to mess the CHAR() fields, you'll have
# to do things like:
#UPDATE user_group SET admin_flags=replace(admin_flags, "\0", "");
#UPDATE bugs_report SET scope=replace(scope, "\0", "");
#UPDATE cookbook_field_usage SET transition_default_auth=replace(transition_default_auth, "\0", "");
#UPDATE cookbook_report SET scope=replace(scope,"\0", "");
#UPDATE session SET ip_addr=replace(ip_addr,"\0", "");
#UPDATE support_report SET scope=replace(scope,"\0", "");
#UPDATE task_report SET scope=replace(scope,"\0", "");
#UPDATE user SET email_hide=replace(email_hide,"\0", "");
