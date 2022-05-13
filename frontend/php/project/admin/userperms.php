<?php
# Modify user permissions.
#
# Copyright 1999-2000 The SourceForge Crew
# Copyright 2000-2003 Free Software Foundation
# Copyright 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright 2004-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright 2017 Ineiev
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

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

# Labels are used as keys because they are used less often.
$trackers = [
  _("Support Tracker") => 'support', _("Bug Tracker") => 'bugs',
  _("Task Tracker") => 'task', _("Patch Tracker") => 'patch',
  _("Cookbook Manager") => 'cookbook', _("News Manager") => 'news'
];

$perm_regexp = '/^(\d+|NULL)$/';

# Internal function to determine if a squad permission must override user perm
# or not
#  * If user got lower rights, we override, because we assume that user has
# been added to obtain at least the rights given the squad
#  * If user got higher rights, we let as it is because the user may be
# member of a squad that provides somes rights to its members but being
# himself someone that have other duties requiring in some cases more
# rights.
function _compare_perms ($squad_perm, $user_perm)
{
  # We have to do some subtle comparisons because, unfortunately, perms
  # flags have a long history and are not completely consistant: higher does
  # not always mean better
  #   NULL = use default (group or group type)
  #   9 = none
  #   1 = technician
  #   3 = manager
  #   2 = technician & manager

  # if both perms are equal, dont bother checking further
  if ($squad_perm == $user_perm)
    { return $user_perm; }

  # if squad perm is 9 (none), keep user perm anyway
  if ($squad_perm == "9")
    { return $user_perm; }

  # if user perm is 9 (none) or NULL (group default), take it squad perm
  # (that cannot be 9, excluded already)
  if ($user_perm == "9")
    {
      $GLOBALS['did_squad_override'] = true;
      return $squad_perm;
    }

  # If user perm or squad perm is 2 (techn and manager),
  # there is nothing higher, take it
  if ($user_perm == "2" || $squad_perm == "2")
    {
      if ($squad_perm == "2")
        { $GLOBALS['did_squad_override'] = true; }
      return "2";
    }

  # if user perm and squad perm are 1 (techician) and  3 (manager), assume
  # that the result is that the user should be 2 (both technician and manager)
  if (($user_perm == "1" && $squad_perm == "3") ||
      ($squad_perm == "1" && $user_perm == "3"))
    {
      $GLOBALS['did_squad_override'] = true;
      return "2";
    }

  # If we end here, nothing conclusive, keep the user perm
  return $user_perm;
}

extract (sane_import ('post', ['true' => 'update']));
$project = project_get_object($group_id);

if ($update)
  {
    $feedback_able = null;
    $feedback_unable = null;
    $feedback_squad_override = null;

    # Get the members list, taking first the squads.
    $res_dev = db_execute("SELECT user_id,admin_flags FROM user_group "
                          ."WHERE group_id=? AND admin_flags<>'P' "
                          ."ORDER BY admin_flags DESC", array($group_id));

    # Save the squads permissions to override users permissions if necessary.
    $squad_permissions = array();

    while ($row_dev = db_fetch_array($res_dev))
      {
        $row_uid = $row_dev['user_id'];
        $name = user_getname ($row_uid);

        # Site admins are not allowed to changer their own user rights
        # on a project they are member of.
        # It creates issues (flags erroneously set).
        # They should use admin interface instead or end su session.
        if (user_is_super_user() && $row_dev['user_id'] == user_getid())
          {
# TRANSLATORS: the argument is user's number in the database.
            fb(sprintf(_("Configuration for user #%s (you!) ignored to avoid
incoherent flags status. End the superuser session to change your settings in
this group or use the admin user interface."), $row_dev['user_id']), 1);
            continue;
          }

        $names = [];
        foreach ($trackers as $flag)
          {
            $var = $flag . '_flags';
            $names[] = $$var = "${flag}_user_{$row_uid}";
          }
        $names[] = $perm_regexp;

        $onduty = "onduty_user_{$row_uid}";
        $cb_names = [$onduty];
        $cb_names[] = $privacy_flags = "privacy_user_{$row_uid}";
        $admin_flags = "admin_user_$row_uid";

        $permissions =
          sane_import ('post',
            [
              'preg' => [$names],
              'strings' =>  [[$admin_flags, ['A', 'SQD', 'P']]],
              'true' => $cb_names,
            ]
          );

        $sq_flag_arr = $trackers;
        $sq_flag_arr[] = 'privacy';
        $sq_flag_arr[] = 'admin';

        # Admin are not allowed to turn off their own admin flag.
        # It is too dangerous -- set it back to 'A'.
        if (user_getid() == $row_uid)
          $permissions[$admin_flags] = 'A';
        $is_squad = $row_dev['admin_flags'] == 'SQD';
        # Squads flag cannot be changed, squads should not be turned into normal
        # users.
        if ($is_squad)
          $permissions[$admin_flags] = 'SQD';
        if ($permissions[$admin_flags] === null)
          $permissions[$admin_flags] = '';

        # Admins have the access to the private items.
        if ($permissions[$admin_flags] == "A")
          $permissions[$privacy_flags] = '1';

        if ($is_squad)
          {
            # If it is a squad, save every setting even if useless, it costs
            # nothing.
            $squad_id = $row_dev['user_id'];
            foreach ($sq_flag_arr as $flag)
              {
                $var = $flag . '_flags';
                $squad_permissions[$squad_id . $flag]
                  = $permissions[$$var];
              }
          }
        else
          {
            # If it is not a squad, we then have to check if the user is
            # member of any squad, and if he is, we have to check which
            # setting must be kept (see _compare_perms comments).
            $result_user_squads = db_execute ("
              SELECT squad_id FROM user_squad
              WHERE user_id=? AND group_id = ?",
              array($row_dev['user_id'], $group_id)
            );
            if (db_numrows($result_user_squads))
              {
                while ($thissquad = db_fetch_array($result_user_squads))
                  {
                    $thesquad = $thissquad['squad_id'];
                    $GLOBALS['did_squad_override'] = false;
                    foreach ($trackers as $flag)
                      {
                        $var = $flag . '_flags';
                        $perm =
                          $squad_permissions[$thesquad . $flag];
                        $out[$$var] =
                          _compare_perms ($perm, $permissions[$$var]);
                      }

                    if ($squad_permissions[$thesquad . 'privacy']
                         > $permissions[$privacy_flags])
                      {
                        $GLOBALS['did_squad_override'] = true;
                        $permissions[$privacy_flags] =
                          $squad_permissions[$thesquad . 'privacy'];
                      }
                    # Record any squad override for later generated feedback.
                    if ($GLOBALS['did_squad_override'])
                      $feedback_squad_override =
                        sprintf (
                      # TRANSLATORS: the argument is user's name.
_("Personal permissions of %s were overridden by squad permissions")."\n",
                          $name
                        );
                  }
              }
          }

        $fields_values = array(
          'admin_flags' => $permissions[$admin_flags],
          'privacy_flags' => $permissions[$privacy_flags],
          'onduty' => $permissions[$onduty],
        );

        foreach ($trackers as $art)
          {
            $var = $art . '_flags';
            if (tracker_uses ($project, $art))
              $fields_values[$var] = $permissions[$$var];
          }
        $result = db_autoexecute('user_group',
                                 $fields_values,
                                 DB_AUTOQUERY_UPDATE,
                                 "user_id=? AND group_id=?",
                                 array($row_dev['user_id'], $group_id));

      # Notice any change, yell on error, keep silent if no changes was
      # necessary (if db_affected_rows works normally, which does not seems
      # to always be the case).
        if ($result && db_affected_rows($result))
          {
            if ($is_squad)
              {
                $string = 'Changed Squad Permissions';
                $feedback_able .= sprintf(_('Changed Squad %s Permissions')."\n",
                                          $name);
              }
            else
              {
                $string = 'Changed User Permissions';
                $feedback_able .= sprintf(_('Changed User %s Permissions')."\n",
                                          $name);
              }
            group_add_history($string,
                              $name,
                              $group_id);
          }
        elseif (!$result)
          {
            if ($is_squad)
              $feedback_able .= sprintf(_('Unable to change squad %s permissions')
                                        ."\n",
                                        $name);
            else
              $feedback_able .= sprintf(_('Unable to change user %s permissions')
                                        ."\n",
                                        $name);
          }
      } # while ($row_dev = db_fetch_array($res_dev))

    if ($feedback_able)
      fb($feedback_able);
    if ($feedback_squad_override)
      fb($feedback_squad_override);
    if ($feedback_unable)
      fb($feedback_unable);

    # Update group default permissions.
    $names = [];
    foreach ($trackers as $art)
      $names[] = $art . "_user_";
    $names[] = $perm_regexp;
    extract (sane_import ('post', ['preg' => [$names]]));

    # If the group entry do not exists, create it.
    if (!db_numrows(db_execute("SELECT groups_default_permissions_id "
                               ."FROM groups_default_permissions WHERE group_id=?",
                               array($group_id))))
      db_execute("INSERT INTO groups_default_permissions (group_id) VALUES (?)",
                 array($group_id));

    # Update the table.
    $fields_values = [];
    foreach ($trackers as $art)
      {
        $var = $art . '_user_';
        if (tracker_uses ($project, $art))
          $fields_values[$art . '_flags'] = $$var;
      }

    $result = db_autoexecute('groups_default_permissions',
                             $fields_values,
                             DB_AUTOQUERY_UPDATE,
                             "group_id=?", array($group_id));

    if ($result && db_affected_rows($result))
      {
        group_add_history('Changed Group Default Permissions','',$group_id);
        fb(_("Permissions for the group updated."));
      }
    elseif (!$result)
      fb(_("Unable to change group default permissions."), 1);

    # Update posting restrictions
    # (if equal to 0, manually set to NULL, since 0 have a different meaning).
    $names = [];
    foreach ($trackers as $art)
      foreach ([1, 2] as $ev_no)
        $names[] = "${art}_restrict_event$ev_no";
    $names[] = $perm_regexp;
    extract (sane_import ('post', ['preg' => [$names]]));
    foreach ($trackers as $art)
      {
        $flags = $art . '_flags';
        $ev1 = $art . '_restrict_event1'; $ev2 = $art . '_restrict_event2';
        $$flags  = intval ($$ev2) * 100 + intval ($$ev1);
        if (!$$flags)
          $$flags = 'NULL';
      }
    $fields = [];
    foreach ($trackers as $art)
      {
        $var = $art . '_flags';
        $fields[$art . '_rflags'] = $$var;
      }
    $result = db_autoexecute (
      'groups_default_permissions', $fields, DB_AUTOQUERY_UPDATE,
      "group_id = ?", [$group_id]
    );

    if ($result && db_affected_rows($result))
      {
        group_add_history ('Changed Posting Restrictions', '', $group_id);
        fb(_("Posting restrictions updated."));
      }
    elseif (!$result)
      fb(_("Unable to change posting restrictions."), 1);
  } # if ($update)

function finish_page ()
{
  site_project_footer(array());
  exit (0);
}

function tracker_uses ($project, $art)
{
  return $art == 'cookbook' || $project->Uses ($art);
}

function add_used_tracker_titles (&$title_arr, $project)
{
  global $trackers;
  foreach ($trackers as $title => $art)
    if (tracker_uses ($project, $art))
      $title_arr[] = $title;
}

site_project_header(array('title'=>_("Set Permissions"),'group'=>$group_id,
                          'context'=>'ahome'));

print form_header($_SERVER['PHP_SELF']).
form_input("hidden", "group", $group);

# Posting restrictions.
# Exists also in trackers config (missing for news).

$i = 0;
$title_arr = [
  # TRANSLATORS: this is the header for a column with two rows,
  # "Posting new items" and "Posting comments".
  _("Applies when ...")
];
add_used_tracker_titles ($title_arr, $project);

print '<h2>' . _("Group trackers posting restrictions") . "</h2>\n<p>";
print _("Here you can set the minimal authentication level required in order to
post on the trackers.");
print "</p>\n";

print html_build_list_table_top ($title_arr);

$i++;
print "\n<tr class=\"" . utils_altrow ($i) . "\">\n<td>"
  # TRANSLATORS: this is a column row which header says "Applies when ...".
  . _("Posting new items") . "</td>\n";

function select_box_if_uses (
  $project, $tracker, $group_id, $infix, $extra = null
)
{
  if (!tracker_uses ($project, $tracker))
    return;
  $perm_func = 'group_get' . $infix . 's';
  $func = 'html_select_' . $infix . '_box';
  if ($extra === null)
    {
      $perm = $perm_func ($group_id, $tracker);
      $func ($tracker, $perm, 'group');
      return;
    }
  $perm = $perm_func ($group_id, $tracker, $extra);
  $func ($tracker, $perm, '', '', $extra);
}

foreach ($trackers as $art)
  select_box_if_uses ($project, $art, $group_id, 'restriction');

print "</tr>\n";

$i++;
print '<tr class="' . utils_altrow ($i) . "\">\n<td>"
  # TRANSLATORS: this is a column row which header says "Applies when ...".
  . _("Posting comments") . "</td>\n";

foreach ($trackers as $art)
  if ($art != 'news')
    select_box_if_uses ($project, $art, $group_id, 'restriction', 2);

if ($project->Uses("news"))
# Not yet effective!
  print '<td align="center">---</td>';
print "</tr>\n";

print "</table>\n<p class='center'>"
  . form_submit(_("Update Permissions")). "</p>\n";

# Group defaults.
$title_arr = [];
add_used_tracker_titles ($title_arr, $project);

print "<p>&nbsp;</p>\n<h2>" . _("Group Default Permissions") . "</h2>\n";
member_explain_roles();
print html_build_list_table_top ($title_arr);

print "<tr>\n";

foreach ($trackers as $art)
  select_box_if_uses ($project, $art, $group_id, 'permission');

print "</tr>\n</table>\n<p class='center'>"
  . form_submit (_("Update Permissions")) . "</p>\n";

# Get squad list.
$result = db_execute ("
  SELECT
    user.user_name, user.realname, user.user_id,
    user_group.admin_flags, user_group.privacy_flags,
    user_group.bugs_flags, user_group.cookbook_flags,
    user_group.forum_flags, user_group.task_flags,
    user_group.patch_flags, user_group.news_flags,
    user_group.support_flags
  FROM user JOIN user_group ON user.user_id = user_group.user_id
  WHERE user_group.group_id = ? AND user_group.admin_flags = 'SQD'
  ORDER BY user.user_name",
  array($group_id)
);

print "<p>&nbsp;</p>\n<h2>" . _("Permissions per squad") . "</h2>\n";

if (!$result || db_numrows($result) < 1)
  print '<p class="warn">' . _("No Squads Found") . "</p>\n";
else
  {
    $title_arr = [_("Squad"), _("General Permissions")];
    add_used_tracker_titles ($title_arr, $project);

    print '<p>'
      . _("Squad members will automatically obtain their squad permissions.")
      . "</p>\n";
    print html_build_list_table_top ($title_arr);

    $reprinttitle = 0;
    $i = 0;
    while ($row = db_fetch_array($result))
      {
        $i++;
        $reprinttitle++;
        if ($reprinttitle == 9)
          {
            print html_build_list_table_top($title_arr, 0, 0);
            $reprinttitle = 0;
          }
        $row_uname = $row['user_name'];
        $row_uid = $row['user_id'];
        print "<tr class=\"" . utils_altrow ($i)
          . "\">\n<td align=\"center\" id=\"$row_uname\">"
          . utils_user_link ($row_uname, $row['realname']) . "</td>\n";
        print "<td class='smaller'>\n";
        print form_checkbox (
                "privacy_user_$row_uid", $row['privacy_flags'] == '1'
              );
        print "&nbsp;<label for=\"privacy_user_$row_uid\"> "
          . _("Private Items") . "</label>\n</td>\n";

       foreach ($trackers as $art)
         if (tracker_uses ($project, $art))
           html_select_permission_box ($art, $row);
       print "</tr>\n";
     }

    print "</table>\n<p class='center'>"
      . form_submit (_("Update Permissions")) . "</p>\n";
  }

$result = db_execute("
  SELECT
    user.user_name, user.realname, user.user_id,
    user_group.admin_flags, user_group.onduty, user_group.privacy_flags,
    user_group.bugs_flags, user_group.cookbook_flags, user_group.forum_flags,
    user_group.task_flags, user_group.patch_flags, user_group.news_flags,
    user_group.support_flags
  FROM user JOIN user_group ON user.user_id = user_group.user_id
  WHERE
    user_group.group_id = ?
    AND user_group.admin_flags <> 'P' AND user_group.admin_flags <> 'SQD'
  ORDER BY user.user_name",
  array($group_id)
);

print "<p>&nbsp;</p>\n<h2>" . _("Permissions per member") . "</h2>\n";

if (!$result || db_numrows($result) < 1)
  {
    # No point in changing permissions of an orphaned project.
    print '<p class="warn">' . _("No Members Found") . "</p>\n";
    finish_page ();
  }

$title_arr = [_("Member"), _("General Permissions"), _("On Duty")];
add_used_tracker_titles ($title_arr, $project);

print '<p class="warn">';
print _("Projects Admins are always allowed to read private items.");
print "</p>\n";

print html_build_list_table_top ($title_arr);

$reprinttitle = 0;
$i = 0;

while ($row = db_fetch_array ($result))
  {
    $i++;
    $reprinttitle++;
    $row_uid = $row['user_id'];
    if ($reprinttitle == 9)
      {
        print html_build_list_table_top($title_arr, 0, 0);
        $reprinttitle = 0;
      }
    print " <tr class=\"" . utils_altrow($i) . "'\">\n"
      . "<td align='center' id=\"{$row['user_name']}\">"
      . utils_user_link ($row['user_name'], $row['realname']) . "</td>\n";
    print '<td class="smaller">';
    if ($row_uid == user_getid())
      print '<em>' . _("You are Admin") . '</em>';
    else
      {
        print
          form_checkbox (
            "admin_user_$row_uid", $row['admin_flags'] == 'A', ['value' => 'A']
          )
          . "&nbsp;<label for=\"admin_user_$row_uid\">"
          . _("Admin") . "</label>\n";
      }
    if ($row['admin_flags'] != 'A')
     {
       print "<br />\n"
         . form_checkbox (
             "privacy_user_$row_uid", $row['privacy_flags'] == '1'
           )
         . "&nbsp;<label for=\"privacy_user_$row_uid\">"
         . _("Private Items") . '</label>';
      }
    else
      print form_input("hidden", "privacy_user_$row_uid", 1);
    print "</td>\n";
    print '<td align="center">';
    print form_checkbox (
            "onduty_user_$row_uid", $row['onduty'] == '1',
            ['title' => _("On Duty")]
          );
    print "</td>\n";
    foreach ($trackers as $art)
      if (tracker_uses ($project, $art))
        html_select_permission_box($art, $row);
    print "</tr>\n";
  } # while ($row = db_fetch_array($result))

print "</table>\n" . form_footer (_("Update Permissions"));

finish_page ();
?>
