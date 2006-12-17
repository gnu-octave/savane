<?php
# Manage commit hooks/triggers
# Copyright (C) 2006  Sylvain Beucler

# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


require_once "../../include/pre.php";
require_once "../../include/account.php";

# get current information
$res_grp = group_get_result($group_id);

if (db_numrows($res_grp) < 1)
{
  exit_error(_("Invalid Group"));
}

session_require(array('group'=>$group_id,'admin_flags'=>'A'));


#echo "<pre>";
#print_r($_POST);
#echo "</pre>";

#echo "<pre>\n";
if (isset($_POST['log_accum'])) {
  foreach ($_POST['remove'] as $hook_id => $ignored) {
    if (!ctype_digit($hook_id.''))
      exit_error(_("Non-numeric hook id") . ": [" . htmlspecialchars($hook_id) . "]");
    $query = "DELETE cvs_hooks, cvs_hooks_log_accum FROM cvs_hooks, cvs_hooks_log_accum
              WHERE cvs_hooks.id = cvs_hooks_log_accum.hook_id AND group_id='$group_id' AND id='$hook_id'\n";
#    echo $query;
    db_query($query) or die(mysql_error());
  }
  foreach ($_POST['id'] as $hook_id => $ignored) {
    // Input validation
    if (!ctype_digit($hook_id.'') and ($hook_id != "new"))
      exit_error(_("Non-numeric hook id") . ": [" . htmlspecialchars($hook_id) . "]");

    if (!isset($_POST['remove'][$hook_id])) {
      $repo_name = $_POST['repo_name'][$hook_id];
      if ($repo_name != 'sources' and $repo_name != 'web')
	exit_error(_("Invalid repository name"));
      $match_type = $_POST['match_type'][$hook_id];
      if ($match_type != 'ALL' and $match_type != 'dir_list' and $match_type != 'DEFAULT')
	exit_error(_("Invalid matching type"));
      $dir_list = $_POST['dir_list'][$hook_id];
      if ($match_type != 'dir_list')
	unset($dir_list);
      else if ($dir_list == '' or preg_match('/[[:space:]]/', $dir_list) or !preg_match("/^(([a-zA-Z0-9_.+-\/]+)(,|$))+/", $dir_list))
	exit_error(_("Invalid directories list"));
      $branch = $_POST['branch'][$hook_id];
      if ($branch == '')
	unset($branch);
      $enable_diff = '0';
      if (isset($_POST['enable_diff'][$hook_id]))
	$enable_diff = $_POST['enable_diff'][$hook_id];
      if ($enable_diff != '0' and $enable_diff != '1')
	exit_error(_("Invalid value for enable_diff"));
      $emails_notif = $_POST['emails_notif'][$hook_id];
      if (!preg_match('/^(([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+.)+[a-zA-Z0-9]+)(,|$))+/', $emails_notif))
	exit_error(_("Invalid list of notification e-mails"));
      $emails_diff = $_POST['emails_diff'][$hook_id];
      if ($emails_diff == '' or $enable_diff == '0')
	unset($emails_diff);
      else if (!preg_match('/^(([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+.)+[a-zA-Z0-9]+)(,|$))*/', $emails_diff))
	exit_error(_("Invalid list of diff notification e-mails"));

      if ($hook_id == 'new') {
	// New entry
	$query = "INSERT INTO
                    cvs_hooks (group_id, repo_name, match_type, dir_list, hook_name, needs_refresh)
                  VALUES ($group_id,
                          '" . sane_mysql($repo_name) . "',
                          '" . sane_mysql($match_type) . "',
                          " . (!isset($dir_list) ? 'NULL' : "'".sane_mysql($dir_list)."'") . ",
                          'log_accum',
                          1)";
	db_query($query) or die(mysql_error());
	$new_hook_id = mysql_insert_id();
	$query = "INSERT INTO
                    cvs_hooks_log_accum (hook_id, branch, emails_notif, enable_diff, emails_diff)
                  VALUES ($new_hook_id,
                          " . (!isset($branch) ? 'NULL' : "'".sane_mysql($branch)."'") . ",
                          '" . sane_mysql($emails_notif) . "',
                          '" . sane_mysql($enable_diff) . "',
                          " . (!isset($emails_diff) ? 'NULL' : "'".sane_mysql($emails_diff)."'") . ")";
	db_query($query) or die(mysql_error());
      } else {
	// Update existing entry
	$query = "UPDATE cvs_hooks, cvs_hooks_log_accum SET
                    repo_name='" . sane_mysql($repo_name) . "',
                    match_type='" . sane_mysql($match_type) . "',
                    dir_list=" . (!isset($dir_list) ? 'NULL' : "'".sane_mysql($dir_list)."'") . ",
                    hook_name='log_accum',
                    needs_refresh=1,
                    branch=" . (!isset($branch) ? 'NULL' : "'".sane_mysql($branch)."'") . ",
                    emails_notif='" . sane_mysql($emails_notif) . "',
                    enable_diff='" . sane_mysql($enable_diff) . "',
                    emails_diff=" . (!isset($emails_diff) ? 'NULL' : "'".sane_mysql($emails_diff)."'") . "
                  WHERE cvs_hooks.id = cvs_hooks_log_accum.hook_id AND group_id=$group_id AND id=$hook_id\n";
#	echo $query;
	db_query($query) or die(mysql_error());
      }
    }
  }
}
#echo "</pre>\n";


site_project_header(array('group'=>$group_id,'context'=>'ahome'));

$available_hooks = array();
echo "<p>";
echo "Available hooks:<br />";
echo "</p>";
echo "<ul>";
foreach (glob(dirname(__FILE__).'/hooks/*.php') as $filename) {
  array_push($available_hooks, preg_replace(':.*/([0-9a-zA-Z_]+).php$:', '\1', $filename));
}

foreach ($available_hooks as $hook) {
  echo "<li>$hook</li>";
}
echo "</ul>";



$hook = 'log_accum';
// Show the project's log_accum hooks
$query = "
SELECT hook_id, repo_name, match_type, dir_list, hook_name, needs_refresh,
	branch, emails_notif, enable_diff, emails_diff
FROM cvs_hooks
JOIN cvs_hooks_$hook ON cvs_hooks.id = hook_id
WHERE group_id = '$group_id'
";

echo "<h2>log_accum</h2>";
echo "<h3>Current notifications</h3>";
echo "<form action='{$_SERVER['PHP_SELF']}?group=$group' method='post'>";
echo "<table>";
echo html_build_list_table_top(array('X', 'Repository', 'Match type', 'Module list', 'Only branch', 'Notification to', 'Diff?', 'Separate diffs to', 'Updated?'));
$result =  db_query($query);
while ($row = mysql_fetch_assoc($result)) {
  $cur= $row['hook_id'];
  echo "<tr>\n";
  echo "<td>";
  echo "<input type='hidden' name='id[$cur]' value='$cur' />\n";
  echo html_build_checkbox("remove[$cur]", 0);
  echo "</td>\n";
  echo "<td>";
  echo html_build_select_box_from_array(array('sources', 'web'), "repo_name[$cur]", $row['repo_name'], 1);
  echo "</td>\n";
  echo "<td>";
  echo html_build_select_box_from_arrays(array('ALL', 'dir_list', 'DEFAULT'),
					 array('Always', 'Module list', 'Fallback'),
					 "match_type[$cur]", $row['match_type'], 0);
  echo "</td>\n";
  echo "<td><input type='text' name='dir_list[$cur]' value='{$row['dir_list']}' /></td>\n";
  echo "<td><input type='text' name='branch[$cur]' value='{$row['branch']}' /></td>\n";
  echo "<td><input type='text' name='emails_notif[$cur]' value='{$row['emails_notif']}' /></td>\n";
  echo "<td>";
  echo html_build_checkbox("enable_diff[$cur]", $row['enable_diff']);
  echo "</td>\n";
  echo "<td><input type='text' name='emails_diff[$cur]' value='{$row['emails_diff']}' /></td>\n";
  echo "<td>" . ($row['needs_refresh'] ? 'Scheduled' : 'Yes') . "</td>";
  echo "</tr>\n";
}

echo "</table>";
$caption=_("Modify");
echo "<input name='log_accum' type='submit' value='$caption' />";
echo "</form>";

// NEW
echo "<h3>New notification</h3>";
echo "<form action='{$_SERVER['PHP_SELF']}?group=$group' method='post'>";
echo "<ol>";
echo "<li>Repository: ";
echo html_build_select_box_from_array(array('sources', 'web'), "repo_name[new]", $row['repo_name'], 1);
echo "</li><li>";
echo "Matching type: ";
echo html_build_select_box_from_arrays(array('ALL', 'dir_list', 'DEFAULT'),
					 array('Always', 'Module list', 'Fallback'),
					 "match_type[new]", $row['match_type'], 0);
echo "<ul>
  <li><i>Always</i> is always performed (even in addition to Fallback)</li>
  <li><i>Module list</i> if you specify a list of directories (see below)<li/>
  <li><i>Fallback</i> is used when nothing matches</li>
</ul>";
echo "</li><li>";
echo "Filter by directory: if match is <i>Module list</i>, enter a list of directories separated by commas
  (eg: <code>emacs,emacs/lisp,manual</code>)<br />
  You'll only get notifications if the commit is performed in one of these directories.<br/>
  Leave blank if you want to get notifications for all the repository (default):<br />";
echo "<input type='text' name='dir_list[new]' value='{$row['dir_list']}' />";
echo "</li><li>";
echo "List of comma-separated e-mails to send notifications to (eg: him@domain.org,her@domain.org):<br />";
echo "<input type='text' name='emails_notif[new]' value='{$row['emails_notif']}' />";
echo "</li><li>";
echo "Send diffs? ";
echo html_build_checkbox("enable_diff[new]", $row['enable_diff']);
echo "</li><li>";
echo "Optional alternate list of mails to send diffs separately to (eg: him@domain.org,her@domain.org).<br />
  If empty, the diffs will be included with the commit notifications:<br />";
echo "<input type='text' name='emails_diff[new]' value='{$row['emails_diff']}' />\n";
echo "</li><li>";
echo "Filter by branch: you will be notified only of this branch's commits.<br />
  Enter <i>HEAD</i> if you only want trunk (non-branch) commits.<br />
  Leave blank if you want to get notifications for all commits (default):<br/>";
echo "<input type='text' name='branch[new]' value='{$row['branch']}' />";
echo "</li></ol>";
echo "<input type='hidden' name='id[new]' value='new' />";
$caption = _('Add');
echo "<input type='submit' name='log_accum' value='$caption' />
</form>";


$hook = 'cia';
// Show the project's log_accum hooks
$query = "
SELECT repo_name, match_type, dir_list, hook_name, needs_refresh,
	project_account
FROM cvs_hooks
JOIN cvs_hooks_$hook ON cvs_hooks.id = hook_id
WHERE group_id = '$group_id'
";

echo "<h2>cia (in progress)</h2>";
echo "<h3>Current CIA notifications</h3>";
echo "<form action={$_SERVER['PHP_SELF']}>";
echo "<table>";
echo html_build_list_table_top(array('X', 'Repository', 'Match type', 'Module list', 'CIA Project', 'Updated?'));
$result =  db_query($query);
while ($row = mysql_fetch_assoc($result)) {
  echo "<tr>";
  echo "<td>";
  echo html_build_checkbox("remove[$cur]", 0);
  echo "</td>\n";
  echo "<td>";
  echo html_build_select_box_from_array(array('sources', 'web'), 'repo_name', $row['repo_name'], 1);
  echo "</td>";
  echo "<td>";
  echo html_build_select_box_from_arrays(array('ALL', 'dir_list', 'DEFAULT'),
					 array('Always', 'Module list', 'Fallback'),
					 'match_type', $row['match_type'], 0);
  echo "</td>";
  echo "<td><input type='text' name='dir_list[$cur]' value='{$row['dir_list']}' /></td>\n";
  echo "<td><INPUT TYPE='text' VALUE='{$row['project_account']}' /></td>";
  echo "<td>" . ($row['needs_refresh'] ? 0 : 1) . "</td>";
  echo "</tr>";  
}

echo "</table>";
echo "</form>";


site_project_footer(array());
