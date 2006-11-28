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

site_admin_header(array('title'=>_("User List"),'context'=>'admuser'));

# Administrative functions
$out='NOTHING';
if ($action=='delete') 
{
  db_query("UPDATE user SET status='D' WHERE user_id='$user_id'");
  $out = _("DELETE");
} 
else if ($action=='activate') 
{
  
  db_query("UPDATE user SET status='A' WHERE user_id='$user_id'");
  $out = _("ACTIVE");
} 
else if ($action=='suspend') 
{
  db_query("UPDATE user SET status='S' WHERE user_id='$user_id'");
  $out = _("SUSPEND");
}

if ($action) 
{
  print '<h3>'._("Action done").' :</h3>';
  print '<p>';
  printf(_("Status updated to %s for user %s"), $out, $user_id);
  print '</p>';
  print db_error();
}


# Search users 
$abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');

print '<h3>'._("User Search").'</h3>';

print '<p>'._("Display users beginning with").' : ';

for ($i=0; $i < count($abc_array); $i++) {
	echo "<a href=\"userlist.php?user_name_search=$abc_array[$i]\">$abc_array[$i]</a> ";
}

print '<br />'._("or search by email, username, realname or userid").' :';
print '
<form name="usersrch" action="userlist.php" method="POST">
  <input type="text" name="search" />
  <input type="hidden" name="usersearch" value="1" />
  <input type="submit" value="'._("Search").'" />
</form>
</p>';



# Show list of users

print '<h3>'._("User list for:").' ';

$MAX_ROW=100;
if (!$offset) 
{ $offset = 0; }


if (!$group_id) 
{
  print '<strong>'._("All Groups").'</strong></h3>';
  
  
  if ($user_name_search) 
    {
      $result = db_query("SELECT user_name,user_id,status,people_view_skills FROM user WHERE user_name LIKE '$user_name_search%' ORDER BY user_name LIMIT $offset,".($MAX_ROW+1));
    } 
  else 
    {
      $result = db_query("SELECT user_name,user_id,status,people_view_skills FROM user ORDER BY user_name LIMIT $offset,".($MAX_ROW+1));
    }
  
}
else
{
  # Show list for one group
  print " <strong>" . group_getname($group_id) . "</strong></h3>";
   
  $result = db_query("SELECT user.user_id AS user_id, user.user_name AS user_name, user.status AS status ,user.people_view_skills AS people_view_skills"
		     . "FROM user,user_group "
		     . "WHERE user.user_id=user_group.user_id AND "
		     . "user_group.group_id=$group_id ORDER BY user.user_name LIMIT $offset,".($MAX_ROW+1));
  
}

$rows = $rows_returned = db_numrows($result);

$title_arr=array();
$title_arr[]=_("Id");
$title_arr[]=_("User");
$title_arr[]=_("Status");
$title_arr[]=_("Member Profile");
$title_arr[]=_("Action");

print html_build_list_table_top ($title_arr);


if ($rows_returned < 1) 
{
  print '<tr class="'.utils_get_alt_row_color($inc++).'"><td colspan="7">';
  print _("No matches");
  print '.</td></tr>';
  
} 
else 
{
  if ($rows_returned > $MAX_ROW) {
    $rows = $MAX_ROW;
  }
  for ($i = 0; $i < $rows; $i++) 
    {
      $usr = db_fetch_array($result);
      print '<tr class="'.utils_get_alt_row_color($inc++).'"><td>'.$usr[user_id].'</td><td><a href="usergroup.php?user_id='.$usr[user_id].'">';
      print "$usr[user_name]</a>";
      print "</td><td>\n";
      
      switch ($usr[status]) 
	{
	case 'A': print _("Active"); break;
	case 'D': print _("Deleted"); break;
	case 'S': print _("Suspended"); break;
	case 'SQD': print _("Active (Squad)"); break;
	case 'P': print _("Pending"); break;
	default: print _("Unknown status")." : ".$usr[status]; break;
	}
      if ($usr[people_view_skills] == 1 ) 
	{
	  print '<td><a href="'.$GLOBALS['sys_home'].'people/resume.php?user_id='.$usr[user_id].'">['._("View").']</a></td>';
	} 
      else 
	{
	  print '<td>('._("Private").')</td>';
	}
      print '<td>';
      if ($usr[status] != 'D')
	{ print '<a href="'.$GLOBALS['sys_home'].'admin/userlist.php?action=delete&user_id='.$usr[user_id].'">['._("Delete").']</a> '; }
      if ($usr[status] != 'S') 
	{ print '<a href="'.$GLOBALS['sys_home'].'admin/userlist.php?action=suspend&user_id='.$usr[user_id].'">['._("Suspend").']</a> '; }
      if ($usr[status] != 'A' && $usr[status] != 'SQD')
	{ print '<a href="'.$GLOBALS['sys_home'].'admin/userlist.php?action=activate&user_id='.$usr[user_id].'">['._("Activate").']</a> '; }
      print "</td></tr>\n";
    }
}
print "</table>";

html_nextprev($_SERVER['PHP_SELF'].'?user_name_search='.urlencode($user_name_search).'&amp;usersearch=1&amp;search='.urlencode($search), $rows, $rows_returned);


$HTML->footer(array());

?>