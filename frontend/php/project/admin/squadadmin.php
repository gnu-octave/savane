<?php
# Manage squads.
#
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2022 Ineiev
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


## NOTE: for now, squads are group specific. However, as squads reuse the
# users code, we could easily imagine to share squads among different projects

require_once('../../include/init.php');
require_once('../../include/account.php');

register_globals_off();
extract (sane_import ('request', ['digits' => 'squad_id']));
extract (sane_import ('post',
  [
    'true' =>
      [
        'update', 'update_general', 'update_delete_step1',
        'update_delete_step2', 'deletionconfirmed', 'add_to_squad',
        'remove_from_squad',
      ],
    'hash' => 'form_id',
    'array' => [['user_ids', ['digits', 'digits']]],
    'digits' => ['squad_id_to_delete'],
     # form_realname is sanitized further.
    'pass' => 'form_realname',
    'name' => 'form_loginname',
  ]
));

session_require (['group' => $group_id, 'admin_flags' => 'A']);

if (!$group_id)
  exit_no_group();

function finish_page ()
{
  site_project_footer ([]);
  exit (0);
}

if (account_realname_valid ($form_realname))
  $form_realname = account_sanitize_realname ($form_realname);
else
  $form_realname = '';

function update_squad_name ($new_name, $squad_id, $group_id, &$current_name)
{
  if (empty ($new_name))
    {
      fb (_("You must supply a non-empty real name."), 1);
      return;
    }
  $result = db_execute (
    "UPDATE user SET realname = ? WHERE user_id = ?",
    [$new_name, $squad_id]
  );
  if (db_affected_rows ($result) <= 0)
    return;

  fb (_("Squad name updated"));
  group_add_history ('Squad name update', $current_name, $group_id);
  $current_name = $new_name;
}

function confirm_squad_deletion ($group_id, $squad_id, $realname, $squad_name)
{
  site_project_header (
    ['title' => _("Manage Squads"), 'group' => $group_id, 'context' => 'ahome']
  );
  print '<p>' . _('This action cannot be undone.') . "</p>\n";

  print form_header ($_SERVER["PHP_SELF"]);
  print form_input( "hidden", "group_id", $group_id);
  # Do not pass the squad id as $squad_id, because if $squad_id is defined
  # the software will try show the squad details, even if it has been
  # removed, while we want the list of existing squads.
  print form_input( "hidden", "squad_id_to_delete", $squad_id);
  print '<p><span class="preinput">'
    . _("Do you really want to delete this squad account?")
    . "</span></p>\n";
  print "<p>$realname &lt;$squad_name&gt;&nbsp;&nbsp;";
  print form_checkbox ("deletionconfirmed", 0, ['value' => "yes"])
    . ' ' . _("Yes, I really do") . "</p>\n";
  print form_submit (_("Update"), "update_delete_step2");
  finish_page ();
}

if ($squad_id)
  {
    # A squad passed? Allow to add and remove member, to
    # change the squad name or to delete it.
    $sql = "
      SELECT user.user_name, user.realname, user.user_id
      FROM user, user_group
      WHERE
        user.user_id = ? AND user_group.group_id = ?
        AND user_group.user_id = user.user_id
        AND user_group.admin_flags = 'SQD'
      ORDER BY user.user_name";
    $result = db_execute($sql, array($squad_id, $group_id));
    if (!db_numrows($result))
      exit_error(_("Squad not found"));

    $realname = db_result ($result, 0, 'realname');
    $squad_name = db_result ($result, 0, 'user_name');

    if ($update_general)
      update_squad_name ($form_realname, $squad_id, $group_id, $realname);

    if ($update_delete_step1)
      confirm_squad_deletion ($group_id, $squad_id, $realname, $squad_name);

    if ($add_to_squad && !empty ($user_ids))
      foreach ($user_ids as $user)
        {
          $ok = member_squad_add ($user, $squad_id, $group_id);
          if ($ok)
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s added to the squad.");
          else
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s is already part of the squad.");
          fb (sprintf ($str, user_getname ($user)), !$ok);
        }

    if ($remove_from_squad && !empty ($user_ids))
      foreach ($user_ids as $user)
        {
          $ok = member_squad_remove($user, $squad_id, $group_id);
          if ($ok)
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s removed from the squad.");
          else
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s is not part of the squad.");
          fb (sprintf ($str, user_getname($user)), !$ok);
        }

    site_project_header(array('title' => _("Manage Squads"),
                              'group' => $group_id,
                              'context' => 'ahome'));

    print form_header($_SERVER["PHP_SELF"]);
    print form_input("hidden", "group_id", $group_id);
    print form_input("hidden", "squad_id", $squad_id);
    print '<p><span class="preinput"><label for="form_realname">'
          . _("Real Name:") . "</label></span><br />\n&nbsp;&nbsp;";
    print form_input("text", "form_realname", $realname)
                     . " &lt;$squad_name&gt;</p>\n";
    print form_submit(_("Update"), "update_general") . ' '
          . form_submit(_("Delete Squad"), "update_delete_step1") . "</form>\n";

    print '<h2>' . _("Removing members") . "</h2>\n";

    $result_delusers = db_execute ("
      SELECT user.user_id, user.user_name, user.realname
      FROM user, user_squad
      WHERE user.user_id = user_squad.user_id AND user_squad.squad_id = ?
      ORDER BY user.user_name",
      [$squad_id]
   );

    print "<p>"
. _("To remove members from the squad, select their names and push the button
below.") . "</p>\n";
    print form_header($_SERVER["PHP_SELF"]);
    print form_input("hidden", "group_id", $group_id);
    print form_input("hidden", "squad_id", $squad_id);
    print '&nbsp;&nbsp;<select title="' . _("Users")
          . '" name="user_ids[]" size="10" multiple="multiple">';
    $exists = false;
    $already_in_squad = array();
    while ($thisuser = db_fetch_array($result_delusers))
      {
        print '<option value="' . $thisuser['user_id'] . '">'
          . $thisuser['realname']
          . ' &lt;' . $thisuser['user_name'] . "&gt;</option>\n";
        $already_in_squad[$thisuser['user_id']] = true;
        $exists = true;
      }

    if (!$exists)
      print '<option>' . _("None found") . "</option>\n";
    print '</select>';
    print "<br />\n" . form_submit(_("Remove Members"), "remove_from_squad")
          . "</form>\n";

    print '<h2>' . _("Adding members") . "</h2>\n";

    $result_addusers =  db_execute ("
      SELECT user.user_id, user.user_name, user.realname
      FROM user, user_group
      WHERE
        user.user_id=user_group.user_id AND user_group.group_id = ?
        AND admin_flags <> 'P' AND admin_flags <> 'SQD'
      ORDER BY user.user_name",
      array($group_id)
    );

    print "<p>"
. _("To add members to the squad, select their name and push the button below.")
. "</p>\n";
    print form_header($_SERVER["PHP_SELF"]);
    print form_input("hidden", "group_id", $group_id);
    print form_input("hidden", "squad_id", $squad_id);
    print '&nbsp;&nbsp;<select title="' . ("Users")
      . '" name="user_ids[]" size="10" multiple="multiple">';
    $exists = false;
    while ($thisuser = db_fetch_array($result_addusers))
      {
        # Ignore if previously found as member.
          if (array_key_exists($thisuser['user_id'], $already_in_squad))
            continue;

        print "<option value=\"{$thisuser['user_id']}\">{$thisuser['realname']}"
          . " &lt;{$thisuser['user_name']}&gt;</option>\n";
        $exists = true;
      }

    if (!$exists)
      print '<option>' . _("None found") . "</option>\n";
    print "</select>\n";
    print "<br />\n" . form_submit(_("Add Members"), "add_to_squad")
          . "</form>\n";
    print '<h2>' . _("Setting permissions") . "</h2>\n";
    print "<p><a href=\"userperms.php?group=$group#$squad_name\">"
          . _("Set Permissions") . "</a></p>\n";
    finish_page ();
  } # if ($squad_id)

# No $squad_id.  List existing squads, allow to create one.

function validate_loginname ($form_loginname, $group)
{
  if (!account_namevalid ($form_loginname))
    return false; # Feedback included by the check function.

  $res = db_execute (
    "SELECT user_id FROM user WHERE user_name LIKE ?",
     [$group . '-' . $form_loginname]
  );
  if (db_numrows ($res) > 0)
    {
      fb(_("That username already exists."), 1);
      return false;
    }

  $res = db_execute (
    "SELECT group_list_id FROM mail_group_list WHERE list_name LIKE ?",
    [$group . '-' . $form_loginname]
  );
  if (db_numrows ($res) <= 0)
    return true;
  fb (_("That username may conflict with mailing list addresses."), 1);
  return false;
}

function create_squad (&$form_loginname, &$form_realname, $group)
{
  global $form_id, $group_id, $sys_mail_replyto, $sys_mail_domain;

  $result = db_autoexecute (
    'user',
    [
      'user_name' => strtolower ($group . "-" . $form_loginname),
      'user_pw' => 'ignored', 'realname' => $form_realname,
      'email' => "{$sys_mail_replyto}@{$sys_mail_domain}",
      'add_date' => time(), 'status' => 'SQD', 'email_hide' => 1,
    ],
    DB_AUTOQUERY_INSERT
  );
  if (db_affected_rows ($result) <= 0)
    {
      fb(_("Error during squad creation"));
      return;
    }
  fb (_("Squad created"));
  $created_squad_id = db_insertid ($result);
  member_add ($created_squad_id, $group_id, 'SQD');

  # Clear variables so the form below will be empty.
  $form_id = $form_loginname = $form_realname = null;
}

if ($update && form_check ($form_id))
  {
    if (!$form_loginname)
      fb(_("You must supply a username."), 1);
    if (!$form_realname)
      fb(_("You must supply a non-empty real name."), 1);

    if (
      $form_loginname && $form_realname
      && validate_loginname ($form_loginname, $group)
    )
      create_squad ($form_loginname, $form_realname, $group);
  }

if ($update_delete_step2 && $deletionconfirmed == "yes")
  {
    $squad_id_to_delete = $squad_id_to_delete;
    $delete_result = db_execute ("
      SELECT user.user_name, user.realname, user.user_id
      FROM user, user_group
      WHERE
        user.user_id = ? AND user_group.group_id = ?
        AND user_group.user_id = user.user_id
        AND user_group.admin_flags = 'SQD'
      ORDER BY user.user_name",
      array($squad_id_to_delete, $group_id)
    );

    if (!db_numrows($delete_result))
      exit_error(_("Squad not found"));

    fb(_("Squad deleted"));
    member_remove($squad_id_to_delete, $group_id);
  }

$result = db_execute ("
  SELECT user.user_name, user.realname, user.user_id
  FROM user, user_group
  WHERE
    user.user_id = user_group.user_id AND user_group.group_id = ?
    AND user_group.admin_flags = 'SQD'
  ORDER BY user.user_name",
  array($group_id)
);
$rows = db_numrows($result);

site_project_header(array('title' => _("Manage Squads"),
                          'group' => $group_id, 'context' => 'ahome'));

print '<p>'
. _("Squads can be assigned items, share permissions. Creating squads is useful
if you want to assign some items to several members at once.") . "</p>\n";

print '<h2 id="form">' . _("Squad List") . "</h2>\n";

if ($rows < 1)
  print '<p class="warn">' . _("None found") . "</p>\n";
else
  {
    print "<ul>\n";
    while ($squad = db_fetch_array ($result))
      {
        print "<li><a href=\"?squad_id={$squad['user_id']}&amp;group_id="
              . "$group_id\">{$squad['realname']} &lt;"
              . "{$squad['user_name']}&gt;</a></li>\n";
      }
    print "</ul>\n";
  }

# Limit squad creation to the group size (yes, one can easily override this
# restriction by creating fake users, but the point is only to incitate
# to create squads only if necessary, not to really enforce something
# important).
print '<h2>' . _("Create a New Squad") . "</h2>\n";

$result = db_execute ("
  SELECT user_id FROM user_group
  WHERE group_id = ? AND admin_flags <> 'P' AND admin_flags <> 'SQD'",
  array($group_id)
);
if ($rows < db_numrows ($result))
  {
    print form_header ($_SERVER["PHP_SELF"] . '#form', $form_id);
    print form_input ("hidden", "group_id", $group_id);
    print '<p><span class="preinput"><label for="form_loginname">'
      . _ ("Squad Login Name:") . "</label></span>\n<br />&nbsp;&nbsp;";
    print "$group-" . form_input("text", "form_loginname", $form_loginname)
      . "</p>\n";
    print '<p><span class="preinput"><label for="form_realname">'
      . _("Squad Full Name:") . "</label></span>\n<br />&nbsp;&nbsp;";
    print form_input ("text", "form_realname", $form_realname) . "</p>\n";
    print form_footer ();
  }
else
  print '<p class="warn">'
    . _("You cannot have more squads than members") . "</p>\n";
finish_page ();
?>
