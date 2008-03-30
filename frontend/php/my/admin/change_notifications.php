<?php
# <one line to give a brief idea of what this does.>
# 
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
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


//   Previous overcomplicated code, 
//   see task #4080 and task #3632
// $user_id = user_getid();
// # get notification roles
// # get notification roles
// $res_roles = trackers_data_get_notification_roles();
// $num_roles = db_numrows($res_roles);
// $i=0;
// while ($arr = db_fetch_array($res_roles))
// {
//   $arr_roles[$i] = $arr; $i++;
// }

// # get notification events
// $res_events = trackers_data_get_notification_events();
// $num_events = db_numrows($res_events);
// $i=0;
// while ($arr = db_fetch_array($res_events))
// {
//   $arr_events[$i] = $arr; $i++;
// }

// # build the default notif settings in case the user has not yet defined her own
// # By default it's all 'yes'
// for ($i=0; $i<$num_roles; $i++)
// {
//   $role_id = $arr_roles[$i]['role_id'];
//   for ($j=0; $j<$num_events; $j++)
//     {
//       $event_id = $arr_events[$j]['event_id'];
//       $arr_notif[$role_id][$event_id] = 1;
//     }
// }

// # Overwrite with user settings if any
// $res_notif = trackers_data_get_notification($user_id);
// while ($arr = db_fetch_array($res_notif))
// {
//   $arr_notif[$arr['role_id']][$arr['event_id']] = $arr['notify'];
// }

/*  ==================================================
    The form has been submitted - update the database
 ================================================== */

if ($update)
{
//   Previous overcomplicated code, 
//   see task #4080 and task #3632
//
//   ######### Event/Role specific settings
//   for ($i=0; $i<$num_roles; $i++)
//     {
//       $role_id = $arr_roles[$i]['role_id'];
//       for ($j=0; $j<$num_events; $j++)
// 	{
// 	  $event_id = $arr_events[$j]['event_id'];
// 	  $cbox_name = 'cb-'.$role_id.'-'.$event_id;
// 	  #print "DBG $cbox_name -> '".$$cbox_name."'<br />";
// 	  $arr_notif[$role_id][$event_id] = ( $$cbox_name ? 1 : 0);
// 	}
//     }
//   trackers_data_delete_notification($user_id);
//   $res_notif = trackers_data_insert_notification($user_id, $arr_roles, $arr_events, $arr_notif);
//
//   # Give Feedback
//   if ($res_notif)
//     { fb(_("Successfully updated notification by role settings")); }
//   else
//     { fb(_("Failed to update notification by role settings"), 1); }

  ######### Item Notif exceptions
  $success = false;
  if ($form_notifset_unless_im_author)
    { $success += user_set_preference("notify_unless_im_author", 1); }
  else
    { $success += user_unset_preference("notify_unless_im_author"); }

  if ($form_notifset_item_closed)
    { $success += user_set_preference("notify_item_closed", 1); }
  else
    { $success += user_unset_preference("notify_item_closed"); }
  
  if ($form_notifset_item_statuschanged)
    { $success += user_set_preference("notify_item_statuschanged", 1); }
  else
    { $success += user_unset_preference("notify_item_statuschanged"); }

  if ($form_skipcc_postcomment)
    { $success += user_set_preference("skipcc_postcomment", 1); }
  else
    { $success += user_unset_preference("skipcc_postcomment"); }

  if ($form_skipcc_updateitem)
    { $success += user_set_preference("skipcc_updateitem", 1); }
  else
    { $success += user_unset_preference("skipcc_updateitem"); }
  
  if ($form_removecc_notassignee)
    { $success += user_set_preference("removecc_notassignee", 1); }
  else
    { $success += user_unset_preference("removecc_notassignee"); }


  if ($success == 6)
    { fb(_("Successfully set Notification Exceptions")); }
  else
    { fb(_("Failed to set Notification Exceptions"), 1); }


  ######### Reminder
  if (user_set_preference("batch_frequency", $form_frequency))
    { fb(_("Successfully Updated Reminder Settings")); }
  else
    { fb(_("Failed to Update Reminder Setting"), 1); }

  if (user_get_preference("batch_lastsent") == "")
    {
      if (user_set_preference("batch_lastsent", "0"))
	{ fb(_("Successfully set Timestamp of the Latest Reminder")); }
      else
	{ fb(_("Failed to Reset Timestamp of the Latest Reminder"), 1); }
    }

  ####### Subject line
  # First test content: to avoid people entering white space and being in
  # trouble at a later point, first check if we can find something else than
  # white space
  $form_subject_line = $form_subject_line;
  if (preg_replace("/ /", "", $form_subject_line))
    {
      # Some characters cannot be allowed
      if (strspn($form_subject_line,'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_[]()&יטא=$ש*:!,;?./%$ <>|') == strlen($form_subject_line))
	{
	  user_set_preference("subject_line", $form_subject_line);
	  fb(_("Successfully configured subject line"));
	}
      else
	{ fb(_("Non alphanumeric characters in the proposed subject line, subject line configuration skipped."), 1); }
    }
  else
    {
      # Empty? Check if there is a configuration already. If so, kill it.
      if (user_get_preference("subject_line"))
	{
	  user_unset_preference("subject_line");
	}

    }



}
# end submit


/*  ==================================================
    Start HTML
 ================================================== */

site_user_header(array('title'=>_("Mail Notification Settings"),'context'=>'account'));


print '<h3>'._("Notification Exceptions").'</h3>';
print '<p>'._("When you post and update items, you are automatically added into items Carbon-Copy list to receive notifications regarding future updates. You can always remove yourself from the item Carbon-Copy list.").'</p><p>'._("If an item is assigned to you, you will receive notifications as long as you are assignee but, however, you will not be added to the Carbon-Copy list. If do not post any comment or update to the item while you are assignee, if the item get reassigned, you will not receive further updates notifications.").'</p><p>'._("Here, you can tune your notification settings. For instance, you can decide in which circonstances you do not want to be notified even if you are in the Carbon-Copy list of an item.").'</p>
';


print '
'.form_header($_SERVER['PHP_SELF']);


print '<span class="preinput">'._("Send notification to me only when:").'</span><br />&nbsp;&nbsp;';

$checked = '';
if (user_get_preference("notify_unless_im_author")) { $checked = 'checked="checked"'; }
print form_input("checkbox", "form_notifset_unless_im_author", "1", $checked).' '._("I am not the author of the item update").'<br />&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("notify_item_closed")) { $checked = 'checked="checked"'; }
print form_input("checkbox", "form_notifset_item_closed", "1", $checked).' '._("the item was closed").'<br />&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("notify_item_statuschanged")) { $checked = 'checked="checked"'; }
print form_input("checkbox", "form_notifset_item_statuschanged", "1", $checked).' '._("the item status changed").'<br />';

print '<span class="preinput">'._("Do not add me in Carbon-Copy when:").'</span><br />&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("skipcc_postcomment")) { $checked = 'checked="checked"'; }
print form_input("checkbox", "form_skipcc_postcomment", "1", $checked).' '._("I post a comment").'<br />&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("skipcc_updateitem")) { $checked = 'checked="checked"'; }
print form_input("checkbox", "form_skipcc_updateitem", "1", $checked).' '._("I update a field, add dependancies, attach file, etc").'<br />';
$checked = '';

print '<span class="preinput">'._("Remove me from Carbon-Copy when:").'</span><br />&nbsp;&nbsp;';
$checked = '';
if (user_get_preference("removecc_notassignee")) { $checked = 'checked="checked"'; }
print form_input("checkbox", "form_removecc_notassignee", "1", $checked).' '._("I am no longer assigned to the item").'<br />&nbsp;&nbsp;';





//  Previous overcomplicated code, 
//  see task #4080 and task #3632

// print '
// <table class="box">
// <tr>
//     <td colspan="'.$num_roles.'" align="center" width="50%" class="boxtitle">'._("If my role in an item is:").'</td>
//     <td rowspan="2" width="50%" class="boxtitle">'._("I want to be notified when:").'</td>
// </tr>';

// for ($i=0; $i<$num_roles; $i++)
// {
//   print '<td align="center" width="10%" class="boxtitle"><span class="smaller">'.$arr_roles[$i]['short_description']."</span></td>\n";
// }
// print "</tr>\n";

// for ($j=0; $j<$num_events; $j++)
// {
//   $event_id = $arr_events[$j]['event_id'];
//   $event_label = $arr_events[$j]['event_label'];
//   print '<tr class="'.utils_get_alt_row_color($j)."\">\n";
//   for ($i=0; $i<$num_roles; $i++)
//     {
//       $role_id = $arr_roles[$i]['role_id'];
//       $role_label = $arr_roles[$i]['role_label'];
//       $cbox_name = 'cb-'.$role_id.'-'.$event_id;
//       if ((($event_label == 'NEW_ITEM') && ($role_label != 'ASSIGNEE') && ($role_label != 'SUBMITTER')) )
// 	{
// 	  # if the user is not an assignee or a submitter the new_item event is meaningless 
// 	  print '   <td align="center"><input type="hidden" name="'.$cbox_name.'" value="1" />-</td>'."\n";
// 	}
//       else
// 	{
// 	  print '   <td align="center"><input type="checkbox" name="'.$cbox_name.'" value="1" '.
// 	    ($arr_notif[$role_id][$event_id] ? 'checked="checked"':'')." /></td>\n";
// 	}
//     }
//   print '   <td>'.$arr_events[$j]['description']."</td>\n";
//   print "</tr>\n";
// }

// print'
// </table>
// ';


print '<br /><h3>'._("Subject Line").'</h3>';
print '<p>'.sprintf(_('The header "%s" will always be included, and when applicable, so will "%s", "%s", and/or "%s".'), "X-Savane-Server", "X-Savane-Project", "X-Savane-Tracker", "X-Savane-Item-ID").'</p><p>'.sprintf(_('Another option for message filtering is to configure the prefix of the subject line with the following form. In this form, you can use the strings "%s", "%s", "%s", and "%s". They will be replaced by the appropriate values. If you leave this form empty, you will receive the default subject line.'), "%SERVER", "%PROJECT", "%TRACKER", "%ITEM").'
</p>
';

$frequency = array("0" => _("None"),
		   "1" => _("Daily"),
		   "2" => _("Weekly"),
		   "3" => _("Monthly"));

print '<span class="preinput">'._("Subject Line:").'</span><br />&nbsp;&nbsp;';
print '<input name="form_subject_line" size="50" type="text" value="'.user_get_preference("subject_line").'" />';


print '<br /><h3>'._("Reminder").'</h3>';
print '<p>'._("You can also receive reminders about opened items assigned to you, when their priority is higher than 5. Note that projects administrators can also set reminders for you, out of your control, for your activities on the project they administer.").'</p>
';
$frequency = array("0" => _("None"),
		   "1" => _("Daily"),
		   "2" => _("Weekly"),
		   "3" => _("Monthly"));

print '<span class="preinput">'._("Frequency of reminders:").'</span><br />&nbsp;&nbsp;';
print html_build_select_box_from_array($frequency,
				       "form_frequency",
				       user_get_preference("batch_frequency"));



print '<br />'.form_footer(_("Update"));

site_user_footer(array());
