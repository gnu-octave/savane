<?php
# Edit user's groups, email &c.
#
# This file is part of the Savane project
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2017, 2018, 2019 Ineiev
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

require_once('../include/init.php');
require_once('../include/account.php');
require_once('../include/markup.php');
require_once('../include/trackers/data.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>no_i18n('Admin: User Info')));

extract(sane_import('request', array('user_id', 'action',
                                     'comment_max_rows', 'comment_offset')));
extract(sane_import('post', array('admin_flags', 'email', 'new_name')));

if (!isset($comment_max_rows))
  $max_rows = 50;
else
  $max_rows = intval($comment_max_rows);

if (!isset($comment_offset))
  $offset = 0;
else
  $offset = intval($comment_offset);

function list_user_contributions ($user_id, $user_name)
{
  global $offset, $max_rows;

  print "\n<h2>";
  if ($user_id != 100)
    print no_i18n ("Contributions");
  else
    print no_i18n ("Anonymous Posts");
  print "</h2>\n";

  $trackers = array ('cookbook', 'bugs', 'task', 'support', 'patch');
  $query = '';
  foreach ($trackers as $tracker)
    {
      $query .= '
SELECT CONCAT("<a href=\"/' . $tracker. '/?", bug_id, "\">New Item in ",
              "' . $tracker . ' #", bug_id, ": ", summary, "</a>") as summary,
       details as details, spamscore as spamscore, 0 as comment_id, date as date
  FROM ' . $tracker . '
  WHERE submitted_by=' . $user_id . '
UNION
SELECT CONCAT("<a href=\"/' . $tracker. '/?", bug_id, "\">Comment #",
              bug_history_id, " in ", "' . $tracker . ' #", bug_id,
              " (", field_name ,")</a>") as summary,
       old_value as details, spamscore as spamscore,
       bug_history_id as comment_id, date as date
  FROM ' . $tracker . '_history
  WHERE mod_by=' . $user_id . '
UNION';
    }
  $query .= '
SELECT CONCAT("<a href=\"/project/admin/history.php?group=",
              groups.unix_group_name,
              "\">Request for inclusion in ", groups.group_name, "</a>")
         as summary, " " as details, -1 as spamscore,
         group_history_id as comment_id, group_history.date as date
  FROM group_history,groups
  WHERE group_history.old_value = "' . $user_name . '"
        AND groups.group_id = group_history.group_id
        AND group_history.field_name = "User Requested Membership"
ORDER BY date DESC LIMIT ' . $offset . ',' . ($max_rows + 1);
  $result = db_execute ($query);
  if (!db_numrows($result))
    {
      print '<p>' . no_i18n ('No contributions found.') . "</p>\n";
      return;
    }
  html_nextprev (htmlentities ($_SERVER['PHP_SELF']) . '?user_id='
                 . urlencode ($user_id), $max_rows, db_numrows ($result),
                 'comment');
  print "<dl id=\"comment_results\">\n";
  $i = 0;
  while ($entry = db_fetch_array ($result))
    {
      if (++$i > $max_rows)
        break;
      $spam = $entry['spamscore'];
      $date = utils_format_date ($entry['date'], 'natural');
      if ($spam == 0)
        $spam = no_i18n ('Spam score') . ' ' . $spam . "; ";
      elseif ($spam > 0)
        $spam = no_i18n ('Spam score') . ' <b>' . $spam . '</b>; ';
      else
        $spam = '';
      print "  <dt><b>" . ($i + $offset) . "</b>: " . $spam
             . $date . " " . $entry['summary'] . "</dt>\n";
      if (preg_match ('/">New Item in/', $entry['summary']))
        {
          $entry['details'] = trackers_decode_value ($entry['details']);
          $entry['details'] = '<div class="tracker_comment">'
                              . markup_full ($entry['details']) . "</div>\n";
        }
      elseif (preg_match ('/ \(details\)<\/a>$/', $entry['summary']))
        {
          $entry['details'] = trackers_decode_value ($entry['details']);
          $entry['details'] = '<div class="tracker_comment">'
                              . markup_rich ($entry['details']) . "</div>\n";
        }
      elseif ($entry['spamscore'] < 0)
          $entry['details'] = markup_rich (trackers_decode_value ($entry['details']));
      else
        $entry['details'] = htmlentities ($entry['details']);
      print "    <dd>" . $entry['details'] . "</dd>\n";
    }
  print "</dl>\n";
  html_nextprev (htmlentities ($_SERVER['PHP_SELF']) . '?user_id='
                 . urlencode ($user_id), $max_rows, db_numrows ($result),
                 'comment');
}

if ($user_id == 100)
  {
    list_user_contributions ($user_id, '_');
    html_feedback_bottom($feedback);
    $HTML->footer(array());
    exit;
  }

if ($action=='remove_user_from_group')
  {
    $result = db_execute("DELETE FROM user_group "
                         ."WHERE user_id=? AND group_id=?",
                         array($user_id, $group_id));
    if (!$result || db_affected_rows($result) < 1)
      fb(no_i18n('Error Removing User:').' '.db_error(), 1);
    else
      fb (no_i18n('Successfully removed user'));
  }
elseif ($action=='update_user_group')
  {
    $result = db_execute("UPDATE user_group SET admin_flags=? "
                         . "WHERE user_id=? AND group_id=?",
                         array($admin_flags, $user_id, $group_id));
    if (!$result || db_affected_rows($result) < 1)
      fb(no_i18n('Error Updating User Group:').' '.db_error(), 1);
    else
      fb(no_i18n('Successfully updated user group'));
  }
elseif ($action=='update_user')
  {
    $result=db_execute("UPDATE user SET email=? WHERE user_id=?",
                       array(preg_replace ('/\s/', "", $email), $user_id));
    if (!$result || db_affected_rows($result) < 1)
      fb(no_i18n('Error Updating User:').$result.' '.db_error(), 1);
    else
      fb(no_i18n('Successfully updated user'));
  }
elseif ($action=='add_user_to_group')
  {
    $result=db_execute("INSERT INTO user_group (user_id, group_id) "
                           ."VALUES (?, ?)",
                       array($user_id, $group_id));
    if (!$result || db_affected_rows($result) < 1)
      fb(no_i18n('Error Adding User to Group:').' '.db_error(), 1);
    else
      fb(no_i18n('Successfully added user to group'));
  }
elseif ($action == 'rename')
  {
    if (!account_namevalid ($new_name))
      {
        fb(no_i18n(sprintf('New account name <%s> is invalid', $new_name), 1));
      }
    else
      {
        $res = user_rename ($user_id, $new_name);
        if ('' == $res)
          {
            fb(no_i18n('Successfully renamed account to '). $new_name);
          }
        else
          {
            fb(no_i18n('Error renaming account to <'. $new_name . '>:' . $res),
                       1);
          }
      }
  }

# Get user info.
$res_user = db_execute("SELECT * FROM user WHERE user_id=?", array($user_id));
$row_user = db_fetch_array($res_user);

print '
<p>' . no_i18n('Savannah User Group Edit for user:') . ' <strong>'
. $user_id .  ' ' . user_getname($user_id) . "</strong></p>\n";
if ($row_user['status'] == 'SQD')
  print '<p>' . no_i18n('Account info: this is a squad.') . '</p>';
else
  {
   print
'<p>
' . no_i18n('Account Info:') . '
<form method="post" action="' . htmlentities ($_SERVER['PHP_SELF']) . '">
<input type="hidden" name="action" value="update_user">
<input type="hidden" name="user_id" value="' . htmlspecialchars($user_id) . '">
</p>
<p>Email:
<input type="text" title="' . no_i18n("Email") . '" name="email" value="'
. htmlspecialchars($row_user['email']) . '" size="25" maxlength="55">
</p>
<p>
<input type="submit" name="Update_Unix" value="' . no_i18n('Update') . '">
</p>
</form>
<form method="post" action="' . htmlentities ($_SERVER['PHP_SELF']) . '">
<input type="hidden" name="action" value="rename">
<input type="hidden" name="user_id" value="' . htmlspecialchars($user_id) . '">
<p>Account name:
<input type="text" title="' . no_i18n("New name") . '" name="new_name" value="'
. htmlspecialchars ($row_user['user_name']) . '" size="25" maxlength="55">
</p>
<p>
<input type="submit" name="' . no_i18n('Update_Name') . '" value="'
 . no_i18n('Rename') . '">
</p>
</form>
<hr />';
  } #  $row_user['status'] != 'SQD'
print '<p><a href="/siteadmin/userlist.php?action=delete&user_id=' . $user_id
      . '">'. no_i18n ('[Delete User]') . "</a></p>\n";
print '
<h2>' . no_i18n('Current Groups') . "</h2>\n";

# Iterate and show groups this user is in.
$res_cat = db_execute("SELECT groups.group_name AS group_name, "
           . "groups.group_id AS group_id, "
           . "user_group.admin_flags AS admin_flags FROM "
           . "groups,user_group WHERE user_group.user_id=? AND "
           . "groups.group_id=user_group.group_id", array($user_id));

while ($row_cat = db_fetch_array($res_cat))
  {
    if ($row_user['status'] == 'SQD')
      {
        print "<br />\n"
         . '<a href="/project/admin/squadadmin.php?squad_id='
         . htmlspecialchars($user_id) . '&group_id=' . $row_cat['group_id']
         . '">' . group_getname($row_cat['group_id']) . "</a>\n";
        continue;
      }
    print ("<br /><hr /><strong>"
         . group_getname($row_cat['group_id']) . "</strong> "
         . "<a href=\"usergroup.php?user_id="
         . htmlspecialchars($user_id)."&action=remove_user_from_group&group_id="
         . htmlspecialchars($row_cat['group_id']) . "\">"
         . "[" . no_i18n('Remove User from Group') . "]</a>");
    print '
<form action="' . htmlentities ($_SERVER['PHP_SELF']) . '" method="post">
<input type="hidden" name="action" value="update_user_group">
<input name="user_id" type="hidden" value="' . htmlspecialchars($user_id) . '">
<input name="group_id" type="hidden" value="'
       .htmlspecialchars($row_cat['group_id']).'">
<br /><label for="admin_flags">
'.no_i18n('Admin Flags:').'</label>
<br />
<input type="text" name="admin_flags" id="admin_flags" value="'
. htmlspecialchars($row_cat['admin_flags'], ENT_QUOTES) . '">
<br />
<input type="submit" name="Update_Group" value="' . no_i18n('Update') . '" />
</form>
';
  }
if ($row_user['status'] != 'SQD')
  {
# Show a form so a user can be added to a group.
    print '
<hr />
<p>
<form action="' . htmlentities ($_SERVER['PHP_SELF']) . '" method="post">
<input type="hidden" name="action" value="add_user_to_group">
<input name="user_id" type="hidden" value="' . htmlspecialchars($user_id) . '">
<p><label for="group_id">
' . no_i18n('Add User to Group (group_id):') . '</label>
<br />
<input type="text" name="group_id" id="group_id" length="4" maxlength="5" />
</p>
<p>
<input type="submit" name="Submit" value="' . no_i18n('Submit') . '" />
</form>

<p><a href="user_changepw.php?user_id='
    . htmlspecialchars($user_id) . '">[' . no_i18n('Change User\'s Password')
    . "]</a>\n</p>\n";
    list_user_contributions ($user_id, $row_user['user_name']);
  }

html_feedback_bottom($feedback);
$HTML->footer(array());
?>
