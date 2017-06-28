<?php
# Edit notifications.
# 
#  Copyright (C) 2003-2004 Yves Perrin <Yves.Perrin@cern.ch>
#  Copyright (C) 2003-2004 Mathieu Roy <yeupou--at--gnu.org>
#  Copyright (C) 2017 Ineiev
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


require_once('../../include/init.php');
require_once('../../include/vars.php');

require_directory("trackers");

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

extract(sane_import('post', array('update',
  'form_news_address',
  'form_frequency',
)));
# Other imports in trackers_data_*

if ($update)
{

  group_add_history('Changed Group Notification Settings','',$group_id);

  $res_new = trackers_data_post_notification_settings($group_id, "bugs");
  $res_new = trackers_data_post_notification_settings($group_id, "support");
  $res_new = trackers_data_post_notification_settings($group_id, "task");
  $res_new = trackers_data_post_notification_settings($group_id, "patch");
  $res_new = trackers_data_post_notification_settings($group_id, "cookbook");
  db_execute("UPDATE groups SET "
	     ."new_news_address=?"
	     . " WHERE group_id=?",
	     array($form_news_address ? $form_news_address : '', $group_id));

  ######### Reminder
  if (group_set_preference($group_id, "batch_frequency", $form_frequency))
    { fb(_("Successfully Updated Reminder Settings")); }
  else
    { fb(_("Failed to Update Reminder Setting"), 1); }

  if (group_get_preference($group_id, "batch_lastsent") == "")
    { 
      if (group_set_preference($group_id, "batch_lastsent", "0"))
	{ fb(_("Successfully set Timestamp of the Latest Reminder")); }
      else
	{ fb(_("Failed to Reset Timestamp of the Latest Reminder"), 1); }
    }
}

# update info for page
$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));
if (db_numrows($res_grp) < 1)
{
  exit_no_group();
}
$row_grp = db_fetch_array($res_grp);


site_project_header(array('title'=>_("Set Notifications"),'group'=>$group_id,
                    'context'=>'ahome'));

# ####################################### General Description

print '
<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="group_id" value="'.$group_id.'" />';

print '<h3>'._("Bug Tracker Email Notification Settings").'</h3>
';
trackers_data_show_notification_settings($group_id, 'bugs', 0);
print '<br />
';

print '<h3>'._("Support Tracker Email Notification Settings").'</h3>
';
trackers_data_show_notification_settings($group_id, 'support', 0);
print '<br />
';

print '<h3>'._("Task Tracker Email Notification Settings").'</h3>
';
trackers_data_show_notification_settings($group_id, 'task', 0);
print '<br />
';

print '<h3>'._("Patch Tracker Email Notification Settings").'</h3>
';
trackers_data_show_notification_settings($group_id, 'patch', 0);
print '<br />
';

print '<h3>'._("Cookbook Manager Email Notification Settings").'</h3>
';
trackers_data_show_notification_settings($group_id, 'cookbook', 0);
print '<br />
';

# yeupou--gnu.org 2004-09-17: in the end, the goal is to make news
# using the common tracker code
print '<h3>'._("News Manager Email Notification Settings").'</h3>
';
print '<span class="preinput">'._("Carbon-Copy List:")
.'</span><br />&nbsp;&nbsp;<input type="text" name="form_news_address" value="'
.$row_grp['new_news_address'].'" size="40" maxlength="255" />';
print '<br /><br />
';

print '<h3>'._("Reminders").'</h3>
';
print '<p>'._("You can configure the project so that reminder emails get sent
to project members who have opened items with priority higher than 5 assigned
to them.").'<br/>
<span class="warn">'._("This will be done regardless of the
fact project members have or have not requested to receive such reminders via
their personal notification settings!").'</span></p>
';
$frequency = array("0" => _("None"),
		   "1" => _("Daily"),
		   "2" => _("Weekly"),
		   "3" => _("Monthly"));

print '<span class="preinput">'._("Frequency of reminders:")
      .'</span> &nbsp;&nbsp;';
print html_build_select_box_from_array($frequency, 
				       "form_frequency", 
				       group_get_preference($group_id,
                                                            "batch_frequency"));
print '
<p align="center"><input type="submit" name="update" value="'._("Update").'" />
</form>
';

site_project_footer(array());
?>
