<?php
# <one line to give a brief idea of what this does.>
# 
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


extract(sane_import('request', array('report_id')));
extract(sane_import('get', array('show_report', 'new_report', 'delete_report')));
extract(sane_import('post', array(
'post_changes',
'create_report', 'update_report',
'rep_name', 'rep_scope', 'rep_desc',
'TFSRCH_bug_id', 'TFREP_bug_id', 'TFCW_bug_id',   'CBSRCH_bug_id', 'CBREP_bug_id',
'TFSRCH_submitted_by', 'TFREP_submitted_by', 'TFCW_submitted_by', 'CBSRCH_submitted_by', 'CBREP_submitted_by',
'TFSRCH_date', 'TFREP_date', 'TFCW_date', 'CBSRCH_date', 'CBREP_date',
'TFSRCH_close_date', 'TFREP_close_date', 'TFCW_close_date', 'CBSRCH_close_date', 'CBREP_close_date',
'TFSRCH_planned_starting_date', 'TFREP_planned_starting_date', 'TFCW_planned_starting_date', 'CBSRCH_planned_starting_date', 'CBREP_planned_starting_date',
'TFSRCH_planned_close_date', 'TFREP_planned_close_date', 'TFCW_planned_close_date', 'CBSRCH_planned_close_date', 'CBREP_planned_close_date',
'TFSRCH_category_id', 'TFREP_category_id', 'TFCW_category_id', 'CBSRCH_category_id', 'CBREP_category_id',
'TFSRCH_priority', 'TFREP_priority', 'TFCW_priority', 'CBSRCH_priority', 'CBREP_priority',
'TFSRCH_resolution_id', 'TFREP_resolution_id', 'TFCW_resolution_id', 'CBSRCH_resolution_id', 'CBREP_resolution_id',
'TFSRCH_privacy', 'TFREP_privacy', 'TFCW_privacy', 'CBSRCH_privacy', 'CBREP_privacy',
'TFSRCH_vote', 'TFREP_vote', 'TFCW_vote', 'CBSRCH_vote', 'CBREP_vote',
'TFSRCH_percent_complete', 'TFREP_percent_complete', 'TFCW_percent_complete', 'CBSRCH_percent_complete', 'CBREP_percent_complete',
'TFSRCH_assigned_to', 'TFREP_assigned_to', 'TFCW_assigned_to', 'CBSRCH_assigned_to', 'CBREP_assigned_to',
'TFSRCH_status_id', 'TFREP_status_id', 'TFCW_status_id', 'CBSRCH_status_id', 'CBREP_status_id',
'TFSRCH_discussion_lock', 'TFREP_discussion_lock', 'TFCW_discussion_lock', 'CBSRCH_discussion_lock', 'CBREP_discussion_lock',
'TFSRCH_hours', 'TFREP_hours', 'TFCW_hours', 'CBSRCH_hours', 'CBREP_hours',
'TFSRCH_summary', 'TFREP_summary', 'TFCW_summary', 'CBSRCH_summary', 'CBREP_summary',
'TFSRCH_details', 'TFREP_details', 'TFCW_details', 'CBSRCH_details', 'CBREP_details',
'CBSRCH_severity', 'CBREP_severity', 'TFSRCH_severity', 'TFREP_severity',
'CBSRCH_bug_group_id', 'CBREP_bug_group_id', 'TFSRCH_bug_group_id', 'TFREP_bug_group_id',
'CBSRCH_originator_name', 'CBREP_originator_name', 'TFSRCH_originator_name', 'TFREP_originator_name',
'CBSRCH_originator_email', 'CBREP_originator_email', 'TFSRCH_originator_email', 'TFREP_originator_email',
'CBSRCH_originator_phone', 'CBREP_originator_phone', 'TFSRCH_originator_phone', 'TFREP_originator_phone',
'CBSRCH_release', 'CBREP_release', 'TFSRCH_release', 'TFREP_release',
'CBSRCH_release_id', 'CBREP_release_id', 'TFSRCH_release_id', 'TFREP_release_id',
'CBSRCH_category_version_id', 'CBREP_category_version_id', 'TFSRCH_category_version_id', 'TFREP_category_version_id',
'CBSRCH_platform_version_id', 'CBREP_platform_version_id', 'TFSRCH_platform_version_id', 'TFREP_platform_version_id',
'CBSRCH_reproducibility_id', 'CBREP_reproducibility_id', 'TFSRCH_reproducibility_id', 'TFREP_reproducibility_id',
'CBSRCH_size_id', 'CBREP_size_id', 'TFSRCH_size_id', 'TFREP_size_id',
'CBSRCH_fix_release_id', 'CBREP_fix_release_id', 'TFSRCH_fix_release_id', 'TFREP_fix_release_id',
'CBSRCH_comment_type_id', 'CBREP_comment_type_id', 'TFSRCH_comment_type_id', 'TFREP_comment_type_id',
'CBSRCH_plan_release_id', 'CBREP_plan_release_id', 'TFSRCH_plan_release_id', 'TFREP_plan_release_id',
'CBSRCH_component_version', 'CBREP_component_version', 'TFSRCH_component_version', 'TFREP_component_version',
'CBSRCH_fix_release', 'CBREP_fix_release', 'TFSRCH_fix_release', 'TFREP_fix_release',
'CBSRCH_plan_release', 'CBREP_plan_release', 'TFSRCH_plan_release', 'TFREP_plan_release',
'CBSRCH_keywords', 'CBREP_keywords', 'TFSRCH_keywords', 'TFREP_keywords',
'CBSRCH_custom_tf1', 'CBREP_custom_tf1', 'TFSRCH_custom_tf1', 'TFREP_custom_tf1',
'CBSRCH_custom_tf2', 'CBREP_custom_tf2', 'TFSRCH_custom_tf2', 'TFREP_custom_tf2',
'CBSRCH_custom_tf3', 'CBREP_custom_tf3', 'TFSRCH_custom_tf3', 'TFREP_custom_tf3',
'CBSRCH_custom_tf4', 'CBREP_custom_tf4', 'TFSRCH_custom_tf4', 'TFREP_custom_tf4',
'CBSRCH_custom_tf5', 'CBREP_custom_tf5', 'TFSRCH_custom_tf5', 'TFREP_custom_tf5',
'CBSRCH_custom_tf6', 'CBREP_custom_tf6', 'TFSRCH_custom_tf6', 'TFREP_custom_tf6',
'CBSRCH_custom_tf7', 'CBREP_custom_tf7', 'TFSRCH_custom_tf7', 'TFREP_custom_tf7',
'CBSRCH_custom_tf8', 'CBREP_custom_tf8', 'TFSRCH_custom_tf8', 'TFREP_custom_tf8',
'CBSRCH_custom_tf9', 'CBREP_custom_tf9', 'TFSRCH_custom_tf9', 'TFREP_custom_tf9',
'CBSRCH_custom_tf10', 'CBREP_custom_tf10', 'TFSRCH_custom_tf10', 'TFREP_custom_tf10',
'CBSRCH_custom_ta1', 'CBREP_custom_ta1', 'TFSRCH_custom_ta1', 'TFREP_custom_ta1',
'CBSRCH_custom_ta2', 'CBREP_custom_ta2', 'TFSRCH_custom_ta2', 'TFREP_custom_ta2',
'CBSRCH_custom_ta3', 'CBREP_custom_ta3', 'TFSRCH_custom_ta3', 'TFREP_custom_ta3',
'CBSRCH_custom_ta4', 'CBREP_custom_ta4', 'TFSRCH_custom_ta4', 'TFREP_custom_ta4',
'CBSRCH_custom_ta5', 'CBREP_custom_ta5', 'TFSRCH_custom_ta5', 'TFREP_custom_ta5',
'CBSRCH_custom_ta6', 'CBREP_custom_ta6', 'TFSRCH_custom_ta6', 'TFREP_custom_ta6',
'CBSRCH_custom_ta7', 'CBREP_custom_ta7', 'TFSRCH_custom_ta7', 'TFREP_custom_ta7',
'CBSRCH_custom_ta8', 'CBREP_custom_ta8', 'TFSRCH_custom_ta8', 'TFREP_custom_ta8',
'CBSRCH_custom_ta9', 'CBREP_custom_ta9', 'TFSRCH_custom_ta9', 'TFREP_custom_ta9',
'CBSRCH_custom_ta10', 'CBREP_custom_ta10', 'TFSRCH_custom_ta10', 'TFREP_custom_ta10',
'CBSRCH_custom_sb1', 'CBREP_custom_sb1', 'TFSRCH_custom_sb1', 'TFREP_custom_sb1',
'CBSRCH_custom_sb2', 'CBREP_custom_sb2', 'TFSRCH_custom_sb2', 'TFREP_custom_sb2',
'CBSRCH_custom_sb3', 'CBREP_custom_sb3',  'TFSRCH_custom_sb3', 'TFREP_custom_sb3',
'CBSRCH_custom_sb4', 'CBREP_custom_sb4', 'TFSRCH_custom_sb4', 'TFREP_custom_sb4',
'CBSRCH_custom_sb5', 'CBREP_custom_sb5', 'TFSRCH_custom_sb5', 'TFREP_custom_sb5',
'CBSRCH_custom_sb6', 'CBREP_custom_sb6', 'TFSRCH_custom_sb6', 'TFREP_custom_sb6',
'CBSRCH_custom_sb7', 'CBREP_custom_sb7', 'TFSRCH_custom_sb7', 'TFREP_custom_sb7',
'CBSRCH_custom_sb8', 'CBREP_custom_sb8', 'TFSRCH_custom_sb8', 'TFREP_custom_sb8',
'CBSRCH_custom_sb9', 'CBREP_custom_sb9', 'TFSRCH_custom_sb9', 'TFREP_custom_sb9',
'CBSRCH_custom_sb10', 'CBREP_custom_sb10', 'TFSRCH_custom_sb10', 'TFREP_custom_sb10',
'CBSRCH_custom_df1', 'CBREP_custom_df1', 'TFSRCH_custom_df1', 'TFREP_custom_df1',
'CBSRCH_custom_df2', 'CBREP_custom_df2', 'TFSRCH_custom_df2', 'TFREP_custom_df2',
'CBSRCH_custom_df3', 'CBREP_custom_df3', 'TFSRCH_custom_df3', 'TFREP_custom_df3',
'CBSRCH_custom_df4', 'CBREP_custom_df4', 'TFSRCH_custom_df4', 'TFREP_custom_df4',
'CBSRCH_custom_df5', 'CBREP_custom_df5', 'TFSRCH_custom_df5', 'TFREP_custom_df5',
)));

# HELP: what we call now "query form" was previously called "report",
# that name is still in the database.

$is_admin_page='y';

if (!$group_id) {
  exit_no_group();
}

if (!user_ismember($group_id,'A'))
{
  exit_permission_denied();
}

# Initialize global bug structures
trackers_init($group_id);

if ($post_changes)
{

  # scope is always project scope
  $rep_scope = "P";

  if ($update_report)
    {
      # Updat report name and description and delete old report entries
      $res = db_execute("DELETE FROM ".ARTIFACT."_report_field WHERE report_id=?", array($report_id));
      $res = db_autoexecute(ARTIFACT.'_report',
	array(
          'name' => $rep_name,
	  'description' => $rep_desc,
	  'scope' => $rep_scope,
        ), DB_AUTOQUERY_UPDATE,
	"report_id=?", array($report_id));
    }

  else if ($create_report)
    {
      # Create a new report entry
      $res = db_autoexecute(ARTIFACT.'_report',
	array(
          'group_id' => $group_id,
	  'user_id' => user_getid(),
	  'name' => $rep_name,
	  'description' => $rep_desc,
	  'scope' => $rep_scope,
	), DB_AUTOQUERY_INSERT);
      $report_id = db_insertid($res);
    }

  # And now insert all the field entries in the trackers_report_field table
  $sql = 'INSERT INTO '.ARTIFACT.'_report_field (report_id, field_name,'.
     'show_on_query,show_on_result,place_query,place_result,col_width) VALUES ';
  $params = array();
  while ( $field = trackers_list_all_fields() )
    {
      if ( ($field == 'group_id') ||
	   ($field == 'comment_type_id') )
	{ continue; }

      $cb_search = 'CBSRCH_'.$field;
      $cb_report = 'CBREP_'.$field;
      $tf_search = 'TFSRCH_'.$field;
      $tf_report = 'TFREP_'.$field;
      $tf_colwidth = 'TFCW_'.$field;

      if ($$cb_search || $$cb_report || $$tf_search || $$tf_report)
	{

	  $cb_search_val = ($$cb_search ? 1:0);
	  $cb_report_val = ($$cb_report ? 1:0);
	  $tf_search_val = ($$tf_search ? $$tf_search : null);
	  $tf_report_val = ($$tf_report ? $$tf_report : null);
	  $tf_colwidth_val = ((array_key_exists ($tf_colwidth, get_defined_vars())
			       && $$tf_colwidth) ? $$tf_colwidth : null);
	  $sql .= "(?, ?, ?, ?, ?, ?, ?),";
	  $params = array_merge($params,
				array($report_id,$field,$cb_search_val,$cb_report_val,
				      $tf_search_val,$tf_report_val,$tf_colwidth_val));
	}
    }
  $sql = substr($sql,0,-1);
  #print "<br /> DBG SQL = $sql";

  $res = db_execute($sql, $params);
  if ($res)
    {
      if ($create_report)
        {
          sprintf(_("Query form '%s' created successfully"),$rep_name);
	}
      else
        {
          sprintf(_("Query form '%s' updated successfully"),$rep_name);
	}
    }
  else
    {
      if ($create_report)
        {
          sprintf(_("Failed to create query form '%s'"),$rep_name);
	}
      else
        {
          sprintf(_("Failed to update query form '%s'"),$rep_name);
	}
    }

} /* End of post_changes */

else if ($delete_report)
{
  # Record the change
  group_add_history('Deleted query form', ARTIFACT.', form "'.$rep_name.'"', $group_id);

  db_execute("DELETE FROM ".ARTIFACT."_report WHERE report_id=?", array($report_id));
  db_execute("DELETE FROM ".ARTIFACT."_report_field WHERE report_id=?", array($report_id));
}


# Display the UI forms

if ($new_report)
{
  trackers_header_admin(array ('title'=>_("Create A New Query Form")));
  
# display the table of all fields that can be included in the report
  $title_arr=array();
  $title_arr[]=_("Field Label");
  $title_arr[]=_("Description");
  $title_arr[]=_("Use as a Search Criteria");
  $title_arr[]=_("Rank on Search");
  $title_arr[]=_("Use as an Output Column");
  $title_arr[]=_("Rank on Output");
  $title_arr[]=_("Column width (optional)");
  
  print'
	<form action="'.$_SERVER['PHP_SELF'].'" method="post">
	   <input type="hidden" name="create_report" value="y" />
	   <input type="hidden" name="group_id" value="'.$group_id.'" />
	   <input type="hidden" name="post_changes" value="y" />
           <p>
	   <span class="preinput">'
    ._("Name of the Query Form:").'</span><br />
	   <input type="text" name="rep_name" value="" size="20" maxlength="20" />
           </p><p>
	   <span class="preinput">'
    ._("Scope:").'</span><br />';
  
  /*
 Separation of personal and project administration operation
  if (user_ismember($group_id,'A')
    {
      print '<select name="rep_scope">
                        <option value="I">'._("Personal").'</option>
                        <option value="P">'._("Project").'</option>
                        </select>';
    }
  else
    { print _("Personal").' <input type="hidden" name="rep_scope" value="I" />'; }
  */
  print _("Project").' <input type="hidden" name="rep_scope" value="P" />';
  
  
  
  print ' </p><p>
	    <span class="preinput">'._("Description:").'</span><br />
	     <input type="text" name="rep_desc" value="" size="50" maxlength="120" />
                  </p>';
  
  print html_build_list_table_top ($title_arr);
  $i=0;
  while ($field = trackers_list_all_fields())
    {
      // Do not show fields not used by the project
      if ( !trackers_data_is_used($field))
	{ continue; }
      
      // Do not show some special fields any way
      if (trackers_data_is_special($field))
	{
	  if ( ($field == 'group_id') ||
	       ($field == 'comment_type_id') )
	    { continue; }
	}
      
      $cb_search = 'CBSRCH_'.$field;
      $cb_report = 'CBREP_'.$field;
      $tf_search = 'TFSRCH_'.$field;
      $tf_report = 'TFREP_'.$field;
      $tf_colwidth = 'TFCW_'.$field;
      
      // For the rank values, set defaults, for the common fields, as
      // it gets easily messy when not specified
      $tf_report_val = 100;
      
      // Summary should be just after the item id
      if ($field == 'summary')
	{ $tf_report_val = 5; }
      // Statis should just after
      if ($field == 'resolution_id')
	{ $tf_report_val = 10; }
      // Moderately important fields
      if ($field == 'category_id' || $field == 'severity' || $field == 'vote')
	{ $tf_report_val = 25; }
      // Very moderately important fields
      if ($field == 'submitted_by' || $field == 'assigned_to')
	{ $tf_report_val = 50; }      
      
      
      print '<TR class="'. utils_get_alt_row_color($i) .'">';
      
      print "\n<td>".trackers_data_get_label($field).'</td>'.
	"\n<td>".trackers_data_get_description($field).'</td>'.
	"\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1" /></td>'.
	"\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="" size="5" maxlen="5" /></td>';
      
      // If the current field is item id, we force its presence on the
      // report with rank 0. This field is mandatory: otherwise some
      // links would be broken or there would be even no links.
      if ($field == 'bug_id') 
	{
	  print "\n<td align=\"center\"><input type=\"hidden\" name=\"".$cb_report."\" value=\"1\" />X</td>".
	    "\n<td align=\"center\"><input type=\"hidden\" name=\"".$tf_report."\" value=\"0\" />0</td>";
	}
      else
	{
	  print "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_report.'" value="1" /></td>'.
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_report.'" value="'.$tf_report_val.'" size="5" maxlen="5" /></td>';
	}
      
      print "\n<td align=\"center\">".'<input type="text" name="'.$tf_colwidth.'" value="" size="5" maxlen="5" /></td>'.
	'</tr>';
      $i++;
    }
  print '</table>'.
    '<p><center><input type="submit" name="submit" value="'._('Submit').'" /></center></p>'.
    '</form>';
  
} 
else if ($show_report)
{
  
  trackers_header_admin(array ('title'=>_("Modify a Query Form")));
  
  // fetch the report to update
  $res = db_execute("SELECT * FROM ".ARTIFACT."_report WHERE report_id=?",
		    array($report_id));
  $rows = db_numrows($res);
  if (!$rows)
    {
      exit_error('Error',"Unknown Report ID ($report_id)");
    }
  
  // make sure this user has the right to modify the bug report
  if ( (db_result($res,0,'scope') == 'P') &&
       !user_ismember($group_id,'A'))
    {
      exit_permission_denied();
    }
  
  $res_fld = db_execute("SELECT * FROM ".ARTIFACT."_report_field WHERE report_id=?",
			array($report_id));
  
  // Build the list of fields involved in this report
  while ( $arr = db_fetch_array($res_fld) )
    {
      $fld[$arr['field_name']] = $arr;
    }
  
  // display the table of all fields that can be included in the
  // report along with their current state in this report
  $title_arr=array();
  $title_arr[]=_("Field Label");
  $title_arr[]=_("Description");
  $title_arr[]=_("Use as a Search Criteria");
  $title_arr[]=_("Rank on Search");
  $title_arr[]=_("Use as an Output Column");
  $title_arr[]=_("Rank on Output");
  $title_arr[]=_("Column width (optional)");
  
  print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">
	   <input type="hidden" name="update_report" value="y" />
	   <input type="hidden" name="group_id" value="'.$group_id.'" />
	   <input type="hidden" name="report_id" value="'.$report_id.'" />
	   <input type="hidden" name="post_changes" value="y" />
	   <span class="preinput">'._("Name:").' </span><br />&nbsp;&nbsp;&nbsp;
	   <input type="text" name="rep_name" value="'.db_result($res,0,'name').'" size="20" maxlength="20" />';
  
  
  /*
                  &nbsp;&nbsp;&nbsp;&nbsp;<strong>'._("Scope:").' </strong>';
  $scope = db_result($res,0,'scope');
  if (user_ismember($group_id,'A'))
    { print '<SELECT NAME="rep_scope">
                        <option value="i"'.($scope=='i' ? 'selected':'').'>'._("Personal").'</option>
                        <option value="p"'.($scope=='p' ? 'selected':'').'>'._("Project").'</option>
                        </select>'; }
   else {
    print ($scope=='P' ? 'Project':'Personal').
      '<input type="hidden" name="rep_scope" value="'.$scope.'" />'; }
  */
  
  print '
	    <p>
	       <span class="preinput">'._("Description:").'</span><br />&nbsp;&nbsp;&nbsp;
	    <input type="text" name="rep_desc" value="'.db_result($res,0,'description').'" size="50" maxlength="120" /></p>
                  <p>';
  
  print html_build_list_table_top ($title_arr);
  $i = 0;
  while ( $field = trackers_list_all_fields() )
    {
      
      // Do not show fields not used by the project
      if ( !trackers_data_is_used($field))
	{ continue; }
      
      // Do not show some special fields any way
      if (trackers_data_is_special($field))
	{
	  if ( ($field == 'group_id') ||
	       ($field == 'comment_type_id') )
	    { continue; }
	}
      
      $cb_search = 'CBSRCH_'.$field;
      $cb_report = 'CBREP_'.$field;
      $tf_search = 'TFSRCH_'.$field;
      $tf_report = 'TFREP_'.$field;
      $tf_colwidth = 'TFCW_'.$field;
      
      $cb_search_chk = (!empty($fld[$field]['show_on_query']) ? 'checked="checked"':'');
      $cb_report_chk = (!empty($fld[$field]['show_on_result']) ? 'checked="checked"':'');
      $tf_search_val = (!empty($fld[$field]['place_query']) ? $fld[$field]['place_query']:'');
      $tf_report_val = (!empty($fld[$field]['place_result']) ? $fld[$field]['place_result']:'');
      $tf_colwidth_val = (!empty($fld[$field]['col_width']) ? $fld[$field]['col_width']:'');
      
      print '<tr class="'. utils_get_alt_row_color($i) .'">';
      
      print "\n<td>".trackers_data_get_label($field).'</td>'.
	"\n<td>".trackers_data_get_description($field).'</td>'.
	"\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1" '.$cb_search_chk.'  /></td>'.
	"\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="'.$tf_search_val.'" size="5" maxlen="5" /></td>';
      // If the current field is item id, we force it's presence on
      // the report with rank 0. This field is mandatory: otherwise
      // some links would be broken or there would be even no links.
      if ($field == 'bug_id') 
	{
	  print "\n<td align=\"center\"><input type=\"hidden\" name=\"".$cb_report."\" value=\"1\" />X</td>".
	    "\n<td align=\"center\"><input type=\"hidden\" name=\"".$tf_report."\" value=\"0\" />0</td>";
	}
      else
	{
	  print "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_report.'" value="1" '.$cb_report_chk.'  /></td>'.
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_report.'" value="'.$tf_report_val.'" size="5" maxlen="5" /></td>';
	}
      print "\n<td align=\"center\">".'<input type="text" name="'.$tf_colwidth.'" value="'.$tf_colwidth_val.'" size="5" maxlen="5" /></td>'.
	'</tr>';
      $i++;
    }
  print '</table>'.
    '<p><center><input type="submit" name="submit" value="'._("Submit").'" /></center>'.
    '</form>';
  
}
else
{
  
# Front page
  trackers_header_admin(array ('title'=>_("Edit Query Forms")));
  
#    print '<h2>'._("Manage Bug Reports").' '.help_button('trackers_admin_report_list',false).'</h2>';
  
  $res = db_execute("SELECT * FROM ".ARTIFACT."_report WHERE group_id=? ".
		    ' AND (user_id=? OR scope=\'P\')', array($group_id, user_getid()));
  $rows = db_numrows($res);
#print "<br /> DBG sql = $sql";
  
  if ($rows)
    {
# Loop through the list of all bug report
      $title_arr=array();
      $title_arr[]=_("Id");
      $title_arr[]=_("Query form name");
      $title_arr[]=_("Description");
      $title_arr[]=_("Scope");
      $title_arr[]=_("Delete?");
      
      print "\n<h3>"._("Existing Query Forms:").'</h3>';
      print '<p>('._("Click to modify").')';
      print html_build_list_table_top ($title_arr);
      $i=0;
      while ($arr = db_fetch_array($res))
	{
	  
	  print '<tr class="'. utils_get_alt_row_color($i) .'"><td>';
	  
	  if ( ($arr['scope']=='P') && !user_ismember($group_id,'A') )
	    {
	      print $arr['report_id'];
	      print "</td>\n<td>".$arr['name'].'</td>';
	    }
	  else
	    {
	      print '<a href="'.$_SERVER['PHP_SELF'].'?group='.$group.
		'&show_report=1&report_id='.$arr['report_id'].'">'.
		$arr['report_id'].'</a>';
	      print "</td>\n";
	      print '<td><a href="'.$_SERVER['PHP_SELF'].'?group='.$group.
		'&show_report=1&report_id='.$arr['report_id'].'">'.
		$arr['name'].'</a></td>';
	    }
	  
	  print "\n<td>".$arr['description'].'</td>'.
	    "\n<td align=\"center\">".(($arr['scope']=='P') ? _("Project"):_("Personal")).'</td>'.
	    "\n<td align=\"center\">";
	  
	  if ( ($arr['scope']=='P') && !user_ismember($group_id,'A') )
	    {
	      print '-';
	    }
	  else
	    {
	      print '<a href="'.$_SERVER['PHP_SELF'].'?group='.$group.
		'&amp;delete_report=1&amp;report_id='.$arr['report_id'].
		'&amp;rep_name='.$arr['name'].'">'.
		'<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" border="0" /></A>';
	    }

	  print '</td></tr>';
	  $i++;
	}
      print '</table>';
    }
  else
    {
      print '<h3>'._("No query form defined yet.").'</h3>';
    }
  
  printf ('<p>'._("You can %s create a new query form%s").'</p>','<a href="'.$_SERVER["PHP_SELF"].'?group='.$group.'&new_report=1">','</a>');
}

trackers_footer(array());
