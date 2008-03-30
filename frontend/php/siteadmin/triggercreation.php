<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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
require_once('../include/proj_email.php');

# Skip admin rights check if we are dealing with the sys group
if ($GLOBALS['sys_group_id'] != $group_id)
{ session_require(array('group'=>'1','admin_flags'=>'A')); }

################
# Configure the project according to group type settings:
#   If a project can use a feature for its group type, assume he would
#   use it by default
#   Exception: the patch tracker is deprecated, so it is ignored.
$group_type = db_result(db_execute("SELECT type FROM groups WHERE group_id=?", array($group_id)),0,'type');
$res_type = db_execute("SELECT * FROM group_type WHERE type_id=?", array($group_type));
$user_id = user_getid();

$to_update = array("homepage", "download", "cvs", "forum","mailing_list","task","news","support","bug");
$upd_list = array();
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
  $upd_list[$field] = $value;
}

if ($upd_list)
{
  $result=db_affected_rows(db_autoexecute('groups', $upd_list, DB_AUTOQUERY_UPDATE,
					  "group_id=?", array($group_id)));

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
$to_update = '';
$upd_list = array();

# Build the notification list
$res_admins = db_execute("SELECT user.user_name FROM user,user_group WHERE "
			 . "user.user_id=user_group.user_id AND user_group.group_id=? AND "
			 . "user_group.admin_flags='A'", array($group_id));
if (db_numrows($res_admins) > 0)
{
  $admin_list = '';
  while ($row_admins = db_fetch_array($res_admins))
    {
      $admin_list .= ($admin_list ? ', ':'').$row_admins['user_name'];
    }
  
  $to_update = array("news", "support", "task", "bugs", "patch", "cookbook");
  while (list(,$field) = each($to_update))
    {
      $upd_list["new_".$field."_address"] = $admin_list;
      if ($field != "news")
	{
	  $upd_list["send_all_".$field] = $value;
	}
    }
}
if ($upd_list)
{
  # strip the excess comma at the end of the update field list
  $result=db_affected_rows(db_autoexecute('groups', $upd_list, DB_AUTOQUERY_UPDATE, "group_id=?", array($group_id)));

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
