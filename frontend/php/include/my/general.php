<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


# Show hidden or visible a list of items depending on user prefs
# Set prefs if a changed was asked
function my_hide_url ($role, 
		      $group_id, 
		      $count, 
		      $link="")
{
/*
  Function that generates hide/show urls to expand/collapse
  sections of the personal page

Input:
  $hide = hide param as given in the script URL (-1 means no param was given)

Output:
  $hide_url: URL to use in the page to switch from hide to show or vice versa
  $count_diff: difference between the number of items in the list between now and
     the previous last time the section was open (can be negative if items were removed)
  $hide_flag: true if the section must be hidden, false otherwise

*/

  # Determine if we should hide or not
  $hide = my_is_hidden($role, $group_id);

  # Compare with preferences, update preference if not equal
  $pref_name = 'my_hide_'.$role.$group_id;
  $old_pref_value = user_get_preference($pref_name);
  list(,$old_count) = explode("|", $old_pref_value);
  $pref_value = "$hide|$count";
  if ($old_pref_value != $pref_value)
    {
      user_set_preference($pref_name, $pref_value);
    }

  # Determine the relevant content (title with a + or a -)
  if ($hide)
    {
      $hide_url= '<a name="'.$role.$group_id.'" href="'.$_SERVER['PHP_SELF'].'?hide_'.$role.'=0&amp;hide_group_id='.$group_id.'#'.$role.$group_id.'"><span class="minusorplus">(+)</span>'.$link.'</a>';
    }
  else
    {
      $hide_url= '<a name="'.$role.$group_id.'" href="'.$_SERVER['PHP_SELF'].'?hide_'.$role.'=1&amp;hide_group_id='.$group_id.'#'.$role.$group_id.'"><span class="minusorplus">(-)</span>'.$link.'</a>';
    }

  # Return everything
  return array($hide, $count-$old_count, $hide_url);
}

# Determine whether a given group items of a given role should be hidden or not
function my_is_hidden ($role, $group_id)
{
  # Extract user prefs
  # No pref? Then assume we do not want to hide.
  $pref_name = 'my_hide_'.$role.$group_id;
  $old_pref_value = user_get_preference($pref_name);
  if ($old_pref_value)
    { list($old_hide,) = explode('|', $old_pref_value); }
  else
    { $old_hide = 0; }

  # Extract url arguments
  $asked_to_hide_group = sane_get("hide_group_id");
  $asked_to_hide_role = sane_isset("hide_$role");

  # The user asked to change something for this role and this group,
  # return exactly what he asked for
  if ($asked_to_hide_group == $group_id && 
      $asked_to_hide_role)
    {
      return sane_get("hide_$role");
    }

  # No related change, return the pref
  return $old_hide;
}

function my_format_as_flag($assigned_to, $submitted_by)
{
  $AS_flag = '';
  if ($assigned_to == user_getid())
    {
      $AS_flag = 'A';
    }
  if ($submitted_by == user_getid())
    {
      $AS_flag .= 'S';
    }
  if ($AS_flag) { $AS_flag = '[<strong>'.$AS_flag.'</strong>]'; }

  return $AS_flag;
}

function my_item_count($total, $new)
{
  $ret = '(';
  $ret .= sprintf(ngettext("%s item", "%s items", $total), $total);
  if ($new)
    {
      $ret .= ", <strong>".sprintf(ngettext("%s new", "%s new", $new), $new)."</strong>";
    }
  $ret .= ')';
  return $ret;
}


# Function that expect item_data and $group_data to exist as globals,
# so we can avoid
# doing hundred of time the same SQL requests.
# Indeed, it is safe only as register_globals_off() is used on my/ pages
# and since it is reinitialized at the begin of these pages.
function my_item_list ($role="assignee", $threshold="5", $openclosed="open", $uid=0, $condensed=0) {
  global $item_data, $group_data, $items_per_groups, $maybe_missed_rows;
  $items_per_groups = array();
  
  $maybe_missed_rows = 0;
  
  # We first need to run one sql per tracker for the current role
  # Some roles (currently only "newlyassigned") require more than one
  # sql to extract data (otherwise it would require very complex and slow
  # sqls)
  # Note: this is no longer true, as we deactivated the CPU consuming
  # search on old items of newlyassigned
  #if ($role != "newlyassigned")
  #  {
    $roles = array($role);
  #  }
  #else
  #  {
      # FIXME: newlyassigned-olditems IS TOO SLOW, SQL request takes hours
      #$roles = array("newlyassigned-newitems", "newlyassigned-olditems");
  #    $roles = array("newlyassigned-newitems");
  #  }
   
  
  while (list(, $currentrole) = each($roles))
    {
      $trackers = array("support", "bugs", "task", "cookbook", "patch");
      while (list(, $currenttracker) = each($trackers))
	{
	  unset($sql);

	  # Create the SQL request
	  $sql = my_item_list_buildsql($currenttracker, $currentrole, $threshold, $openclosed, $uid);

	  # Ignores if not able to produce a SQL (maybe because the user
	  # have no relevant rights, whatever)
	  if (!$sql)
	    { continue; }

	  # Feed the hashes that contains data
	  my_item_list_extractdata($sql, $currenttracker);

	}

    }
  
  my_item_list_print($role, $openclosed, $condensed);
}


# Build sql request depending on what we are looking for
function my_item_list_buildsql ($tracker, $role="assignee", $threshold="5", $openclosed="open", $uid=false) {
  global $item_data, $group_data, $sql_limit, $usergroups, $usergroups_groupid, $items_per_groups, $usersquads;

  # status: 1 = open, 3 = closed
  if ($openclosed == "open") 
    { $openclosed = 1; }
  if ($openclosed == "closed") 
    { $openclosed = 3; }


  # Max items: defines to 50 by default
  # (meaning 50 x trackers for each list = 200 items)
  # This is important to save CPU resources
  # This variable is set as global to able to afterwards check if we hit
  # max results or not.
  $sql_limit = 50;

  # threshold: based on priority

  # by defaut, consider we are printing items of the current user
  # if not, we want to ignore private items
  $showprivate = '';
  if (!$uid)
    { $uid = user_getid(); }
  else
    {
      $showprivate = ' AND privacy<>2 ';
    }

  
  # Get a timestamp to get new items (15 days)
  $new_date_limit = mktime(date("H"),
			   date("i"),
			   0,
			   date("m"),
			   date("d")-15,
			   date("Y"));
  
  # FIXME: should we put a SQL LIMIT, to avoid cases of users that would
  # have tons of items, with a meaningful error message?
  if ($role == "assignee" || $role == "submitter")
    {
      ## Items listing in My Items:
      ##      assigned to and posted by
      $select = 'SELECT '.$tracker.'.bug_id,'.$tracker.'.date,'.$tracker.'.priority,'.$tracker.'.resolution_id,'.$tracker.'.summary,groups.group_id,groups.group_name,groups.unix_group_name ';
      $from = 'FROM '.$tracker.',groups ';
      $where = 'WHERE groups.group_id='.$tracker.'.group_id '.
	'AND '.$tracker.'.status_id='.$openclosed.' '.
	'AND ('.$tracker.'.priority >= '.$threshold.' OR  '.$tracker.'.date > '.$new_date_limit.') '.$showprivate;

      if ($role == "assignee")
	{
	  $where .= 'AND ('.$tracker.'.assigned_to='.$uid.' '; 

          # If the user is member of squads, add them now
	  reset($usersquads);
	  foreach ($usersquads as $squad_id) 
	    { $where .= 'OR '.$tracker.'.assigned_to='.$squad_id.' '; }	    

	  $where .= ' ) ';

	}
      else
	{
          # If the submitter is also the owner, we ll show it in
	  # the assigned
          # list, which matters more than the fact he is submitter
	  $where .= 'AND '.$tracker.'.assigned_to<>'.$uid.' AND '.$tracker.'.submitted_by='.$uid.' ';
	}
      

      # 1. Restrict to groups the users belongs to  
      # 2. Do a simple SQL count if the group is supposed to be hidden
      $restrict_to_groups = '';
      reset($usergroups_groupid);
      while (list(,$current_group_id) = each($usergroups_groupid))
	{
	  if (!my_is_hidden($role, $current_group_id))
	    {
	      # When we look for items the user submitted, we do not restrict
	      # groups
	      if ($role == "submitter")
		{ continue; }

	      if ($restrict_to_groups)
		{ $restrict_to_groups .= ' OR '; }
              # Group is not supposed to be hidden
	      $restrict_to_groups .= ' '.$tracker.'.group_id="'.$current_group_id.'" ';
	    }      
	  else
	    {
	      # No restriction if we are not listing the items of the logged
	      # in user: we are not in page where items can be hidden
	      if ($uid != user_getid())
		{ continue; }
	      
	      # This group is supposed to be hidden, just do a count; do it 
	      # now.
	      $rows = db_numrows(db_query('SELECT count('.$tracker.'.bug_id) as count '.$from.' '.$where.' AND '.$tracker.'.group_id="'.$current_group_id.'" GROUP BY bug_id LIMIT '.$sql_limit));

	      # Feed the array so it nows exactly how many items we have
	      # (array_fill exists only in PHP 4.2)
	      for ($k=0; $k<$rows; $k++)
		{ $items_per_groups[$current_group_id][] = true; }


	      # When we look for items the user submitted, we do not restrict
	      # groups, if this one is supposed to be hidden, we have to 
	      # explicitely ignores it
	      if ($role == "submitter")
		{
		  if ($restrict_to_groups)
		    { $restrict_to_groups .= ' AND '; }
		  $restrict_to_groups .= ' '.$tracker.'.group_id<>"'.$current_group_id.'" ';
		}
	    }
	}

      # No SQL if not at least one project is not in hidden mode
      if (!$restrict_to_groups && $role == "assignee")
	{ return; }

      if ($restrict_to_groups)
	{ $restrict_to_groups = ' AND ('.$restrict_to_groups.') '; }

      # Complete the SQL
      $sql = $select.' '.$from.' '.$where.' '.$restrict_to_groups.' GROUP BY bug_id ORDER BY '.$tracker.'.date  DESC ';

    }
  else
    {
      ## Items listing in My Incoming Items:
      ##   recent unassigned items or recently assigned items
     
      
      if ($role == "unassigned") 
	{
	  
	  $select = 'SELECT '.$tracker.'.bug_id,'.$tracker.'.date,'.$tracker.'.priority,'.$tracker.'.resolution_id,'.$tracker.'.summary,groups.group_id,groups.group_name,groups.unix_group_name ';
	  $from = ' FROM '.$tracker.',groups ';
	  $where = 'WHERE groups.group_id='.$tracker.'.group_id '.
	    'AND '.$tracker.'.status_id=1 '.
	    'AND '.$tracker.'.date > '.$new_date_limit.' '.
	    'AND '.$tracker.'.assigned_to=100 ';
	  
	}
      else if ($role == "newlyassigned")
	{

	  # Incoming assigned items is a bit complex:
	  #   we want newly assigned item
          # that are in fact completely new items, 
	  # with no history, and assigned
          # item that may be very very old but
	  # that were assigned recently to the
          # user
	      
          # For newly assigned, set the limit to 25, as we do 2 SQL requests
          # per tracker
	  /* $sql_limit = 25; not required as olditems is deactivated */
	  
          # Handle the old item newly assigned
	  /*  if ($role == "newlyassigned-olditems")
	    {
	      # DEACTIVATED 
	      # TOO SLOW, too complicated

              # Now handle the old item newly assigned 
	      $sql .= '(('.$tracker.'_history.bug_id='.$tracker.'.bug_id '.
		'AND '.$tracker.'_history.new_value='.$uid.' '.
		'AND '.$tracker.'_history.old_value<>'.$uid.' '.
		'AND '.$tracker.'_history.mod_by<>'.$uid.' '.
		'AND '.$tracker.'.date < '.$new_date_limit.' '.
		'AND '.$tracker.'_history.field_name="assigned_to" '.
		'AND '.$tracker.'_history.date > '.$new_date_limit.') OR ';
	      
	    } */

          # Handle the others
	  /* if ($role == "newlyassigned-newitems")
	    { */
	  
	  $select = 'SELECT '.$tracker.'.bug_id,'.$tracker.'.date,'.$tracker.'.priority,'.$tracker.'.resolution_id,'.$tracker.'.summary,groups.group_id,groups.group_name,groups.unix_group_name ';
	  $from = ' FROM '.$tracker.',groups ';
	  $where = ' WHERE groups.group_id='.$tracker.'.group_id AND '.$tracker.'.status_id='.$openclosed.' AND ('.$tracker.'.assigned_to='.$uid;

          # If the user is member of squads, add them now
	  reset($usersquads);
	  foreach ($usersquads as $squad_id) 
	    { $where .= ' OR '.$tracker.'.assigned_to='.$squad_id; }	  
	  
	  $where .= ') AND ('.$tracker.'.date > '.$new_date_limit.' AND '.$tracker.'.submitted_by<>'.$uid.') ';

	      /*   } */

	}
      
      # Go thru the list of groups the user belongs to
      # to find out if any is relevant
      $restrict_to_groups = NULL;
      reset($usergroups_groupid);
      while (list(,$current_group_id) = each ($usergroups_groupid))
	{
	  if ($role == "unassigned")
	    {
              # For unassigned items, we must ignore all trackers the user
	      # is not a manager of
	      if (!member_check(0, $current_group_id, member_create_tracker_flag($tracker).'3'))
		{
		  continue;

		}
	    }

	  
	  if (!my_is_hidden($role, $current_group_id))
	    {

	      # This group will be shown
	      if ($restrict_to_groups) 
		{ $restrict_to_groups .= "OR "; }

	      $restrict_to_groups .= " $tracker.group_id='".$current_group_id."' ";
	    }
	  else
	    {
	      # This group is supposed to be hidden, just do a count; do it 
	      # now.
	      $rows = db_numrows(db_query('SELECT count('.$tracker.'.bug_id) as count '.$from.' '.$where.' AND '.$tracker.'.group_id="'.$current_group_id.'" GROUP BY bug_id LIMIT '.$sql_limit));

	      # Feed the array so it nows exactly how many items we have
	      # (array_fill exists only in PHP 4.2)
	      for ($k=0; $k<$rows; $k++)
		{ $items_per_groups[$current_group_id][] = true; }

	    }
	}
      
      /* if ($role == "newlyassigned-olditems")
	{
	  $sql .= ') GROUP BY bug_id ORDER BY '.$tracker.'_history.date DESC ';
	} */


      # No SQL if not at least one project is not in hidden mode
      if (!$restrict_to_groups)
	{ return; }

      # Complete the SQL
      $sql = $select.' '.$from.' '.$where.' AND ('.$restrict_to_groups.') GROUP BY bug_id ORDER BY '.$tracker.'.date,'.$tracker.'.bug_id DESC ';
      
    }

  # Return the sql
  return $sql." LIMIT ".$sql_limit;
}

# Extract items data from database, put in hashes
function my_item_list_extractdata ($sql, $tracker) {
  global $item_data, $group_data, $items_per_groups, $sql_limit, $maybe_missed_rows;

  # Run the query
  $result=db_query($sql);
  $rows=db_numrows($result);

  # Record for later if we maybe missed items
  if ($sql_limit <= $rows)
    { $maybe_missed_rows = 1; }

  # If there are results, grab data
  if ($result && $rows > 0)
    {
      $items_exist = 1;
      for ($j=0; $j<$rows; $j++)
	{
          # Create item unique name beginning by the date to ease
	  # sorting
	  $thisitem = db_result($result, $j, 'date').'.'.$tracker.'#'.db_result($result,$j,'bug_id');
	  $thisgroup = db_result($result,$j,'group_id');
	      
	  # Associate to the group
          # (ignore if it was already done)	      
	  if (array_key_exists($thisgroup, $items_per_groups)
	      && is_array($items_per_groups[$thisgroup])
	      && array_key_exists($thisitem, $items_per_groups[$thisgroup]))
	    { continue; }
	  $items_per_groups[$thisgroup][$thisitem] = true;

	  # Store data
	  # (ignore if already found)    
	  if (array_key_exists('item_id', $item_data)
	      && is_array($item_data['item_id'])
	      && array_key_exists($thisitem, $item_data['item_id']))
	    { continue; }
	  
	  $item_data['item_id'][$thisitem] = db_result($result,$j,'bug_id');
	  $item_data['tracker'][$thisitem] = $tracker;
	  $item_data['date'][$thisitem] = db_result($result,$j,'date');
	  $item_data['priority'][$thisitem] = db_result($result,$j,'priority');
	  $item_data['status'][$thisitem] = db_result($result,$j,'resolution_id');
	  $item_data['summary'][$thisitem] = db_result($result,$j,'summary');
	}
    }

  # Return the number of rows found
  return $rows;
}

# Print a list of data from what was in the hash
function my_item_list_print ($role="assignee", $openclosed="open", $condensed=false) {
  global $item_data, $group_data, $items_per_groups, $maybe_missed_rows;

  if ($openclosed == "closed") 
    { $openclosed = 3; }

  # Break here if we have no results
  if (count($items_per_groups) < 1)
    {
      print _("None found");
      return false;
    }

  # If when doing the SQL, we found as many result as possible with the
  # SQL limits, we may have missed others items because they are too many
  if ($maybe_missed_rows)
    { 
      print '<div class="boxitem"><span class="xsmall"><span class="warn">'._("We found many items that match the current criteria. We had to set a limit at some point, some items that match the criteria may be missing for this list.").'</span></span></div>'; 
      if (!$condensed)
	{
	  print '<br />';
	}
      
    }
  


  # Go thru the group list
  ksort($items_per_groups);
  while (list($current_group_id, $current_group_items) = each($items_per_groups))
    {

      # Obtain the group fullname
      if (!array_key_exists("group".$current_group_id, $group_data))
	{
	  $group_data["group".$current_group_id] = 
	    group_getname($current_group_id);
	}

      # Print subtitle
      if (!$condensed)
	{
	  $count = count($current_group_items);
	  list($hide_now,$count_diff,$hide_url) = 
	    my_hide_url($role,
			$current_group_id,
			$count,
			'<strong>'.$group_data["group".$current_group_id].'</strong>');
	  print '<div class="'.utils_altrow(1).'"> '.$hide_url.' <span class="smaller">'.my_item_count($count,max(0, $count_diff)).'</span></div>';
	}
      else
	{
	  # In condensed mode, there is no hide url
	  print '<div class="'.utils_altrow(1).'"> '.sprintf(_("%s: "), $group_data["group".$current_group_id]).'</div>';

	}

      # Go thru the item list, unless asked to hide
      if (!$hide_now)
	{
	  krsort($current_group_items);
	  while (list($thisitem,) = each($current_group_items))
	    {
	      $current_item_id = $item_data['item_id'][$thisitem];
	      $tracker = $item_data['tracker'][$thisitem];
	      $prefix = utils_get_tracker_prefix($tracker);
	      $icon = utils_get_tracker_icon($tracker);
	      

	      # Found out the status full text name:
   	      # this is project specific. If there is no project setup for this
	      # then go to the default for the site
	      if (!array_key_exists($current_group_id.
				    $tracker.
				    $item_data['status'][$thisitem],
				    $group_data))
		{
		  $group_data[$current_group_id.$tracker.$item_data['status'][$thisitem]] =
		    db_result(db_query("SELECT value FROM ".$tracker."_field_value WHERE bug_field_id='108' AND (group_id='".$current_group_id."' OR group_id='100') AND value_id='".$item_data['status'][$thisitem]."' ORDER BY bug_fv_id DESC LIMIT 1"), 0, 'value');
		}
	      $status = $group_data[$current_group_id.$tracker.$item_data['status'][$thisitem]];
	      
              # Print directly, to avoid putting too much things in memory
	      print '<div class="'.utils_get_priority_color($item_data['priority'][$thisitem], $openclosed).'">'.
		'<a href="'.$GLOBALS['sys_home'].$tracker.'/?'.$current_item_id.'" class="block">'.
		'<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/'.$icon.'.png" class="icon" alt="'.$tracker.'" /> '.
		stripslashes($item_data['summary'][$thisitem]).
		'&nbsp;<span class="xsmall">('.$prefix .' #'.$current_item_id.', '.$status.')</span></a></div>';
	      
	    }

	}
      
      # Add extra space to make the page easier to read
      if (!$condensed)
	{ print '<br />'; }
      
    }
}

?>
