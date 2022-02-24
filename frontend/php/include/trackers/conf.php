<?php
# Functions related to trackers configuration
#
# Copyright (C) 2004 Mathieu Roy <yeupou--at--gnu.org>
# Copyright (C) 2004 Yves Perrin <yves.perrin--at--cern.ch>
# Copyright (C) 2017, 2018, 2022 Ineiev
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

# This page should store function related to trackers configuration
# (some of these are in general/data and should be moved here)

# Copy for a given tracker the configuration of the tracker of another
# project. This action is irreversible and can alter in an incoherent way
# already posted items: it is supposed to be mainly used to configure a
# new tracker. It can be used to keep several project using a coherent
# configuration but it should not be used a trackers will divergeant
# configuration already being used.
#
# To ease development, we ll make simple SQL query and we ll parse the
# result. We ll be doing dumb code, code that we ll be able to debug.
# (you need to be smarter than the code to be able to debug it, so lets avoid
# writing the smartest code, so we still have a chance)
function artifact_name_prefixed ($artifact)
{
   switch($artifact)
     {
       case 'bugs': return
       # TRANSLATORS: this string (after removing '[artifact]')
       # is used in context of "%s tracker".
                           _('[artifact]bug');
       case 'patch': return
       # TRANSLATORS: this string (after removing '[artifact]')
       # is used in context of "%s tracker".
                           _('[artifact]patch');
       case 'task': return
       # TRANSLATORS: this string (after removing '[artifact]')
       # is used in context of "%s tracker".
                           _('[artifact]task');
       case 'cookbook': return
       # TRANSLATORS: this string (after removing '[artifact]')
       # is used in context of "%s tracker".
                               _('[artifact]cookbook');
       case 'support': return
       # TRANSLATORS: this string (after removing '[artifact]')
       # is used in context of "%s tracker".
                              _('[artifact]support');
       case 'news': return
       # TRANSLATORS: this string (after removing '[artifact]')
       # is used in context of "%s tracker".
                             _('[artifact]news');
       default: return $artifact;
     }
   return $artifact;
}
function artifact_name ($artifact)
{
  $name = artifact_name_prefixed ($artifact);
  $pos = strpos ($name, ']');
  if ($pos === false)
    return $name;
  return substr ($name, $pos + 1);
}

function trackers_conf_copy ($group_id, $artifact, $from_group_id)
{
  if (!$artifact || !$group_id || !$from_group_id)
    {
      # Case that should never happen.
      fb(_("Missing parameters"), 1);
      return 0;
    }
  fb(sprintf(
# TRANSLATORS: the first argument is group id (a number),
# the second argument is previously defined string (bug|patch|task|...)
             _('Start copying configuration of group #%1$s %2$s tracker'),
             $from_group_id, artifact_name($artifact)));

# Copy the notification settings.
  $res_groups_from_group = db_execute("SELECT * FROM groups WHERE group_id=?",
                                      array($from_group_id));
  $rows = db_fetch_array($res_groups_from_group);
  $res = db_autoexecute('groups',
                        array("new_{$artifact}_address"
                                 => $rows["new_{$artifact}_address"],
                              "{$artifact}_glnotif"
                                 => $rows["{$artifact}_glnotif"],
                              "send_all_{$artifact}"
                                 => $rows["send_all_{$artifact}"],
                              "{$artifact}_private_exclude_address"
                                 => $rows["{$artifact}_private_exclude_address"]),
                        DB_AUTOQUERY_UPDATE,
                        "group_id=?", array($group_id));

  if (db_affected_rows($res))
    fb(_("Notification settings copied"));

  # Delete currently set field usage and field values
  # Copy the field usage and field values of the other project
  if (db_affected_rows(db_execute("DELETE FROM {$artifact}_field_value
                                   WHERE group_id = ?", array($group_id))))
    fb(_("Previous field values deleted"));
  if (db_affected_rows(db_execute("DELETE FROM {$artifact}_field_usage
                                   WHERE group_id = ?", array($group_id))))
    fb(_("Previous field usage deleted"));

  $result_field_usage_from_group =
    db_execute("SELECT * FROM {$artifact}_field_usage WHERE group_id=?",
               array($from_group_id));

  function print_no ($id)
  {
    return sprintf (
    # TRANSLATORS: the argument is id (a number).
                    _("#%s"), $id)." ";
  }

  function print_items ($result_field_usage_from_group, $artifact_key,
                        $group_id, $field, $field_idx)
  {
    $z = 0;
    $ret = '';

    while ($thisone = db_fetch_array($result_field_usage_from_group))
      {
        $res = db_createinsertinto($result_field_usage_from_group,
                                   $artifact_key,
                                   $z,
                                   $field,
                                   "group_id",
                                   $group_id);

        if (db_affected_rows($res))
          $ret .= print_no ($thisone[$field_idx]);
        $z++;
      }
    return $ret;
  }
  $itemsdone = print_items ($result_field_usage_from_group,
                            $artifact."_field_usage", $group_id, 'none',
                            'bug_field_id');
  if ($itemsdone)
    fb(sprintf(
# TRANSLATORS: the argument is space-separated list of field ids.
               _("Field values %s copied"), $itemsdone));

  $result_field_value_from_group =
    db_execute("SELECT * FROM ".$artifact."_field_value WHERE group_id=?",
               array($from_group_id));
  $itemsdone = print_items ($result_field_usage_from_group,
                            $artifact."_field_value", $group_id, 'bug_fv_id',
                            'bug_fv_id');
  if ($itemsdone)
    fb(sprintf(
# TRANSLATORS: the argument is space-separated list of value ids.
               _("Field values %s copied"), $itemsdone));

  # Delete currently set canned responses.
  # Copy the canned responses of the other project.
  if (db_affected_rows(db_execute("DELETE FROM ".$artifact
                                  ."_canned_responses WHERE group_id=?",
                                  array($group_id))))
    fb(_("Previous canned responses deleted"));

  $result_canned_from_group = db_execute("SELECT * FROM ".$artifact
                                         ."_canned_responses WHERE group_id=?",
                                         array($from_group_id));
  $itemsdone = print_items ($result_canned_from_group,
                            $artifact."_canned_responses", $group_id,
                            'bug_canned_id', 'bug_canned_id');
  if ($itemsdone)
    fb(sprintf(
# TRANSLATORS: the argument is space-separated list of response ids.
               _("Canned responses %s copied"), $itemsdone));

  # Delete currently set query forms.
  # Copy the query forms of the other project.
  $res_queryforms = db_execute("SELECT * FROM ".$artifact
                               ."_report WHERE group_id=?",
                               array($group_id));
  if (db_affected_rows(db_execute("DELETE FROM ".$artifact
                                  ."_report WHERE group_id=?",
                                  array($group_id))))
    fb(_("Previous query forms deleted"));
  while ($thisone = db_fetch_array($res_queryforms))
    {
      # Not verbose.
      db_execute("DELETE FROM ".$artifact."_report_field WHERE report_id=?",
                 array($thisone['report_id']));
    }

  $result_queryforms_from_group = db_execute("SELECT * FROM ".$artifact."_report
                                              WHERE group_id=?",
                                             array($from_group_id));
  $z = 0;
  $itemsdone = '';
  while ($thisone = db_fetch_array($result_queryforms_from_group))
    {
      # Copy the report.
      $res = db_createinsertinto($result_queryforms_from_group,
                                 $artifact."_report",
                                 $z,
                                 "report_id",
                                 "group_id",
                                 $group_id);
      $thisone_id = db_insertid($res);
      if ($thisone_id)
        {
          $itemsdone .= print_no($thisone['report_id']);

          # Copy the info related to the report in report_field.
          $result_thisqueryforms_from_group =
            db_execute("SELECT * FROM ".$artifact
                       ."_report_field WHERE report_id=?",
                       array($thisone['report_id']));
          $y = 0;
          while ($thisonequery = db_fetch_array($result_thisqueryforms_from_group))
            {
              # Silent: if we list even these insert, the feedback will
              # be unreadable, too long.
              db_createinsertinto($result_thisqueryforms_from_group,
                                  $artifact."_report_field",
                                  $y,
                                  "none",
                                  "report_id",
                                  $thisone_id);
              $y++;
            }
        }
      $z++;
    }
  if ($itemsdone)
    fb(sprintf(
# TRANSLATORS: the argument is space-separated list of report ids.
               _("Query forms %s copied"), $itemsdone));

  # Delete current set transitions.
  # Copy the transition of the other project.
  $res_transitions= db_execute("SELECT * FROM trackers_field_transition
                                WHERE group_id=? AND artifact=?",
                               array($group_id, $artifact));
  if (db_affected_rows(db_execute("DELETE FROM trackers_field_transition
                                   WHERE group_id=? AND artifact=?",
                                  array($group_id, $artifact))))
    fb(_("Previous field transitions deleted"));
  while ($thisone = db_fetch_array($res_transitions))
    {
      db_execute("DELETE FROM trackers_field_transition_other_field_update
                  WHERE transition_id=?",
                 array($thisone['transition_id']));
    }

  $result_transitions_from_group =
    db_execute("SELECT * FROM trackers_field_transition
                WHERE artifact=? AND group_id=?",
               array($artifact, $from_group_id));
  $z = 0;
  $itemsdone = '';
  while ($thisone = db_fetch_array($result_transitions_from_group))
    {
      # Copy the report.
      $res = db_createinsertinto($result_transitions_from_group,
                                 "trackers_field_transition",
                                 $z,
                                 "transition_id",
                                 "group_id",
                                 $group_id);
      $thisone_id = db_insertid($res);
      if ($thisone_id)
        {
          $itemsdone .= print_no ($thisone['transition_id']);

          # Copy the info related to the report in report_field.
          $result_thistransitions_from_group =
            db_execute("SELECT * FROM trackers_field_transition_other_field_update
                        WHERE transition_id=?", array($thisone['transition_id']));
          $y = 0;
          while ($thisonequery =
                   db_fetch_array($result_thistransitions_from_group))
            {
              # Silent: if we list even these insert, the feedback will
              # be unreadable, too long.
              db_createinsertinto($result_thistransitions_from_group,
                                  "trackers_field_transition_other_field_update",
                                  $y,
                                  "other_field_update_id",
                                  "report_id",
                                  $thisone_id);
              $y++;
            }
        }
      $z++;
    }
  if ($itemsdone)
    fb(sprintf(
# TRANSLATORS: the argument is space-separated list of transition ids.
               _("Transitions %s copied"), $itemsdone));
  fb(_("Configuration copy finished"));
}

function conf_form ($group_id, $artifact)
{
  $result = db_execute("SELECT groups.group_name,groups.group_id
                       FROM groups,user_group
                       WHERE groups.group_id=user_group.group_id
                         AND user_group.user_id = ?
                         AND groups.status = 'A'
                         AND groups.use_{$artifact} = '1'",
                       array(user_getid()));

  $vals = array();
  $texts = array();
  $found = false;
  while ($thisgroup = db_fetch_array ($result))
    {
      $vals[] = $thisgroup['group_id'];
      $texts[] = $thisgroup['group_name'];
      $found = true;
    }
  print '<p>';
  $art_name = artifact_name ($artifact);
  if (!$found)
    {
      printf (
# TRANSLATORS: the argument is previously defined string (bug|patch|task|...)
        _("You cannot copy the configuration of other
projects because you are not member of any project hosted here that uses a %s
tracker.") . "</p>\n",
        $art_name
      );
      return;
    }
  printf(
    # TRANSLATORS: the argument is previously defined string (bug|patch|...)
    _("You can copy the configuration of the %s tracker
of the following projects (this list was established according to your
currently membership record)."),
    $art_name);
  print"</p>\n<p class='warn'>"
    . _("Beware, your current configuration will be irremediably lost.")
    . "</p>\n\n<form action=\""
    . htmlentities ($_SERVER['PHP_SELF']) . "\" method='post'>\n"
    . "<input type='hidden' name='group_id' value='$group_id)' />\n"
    . "<input type='hidden' name='artifact' value='$artifact' />\n"
    . '<span class="preinput"><label for="from_group_id">' . _("Projects:")
    . "</label></span>&nbsp;&nbsp;&nbsp;\n";
  print html_build_select_box_from_arrays ($vals, $texts, 'from_group_id');
  print form_footer();
}
?>
