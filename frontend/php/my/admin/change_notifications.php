<?php
# Changing notifications.
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2018, 2022 Ineiev
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

$notif_arr = [
  'notify_unless_im_author', 'notify_item_closed',
  'notify_item_statuschanged', 'skipcc_postcomment',
  'skipcc_updateitem', 'removecc_notassignee',
];

$names = [
  'true' => ['update'],
  'digits' => [['form_frequency', [0, 3]]],
  'pass' => 'form_subject_line', # Validated later.
];

foreach ($notif_arr as $n)
  $names['true'][] = "form_$n";

extract (sane_import ('post', $names));

function update_subject_line ($form_subject_line)
{
  if (!preg_replace("/ /", "", $form_subject_line))
    {
      # Empty line requested? Clear if set.
      if (user_get_preference("subject_line"))
        user_unset_preference("subject_line");
      return;
    }
  if (
    strspn (
      $form_subject_line,
      'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVW'
      . 'XYZ0123456789-_[]()&=$*:!,;?./%$ <>|'
    )
    != strlen ($form_subject_line)
  )
    {
      fb(_("Non alphanumeric characters in the proposed subject line, subject
line configuration skipped."), 1);
      return;
    }
  if (user_set_preference ("subject_line", $form_subject_line))
    fb (_("Successfully configured subject line."));
}

if ($update)
  {
    $success = false;
    foreach ($notif_arr as $n)
      if (${"form_$n"})
        $success += user_set_preference ($n, 1);
      else
        $success += user_unset_preference ($n);

    if ($success == count ($notif_arr))
      fb(_("Successfully set notification exceptions."));
    else
      fb(_("Failed to set notification exceptions."), 1);

    if (user_set_preference("batch_frequency", $form_frequency))
      fb(_("Successfully updated reminder settings."));
    else
      fb(_("Failed to update reminder setting."), 1);

    if (user_get_preference("batch_lastsent") == "")
      user_set_preference ("batch_lastsent", "0");

    update_subject_line ($form_subject_line);
  } # if ($update)

site_user_header(array('title'=>_("Mail Notification Settings"),
                       'context'=>'account'));

print '<h2>' . _("Notification Exceptions") . "</h2>\n";
print '<p>'
  . _("When you post or update an item, you are automatically added to
its Carbon-Copy list to receive notifications regarding future updates. You can
always remove yourself from an item Carbon-Copy list.")
  . "</p>\n<p>"
  . _("If an item is assigned to you, you will receive notifications as long as
you are the assignee; however, you will not be added to the Carbon-Copy list.
If you do not post any comment or update to the item while you are the
assignee, and the item gets reassigned, you will not receive further update
notifications.")
  . "</p>\n<p>"
  . _("Here, you can tune your notification settings.") . "</p>\n";

print "\n" . form_header($_SERVER['PHP_SELF']);

print '&nbsp;&nbsp;<span class="preinput">'
  . _("Send notification to me only when:") . "</span><br />\n&nbsp;&nbsp;";

function pref_cbox ($name, $title)
{
  print form_checkbox ("form_$name", user_get_preference ($name));
  print " <label for=\"form_$name\">$title</label><br />\n&nbsp;&nbsp;";
}

$pref_arr = [
  "notify_unless_im_author" => _("I am not the author of the item update"),
  "notify_item_closed" => _("the item was closed"),
  "notify_item_statuschanged" => _("the item status changed"),
];

foreach ($pref_arr as $n => $t)
  pref_cbox ($n, $t);

print '<span class="preinput">' . _("Do not add me to Carbon-Copy when:")
  . "</span><br />\n&nbsp;&nbsp;";


$pref_arr = [
  "skipcc_postcomment" => _("I post a comment"),
  "skipcc_updateitem" =>
    _("I update a field, add dependencies, attach file, etc"),
];

foreach ($pref_arr as $n => $t)
  pref_cbox ($n, $t);

print '<span class="preinput">' . _("Remove me from Carbon-Copy when:")
  . "</span><br />\n&nbsp;&nbsp;";

pref_cbox ("removecc_notassignee", _("I am no longer assigned to the item"));

print '<h2>' . _("Subject Line") . "</h2>\n";
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

print '<h2>' . _("Reminder") . "</h2>\n";
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
  . _("Frequency of reminders:") . "</label></span><br />\n&nbsp;&nbsp;";

print html_build_select_box_from_array (
  $frequency, "form_frequency", user_get_preference("batch_frequency")
);
print "<br />\n" . form_footer (_("Update"));
site_user_footer(array());
?>
