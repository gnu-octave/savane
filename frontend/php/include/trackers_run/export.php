<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
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


register_globals_off();
extract(sane_import('post', array(
  'create',
  // Use the time as it was while the form was printed to the user
  'current_time',
  // Find out the relevant timestamp that will be used by the backend
  // to determine which job must be performed
  'date_mainchoice',
  'date_next_day', 'date_next_hour', 'date_frequent_hour', 'date_frequent_day',
  'sumORdet')));
extract(sane_import('request', array('update', 'report_id', 'advsrch',
  'form_id', 'report_id')));
extract(sane_import('get', array('delete', 'feedback')));

# use the wording "export job" to distinguish the job from the task that
# will help users to follow the job.
# Yes, job and task can be considered as synonym. But as long as we havent
# got such jobs completely managed via the task tracker, we need to avoid
# confusions.

if (!$group_id)
{ print exit_no_group(); }

$project = project_get_object($group_id);

if (!member_check(0, $group_id))
{
  exit_error(_("Data Export is currently restricted to projects members"));
}

trackers_init($group_id);

# Set $printer that may be used in later pages instead of PRINTER
if (defined('PRINTER'))
{ $printer = 1; }

# Set the limit of possible jobs per user
$max_export = 5;

# Get the list of current exports
$res_export = db_execute("SELECT * FROM trackers_export WHERE user_name=? AND unix_group_name=? AND status<>'I' ORDER BY export_id ASC", array(user_getname(), $group));
$export_count = db_numrows($res_export);



########################################################################
# GET/POST Update

if ($update)
{
  # Create new item
  if ($create)
    {
      if (!form_check($form_id))
	{ exit_error(_("Exiting")); }

      if ($export_count >= $max_export)
	{
	  # Already registered 5 exports? Kick out
	  form_clean($form_id);
	  exit_error(sprintf(ngettext("You have already registered %s export job for this project, which is the current limit. If more exports are required ask other project members.", "You have already registered %s export jobs for this project, which is the current limit. If more exports are required ask other project members.", $max_export), $max_export));
	}


      ##
      # Find out the sql to build up export
      if (!$report_id)
	{ $report_id = 100; }
      trackers_report_init($group_id, $report_id);

      $select = 'SELECT bug_id ';
      $from = 'FROM '.ARTIFACT.' ';
      $where = 'WHERE group_id=? ';
      $where_params = array($group_id);

      #################### GRABBED FROM BROWSE
      # This should probably included in functions
      $url_params = trackers_extract_field_list();
      unset($url_params['group_id'], $url_params['history_date']);
      while (list($field,$value_id) = each($url_params))
	{
	  if (!is_array($value_id))
	    {
	      unset($url_params[$field]);
	      $url_params[$field][] = $value_id;
	    }
	  if (trackers_data_is_date_field($field)) 
	    {
	      if ($advsrch)
		{
		  $field_end = $field.'_end';
		  $in = sane_import('post', array($field_end));
		  $url_params[$field_end] = $in[$field_end];
		}
	      else
		{
		  $field_op = $field.'_op';
		  $in = sane_import('post', array($field_op));
		  $url_params[$field_op] = $in[$field_op];
		  if (!$url_params[$field_op])
		    { $url_params[$field_op] = '='; }
		}
	    }
	}

      while ($field = trackers_list_all_fields())
	{
	  if (trackers_data_is_showed_on_query($field) &&
	      trackers_data_is_select_box($field) )
	    {
	      if (!isset($url_params[$field]))
		{ $url_params[$field][] = 0; }
	    }
	}

      reset($url_params);
      while (list($field,$value_id) = each($url_params))
	{
	  # This break the sql, I dont now why. Apparently it returns false
	  # for the date fields.
	  #if (!trackers_data_is_showed_on_query($field))
	  #  { continue; }

	  if (trackers_data_is_select_box($field) && !trackers_isvarany($url_params[$field]) )
	    {
	      $where .= ' AND '.$field.' IN ('.implode(',', array_fill(0, count($url_params[$field]), '?')).') ';
	      $where_params = array_merge($where_params, $url_params[$field]);
	    }
	  else if (trackers_data_is_date_field($field) && $url_params[$field][0])
	    {
       	      list($time,$ok) = utils_date_to_unixtime($url_params[$field][0]);
	      preg_match("/\s*(\d+)-(\d+)-(\d+)/", $url_params[$field][0],$match);
	      list(,$year,$month,$day) = $match;

	      if ($advsrch)
		{
		  list($time_end,$ok_end) = utils_date_to_unixtime($url_params[$field.'_end'][0]);
		  if ($ok)
		    {
		      $where .= ' AND '.$field.' >= ? ';
		      $where_params[] = $time;
		    }

		  if ($ok_end)
		    {
		      $where .= ' AND '.$field.' <= ? ';
		      $where_params[] = $time_end;
		    }
		}
	      else
		{
		  $operator = $url_params[$field.'_op'][0];
          # '=' means that day between 00:00 and 23:59
		  if ($operator == '=')
		    {
		      $time_end = mktime(23, 59, 59, $month, $day, $year);
		      $where .= ' AND '.$field.' >= ? AND '.$field.' <= ? ';
		      $where_params[] = $time;
		      $where_params[] = $time_end;
		    }
		  else
		    {
		      $time = mktime(0,0,0, $month, ($day+1), $year);
		      $where .= ' AND '.$field." $operator= ? ";
		      $where_params[] = $time;
		    }
		}

      # Always exclude undefined dates (0)
	      $where .= ' AND '.$field." <> 0 ";

	    }
	  elseif ((trackers_data_is_text_field($field) ||
		   trackers_data_is_text_area($field)) &&
		  $url_params[$field][0])
	    {
      # Buffer summary and original submission (details) to handle them later
      # in case we have an OR to do between the two, instead of the usual
      # AND
	      if ($sumORdet == 1 &&
		  ($field == 'summary' || $field == 'details'))
		{
		  if ($field == 'summary')
		    { $summary_search = 1; }
		  if ($field == 'details')
		    { $details_search = 1; }
		}
	      else
		{
          # It s a text field accept. Process INT or TEXT,VARCHAR fields differently
		  list($expr, $params) = trackers_build_match_expression($field, $url_params[$field][0]);
		  $where .= ' AND $expr ';
		  $where_params = array_merge($where_params, $params);
		}
	    }
	}


      # Handle summary and/or original submission now, if a AND is required
      if ($sumORdet == 1)
	{
      # We will process the usual normal AND case: there was something for both
      # fields.
	  if ($details_search == 1 && $summary_search == 1)
	    {
	      $where .= ' AND ';
	      $where .= '( ( ';
	      list($expr, $params) = trackers_build_match_expression('details', $url_params['details'][0]);
	      $where .= $expr;
	      $where_params = array_merge($where_params, $params);
	      $where .= ' ) OR ( ';
	      list($expr, $params) = trackers_build_match_expression('summary', $url_params['summary'][0]);
	      $where .= $expr;
	      $where_params = array_merge($where_params, $params);
	      $where .= ') ) ';
	    }
	  else
	    {
      # Now we take care of the unusual, possible though, case where and
      # AND was asked but not both fields set.
      # Since the AND was asked, the fields havent been taken care of before
      # and we need to do it now.
      # We do that in two IF, in case something went very wrong. In such case
      # we will proceed with a usual AND.
	      if ($details_search == 1 && $url_params['details'][0])
		{
		  $where .= ' AND ';
		  list($expr, $params) = trackers_build_match_expression('details', $url_params['details'][0]);
		  $where .= $expr;
		  $where_params = array_merge($where_params, $params);
		}
	      if ($summary_search == 1 && $url_params['summary'][0])
		{
		  $where .= ' AND ';
		  list($expr, $params) = trackers_build_match_expression('summary', $url_params['summary'][0]);
		  $where .= $expr;
		  $where_params = array_merge($where_params, $params);
		}
	    }
	}
      #################### GRABBED FROM BROWSE
      $export_sql = db_variable_binding("$select $from $where", $where_params);


      ##
      # Find out the time arguments
      $timestamp = $requested_hour = $requested_day = null;

      switch ($date_mainchoice)
	{
	case 'asap':
	  # Basic case where the user wants the export to be done as soon
	  # as possible: we provide current time as timestamp
	  $timestamp = time();
	  break;
	case 'next':
	  # Case where the user provide a date for a one time export
	  # In the form:
	  #    0 = today
	  #    1 = tomorrow
	  #    etc...
	  $current_day = strftime('%d', $current_time);
	  $current_month = strftime('%m', $current_time);
	  $day = ($current_day+$date_next_day);
	  $hour = $date_next_hour;
	  $timestamp = mktime($hour, 0, 0, $current_month, $day);
	  break;
	case 'frequent':
	  # Data export will be done on a weekly basis
	  # We store the timestamp of the next time it is expect
	  # and we save the request, so the backend now that he will have
	  # to update the timestamp afterwards
	  $current_day = strftime('%d', $current_time);
	  $current_month = strftime('%m', $current_time);
	  $hour =  $date_frequent_hour;
	  $requested_hour = $hour;
	  $requested_day = $date_frequent_day + 1;
	  if ($date_frequent_day < 7) {
	    for ($day = $current_day; $day <= ($current_day+8); $day++)
	      {
		// Test the next 8 days and find out which one match
		// with the requested day
		$timestamp = mktime($hour, 0, 0, $current_month, $day);
		if (strftime('%u', $timestamp) == $requested_day)
		  { break; }
	      }
	  } else {
	    $timestamp = mktime($hour, 0, 0, $current_month, $current_day);
	    if ($timestamp < time()) {
	      $timestamp = mktime($hour, 0, 0, $current_month,$current_day+1);
	    }
	  }
	}
	  

      ##
      # Insert the request into the database

      # First add an entry in trackers_export. Create the export with the
      # status invalid (I) so it wont be handled by the backend before
      # the next step is done on the frontend side
      $result = db_autoexecute('trackers_export',
	array(
          'task_id' => 0,
	  'artifact' => ARTIFACT,
	  'unix_group_name' => $group,
	  'user_name' => user_getname(),
	  'sql' => $export_sql,
	  'status' => 'I',
	  'date' => $timestamp,
	  'frequency_day' => $requested_day,
	  'frequency_hour' => $requested_hour,
	), DB_AUTOQUERY_INSERT);
      if (!$result)
	{
	  exit_error(_("SQL insert error"));
	}
      $insert_id = db_insertid($result);


      form_clean($form_id);

      # Second, create a task to make it easy for other project members
      # to follow the export and for the user to have the export in my items
      # as a task.
      # We could have imagined using a simple task to manage the whole
      # export stuff, but that would be probably overkill for now.
      # Maybe we wil reconsider this later.
      session_redirect($GLOBALS['sys_home']."task/export-createtask.php?group=".rawurlencode($group)."&export_id=$insert_id&from=".ARTIFACT);

    } /* if ($create) */

  // Delete item
  if ($delete)
    {
      $export_id = $delete;
      
      // Obtain the relevant task number
      $task_id = db_result(db_execute("SELECT task_id FROM trackers_export WHERE export_id=? LIMIT 1", array($export_id)), 0, 'task_id');
      
      // Delete the entry
      $result = db_execute("DELETE FROM trackers_export WHERE export_id=? AND user_name=? LIMIT 1", array($export_id, user_getname()));
      if (db_affected_rows($result))
	{
	  fb(sprintf(_("Export job #%s successfully removed"), $export_id));
	  
	  session_redirect($GLOBALS['sys_home']."task/export-updatetask.php?group=".rawurlencode($group)."&export_id=$export_id&task_id=$task_id&from=".ARTIFACT);
	  
	}
      else
	{
	  fb(sprintf(_("Unable to remove export job #%s"), $export_id), 1);
	}
      
      
      
      // Update the list of current exports
      $res_export = db_execute("SELECT * FROM trackers_export WHERE user_name=? AND unix_group_name=? AND status<>'I' ORDER BY export_id ASC", array(user_getname(), $group));
      $export_count = db_numrows($res_export);
    } /* if ($delete) */
 }



########################################################################
# Print XHTML page
#
# If we have an export_id : we edit the pending export
# If we have no export_id :
#                        - we provide the list of pending exports
#                        - we provide the form to add a new pending export
#                        if the maximum was of 10 queues was not reached


$export_id = null; # Not implemented
if ($export_id)
{
  # Not implemented

}
else
{
  trackers_header(array('title'=>_("Data Export Jobs")));

  print '<p>'._("From here, you can select criteria for an XML export of the items of your project of the current tracker. Then your request will be queued and made available on an HTTP accessible URL. This way you can automate exports, using scripts, as you know the file URL in advance.").'</p>';


  ##
  # List of pending exports
  print '<h3>'.html_anchor(_("Pending Export Jobs"), "pending").'</h3>';

  if ($export_count > 0)
    {
      print $HTML->box_top(_("Queued Jobs"));

      for ($i = 0; $i < $export_count; $i++)
	{
	 if ($i > 0)
	    { print $HTML->box_nextitem(utils_get_alt_row_color($i+1)); }

	 print '<span class="trash">';
	 print utils_link(htmlentities ($_SERVER['PHP_SELF']).'?update=1&amp;delete='.db_result($res_export, $i, 'export_id').'&amp;group='.$group,
			  '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" border="0" alt="'._("Remove this job").'" />');
	 print '</span>';

	 $status = _("Pending");
	 if (db_result($res_export, $i, 'status') == 'D')
	   { $status = _("Done"); }

	 print utils_link($GLOBALS['sys_home'].'task/?func=detailitem&amp;item_id='.db_result($res_export, $i, 'task_id'),
			  # I18N
	   		  # The first two strings are export and task id;
	   		  # the last string is the status (pending, done)
			  sprintf(_("Job #%s, bound to task #%s, %s"),
				  db_result($res_export, $i, 'export_id'),
				  db_result($res_export, $i, 'task_id'),
				  $status));


	 $export_url = $GLOBALS['sys_https_url'].$GLOBALS['sys_home']."export/$group/".user_getname()."/".db_result($res_export, $i, 'export_id').".xml";
	 print '<br />'.sprintf(_("URL: %s"), utils_link($export_url, $export_url));

	 $type = utils_get_tracker_name(db_result($res_export, $i, 'artifact'));

	 if (db_result($res_export, $i, 'frequency_day'))
	   {
	     # I18N
	     # First string is 'every weekday', second the time of day
	     # Example: "every Wednesday at 16:45 hours"
	     if (db_result($res_export, $i, 'frequency_day') == 8) {
	       $date = sprintf(_("%s at %s hours [GMT]"), "every day",
			       db_result($res_export, $i, 'frequency_hour'));
	     } else {
	       $date = sprintf(_("%s at %s hours [GMT]"),
			       calendar_every_weekday_name(db_result($res_export, $i, 'frequency_day')),
			       db_result($res_export, $i, 'frequency_hour'));
	     }
	   }
	 else
	   {
	     $date = utils_format_date(db_result($res_export, $i, 'date'));
	   }

	 print '<br /><span class="smaller">'.
	   # I18N
	   # First string is the type of export (e.g. recipes, bugs, tasks, ...)
	   # Second string is the date of the export
	   # Example: Exporting recipes on Fri, 2 Dec 2005
	   sprintf(_("Exporting %s on %s"),
		   $type,
		   $date).
	   '</span>';
	}

      print $HTML->box_bottom();

      print '<p>'._("Note that xml files will be removed after 2 weeks or if you remove the job from this list.").'</p>';
    }
  else
    {
      print _("You have no export job pending.");
    }

  ##
  # Query to build an export
  print '<br />';
  print '<h3>'.html_anchor(_("Creating a new Export Job"), "new").'</h3>';

  if ($export_count < $max_export)
    {
      ##
      # Query Form selection
      if (!$report_id)
	{ $report_id = 100; }
      trackers_report_init($group_id, $report_id);

      $multiple_selection = $advsrch;
      $advsrch_0 = '';
      $advsrch_1 = '';
      if ($multiple_selection)
	{
	  $advsrch_1 = ' selected="selected"';
	  # Use is_multiple to provide an array to the later display_field
	  # functions that use that to determine if they need to display
	  # simple or multiple select boxes
	  $is_multiple = array();
	}
      else
	{ $advsrch_0 = ' selected="selected"'; }


      $res_report = trackers_data_get_reports($group_id,user_getid());

      print html_show_displayoptions(sprintf(_("Use the %s Query Form and %s selection for export criteria."),
					     html_build_select_box($res_report,
								   'report_id',
								   $report_id,
								   true,
								   'Basic'),
					     '<select name="advsrch"><option value="0"'.$advsrch_0.'>'._("Simple").'</option><option value="1"'.$advsrch_1.'>'._("Multiple").'</option></select>'),
				     form_header($_SERVER['PHP_SELF'].'#new', '').form_input("hidden", "group", $group),
				     form_submit(_("Apply")));

      ##
      # Display criteria
      print form_header($_SERVER['PHP_SELF'], '');
      print form_input("hidden", "group", $group);
      print form_input("hidden", "create", "1");
      $current_time = time();
      print form_input("hidden", "current_time", $current_time);

      print '<span class="preinput">'._("Export criteria:").'</span><br />';

      # FIXME: for some reasons, this does not show up on the cookbook

      #################### GRABBED FROM BROWSE
      # This should probably included in functions
      $ib=0;
      $is=0;
      $fields_per_line=5;
      $load_cal=false;

# Check if summary and original submission are criteria
      $summary_search = 0;
      $details_search = 0;

      $labels = '';
      $boxes = '';
      $html_select = '';
      while ($field = trackers_list_all_fields('cmp_place_query'))
	{
# Skip unused field
	  if (!trackers_data_is_used($field))
	    { continue; }

# Skip fields not part of this query form
	  if (!trackers_data_is_showed_on_query($field))
	    { continue; }

# beginning of a new row
	  if ($ib % $fields_per_line == 0)
	    {
	      $align = ($printer ? "left" : "center");
	      $labels .= "\n".'<tr align="'.$align.'" valign="top">';
	      $boxes .= "\n".'<tr align="'.$align.'" valign="top">';
	    }

	  $labels .= '<td>'.trackers_field_label_display($field,$group_id,false,false).'</td>';
	  $boxes .= '<td><span class="smaller">';

	  if (trackers_data_is_select_box($field))
	    {
	      $value = null;
	      if (isset($is_multiple))
		{ $value = array(); }

	      # For Open/Closed, automatically select Open
	      if ($field == 'status_id')
		{
		  if (isset($is_multiple))
		    { $value = array(1); }
		  else
		    { $value = 1; }
		}

	      $boxes .=
		trackers_field_display($field,$group_id,$value,false,false,($printer?true:false),false,true,'None', true,'Any');

	    }
	  elseif (trackers_data_is_date_field($field))
	    {

	      if ($advsrch)
		{
		  $boxes .= trackers_multiple_field_date($field,$is_multiple,
							 $url_params[$field.'_end'][0],0,0,$printer);
		}
	      else
		{
		  $boxes .= trackers_field_date_operator($field,$is_multiple,$printer).
		    trackers_field_date($field,$url_params[$field][0],0,0,$printer);
		}

	    }
	  elseif (trackers_data_is_text_field($field) ||trackers_data_is_text_area($field))
	    {
	      if ($field == 'summary')
		{ $summary_search = 1; }
	      if ($field == 'details')
		{ $details_search = 1; }

	      $boxes .=
		($printer ? $url_params[$field][0] : trackers_field_text($field,$url_params[$field][0],15,80)) ;
	    }

	  $boxes .= "</span></td>\n";

	  $ib++;

# end of this row
	  if ($ib % $fields_per_line == 0)
	    {
	      $html_select .= $labels.'</tr>'.$boxes.'</tr>';
	      $labels = $boxes = '';
	    }

	}

# Make sure the last few cells are in the table
      if ($labels)
	{
	  $html_select .= $labels.'</tr>'.$boxes.'</tr>';
	}

      # [...]
      print '<table cellpadding="0" cellspacing="5">
        <tr><td colspan="'.$fields_per_line.'" nowrap="nowrap">';
      print $html_select;
      print '</table>';

      #################### GRABBED FROM BROWSE


      ##
      # Time selection
      print '<br />';
      print '<span class="preinput">'._("The export should be generated:").'</span><br />';

      # ASAP case
      print '&nbsp;&nbsp;&nbsp;'.form_input("radio", "date_mainchoice", "asap\" checked=\"checked").' '._("as soon as possible").'<br />';

      # Today at xHour
      # (this wont be ready for alternative calendars, but currently the
      # priority is to have this working)
      $timezone = strftime('%Z', $current_time);
      $current_hour = strftime('%H', $current_time);
      $current_day = strftime('%d', $current_time);
      $current_month = strftime('%m', $current_time);

      $valid_hours = array();
      for ($hour = 0; $hour <= 24; $hour++)
	{ $valid_hours[] = $hour; }

      $valid_days = array();
      $valid_days = array(_("Today"), _("Tomorrow"));
      $count = 0;
      for ($day = ($current_day+2); $count <= 31; $day++)
	{
	  $count++;
	  $day_time = mktime(0, 0, 0, $current_month, $day);
	  # use format minimal because the hour is meaningless here
	  $valid_days[] = utils_format_date($day_time, 'minimal');
	}

      $valid_hours = array();
      for ($hour = 0; $hour <= 23; $hour++)
	{ $valid_hours[] = $hour; }

      print '&nbsp;&nbsp;&nbsp;'.form_input("radio", "date_mainchoice", "next").' '.
        # I18N
	# First %s: the day (e.g. today, tomorrow, Fri 2. Dec 2005, ...)
	# Second %s: the time (e.g. 16:37)
	# Third %s: the timezone (e.g. GMT)
	# Note that the GMT string may not be set, so dont put it beween 
	# parenthesis.
	sprintf(_("%s at %s hours %s"),
		html_build_select_box_from_array($valid_days,
						 "date_next_day"),
		html_build_select_box_from_array($valid_hours,
						 "date_next_hour",
						 ($current_hour+1)),
		$timezone).'<br />';


      # Next/Every xDay at xHour
      # (this wont be ready for alternative calendars, but currently the
      # priority is to have this working)
      $valid_days = array();
      for ($day = 1; $day <= 7; $day++)
	{ $valid_days[] = calendar_every_weekday_name($day); }
      $valid_days[] =  _("every day");

      print '&nbsp;&nbsp;&nbsp;'.form_input("radio", "date_mainchoice", "frequent").' '.
        # I18N
        # First string is 'every weekday', second the time of day,
	# third is the timezone
        # Example: "every Wednesday at 16:45 hours GMT"
	# Note that the GMT string may not be set, so dont put it beween 
	# parenthesis.
	sprintf(_("%s at %s hours %s"),
		html_build_select_box_from_array($valid_days,
						 "date_frequent_day"),
		html_build_select_box_from_array($valid_hours,
						 "date_frequent_hour",
						 ($current_hour+1)),
		$timezone
	).'<br />';


      print '<p align="center">'.form_submit().'</p>';
    }
  else
    {
      print sprintf(ngettext("You have already registered %s export job for this project, which is the current limit. If more exports are required ask other project members.", "You have already registered %s export jobs for this project, which is the current limit. If more exports are required ask other project members.", $max_export), $max_export);
    }

  trackers_footer(array());
}
