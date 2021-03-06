<?php
# Simple way of wrapping our SQL so it can be
# shared among the XML outputs and the PHP web front-end.
# Also abstracts controls to update data.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2018, 2019, 2020, 2022 Ineiev
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

$dir_name = dirname (__FILE__);
require_once ("$dir_name/../trackers/transition.php");
require_once ("$dir_name/../trackers/cookbook.php");

# Get all the possible bug fields for this project both used and unused. If
# used then show the project specific information about field usage
# otherwise show the default usage parameter.
# Make sure array element are sorted by ascending place.
function trackers_data_get_all_fields ($group_id = false, $reload = false)
{
  global $BF_USAGE_BY_ID, $BF_USAGE_BY_NAME, $AT_START;

  if (!ctype_alnum (ARTIFACT))
    die ("Invalid ARTIFACT name: " . htmlspecialchars (ARTIFACT ));

  # Do nothing if already set and reload not forced.
  if (isset ($BF_USAGE_BY_ID) && !$reload)
    return;

  $BF_USAGE_BY_ID = $BF_USAGE_BY_NAME = [];

  # First get the all the defaults.
  $art_field = ARTIFACT . '_field';
  $sql = "
    SELECT
      af.bug_field_id, field_name, display_type, display_size, label,
      description, scope, required, empty_ok, keep_history, special, custom,
      group_id, use_it, show_on_add, show_on_add_members, place, custom_label,
      custom_description, custom_display_size, custom_empty_ok,
      custom_keep_history
    FROM $art_field af, ${art_field}_usage afu
    WHERE group_id = ? AND af.bug_field_id = afu.bug_field_id";

  $res_defaults = db_execute ($sql, [100]);

  # Put all used fields in a global array for faster access.
  # Index both by field_name and bug_field_id.
  while ($field_array = db_fetch_array ($res_defaults))
    {
      $BF_USAGE_BY_ID[$field_array['bug_field_id'] ] = $field_array;
      $BF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

  # Select all project-specific entries.
  $res_project = db_execute ($sql, [$group_id]);

  # Override entries in the default array.
  while ($field_array = db_fetch_array ($res_project))
    {
      $BF_USAGE_BY_ID[$field_array['bug_field_id'] ] = $field_array;
      $BF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

  # Rewind internal pointer of global arrays.
  reset ($BF_USAGE_BY_ID);
  reset ($BF_USAGE_BY_NAME);
  $AT_START = true;
}

function trackers_data_get_item_group ($item_id)
{
  $res = db_execute (
    "SELECT group_id FROM " . ARTIFACT . " WHERE bug_id = ?", [$item_id]
  );
  return db_result ($res, 0, 'group_id');
}

function &trackers_data_get_notification_settings ($group_id, $tracker)
{
  assert ('ctype_alnum($tracker)');

  $result = db_execute (
    "SELECT * FROM groups WHERE group_id = ?", [$group_id]
  );
  if (db_numrows ($result) < 1)
    exit_no_group ();

  $settings = [];

  $settings['glnotif'] = db_result ($result, 0, "{$tracker}_glnotif");
  $settings['glsendall'] = db_result ($result, 0, "send_all_$tracker");
  $settings['glnewad'] = db_result ($result, 0, "new_{$tracker}_address");
  $settings['private_exclude'] = db_result (
    $result, 0, "{$tracker}_private_exclude_address"
  );
  $cat_field_name = "category_id";
  # Warning: The hardcoded fiels names: bug_fv_id and bug_field_id will need
  # to be changed one day to generic names since they apply to bugs but also
  # to suuports,tasks and patch-related tables. For now these fileds are
  # called bug_xxx whatever the service related tables. Too much work to make
  # all the changes in the code.
  $result = db_execute ("
    SELECT fv.bug_fv_id, fv.value, fv.email_ad, fv.send_all_flag
    FROM {$tracker}_field f, {$tracker}_field_value fv
    WHERE
      fv.group_id = ?  AND f.field_name = ?
      AND fv.bug_field_id = f.bug_field_id AND fv.status != 'H'",
    [$group_id, $cat_field_name]
  );
  $settings['nb_categories'] = db_numrows ($result);
  $settings['category'] = [];
  for ($i = 0; $i < $settings['nb_categories']; $i++)
    {
      $settings['category'][$i] = [];
      $settings['category'][$i]['name'] = db_result ($result, $i, 'value');
      $settings['category'][$i]['fv_id'] =
        db_result ($result, $i, 'bug_fv_id');
      $email = db_result ($result, $i, 'email_ad');
      if ($email == '100')
        $email = "";
      $settings['category'][$i]['email'] = $email;
      $settings['category'][$i]['send_all_flag'] =
        db_result ($result, $i, 'send_all_flag');
    }
  return $settings;
}

function trackers_data_show_notification_settings (
  $group_id, $tracker, $show_intro_msg
)
{
  $grtrsettings = &trackers_data_get_notification_settings (
    $group_id, $tracker
  );
  if (!(user_ismember ($group_id, 'A')))
    return;
  $check = ' checked="checked" ';
  $cat_ck = $glob_ck = $both_ck ='';
  if ($grtrsettings['glnotif'] == 0)
    $cat_ck = $check;
  if ($grtrsettings['glnotif'] == 1)
    $glob_ck = $check;
  if ($grtrsettings['glnotif'] == 2)
    $both_ck = $check;
  $cat_n = $grtrsettings['nb_categories'];
  if ($cat_n > 0)
    {
      if ($show_intro_msg != 0)
          print '<p>'
            . _("Here you can decide whether the lists "
                . "of persons to be notified on new submissions and updates "
                . "depend on item categories, and provide the respective "
                . "email addresses (comma-separated list).")
             . "</p>\n";
      print "<input type='radio' name=\"${tracker}_notif_scope\" "
        . "value='global'$glob_ck/>&nbsp;&nbsp;<span class='preinput'>"
        . _("Notify persons in the global list only") . "</span><br />\n"
        . "<input type='radio' name=\"${tracker}_notif_scope\" "
        . "value='category'$cat_ck/>&nbsp;&nbsp;<span class='preinput'>"
        . _("Notify persons in the category related list "
            . "instead of the global list") . "</span><br />\n"
        . "<input type='radio' name=\"${tracker}_notif_scope\" "
        . "value='both'$both_ck/>&nbsp;&nbsp;<span class='preinput'>"
        . _("Notify persons in the category related list in addition to "
            . "the global list")
        . "</span><br />\n<h2>" . _("Category related lists") . "</h2>\n";
      print "<input type='hidden' name=\"${tracker}_nb_categories\" "
        . "value=\"$cat_n\" />\n";

      for ($i = 0; $i < $cat_n ; $i++)
        {
          $tr_cat = $tracker . '_cat_' . $i;
          $cb_name = $tr_cat . '_send_all_flag';
          $settings = $grtrsettings['category'][$i];
          print '<input type="hidden" name="' . $tr_cat . '_bug_fv_id" value="'
            . $settings['fv_id'] . '" />';
          print "<span class='preinput'><label for=\"${tr_cat}_email\">"
            . $settings['name']
            . "</span><br />\n&nbsp;&nbsp;<input type='text' id=\""
            . "${tr_cat}_email\" name=\"${tr_cat}_email\" value=\""
            . htmlspecialchars ($settings['email'])
            . "\" size='50' maxlength='255' />\n"
            . "&nbsp;&nbsp;<span class='preinput'>("
            . form_checkbox ($cb_name, $settings['send_all_flag'])
            . "<label for=\"$cb_name\">" . _("Send on all updates")
            . ")</label></span><br />\n";
        }
      print '<h2>' . _("Global list") . "</h2>\n";
    }
  elseif ($show_intro_msg != 0)
    print '<p>'
        . _("Here you can decide whether the lists "
            . "of persons to be notified on new submissions and updates "
            . "depend on item categories, and provide the respective "
            . "email addresses (comma-separated list).")
         . "</p>\n";

$cb_name = "{$tracker}_send_all_changes";
$txt_name = "{$tracker}_new_item_address";
  print "<span class='preinput'><label for=\"$txt_name\">"
    . _("Global List:") . "</label></span><br />\n&nbsp;&nbsp;"
    . "<input type='text' id=\"$txt_name\" name=\"$txt_name\""
    . 'value="' . htmlspecialchars ($grtrsettings['glnewad'])
    . '" size="50" maxlength="255" />
      &nbsp;&nbsp;<span class="preinput">('
    . form_checkbox ($cb_name, $grtrsettings['glsendall'])
    . "<label for=\"$cb_name\">"
    . _("Send on all updates") . '</label>)</span>';

  print '<h2>' . _("Private items exclude list") . "</h2>\n";
  if ($show_intro_msg != 0)
    print '<p>'
      . _("Addresses registered in this list will be excluded from default mail
notification for private items.")
      . "</p>\n";

  $txt_name = "${tracker}_private_exclude_address";
  print "<span class='preinput'><label for=\"$txt_name\">"
    . _("Exclude List:") . "</label></span><br />\n&nbsp;&nbsp;"
    . "<input type='text' id=\"$txt_name\" name=\"$txt_name\" value=\""
    . htmlspecialchars ($grtrsettings['private_exclude'])
    . "\" size='50' maxlength='255' /><br />\n";
}

function trackers_data_post_notification_settings ($group_id, $tracker)
{
  $local_feedback = "";
  # Build the variable names related to elements always present in the form
  # and get their values.

  $notif_scope_name = "{$tracker}_notif_scope";
  $new_item_address_name = "{$tracker}_new_item_address";
  $send_all_changes_name = "{$tracker}_send_all_changes";
  $nb_categories_name = "{$tracker}_nb_categories";
  $private_exclude_address_name = "{$tracker}_private_exclude_address";

  $in = sane_import ('post',
    [
      "strings" =>
        [[$notif_scope_name, ['default' => 'global', 'category', 'both']]],
      'pass' => [$new_item_address_name, $private_exclude_address_name],
      'true' => $send_all_changes_name,
      'digits' => $nb_categories_name
    ]
  );

  $notif_scope = $in[$notif_scope_name];
  $new_item_address = $in[$new_item_address_name];
  if (empty ($new_item_address))
    $new_item_address = '';
  $send_all_changes = $in[$send_all_changes_name];
  $nb_categories = $in[$nb_categories_name];
  $private_exclude_address = $in[$private_exclude_address_name];
  if (empty ($private_exclude_address))
    $private_exclude_address = '';

  $notif_value = 1; # global by default.
  if (isset ($notif_scope))
    {
      if ($notif_scope == "category")
        $notif_value = 0;
      if ($notif_scope == "both")
        $notif_value = 2;
    }

  # Set global notification info for this group.
  $res_gl = db_autoexecute (
    'groups',
    [
      "{$tracker}_glnotif" => $notif_value,
      "send_all_$tracker" => $send_all_changes,
      "new_{$tracker}_address" => $new_item_address,
      $private_exclude_address_name => $private_exclude_address
    ],
    DB_AUTOQUERY_UPDATE, "group_id = ?", [$group_id]
  );
  if (!$res_gl)
    # TRANSLATORS: the argument is table name (like groups);
    # the string shall be followed by database error message.
    $local_feedback .=
      sprintf (_("%s table Update failed:"), 'groups')
      . ' ' . db_error ();

  $ok = 0;
  if ($nb_categories > 0)
    {
      for ($i = 0; $i < $nb_categories; $i++)
        {
          $tr_cat = "{$tracker}_cat_$i";
          $current_fv_name = "{$tr_cat}_bug_fv_id";
          $current_email_name = "{$tr_cat}_email";
          $current_send_all_name = "{$tr_cat}_send_all_flag";
          $in = sane_import ('post',
            [
              'digits' => $current_fv_name,
              'pass' => $current_email_name,
              'true' => $current_send_all_name
            ]
          );

          $current_fv_id = $in[$current_fv_name];
          $current_email = $in[$current_email_name];
          $current_send_all_flag = $in[$current_send_all_name];

          $res_cat = db_autoexecute (
            "{$tracker}_field_value",
            [
              'email_ad' => $current_email,
              'send_all_flag' => $current_send_all_flag
            ],
            DB_AUTOQUERY_UPDATE, "bug_fv_id = ?", [$current_fv_id]
          );
          if ($res_cat)
            $ok++;
          else
            # TRANSLATORS: the argument is table name (like groups);
            # the string shall be followed by database error message.
            $local_feedback .=
              sprintf (_("%s table Update failed:"), $tracker)
              . ' ' . db_error ();
        }
    }
  if (($res_gl) && ($ok == $nb_categories) && ($local_feedback == ""))
    return 1;
  if ($local_feedback != "")
    fb ($local_feedback);
  return 0;
}

function trackers_data_get_item_notification_info (
  $item_id, $artifact, $updated
)
{
  $emailad = "";
  $sendemail = 0;
  # Get group information bur new entity notification settings.
  $result = db_execute ("
    SELECT
      g.{$artifact}_glnotif, g.send_all_{$artifact}, g.new_{$artifact}_address
    FROM {$artifact} a, groups g
    WHERE a.bug_id = ?  AND g.group_id = a.group_id",
    [$item_id]
  );

  $glnotif = db_result ($result, 0, "{$artifact}_glnotif");
  $glsendall = db_result ($result, 0, "send_all_$artifact");
  $glnewad = db_result ($result, 0, "new_{$artifact}_address");
  if ($glnotif != 1)
    {   # not 'global only'
      $cat_field_name = "category_id";

      $result = db_execute ("
        SELECT v.email_ad, v.send_all_flag
        FROM ${artifact}_field_value v, ${artifact}_field f, $artifact a
        WHERE
          a.bug_id = ?  AND f.field_name = ? AND v.group_id = a.group_id
        AND v.bug_field_id = f.bug_field_id AND v.value_id = a.category_id",
        [$item_id, $cat_field_name]
      );
      $rows = db_numrows ($result);
      if ($rows > 0)
        {
          $sendallflag = db_result ($result, 0, 'send_all_flag');
          if (($updated == 0) || (($updated == 1) && ($sendallflag == 1)))
            $emailad .= db_result ($result, 0, 'email_ad');
        }
      else
        {
          # Could be that administrator closes category notification and forgot
          # to define categories BUT in most cases it means the submitter
          # selected the 'NONE' category for this bug.
          if (($updated == 0) || (($updated == 1) && ($glsendall == 1)))
            $emailad .= $glnewad;
        }
    }
  if ($glnotif > 0)
    {   # not 'category only'
      if (($updated == 0) || (($updated == 1) && ($glsendall == 1)))
        {
          if ($emailad != "")
            $emailad .= ',';
          $emailad .= $glnewad;
        }
    }
  if (trim ($emailad) != "")
    $sendemail = 1;
  return [$emailad, $sendemail];
}

function cmp_place ($ar1, $ar2)
{
  $place1 = isset ($ar1['place']) ? $ar1['place'] : 0;
  $place2 = isset ($ar2['place']) ? $ar2['place'] : 0;
  if ($place1 < $place2)
    return -1;
  else if ($place1 > $place2)
    return 1;
  return 0;
}

function cmp_place_query ($ar1, $ar2)
{
  $place1 = isset ($ar1['place_query']) ? $ar1['place_query'] : 0;
  $place2 = isset ($ar2['place_query']) ? $ar2['place_query'] : 0;
  if ($place1 < $place2)
    return -1;
  else if ($place1 > $place2)
    return 1;
  return 0;
}

function cmp_place_result ($ar1, $ar2)
{
  $place1 = isset ($ar1['place_result']) ? $ar1['place_result'] : 0;
  $place2 = isset ($ar2['place_result']) ? $ar2['place_result'] : 0;
  if ($place1 < $place2)
    return -1;
  elseif ($place1 > $place2)
    return 1;
  return 0;
}

# Get all the bug fields involved in the bug report.
# Return false if no query for given $report_id was defined.
# WARNING: This function must only be called after bug_init ().
function trackers_data_get_all_report_fields ($report_id = 100)
{
  global $BF_USAGE_BY_ID, $BF_USAGE_BY_NAME;
  $have_bug_id = false;

  # Build the list of fields involved in this report.
  $res = db_execute ("
    SELECT * FROM " . ARTIFACT . "_report_field WHERE report_id = ?",
    [$report_id]
  );

  while ($arr = db_fetch_array ($res))
    {
      $field = $arr['field_name'];
      if ($field === 'bug_id')
        {
          $have_bug_id = true;
          # bug_id should always show up.
          if (!$arr['show_on_result'])
            $arr['show_on_result'] = 1;
        }
      $field_id = trackers_data_get_field_id ($field);
      $BF_USAGE_BY_NAME[$field]['show_on_query'] =
        $BF_USAGE_BY_ID[$field_id]['show_on_query'] = $arr['show_on_query'];

      $BF_USAGE_BY_NAME[$field]['show_on_result'] =
        $BF_USAGE_BY_ID[$field_id]['show_on_result'] = $arr['show_on_result'];

      $BF_USAGE_BY_NAME[$field]['place_query'] =
        $BF_USAGE_BY_ID[$field_id]['place_query'] = $arr['place_query'];

      $BF_USAGE_BY_NAME[$field]['place_result'] =
        $BF_USAGE_BY_ID[$field_id]['place_result'] = $arr['place_result'];

      $BF_USAGE_BY_NAME[$field]['col_width'] =
        $BF_USAGE_BY_ID[$field_id]['col_width'] = $arr['col_width'];
    }
  # Every query form should have 'bug_id'; if it hasn't, add it.
  if (!$have_bug_id)
    {
      if (db_numrows ($res) > 0)
        error_log ("No bug it found in query form #" . $report_id);
      $field = 'bug_id';
      $field_id = trackers_data_get_field_id ($field);
      $BF_USAGE_BY_NAME[$field]['show_on_query'] =
        $BF_USAGE_BY_ID[$field_id]['show_on_query'] = 0;

      $BF_USAGE_BY_NAME[$field]['show_on_result'] =
        $BF_USAGE_BY_ID[$field_id]['show_on_result'] = 1;

      $BF_USAGE_BY_NAME[$field]['place_query'] =
        $BF_USAGE_BY_ID[$field_id]['place_query'] = null;

      $BF_USAGE_BY_NAME[$field]['place_result'] =
        $BF_USAGE_BY_ID[$field_id]['place_result'] = null;

      $BF_USAGE_BY_NAME[$field]['col_width'] =
        $BF_USAGE_BY_ID[$field_id]['col_width'] = null;
    }
  return db_numrows ($res) > 0;
}

# Return all possible values for a select box field.
# Rk: if the checked value is given then it means that we want this value
# in the list in any case (even if it is hidden and active_only is requested).
function trackers_data_get_field_predefined_values (
  $field, $group_id  = false, $checked  = false, $by_field_id  = false,
  $active_only  = true
)
{
  $field_id = ($by_field_id ? $field : trackers_data_get_field_id ($field));
  $field_name = ($by_field_id ? trackers_data_get_field_name ($field) : $field);

  # The "Assigned_to" box requires some special processing,
  # because possible values  are project members) and they are
  # not stored in the trackers_field_value table but in the user_group table.
  if ($field_name == 'assigned_to')
    return trackers_data_get_technicians ($group_id);

  if ($field_name == 'submitted_by')
    return trackers_data_get_submitters ($group_id);

  $status_cond = '';
  $status_cond_params = [];

  if ($active_only)
    {
      if ($checked and !is_array ($checked))
        {
          $status_cond = "AND  (status IN ('A','P') OR value_id=?) ";
          $status_cond_params = [$checked];
        }
      else
        {
          $status_cond = "AND  status IN ('A','P') ";
          $status_cond_params = [];
        }
    }

  # The fields value_id and value must be first in the select statement,
  # because the output is used in the html_build_select_box function.

  # Look for project specific values first.
  $sql = "
    SELECT
      value_id, value, bug_fv_id, bug_field_id, group_id, description,
      order_id, status
    FROM " . ARTIFACT . "_field_value
    WHERE group_id = ? AND bug_field_id = ? $status_cond
    ORDER BY order_id,value ASC";
  $res_value = db_execute (
    $sql, array_merge ([$group_id, $field_id], $status_cond_params)
  );
  # If no specific value for this group, then look for default values.
  if (db_numrows ($res_value) != 0)
    return $res_value;
  return db_execute (
    $sql, array_merge ([100, $field_id], $status_cond_params)
  );
}

function trackers_data_use_field_predefined_values ($field, $group_id)
{
  # Check whether a group field values are the default one or not.
  # If no entry in the database for the relevant field value belong to the
  # group, then it uses default values (fallback).
  $field_id = trackers_data_get_field_id ($field);

  $result = db_execute ("
    SELECT bug_fv_id FROM " . ARTIFACT . "_field_value
    WHERE group_id = ? AND bug_field_id = ?",
    [$group_id, $field_id]
  );
  return db_numrows ($result);
}

function trackers_data_by_field_var ($by_field_id)
{
  $var_name = "BF_USAGE_BY_";
  if ($by_field_id)
    return $var_name . 'ID';
  return $var_name . 'NAME';
}

function trackers_data_is_custom ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return $GLOBALS[$var_name][$field]['custom'];
}

function trackers_data_is_special ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return !empty ($GLOBALS[$var_name][$field]['special']);
}

# 1 = not mandatory
# 0 = relaxed mandatory (mandatory if it was to the submitter)
# 3 = mandatory whenever possible
function trackers_data_mandatory_flag ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['custom_empty_ok']))
    return $GLOBALS[$var_name][$field]['custom_empty_ok'];
  if (isset ($GLOBALS[$var_name][$field]['empty_ok']))
    return $GLOBALS[$var_name][$field]['empty_ok'];
  return null;
}

function trackers_data_do_keep_history ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['custom_keep_history']))
    return $GLOBALS[$var_name][$field]['custom_keep_history'];
  if (isset ($GLOBALS[$var_name][$field]['keep_history']))
    return $GLOBALS[$var_name][$field]['keep_history'];
  return null;
}

function trackers_data_is_required ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['required']))
    return $GLOBALS[$var_name][$field]['required'];
  return null;
}

function trackers_data_is_used ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return $GLOBALS[$var_name][$field]['use_it'];
}

function trackers_data_is_showed_on_query ($field)
{
  global $BF_USAGE_BY_NAME;
  # show_on_query can be unset if not in the DB.
  return !empty ($BF_USAGE_BY_NAME[$field]['show_on_query']);
}

function trackers_data_is_showed_on_result ($field)
{
  global $BF_USAGE_BY_NAME;
  return !empty ($BF_USAGE_BY_NAME[$field]['show_on_result']);
}

# Return a TRUE value if non project members who still are
# logged in users should be able to access this field
# (first bit of show_on_add set).
function trackers_data_is_showed_on_add ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['show_on_add']))
    return $GLOBALS[$var_name][$field]['show_on_add'] & 1;
  return 0;
}

# Return a TRUE value if non-logged in users should be able to
# access this field (second bit of show_on_add set).
function trackers_data_is_showed_on_add_nologin ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['show_on_add']))
    return $GLOBALS[$var_name][$field]['show_on_add'] & 2;
  return 0;
}

# Return a TRUE value if project members should be able to
# access this field.
function trackers_data_is_showed_on_add_members ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['show_on_add_members']))
    return $GLOBALS[$var_name][$field]['show_on_add_members'];
  return null;
}

function trackers_data_is_date_field ($field, $by_field_id = false)
{
  return trackers_data_get_display_type ($field, $by_field_id) == 'DF';
}

function trackers_data_is_text_field ($field, $by_field_id = false)
{
  return trackers_data_get_display_type ($field, $by_field_id) == 'TF';
}

function trackers_data_is_text_area ($field, $by_field_id = false)
{
  return trackers_data_get_display_type ($field, $by_field_id) == 'TA';
}

function trackers_data_is_select_box ($field, $by_field_id = false)
{
  return trackers_data_get_display_type ($field, $by_field_id) == 'SB';
}

function trackers_data_is_username_field ($field, $by_field_id = false)
{
  if ($by_field_id)
    $field = trackers_data_get_field_name ($field);

  return ($field == 'assigned_to') || ($field == 'submitted_by');
}

function trackers_data_is_project_scope ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return $GLOBALS[$var_name][$field]['scope'] == 'P';
}

function trackers_data_is_status_closed ($status)
{
  return $status == '3';
}

function trackers_data_get_field_name ($field_id)
{
  global $BF_USAGE_BY_ID;
  return $BF_USAGE_BY_ID[$field_id]['field_name'];
}

function trackers_data_get_field_id ($field_name)
{
  global $BF_USAGE_BY_NAME;
  if (isset ($BF_USAGE_BY_NAME[$field_name]['bug_field_id']))
    return $BF_USAGE_BY_NAME[$field_name]['bug_field_id'];
  return null;
}

function trackers_data_get_group_id ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return $GLOBALS[$var_name][$field]['group_id'];
}

function trackers_data_get_label ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['custom_label']))
    return $GLOBALS[$var_name][$field]['custom_label'];
  if (isset ($GLOBALS[$var_name][$field]['label']))
    return $GLOBALS[$var_name][$field]['label'];
  return null;
}

function trackers_data_get_description ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  $desc = $GLOBALS[$var_name][$field]['custom_description'];
  if (!isset ($desc))
    $desc = $GLOBALS[$var_name][$field]['description'];
  return $desc;
}

function trackers_data_get_display_type ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($GLOBALS[$var_name][$field]['display_type']))
    return $GLOBALS[$var_name][$field]['display_type'];
  return null;
}

function trackers_data_get_display_type_in_clear ($field, $by_field_id = false)
{
  if (trackers_data_is_select_box ($field, $by_field_id))
    return 'Select Box';
  if (trackers_data_is_text_field ($field, $by_field_id))
    return 'Text Field';
  if (trackers_data_is_text_area ($field, $by_field_id))
    return 'Text Area';
  if (trackers_data_is_date_field ($field, $by_field_id))
    return 'Date Field';
  return '?';
}

function trackers_data_get_keep_history ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  if (isset ($BF_USAGE_BY_ID[$field]['custom_keep_history']))
    return $GLOBALS[$var_name][$field]['custom_keep_history'];
  return $GLOBALS[$var_name][$field]['keep_history'];
}

function trackers_data_get_place ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return $GLOBALS[$var_name][$field]['place'];
}

function trackers_data_get_scope ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return  $GLOBALS[$var_name][$field]['scope'];
}

function trackers_data_get_col_width ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  return $GLOBALS[$var_name][$field]['col_width'];
}

function trackers_data_get_display_size ($field, $by_field_id = false)
{
  $var_name = trackers_data_by_field_var ($by_field_id);
  $val = null;
  if (isset ($GLOBALS[$var_name][$field]['custom_display_size']))
    $val = $GLOBALS[$var_name][$field]['custom_display_size'];
  elseif (isset ($GLOBALS[$var_name][$field]['display_size']))
    $val = $GLOBALS[$var_name][$field]['display_size'];
  return explode ('/', $val);
}

# Return the default value associated to a field_name as defined
# in the bug table (SQL definition).
function trackers_data_get_default_value ($field, $by_field_id = false)
{
  if ($by_field_id)
    $field = trackers_data_get_field_name ($field);

  $result = db_query ('DESCRIBE ' . ARTIFACT . ' `' . $field . '`');
  return (db_result ($result, 0, 'Default'));
}

# Find the maximum value for the value_id of a field for a given group.
# Return -1, if no value exist yet.
function trackers_data_get_max_value_id (
  $field, $group_id, $by_field_id = false
)
{
  if (!$by_field_id)
    $field_id = trackers_data_get_field_id ($field);

  $res = db_execute ("
    SELECT max(value_id) as max FROM " . ARTIFACT . "_field_value
    WHERE bug_field_id = ? AND group_id = ?",
    [$field_id, $group_id]
  );

  if (!db_numrows ($res))
    return -1;
  return db_result ($res, 0, 'max');
}

# Return true if there is an existing set of values for given field
# for a given group and false if it is empty.
function trackers_data_is_value_set_empty (
  $field, $group_id, $by_field_id = false
)
{
  if (!$by_field_id)
    $field_id = trackers_data_get_field_id ($field);

  $res = db_execute ("
    SELECT value_id FROM " . ARTIFACT . "_field_value
    WHERE bug_field_id = ? AND group_id = ?",
    [$field_id, $group_id]
  );
  return db_numrows ($res) <= 0;
}

# Initialize the set of values for a given field for a given group by using
# the system default (default values belong to group_id 'None'  = 100).
function trackers_data_copy_default_values (
  $field, $group_id, $by_field_id = false
)
{
  if (!$by_field_id)
    $field_id = trackers_data_get_field_id ($field);

  # If group_id is 100 (None), it is a null operation,
  # because default values belong to group_id 100 by definition.
  if ($group_id == 100)
    return;
  # First delete the exisiting value if any.
  $res = db_execute ("
    DELETE FROM " . ARTIFACT . "_field_value
    WHERE bug_field_id = ? AND group_id = ?",
    [$field_id, $group_id]
  );

  # Second insert default values (if any) from group 'None'.
  # Rk: The target table of the INSERT statement cannot appear in
  # the FROM clause of the SELECT part of the query, because it's forbidden
  # in ANSI SQL to SELECT. So do it by hand !
  $res = db_execute ("
    SELECT value_id, value, description, order_id, status
    FROM " . ARTIFACT . "_field_value
    WHERE bug_field_id = ? AND group_id = 100",
    [$field_id]
  );
  $rows = db_numrows ($res);

  for ($i = 0; $i < $rows; $i++)
    {
      $value_id = db_result ($res, $i, 'value_id');
      $value = db_result ($res, $i, 'value');
      $description = db_result ($res, $i, 'description');
      $order_id = db_result ($res, $i, 'order_id');
      $status  = db_result ($res, $i, 'status');

      $res_insert = db_autoexecute (
        ARTIFACT . "_field_value",
        [
          'bug_field_id' => $field_id, 'group_id' => $group_id,
          'value_id' => $value_id, 'value' => $value,
          'description' => $description, 'order_id' => $order_id,
          'status' => $status
        ],
        DB_AUTOQUERY_INSERT
      );

      if (db_affected_rows ($res_insert) < 1)
        {
          fb (_("Insert of default value failed."), 0);
          db_error ();
        }
    }
}

function trackers_data_get_cached_field_value ($field, $group_id, $value_id)
{
  global $BF_VALUE_BY_NAME;

  if (isset ($BF_VALUE_BY_NAME[$field][$value_id]))
    return $BF_VALUE_BY_NAME[$field][$value_id];
  $res = trackers_data_get_field_predefined_values ($field, $group_id);
  while ($fv_array = db_fetch_array ($res))
    {
      $BF_VALUE_BY_NAME[$field][$fv_array['value_id']] = $fv_array['value'];
    }
  if (!isset ($BF_VALUE_BY_NAME[$field][$value_id]))
    $BF_VALUE_BY_NAME[$field][$value_id] = null;
  return $BF_VALUE_BY_NAME[$field][$value_id];
}

# Get all the columns associated to a given field value.
function trackers_data_get_field_value ($item_fv_id)
{
  return db_execute ("
    SELECT * FROM " . ARTIFACT . "_field_value WHERE bug_fv_id = ?",
    [$item_fv_id]
  );
}

# See if this field value belongs to group None (100). In this case
# it is a so called default value.
function trackers_data_is_default_value ($item_fv_id)
{
  $res = db_execute ("
    SELECT bug_field_id,value_id FROM " . ARTIFACT . "_field_value
    WHERE bug_fv_id = ? AND group_id = 100",
    [$item_fv_id]
  );
  return ((db_numrows ($res) >= 1) ? $res : false);
}

# Insert a new value for a given field for a given group.
function trackers_data_create_value (
  $field, $group_id, $value, $description, $order_id, $status = 'A',
  $by_field_id = false
)
{
  if (preg_match ("/^\s*$/", $value))
    {
      fb (_("Empty field value not allowed"), 0);
      return;
    }

  if (!$by_field_id)
    $field_id = trackers_data_get_field_id ($field);

  # If group_id = 100 (None), then do nothing,
  # because no real project should have the group number '100'.
  if ($group_id == 100)
    return;

  # If the current value set for this project is empty
  # then copy the default values first (if any).
  if (trackers_data_is_value_set_empty ($field, $group_id))
    trackers_data_copy_default_values ($field, $group_id);

  # Find the next value_id to give to this new value. (Start arbitrarily
  # at 200 if no value exists (and therefore max is undefined).
  $max_value_id = trackers_data_get_max_value_id ($field, $group_id);

  if ($max_value_id < 0)
    $value_id = 200;
  else
    $value_id = $max_value_id + 1;

  $result = db_autoexecute (
    ARTIFACT . "_field_value",
    [
      'bug_field_id' => $field_id, 'group_id' => $group_id,
      'value_id' => $value_id, 'value' => $value,
      'description' => $description, 'order_id' => $order_id,
      'status' => $status
    ],
    DB_AUTOQUERY_INSERT
  );

  if (db_affected_rows ($result) < 1)
    fb (_("Insert failed."), 1);
  else
    fb (_("New field value inserted."));
}

# Insert a new value for a given field for a given group.
function trackers_data_update_value (
  $item_fv_id, $field, $group_id, $value, $description, $order_id,
  $status = 'A'
)
{
  if (preg_match ("/^\s*$/", $value))
    {
      fb (_("Empty field value not allowed"), 0);
      return;
    }

  # Updating a bug field value that belong to group 100 (None) is
  # forbidden. These are default values that cannot be changed, so
  # make sure to copy the default values first in the project context first.

  if ($res = trackers_data_is_default_value ($item_fv_id))
    {
      trackers_data_copy_default_values ($field, $group_id);

      $arr = db_fetch_array ($res);
      $where_cond = "bug_field_id = ? AND value_id = ? AND group_id = ?";
      $where_cond_params = [
        $arr['bug_field_id'], $arr['value_id'], $group_id
      ];
    }
  else
    {
      $where_cond = "bug_fv_id = ? AND group_id <> 100";
      $where_cond_params = [$item_fv_id];
    }

  $result = db_autoexecute (
    ARTIFACT . "_field_value",
    [
     'value' => $value,
     'description' => $description,
     'order_id' => $order_id,
     'status' => $status
    ],
    DB_AUTOQUERY_UPDATE, "$where_cond", $where_cond_params
  );

  if (db_affected_rows ($result) < 1)
    fb (_("Update of field value failed."), 1);
  else
    fb (_("New field value updated."));
}

# Reset a field settings to its defaults usage (values are untouched). The defaults
# always belong to group_id 100 (None) so make sure we don't delete entries for
# group 100.
function trackers_data_reset_usage ($field_name, $group_id)
{
  if ($group_id == 100)
    return;

  $field_id = trackers_data_get_field_id ($field_name);
  db_execute ("
    DELETE FROM " . ARTIFACT . "_field_usage
    WHERE group_id = ? AND bug_field_id = ?",
    [$group_id, $field_id]
  );
  fb (_("Field value successfully reset to defaults."));
}

# Update a field settings in the trackers_usage_table.
# Rk: All the show_on_xxx boolean parameters are set to 0 by default because their
# values come from checkboxes and if not checked the form variable
# is not set at all. It must be 0 to be ok with the SQL statement.
function trackers_data_update_usage (
  $field_name, $group_id, $label, $description, $use_it, $rank, $display_size,
  $empty_ok, $keep_history, $show_on_add_members = 0, $show_on_add = 0,
  $transition_default_auth = 'A'
)
{
  # If it's a required field then make sure the use_it flag is true.
  if (trackers_data_is_required ($field_name))
    $use_it = 1;

  $field_id = trackers_data_get_field_id ($field_name);

  $lbl = isset ($label) ? $label : null;
  $desc = isset ($description) ? $description : null;
  $disp_size = isset ($display_size) ? $display_size : null;
  $emp_ok = isset ($empty_ok) ? $empty_ok : null;
  $keep_hist = isset ($keep_history) ? $keep_history : null;

  if (!isset ($show_on_add))
    $show_on_add = 0;
  if (!isset ($show_on_add_members))
    $show_on_add_members = 0;
  if (!isset ($transition_default_auth))
    $transition_default_auth = '';

  # See if this field usage exists in the table for this project.
  $result = db_execute ("
    SELECT bug_field_id FROM " . ARTIFACT . "_field_usage
    WHERE bug_field_id = ? AND group_id = ?",
    [$field_id, $group_id]
  );
  $rows = db_numrows ($result);

  # If it does exist, then update it, else insert a new usage entry
  # for this field.
  if ($rows)
    $result = db_autoexecute (
      ARTIFACT . '_field_usage',
      [
        'use_it' => $use_it, 'show_on_add' => $show_on_add,
        'show_on_add_members' => $show_on_add_members,
        'place' => $rank, 'custom_label' => $lbl,
        'custom_description' => $desc, 'custom_display_size' => $disp_size,
        'custom_empty_ok' => $emp_ok, 'custom_keep_history' => $keep_hist,
        'transition_default_auth' => $transition_default_auth
      ],
      DB_AUTOQUERY_UPDATE, "bug_field_id=? AND group_id=?",
      [$field_id, $group_id]
    );
  else
    $result = db_autoexecute (
      ARTIFACT . '_field_usage',
      [
        'bug_field_id' => $field_id, 'group_id' => $group_id,
        'use_it' => $use_it, 'show_on_add' => $show_on_add,
        'show_on_add_members' => $show_on_add_members,
        'place' => $rank, 'custom_label' => $lbl,
        'custom_description' => $desc, 'custom_display_size' => $disp_size,
        'custom_empty_ok' => $emp_ok, 'custom_keep_history' => $keep_hist,
        'transition_default_auth' => $transition_default_auth
      ], DB_AUTOQUERY_INSERT
    );

  if (db_affected_rows ($result) < 1)
    fb (_("Update of field usage failed."), 1);
  else
    fb (_("Field usage updated."));
}

# Get a list of technicians for a tracker.
function trackers_data_get_technicians ($group_id)
{
  # FIXME: The cleanest thing would be to issue one SQL command.
  # But we have to handle the fact that "no setting" = get back
  # to the group, or even group type, setting.

  # In fact, this is terrible, we cannot return something else than
  # a mysql result if we do not want to rewrite 25 functions.
  # So we get the appropriate list of users... and finally issue
  # a mysql command only to be able to return a mysql result.
  # Please, propose something better at savannah-dev@gnu.org.

  # Get list of members.
  $members_res = db_execute ("
    SELECT user.user_id FROM user,user_group
    WHERE user.user_id=user_group.user_id AND user_group.group_id = ?",
    [$group_id]
  );
  $sql = "SELECT user_id,user_name FROM user WHERE ";
  $params = array();
  $notfirst = false;
  while ($member = db_fetch_array ($members_res))
    {
      $mem_ck = member_check (
        $member['user_id'], $group_id,
        member_create_tracker_flag (ARTIFACT) . '1'
      );
      if ($mem_ck)
        {
          if ($notfirst)
            $sql .= " OR ";
          $sql .= " user_id = ?";
          $params[] = $member['user_id'];
          $notfirst = true;
        }
    }
  if (empty ($params))
    # Return a valid (but empty) resultset.
    $sql .= 'NULL';
  $sql .= " ORDER BY user_name";
  return db_execute ($sql, $params);
}

# Get transitions valid for a given tracker as an array.
# DEPRECATED, moved to transition.php.
function trackers_data_get_transition ($group_id)
{
  return trackers_transition_get_update ($group_id);
}

function trackers_data_get_submitters ($group_id = false)
{
  $art = ARTIFACT;
  return db_execute ("
    SELECT DISTINCT user.user_id, user.user_name
    FROM user, $art a
    WHERE user.user_id = a.submitted_by AND a.group_id = ?
    ORDER BY user.user_name", [$group_id]
  );
}

# Get the items for this project.
function trackers_data_get_items ($group_id = false, $artifact)
{
  return db_execute ("
    SELECT bug_id, summary FROM $artifact
    WHERE group_id = ?  AND status_id <> 3
    ORDER BY bug_id DESC LIMIT 100",
    [$group_id]
  );
}

# Get the list of ids this is dependent on.
function trackers_data_get_dependent_items (
  $item_id = false, $artifact, $notin = false
)
{
  $sql = "
    SELECT is_dependent_on_item_id FROM " . ARTIFACT . "_dependencies
    WHERE item_id = ? AND is_dependent_on_item_id_artifact = ?";
  $sql_params = [$item_id, $artifact];
  if ($notin)
    {
      $sql .= ' AND is_dependent_on_item_id NOT IN ('
        . utils_str_join (',', '?', count ($dict))
        . ')';
      $sql_params = array_merge ($sql_params, $notin);
    }
  return db_execute ($sql, $sql_params);
}

function trackers_data_get_valid_bugs ($group_id = false, $item_id = '')
{
  return db_execute ("
    SELECT bug_id, summary
    FROM " . ARTIFACT . " a
    WHERE group_id = ? AND bug_id <> ? AND a.resolution_id <> 2
    ORDER BY bug_id DESC LIMIT 200",
    [$group_id, $item_id]
  );
}

function trackers_data_get_followups ($item_id = false, $rorder = false)
{
  if ($rorder == true)
    $rorder = "DESC";
  else
    $rorder = "ASC";

  $tracker = ARTIFACT;

  return
    db_execute ("
      SELECT DISTINCT
        bug_history_id, field_name, old_value, spamscore, new_value, date,
        user_name, realname, user_id, value AS comment_type
      FROM
        (
          SELECT
            b.bug_history_id, b.field_name, b.old_value, b.spamscore,
            b.new_value, b.date, b.mod_by, b.type, t.group_id AS grp,
            u.user_name, u.realname, u.user_id
          FROM ${tracker}_history b, ${tracker} t, user u
          WHERE
            t.bug_id = ? AND b.bug_id = t.bug_id
            AND b.field_name = 'details' AND b.mod_by = u.user_id
        ) bhi
        LEFT JOIN
        (
          SELECT DISTINCT v.value, v.value_id, v.group_id
          FROM ${tracker}_field_value v, ${tracker}_field f
          WHERE
            v.bug_field_id = f.bug_field_id
            AND f.field_name = 'comment_type_id'
        ) fv
        ON bhi.type = fv.value_id AND bhi.grp = fv.group_id
      ORDER BY date $rorder",
      [$item_id]
    );
}

function trackers_data_get_commenters ($item_id)
{
  return db_execute ("
    SELECT DISTINCT mod_by FROM " . ARTIFACT . "_history h
    WHERE h.bug_id = ? AND h.field_name = 'details'",
    [$item_id]
  );
}

function trackers_data_get_history ($item_id = false)
{
  return db_execute ("
    SELECT
      h.field_name, h.old_value, h.date, h.type, user.user_name, h.new_value
     FROM " . ARTIFACT . "_history h, user
     WHERE
       h.mod_by = user.user_id AND h.field_name <> 'details' AND bug_id = ?
     ORDER BY h.date DESC",
     [$item_id]
  );
}

function trackers_data_get_attached_files ($item_id = false, $order = 'DESC')
{
  if ($order != 'DESC' and $order != 'ASC')
    die (
      "trackers_data_get_attached_files: invalid \$order '"
      . htmlescape ($order) . "')"
    );

  return db_execute ("
    SELECT
      file_id, filename, filesize, filetype, description, date, user.user_name
    FROM trackers_file, user
    WHERE submitted_by = user.user_id AND artifact = ?  AND item_id = ?
    ORDER BY date $order",
    [ARTIFACT, $item_id]
  );
}

function trackers_data_get_cc_list ($item_id = false)
{
  return db_execute ("
    SELECT bug_cc_id, cc.email, cc.added_by, cc.comment, cc.date, u.user_name
    FROM " . ARTIFACT . "_cc cc, user u
    WHERE added_by = u.user_id AND bug_id = ?
    ORDER BY date DESC",
    [$item_id]
  );
}

# Magic value to mark base64-encoded comments.
$trackers_encode_value_prefix = 'jbexnebhaq ZlFDY oht';

function trackers_encode_value ($value)
{
  global $trackers_encode_value_prefix;
  return $trackers_encode_value_prefix . base64_encode ($value);
}

function trackers_decode_value ($value)
{
  global $trackers_encode_value_prefix;
  $len = strlen ($trackers_encode_value_prefix);
  if (strlen ($value) <= $len)
    return $value;
  if (strcmp (substr ($value, 0, $len), $trackers_encode_value_prefix))
    return $value;
  return base64_decode (substr ($value, $len));
}

function trackers_data_add_history (
  $field_name, $old_value, $new_value, $item_id, $type = false, $artifact = 0,
  $force = 0
)
{
  # If no artifact is defined, get the default one.
  if (!$artifact)
    $artifact = ARTIFACT;

  # If field is not to be kept in bug change history then do nothing.
  if (!$force && !trackers_data_get_keep_history ($field_name))
    return;

  if (!user_isloggedin ())
    $user = 100;
  else
    $user = user_getid ();

  # If spamscore is relevant (if it is a comment), set it, otherwise go with 0.
  $spamscore = 0;
  if ($field_name == 'details')
    $spamscore = spam_get_user_score ($user);

  # If type has a value, add it into the sql statement (this is only
  # for the follow up comments (details field)).
  $val_type = 'NULL';
  if ($type)
    {
      $val_type = $type;
    }
  else
    {
        # No comment type specified for a followup comment,
        # so force it to None (100).
      if ($field_name == 'details')
        $val_type = 100;
    }

  $result = db_autoexecute (
    "${artifact}_history",
    [
      'bug_id' => $item_id, 'field_name' => $field_name,
      'old_value' => $old_value, 'new_value' => $new_value,
      'mod_by' => $user, 'date' => time (),
      'spamscore' => $spamscore, 'ip' => '127.0.0.1', 'type' => $val_type
    ],
    DB_AUTOQUERY_INSERT
  );
  $insert_id = db_insertid ($result);
  if ($field_name == 'details')
    {
      # Check if we read what we've written,
      # see Savannah sr #109423.

      $read_result = db_execute ("
        SELECT old_value, new_value
        FROM ${artifact}_history WHERE bug_history_id = ?",
        [$insert_id]
      );

      $prev_old_value = $old_value;
      $prev_new_value = $new_value;

      if (db_numrows ($read_result))
        {
          global $trackers_encode_value_prefix;
          $len = strlen ($trackers_encode_value_prefix);
          # Encode comments if needed.
          $entry = db_fetch_array ($read_result);
          if ($entry['old_value'] != $old_value
              || !strcmp (substr ($old_value, 0, $len),
                          $trackers_encode_value_prefix))
            $old_value = trackers_encode_value ($old_value);
          if ($entry['new_value'] != $new_value
              || !strcmp (substr ($new_value, 0, $len),
                          $trackers_encode_value_prefix))
            $new_value = trackers_encode_value ($new_value);
          if ($prev_old_value != $old_value
              || $prev_new_value != $new_value)
          $res = db_autoexecute (
            "${artifact}_history",
            ['new_value' => $new_value, 'old_value' => $old_value],
            DB_AUTOQUERY_UPDATE, "bug_history_id = ?", [$insert_id]
          );
        } # db_numrows ($read_result)
    } # $field_name == 'details'

  spam_set_item_default_score (
    $item_id, db_insertid ($result), $artifact, $spamscore, $user
  );

  # Add to spamcheck queue if necessary (will temporary set the spamscore to
  # 5, if necessary).
  # Useless if already considered to be spam.
  if ($spamscore < 5)
    {
      $result = db_execute ("
        SELECT group_id FROM $artifact WHERE bug_id = ?",
        [$item_id]
      );
      if (db_numrows ($result))
        $group_id = db_result ($result, 0, 'group_id');
      else
        exit_error (_("Item not found"));
      spam_add_to_spamcheck_queue (
        $item_id, db_insertid ($result), $artifact, $group_id, $spamscore
      );
    }
  return $result;
}

function trackers_data_append_canned_response ($details, $canned_response)
{
  if (
    $canned_response == 100 || $canned_response == '!multiple!'
    || empty ($canned_response)
  )
    return $details;
  $separator = "\n\n";
  if (!empty ($details))
    $details .= $separator;

  if (!is_array ($canned_response))
    $canned_response = [$canned_response];

  $any_response_used = false;
  foreach ($canned_response as $response)
    {
      $res = db_execute ("
        SELECT * FROM " . ARTIFACT . "_canned_responses
        WHERE bug_canned_id = ?",
        [$response]
      );

      if (!$res || db_numrows ($res) <= 0)
        {
          fb (_("Unable to use canned response"), 1);
          continue;
        }
      if (!empty ($details))
        $details .= $separator;
      $details .= htmlspecialchars_decode (
        db_result ($res, 0, 'body'), ENT_QUOTES
      );
      $any_response_used = true;
    }
  if ($any_response_used)
    fb (_("Canned response used"));
  return $details;
}

# Handle update of most usual fields.
function trackers_data_handle_update (
  $group_id, $item_id, $dependent_on_task, $dependent_on_bugs,
  $dependent_on_support, $dependent_on_patch, $canned_response, $vfl,
  &$changes, &$extra_addresses
)
{
  # Variable to track changes made inside the function.
  $change_exists = false;

  # Update an item. Rk: vfl is an variable list of fields, Vary from one
  # project to another.
  # Return true if bug updated, false if nothing changed or
  # DB update failed.

  # Make sure absolutely required fields are not empty
  # yeupou, 2005-11: why is canned_response absolutely required?
  if (!$group_id || !$item_id || !$canned_response)
    {
      dbg ("params were group_id:$group_id item_id:$item_id "
          . "canned_response:$canned_response");
      exit_missing_param ();
    }

  # Make sure mandatory fields are not empty, otherwise we want the form
  # to be re-submitted.
  if ((trackers_check_empty_fields ($vfl, false) == false))
    {
      # In such circonstances, we reprint the form
      # highligthing missing fields.
      # (It is important that trackers_check_empty_fields set the global var
      # previous_form_bad_fields).
      return false;
    }

  # Get this bug from the DB.
  $result = db_execute (
    "SELECT * FROM " . ARTIFACT . " WHERE bug_id = ?", [$item_id]
  );

  # Extract field transition possibilities:
  $field_transition = trackers_data_get_transition ($group_id);
  # We will store in an array the transition_id accepted, to check
  # other fields updates.
  $field_transition_accepted = array();

  # See which fields changed during the modification
  # and if we must keep history then do it. Also add them to the update
  # statement ($changes was initialized in index, as it is used by other
  # functions).
  reset ($vfl);
  $upd_list = [];
  foreach ($vfl as $field => $value)
    {
      # $field_transition_id needed to be reset for every field in the loop
      # and $field_transition_accepted filled only if $field_transition_id
      # is not empty (otherwise transition automatic updates risk to be
      # done by error if a transition is defined for any field!)
      $field_transition_id = '';

      # Skip over special fields  except for summary which in this
      # particular case can be processed normally.
      if (trackers_data_is_special ($field) && ($field != 'summary'))
        continue;

      # Skip over comment, which is also a special field but not known as
      # special by the database.
      if ($field == 'comment')
        continue;

      $old_value = db_result ($result, 0, $field);

      # Handle field transitions checks+cc notif,
      # register id of transition to execute.
      $field_id = trackers_data_get_field_id ($field);
      $field_transition_cc = '';
      if (array_key_exists ($field_id, $field_transition)
          # First check basic transition;
          # check multiple transition, override other transition.
          && (array_key_exists ($old_value, $field_transition[$field_id])
              || array_key_exists ("any", $field_transition[$field_id])))
        {
          $ft = $field_transition[$field_id];
          if (array_key_exists ("any", $ft)
              && array_key_exists ($value, $ft["any"]))
            {
              $field_transition_cc = $ft["any"][$value]['notification_list'];

              # Register the transition, but only if the field it is about
              # was not filled in the form
              if (!isset ($changes[$field])
                  || !is_array ($changes[$field])
                  || (!array_key_exists ('del', $changes[$field])
                      && !array_key_exists ('add', $changes[$field])))
                $field_transition_id = $ft["any"][$value]['transition_id'];
            }
          elseif (array_key_exists ($old_value, $field_transition[$field_id])
                   && array_key_exists ($value,
                                       $field_transition[$field_id][$old_value]))
            {
              $field_transition_cc =
                $field_transition[$field_id][$old_value][$value]['notification_list'];

              # Register the transition, but only if the field it is about
              # was not filled in the form.
              if (!is_array ($changes[$field]) ||
                  (!array_key_exists ('del', $changes[$field])
                   && !array_key_exists ('add', $changes[$field])))
                $field_transition_id =
                  $field_transition[$field_id][$old_value][$value]['transition_id'];
            }
        }

      $is_text = (trackers_data_is_text_field ($field)
                  || trackers_data_is_text_area ($field));
      if  ($is_text)
        {
          $differ = ($old_value != htmlspecialchars ($value));
        }
      elseif (trackers_data_is_date_field ($field))
        {
          $date_value = $value;
          list ($value,$ok) = utils_date_to_unixtime ($value);

          # Users can be on different timezone ; The form
          # saves only the day, month, year.
          # We cannot compare the timestamp (affected by timezone changes).
          $date_old_value = date ("Y-n-j", $old_value);

          $differ = ($date_old_value != $date_value);
        }
      else
        $differ = $old_value != $value;

      if ($differ)
        {
          if (trim ("$extra_addresses$field_transition_cc") != "")
            $extra_addresses .= ", ";
          $extra_addresses .= $field_transition_cc;

          if ($is_text)
            $upd_list[$field] = htmlspecialchars ($value);
          else
            $upd_list[$field] = $value;
          trackers_data_add_history ($field, $old_value, $value, $item_id);

          # Keep track of the change.
          $changes[$field]['del'] =
            trackers_field_display (
              $field, $group_id, $old_value, false, false, true, true
            );
          $changes[$field]['add'] =
            trackers_field_display (
              $field, $group_id, $value, false, false, true, true
            );

          # Keep track of the change real numeric values.
          $changes[$field]['del-val'] = $old_value;
          $changes[$field]['add-val'] = $value;

          # Register transition id, if not empty.
          if ($field_transition_id != '')
            {
              $field_transition_accepted[] = $field_transition_id;
            }
        }
    } # foreach ($vfl as $field => $value)

  # Now we run transitions other fields update. This function does check
  # what already changed and that we shan't automatically update.
  trackers_transition_update_item (
    $item_id, $field_transition_accepted, $changes
  );

  # Comments field history is handled a little differently. Followup comments
  # are added in the bug history along with the comment type.
  # Comments are called 'details' here for historical reason.
  $details = trackers_data_append_canned_response (
    $vfl['comment'], $canned_response
  );

  # Comment field history is handled a little differently. Followup comments
  # are added in the bug history along with the comment type.
  if ($details != '')
    {
      $change_exists = 1;
      fb (_("Comment added"), 0);
      $dtext = htmlspecialchars ($details);
      trackers_data_add_history (
        'details', $dtext, '', $item_id, $vfl['comment_type_id']
      );
      $changes['details']['add'] = $dtext;
      $changes['details']['type'] =
        trackers_data_get_value (
          'comment_type_id', $group_id, $vfl['comment_type_id']
        );

      # Add poster in CC.
      if (user_isloggedin () && !user_get_preference ("skipcc_postcomment"))
        trackers_add_cc (
          $item_id, $group_id, user_getid (), "-COM-", $changes
        );
    }

  # If we are on the cookbook, the original submission have been details.
  if (ARTIFACT == 'cookbook')
    {
      $details = htmlspecialchars ($vfl['details']);
      $previous_details = db_result ($result,0,'details');

      if ($details != $previous_details)
        {
          $change_exists = 1;
          $upd_list['details'] = $details;
          # We should use "details" but since details are used for comment
          # (which is really nasty), we simply can't.

          # How should be print the change?
          # The way we do it here is to show the previous recipe cut to 25 chars
          # and after the -> we say the number of characters that have been added.
          $del_cut = utils_cutstring ($previous_details, 25);
          $change = strlen ($details) - strlen ($previous_details);
          if ($change >= 0)
            $change = "+$change";
          $change .= " chars";

          trackers_data_add_history (
            'realdetails', htmlspecialchars ($del_cut),
            htmlspecialchars ($change), $item_id, false, false, true
          );
          $changes['realdetails']['add'] = $change;
          $changes['realdetails']['del'] = $del_cut;
        }
    }

  # Enter the timestamp if we are changing to closed or declined
  # (if not already set).
  if (isset ($fvl['status_id'])
      && trackers_data_is_status_closed ($vfl['status_id'])
      && $vfl['status_id'] != db_result ($result, 0, 'status_id'))
    {
      $now = time ();
      $upd_list['close_date'] = $now;
      trackers_data_add_history (
        'close_date', db_result ($result, 0, 'close_date'), $now, $item_id
      );
    }

  # Enter new dependencies.
  $artifacts = ["support", "bugs", "task", "patch"];
  $address = '';
  foreach ($artifacts as $dependent_on)
    {
      $art = $dependent_on;
      $dependent_on = "dependent_on_$dependent_on";
      if ($$dependent_on)
        {
          foreach ($$dependent_on as $dep)
            {
              trackers_data_update_dependent_items ($dep, $item_id, $art);

              $changes['Depends on']['add'] = "$art #$dep";
              $change_exists = 1;

              # Check if we are supposed to send all modifications
              # to an address.
              list ($address, $sendall) =
                trackers_data_get_item_notification_info ($dep, $art, 1);
              if (($sendall == 1) && (trim ($address) != ""))
                {
                  if (trim ($extra_addresses) != "")
                    {
                      $extra_addresses .= ", ";
                    }
                  $extra_addresses .= $address;
                }
            }
        }
    }

  # If we are on the cookbook, Store related links.
 if (ARTIFACT == 'cookbook')
   {
     cookbook_handle_update ($item_id, $group_id);
   }

  # Finally, build the full SQL query and update the bug itself (if need be).
  dbg ("UPD LIST: " . implode (',', $upd_list));
  if (count ($upd_list) > 0)
    {
      $res = db_autoexecute (
        ARTIFACT, $upd_list, DB_AUTOQUERY_UPDATE,
        "bug_id = ? AND group_id = ?", [$item_id, $group_id]
      );
      $result = db_affected_rows ($res);

      # Add CC (CC in case of comment would have been already entered,
      # if there is only a comment, we should not end up here).
      if (user_isloggedin () && !user_get_preference ("skipcc_updateitem"))
        trackers_add_cc ($item_id, $group_id, user_getid (), "-UPD-");
    }
  else
    {
      if ($change_exists)
        return true;
      fb (_("No field to update"));
      # Must return false, otherwise a notif would be sent.
      return false;
    }

  if (!$result)
    {
      exit_error (_("Item Update failed"));
      return false;
    }
  fb (_("Item Successfully Updated"));
  return true;
}

function trackers_data_reassign_item (
  $item_id, $reassign_change_project, $reassign_change_artifact
)
{
  global $group_id;

  # Can only be done by a tracker manager.
  if (
    !member_check (0, $group_id, member_create_tracker_flag (ARTIFACT) . '2')
  )
    return false;
  # If the new group_id is equal to the current one, nothing need
  # to be done, unless the artifact changed.
  # If the new group_id does not exists, nothing to be done either,
  # unless the artifact changed: if no new valid group_id, let
  # consider that it does not require a change.
  $new_group_id = group_getid ($reassign_change_project);
  if (!$new_group_id)
    $new_group_id = $group_id;

  if ($new_group_id == $group_id && ARTIFACT == $reassign_change_artifact)
    {
      fb (_("No reassignation required or possible."), 1);
      return false;
    }

  $now = time ();

  # To reassign an item, we close the item and we reopen a new one
  # at the appropriate place, copying information from the previous one
  # We do this because trackers may have specific fields not compatible
  # each others. Simply erase previous information could cause data loss.

  # Fetch all the information.
  $res_data = db_execute (
    "SELECT * FROM " . ARTIFACT . " WHERE bug_id = ?",
    [$item_id]
  );
  $row_data = db_fetch_array ($res_data);

  # Duplicate the report.
  if (!$reassign_change_project)
    $reassign_change_project = $group_id;

  if (!$reassign_change_artifact)
    {
      fb (_("Unable to find out to which artifact the item is to be\n"
            . "reassigned, exiting."), 1);
          return false;
    }

  # Move item.
  $result = db_autoexecute (
    $reassign_change_artifact,
    [
      'group_id' => $new_group_id, 'status_id' => 1, 'date' => $now,
      'severity' => $row_data['severity'],
      'submitted_by' => $row_data['submitted_by'],
      'summary' => $row_data['summary'], 'details' => $row_data['details'],
      'priority' => $row_data['priority'],
      'planned_starting_date' => $row_data['planned_starting_date'],
      'planned_close_date' => $row_data['planned_close_date'],
      'percent_complete' => $row_data['percent_complete'],
      'originator_email' => $row_data['originator_email']
    ],
    DB_AUTOQUERY_INSERT
  );

  if (!$result)
    {
      fb (_("Unable to create a new item."), 1);
      return false;
    }
  fb (_("New item created."));

  # Need to get the new item value.
  $new_item_id =  db_insertid ($result);
  if (!$new_item_id)
    {
      fb (_("Unable to find the ID of the new item."), 1);
      return false;
    }

  $grp_item = group_getname ($group_id) . ', '
    . utils_get_tracker_prefix (ARTIFACT) . " #$item_id";
  $new_grp_item = group_getname ($new_group_id) . ', '
    . utils_get_tracker_prefix ($reassign_change_artifact) . " #$new_item_id";

  trackers_data_add_history (
    'Reassign Item', $grp_item, $new_grp_item, $item_id, false, ARTIFACT, 1
  );

  trackers_data_add_history (
    'Reassign item', $grp_item, $new_grp_item,
    $new_item_id, false, $reassign_change_artifact, 1
  );

  # Duplicate the comments.
  $res_history = db_execute (
    "SELECT * FROM " . ARTIFACT . "_history WHERE bug_id = ? AND type = 100",
    [$item_id]
  );
  while ($row_history = db_fetch_array ($res_history))
    {
      $result = db_autoexecute (
        $reassign_change_artifact . "_history",
        [
          'bug_id' => $new_item_id, 'field_name' => $row_history['field_name'],
          'old_value' => $row_history['old_value'],
          'mod_by' => $row_history['mod_by'], 'date' => $row_history['date'],
          'type' => $row_history['type']
        ],
        DB_AUTOQUERY_INSERT
      );
      if (!$result)
        {
          fb (
            _("Unable to duplicate a comment from the original item\n"
              . "report information."),
            1);
        }
    }

  # Add a comment giving every original information.
  $comment = "This item has been reassigned from the project "
    . group_getname ($row_data['group_id']) . " " . ARTIFACT
    . " tracker to your tracker.\n\nThe original report is still available at "
    . ARTIFACT . " #$item_id\n\n"
    . "Following are the information included in the original report:\n\n";

  $res_show = db_query ("SHOW COLUMNS FROM " . ARTIFACT);
  $list = array();
  while ($row_show = db_fetch_array ($res_show))
    {
      # Build a list of any possible field.
      $list[] = $row_show['Field'];
    }

  foreach ($list as $l => $v)
    {
      if ($row_data[$l])
        {
          $comment .= "[field #" . $l . "] ";
          $comment .= trackers_field_display (
            $v, $group_id, $row_data[$l], false, true, true, true
          );
          $comment .= "<br />\n";
        }
    }

  $result = db_autoexecute (
    $reassign_change_artifact . "_history",
    [
      'bug_id' => $new_item_id, 'field_name' => 'details',
      'old_value' => $comment, 'mod_by' => user_getid (),
      'date' => $now, 'type' => 100
    ],
    DB_AUTOQUERY_INSERT
  );

  if (!$result)
    {
      fb (
        _("Unable to add a comment with the original item report\n"
          . "information."),
        1
      );
    }

  # Usually, reassigning means duplicating data.
  # In case of attached files, we simply reassign the file to another
  # item. This could avoid wasting too much disk space as file are expected
  # to be much bigger than CC list and alike.
  $result = db_autoexecute (
    "trackers_file",
    ['item_id' => $new_item_id, 'artifact' => $reassign_change_artifact],
    DB_AUTOQUERY_UPDATE, "item_id = ? AND artifact = ?", [$item_id, ARTIFACT]
  );

  if (!$result)
    {
      $msg = sprintf (
       _("Unable to duplicate an attached file (%s) from the\n"
         . "original item report information."),
       $row_attachment['filename']
      );
      fb ($msg, 1);
      dbg ("sql: $sql");
    }

  # Duplicate CC List.
  $res_cc = db_execute (
    "SELECT * FROM " . ARTIFACT . "_cc WHERE bug_id = ?",
    [$item_id]
  );
  while ($row_cc = db_fetch_array ($res_cc))
    {
      $result = db_autoexecute (
       "{$reassign_change_artifact}_cc",
        [
          'bug_id' => $new_item_id, 'email' => $row_cc['email'],
          'added_by' => $row_cc['added_by'],
          'comment' => $row_cc['comment'], 'date' => $row_cc['date']
        ],
        DB_AUTOQUERY_INSERT
      );

      if (!$result)
        {
          $msg = sprintf (
            _("Unable to duplicate a CC address (%s) from the\n"
              . "original item report information."),
            $row_cc['email']
          );
          fb ($msg, 1);
        }
    }

  # Update data of the original to make sure people dont get confused.
  # Close the original item.
  $result = db_autoexecute (
    ARTIFACT,
    [
      'status_id' => 3, 'close_date' => $now,
      'summary' =>
        "Reassigned to another tracker [was: {$row_data['summary']}]",
      'details' => 'THIS ITEM WAS REASSIGNED TO '
        . strtoupper (utils_get_tracker_prefix ($reassign_change_artifact))
        . " #$new_item_id\n" . $row_data['details']
    ],
    DB_AUTOQUERY_UPDATE, "bug_id = ?", [$item_id]
  );
  trackers_data_add_history ('close_date', $now, $now, $item_id);

  if ($result)
      fb (_("Original item is now closed."));
  else
      fb (_("Unable to close the original item report."), 1);

  # Finally put an extra comment so people dont get confused
  # (it is not important, so run the sql without checks).
  db_autoexecute (
    ARTIFACT . "_history",
    [
      'bug_id' => $item_id, 'field_name' => 'details',
      'old_value' => 'THIS ITEM WAS REASSIGNED TO '
        . strtoupper (utils_get_tracker_prefix ($reassign_change_artifact))
        . " #$new_item_id</p>\n"
        . 'Please, do not post any new comments to this item.',
      'mod_by' => user_getid (), 'date' => $now, 'type' => 100
    ],
    DB_AUTOQUERY_INSERT
  );

  # Now send the notification (this must be done here, because we got
  # here the proper new id, etc).
  list ($additional_address, $sendall) =
    trackers_data_get_item_notification_info (
      $new_item_id, $reassign_change_artifact, 1
    );
  trackers_mail_followup (
    $new_item_id, $additional_address, false, false, $reassign_change_artifact
  );

  # If we get here, assume everything went properly.
  return true;
}

function trackers_data_update_dependent_items ($depends_on, $item_id, $artifact)
{
  # Check if the dependency does not already exists.
  $result = db_execute ("
     SELECT item_id FROM " . ARTIFACT . "_dependencies
     WHERE
       item_id=?  AND is_dependent_on_item_id = ?
       AND is_dependent_on_item_id_artifact = ?",
    [$item_id, $depends_on, $artifact]
  );

  if (db_numrows ($result))
    return;
  # If there is no dependency know, insert it.
  $result = db_autoexecute (
    ARTIFACT . "_dependencies",
    [
      'item_id' => $item_id, 'is_dependent_on_item_id' => $depends_on,
      'is_dependent_on_item_id_artifact' => $artifact
    ],
    DB_AUTOQUERY_INSERT
  );
  if (!$result)
    {
      fb (_("Error inserting dependency"), 1);
      return;
    }
  fb (_("Dependency added"));
  trackers_data_add_history (
    "Dependencies", "-", "Depends on $artifact #$depends_on",
    $item_id, 0, 0, 1
  );
  trackers_data_add_history (
    "Dependencies", "-", ARTIFACT . " #$item_id is dependent",
    $depends_on, 0, $artifact, 1
  );
}

function trackers_data_create_item ($group_id, $vfl, &$extra_addresses)
{
  # We don't force them to be logged in to submit a bug.
  unset ($ip);
  if (!user_isloggedin ())
    $user = 100;
  else
    $user = user_getid ();

  # Make sure required fields are not empty.
  if (trackers_check_empty_fields ($vfl) == false)
    {
      # In such circumstances, we reprint the form
      # highligthing missing fields.
      # (It is important that trackers_check_empty_fields set the global var
      # previous_form_bad_fields.)
      return false;
    }

  # Finally, create the bug itself.
  # This SQL query only sets up the values for fields used by
  # this project. For other unused fields we assume that the DB will set
  # up an appropriate default value (see bug table definition).

  # Extract field transition possibilities:
  $field_transition = trackers_data_get_transition ($group_id);
  # We shall store in an array the transition_id accepted, to check
  # other field updates.
  $field_transition_accepted = array();
  $changes = array();

  # Build the variable list of fields and values.
  # We must add open/closed by ourselves, as it is missing from the
  # form for obvious reasons while automatic transitions may rely on its
  # presence.
  $vfl['status_id'] = '1';
  reset ($vfl);
  $insert_fields = [];
  $field_transition_id = '';
  foreach ($vfl as $field => $value)
    {
      if (trackers_data_is_special ($field))
        continue;

      # If value is the special string default,*
      # take the default from the database.
      if ($value == "!unknown!")
        continue;

      # COPIED from handle_update transition code, with one exception:
      # old_value is equal to "none".
      # Handle field transitions checks/changes.
      $field_id = trackers_data_get_field_id ($field);
      $field_transition_cc = '';
      if (array_key_exists ($field_id, $field_transition)
          # First check basic transition;
          # check multiple transition, override other transition.
          && (array_key_exists ("100", $field_transition[$field_id])
              || array_key_exists ("any", $field_transition[$field_id])))
        {
           $ft = $field_transition[$field_id];
           if (array_key_exists ("any", $ft)
               && array_key_exists ($value, $ft["any"]))
             {
               $field_transition_cc = $ft["any"][$value]['notification_list'];

               # Register the transition, but only if the field it is about
               # was not filled in the form.
               if (
                 !is_array ($changes[$field])
                 || (!array_key_exists ('del', $changes[$field])
                     && !array_key_exists ('add', $changes[$field]))
               )
                 $field_transition_id = $ft["any"][$value]['transition_id'];
             }
           else if (array_key_exists ("100", $ft)
                    && array_key_exists ($value, $ft["100"]))
             {
               $field_transition_cc = $ft["100"][$value]['notification_list'];
               # Register the transition, but only if the field it is about
               # was not filled in the form
               if (
                 !is_array ($changes[$field])
                 || (!array_key_exists ('del', $changes[$field])
                     && !array_key_exists ('add', $changes[$field]))
               )
                 $field_transition_id = $ft["100"][$value]['transition_id'];
             }
        }

      if (trackers_data_is_text_area ($field)
          || trackers_data_is_text_field ($field))
        $value = htmlspecialchars ($value);
      elseif (trackers_data_is_date_field ($field))
        list ($value, $ok) = utils_date_to_unixtime ($value);

      $insert_fields[$field] = $value;

      # Keep track of the change:
      $changes[$field]['del'] =
        trackers_field_display ($field, $group_id, '', false, false, true, true);
      $changes[$field]['add'] =
        trackers_field_display ($field, $group_id, $value, false, false, true,
                               true);

      $changes[$field]['del-val']= '';
      $changes[$field]['add-val']= $value;

      # Register transition id.
      $field_transition_accepted[] = $field_transition_id;

      if ($field_transition_cc)
        $extra_addresses .= $field_transition_cc;
    } # foreach ($vfl as $field => $value)

  # Get the default spamscore.
  $spamscore = spam_get_user_score ($user);
  if ($spamscore > 4)
    {
      $vfl['summary'] = "[SPAM] " . $vfl['summary'];
    }

  # Add all special fields that were not handled in the previous block.
  $insert_fields['close_date'] = 0;
  $insert_fields['group_id'] = $group_id;
  $insert_fields['submitted_by'] = $user;
  $insert_fields['date'] = time ();
  $insert_fields['summary'] = htmlspecialchars ($vfl['summary']);
  $insert_fields['details'] = htmlspecialchars ($vfl['details']);
  $insert_fields['spamscore'] = $spamscore;
  $insert_fields['ip'] = '127.0.0.1';

  # Actually insert the entry.
  $result = db_autoexecute (ARTIFACT, $insert_fields, DB_AUTOQUERY_INSERT);
  $item_id = db_insertid ($result);

  if (!$item_id)
    {
      fb (
        _("New item insertion failed, please report this issue to the\n"
          . "administrator"),
        1
      );
      return false;
    }

  # TANSLATORS: the first argument is tracker type (like sr, bug or recipe)
  # the second argument is item id (number).
  $msg = sprintf (
    _('New item posted (%1$s #%2$s)'),
    utils_get_tracker_prefix (ARTIFACT), $item_id
  );
  fb ($msg);

  # Register the spam score.
  spam_set_item_default_score ($item_id, '0', ARTIFACT, $spamscore, $user);

  # Add to spamcheck queue, if necessary (will temporary set the spamscore to
  # 5, if necessary).
  # Useless, if already considered to be spam.
  if ($spamscore < 5)
    spam_add_to_spamcheck_queue ($item_id, 0, ARTIFACT, $group_id, $spamscore);

  # If we are on the cookbook, store related links.
  if (ARTIFACT == 'cookbook')
   cookbook_handle_update ($item_id, $group_id);

  # Now we run transitions other fields update. This function does check
  # what already changed and that we shan't automatically update.
  trackers_transition_update_item (
    $item_id, $field_transition_accepted, $changes
  );

  # Add the submitter in CC
  # (currently, no option to avoid this, but we could make this a notif
  # configuration option, if wanted).
  if (user_isloggedin ())
    trackers_add_cc ($item_id, $group_id, user_getid (), "-SUB-");
  return $item_id;
}

# Simply return the value associated with a given value_id
# for a given field of a given group. If associated value not
# found then return value_id itself.
# By doing so if this function is called by mistake on a field with type
# text area or text field then it returns the text itself.
function trackers_data_get_value (
  $field, $group_id, $value_id, $by_field_id = false
)
{
  # submitted_by and assigned_to fields are special select box fields.
  if (($field == 'assigned_to') || ($field == 'submitted_by'))
    return user_getname ($value_id);

  if (trackers_data_is_date_field ($field))
    return utils_format_date ($value_id);

  if ($by_field_id)
    $field_id = $field;
  else
    $field_id = trackers_data_get_field_id ($field);

  $sql = "
    SELECT * FROM " . ARTIFACT . "_field_value
    WHERE group_id = ? AND value_id = ?";
  $args = [$group_id, $value_id];
  if ($field_id !== null)
    {
      $sql .= ' AND bug_field_id = ?';
      $args[] = $field_id;
    }

  # Look for project specific values first...
  $result = db_execute ($sql, $args);
  if ($result && db_numrows ($result) > 0)
    return db_result ($result, 0, 'value');

  # ... if it fails, look for system wide default values (group_id=100)...
  $args[0] = 100;
  $result = db_execute ($sql, $args);
  if ($result && db_numrows ($result) > 0)
    return db_result ($result, 0, 'value');

  # No value found for this value id.
  return $value_id . _('(Error - Not Found)');
}

# Show defined and site-wide responses.
function trackers_data_get_canned_responses ($group_id)
{
  # Return handle for use by select box.
  return db_execute ("
    SELECT bug_canned_id, title, body, order_id
    FROM " . ARTIFACT . "_canned_responses
    WHERE (group_id = ? OR group_id = 0)
    ORDER BY order_id ASC",
    [$group_id]
  );
}

function trackers_data_get_reports ($group_id, $user_id = 100)
{
  # Currently, reports are group based.
  # Print first system reports.

  # OUTDATED: currently personal query forms are deactivated in the code
  # If user is unknown then get only project-wide and system wide reports
  # else get personal reports in addition  project-wide and system wide.

  $system_scope = 'S';

  $sql = "
    SELECT report_id,name FROM " . ARTIFACT . "_report
    WHERE (group_id = ? AND scope = 'P') OR scope = ?
    ORDER BY scope DESC, report_id ASC";

  return db_execute ($sql, [$group_id, $system_scope]);
}

function trackers_data_get_notification ($user_id)
{
  return db_execute ("
    SELECT role_id, event_id, notify FROM trackers_notification
    WHERE user_id = ?",
    [$user_id]
  );
}

function trackers_data_get_notification_with_labels ($user_id)
{
  return db_execute ("
    SELECT role_label, event_label, notify
    FROM
      trackers_notification_role r, trackers_notification_event e,
      trackers_notification n
    WHERE
      n.role_id = r.role_id AND n.event_id = e.event_id AND user_id = ?",
    [$user_id]
  );
}

function trackers_data_get_notification_roles ()
{
  return db_query (
    'SELECT * FROM trackers_notification_role ORDER BY rank ASC'
  );
}

function trackers_data_get_notification_events ()
{
  return db_query (
    'SELECT * FROM trackers_notification_event ORDER BY rank ASC'
  );
}

function trackers_data_delete_notification ($user_id)
{
  return db_execute (
    "DELETE FROM trackers_notification WHERE user_id = ?", [$user_id]
  );
}

function trackers_data_insert_notification (
  $user_id, $arr_roles, $arr_events, $arr_notification
)
{
  $sql = 'INSERT INTO trackers_notification (user_id,role_id,event_id,notify)
          VALUES ';
  $sql_params = [];

  $num_roles = count ($arr_roles);
  $num_events = count ($arr_events);
  for ($i = 0; $i < $num_roles; $i++)
    {
      $role_id = $arr_roles[$i]['role_id'];
      for ($j = 0; $j < $num_events; $j++)
        {
          $sql_params[] = $user_id;
          $sql_params[] = $role_id;
          $sql_params[] = $arr_events[$j]['event_id'];
          $sql_params[] = $arr_notification[$role_id][$event_id];
        }
    }
  $sql .= utils_str_join (",\n", "(?, ?, ?, ?)", $num_events * $num_roles);
  return db_execute ($sql, $sql_params);
}

function trackers_data_get_watchers ($user_id)
{
  return db_execute ("
    SELECT user_id, group_id FROM trackers_watcher WHERE watchee_id = ?",
    [$user_id]
  );
}

function trackers_data_get_watchees ($user_id)
{
  return db_execute ("
    SELECT watchee_id, group_id FROM trackers_watcher WHERE user_id = ?",
    [$user_id]
  );
}

function trackers_data_add_watchees ($user_id, $watchee_id, $group_id)
{
  if (
    !(member_check (0, $group_id)
      && !trackers_data_is_watched ($user_id, $watchee_id, $group_id))
  )
    return 0;
  # Only accept the request from a member of the project.
  # Note that a user can trick the URL to watch himself.
  # It has no consequences, so we do not care.
  return db_autoexecute (
   'trackers_watcher',
    [
      'user_id' => $user_id, 'watchee_id' => $watchee_id,
      'group_id' => $group_id
    ],
    DB_AUTOQUERY_INSERT
  );
}

function trackers_data_delete_watchees ($user_id, $watchee_id, $group_id)
{
  return db_execute ("
    DELETE FROM trackers_watcher
    WHERE user_id = ? AND watchee_id = ? AND group_id = ?",
    [$user_id, $watchee_id, $group_id]
  );
}

function trackers_data_is_watched ($user_id, $watchee_id, $group_id)
{
  $result = db_execute ("
     SELECT watchee_id FROM trackers_watcher
     WHERE user_id = ? AND watchee_id = ? AND group_id = ?",
    [$user_id, $watchee_id, $group_id]
  );
  if (db_numrows ($result))
    return db_result ($result, 0, 'watchee_id');
  return null;
}

function trackers_data_delete_file ($group_id, $item_id, $file_id)
{
  global $sys_trackers_attachments_dir;
  # Make sure the attachment belongs to the group.
  $res = db_execute ("
    SELECT bug_id from " . ARTIFACT . " WHERE bug_id = ? AND group_id = ?",
    [$item_id, $group_id]
  );
  if (db_numrows ($res) <= 0)
    {
      # TRANSLATORS: the argument is item id (a number).
      $msg = sprintf (
        _("Item #%s doesn't belong to project"), $item_id
      );
      fb ($msg, 1);
      return;
    }

  $result = false;
  # Delete the attachment.
  if (unlink ("$sys_trackers_attachments_dir/$file_id"))
    $result = db_execute ("
      DELETE FROM trackers_file WHERE item_id = ?  AND file_id = ?",
      [$item_id, $file_id]
    );

  if (!$result)
    {
      # TRANSLATORS: the argument is file id (a number); the string
      # shall be followed by database error message.
      $msg = sprintf (_("Can't delete attachment #%s:"), $file_id);
      fb ($msg . " " . db_error ($res), 1);
    }
  else
    {
      fb (_("File successfully deleted"));
      trackers_data_add_history (
        "Attached File", "#$file_id", "Removed", $item_id, 0, 0, 1
      );
    }
}

function trackers_data_count_field_value_usage (
  $group_id, $field, $field_value_value_id
)
{
  if ($field == 'comment_type_id')
    return 0;
  if (!preg_match ('/^[a-z0-9_]+$/', $field))
    util_die (
      'trackers_data_count_field_value_usage: invalid $field <em>'
      . htmlspecialchars ($field) . '</em>'
    );
  $res = db_execute ("
    SELECT COUNT(*) AS count FROM " . ARTIFACT . "
    WHERE $field = ? AND group_id = ?",
    [$field_value_value_id, $group_id]
  );
  return db_result ($res, 0, 'count');
}

function trackers_data_quote_comment ($item_id, $quote_no)
{
  $entry = false;
  if ($quote_no == 0)
    {
      $result = db_execute ("
        SELECT
          u.user_id, u.user_name, u.realname, a.date, a.details, a.spamscore
        FROM " . ARTIFACT . " a, user u
        WHERE a.submitted_by = u.user_id AND a.bug_id = ? LIMIT 1",
        [$item_id]
      );
      $entry = db_fetch_array ($result)['details'];
      $label = _("original submission:");
    }
  else
    {
      $result = trackers_data_get_followups ($item_id);
      if ($quote_no <= db_numrows ($result))
        $entry = db_result ($result, $quote_no - 1, 'old_value');
      $label = sprintf (_("comment #%s:"), $quote_no);
    }
  if ($entry === false)
    return $entry;
  $entry = html_entity_decode (trackers_decode_value ($entry));
  $quote = preg_replace ("/(^|\n)/", "$1> ", $entry);
  $quote = "\n\n[comment #$quote_no $label]\n$quote";
  return $quote;
}
?>
