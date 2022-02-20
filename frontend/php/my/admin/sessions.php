<?php
# Handle open sessions.
#
# Copyright (C) 2004 Mathieu Roy <yeupou--gnu.org>
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

extract (sane_import ('get',
  [
    'strings' =>
      [
        ['func', 'del'],
      ],
    'true' => 'dkeep_one',
    'digits' => 'dtime',
    'preg' =>
      [
        ['dip_addr', ',^[\d./:]+$,'],
        ['dsession_hash', '/^[a-f\d]+[.]{3}$/']
      ],
  ]
));
extract (sane_import ('cookie', ['hash' => 'session_hash']));

# Update the database.
if ($func == 'del')
  {
    if ($dsession_hash && $dip_addr && $dtime)
      {
        # Delete one session.
        $dsession_hash = substr($dsession_hash, 0, 6)."%";
        if (db_execute("DELETE FROM session "
              . " WHERE session_hash like ? AND ip_addr=? "
              . " AND time=? AND user_id=? LIMIT 1",
              array($dsession_hash, $dip_addr,
                    $dtime, user_getid())))
          fb(
# TRANSLATORS: this is a report of a successful action.
             _("Old session deleted"));
        else
          fb(_("Failed to delete old session"), 1);
      }
    elseif ($dkeep_one)
      {
        # Delete all sessions apart from the current one.
        if (db_execute("DELETE FROM session "
              . " WHERE session_hash<>? AND user_id=?",
              array($session_hash, user_getid())))
          fb(
# TRANSLATORS: this is a report of a successful action.
             _("Old sessions deleted"));
        else
          fb(_("Failed to delete old sessions"), 1);
      }
    else
      fb(_("Parameters missing, update canceled"), 1);
  }
# Actually print the HTML page.
site_user_header(array('title'=>_("Manage sessions"),
                       'context'=>'account'));
$res = db_execute("SELECT session_hash,ip_addr,time FROM session WHERE "
                  . "user_id = ? "
                  . "ORDER BY time DESC", array(user_getid()));
if (db_numrows($res) < 1)
  exit_error(_("No session found."));

print $HTML->box_top(_("Opened Sessions"));
$i = 0;
while ($row = db_fetch_array($res))
  {
    $i++;
    if ($i > 1)
      print $HTML->box_nextitem(utils_get_alt_row_color($i));

  # We destroy a part of the session hash because in no case we want to
  # provide in clear text that complete information that could be used for
  # forgery (even if it is true that this page access is normally properly
  # restricted).
    $dsession_hash = substr($row['session_hash'], 0, 6)."...";
    # Do not incitate users to kill their own session.
    print '<span class="trash">';
    if ($session_hash != $row['session_hash'])
      {
        print utils_link(htmlentities ($_SERVER['PHP_SELF'])
                         .'?func=del&amp;dsession_hash='
                         .$dsession_hash.'&amp;dip_addr='.$row['ip_addr']
                         .'&amp;dtime='.$row['time'],
                         '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
                         .'.theme/misc/trash.png" border="0" alt="'
                         ._("Kill this session").'" />');
      }
    else
      print _("Current session").' ';
    print '</span>';

  # TRANSLATORS: The variables are session identifier, time, remote host.
    print sprintf(_('Session %1$s opened on %2$s from %3$s'), $dsession_hash,
                  utils_format_date($row['time']), gethostbyaddr($row['ip_addr']))
          ."<br />&nbsp;";
  }

# Allow to kill sessions apart the current one,
# if more than 3 sessions were counted
# (otherwise, it looks overkill).
if ($i > 3)
  {
    $i++;
    print $HTML->box_nextitem(utils_get_alt_row_color($i));
    print '<span class="trash">';
    print utils_link(htmlentities ($_SERVER['PHP_SELF'])
                     .'?func=del&amp;dkeep_one=1',
                     '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
                     .'.theme/misc/trash.png" border="0" alt="'
                     ._("Kill all sessions").'" />');
    print '</span>';
    print '<em>'._("All sessions apart from the current one").'</em><br />&nbsp;';
  }
print $HTML->box_bottom();
site_user_footer(array());
?>
