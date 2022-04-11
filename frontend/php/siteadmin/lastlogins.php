<?php
# Show last logins.
# 
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev
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


require_once('../include/init.php');
register_globals_off();

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

site_admin_header(array('title'=>no_i18n("Check Last Logins"),
                  'context'=>'admhome'));

$res_logins = db_query("SELECT session.user_id AS user_id,"
	. "session.ip_addr AS ip_addr,"
	. "session.time AS time,"
	. "user.user_name AS user_name FROM session,user "
	. "WHERE session.user_id=user.user_id AND "
	. "session.user_id>0 AND session.time>0 ORDER BY session.time "
        . "DESC LIMIT 250");

if (db_numrows($res_logins) < 1) {
	$feedback = no_i18n("No records found, there must be an error somewhere.");

} else {

	print '<p>'.no_i18n("Follow most recent logins:").'</p>
';

	$title_arr=array();
	$title_arr[]=no_i18n("User Name");
	$title_arr[]=no_i18n("Ip");
	$title_arr[]=no_i18n("Date");
	print html_build_list_table_top ($title_arr);

	$inc=0;
	while ($row_logins = db_fetch_array($res_logins)) {
		print '<tr class="'.utils_altrow($inc++).'">';
		print "<td>$row_logins[user_name]</td>";
		print "<td>$row_logins[ip_addr]</td>";
		print "<td>" . utils_format_date($row_logins['time'])
                      . "</td>\n";
		print '</tr>
';
	}
	print '</table>
';
}
$HTML->footer(array());
?>
