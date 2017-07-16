<?php
# Group administration.
# 
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
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
			<tr class="'. html_get_alt_row_color($i)
       .'"><td>'.$field.'</td>
<td>';

      if ($field=='removed user') {
	echo user_getname(db_result($result, $i, 'old_value'));
      } else {
	echo db_result($result, $i, 'old_value');
      }
      echo '</td>
<td>'.utils_format_date(db_result($result, $i, 'date')).'</td>
<td>'.db_result($result, $i, 'user_name')."</td></tr>\n";
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
  $res_admin = db_execute("SELECT user.user_id AS user_id,user.user_name "
                        . "AS user_name, user.realname "
                        . "AS realname, user.email AS email "
			. "FROM user,user_group "
			. "WHERE user_group.user_id=user.user_id "
                        . "AND user_group.group_id=? AND "
			. "user_group.admin_flags = 'A'",
                          array($row_grp['group_id']));

  print '<p><span class="preinput">'._("Project Admins").':</span><br /> ';
  while ($row_admin = db_fetch_array($res_admin)) {
    print "<a href=\"".$GLOBALS['sys_home']
     ."users/$row_admin[user_name]/\">$row_admin[realname] "
     ."&lt;$row_admin[email]&gt;</a> ; ";
  }
  print "</p>\n";
  print '<p><span class="preinput">'._("Registration Date").':</span><br /> '
        .utils_format_date($row_grp['register_time']);
  print "</p>\n";
  print '<p><span class="preinput">'._("System Group Name:").'</span><br /> '
        .$row_grp['unix_group_name'];
  print "</p>\n";
  print '<p><span class="preinput">'._("Submitted Description:").'</span><br /> '
        .markup_full($row_grp['register_purpose']);
  print "</p>\n";
  print '<p><span class="preinput">'._("Required software:")
        .'</span><br /> '.markup_full($row_grp['required_software']);
  print "</p>\n";
  print '<p><span class="preinput">'._("Other comments:").'</span><br /> '
        .markup_full($row_grp['other_comments']);
  print "</p>\n";
  print '<p>';
  print utils_registration_history($row_grp['unix_group_name']);
  print "</p>\n";
}
?>
