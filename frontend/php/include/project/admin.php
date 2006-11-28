<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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



# Nicely html-formatted output of this group's audit trail

function show_grouphistory ($group_id)
{
		# show the group_history rows that are relevant to
		# this group_id
  global $sys_datefmt;
  $result=group_get_history($group_id);
  $rows=db_numrows($result);

  if ($rows > 0) {

    echo '
		<h3>'
      ._("Group Change History").'</h3>
		<p>';
    $title_arr=array();
    $title_arr[]=_("Field");
    $title_arr[]=_("Value");
    $title_arr[]=_("Date");
    $title_arr[]=_("By");

    echo html_build_list_table_top ($title_arr);

    for ($i=0; $i < $rows; $i++) {
      $field=db_result($result, $i, 'field_name');
      echo '
			<tr class="'. html_get_alt_row_color($i) .'"><td>'.$field.'</td><td>';

      if ($field=='removed user') {
	echo user_getname(db_result($result, $i, 'old_value'));
      } else {
	echo db_result($result, $i, 'old_value');
      }
      echo '</td>'.
	'<td>'.format_date($sys_datefmt,db_result($result, $i, 'date')).'</td>'.
	'<td>'.db_result($result, $i, 'user_name').'</td></tr>';
    }

    echo '
		</table>';

  } else {
    echo '
		<h3>'
      ._("No Changes Have Been Made to This Group").'</h3>';
  }
}


function project_admin_registration_info ($row_grp)
{
  $res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name, user.realname AS realname, user.email AS email "
			. "FROM user,user_group "
			. "WHERE user_group.user_id=user.user_id AND user_group.group_id=".$row_grp['group_id']." AND "
			. "user_group.admin_flags = 'A'");


  print '<p><span class="preinput">'._("Project Admins").':</span><br /> ';
  while ($row_admin = db_fetch_array($res_admin)) {
    print "<a href=\"".$GLOBALS['sys_home']."users/$row_admin[user_name]/\">$row_admin[realname] &lt;$row_admin[email]&gt;</a> ; ";
  }

  print '<p><span class="preinput">'._("Registration Date").':</span><br /> '.format_date($sys_datefmt,$row_grp[register_time]);

  print '<p><span class="preinput">'._("System Group Name:").'</span><br /> '.$row_grp[unix_group_name];

  print '<p><span class="preinput">'._("Submitted Description:").'</span><br /> '.markup_full($row_grp[register_purpose]);

  print '<p><span class="preinput">'._("Required software:").'</span><br /> '.markup_full($row_grp[required_software]);

  print '<p><span class="preinput">'._("Other comments:").'</span><br /> '.markup_full($row_grp[other_comments]);

  print '<p>';
  print utils_registration_history($row_grp[unix_group_name]);

}

?>
