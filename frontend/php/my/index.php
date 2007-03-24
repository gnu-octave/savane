<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require_once('../include/init.php');
require_directory("trackers");

register_globals_off();

global $item_data, $group_data;
$item_data = array();
$group_data = array();


if (user_isloggedin())
{
  site_user_header(array('context'=>'my'));

  print '<p>'._("Here's a list of recent items (< 16 days) we think you should have a look at. These are items recently posted on trackers you manage that are still unassigned or assigned to you and news posted on a project you are member of.").'</p>';

  # Get the list of projects the user is member of

  $sql = "SELECT groups.group_name,"
    . "groups.group_id,"
    . "groups.unix_group_name,"
    . "groups.status "
    . "FROM groups,user_group "
    . "WHERE groups.group_id=user_group.group_id "
    . "AND user_group.user_id='".user_getid()."' "
    . "AND groups.status='A' "
    . "GROUP BY groups.unix_group_name "
    . "ORDER BY groups.unix_group_name";

  $result = db_query($sql);
  $rows = db_numrows($result);
  $usergroups = array();
  $usergroups_groupid = array();
  if ($result && $rows > 0)
    {
      unset($nogroups);
      for ($j=0; $j<$rows; $j++)
	{
	  $unixname = db_result($result,$j,'unix_group_name');
	  $usergroups[$unixname] = db_result($result,$j,'group_name');
	  $usergroups_groupid[$unixname] = db_result($result,$j,'group_id');
	}
    }
  else
    { $nogroups = 1; }

  # Get the list of squads the user is member of
  $sql = "SELECT squad_id FROM user_squad "
    . "WHERE user_id='".user_getid()."'";

  $result = db_query($sql);
  $rows = db_numrows($result);
  $usersquads = array();
  if ($result && $rows > 0)
    {
      unset($nosquads);
      for ($j=0; $j<$rows; $j++)
	{
	  $usersquads[] = db_result($result,$j,'squad_id');
	}
    }
  else
    { $nosquads = 1; }



  # Get a timestamp to get new items (15 days)
  $new_date_limit = mktime(date("H"),
			   date("i"),
			   0,
			   date("m"),
			   date("d")-15,
			   date("Y"));

 ################ RIGHT PART ############################

  print html_splitpage(1);

  #### News to approve
  # Shown only if the user is news manager somewhere and if any item found
  reset($usergroups);
  reset($usergroups_groupid);
  unset($result);
  unset($rows);
  # Build an sql request that will fetch any relevant news
  $sql = "SELECT group_id,date,id,summary FROM news_bytes ".
    "WHERE date > '$new_date_limit' AND is_approved='5' AND (";
  $previous = 0;
  while (list($group, $groupname) = each ($usergroups))
    {
      if (member_check(0, $usergroups_groupid[$group],'N3'))
	{
	  if ($previous) { $sql .= "OR "; }
	  $sql .= "group_id='".$usergroups_groupid[$group]."' ";
	  $previous = 1;
	}
    }
  $sql .= ") ORDER BY date DESC";

  # If there is no relevant group (previous not set), it is not even necessary
  # to run the sql command
  $result = NULL;
  if ($previous)
    {
      $result = db_query($sql);
      $rows = db_numrows($result);
    }

  if ($result && $rows > 0)
    {
      print '<br /><div class="box"><div class="boxtitle">'._("News Waiting for Approval").'</div>';
      for ($j=0; $j<$rows; $j++)
	{
	  print '<div class="'.utils_get_alt_row_color($j).'">';
	  print '<a href="'.$GLOBALS[sys_home].'news/approve.php?approve=1&amp;id='.db_result($result, $j, 'id').'&amp;group='.group_getunixname(db_result($result, $j, 'group_id')).'">'.db_result($result, $j, 'summary').'</a><br />';
	  # FIXME: num. of new comments?
	  print '<span class="smaller">'.sprintf(_("Project %s, %s"), group_getname(db_result($result, $j, 'group_id')), utils_format_date(db_result($result,$j,'date'))).'</span>';
	  print '</div>';
	}
      print '</div>';
    }


  #### Latest Approved News
  print '<br /><div class="box"><div class="boxtitle">'._("News").'</div>';
  reset($usergroups);
  reset($usergroups_groupid);
  # Build an sql request that will fetch any relevant news
  $sql = "SELECT group_id,date,forum_id,summary FROM news_bytes ".
    "WHERE date > '$new_date_limit' AND (is_approved='0' OR is_approved='1') AND (group_id='".$GLOBALS['sys_group_id']."' ";
  while (list($group, $groupname) = each ($usergroups))
    {
      $sql .= "OR group_id='".$usergroups_groupid[$group]."' ";
    }
  $sql .= ") ORDER BY date DESC";

  $result = db_query($sql);
  $rows = db_numrows($result);
  if ($result && $rows > 0)
    {
      for ($j=0; $j<$rows; $j++)
	{
	  print '<div class="'.utils_get_alt_row_color($j).'">';
	  print '<a href="'.$GLOBALS['sys_home'].'forum/forum.php?forum_id='.db_result($result, $j, 'forum_id').'">'.db_result($result, $j, 'summary').'</a><br />';
	  # FIXME: num. of new comments?
	  print '<span class="smaller">'.sprintf(_("Project %s, %s"), group_getname(db_result($result, $j, 'group_id')), utils_format_date(db_result($result,$j,'date'))).'</span>';
	  print '</div>';
	}
    }
  else
    {
      print _("None found");
    }
  print '</div>';

 ################ LEFT PART ############################


  print html_splitpage(2);

  #### New items to assign
  # shown only if the user is tracker manager somewhere and if any item found
  # (so the title is included in the function called)

  print '<br /><div class="box"><div class="boxtitle">'._("New and Unassigned Items").'</div>';
  print my_item_list("unassigned");
  print '</div>';


  ### Items newly assigned (not necessarily new items)
  print '<br /><div class="box"><div class="boxtitle">'._("New and Assigned Items").'</div>';
  print my_item_list("newlyassigned");
  print '</div>';

  print html_splitpage(3);

  # End

  print "\n\n".show_priority_colors_key();


  $HTML->footer(array());

}
else
{

  exit_not_logged_in();

}

?>
