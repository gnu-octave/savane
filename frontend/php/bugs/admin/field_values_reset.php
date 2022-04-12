<?php
# Reset field values.
#
# Copyright (C) 2005-2006 Mathieu Roy <yeupou--gnu.org>
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

require_once ('../../include/init.php');
require_once ('../../include/trackers/general.php');
extract (sane_import ('post',
  [
    'name' => 'field',
    'true' => ['confirm', 'cancel']
  ]
));

if (!$group_id)
  exit_no_group ();
if (!user_ismember ($group_id, 'A'))
  exit_permission_denied ();

trackers_init($group_id);

if (!$field)
  exit_missing_param ();

$redraw_url = $sys_home . ARTIFACT
  . "/admin/field_values.php?group_id=$group_id&field=$field&list_value=1";

if ($cancel)
  session_redirect ($redraw_url);
if ($confirm)
  {
    db_execute ("
      DELETE FROM " . ARTIFACT . "_field_value
      WHERE group_id = ? AND bug_field_id = ?",
      [$group_id, trackers_data_get_field_id ($field)]
    );
    session_redirect ($redraw_url);
    exit (0);
  }
$hdr = sprintf (_("Reset Values of '%s'"), trackers_data_get_label ($field));
trackers_header_admin (['title' => $hdr]);

print '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
  . "\" method='post'>\n";
print form_hidden (["group_id" => $group_id, "field" => $field]);
print '<span class="preinput">';
printf(
  _("You are about to reset values of the field %s.\n"
    . "This action will not be undoable, please confirm:"),
  trackers_data_get_label ($field)
);
print '</span>';
print '<div class="center"><input type="submit" name="confirm" value="'
  . _("Confirm") . '" /> <input type="submit" name="cancel" value="'
  . _("Cancel") . "\" /></div>\n";
print "</form>\n";
?>
