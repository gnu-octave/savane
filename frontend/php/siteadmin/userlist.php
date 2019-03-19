<?php
# List users.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
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

site_admin_header(array('title'=>no_i18n("User List"),'context'=>'admuser'));

extract(sane_import('get', array('user_name_search', 'offset', 'text_search',
                                 'action', 'user_id')));
extract(sane_import('request', array('search')));

# Get user_name and realname as they were before the action to display
# in further feedback.
if ($action == 'delete' || $action == 'activate' || $action == 'suspend')
  {
    $result = db_execute("SELECT user_name,realname FROM user WHERE user_id=?",
                         array($user_id));
  }
else
  $action = false;

if ($action == 'delete' || $action == 'suspend')
  {
    user_delete ($user_id);
    $out = no_i18n("DELETE");
  }
elseif ($action == 'activate')
  {
    db_execute("UPDATE user SET status='A' WHERE user_id=?", array($user_id));
    $out = no_i18n("ACTIVE");
  }

if ($action)
{
  print '<h2>'.no_i18n("Action done").' :</h2>';
  print '<p>';

  $usr = db_fetch_array($result);
  printf(no_i18n('Status updated to %s for user %s %s.'), $out, $user_id,
         utils_user_link ($usr['user_name'], $usr['realname']));
  print '</p>
';
  print db_error();
}


# Search users.
$abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N',
                   'O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1',
                   '2','3','4','5','6','7','8','9', '_');

print '<h2>'.no_i18n("User Search").'</h2>
<p>'.no_i18n("Display users beginning with").': ';

for ($i=0; $i < count($abc_array); $i++)
  print '<a href="'.htmlentities ($_SERVER["PHP_SELF"])
        .'?user_name_search='.
        $user_name_search.$abc_array[$i].'">'.
        $user_name_search.$abc_array[$i].'</a> ';

print '<br />'.no_i18n("Search by email, username, realname or userid").':';
print '
<form name="usersrch" action="'.htmlentities ($_SERVER["PHP_SELF"])
  .'" method="GET">
  <input type="text" name="text_search" value="'.htmlspecialchars($text_search)
         .'" />
  <input type="hidden" name="usersearch" value="1" />
  <input type="submit" value="'.no_i18n("Search").'" />
</form>
</p>
';

# Show list of users.

$MAX_ROW=100;
if (!$offset)
  $offset = 0;
else
  $offset = intval($offset);

if (!$group_id)
{
  $group_listed = no_i18n("All Groups");

  if ($user_name_search)
    {
      $result = db_execute("SELECT user_name,user_id,status,people_view_skills "
                           ."FROM user WHERE user_name LIKE ? "
                           ."ORDER BY user_name LIMIT ?,?",
                           array(str_replace ('_', '\_', $user_name_search).'%',
                                 $offset, $MAX_ROW+1));
    }
  elseif ($text_search)
    $result = db_execute("SELECT user_name,user_id,status,people_view_skills
                          FROM user WHERE user_name LIKE ? OR user_id LIKE ?
                          OR realname LIKE ? OR email LIKE ?
                          ORDER BY user_name LIMIT ?,?",
                          array($text_search, $text_search,
                                $text_search, $text_search,
                                $offset, $MAX_ROW+1));
  else
    {
      $result = db_execute("SELECT user_name,user_id,status,people_view_skills "
                           ."FROM user ORDER BY user_name LIMIT ?,?",
                           array($offset, $MAX_ROW+1));
    }

}
else
{
  # Show list for one group.
  $group_listed = group_getname($group_id);

  $result = db_execute("SELECT user.user_id AS user_id, user.user_name "
                     . "AS user_name, user.status AS status, "
                     . "user.people_view_skills AS people_view_skills "
                     . "FROM user,user_group "
                     . "WHERE user.user_id=user_group.user_id AND "
                     . "user_group.group_id=? ORDER BY user.user_name LIMIT ?,?",
                       array($group_id, $offset, $MAX_ROW+1));

}

print '<h2>'.sprintf(no_i18n("User List for %s"),
                     '<strong>'.$group_listed.'</strong>')."</h2>\n";

$rows = $rows_returned = db_numrows($result);

$title_arr=array();
$title_arr[]=no_i18n("Id");
$title_arr[]=no_i18n("User");
$title_arr[]=no_i18n("Status");
$title_arr[]=no_i18n("Member Profile");
$title_arr[]=no_i18n("Action");

print html_build_list_table_top ($title_arr);

$inc = 0;
if ($rows_returned < 1)
{
  print '<tr class="'.utils_get_alt_row_color($inc++).'"><td colspan="7">';
  print no_i18n("No matches");
  print '.</td></tr>
';

}
else
{
  if ($rows_returned > $MAX_ROW)
    $rows = $MAX_ROW;

  for ($i = 0; $i < $rows; $i++)
    {
      $usr = db_fetch_array($result);
      print '<tr class="'.utils_get_alt_row_color($inc++).'"><td>'
            .$usr['user_id'].'</td><td><a href="usergroup.php?user_id='
            .$usr['user_id'].'">';
      print "$usr[user_name]</a>";
      print "</td>\n<td>\n";

      switch ($usr['status'])
        {
        case 'A': print no_i18n("Active"); break;
        case 'D': # Fall through.
        case 'S': print no_i18n("Deleted"); break;
        case 'SQD': print no_i18n("Active (Squad)"); break;
        case 'P': print no_i18n("Pending"); break;
        default: print no_i18n("Unknown status")." : ".$usr['status']; break;
        }
      if ($usr['people_view_skills'] == 1 )
        {
          print '<td><a href="'.$GLOBALS['sys_home']
                .'people/resume.php?user_id='.$usr['user_id'].'">['.no_i18n("View")
                .']</a></td>
';
        }
      else
        {
          print '<td>('.no_i18n("Private").')</td>
';
        }
      print '<td>';
      if ($usr['status'] != 'D' && $usr['status'] != 'S' && $usr['status'] != 'SQD')
        print '<a href="?action=delete&user_id='.$usr['user_id'].'">['
                .no_i18n("Delete").']</a> ';
      if ($usr['status'] != 'A' && $usr['status'] != 'SQD')
        print '<a href="?action=activate&user_id='.$usr['user_id'].'">['
                .no_i18n("Activate").']</a> ';
      print "</td>\n</tr>\n";
    }
}
print "</table>\n";

html_nextprev(htmlentities ($_SERVER['PHP_SELF']).
              '?user_name_search='.urlencode($user_name_search).
              '&amp;usersearch=1&amp;search='.urlencode($search).
              '&amp;text_search='.urlencode($text_search),
              $rows, $rows_returned);

$HTML->footer(array());
?>
