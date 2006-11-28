<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2004-2006 (c) Yves Perrin <yves.perrin--cern.ch>
#                          Mathieu Roy <yeupou---gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


# Get an array of fields that should be updated on field transitions
function trackers_transition_get_update($group_id)
{
  $field_transition = array();
  $field_transition_sql = "SELECT transition_id,field_id,from_value_id,to_value_id,is_allowed,notification_list ".
     "FROM trackers_field_transition ".
     "WHERE group_id='".$group_id."' AND artifact='".ARTIFACT."' ";
  
  $field_transition_result = db_query($field_transition_sql);
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
	  
	  $field_transition[$field_id][$from][$to]['transition_id'] = $this_transition['transition_id'];
	  $field_transition[$field_id][$from][$to]['allowed'] = $this_transition['is_allowed'];
	  $field_transition[$field_id][$from][$to]['notification_list'] = $this_transition['notification_list'];
	}
    }
  return $field_transition;
}



# Return as an array the other field update pair field/value for a given transition.
# No such transition, no update planned? Return false
function trackers_transition_get_other_field_update ($transition_id)
{
  $result = db_query("SELECT update_field_name,update_value_id FROM trackers_field_transition_other_field_update WHERE transition_id='".$transition_id."'");
  if (!db_numrows($result))
    { return false; }

  # returning an array does not work afterward in while statements. the current workaround is
  # to return the result as it is. It is ugly, feel free to improve.
  return $result;
  #return db_fetch_array($result);
}

# For a given transition, add/remove/update and "other field update", if necessary.
function trackers_transition_update_other_field ($transition_id, $field_name, $value_id)
{
  # If value_id is equal to 0, we are in the delete case
  if ($value_id == 0)
    {
      # We do not set LIMIT 1: if there were several entries for the same transition and 
      # field name, it was a bug anyway.
      if (db_affected_rows(db_query("DELETE FROM trackers_field_transition_other_field_update WHERE transition_id='$transition_id' AND update_field_name='$field_name'")) > 0)
	{ 
	  fb(_("Other Field update deleted")); 
	  return true;
	}
      
      fb_dberror(); 
      return false;
    }

      fb("other $transition_id, $field_name, $value_id", 1);
  # Otherwise, we first check if there is such "other field update configured" and do
  # INSERT or UPDATE accordingly
  $id = db_result(db_query("SELECT other_field_update_id FROM trackers_field_transition_other_field_update WHERE transition_id='$transition_id' AND update_field_name='$field_name' LIMIT 1"),
		  0,
		  'other_field_update_id');
  if ($id)
    { 
      $sql = "UPDATE trackers_field_transition_other_field_update SET update_value_id='$value_id' WHERE other_field_update_id='$id'";
    }
  else
    {
      $sql = "INSERT INTO trackers_field_transition_other_field_update (transition_id,update_field_name,update_value_id) VALUES ('$transition_id','$field_name','$value_id')";
    }
  if (db_affected_rows(db_query($sql)))
    { 
      fb_dbsuccess(); 
      return true;
    }
  
  fb_dberror(); 
  return false;
}

# For a given array of transitions and one item id, update other fields.
# It must check, before updating a field, that no other update was made before.
# It will also follow the first update configured found, if there are configuration conflicts.
function trackers_transition_update_item ($item_id, $transition_id_array, $changes)
{
  # Array in which we ll store field to updates
  $toupdate = array();
  
  # Extract transitions updates
  if (is_array($transition_id_array))
    {
      while (list(,$transition_id) = each($transition_id_array))
	{
	  # Make sure we have a valid entry
	  if (!$transition_id)
	    { continue; }

	  # Get list of register updated for this transition
	  $registered = trackers_transition_get_other_field_update($transition_id);
      
          # No result? skip it
	  if (!$registered)
	    { continue; }
	  else
	    {
	      # Run the list of registered updates for this transition
	      while ($update = db_fetch_array($registered))
		{
                  # Skip it if it already on the list to be changed
		  if ((is_array($changes) && !array_key_exists($update['update_field_name'], $changes)) &&
		      !array_key_exists($update['update_field_name'], $toupdate))
		    {
		      # Add to the list of planned updates
		      $toupdate[$update['update_field_name']] = $update['update_value_id'];
		      # If we close the item, update the closed_date field
		      if  ($update['update_field_name'] == 'status_id' &&
			   $update['update_value_id'] == '3')
			{
			   $toupdate['close_date'] = time();
			}
		      
		    }
		}
	    }

	}

      # Now update fields
      unset($upd_list);
      while (list($field,$value) = each($toupdate))
	{
	  if ($value) 
	    {
	      trackers_data_add_history($field,
					'transition-other-field-update',
					$value,
					$item_id);
	      # Put some feedback: do not mention internal fields like
	      # 'closed on'
	      if ($field != 'close_date')
		{
		  fb(sprintf(_("Automatic update of %s due to transitions settings"),trackers_data_get_label($field)));
		}
	      $upd_list .= "$field='$value',";
	      $exists = 1;
	    }
	}
      
      if ($exists)
	{
	  # Update database silently, we may have no rows to update
	  db_query("UPDATE ".ARTIFACT." SET ".trim($upd_list, ",")." WHERE bug_id='$item_id'");
	  
	}
	  

    }

  return true;
}

?>