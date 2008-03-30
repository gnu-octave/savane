<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
#
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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
site_admin_header(array('title'=>_("Check Last Logins"),'context'=>'admhome'));

$res_logins = db_query("SELECT session.user_id AS user_id,"
	. "session.ip_addr AS ip_addr,"
	. "session.time AS time,"
	. "user.user_name AS user_name FROM session,user "
	. "WHERE session.user_id=user.user_id AND "
	. "session.user_id>0 AND session.time>0 ORDER BY session.time DESC LIMIT 250");

if (db_numrows($res_logins) < 1) {
	$feedback = "No records found, There must be an error somewhere.";

} else {

	print '<p>'._("Follow most recent logins:").'</p>';

	$title_arr=array();
	$title_arr[]=_("User Name");
	$title_arr[]=_("Ip");
	$title_arr[]=_("Date");
	print html_build_list_table_top ($title_arr);

	$inc=0;
	while ($row_logins = db_fetch_array($res_logins)) {
		print '<tr class="'.utils_get_alt_row_color($inc++).'">';
		print "<td>$row_logins[user_name]</td>";
		print "<td>$row_logins[ip_addr]</td>";
		print "<td>" . utils_format_date($row_logins['time']) . "</td>";
		print '</tr>';
	}

	print '</table>';
}
$HTML->footer(array());
