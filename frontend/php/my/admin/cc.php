<?php
# Cancelling notifications.
# 
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
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


require_once('../../include/init.php');
register_globals_off();

# Check if the user is logged in.
session_require(array('isloggedin'=>'1'));

# Initialize some vars
$trackers = array('bugs', 'task', 'patch', 'support', 'cookbook');
$user_id = user_getid();
$user_email = user_getemail();
$user_name = user_getname();


extract(sane_import('request', array('cancel')));

########################################################################
# Update the database
if (!empty($cancel))
{
  $whichgroup = $cancel;
  foreach ($trackers as $tracker)
    {
      if ($whichgroup == 'any')
	{
          # If it all groups, go the easy way
	  db_execute("DELETE FROM ${tracker}_cc
                      WHERE ${tracker}_cc.email=?
                        OR ${tracker}_cc.email=?
                        OR ${tracker}_cc.email=?",
		   array($user_id, $user_email, $user_name));
	}
      else
	{
	  # If we need to remove items only for a given group, we first need
	  # to get this list of itemsps
	  $result = db_execute("SELECT bug_id FROM $tracker WHERE group_id=?",
			       array($whichgroup));
	  while ($entry = db_fetch_array($result))
	    {
	      db_execute("DELETE FROM ${tracker}_cc
                          WHERE bug_id=? AND (${tracker}_cc.email=?
                            OR ${tracker}_cc.email=? OR ${tracker}_cc.email=?)",
			 array($entry['bug_id'], $user_id, $user_email, $user_name));

	    }
	  
	}
    }

  # Not much crosscheck here, so no feedback (the result should be obvious
  # anyway)
}

########################################################################
# Actually prints the HTML page

site_user_header(array('title'=>_("Cancel Mail Notifications"),
		       'context'=>'account'));

# The following text is in two gettext string, because the first part is also
# shown in My Admin index.
print '<p>'._("Here, you can cancel all mail notifications. Beware: this
process cannot be undone, you will be definitely removed from carbon-copy lists
of any items of the selected groups.").'<p>'."\n";

# Find all CC the users is registered to receive, list them per groups.
$groups_with_cc = array();
$groups_with_cc_gid = array();
foreach ($trackers as $tracker)
{
  $result = db_execute("
SELECT groups.unix_group_name,groups.group_name,$tracker.group_id
FROM groups,$tracker,${tracker}_cc
WHERE groups.group_id = $tracker.group_id
  AND $tracker.bug_id = {$tracker}_cc.bug_id
  AND (${tracker}_cc.email = ?
       OR ${tracker}_cc.email = ?
       OR ${tracker}_cc.email = ?)
GROUP BY groups.group_name",
    array($user_id, $user_email, $user_name));
  while ($entry = db_fetch_array($result)) 
    {
      if (isset($groups_with_cc[$entry['group_id']]))
	{ continue; }
      $groups_with_cc[$entry['unix_group_name']] = $entry['group_name'];
      $groups_with_cc_gid[$entry['unix_group_name']] = $entry['group_id'];
    }
}

if (!count($groups_with_cc))
{
  print '<p class="warn">'
        ._("You are not registered on any Carbon-Copy list.").'</p>'."\n";
  site_user_footer(array());
  exit;
}



print $HTML->box_top(_("Groups to which belong items you are in Carbon Copy for"));
ksort($groups_with_cc);
$i = 0;
foreach ($groups_with_cc as $thisunixname => $thisname)
{

  $i++;
  if ($i > 1)
    { print $HTML->box_nextitem(utils_get_alt_row_color($i)); }

  print '<span class="trash">';
  print utils_link($_SERVER['PHP_SELF'].'?cancel='.$groups_with_cc_gid[$thisunixname],
		   '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
                   .'.theme/misc/trash.png" border="0" alt="'
                   ._("Cancel CC for this group").'" />');
  print '</span>';
  
  # I18N
  # The variables are: session identifier, time, remote host
  print  '<a href="'.$GLOBALS['sys_home'].'projects/'.$thisunixname
         .'/">'.$thisname.'</a><br />'."\n";
}

# Allow to kill sessions apart the current one,
# if more than 3 sessions were counted
# (otherwise, it looks overkill)
if ($i > 3)
{
  $i++;
  print $HTML->box_nextitem(utils_get_alt_row_color($i));
 print '<span class="trash">';
  print utils_link($_SERVER['PHP_SELF'].'?cancel=any',
		   '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
                   .'.theme/misc/trash.png" border="0" alt="'
                   ._("Cancel All CC").'" />');
  print '</span>';
# TRANSLATORS: the argument is site name (like Savannah).
  print '<em>'.sprintf(_("All Carbon-Copies over %s"), $GLOBALS['sys_name'])
              .'</em><br />&nbsp;';

}

print $HTML->box_bottom();

site_user_footer(array());
?>
