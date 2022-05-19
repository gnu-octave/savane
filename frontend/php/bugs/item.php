<?php
# Serve comments in an item as an ASCII file, encrypted when needed.
#
# Copyright (C) 2022 Ineiev
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

$includes = ['init', 'gpg', 'member', 'trackers/general', 'trackers/format'];

foreach ($includes as $i)
  require_once ("../include/$i.php");

extract (sane_import ('get', ['name' => 'user']));

$tracker = ARTIFACT;

$needed_params = ['user', 'item_id'];
$missing_params = [];
foreach ($needed_params as $p)
  if (empty ($$p))
    $missing_params[] = $p;
if (!empty ($missing_params))
  exit_missing_param ($missing_params);

$fields = ['group_id', 'privacy'];
$field_list = join (', ', $fields);

$result = db_execute (
  "SELECT $field_list FROM $tracker WHERE bug_id = ?", [$item_id]
);

if (!$result || db_numrows ($result) < 1)
  exit_error (_("Item not found."));

$arr = db_fetch_array ($result);
foreach ($fields as $k)
  $$k = $arr[$k];

$group = project_get_object ($group_id);
if ($group->isError ())
  exit_no_group ();

$result = db_execute (
  "SELECT user_id FROM user WHERE user_name = ?", [$user]
);

if (!$result || db_numrows ($result) < 1)
  exit_error (_("User not found."));

$user_id = db_fetch_array ($result)['user_id'];

if ($privacy == '2' && !member_check_private ($user_id, $group_id))
  exit_permission_denied ();

if (!($group->isPublic () || member_check ($user_id, $group_id)))
  exit_permission_denied ();

$ctype = "text/plain";
$fname = "$item_id.txt";
$message = format_item_details ($item_id, $group_id, true);
if ($privacy == '2' || !$group->isPublic ())
  {
    list ($exit_code, $error_msg, $encrypted_message) =
      encrypt_to_user ($user_id, $message);
    if ($exit_code)
      exit_error ($error_msg);
    $message = $encrypted_message;
    $fname .= '.gpg';
    $ctype = "application/pgp-encrypted";
  }

header ("Content-Type: $ctype");
header ("Content-Disposition: attachment; filename=$fname");
print $message;
?>
