<?php
# Changing notifications.
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2018 Ineiev
# Modified 2016 Karl Berry (trivial wording changes)
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
require_once('../../include/account.php');
require_directory("trackers");
register_globals_off();

extract(sane_import('post',
  array('update',
        'form_notifset_unless_im_author',
        'form_notifset_item_closed',
        'form_notifset_item_statuschanged',
        'form_skipcc_postcomment',
        'form_skipcc_updateitem',
        'form_removecc_notassignee',
        'form_frequency',
        'form_subject_line',
        )));

# The form has been submitted - update the database.
if ($update)
  {
  # Item Notif exceptions
    $success = false;
    if ($form_notifset_unless_im_author)
      $success += user_set_preference("notify_unless_im_author", 1);
    else
      $success += user_unset_preference("notify_unless_im_author");

    if ($form_notifset_item_closed)
      $success += user_set_preference("notify_item_closed", 1);
    else
      $success += user_unset_preference("notify_item_closed");

    if ($form_notifset_item_statuschanged)
      $success += user_set_preference("notify_item_statuschanged", 1);
    else
      $success += user_unset_preference("notify_item_statuschanged");

    if ($form_skipcc_postcomment)
      $success += user_set_preference("skipcc_postcomment", 1);
    else
      $success += user_unset_preference("skipcc_postcomment");

    if ($form_skipcc_updateitem)
      $success += user_set_preference("skipcc_updateitem", 1);
    else
      $success += user_unset_preference("skipcc_updateitem");

    if ($form_removecc_notassignee)
      $success += user_set_preference("removecc_notassignee", 1);
    else
      $success += user_unset_preference("removecc_notassignee");
    if ($success == 6)
      fb(_("Successfully set Notification Exceptions"));
    else
      fb(_("Failed to set Notification Exceptions"), 1);

  # Reminder
    if (user_set_preference("batch_frequency", $form_frequency))
      fb(_("Successfully Updated Reminder Settings"));
    else
      fb(_("Failed to Update Reminder Setting"), 1);

    if (user_get_preference("batch_lastsent") == "")
      {
        if (user_set_preference("batch_lastsent", "0"))
          fb(_("Successfully set Timestamp of the Latest Reminder"));
        else
          fb(_("Failed to Reset Timestamp of the Latest Reminder"), 1);
      }
  ####### Subject line
  # First test content: to avoid people entering white space and being in
  # trouble at a later point, first check if we can find something else than
  # white space.
    $form_subject_line = $form_subject_line;
    if (preg_replace("/ /", "", $form_subject_line))
      {
        # Some characters cannot be allowed
        if (strspn($form_subject_line,
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVW'
            .'XYZ0123456789-_[]()&יטא=$ש*:!,;?./%$ <>|')
            == strlen($form_subject_line))
          {
            user_set_preference("subject_line", $form_subject_line);
            fb(_("Successfully configured subject line"));
          }
        else
          fb(_("Non alphanumeric characters in the proposed subject line, subject
line configuration skipped."), 1);
      }
    else
      {
      # Empty? Check if there is a configuration already. If so, kill it.
        if (user_get_preference("subject_line"))
          user_unset_preference("subject_line");
      }
  } # if ($update)

# Start HTML.
site_user_header(array('title'=>_("Mail Notification Settings"),
                       'context'=>'account'));

print '<h2>'._("Notification Exceptions").'</h2>';
print '<p>'._("When you post or update an item, you are automatically added to
its Carbon-Copy list to receive notifications regarding future updates. You can
always remove yourself from an item Carbon-Copy list.")
  ."</p>\n<p>"
  ._("If an item is assigned to you, you will receive notifications as long as
you are the assignee; however, you will not be added to the Carbon-Copy list.
If you do not post any comment or update to the item while you are the
assignee, and the item gets reassigned, you will not receive further update
notifications.")
  ."</p>\n<p>"
  ._("Here, you can tune your notification settings.").'</p>
';

print '
'.form_header($_SERVER['PHP_SELF']);

print '<span class="preinput">'
      ._("Send notification to me only when:").'</span><br />&nbsp;&nbsp;';

$checked = '';
if (user_get_preference("notify_unless_im_author"))
  $checked = 'checked="checked"';
print form_input("checkbox", "form_notifset_unless_im_author", "1", $checked)
      .' <label for="form_notifset_unless_im_author">'
      ._("I am not the author of the item update").'</label><br />
&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("notify_item_closed"))
  $checked = 'checked="checked"';
print form_input("checkbox", "form_notifset_item_closed", "1", $checked)
      .' <label for="form_notifset_item_closed">'
      ._("the item was closed").'</label><br />
&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("notify_item_statuschanged"))
  $checked = 'checked="checked"';
print form_input("checkbox", "form_notifset_item_statuschanged", "1", $checked)
      .' <label for="form_notifset_item_statuschanged">'
      ._("the item status changed").'</label><br />'."\n";

print '<span class="preinput">'._("Do not add me in Carbon-Copy when:")
      .'</span><br />
&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("skipcc_postcomment"))
  $checked = 'checked="checked"';
print form_input("checkbox", "form_skipcc_postcomment", "1", $checked)
      .' <label for="form_skipcc_postcomment">'
      ._("I post a comment").'</label><br />
&nbsp;&nbsp;'."\n";
$checked = '';
if (user_get_preference("skipcc_updateitem"))
  $checked = 'checked="checked"';
print form_input("checkbox", "form_skipcc_updateitem", "1", $checked)
      .' <label for="form_skipcc_updateitem">'
      ._("I update a field, add dependencies, attach file, etc").'<br />'."\n";
$checked = '';

print '<span class="preinput">'._("Remove me from Carbon-Copy when:")
      .'</span><br />
&nbsp;&nbsp;'."\n";
$checked = '';
if (user_get_preference("removecc_notassignee"))
  $checked = 'checked="checked"';
print form_input("checkbox", "form_removecc_notassignee", "1", $checked)
      .' <label for="form_removecc_notassignee">'
      ._("I am no longer assigned to the item").'</label><br />
&nbsp;&nbsp;'."\n";

print '<h2>'._("Subject Line").'</h2>'."\n";
print '<p>';
printf(_('The header &ldquo;%s&rdquo; will always be included, and when
applicable, so will &ldquo;%s,&rdquo; &ldquo;%s,&rdquo; and &ldquo;%s.&rdquo;'),
       "X-Savane-Server", "X-Savane-Project", "X-Savane-Tracker",
       "X-Savane-Item-ID");
print "</p>\n<p>";
printf(_('Another option for message filtering is to configure the prefix of
the subject line with the following form. In this form, you can use the strings
&ldquo;%s,&rdquo; &ldquo;%s,&rdquo; &ldquo;%s,&rdquo; and &ldquo;%s.&rdquo;
They will be replaced by the appropriate values. If you leave this form empty,
you will receive the default subject line.'),
       "%SERVER", "%PROJECT", "%TRACKER", "%ITEM");
print '</p>
';

print '<span class="preinput"><label for="form_subject_line">'
      ._("Subject Line:").'</label></span><br />
&nbsp;&nbsp;';
print
'<input name="form_subject_line" id="form_subject_line" size="50"
        type="text" value="'.user_get_preference("subject_line").'" />';

print '<h2>'._("Reminder").'</h2>'."\n";
print '<p>'._("You can also receive reminders about opened items assigned to
you, when their priority is higher than 5. Note that projects administrators
can also set reminders for you, out of your control, for your activities on the
project they administer.").'</p>
';

$frequency = array("0" =>
# TRANSLATORS: this is frequency.
                          _("Never"),
                   "1" => _("Daily"),
                   "2" => _("Weekly"),
                   "3" => _("Monthly"));

print '<span class="preinput"><label for="form_frequency">'
      ._("Frequency of reminders:")
      .'</label></span><br />
&nbsp;&nbsp;';

print html_build_select_box_from_array($frequency,
                                       "form_frequency",
                                       user_get_preference("batch_frequency"));
print '<br />
'.form_footer(_("Update"));
site_user_footer(array());
?>
