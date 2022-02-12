<?php
# Manage commit hooks/triggers.
# Copyright (C) 2006  Sylvain Beucler
# Copyright (C) 2017, 2018, 2021, 2022 Ineiev
#
# This file is part of the Savane project
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
require_once('../../include/sane.php');
require_once('../../include/account.php');

function match_type_box ($arr_match_type, $row_match_type)
{
  return
    html_build_select_box_from_arrays (array('ALL', 'dir_list', 'DEFAULT'),
                                       array(_('Always'), _('Module list'),
                                             _('Fallback')),
                                       $arr_match_type,
                                       $row_match_type, false, 'None',
                                       false, 'Any', false, 'match type');
}

# Get current information.
$res_grp = group_get_result($group_id);

if (db_numrows($res_grp) < 1)
  exit_error(_("Invalid Group"));

session_require(array('group'=>$group_id,'admin_flags'=>'A'));
$key_func = ['preg', '/^(([\d]+)|(new))$/'];
extract(sane_import('post',
  [
    'true' => 'log_accum',
    'array' => [
      [
        'arr_branches',
        [$key_func, ['preg', '/^[-~!@#$%^&*()+=:.,_\da-zA-Z]+$/']]
      ],
      ['arr_id', [$key_func, 'true']],
      ['arr_remove', [['preg', '/^[\d]+$/'], 'true']],
      ['arr_repo_name', [$key_func, ['strings', ['sources', 'web']]]],
      [
        'arr_match_type',
        [$key_func, ['strings', ['ALL', 'dir_list', 'DEFAULT']]]
      ],
      [
        'arr_dir_list',
        [
          $key_func,
          ['preg', '/^(([a-zA-Z0-9_.+\/-]+,)*([a-zA-Z0-9_.+\/-]+))$/']
        ]
      ],
      ['arr_emails_notif', 'arr_emails_diff',
        [
          $key_func,
          [
            'preg',
            '/^([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+,)*'
            . '([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+)$/'
          ]
        ]
      ],
      ['arr_enable_diff', [$key_func, ['digits', [1, 1]]]]
    ]
  ]));

if (isset($log_accum))
  {
    $have_arr_id = isset ($arr_id) && is_array ($arr_id);
    if (isset($arr_remove) and is_array($arr_remove))
      {
        foreach ($arr_remove as $hook_id => $ignored)
          {
            db_execute("DELETE cvs_hooks, cvs_hooks_log_accum
                  FROM cvs_hooks, cvs_hooks_log_accum
                  WHERE cvs_hooks.id = cvs_hooks_log_accum.hook_id
                    AND group_id=? AND id=?",
                 array($group_id, $hook_id)) or die(db_error());
            if ($have_arr_id)
              unset ($arr_id[$hook_id]);
          }
      }
    if ($have_arr_id)
      {
        foreach ($arr_id as $hook_id => $ignored)
          {
            if (!isset ($arr_repo_name[$hook_id]))
              continue;
            if (!isset ($arr_match_type[$hook_id]))
              continue;
            $repo_name = $arr_repo_name[$hook_id];
            $match_type = $arr_match_type[$hook_id];
            $dir_list = null;
            if (isset ($arr_dir_list[$hook_id]) && $match_type == 'dir_list')
              $dir_list = $arr_dir_list[$hook_id];
            $branches = null;
            if (isset ($arr_branches[$hook_id])
                && $arr_branches[$hook_id] != '')
              $branches = $arr_branches[$hook_id];
            $enable_diff = '0';
            if (isset($arr_enable_diff[$hook_id]))
              $enable_diff = $arr_enable_diff[$hook_id];
            $emails_notif = null;
            if (isset ($arr_emails_notif[$hook_id]))
              $emails_notif = $arr_emails_notif[$hook_id];
            if ($emails_notif === null)
              continue;
            $emails_diff = null;
            if (isset ($arr_emails_diff[$hook_id])
                && $arr_emails_diff[$hook_id])
              $emails_diff = $arr_emails_diff[$hook_id];
            if ($enable_diff && $emails_diff === null)
              continue;

            if ($hook_id == 'new')
              {
                db_autoexecute ('cvs_hooks',
                  [
                    'group_id' => $group_id,
                    'repo_name' => $repo_name,
                    'match_type' => $match_type,
                    'dir_list' => $dir_list,
                    'hook_name' => 'log_accum'
                  ],
                  DB_AUTOQUERY_INSERT) or die(db_error());
                $new_hook_id = db_insertid (NULL);
                db_autoexecute ('cvs_hooks_log_accum',
                  [
                    'hook_id' => $new_hook_id,
                    'branches' => $branches,
                    'emails_notif' => $emails_notif,
                    'enable_diff' => $enable_diff,
                    'emails_diff' => $emails_diff
                   ],
                   DB_AUTOQUERY_INSERT) or die(db_error());
              }
            else
              {
                db_autoexecute ('cvs_hooks, cvs_hooks_log_accum',
                  [
                    'repo_name' => $repo_name,
                    'match_type' => $match_type,
                    'dir_list' => $dir_list,
                    'hook_name' => 'log_accum',
                    'branches' => $branches,
                    'emails_notif' => $emails_notif,
                    'enable_diff' => $enable_diff,
                    'emails_diff' => $emails_diff
                  ],
                  DB_AUTOQUERY_UPDATE,
                  "cvs_hooks.id = cvs_hooks_log_accum.hook_id
                  AND group_id=? AND id=?",
                  [$group_id, $hook_id]) or die(db_error());
            }
          } # foreach ($arr_id as $hook_id => $ignored)
      } # if ($have_arr_id)
  } # if (isset($log_accum))
site_project_header(array('group'=>$group_id,'context'=>'ahome'));

$hook = 'log_accum';
# Show the project's log_accum hooks.
$result =  db_execute("
SELECT hook_id, repo_name, match_type, dir_list, hook_name,
        branches, emails_notif, enable_diff, emails_diff
FROM cvs_hooks
JOIN cvs_hooks_$hook ON cvs_hooks.id = hook_id
WHERE group_id = ?", array($group_id));

echo "<h2>" . _("Current notifications") . "</h2>\n";
echo "<form action='";
print htmlentities ($_SERVER['PHP_SELF'])."?group=$group' method='post'>\n";
echo "<table>\n";
echo html_build_list_table_top(array(
'<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
. '.theme/misc/trash.png" class="icon" alt="' . _("Delete") . '" />',
   _('Repository'), _('Match type'),
   _('Module list'), _('Branch filter'),
   _('Notification to'), _('Diff?'),
   _('Separate diffs to')));

while ($row = db_fetch_array ($result))
  {
    $cur= $row['hook_id'];
    echo "<tr>\n";
    echo "<td>";
    echo "<input type='hidden' name='arr_id[$cur]' value='$cur' />\n";
    echo html_build_checkbox("arr_remove[$cur]", 0);
    echo "</td>\n";
    echo "<td>";
    echo html_build_select_box_from_array(array('sources', 'web'),
                                          "arr_repo_name[$cur]",
                                          $row['repo_name'], 1);
    echo "</td>\n";
    echo "<td>";
    echo match_type_box ("arr_match_type[$cur]", $row['match_type']);
    echo "</td>\n";
    echo "<td><input type='text' name='arr_dir_list[$cur]' "
         ."value='{$row['dir_list']}' /></td>\n";
    echo "<td><input type='text' name='arr_branches[$cur]' "
         ."value='{$row['branches']}' /></td>\n";
    echo "<td><input type='text' name='arr_emails_notif[$cur]' "
         ."value='{$row['emails_notif']}' /></td>\n";
    echo "<td>";
    echo html_build_checkbox("arr_enable_diff[$cur]", $row['enable_diff']);
    echo "</td>\n";
    echo "<td><input type='text' name='arr_emails_diff[$cur]' "
         ."value='{$row['emails_diff']}' /></td>\n";
    echo "</tr>\n";
  }

echo "</table>\n";
$caption=_("Modify");
echo "<input name='log_accum' type='submit' value='$caption' />\n";
echo "</form>\n";

echo "<h2>" . _("New notification") . "</h2>\n";
echo "<form action='";
print htmlentities ($_SERVER['PHP_SELF'])."?group=$group' method='post'>\n";
echo "<ol>\n";
echo "<li>" . _("Repository:") . " ";
echo html_build_select_box_from_array(array(
# TRANSLATORS: this is the type of repository (sources  or web).
                                            _('sources'),
# TRANSLATORS: this is the type of repository (sources  or web).
                                            _('web')),
                                      "arr_repo_name[new]", $row['repo_name'], 1);
echo "</li>\n<li>";
echo _("Matching type:") . " ";
echo match_type_box ("arr_match_type[new]", $row['match_type']);
echo "<ul>
  <li>" . _("<i>Always</i> is always performed (even in addition to Fallback)")
  . "</li>
  <li>"
  . _("<i>Module list</i> if you specify a list of directories (see below)")
  . "</li>
  <li>" . _("<i>Fallback</i> is used when nothing matches") . "</li>
</ul>\n";
echo "</li>\n<li>";
echo _("Filter by directory: if match is <i>Module list</i>, enter a list of
  directories separated by commas,
  e.g. <code>emacs,emacs/lisp,manual</code><br />
  You'll only get notifications if the commit is performed in one of these
  directories.<br />
  Leave blank if you want to get notifications for all the repository:") . "<br />";
echo "<input type='text' name='arr_dir_list[new]' value='{$row['dir_list']}' />";
echo "</li>\n<li>";
echo _("List of comma-separated emails to send notifications to, e.g.
     him@domain.org, her@domain.org):") . "<br />";
echo "<input type='text' name='arr_emails_notif[new]' "
     ."value='{$row['emails_notif']}' />";
echo "</li>\n<li>";
echo _("Send diffs?") . " ";
echo html_build_checkbox("arr_enable_diff[new]", $row['enable_diff']);
echo "</li>\n<li>";
echo _("Optional alternate list of emails to send diffs separately to, e.g.
      him@domain.org, her@domain.org.<br />
  If empty, the diffs will be included in commit notifications:")
  . "<br />";
echo "<input type='text' name='arr_emails_diff[new]' "
     ."value='{$row['emails_diff']}' />\n";
echo "</li>\n<li>";
echo _("Filter by branch: you will be notified only commits in these branches,
  separated by commas.<br />
  Enter <i>HEAD</i> if you only want trunk commits.<br />
  Leave blank to get notifications for all commits:")
  . "<br />";
echo "<input type='text' name='arr_branches[new]' value='{$row['branches']}' />";
echo "</li>\n</ol>\n";
echo "<input type='hidden' name='arr_id[new]' value='new' />\n";
$caption = _('Add');
echo "<input type='submit' name='log_accum' value='$caption' /\n>
</form>\n";

print "<p>" . _("The changes come into effect within an hour.") . "</p>\n";

site_project_footer(array());
?>
