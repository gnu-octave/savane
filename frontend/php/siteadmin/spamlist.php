<?php
# List spam items.
#
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2019 Ineiev
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

register_globals_off();
session_require(array('group'=>'1','admin_flags'=>'A'));

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

extract (sane_import ('get',
  ['digits' => ['ban_user_id', 'wash_user_id', 'max_rows', 'offset']]
));

if ($ban_user_id)
  {
    if (!user_exists($ban_user_id))
      fb(no_i18n("User not found"), 1);
    else
      user_delete($ban_user_id);
  }

if ($wash_user_id)
  {
    if (!user_exists($wash_user_id))
      fb(no_i18n("User not found"), 1);
    else
      {
        # Update the user spamscore field.
        db_execute("UPDATE user SET spamscore='0' WHERE user_id=?",
                   array($wash_user_id));

        # Previous comment flagged as spam will stay as such.
        # We just changed the affected user id that it won't affect this guy
        # any more.
        # We assume that message flagged as spam really were.
        # (we may change that in the future, depending on user experience).
        db_execute("UPDATE trackers_spamscore SET affected_user_id='100' "
                   . "WHERE affected_user_id=?", array($wash_user_id));
      }
  }

site_admin_header(array('title' => no_i18n("Monitor Spam"),
                        'context' => 'admhome'));

print '<h2>' . html_anchor(no_i18n("Suspected users"), "users_results") . '</h2>
<p>' . no_i18n("Follow the list of users that post content that as been flagged
as spam, ordered by their spam score. If the user is an obvious spammer, you
can ban him immediately. If it was flagged by mistake, you can wash his
reputation.") . ' <span class="warn">'
. no_i18n("Banning an user is a one-way-ticket
process. Be careful. For efficiency purpose, there won't be any
warnings.") . "</span></p>\n";

$title_arr = [
  no_i18n("User"), no_i18n("Score"), no_i18n("Ban user"),
  no_i18n("Wash score"), no_i18n("Incriminated content"),
  no_i18n("Flagged by")
];

if (empty ($max_rows))
  $max_rows = 50;

if (empty ($offset))
  $offset = 0;
$offset = intval($offset);

$result = db_execute("SELECT user_name,realname,user_id,spamscore FROM user "
                     ."WHERE status='A' AND spamscore > 0 ORDER BY spamscore "
                     ."DESC LIMIT ?,?", array($offset,($max_rows+1)));
if (!db_numrows($result))
  {
    print '<p>' . no_i18n("No suspects found") . "</p\n";
    $HTML->footer(array());
    exit (0);
  }

print html_build_list_table_top($title_arr);

$i = 0;
while ($entry = db_fetch_array($result))
  {
    $i++;

    # The sql was artificially asked to search more result than the number
    # we print. If $i > $max, it means that there were more results than
    # the max, we shan't print these more, but below we will add next/prev
    # links.
    if ($i > $max_rows)
      break;

    $res_score = db_execute("SELECT trackers_spamscore.artifact,"
                            . "trackers_spamscore.item_id,"
                            . "trackers_spamscore.comment_id,"
                            . "user.user_name FROM trackers_spamscore,user "
                            . "WHERE trackers_spamscore.affected_user_id=? "
                            . "AND user.user_id="
                            . "trackers_spamscore.reporter_user_id "
                            . "LIMIT 50", array($entry['user_id']));
    $flagged_by = '';
    $incriminated_content = '';
    $seen_before = array();
    while ($entry_score = db_fetch_array($res_score))
      {
        if (!isset($seen_before[$entry_score['user_name']]))
          {
            $flagged_by .= utils_user_link($entry_score['user_name']).', ';
            $seen_before[$entry_score['user_name']] = true;
          }

        if (!isset($seen_before[$entry_score['artifact']
                   . $entry_score['item_id']
                   . 'C' . $entry_score['comment_id']]))
          {
            # Only put the string "here" for each item, otherwise it gets
            # overlong when we have to tell comment #nnn of item #nnnn.
            $incriminated_content .= utils_link($GLOBALS['sys_home']
                                  . $entry_score['artifact'] . '/?item_id='
                                  . $entry_score['item_id']
                                  . '&amp;func=viewspam&amp;comment_internal_id='
                                  . $entry_score['comment_id'] . '#spam'
                                  . $entry_score['comment_id'],
                                               no_i18n("here")) . ', ';
            $seen_before[$entry_score['artifact'] . $entry_score['item_id']
                         . 'C' . $entry_score['comment_id']] = true;
          }
      }
    $flagged_by = rtrim($flagged_by, ', ');
    $incriminated_content = rtrim($incriminated_content, ', ');

    print '<tr class="' . utils_get_alt_row_color($i) . '">';
    print '<td width="25%">'
. utils_user_link($entry['user_name'], $entry['realname'])
. '</td>
<td width="5%" class="center">' . $entry['spamscore'] . '</td>
<td width="5%" class="center">'
. utils_link(htmlentities ($_SERVER['PHP_SELF']) . '?ban_user_id='
             . $entry['user_id'] . '#users_results',
             '<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
             . '.theme/misc/trash.png" alt="' . no_i18n("Ban user") . '" />')
. '</td>
<td width="5%" class="center">'
. utils_link(htmlentities ($_SERVER['PHP_SELF']) . '?wash_user_id='
             . $entry['user_id'] . '#users_results',
             '<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
             . '.theme/bool/ok.png" alt="' . no_i18n("Wash score") . '" />')
. '</td>
<td width="30%">' . $incriminated_content . '</td>
<td width="30%">' . $flagged_by . '</td>
</tr>
';
  }
print "</table>\n";

# More results than $max? Print next/prev.
html_nextprev(htmlentities ($_SERVER['PHP_SELF']).'?', $max_rows, $i, "users");
$HTML->footer(array());
?>
