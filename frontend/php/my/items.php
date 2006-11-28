<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require '../include/pre.php';
require_directory("trackers");

register_globals_off();

global $item_data, $group_data;
$item_data = array();
$group_data = array();

if (user_isloggedin())
{


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
      for ($j=0; $j<$rows; $j++)
	{
	  unset($nogroups);
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



  $form_threshold = sane_all("form_threshold");
  $form_open = sane_all("form_open");
  unset($threshold);
  unset($open);

  # Extract arguments
  if ($form_threshold)
    {
      # Check if the argument is valid: numeric > 0 and < 10 
      if (preg_match('/^[1-9]*$/i', $form_threshold))
	{ 
	  $threshold = $form_threshold;
	  user_set_preference("my_items_threshold", $threshold);
	}
    }
  if ($form_open)
    {
      # Check if the argument is valid: open or closed
      if ($form_open == 'open' || $form_open == 'closed')
	{ 
	  $open = $form_open;
	  user_set_preference("my_items_open", $open);
	}
    }

  # Extract configuration if needed
  if (!$threshold) 
    { $threshold = user_get_preference("my_items_threshold"); }
  if (!$open) 
    { $open = user_get_preference("my_items_open"); }
  
  # Still nothing? Set the default settings
  if (!$threshold) 
    { $threshold = 5; }
  if (!$open) 
    { $open = "open"; }

  site_user_header(array('context'=>'myitems'));

  print '<p>'._("This page contains lists of items assigned to or submitted by you.").'</p>';

  # we get site-specific content
  utils_get_content("my/items");

  $fopen = '<select name="form_open"><option value="open" '.($open == "open" ? 'selected="selected"':'').'>'._("Open").'</option><option value="closed" '.($open == "closed" ? 'selected="selected"':'').'>'._("Closed").'</option></select> ';
  $fthreshold = '<select name="form_threshold"><option value="1" '.($threshold == 1 ? 'selected="selected"':'').'>'._("Lowest").'</option><option value="3" '.($threshold == 3 ? 'selected="selected"':'').'>'._("Low").'</option><option value="5" '.($threshold == 5 ? 'selected="selected"':'').'>'._("Normal").'</option><option value="7" '.($threshold == 7 ? 'selected="selected"':'').'>'._("High").'</option><option value="9" '.($threshold == 9 ? 'selected="selected"':'').'>'._("Immediate").'</option></select> ';

  $form_opening = '<form action="'.$PHP_SELF.'#options" method="get">';
  $form_submit = '<input class="bold"  type="submit" value="'._("Apply").'" />';
  print html_show_displayoptions(sprintf(_("Show %s new items or of %s priority at least."), $fopen, $fthreshold),
				 $form_opening,
				 $form_submit);


 ################ RIGHT PART ############################

  print html_splitpage(1);

  print '<br /><div class="box"><div class="boxtitle">'._("Assigned to me").'</div>';
  print my_item_list("assignee", $threshold, $open);
  print '</div>';


//   # Forums that are actively monitored
//   print $HTML->box1_top(_("Monitored Forums"));

//   $sql="SELECT groups.group_id, groups.group_name ".
//      "FROM groups,forum_group_list,forum_monitored_forums ".
//      "WHERE groups.group_id=forum_group_list.group_id ".
//      "AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
//      "AND forum_monitored_forums.user_id='".user_getid()."' GROUP BY group_id ORDER BY group_id ASC LIMIT 100";

//   $result=db_query($sql);
//   $rows=db_numrows($result);
//   if (!$result || $rows < 1)
//     {
//       print '<p>'._("I am not monitoring any forums.").'</p>';
//       print '<p>'._("If I monitor forums, I will be sent new posts in the form of an email, with a link to the new message.");
//       print '<p>'._("I can monitor forums by clicking 'Monitor Forum' in any given discussion forum.");
//       print '<br />&nbsp;';
//       print db_error();
//     }
//   else
//     {

//       for ($j=0; $j<$rows; $j++)
// 	{

// 	  $group_id = db_result($result,$j,'group_id');

// 	  $sql2="SELECT forum_group_list.group_forum_id,forum_group_list.forum_name ".
// 	     "FROM groups,forum_group_list,forum_monitored_forums ".
// 	     "WHERE groups.group_id=forum_group_list.group_id ".
// 	     "AND groups.group_id=$group_id ".
// 	     "AND forum_group_list.group_forum_id=forum_monitored_forums.forum_id ".
// 	     "AND forum_monitored_forums.user_id='".user_getid()."' LIMIT 100";

// 	  $result2 = db_query($sql2);
// 	  $rows2 = db_numrows($result2);

// 	  list($hide_now,$count_diff,$hide_url) =
// 	    my_hide_url('forum',$group_id,$hide_item_id,$rows2,$hide_forum);

// 	  $html_hdr = ($j ? '<td colspan="2">' : '').
// 	     $hide_url.'<A HREF="'.$GLOBALS['sys_home'].'forum/?group_id='.$group_id.'"><strong>'.
// 	     db_result($result,$j,'group_name').'</strong></A>&nbsp;&nbsp;&nbsp;&nbsp;';

// 	  $html = '';
// 	  $count_new = max(0, $count_diff);
// 	  for ($i=0; $i<$rows2; $i++)
// 	    {

// 	      if (!$hide_now)
// 		{
// 		  $group_forum_id = db_result($result2,$i,'group_forum_id');
// 		  $html .= '
// 			<tr class="'. utils_get_alt_row_color($i) .'"><td width="99%">'.
// 		     '&nbsp;&nbsp;&nbsp;-&nbsp;<a href="'.$GLOBALS['sys_home'].'forum/forum.php?forum_id='.$group_forum_id.'">'.
// 		     stripslashes(db_result($result2,$i,'forum_name')).'</a></td>'.
// 		     '<td align="middle"><a href="'.$GLOBALS['sys_home'].'forum/monitor.php?forum_id='.$group_forum_id.
// 		     '" onClick="return confirm(\''._("Stop monitoring this Forum?").'\')">'.
// 		     '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" '.
// 		     'border="0" alt="'._("Stop monitoring this Forum?").'" /></a></td></tr>';
// 		}
// 	    }

// 	  $html_hdr .= my_item_count($rows2,$count_new).'</td></tr>';
// 	  print $html_hdr.$html;
// 	}

//       print '<TR><TD COLSPAN="2">&nbsp;</TD></TR>';
//     }
//   print $HTML->box1_bottom();
//   print '<br /><br />';

 ################ LEFT PART ############################

				    
  print html_splitpage(2);

  print '<br /><div class="box"><div class="boxtitle">'._("Submitted by me").'</div>';
  print my_item_list("submitter", $threshold, $open);
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
