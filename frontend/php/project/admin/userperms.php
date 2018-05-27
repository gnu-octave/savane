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

extract(sane_import('post', array('update')));
$project = project_get_object($group_id);

if ($update)
  {
  # Update members permissions.
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
        $is_squad = false;
        $name = user_getname($row_dev['user_id']);

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

        $bugs_flags="bugs_user_{$row_dev['user_id']}";
        $task_flags="task_user_{$row_dev['user_id']}";
        $patch_flags="patch_user_{$row_dev['user_id']}";
        $support_flags="support_user_{$row_dev['user_id']}";
        $cookbook_flags="cookbook_user_{$row_dev['user_id']}";
        $news_flags="news_user_{$row_dev['user_id']}";

        $admin_flags="admin_user_{$row_dev['user_id']}";
        $privacy_flags="privacy_user_{$row_dev['user_id']}";
        $onduty="onduty_user_{$row_dev['user_id']}";

        $permissions = sane_import('post', array(
                                                 $bugs_flags,
                                                 $task_flags,
                                                 $patch_flags,
                                                 $support_flags,
                                                 $cookbook_flags,
                                                 $news_flags,
                                                 $admin_flags,
                                                 $onduty,
                                                 $privacy_flags,));

        # Admin are not allowed to turn off their own admin flag.
        # It is too dangerous -- set it back to 'A'.
        if (user_getid() == $row_dev['user_id'])
          $permissions[$admin_flags] = 'A';
        # Squads flag cannot be changed, squads should not be turned into normal
        # users.
        if ($row_dev['admin_flags'] == 'SQD')
          {
            $permissions[$admin_flags] = 'SQD';
            $is_squad = true;
          }
        if ($permissions[$admin_flags] == null)
          $permissions[$admin_flags] = '';

        # If someone is made admin, he got automatically the right to read
        # private items.
        if ($permissions[$admin_flags] == "A")
          $permissions[$privacy_flags] = '1';

        if ($is_squad)
          {
            # If it is a squad, save every setting even if useless, it cost
            # nothing.
            $squad_id = $row_dev['user_id'];
            $squad_permissions[$squad_id.'bugs'] = $permissions[$bugs_flags];
            $squad_permissions[$squad_id.'task'] = $permissions[$task_flags];
            $squad_permissions[$squad_id.'patch'] = $permissions[$patch_flags];
            $squad_permissions[$squad_id.'support'] = $permissions[$support_flags];
            $squad_permissions[$squad_id.'cookbook'] = $permissions[$cookbook_flags];
            $squad_permissions[$squad_id.'news'] = $permissions[$news_flags];
            $squad_permissions[$squad_id.'privacy'] = $permissions[$privacy_flags];
          }
        else
          {
            # If it is not a squad, we then have to check if the user is
            # member of any squad, and if he is, we have to check which
            # setting must be kept (see _compare_perms comments).
            $result_user_squads = db_execute("SELECT squad_id FROM user_squad "
                                             ."WHERE user_id=? AND group_id=?",
                                             array($row_dev['user_id'], $group_id));
            if (db_numrows($result_user_squads))
              {
                while ($thissquad = db_fetch_array($result_user_squads))
                  {
                    $GLOBALS['did_squad_override'] = false;
                    $out[$bugs_flags] =
                      _compare_perms($squad_permissions[$thissquad['squad_id']
                                                        .'bugs'],
                                     $permissions[$bugs_flags]);
                    $out[$task_flags] =
                      _compare_perms($squad_permissions[$thissquad['squad_id']
                                                        .'task'],
                                     $permissions[$task_flags]);

                    $out[$patch_flags] =
                      _compare_perms($squad_permissions[$thissquad['squad_id']
                                                        .'patch'],
                                     $permissions[$patch_flags]);
                    $out[$support_flags] =
                      _compare_perms($squad_permissions[$thissquad['squad_id']
                                                        .'support'],
                                     $permissions[$support_flags]);
                    $out[$cookbook_flags] =
                      _compare_perms($squad_permissions[$thissquad['squad_id']
                                                        .'cookbook'],
                                     $permissions[$cookbook_flags]);
                    $out[$news_flags] =
                      _compare_perms($squad_permissions[$thissquad['squad_id']
                                                        .'news'],
                                     $permissions[$news_flags]);

                    if ($squad_permissions[$thissquad['squad_id'].'privacy']
                         > $permissions[$privacy_flags])
                      {
                        $GLOBALS['did_squad_override'] = true;
                        $permissions[$privacy_flags] =
                          $squad_permissions[$thissquad['squad_id'].'privacy'];
                      }
                    # Record any squad override for later generated feedback.
                    if ($GLOBALS['did_squad_override'])
                      $feedback_squad_override = sprintf(
# TRANSLATORS: the argument is user's name.
_("Personal permissions of %s were overridden by squad permissions")."\n", $name);
                  }
              }
          }

        $fields_values = array(
          'admin_flags' => $permissions[$admin_flags],
          'privacy_flags' => $permissions[$privacy_flags],
          'onduty' => $permissions[$onduty],
          'cookbook_flags' => $permissions[$cookbook_flags],
        );

        if ($project->Uses("bugs"))
          $fields_values['bugs_flags'] = $permissions[$bugs_flags];
        if ($project->Uses("news"))
          $fields_values['news_flags'] = $permissions[$news_flags];
        if ($project->Uses("task"))
          $fields_values['task_flags'] = $permissions[$task_flags];
        if ($project->Uses("patch"))
          $fields_values['patch_flags'] = $permissions[$patch_flags];
        if ($project->Uses("support"))
          $fields_values['support_flags'] = $permissions[$support_flags];

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
      }

    if ($feedback_able)
      fb($feedback_able);
    if ($feedback_squad_override)
      fb($feedback_squad_override);
    if ($feedback_unable)
      fb($feedback_unable);

    # Update group default permissions.
    extract(sane_import('post', array(
      'bugs_user_',
      'task_user_',
      'patch_user_',
      'support_user_',
      'cookbook_user_',
      'news_user_',
    )));

    # If the group entry do not exists, create it.
    if (!db_numrows(db_execute("SELECT groups_default_permissions_id "
                               ."FROM groups_default_permissions WHERE group_id=?",
                               array($group_id))))
      db_execute("INSERT INTO groups_default_permissions (group_id) VALUES (?)",
                 array($group_id));

    # Update the table.
    $fields_values = array('cookbook_flags' => $cookbook_user_);

    if ($project->Uses("bugs"))
      $fields_values['bugs_flags'] = $bugs_user_;
    if ($project->Uses("news"))
      $fields_values['news_flags'] = $news_user_;
    if ($project->Uses("task"))
      $fields_values['task_flags'] = $task_user_;
    if ($project->Uses("patch"))
      $fields_values['patch_flags'] = $patch_user_;
    if ($project->Uses("support"))
      $fields_values['support_flags'] = $support_user_;

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
    extract(sane_import('post', array(
      'bugs_restrict_event1',     'bugs_restrict_event2',
      'task_restrict_event1',     'task_restrict_event2',
      'support_restrict_event1',  'support_restrict_event2',
      'patch_restrict_event1',    'patch_restrict_event2',
      'cookbook_restrict_event1', 'cookbook_restrict_event2',
      'news_restrict_event1')));
    $bugs_flags = ($bugs_restrict_event2)*100 + $bugs_restrict_event1;
    if (!$bugs_flags)
      $bugs_flags = 'NULL';

    $task_flags = ($task_restrict_event2)*100 + $task_restrict_event1;
    if (!$task_flags)
      $task_flags = 'NULL';

    $support_flags = ($support_restrict_event2)*100 + $support_restrict_event1;
    if (!$support_flags)
      $support_flags = 'NULL';

    $patch_flags = ($patch_restrict_event2)*100 + $patch_restrict_event1;
    if (!$patch_flags)
      $patch_flags = 'NULL';

    $cookbook_flags = ($cookbook_restrict_event2)*100 + $cookbook_restrict_event1;
    if (!$cookbook_flags)
      $cookbook_flags = 'NULL';

    $news_flags = $news_restrict_event1;
    if (!$news_flags)
      $news_flags = 'NULL';

    $result = db_autoexecute('groups_default_permissions',
                             array('bugs_rflags' => $bugs_flags,
                                   'news_rflags' => $news_flags,
                                   'cookbook_rflags' => $cookbook_flags,
                                   'task_rflags' => $task_flags,
                                   'patch_rflags' => $patch_flags,
                                   'support_rflags' => $support_flags,
                                  ), DB_AUTOQUERY_UPDATE,
                             "group_id=?", array($group_id));

    if ($result && db_affected_rows($result))
      {
        group_add_history('Changed Posting Restrictions','',$group_id);
        fb(_("Posting restrictions updated."));
      }
    elseif (!$result)
      fb(_("Unable to change posting restrictions."), 1);
  }

# Start HTML.
site_project_header(array('title'=>_("Set Permissions"),'group'=>$group_id,
                          'context'=>'ahome'));

print form_header($_SERVER['PHP_SELF']).
form_input("hidden", "group", $group);

# Posting restrictions.
# Exists also in trackers config (missing for news).

$i = 0;
$title_arr=array();
$title_arr[]=
# TRANSLATORS: this is the header for a column with two rows,
# "Posting new items" and "Posting comments".
_("Applies when ...");
if ($project->Uses("support"))
  $title_arr[]=_("Support Tracker");
if ($project->Uses("bugs"))
  $title_arr[]=_("Bug Tracker");
if ($project->Uses("task"))
  $title_arr[]=_("Task Tracker");
if ($project->Uses("patch"))
  $title_arr[]=_("Patch Tracker");
$title_arr[]=_("Cookbook Manager");
if ($project->Uses("news"))
  $title_arr[]=_("News Manager");

print '<h2>'._("Group trackers posting restrictions").'</h2>
<p>';
print _("Here you can set the minimal authentication level required in order to
post on the trackers.");
print '</p>
';

print html_build_list_table_top ($title_arr);

$i++;
print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td>'
# TRANSLATORS: this is a column row which header says "Applies when ...".
._("Posting new items").'</td>';
if ($project->Uses("support"))
  html_select_restriction_box("support", group_getrestrictions($group_id, "support"));
if ($project->Uses("bugs"))
  html_select_restriction_box("bugs", group_getrestrictions($group_id, "bugs"));
if ($project->Uses("task"))
  html_select_restriction_box("task", group_getrestrictions($group_id, "task"));
if ($project->Uses("patch"))
  html_select_restriction_box("patch", group_getrestrictions($group_id, "patch"));
html_select_restriction_box("cookbook", group_getrestrictions($group_id, "cookbook"));
if ($project->Uses("news"))
  html_select_restriction_box("news", group_getrestrictions($group_id, "news"));

print '  </tr>';

$i++;
print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td>'
# TRANSLATORS: this is a column row which header says "Applies when ...".
._("Posting comments").'</td>';
if ($project->Uses("support"))
  html_select_restriction_box("support",
                              group_getrestrictions($group_id, "support", 2),
                              '', '', 2);
if ($project->Uses("bugs"))
  html_select_restriction_box("bugs", group_getrestrictions($group_id,
                                                            "bugs", 2),
                              '', '', 2);
if ($project->Uses("task"))
  html_select_restriction_box("task", group_getrestrictions($group_id, "task", 2),
                              '', '', 2);
if ($project->Uses("patch"))
  html_select_restriction_box("patch", group_getrestrictions($group_id,
                                                             "patch", 2),
                              '', '', 2);
html_select_restriction_box("cookbook", group_getrestrictions($group_id,
                                                              "cookbook", 2),
                            '', '', 2);
if ($project->Uses("news"))
# Not yet effective!
  print '<td align="center">---</td>';
print '  </tr>
';

print '
</table>
<p class="center">'.form_submit(_("Update Permissions")).'</p>
';

# Group defaults.
$title_arr=array();
if ($project->Uses("support"))
  $title_arr[]=_("Support Tracker");
if ($project->Uses("bugs"))
  $title_arr[]=_("Bug Tracker");
if ($project->Uses("task"))
  $title_arr[]=_("Task Tracker");
if ($project->Uses("patch"))
  $title_arr[]=_("Patch Tracker");
$title_arr[]=_("Cookbook Manager");
if ($project->Uses("news"))
  $title_arr[]=_("News Manager");

print '<p>&nbsp;</p>
<h2>'._("Group Default Permissions").'</h2>
';
member_explain_roles();
print html_build_list_table_top ($title_arr);

if ($project->Uses("support"))
  html_select_permission_box("support", group_getpermissions($group_id,
                                                             "support"),
                             "group");
if ($project->Uses("bugs"))
  html_select_permission_box("bugs", group_getpermissions($group_id, "bugs"),
                             "group");
if ($project->Uses("task"))
  html_select_permission_box("task", group_getpermissions($group_id, "task"),
                             "group");
if ($project->Uses("patch"))
  html_select_permission_box("patch", group_getpermissions($group_id, "patch"),
                             "group");
html_select_permission_box("cookbook", group_getpermissions($group_id,
                                                            "cookbook"),
                           "group");
if ($project->Uses("news"))
  html_select_permission_box("news", group_getpermissions($group_id, "news"),
                             "group");
print '  </tr>
</table>
<p class="center">'.form_submit(_("Update Permissions")).'</p>
';

# Get squad list.
$result = db_execute("SELECT user.user_name AS user_name,"
. "user.realname AS realname, "
. "user.user_id AS user_id, "
. "user_group.admin_flags, "
. "user_group.privacy_flags, "
. "user_group.bugs_flags, "
. "user_group.cookbook_flags, "
. "user_group.forum_flags, "
. "user_group.task_flags, "
. "user_group.patch_flags, "
. "user_group.news_flags, "
. "user_group.support_flags "
. "FROM user JOIN user_group ON user.user_id=user_group.user_id "
. "WHERE user_group.group_id = ? AND user_group.admin_flags='SQD' "
. "ORDER BY user.user_name", array($group_id));

print '<p>&nbsp;</p>
<h2>'._("Permissions per squad").'</h2>
';

if (!$result || db_numrows($result) < 1)
  print '<p class="warn">'._("No Squads Found").'</p>
';
else
  {
    $title_arr=array();
    $title_arr[]=_("Squad");
    $title_arr[]=_("General Rights");
    if ($project->Uses("support"))
      $title_arr[]=_("Support Tracker");
    if ($project->Uses("bugs"))
      $title_arr[]=_("Bug Tracker");
    if ($project->Uses("task"))
      $title_arr[]=_("Task Tracker");
    if ($project->Uses("patch"))
      $title_arr[]=_("Patch Tracker");
    $title_arr[]=_("Cookbook Manager");
    if ($project->Uses("news"))
      $title_arr[]=_("News Manager");

    print '<p>
'._("Squad Members will automatically obtain, at least, the Squad permissions.")
.'</p>
';
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
        print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td align="center" id="'.$row['user_name'].'">'
.utils_user_link($row['user_name'], $row['realname']).'</td>';
        print '
    <td class="smaller">';

        print '
      <input type="checkbox" name="privacy_user_'.$row['user_id']
.'" value="1" '.(($row['privacy_flags']=='1')?'checked="checked"':'')
.' />&nbsp;'._("Private Items");

        print '
    </td>
';
       if ($project->Uses("support"))
         html_select_permission_box("support", $row);
       if ($project->Uses("bugs"))
         html_select_permission_box("bugs", $row);
       if ($project->Uses("task"))
         html_select_permission_box("task", $row);
       if ($project->Uses("patch"))
         html_select_permission_box("patch", $row);
       html_select_permission_box("cookbook", $row);
       if ($project->Uses("news"))
         html_select_permission_box("news", $row);
       print '  </tr>
';
     }

    print '
</table>
<p class="center">'.form_submit(_("Update Permissions")).'</p>
';
  }

# Per member.

$result = db_execute("SELECT user.user_name AS user_name,"
. "user.realname AS realname, "
. "user.user_id AS user_id, "
. "user_group.admin_flags, "
. "user_group.onduty, "
. "user_group.privacy_flags, "
. "user_group.bugs_flags, "
. "user_group.cookbook_flags, "
. "user_group.forum_flags, "
. "user_group.task_flags, "
. "user_group.patch_flags, "
. "user_group.news_flags, "
. "user_group.support_flags "
. "FROM user JOIN user_group ON user.user_id=user_group.user_id "
. "WHERE user_group.group_id = ? AND user_group.admin_flags<>'P' "
. "AND user_group.admin_flags<>'SQD' "
. "ORDER BY user.user_name", array($group_id));

print '<p>&nbsp;</p>
<h2>'._("Permissions per member").'</h2>
';

if (!$result || db_numrows($result) < 1)
  # Unusual case! No point in changing permissions of an orphaned project.
  print '<p class="warn">'._("No Members Found").'</p>
';
else
  {
    $title_arr=array();
    $title_arr[]=_("Member");
    $title_arr[]=_("General Rights");
    $title_arr[]=_("On Duty");
    if ($project->Uses("support"))
      $title_arr[]=_("Support Tracker");
    if ($project->Uses("bugs"))
      $title_arr[]=_("Bug Tracker");
    if ($project->Uses("task"))
      $title_arr[]=_("Task Tracker");
    if ($project->Uses("patch"))
      $title_arr[]=_("Patch Tracker");
    $title_arr[]=_("Cookbook Manager");
    if ($project->Uses("news"))
      $title_arr[]=_("News Manager");
    print '<p class="warn">';
    print _("Projects Admins are always allowed to read private items.");
    print '</p>
';
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
        print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td align="center" id="'.$row['user_name'].'">'
.utils_user_link($row['user_name'], $row['realname']).'</td>
';
        print '
    <td class="smaller">';
        if ($row['user_id'] == user_getid())
          print '<em>'._("You are Admin").'</em>';
        else
          {
            $extra = ($row['admin_flags'] == 'A' ) ?'checked="checked"':'';
            print form_input("checkbox", "admin_user_"
                  .$row['user_id'], "A", $extra).'&nbsp;'._("Admin");
          }
        if ($row['admin_flags'] != 'A')
         {
           $extra = ($row['privacy_flags'] == '1' ) ?'checked="checked"':'';
           print '<br />'.form_input("checkbox", "privacy_user_"
                 .$row['user_id'], "1", $extra).'&nbsp;'._("Private Items");
          }
        else
          print form_input("hidden", 'privacy_user_'.$row['user_id'], 1);
        print '
    </td>
';
        print '<td align="center">';
        $extra = ($row['onduty'] == '1' ) ? 'checked="checked"' : '';
        print form_input("checkbox", "onduty_user_".$row['user_id'], 1, $extra);
        print '</td>
';
        if ($project->Uses("support"))
          html_select_permission_box("support", $row);
        if ($project->Uses("bugs"))
          html_select_permission_box("bugs", $row);
        if ($project->Uses("task"))
          html_select_permission_box("task", $row);
        if ($project->Uses("patch"))
          html_select_permission_box("patch", $row);
        html_select_permission_box("cookbook", $row);
        if ($project->Uses("news"))
          html_select_permission_box("news", $row);
        print '  </tr>
';
      }
    print '
</table>'.form_footer(_("Update Permissions"));
  }

site_project_footer(array());
?>
