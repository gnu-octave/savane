<?php
# Tracker transltion functions.
#
# Copyright (C) 2004-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2004-2006 Mathieu Roy <yeupou---gnu.org>
# Copyright (C) 2017, 2018, 2020 Ineiev
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

# Get an array of fields that should be updated on field transitions.
function trackers_transition_get_update($group_id)
{
  $field_transition = array();
  $field_transition_result = db_execute(
    "SELECT transition_id,field_id,from_value_id,to_value_id,is_allowed,"
     ."notification_list
     FROM trackers_field_transition
     WHERE group_id=? AND artifact=?",
    array($group_id, ARTIFACT));
  if ($field_transition_result && db_numrows($field_transition_result) > 0)
    {
      while ($this_transition = db_fetch_array($field_transition_result))
	{
	  $field_id = $this_transition['field_id'];

	  if (!array_key_exists($field_id, $field_transition))
	    { $field_transition[$field_id] = array(); }

	  $from = $this_transition['from_value_id'];
	  if ($from == "0")
	    { $from = "any"; }
	  if (!array_key_exists($from, $field_transition[$field_id]))
	    { $field_transition[$field_id][$from] = array(); }

	  $to = $this_transition['to_value_id'];
	  if (!array_key_exists($to, $field_transition[$field_id][$from]))
	    { $field_transition[$field_id][$from][$to] = array();  }

	  $field_transition[$field_id][$from][$to]['transition_id'] =
            $this_transition['transition_id'];
	  $field_transition[$field_id][$from][$to]['allowed'] =
            $this_transition['is_allowed'];
	  $field_transition[$field_id][$from][$to]['notification_list'] =
            $this_transition['notification_list'];
	}
    }
  return $field_transition;
}

# Return an array the other field update pair field/value for a given transition.
# No such transition, no update planned? Return false.
function trackers_transition_get_other_field_update ($transition_id)
{
  $result = db_execute("SELECT update_field_name,update_value_id
      FROM trackers_field_transition_other_field_update
      WHERE transition_id=?",
    array($transition_id));
  if (!db_numrows($result))
    { return false; }

  # returning an array does not work afterward in while statements. the current
  # workaround is to return the result as it is. It is ugly, feel free to
  # improve.
  return $result;
}

# For a given transition, add/remove/update and "other field update", if necessary.
function trackers_transition_update_other_field ($transition_id, $field_name,
                                                 $value_id)
{
  # If value_id is equal to 0, we are in the delete case
  if ($value_id == 0)
    {
      # We do not set LIMIT 1: if there were several entries for the same transition and
      # field name, it was a bug anyway.
      if (db_affected_rows(db_execute("DELETE
              FROM trackers_field_transition_other_field_update
              WHERE transition_id=? AND update_field_name=?",
            array($transition_id, $field_name))) > 0)
	{
	  fb(_("Other Field update deleted"));
	  return true;
	}

      fb_dberror();
      return false;
    }

      fb("other $transition_id, $field_name, $value_id", 1);
  # Otherwise, we first check if there is such "other field update configured"
  # and do INSERT or UPDATE accordingly.
  $result = db_execute(
      "SELECT other_field_update_id
       FROM trackers_field_transition_other_field_update
       WHERE transition_id=? AND update_field_name=? LIMIT 1",
      array($transition_id, $field_name));
  if (db_numrows($result) > 0)
    $id = db_result($result, 0, 'other_field_update_id');
  else
    $id = null;
  if ($id)
    {
      $sql_res = db_execute(
	"UPDATE trackers_field_transition_other_field_update
         SET update_value_id=? WHERE other_field_update_id=?",
	array($value_id, $id));
    }
  else
    {
      $sql_res = db_autoexecute('trackers_field_transition_other_field_update',
        array (
	  'transition_id' => $transition_id,
	  'update_field_name' => $field_name,
	  'update_value_id' => $value_id
        ), DB_AUTOQUERY_INSERT);
    }
  if (db_affected_rows($sql_res))
    {
      fb_dbsuccess();
      return true;
    }

  fb_dberror();
  return false;
}

# For a given array of transitions and one item id, update other fields.
# It must check, before updating a field, that no other update was made before.
# It will also follow the first update configured found, if there are
# configuration conflicts.
function trackers_transition_update_item ($item_id, $transition_id_array,
                                          $changes)
{
  # Array in which we ll store field to updates.
  $toupdate = array();

  # Extract transitions updates
  if (is_array($transition_id_array))
    {
      foreach ($transition_id_array as $transition_id)
	{
	  # Make sure we have a valid entry.
	  if (!$transition_id)
	    { continue; }

	  # Get list of register updated for this transition.
	  $registered = trackers_transition_get_other_field_update($transition_id);

          # No result? Skip it.
	  if (!$registered)
	    { continue; }
	  else
	    {
	      # Run the list of registered updates for this transition.
	      while ($update = db_fetch_array($registered))
		{
                  # Skip it if it already on the list to be changed.
		  if ((is_array($changes)
                      && !array_key_exists($update['update_field_name'],
                                           $changes))
                      && !array_key_exists($update['update_field_name'],
                                           $toupdate))
		    {
		      # Add to the list of planned updates
		      $toupdate[$update['update_field_name']] =
                        $update['update_value_id'];
		      # If we close the item, update the closed_date field
		      if  ($update['update_field_name'] == 'status_id'
			   && $update['update_value_id'] == '3')
			{
			   $toupdate['close_date'] = time();
			}
		    }
		}
	    }
	}

      # Now update fields.
      $upd_list = array();
      $exists = false;
      foreach ($toupdate as $field => $value)
	{
	  if ($value)
	    {
	      trackers_data_add_history($field,
					'transition-other-field-update',
					$value,
					$item_id);
	      # Put some feedback: do not mention internal fields like
	      # 'closed on'.
	      if ($field != 'close_date')
		{
		  fb(
# TRANSLATORS: the argument is field name.
          sprintf(_("Automatic update of %s due to transitions settings"),
                  trackers_data_get_label($field)));
		}
	      $upd_list[$field] = $value;
	      $exists = true;
	    }
	}

      if ($exists)
	{
	  # Update database silently, we may have no rows to update.
	  db_autoexecute(ARTIFACT, $upd_list, DB_AUTOQUERY_UPDATE,
			 "bug_id=?", array($item_id));
	}
    }
  return true;
}
?>
