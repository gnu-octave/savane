<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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
require "../include/proj_email.php";

# Skip admin rights check if we are dealing with the sys group
if ($GLOBALS['sys_group_id'] != $group_id)
{ session_require(array('group'=>'1','admin_flags'=>'A')); }

################
# Configure the project according to group type settings:
#   If a project can use a feature for its group type, assume he would
#   use it by default
#   Exception: the patch tracker is deprecated, so it is ignored.
$group_type = db_result(db_query("SELECT type FROM groups WHERE group_id=$group_id"),0,'type');
$res_type = db_query("SELECT * FROM group_type WHERE type_id=$group_type");

$to_update = array("homepage", "download", "cvs", "forum","mailing_list","task","news","support","bug");
unset($upd_list);
while (list(,$field) = each($to_update))
{
  # bug = bugs, mailing_list = mail
  $value = db_result($res_type, 0, 'can_use_'.$field);
  
  if ($field == 'mailing_list')
      { $field = 'mail'; }
  if ($field == 'bug')
      { $field = 'bugs'; }
  $field = 'use_'.$field;

  fb(sprintf(_("%s will be set to %s"),$field, $value));
  $upd_list .= "$field='$value',";
}

if ($upd_list)
{
  # strip the excess comma at the end of the update field list
  $upd_list = substr($upd_list,0,-1);
  
  $sql="UPDATE groups SET $upd_list ".
     " WHERE group_id='$group_id'";
  $result=db_affected_rows(db_query($sql));

  if (!$result)
    { 
      fb(_("No field to update or SQL error"));

    }
  else
    { 
      fb_dbsuccess();  
      group_add_history('Set Active Features to the default for the Group Type',user_getname($user_id),$group_id);
    }
}

################
# Now set a default notification setup for the trackers. 
# We do not even check whether the trackers are used, because we want this
# configuration to be already done if at some point the tracker gets activated,
# if it is not the case by default.
unset($to_update, $upd_list);

# Build the notification list
$res_admins = db_query("SELECT user.user_name FROM user,user_group WHERE "
		       . "user.user_id=user_group.user_id AND user_group.group_id='$group_id' AND "
		       . "user_group.admin_flags='A'");
if (db_numrows($res_admins) > 0)
{
  unset($admin_list);
  while ($row_admins = db_fetch_array($res_admins))
    {
      $admin_list .= ($admin_list ? ', ':'').$row_admins['user_name'];
    }
  
  $to_update = array("news", "support", "task", "bugs", "patch", "cookbook");
  while (list(,$field) = each($to_update))
    {
      $upd_list .= "new_".$field."_address='$admin_list',";
      if ($field != "news")
	{
	  $upd_list .= "send_all_".$field."='$value',";
	}
    }
}
if ($upd_list)
{
  # strip the excess comma at the end of the update field list
  $upd_list = substr($upd_list,0,-1);
  
  $sql="UPDATE groups SET $upd_list ".
     " WHERE group_id='$group_id'";
  $result=db_affected_rows(db_query($sql));

  if (!$result)
    { 
      fb(_("No field to update or SQL error"));

    }
  else
    { 
      fb_dbsuccess();  
      group_add_history('Set Mail Notification to a sensible default',user_getname($user_id),$group_id);
    }
}


################
# Send email and do site specific triggered stuff that comes along
send_new_project_email($group_id);
fb(_("Mail sent, site-specific triggers executed"));

if ($GLOBALS['sys_group_id'] != $group_id)
{ 
  site_admin_header(array('title'=>"Project Creation Trigger"));
  site_admin_footer(array());
}
else
{
  site_header(array('title'=>_("Local Administration Project Approved")));
  site_footer(array());
}
?>
