<?php
# Edit miscellaneous tracker settings (preambles).
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
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
$artifact = ARTIFACT;
$pref_preamble_titles = [
  'comment' => [
     _("Comment Post Form Preamble"),
     _("Introductory message for comments"),
  ],
  'file' => [
    _("File Post Form Preamble"),
    _("Introductory message for files"),
  ],
];

function pref_name ($name, $artifact)
{
  return "${artifact}_${name}_preamble";
}
function form_pref_name ($x)
{
  return "form_$x";
}
$pref_preambles = [];
foreach ($pref_preamble_titles as $pre => $t)
  {
    $name = pref_name ($pre, $artifact);
    $pref_preambles[] = $name;
    $form_pref_preambles[] = form_pref_name ($name);
  }

$form_preambles = $form_pref_preambles;
$form_preambles[] = 'form_preamble';

extract (sane_import ('post',
  ['true' => 'submit', 'specialchars' => $form_preambles]
));

require_directory ("project");

$is_admin_page = 'y';

if (!$group_id)
  exit_no_group();

if (
  # Must be at least Bug Admin or Project Admin.
  !member_check (0, $group_id, member_create_tracker_flag (ARTIFACT) . '2')
  && !user_ismember ($group_id, 'A')
)
  exit_permission_denied ();

function fetch_preamble ($group_id, $artifact)
{
  global $current_preamble;
  $res_grp = db_execute ("SELECT * FROM groups WHERE group_id = ?", [$group_id]);
  if (db_numrows ($res_grp) < 1)
    exit_no_group ();
  $row_grp = db_fetch_array ($res_grp);
  return $row_grp["${artifact}_preamble"];
}

$current_preamble = fetch_preamble ($group_id, $artifact);

$new_pref_keys = [];
$new_pref_vals = [];
function fetch_pref_preambles ($group_id)
{
  global $cur_pref_preambles, $pref_preambles, $new_pref_keys, $new_pref_vals;
  $cur_pref_preambles = group_get_preference ($group_id, $pref_preambles);
  foreach ($pref_preambles as $pre)
    {
      $new_val = $GLOBALS["form_$pre"];
      if ($cur_pref_preambles[$pre] === $new_val)
        continue;
      $new_pref_keys[] = $pre;
      $new_pref_vals[] = $new_val;
    }
}

fetch_pref_preambles ($group_id);

$changed = $form_preamble != $current_preamble;
$pref_changed = !empty ($new_pref_keys);

if ($submit && ($changed || $pref_changed))
  {
    group_add_history ('Changed Tracking System Settings', '', $group_id);
    $update_failed = false;
    if ($changed)
      {
        $result = db_execute (
          "UPDATE groups SET ${artifact}_preamble = ?  WHERE group_id = ?",
          [$form_preamble, $group_id]
        );
        if ($result)
          $current_preamble = fetch_preamble ($group_id, $artifact);
        else
          $update_failed = true;
     }
    if ($pref_changed)
      {
        if (group_set_preference ($group_id, $new_pref_keys, $new_pref_vals))
          fetch_pref_preambles ($group_id);
        else
          $update_failed = true;
      }
    if ($update_failed)
      fb (_("Update failed"));
    else
      fb (_("SUCCESSFUL UPDATE"));
  }

trackers_header_admin (['title' => _("Other Settings")]);

print '<h2>' . _("Item Post Form Preamble") . "</h2>\n";
print '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
  . '" method="post">' . form_hidden (['group_id' => $group_id]);
print '<span class="preinput"><label for="form_preamble">';
print _("Introductory message showing at the top of the item submission form");
print '</label> ' . markup_info ("rich")
  . "</span>\n<br />\n"
  . "<textarea cols='70' rows='8' wrap='virtual' id='form_preamble'"
  . " name='form_preamble'>$current_preamble</textarea>\n";

function pref_preamble_input ($form_name, $title, $current)
{
  $heading = $title[0]; $info = $title[1];

  print "<h2>$heading</h2>\n";
  print "<span class='preinput'><label for='$form_name'>";
  print "$info</label> " . markup_info ("rich")
    . "</span>\n<br />\n"
    . "<textarea cols='70' rows='8' wrap='virtual' id='$form_name'"
    . " name='$form_name'>$current</textarea>\n";
}

foreach ($pref_preamble_titles as $pre => $title)
  {
    $name = pref_name ($pre, $artifact);
    $form_name = form_pref_name ($name);
    pref_preamble_input ($form_name, $title, $cur_pref_preambles[$name]);
  }

print '<div class="center"><input type="submit" name="submit" value="'
 . _("Submit") . '" />' . "</div>\n</form>\n";

trackers_footer ([]);
?>
