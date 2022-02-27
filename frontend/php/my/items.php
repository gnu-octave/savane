<?php
# List user's items.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev
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
require_once('../include/init.php');
require_once('../include/my/general.php');
require_directory("trackers");
register_globals_off();

global $item_data, $group_data;
$item_data = array();
$group_data = array();

if (!user_isloggedin())
  exit_not_logged_in();

extract(sane_import('get',
  [
    'digits' => [['form_threshold', [1, 9]]],
    'strings' => [['form_open', ['open', 'closed']]],
    'true' => 'boxoptionwanted'
  ]
));

# Get the list of projects the user is member of.
$result = db_execute("SELECT groups.group_name,"
  . "groups.group_id,"
  . "groups.unix_group_name,"
  . "groups.status "
  . "FROM groups,user_group "
  . "WHERE groups.group_id=user_group.group_id "
  . "AND user_group.user_id = ? "
  . "AND groups.status='A' "
  . "GROUP BY groups.unix_group_name "
  . "ORDER BY groups.unix_group_name", array(user_getid()));
$rows = db_numrows($result);
$usergroups = array();
$usergroups_groupid = array();
if ($result && $rows > 0)
  {
    for ($j=0; $j<$rows; $j++)
        {
          unset($nogroups);
          $unixname = db_result($result,$j,'unix_group_name');
          $usergroups[$unixname] = db_result($result,$j,'group_name');
          $usergroups_groupid[$unixname] = db_result($result,$j,'group_id');
        }
  }
else
  $nogroups = 1;

# Get the list of squads the user is member of.
$result = db_execute("SELECT squad_id FROM user_squad WHERE user_id=?",
                     array(user_getid()));
$rows = db_numrows($result);
$usersquads = array();
if ($result && $rows > 0)
  {
    unset($nosquads);
    for ($j=0; $j<$rows; $j++)
        {
          $usersquads[] = db_result($result,$j,'squad_id');
        }
  }
else
  $nosquads = 1;

$threshold = $form_threshold;
if ($threshold)
  user_set_preference ("my_items_threshold", $threshold);

$open = $form_open;
if ($open)
  user_set_preference ("my_items_open", $open);

# Extract configuration if needed.
if (!$threshold)
  $threshold = user_get_preference("my_items_threshold");
if (!$open)
  $open = user_get_preference("my_items_open");

# Still nothing? Set the default settings.
if (!$threshold)
  $threshold = 5;
if (!$open)
  $open = "open";

site_user_header(array('context'=>'myitems'));
print '<p>'
  ._("This page contains lists of items assigned to or submitted by you.")
  .'</p>';
utils_get_content("my/items");

$fopen = '<select title="'._("open or closed").'" name="form_open">
<option value="open" '
         .($open == "open" ? 'selected="selected"':'').'>'
# TRANSLATORS: This is used later as argument of "Show [%s] new items..."
         ._("Open<!-- items -->");
$fopen .= '</option>
<option value="closed" '
          .($open == "closed" ? 'selected="selected"':'').'>'
# TRANSLATORS: This is used later as argument of "Show [%s] new items..."
          ._("Closed<!-- items -->")
          .'</option></select>
';

$fthreshold = '<select title="'.("priority").'" name="form_threshold">
<option value="1" '
             .($threshold == 1 ? 'selected="selected"':'').'>'
# TRANSLATORS: This is used later as argument of "...new items or of [%s] priority"
             ._("Lowest").'</option>
<option value="3" ';
$fthreshold .= ($threshold == 3 ? 'selected="selected"':'').'>'
# TRANSLATORS: This is used later as argument of "...new items or of [%s] priority"
               ._("Low")
               .'</option>
<option value="5" '
               .($threshold == 5 ? 'selected="selected"':'')
               .'>'
# TRANSLATORS: This is used later as argument of "...new items or of [%s] priority"
               ._("Normal").'</option>
<option value="7" ';
$fthreshold .= ($threshold == 7 ? 'selected="selected"':'').'>'
# TRANSLATORS: This is used later as argument of "...new items or of [%s] priority"
               ._("High")
               .'</option>
<option value="9" ';
$fthreshold .= ($threshold == 9 ? 'selected="selected"':'')
               .'>'
# TRANSLATORS: This is used later as argument of "...new items or of [%s] priority"
               ._("Immediate").'</option></select>
';

$form_opening = '<form action="'.htmlentities($_SERVER['PHP_SELF'])
                .'#options" method="get">';
$form_submit = '<input class="bold"  type="submit" value="'._("Apply").'" />';
# TRANSLATORS: the first argument is either 'Open' or 'Closed',
# the second argument is priority ('Lowest', 'Normal' &c.).
$msg_text = sprintf(_('Show %1$s new items of %2$s priority at least.'),
                    $fopen, $fthreshold);
print html_show_displayoptions($msg_text, $form_opening, $form_submit);

# Right part.
print html_splitpage(1);

print '<br /><div class="box"><div class="boxtitle">'._("Assigned to me")
      .'</div>'."\n";
print my_item_list("assignee", $threshold, $open);
print '</div>'."\n";

# Left part.
print html_splitpage(2);

print '<br /><div class="box"><div class="boxtitle">'._("Submitted by me")
      .'</div>'."\n";
print my_item_list("submitter", $threshold, $open);
print '</div>'."\n";
print html_splitpage(3);
print "\n\n".show_priority_colors_key();
$HTML->footer(array());
?>
