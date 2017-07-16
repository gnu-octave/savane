<?php
# Handling spam.
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

# First, initialize some globals var: conffile allows two vals for
# admin conveniency.
if (!empty($GLOBALS['sys_spamcheck_spamassassin']))
  {
    if ($GLOBALS['sys_spamcheck_spamassassin'] == 1)
      $GLOBALS['sys_spamcheck_spamassassin'] = "anonymous";
    elseif ($GLOBALS['sys_spamcheck_spamassassin'] == 2)
      $GLOBALS['sys_spamcheck_spamassassin'] = "all";
  }

$GLOBALS['int_probablyspam'] = false;
$GLOBALS['int_delayspamcheck_comment_id'] = false;

# Function to mark a spam. This assume that checks on whether the user
# has proper rights have been made already.
function spam_flag ($item_id, $comment_id, $score, $group_id, $reporter_user_id=0)
{
  if (!$reporter_user_id)
    $reporter_user_id = user_getid();

  # Check if the reported havent flagged the incriminated comment already.
  $result = db_execute("SELECT id FROM trackers_spamscore WHERE item_id=?
                          AND artifact=? AND comment_id=? AND reporter_user_id=?",
                       array($item_id, ARTIFACT, $comment_id, $reporter_user_id));
  if (db_numrows($result))
    {
      fb(_("You already flagged this comment"), 1);
      return false;
    }

  # Find out who is the alleged spammer
  # (if comment_id = 0, then it is the item itself that is a spam).
  unset($affected_user_id);
  if ($comment_id)
    {
      # It is important to mention the field_name, to avoid malicious attempt
      # to mess with any other part of history, like svncommit entries.
      $affected_user_id = db_result(db_execute("SELECT mod_by FROM ".ARTIFACT
      ."_history WHERE bug_history_id=? AND field_name='details' AND bug_id=?",
                                               array($comment_id, $item_id)),
                                    0, 'mod_by');
    }
  else
    {
      $affected_user_id = db_result(db_execute("SELECT submitted_by FROM "
                                               .ARTIFACT." WHERE bug_id=?",
                                               array($item_id)),
                                    0, 'submitted_by');
    }

  # Affected user may be 100 (anonymous) or anything else but 0.
  # If it is zero, something went wrong, let assume the worse and stop here.
  if (!$affected_user_id)
    {
      fb(_("Not able to find out who submitted the alleged spam, stopping here"), 1);
      return false;
    }

  # If the affected user is member of the group that owns the item
  # assume that someone is trying to do something stupid. The code does not
  # allow to flag as spam items posted by projects members.
  if ($affected_user_id != 100 && member_check($affected_user_id, $group_id))
    {
      exit_permission_denied();
    }

  # Feed the spamscore table.
  db_autoexecute('trackers_spamscore',
                 array('score' => $score,
                       'affected_user_id' => $affected_user_id,
                       'reporter_user_id' => $reporter_user_id,
                       'artifact' => ARTIFACT,
                       'item_id' => $item_id,
                       'comment_id' => $comment_id),
                 DB_AUTOQUERY_INSERT);

  # Compute the score of the item.
  $newscore = spam_get_item_score($item_id, ARTIFACT, $comment_id);

  # If newscore equal to score (so it was null in first place)
  # and the affected user is anonymous, increment of 3, that is the default
  # for anonymous post. We fill the database only for real users with positive
  # scores.
  if ($affected_user_id == 100 && $newscore == $score)
    $newscore += 3;

  # Update the item spamscore fields.
  if ($comment_id)
    {
      db_execute("UPDATE ".ARTIFACT."_history SET spamscore=?
                  WHERE bug_history_id=? AND field_name='details'
                    AND bug_id=?",
                 array($newscore, $comment_id, $item_id));
    }
  else
    {
      # Get the current summary.
      $summary = db_result(db_execute("SELECT summary FROM ".ARTIFACT
                                      ." WHERE bug_id=?",
                                      array($item_id)),
                           0, 'summary');
      $discussion_lock = array();
      if ($newscore > 4)
        {
          if (strpos($summary, '[SPAM]') === FALSE)
            $summary = '[SPAM] '.$summary;
          $discussion_lock = array('discussion_lock' => 1);
        }

      db_autoexecute(ARTIFACT,
                     array_merge(array('spamscore' => $newscore,
                                       'summary' => $summary),
                                 $discussion_lock),
                     DB_AUTOQUERY_UPDATE,
                     'bug_id=?',
                     array($item_id));
    }

  fb(sprintf(_("Flagged (+%s, total spamscore: %s)"), $score, $newscore));

  # If the total spamscore is superior to 4, the content is supposedly
  # confirmed spam, then increment the user spamscore.
  if ($newscore < 5)
    return true;

  # If the affected_user_id is anonymous, end here, obviously we cannot
  # change any personal spamscore.
  # Set up however an IP-based ban.
  if ($affected_user_id == 100)
    {
      spam_banip($item_id, $comment_id, ARTIFACT);
      return true;
    }

  # If the reporter already flagged a message of this user, end here
  # (we do not want a single user being able to increment by more than one
  # another user spamscore).
  if (spam_get_user_score($affected_user_id, $reporter_user_id) > 1)
    return true;

  # If the reporter is not member of the project that owns the item,
  # not increment user spamscore.
  # FIXME: not sure about this ; as we increment the spamscore only if the
  # content is marked as spam, not if it is simply flagged once, we can
  # consider this to be safe enough.
  #if (!member_check($reporter_user_id, $group_id))
  #  { return true; }

  # Compute the score of the user.
  $userscore = spam_get_user_score($affected_user_id);

  # Update the user spamscore field.
  db_execute("UPDATE user SET spamscore=? WHERE user_id=?",
             array($userscore, $affected_user_id));

  # No feedback about this last part, one user spamscore is the kind of info
  # that belongs to site admins territory.
  return true;
}

# Mark that a spam is actually not one
# (allow to set the tracker, because this function may be called from
# siteadmin area).
function spam_unflag ($item_id, $comment_id, $tracker, $group_id)
{
  # Update the spamscore table.
  db_execute("DELETE FROM trackers_spamscore
              WHERE item_id=? AND comment_id=? AND artifact=?",
             array($item_id, $comment_id, $tracker));

  if (!ctype_alnum($tracker))
    util_die('Tracker is not valid (not alnum): ' . htmlescape($tracker));

  # Update the item spamscore fields.
  if ($comment_id)
    {
      db_execute("UPDATE ".$tracker."_history SET spamscore=0
                  WHERE bug_history_id=? AND field_name='details' AND bug_id=?",
                 array($comment_id, $item_id));
    }
  else
    {
      db_execute("UPDATE $tracker SET spamscore=0
                  WHERE bug_id=? AND group_id=?",
                 array($item_id, $group_id));
    }

}


# Return the total score of a user.
function spam_get_user_score ($user_id=0, $set_by_user_id=0)
{
  if (!$user_id)
    $user_id = user_getid();

  # Anonymous get always a score of 3 (requires two users to succesfully
  # mark as spam something, only one project member).
  if ($user_id == 100)
    return 3;

  $set_by_user_id_sql = '';
  $set_by_user_id_params = array();
  if ($set_by_user_id)
    {
      $set_by_user_id_sql = " AND reporter_user_id=?";
      $set_by_user_id_params = array($set_by_user_id);
    }

  # We cannot do a count because it does not allow us to use GROUP BY.
  $userscore = 0;
  $result = db_execute("SELECT score FROM trackers_spamscore "
                       ."WHERE affected_user_id=? $set_by_user_id_sql "
                       ."GROUP BY reporter_user_id",
                       array_merge(array($user_id), $set_by_user_id_params));
  while ($entry = db_fetch_array($result))
    {
      $userscore++;
    }
  return $userscore;
}

# Return the total score of an item.
function spam_get_item_score ($item_id, $tracker, $comment_id)
{
  $result = db_execute("SELECT score FROM trackers_spamscore "
                       ."WHERE item_id=? AND artifact=? AND comment_id=?",
                       array($item_id, $tracker, $comment_id));
  $newscore = 0;
  while ($entry = db_fetch_array($result))
    {
      $newscore += $entry['score'];
    }
  return $newscore;
}

# To be used when a comment or an item is created. It is not enough to
# update the spamscore field of $tracker and $tracker_history tables.
function spam_set_item_default_score ($item_id, $comment_id, $tracker, $score,
                                      $user_id)
{
  # Nothing to do for anonymous post, spam_flag will properly interpret
  # the fact that the default is not specifically set.
  if ($user_id == 100)
    return;

  # If the score is null, there is obviously nothing to do.
  if ($score < 1)
    return;

  # If the score means spam, fill the global that will be used later
  # to skip mail notif.
  if ($score > 4)
    $GLOBALS['int_probablyspam'] = true;

  # Otherwise, add a new entry in the database, without mentioning the
  # affected user: we want to set the default score for the item, not to
  # increment the user spamscore.
  # We mark the user as reporter, so it is clear where do come from the flag.
  db_autoexecute('trackers_spamscore',
                 array('score' => $score,
                       'reporter_user_id' => $user_id,
                       'artifact' => $tracker,
                       'item_id' => $item_id,
                       'comment_id' => $comment_id),
                 DB_AUTOQUERY_INSERT);
  fb(sprintf(_("Spam score of your post set to %s"), $score), 1);
}

# Put an item or a comment in temporary queue.
function spam_add_to_spamcheck_queue ($item_id, $comment_id, $tracker,
                                      $group_id, $current_score)
{
  assert('ctype_alnum($tracker)');
  # Useless if already considered as spam.
  if ($GLOBALS['int_probablyspam'])
    return false;

  # Check in config if we want to do such checks.
  if (!$GLOBALS['sys_spamcheck_spamassassin'])
    return false;

  # If user is member of the current group, stop anyway.
  if (member_check(0, $group_id))
    return false;

  # If logged in and we have to check only anonymous users, stop here.
  if ($GLOBALS['sys_spamcheck_spamassassin'] == "anonymous"
      && user_isloggedin())
    return false;

  # Otherwise, add to the queue and arbitrarily change spamscore.
  $date = time();
  $priority = 2;
  $newscore = ($current_score + 5);

  # If anonymous, increment priority (yes, it will be meaningless on sites
  # where only anonymous post are checked):
  # While we may consider giving the priority to logged in users for their
  # confort, we have to take into account that we need to start with post
  # that are the most likely to contain spams.
  if (!user_isloggedin())
    $priority++;

  # Fill the queue.
  db_execute("INSERT INTO trackers_spamcheck_queue "
             ."(artifact,item_id,comment_id,priority,date) VALUES "
             ."(?, ?, ?, ?, ?)",
             array($tracker, $item_id, $comment_id, $priority, $date));

  # We change only the item spamscore field, not the spamscore table:
  # it means that if any user unflag the item, it will be as if
  # there was no score yet.
  # (no discussion lock, update will generate notif if sent by users that
  # can skip this spam queue check - members, etc).
  if ($comment_id)
    {
      $result = db_execute("UPDATE ".$tracker."_history SET spamscore=?
                            WHERE bug_history_id=? AND field_name='details'
                            AND bug_id=?",
                           array($newscore, $comment_id, $item_id));
    }
  else
    {
      $result = db_execute("UPDATE ".$tracker." SET spamscore=? "
                           ."WHERE bug_id=? AND group_id=?",
                           array($newscore, $item_id, $group_id));
    }

  if (db_affected_rows($result))
    {
      fb(sprintf(
_("Spam score of your post set temporarily to %s, until it is checked by spam
filters"), $newscore), 1);
    }

  # Mail notif should be delayed.
  $GLOBALS['int_delayspamcheck_comment_id'] = $comment_id;
  $GLOBALS['int_delayspamcheck'] = true;

  return true;
}

# Function to set up an IP-based banned for a spammer that is anonymous.
# This wont prefer the banned IP to login and post content once logged in.
# So legitimate users have want to workaround a buggy spam flagging.
function spam_banip ($item_id, $comment_id, $tracker)
{
  # Fetch the IP by restricting to:
  # * anonymously posted content
  # * content posted during the last 6 hours
  $since =  mktime((date("H")-6),date("i"));

  assert('ctype_alnum($tracker)');

  if ($comment_id)
    {
      $result = db_execute("SELECT ip FROM ".$tracker."_history "
                           ."WHERE bug_history_id=? AND field_name='details' "
                           ."AND bug_id=? AND mod_by='100' AND date>=? LIMIT 1",
                           array($comment_id, $item_id, $since));
    }
  else
    {
      $result = db_execute("SELECT ip FROM ".$tracker." WHERE bug_id=? "
                           ."AND submitted_by='100' AND date>=? LIMIT 1",
                           array($item_id, $since));
    }

  $ip = null;
  if (db_numrows($result))
    $ip = db_result($result, 0, 'ip');
  # No rows? No IP found? Stop here.
  if (empty($ip))
    return false;

  # Now set up the ban.
  $until =  mktime((date("H")+6),date("i"));
  db_autoexecute('trackers_spamban',
                 array('ip' => $ip,
                       'date' => $until),
                 DB_AUTOQUERY_INSERT);
  fb("Poster IP is banned for a few hours");
  return true;
}

# Check if the current user is banned.
function spam_bancheck ()
{
  # Bans are effective only against anonymous users. We can easily track
  # down abusers that are authentified.
  if (user_isloggedin())
    return true;

  # Get DB content.
  $ip = $_SERVER['REMOTE_ADDR'];
  $result = db_execute("SELECT date FROM trackers_spamban WHERE ip=?", array($ip));

  # Return if not found.
  if (!db_numrows($result))
    return true;

  # If we get here, the user is in the blacklist. Return a message that
  # explain to the user he will be able to post if he logs in, but do not
  # tell him until when he is banned. We do not want to give up info that
  # would help to write clever spambots.

  # Log error.
  exit_log("rejected data from ".$ip." - found in savane spamban list");
  # Finally, block here.
  exit_error(_("Your IP address was banned for several hours due to spam
reports incriminating it. In the meantime, if you log in, you can work around
this ban. You should investigate about probable cause of spam reports
incriminating your IP."));
}
