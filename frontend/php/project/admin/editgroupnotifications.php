<?php
# Edit notifications.
#
#  Copyright (C) 2003-2004 Yves Perrin <Yves.Perrin@cern.ch>
#  Copyright (C) 2003-2004 Mathieu Roy <yeupou--at--gnu.org>
#  Copyright (C) 2017, 2018, 2022 Ineiev
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

session_require (['group' => $group_id, 'admin_flags' => 'A']);

extract (sane_import ('post',
  [
    'true' => 'update', 'pass' => 'form_news_address',
    'digits' => [['form_frequency', [0, 3]]],
  ]
));

if (empty ($form_news_address))
  $form_news_address = '';

$artifacts = [
  'bugs' => _("Bug Tracker Email Notification Settings"),
  'support' => _("Support Tracker Email Notification Settings"),
  'task' => _("Task Tracker Email Notification Settings"),
  'patch' => _("Patch Tracker Email Notification Settings"),
  'cookbook' => _("Cookbook Manager Email Notification Settings"),
];

if ($update)
  {
    group_add_history ('Changed Group Notification Settings', '', $group_id);
    foreach ($artifacts as $art => $label)
      trackers_data_post_notification_settings ($group_id, $art);
    db_execute (
      "UPDATE groups SET new_news_address = ? WHERE group_id = ?",
      [$form_news_address, $group_id]
    );

    if (group_set_preference($group_id, "batch_frequency", $form_frequency))
      fb(_("Successfully Updated Reminder Settings"));
    else
      fb(_("Failed to Update Reminder Setting"), 1);

    if (group_get_preference($group_id, "batch_lastsent") == "")
      {
        if (group_set_preference($group_id, "batch_lastsent", "0"))
          fb(_("Successfully set Timestamp of the Latest Reminder"));
        else
          fb(_("Failed to Reset Timestamp of the Latest Reminder"), 1);
      }
  }

$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));
if (db_numrows($res_grp) < 1)
  exit_no_group();
$row_grp = db_fetch_array($res_grp);

site_project_header (
  ['title' => _("Set Notifications"),'group' => $group_id, 'context' => 'ahome']
);
print "<form action=\"" . htmlentities ($_SERVER['PHP_SELF'])
  . "\" method='post'>\n<input type='hidden' name='group_id' "
  . "value=\"$group_id\" />\n";

function print_h2 ($x)
{
  print '<h2>' . $x . "</h2>\n";
}

foreach ($artifacts as $art => $label)
  {
    print_h2 ($label);
    trackers_data_show_notification_settings ($group_id, $art, 0);
    print "<br />\n";
  }
$news_address = htmlspecialchars ($row_grp['new_news_address']);
print_h2 (_("News Manager Email Notification Settings"));
print '<span class="preinput">' . _("Carbon-Copy List:")
  . "</span><br />\n&nbsp;&nbsp;<input type='text' name='form_news_address' "
  . "value=\"{$news_address}\" size=\"40\" maxlength=\"255\" />"
  . "<br /><br />\n";

print_h2 (_("Reminders"));
print '<p>'
  . _("You can configure the project so that reminder emails get sent
to project members who have opened items with priority higher than 5 assigned
to them.")
  . "<br/>\n<span class='warn'>"
  . _("This will be done regardless of the
fact project members have or have not requested to receive such reminders via
their personal notification settings!")
  . "</span></p>\n";
$frequency = array("0" =>
# TRANSLATORS: this is frequency.
                          _("Never"),
                   "1" => _("Daily"),
                   "2" => _("Weekly"),
                   "3" => _("Monthly"));

print '<span class="preinput">' . _("Frequency of reminders:")
  . "</span>\n&nbsp;&nbsp;";
print html_build_select_box_from_array($frequency,
                                       "form_frequency",
                                       group_get_preference($group_id,
                                                            "batch_frequency"));
print "\n<p align='center'><input type='submit' name='update' value='"
  . _("Update") . "' />\n</form>\n";
site_project_footer(array());
?>
