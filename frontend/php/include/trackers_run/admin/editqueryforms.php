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
      $res = db_query("DELETE FROM ".ARTIFACT."_report_field WHERE report_id=$report_id");
      $res = db_query("UPDATE ".ARTIFACT."_report SET name='$rep_name', description='$rep_desc',scope='$rep_scope' WHERE report_id=$report_id");
    }

  else if ($create_report)
    {
      # Create a new report entry
      $res = db_query('INSERT INTO '.ARTIFACT.'_report (group_id,user_id,name,description,scope)'.
		      "VALUES ('$group_id','".user_getid()."','$rep_name',".
		      "'$rep_desc','$rep_scope')");
      $report_id = db_insertid($res);
    }

  # And now insert all the field entries in the trackers_report_field table
  $sql = 'INSERT INTO '.ARTIFACT.'_report_field (report_id, field_name,'.
     'show_on_query,show_on_result,place_query,place_result,col_width) VALUES ';

  while ( $field = trackers_list_all_fields() )
    {

      $cb_search = 'CBSRCH_'.$field;
      $cb_report = 'CBREP_'.$field;
      $tf_search = 'TFSRCH_'.$field;
      $tf_report = 'TFREP_'.$field;
      $tf_colwidth = 'TFCW_'.$field;

      if ($$cb_search || $$cb_report || $$tf_search || $$tf_report)
	{

	  $cb_search_val = ($$cb_search ? '1':'0');
	  $cb_report_val = ($$cb_report ? '1':'0');
	  $tf_search_val = ($$tf_search ? '\''.$$tf_search.'\'' : 'NULL');
	  $tf_report_val = ($$tf_report ? '\''.$$tf_report.'\'' : 'NULL');
	  $tf_colwidth_val = ($$tf_colwidth? '\''.$$tf_colwidth.'\'' : 'NULL');
	  $sql .= "('$report_id','$field',$cb_search_val,$cb_report_val,".
	     "$tf_search_val,$tf_report_val,$tf_colwidth_val),";
	}
    }
  $sql = substr($sql,0,-1);
  #print "<br /> DBG SQL = $sql";

  $res = db_query($sql);
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

  db_query("DELETE FROM ".ARTIFACT."_report WHERE report_id=$report_id");
  db_query("DELETE FROM ".ARTIFACT."_report_field WHERE report_id=$report_id");

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
	<form action="'.$PHP_SELF.'" method="post">
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

# Separation of personal and project administration operation
#  if (user_ismember($group_id,'A')
#    {
#      print '<select name="rep_scope">
#                        <option value="I">'._("Personal").'</option>
#                        <option value="P">'._("Project").'</option>
#                        </select>';
#    }
#  else
#    { print _("Personal").' <input type="hidden" name="rep_scope" value="I" />'; }
       print _("Project").' <input type="hidden" name="rep_scope" value="P" />';



  print ' </p><p>
	    <span class="preinput">'._("Description:").'</span><br />
	     <input type="text" name="rep_desc" value="" size="50" maxlength="120" />
                  </p>';

  print html_build_list_table_top ($title_arr);
  $i=0;
  while ($field = trackers_list_all_fields())
    {

      # Do not show fields not used by the project
      if ( !trackers_data_is_used($field))
	{ continue; }

      # Do not show some special fields any way
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
      
      # For the rank values, set defaults, for the common fields, as it gets
      # easily messy when not specified
      $tf_report_val = 100;

      # Summary should be just after the item id
      if ($field == 'summary')
	{ $tf_report_val = 5; }
      # Statis should just after
      if ($field == 'resolution_id')
	{ $tf_report_val = 10; }
      # Moderately important fields
      if ($field == 'category_id' || $field == 'severity' || $field == 'vote')
	{ $tf_report_val = 25; }
      # Very moderately important fields
      if ($field == 'submitted_by' || $field == 'assigned_to')
	{ $tf_report_val = 50; }      
      
      
      print '<TR class="'. utils_get_alt_row_color($i) .'">';

      print "\n<td>".trackers_data_get_label($field).'</td>'.
	"\n<td>".trackers_data_get_description($field).'</td>'.
	"\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1" /></td>'.
	"\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="" size="5" maxlen="5" /></td>';
      
     # If the current field is item id, we force its presence on the report
     # with rank 0. This field is mandatory: otherwise some links would be
     # broken or there would be even no links.
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

      # fetch the report to update
      $sql = "SELECT * FROM ".ARTIFACT."_report WHERE report_id=$report_id";
      $res=db_query($sql);
      $rows = db_numrows($res);
      if (!$rows)
	{
	  exit_error('Error',"Unknown Report ID ($report_id)");
	}

      # make sure this user has the right to modify the bug report
      if ( (db_result($res,0,'scope') == 'P') &&
	   !user_ismember($group_id,'A'))
	{
	  exit_permission_denied();
	}
      
      $sql_fld = "SELECT * FROM ".ARTIFACT."_report_field WHERE report_id=$report_id";
      $res_fld=db_query($sql_fld);
      
      # Build the list of fields involved in this report
      while ( $arr = db_fetch_array($res_fld) )
	{
	  $fld[$arr['field_name']] = $arr;
	}
      
      # display the table of all fields that can be included in the report
      # along with their current state in this report
      $title_arr=array();
      $title_arr[]=_("Field Label");
      $title_arr[]=_("Description");
      $title_arr[]=_("Use as a Search Criteria");
      $title_arr[]=_("Rank on Search");
      $title_arr[]=_("Use as an Output Column");
      $title_arr[]=_("Rank on Output");
      $title_arr[]=_("Column width (optional)");

      print '<form action="'.$PHP_SELF.'" method="post">
	   <input type="hidden" name="update_report" value="y" />
	   <input type="hidden" name="group_id" value="'.$group_id.'" />
	   <input type="hidden" name="report_id" value="'.$report_id.'" />
	   <input type="hidden" name="post_changes" value="y" />
	   <span class="preinput">'._("Name:").' </span><br />&nbsp;&nbsp;&nbsp;
	   <input type="text" name="rep_name" value="'.db_result($res,0,'name').'" size="20" maxlength="20" />';



  #                &nbsp;&nbsp;&nbsp;&nbsp;<strong>'._("Scope:").' </strong>';
  #$scope = db_result($res,0,'scope');
  #if (user_ismember($group_id,'A'))
  #  { print '<SELECT NAME="rep_scope">
  #                      <option value="i"'.($scope=='i' ? 'selected':'').'>'._("Personal").'</option>
  #                      <option value="p"'.($scope=='p' ? 'selected':'').'>'._("Project").'</option>
  #                      </select>'; }
  # else {
  #  print ($scope=='P' ? 'Project':'Personal').
  #    '<input type="hidden" name="rep_scope" value="'.$scope.'" />'; }


      print '
	    <p>
	       <span class="preinput">'._("Description:").'</span><br />&nbsp;&nbsp;&nbsp;
	    <input type="text" name="rep_desc" value="'.db_result($res,0,'description').'" size="50" maxlength="120" /></p>
                  <p>';

      print html_build_list_table_top ($title_arr);
      $i=0;
      while ( $field = trackers_list_all_fields() )
	{

# Do not show fields not used by the project
	  if ( !trackers_data_is_used($field))
	    { continue; }

# Do not show some special fields any way
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

	  $cb_search_chk = ($fld[$field]['show_on_query'] ? 'checked="checked"':'');
	  $cb_report_chk = ($fld[$field]['show_on_result'] ? 'checked="checked"':'');
	  $tf_search_val = $fld[$field]['place_query'];
	  $tf_report_val = $fld[$field]['place_result'];
	  $tf_colwidth_val = $fld[$field]['col_width'];

	  print '<tr class="'. utils_get_alt_row_color($i) .'">';

	  print "\n<td>".trackers_data_get_label($field).'</td>'.
	    "\n<td>".trackers_data_get_description($field).'</td>'.
	    "\n<td align=\"center\">".'<input type="checkbox" name="'.$cb_search.'" value="1" '.$cb_search_chk.'  /></td>'.
	    "\n<td align=\"center\">".'<input type="text" name="'.$tf_search.'" value="'.$tf_search_val.'" size="5" maxlen="5" /></td>';
# If the current field is item id, we force it's presence on the report
# with rank 0. This field is mandatory: otherwise some links would be
     # broken or there would be even no links.
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

  $sql = "SELECT * FROM ".ARTIFACT."_report WHERE group_id=$group_id ".
     ' AND (user_id='.user_getid().' OR scope=\'P\')';
  $res=db_query($sql);
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
	      print '<a href="'.$PHP_SELF.'?group='.$group_name.
		'&show_report=1&report_id='.$arr['report_id'].'">'.
		$arr['report_id'].'</a>';
	      print "</td>\n";
	      print '<td><a href="'.$PHP_SELF.'?group='.$group_name.
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
	      print '<a href="'.$PHP_SELF.'?group='.$group_name.
		'&amp;delete_report=1&amp;report_id='.$arr['report_id'].
		'&amp;rep_name='.$arr['name'].'">'.
		'<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" border="0" /></A>';
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

  printf ('<p>'._("You can %s create a new query form%s").'</p>','<a href="'.$_SERVER["PHP_SELF"].'?group='.$group_name.'&new_report=1">','</a>');
}

trackers_footer(array());

?>
