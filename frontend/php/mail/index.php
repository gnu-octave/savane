<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
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

#input_is_safe();
#mysql_is_safe();

require_once('../include/init.php');

if ($group_id) 
{  

  exit_test_usesmail($group_id);

  site_project_header(array('group'=>$group_id, 'context'=>'mail'));


  
  if (user_isloggedin() && user_ismember($group_id)) 
    { $public_flag='0,1'; } 
  else 
    { $public_flag='1'; }
  
  $result = db_execute("SELECT * FROM mail_group_list WHERE group_id=? AND is_public IN ($public_flag) ORDER BY list_name ASC", array($group_id));
  $rows = db_numrows($result); 
  
  if (!$result || $rows < 1) 
    {
      printf ('<h2>'._("No Lists found for %s").'</h2>',$project->getName());
      print '<p>'._("Project administrators can add mailing lists using the admin interface.").'</p>';
      $HTML->footer(array());
      exit;
    }
  
  # the <br /> in front is here to put some space with the menu
  # Please, keep it
  print '<br />';

  for ($j = 0; $j < $rows; $j++) 
    {
      $is_public = db_result($result,$j,'is_public');
      $pass = db_result($result,$j,'password');

      $list = db_result($result, $j, 'list_name');

      # Pointer to listinfo or to the mailing list address, if no listinfo is found
      if ($project->getTypeMailingListListinfoUrl($list) && $project->getTypeMailingListListinfoUrl($list) != "http://")
	{ 
	  $default_pointer = $project->getTypeMailingListListinfoUrl($list); }
      else
	{ unset($default_pointer); }

      print '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/contexts/mail.png" border="0" alt="" /> <a href="'.$default_pointer.'">'.$list.'</a> ';
      
      # Description
      print '&nbsp;&nbsp;<em>'.db_result($result, $j, 'description').'</em>';
      print '<p class="smaller">';

      $previoustextexists = false;
      if ($is_public && $project->getTypeMailingListArchivesUrl($list) && $project->getTypeMailingListArchivesUrl($list) != "http://")
	{
	  if ($previoustextexists)
	    { print '<br />'; }
	  $previoustextexists = true;

	  # Pointer to archives
	  print sprintf(_("To see the collection of prior posting to the list, visit the %s%s archives%s"),'<a href="'.$project->getTypeMailingListArchivesUrl($list).'">', $list, '</a>.');
	}

      if (!$is_public && $project->getTypeMailingListArchivesPrivateUrl($list) && $project->getTypeMailingListArchivesPrivateUrl($list) != "http://")
	{
	  if ($previoustextexists)
	    { print '<br />'; }
	  $previoustextexists = true;

	  # Pointer to archives
	  print sprintf(_("To see the collection of prior posting to the list, visit the %s%s archives%s (authorization required)."),'<a href="'.$project->getTypeMailingListArchivesPrivateUrl($list).'">', $list, '</a>');
	}

      if ($project->getTypeMailingListAddress($list))
	{
	  if ($previoustextexists)
	    { print '<br />'; }
	  $previoustextexists = true;

	  # Address
	  print sprintf(_("To post a message to all the list members, write to %s"), utils_email($project->getTypeMailingListAddress($list)));
	}
      else 
	{
	  print '<br /><span class="error">'._("No mailing-list address was found, the configuration of the server is probably broken, contact the admins!").'</span>';
	}

      # Subscribe, unsubscribe:
      # if these fields are empty, go back on the listinfo page
      if ($project->getTypeMailingListSubscribeUrl($list) && 
	  $project->getTypeMailingListSubscribeUrl($list) != "http://" && 
	  $project->getTypeMailingListUnsubscribeUrl($list) && 
	  $project->getTypeMailingListUnsubscribeUrl($list) != "http://")
	{
	  if ($project->getTypeMailingListSubscribeUrl($list) && $project->getTypeMailingListSubscribeUrl($list) != "http://") {

	  if ($previoustextexists)
	    { print '<br />'; }
	  $previoustextexists = true;

	    print sprintf(_("You can subscribe to the list by submitting %sthis message%s"),'<a href="'.$project->getTypeMailingListSubscribeUrl($list).'">','</a>.');
	  }
	  if ($project->getTypeMailingListUnsubscribeUrl($list) && $project->getTypeMailingListUnsubscribeUrl($list) != "http://") {

	  if ($previoustextexists)
	    { print '<br />'; }
	  $previoustextexists = true;

	    print sprintf(_("You can unsubscribe to the list by submitting %sthis message%s"),'<a href="'.$project->getTypeMailingListUnsubscribeUrl($list).'">','</a>.');
	  }
	}
      else if ($project->getTypeMailingListListinfoUrl($list) && $project->getTypeMailingListListinfoUrl($list) != "http://")
	{
	  if ($previoustextexists)
	    { print '<br />'; }
	  $previoustextexists = true;

	  print sprintf(_("You can (un)subscribe to the list by following instructions on the %slist information page%s"),'<a href="'.$project->getTypeMailingListListinfoUrl($list).'">','</a>.');
	}
      
      if ($project->getTypeMailingListAdminUrl($list) && $project->getTypeMailingListAdminUrl($list) != "http://")
	{
	  if ($previoustextexists)
	    { print '<br />'; }
	  $previoustextexists = true;

	  # Admin interface
	  print sprintf(_("Project administrators could use the %sadministrative interface%s to manage the list."),'<a href="'.$project->getTypeMailingListAdminUrl($list).'">','</a>').'</dd>';
	  
	}
      print '</p><br />';
    }
  
  site_project_footer(array()); 
  
} 
else 
{
  exit_no_group();
}
