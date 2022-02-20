<?php
# Vote-related functions.
#
# Copyright (C) 2005 Mathieu Roy <yeupou--gnu.org>
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


# Count the remaining votes of a given user
function trackers_votes_user_remains_count ($user_id)
{
  $total = 100;
  $result = db_execute("SELECT howmuch FROM user_votes WHERE user_id = ?",
		       array($user_id));

  if (db_numrows($result))
    {
      while ($row = db_fetch_array($result))
	{
	  $total = $total - $row['howmuch'];
	}
    }

  # Total < 0 does not make sense
  if ($total < 0)
    {
      fb(_("You appear to have less than 0 votes remaining. There's a bug
somewhere, please contact the administrators"), 1);
    }
  return $total;
}

# Count the number of vote of a given user of a given item
function trackers_votes_user_giventoitem_count ($user_id, $tracker, $item_id)
{
  $total = 0;
  $result = db_execute("SELECT howmuch FROM user_votes WHERE user_id=?
                        AND tracker=? AND item_id=? LIMIT 1",
		       array($user_id, $tracker, $item_id));

  if (db_numrows($result))
    {
      $total = db_result($result, 0, 'howmuch');
    }
  return $total;
}

# Update the database: add / update votes
function trackers_votes_update ($item_id, $group_id=0, $new_vote, $tracker=null)
{
  # If the tracker is undefined, use the constant,
  if (!$tracker)
    $tracker = ARTIFACT;

  # If group_id is not known, we guess it.
  if (!$group_id)
    {
      $res_getgroupid = db_execute("SELECT group_id FROM ".$tracker
                                   ." WHERE bug_id=?",
				   array($item_id));
      $group_id = db_result($res_getgroupid, 0, 'group_id');
    }

  # If the user already voted for this item:
  #   - if he voted 0, we must simply remove the vote
  #   - if he voted something else, we must add or remove the diff

  # Vote = 0
  if ($new_vote < 1)
    {
      $registered_vote = trackers_votes_user_giventoitem_count(user_getid(),
                                                               $tracker,
                                                               $item_id);
      if ($registered_vote)
	{
	  db_execute("DELETE FROM user_votes WHERE user_id=?
                      AND tracker=? AND item_id=? LIMIT 1",
		     array(user_getid(), $tracker, $item_id));
	  $res_get = db_execute("SELECT vote FROM ".$tracker
                                ." WHERE bug_id=? AND group_id=?",
				array($item_id, $group_id));
	  $real_new_vote = db_result($res_get, 0, 'vote') - $registered_vote;
	  db_execute("UPDATE ".$tracker
                     ." SET vote=? WHERE bug_id=? AND group_id=?",
		     array($real_new_vote, $item_id, $group_id));

	  fb(_("Vote erased"));
	}
      return false;
    }
  else
    {
      # Vote > 0

      # Check the diff between the registered vote and the new vote
      $registered_vote = trackers_votes_user_giventoitem_count(user_getid(),
                                                               $tracker,
                                                               $item_id);
      $diff_vote = $new_vote - $registered_vote;

      # If new vote equal to the current vote, nothing to do
      if (!$diff_vote)
	{
	  return true;
	}

      # Check whether the user have not specified more votes than he actually
      # got available
      $remains = trackers_votes_user_remains_count(user_getid());
      if ($remains < $diff_vote)
	{
          # If so, set the diff_vote and new_vote as the maximum possible
          $diff_vote = $remains;
	  $new_vote = $diff_vote + $registered_vote;
	}

      # If the vote is new, we do a SQL INSERT, otherwise a SQL UPDATE
      # in the user_votes table
      if (!$registered_vote)
	{
	  $res_insert = db_autoexecute('user_votes',
            array(
 	      'user_id' => user_getid(),
              'tracker' => $tracker,
	      'item_id' => $item_id,
	      'howmuch' => $new_vote
            ), DB_AUTOQUERY_INSERT);

	  trackers_add_cc($item_id,
			  $group_id,
			  user_getname(),
			  "-VOT-");
	}
      else
	{
	  $res_insert = db_execute("UPDATE user_votes SET howmuch=?
              WHERE user_id=? AND tracker=? AND item_id=?",
	    array($new_vote, user_getid(), $tracker, $item_id));
	}

      if (db_affected_rows($res_insert) < 1)
	{
	  # In case of problem, kept unmodified the item proper info
	  fb(_("Unable to record the vote, please report to admins"), 1);
	  return false;
	}

      # Add the new vote to the item proper info table
      $res_get = db_execute("SELECT vote FROM ".$tracker
                            ." WHERE bug_id=? AND group_id=?",
			    array($item_id, $group_id));
      $real_new_vote = db_result($res_get, 0, 'vote') + $diff_vote;
      $res_update = db_execute("UPDATE ".$tracker
                               ." SET vote=? WHERE bug_id=? AND group_id=?",
			       array($real_new_vote, $item_id, $group_id));
      if (db_affected_rows($res_update) < 1)
	{
	  # In case of problem, kept unmodified the item proper info
	  fb(_("Unable to finally record the vote, please report to admins"), 1);
	  return false;
	}

      # If we arrive here, everything went properly
      if ($diff_vote > 0)
	{ $diff_vote = "+$diff_vote"; }
      fb(_("Vote recorded")." ($diff_vote)");
      return true;
    }
}
?>
