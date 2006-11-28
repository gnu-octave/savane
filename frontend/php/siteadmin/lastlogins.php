<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 1999-2000 (c) The SourceForge Crew
#
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require "../include/pre.php";
site_admin_header(array('title'=>_("Check Last Logins"),'context'=>'admhome'));
register_globals_off();

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

	while ($row_logins = db_fetch_array($res_logins)) {
		print '<tr class="'.utils_get_alt_row_color($inc++).'">';
		print "<td>$row_logins[user_name]</td>";
		print "<td>$row_logins[ip_addr]</td>";
		print "<td>" . format_date($sys_datefmt,$row_logins['time']) . "</td>";
		print '</tr>';
	}

	print '</table>';
}
$HTML->footer(array());

?>
