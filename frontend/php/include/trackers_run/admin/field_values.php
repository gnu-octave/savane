<?php
# <one line to give a brief idea of what this does.>
# 
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
#
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


$is_admin_page='y';

extract(sane_import('request', array(
  'func', 'update_value',
  'field', 'fv_id',
  'create_canned', 'update_canned', 'item_canned_id',
)));
extract(sane_import('get', array(
  'list_value',
  'delete_canned',
  'transition_id',
)));
extract(sane_import('post', array(
  'post_changes',
  'create_value',
  'title', 'description', 'order_id',
  'body',
  'by_field_id', 'from', 'to', 'allowed', 'mail_list',
  'status',
)));

if ($group_id && user_ismember($group_id,'A'))
{
# Initialize global bug structures
  trackers_init($group_id);


# ################################ Update the database

  if ($func == "deltransition")
    {
      $result = db_execute("DELETE FROM trackers_field_transition WHERE transition_id = ? LIMIT 1",
			   array($transition_id));
      if (!$result)
	{
	  fb(_("Error deleting transition"),1);
	}
      else
	{
	  fb(_("Transition deleted"));
	}
    }
  elseif ($post_changes || $delete_canned)
    {
# A form of some sort was posted to update or create
# an existing value

# Deleted Canned doesnt need a forum, so let switch
# into this code

      if ($create_value)
	{
# A form was posted to update a field value
	  if ($title)
	    {
	      trackers_data_create_value($field,
					 $group_id,
					 htmlspecialchars($title),
					 htmlspecialchars($description),
					 $order_id,
					 'A');
	    }
	  else
	    {
	      fb(_("Empty field value not allowed"), 1);
	    }

	}
      else if ($update_value)
	{
# A form was posted to update a field value
	  if ($title)
	    {
	      trackers_data_update_value($fv_id, 
					 $field, 
					 $group_id,
					 htmlspecialchars($title),
					 htmlspecialchars($description),
					 $order_id,
					 $status);
	    }
	  else
	    {
	      fb(_("Empty field value not allowed"), 1);
	    }

	}
      else if ($create_canned)
	{

# A form was posted to create a canned response
	  $sql="INSERT INTO ".ARTIFACT."_canned_responses (group_id,title,body,order_id) ".
	    " VALUES ('$group_id','". htmlspecialchars($title) .
	    "','". htmlspecialchars($body) ."','".addslashes($order_id)."')";
	  $result=db_query($sql);
	  if (!$result)
	    {
	      fb(_("Error inserting canned bug response"),1);
	    }
	  else
	    {
	      fb(_("Canned bug response inserted"));
	    }

	}
      else if ($update_canned)
	{
# A form was posted to update a canned response
	  $sql="UPDATE ".ARTIFACT."_canned_responses ".
	    "SET title='". htmlspecialchars($title) ."', body='". htmlspecialchars($body)."', order_id='".addslashes($order_id)."'".
	    " WHERE group_id='$group_id' AND bug_canned_id='$item_canned_id'";
	  $result=db_query($sql);
	  if (!$result)
	    {
	      fb(_("Error updating canned bug response"),1);
	    }
	  else
	    {
	      fb(_("Canned bug response updated"));
	    }
	}
# Delete Response
      else if ($delete_canned == '1')
	{
	  $result = db_execute("DELETE FROM ".ARTIFACT."_canned_responses ".
	    "WHERE group_id=? AND bug_canned_id=?",
            array($group_id, $item_canned_id));
	  if (!$result) {
	    fb(_("Error deleting canned bug response"),1);
	  }
	  else
	    {
	      fb(_("Canned bug response deleted"));
	    }
	}
    }

  $field_id = ($by_field_id ? $field : trackers_data_get_field_id($field));


  if ($to != $from) {
# A form was posted to update or create a transition
    $sql="SELECT from_value_id,to_value_id,is_allowed,notification_list ".
      "FROM trackers_field_transition ".
      "WHERE group_id='$group_id' AND artifact='".ARTIFACT."' AND field_id='$field_id' AND from_value_id='$from' AND to_value_id='$to' ";
    $res_value = db_query($sql);
    $rows=db_numrows($res_value);

# If no entry for this transition, create one
    if ($rows == 0)
      {

	$sql = "INSERT INTO  trackers_field_transition ".
	  "(group_id,artifact,field_id,from_value_id,to_value_id,is_allowed,notification_list) ".
	  "VALUES ('$group_id','".ARTIFACT."','$field_id','$from','$to','$allowed','$mail_list')";
	$result = db_query($sql);

	if (db_affected_rows($result) < 1)
	  {  fb(_("Insert failed."), 1); }
	else
	  { fb(_("New transition inserted.")); }
      }
    else
      {
# update the existing entry for this transition
	$sql = "UPDATE trackers_field_transition ".
	  "SET is_allowed='$allowed',notification_list='$mail_list' ".
	  "WHERE group_id='$group_id' AND artifact='".ARTIFACT."' AND field_id='$field_id'  AND from_value_id='$from' AND to_value_id='$to' ";
	$result = db_query($sql);

	if (db_affected_rows($result) < 1)
	  {
	    fb(_("Update of transition failed."), 1);
	  }
	else
	  {
	    fb(_("Transition updated."));
	  }
      }
  }


# ################################  Display the UI form

  if ($list_value)
    {

# Display the List of values for a given bug field

      $hdr = sprintf(_("Edit Field Values for '%s'"),trackers_data_get_label($field));

      if (trackers_data_get_field_id($field) &&
	   trackers_data_is_select_box($field))
	{
          # First check that this field is used by the project and
          # it is in the project scope

	  $is_project_scope = trackers_data_is_project_scope($field);

	  trackers_header_admin(array ('title'=>$hdr));
	  
	  print '<h2>'._("Field Label:").' '.trackers_data_get_label($field).' &nbsp;&nbsp; <span class="smaller">('.utils_link($GLOBALS['sys_home'].ARTIFACT.'/admin/field_usage.php?group='.$group.'&amp;update_field=1&amp;field='.$field, _("Jump to this field usage")).")</span></h2>\n";



	  $result = trackers_data_get_field_predefined_values($field, $group_id,false,false,false);
	  $rows = db_numrows($result);

	  if ($result && $rows > 0)
	    {
	      print "\n<h3>".html_anchor(_("Existing Values"),"existing").'</h3>';

	      $title_arr=array();
	      if (!$is_project_scope)
		{ $title_arr[]='ID'; }
	      $title_arr[]=_("Value label");
	      $title_arr[]=_("Description");
	      $title_arr[]=_("Rank");
	      $title_arr[]=_("Status");
	      $title_arr[]=_("Occurences");


	      $hdr = html_build_list_table_top ($title_arr);

	      $ia = $ih = 0;
	      $status_stg = array('A' => _("Active"), 'P' => _("Permanent"), 'H' => _("Hidden"));

# Display the list of values in 2 blocks : active first
# Hidden second
	      $ha = '';
	      $hh = '';
	      while ( $fld_val = db_fetch_array($result) )
		{
		  $item_fv_id = $fld_val['bug_fv_id'];
		  $status = $fld_val['status'];
		  $value_id = $fld_val['value_id'];
		  $value = $fld_val['value'];
		  $description = $fld_val['description'];
		  $order_id = $fld_val['order_id'];
		  if ($field == 'comment_type_id')
		    // FIXME: not a table field... weird
		    $usage = 0;
		  else
		    $usage = trackers_data_count_field_value_usage($group_id, $field, $value_id);

		  $html = '';

# keep the rank of the 'None' value in mind if any (see below)
		  if ($value == 100)
		    { $none_rk = $order_id; }

# Show the value ID only for system wide fields which
# value id are fixed and serve as a guide.
		  if (!$is_project_scope)
		    $html .='<td>'.$value_id.'</td>';

# The permanent values cant be modified (No link)
		  if ($status == 'P')
		    {
		      $html .= '<td>'.$value.'</td>';
		    }
		  else
		    {
		      $html .= '<td><a href="'.$_SERVER['PHP_SELF'].'?update_value=1'.
			'&fv_id='.$item_fv_id.'&field='.$field.
			'&group_id='.$group_id.'">'.$value.'</A></td>';
		    }

		  $html .= '<td>'.$description.'&nbsp;</td>'.
		    '<td align="center">'.$order_id.'</td>'.
		    '<td align="center">'.$status_stg[$status].'</td>';

		  if ($status == 'H' && $usage > 0)
		    {
		      $html .= '<td align="center"><strong class="warn">'.$usage.'</strong></td>';
		    }
		  else
		    {
		      $html .= '<td align="center">'.$usage.'</td>';
		    }

		  if ($status == 'A' || $status == 'P')
		    {
		      $html = '<tr class="'.
			utils_get_alt_row_color($ia) .'">'.$html.'</tr>';
		      $ia++;
		      $ha .= $html;
		    }
		  else
		    {
		      $html = '<tr class="'.
			utils_get_alt_row_color($ih) .'">'.$html.'</tr>';
		      $ih++;
		      $hh .= $html;
		    }

		}

# Display the list of values now
	      if ($ia == 0)
		{
		  $hdr = '<p>'._("No active value for this field. Create one or reactivate a hidden value (if any)").'</p>'.$hdr;
		}
	      else
		{
		  $ha = '<tr><td colspan="4" class="center"><strong>'._("---- ACTIVE VALUES ----").'</strong></tr>'.$ha;
		}
	      if ($ih)
		{
		  $hh = '<tr><td colspan="4"> &nbsp;</td></tr>'.
		    '<tr><td colspan="4"><center><strong>'._("---- HIDDEN VALUES ----").'</strong></center></tr>'.$hh;
		}

	      print $hdr.$ha.$hh.'</table>';

	    }
	  else
	    {
	      printf ("\n<h2>"._("No values defined yet for %s").'</h2>',trackers_data_get_label($field));
	    }


# Only show the add value form if this is a project scope field
	  if ($is_project_scope)
	    {

	      print '<br />';
	      print '<h3>'.html_anchor(_("Create a new field value"),"create").'</h3>';

	      if ($ih)
		{
		  print '<p>'._("Before you create a new value make sure there isn't one in the hidden list that suits your needs.").'</p>';
		}

# yeupou--gnu.org 2004-09-12: a red star should mention with fields
# are mandatory

	      print '
      <form action="'.$_SERVER['PHP_SELF'].'" method="post">
      <input type="hidden" name="post_changes" value="y" />
      <input type="hidden" name="create_value" value="y" />
      <input type="hidden" name="list_value" value="y" />
      <input type="hidden" name="field" value="'.$field.'" />
      <input type="hidden" name="group_id" value="'.$group_id.'" />
      <span class="preinput">'._("Value:").' </span>'.
		form_input("text", "title", "", 'size="30" maxlength="60"').'
      &nbsp;&nbsp;
      <span class="preinput">'._("Rank:").' </span>'.
		form_input("text", "order_id", "", 'size="6" maxlength="6"');

	      if (isset($none_rk))
		{
		  print "&nbsp;&nbsp;<strong> (must be &gt; $none_rk)</strong><br /></p>";
		}

	      print '
      <p>
      <span class="preinput">'._("Description (optional):").'</span><br />
      <textarea name="description" rows="4" cols="65" wrap="hard"></textarea></p>
      <div class="center">
        <input type="submit" name="submit" value="'._("Update").'" />
      </div>
      </form>';

	    }

          # If the project use custom values, propose to reset to the default
	  if (trackers_data_use_field_predefined_values($field,$group_id)) {

	    print '<h3>'._("Reset values").'</h3>';
	    print '<p>'._("You are currently using custom values. If you want to reset values to the default ones, use the following form:").'</p>';

	    print '<form action="field_values_reset.php" method="post" class="center">';
	    print '<input type="hidden" name="group_id" value="'.$group_id.'" />';
	    print '<input type="hidden" name="field" value="'.$field.'" />';
	    print '<input type="submit" name="submit" value="'._("Reset values").'" /> ';
	    print '</form>';



	    print '<p>'._("For your information, the default active values are:").'</p>';

	    $default_result = trackers_data_get_field_predefined_values($field, '100',false,false,false);
	    $default_rows = db_numrows($default_result);
	    $previous = false;
	    if ($default_result && $default_rows > 0)
	      {
		while ($fld_val = db_fetch_array($default_result))
		  {
		    $status = $fld_val['status'];
		    $value = $fld_val['value'];
		    $description = $fld_val['description'];
		    $order = $fld_val['order_id'];

		    # non-active value are not important here
		    if ($status != "A")
		      { continue; }

		    if ($previous)
		      { print ", "; }

		    print '<strong>'.$value.'</strong> <span class="smaller">('.$order.', "'.$description.'")</span>';
		    $previous = true;
		  }

	      }
	    else
	      {
		fb(_("No default values found. You should report this problem to administrators."), 1);
	      }


	  }

	}
      else
	{
	  
	  exit_error(sprintf(_("The field you requested '%s' is not used by your project or you are not allowed to customize it"),$field));
	}


# Transitions
# yeupou--gnu.org 2004-09-12: where the hell is by_field_id set?
      $field_id = ($by_field_id ? $field : trackers_data_get_field_id($field));
      if ( trackers_data_get_field_id($field) &&
           trackers_data_is_select_box($field))
        {

# First get all the value_id - value pairs
	  $sql="SELECT value_id,value ".
	    "FROM ".ARTIFACT."_field_value ".
	    "WHERE group_id='".addslashes($group_id)."' AND bug_field_id='".addslashes($field_id)."'";
          $res_value = db_query($sql);
          $rows=db_numrows($res_value);

          if ($rows > 0)
            {
	      $val_label = array();
	      while ($val_row = db_fetch_array($res_value))
		{
		  $value_id = $val_row['value_id'];
		  $value   = $val_row['value'];
		  $val_label[$value_id] = $value;
		}
            }
          else
            {
	      $sql="SELECT value_id,value ".
		"FROM ".ARTIFACT."_field_value ".
		"WHERE group_id=100 AND bug_field_id='".addslashes($field_id)."'";
	      $res_value = db_query($sql);
	      $rows=db_numrows($res_value);

	      if ($rows > 0)
		{
		  $val_label = array();
		  while ($val_row = db_fetch_array($res_value))
		    {
		      $value_id = $val_row['value_id'];
		      $value   = $val_row['value'];
		      $val_label[$value_id] = $value;
		    }
		}
	    }

          $sql="SELECT transition_id,from_value_id,to_value_id,is_allowed,notification_list ".
	    "FROM trackers_field_transition ".
	    "WHERE group_id='".addslashes($group_id)."' AND artifact='".ARTIFACT."' AND field_id='".addslashes($field_id)."' ";

          $result = db_query($sql);
          $rows = db_numrows($result);

          if ($result && $rows > 0)
            {
              print "\n\n<p>&nbsp;</p><h3>".html_anchor(_("Registered Transitions"),"registered").'</h3>';

              $title_arr=array();
              $title_arr[]=_("From");
              $title_arr[]=_("To");
              $title_arr[]=_("Is Allowed");
              $title_arr[]=_("Others Fields Update");
              $title_arr[]=_("Carbon-Copy List");
              $title_arr[]=_("Delete?");


              $hdr = html_build_list_table_top ($title_arr);
              print $hdr;

              $reg_default_auth = '';
	      $z = 1;
              while ($transition = db_fetch_array($result))
                {
		  $z++;
                  if ($transition['is_allowed'] == 'A') {
                    $allowed = _("Yes");
                  } else {
                    $allowed = _("No");
                  }

		  print '<tr class="'.utils_altrow($z).'">';
		  if (!empty($val_label[$transition['from_value_id']]))
		    {
		      print '<td align="center">'.$val_label[$transition['from_value_id']].'</td>';
		    }
		  else
		    {
		      print '<td align="center">'._("* - Any").'</td>';
		    }

		  print '<td align="center">'.$val_label[$transition['to_value_id']].'</td>'
		    .'<td align="center">'.$allowed.'</td>';

                  if ($transition['is_allowed'] == 'A')
		    {
		      print '<td align="center">';
# Get list of registered field updates
		      $registered = trackers_transition_get_other_field_update($transition['transition_id']);
# Make sure $content is clean
		      $content = '';
# No result? Print only a link
		      if ($registered)
			{
			  while ($entry = db_fetch_array($registered))
			    {
# Add one entry per registered other field update
			      $content .= trackers_data_get_label($entry['update_field_name']).":".trackers_data_get_value($entry['update_field_name'], $group_id, $entry['update_value_id']).", ";
			    }
# Remove extra comma
			  $content = trim($content, ", ");
			}
		      else
			{
			  $content = _("Edit others fields update");
			}

		      print utils_link($GLOBALS['sys_home'].ARTIFACT."/admin/field_values_transition-ofields-update.php?group=".$group."&amp;transition_id=".$transition['transition_id'], $content);
		      print '</td><td align="center">'.$transition['notification_list'].'</td>';
		    }
		  else
		    {
		      print '<td align="center">---------</td>'
			.'<td align="center">--------</td>';
		    }
                  print '<td align="center">'.utils_link($_SERVER['PHP_SELF'].'?group='.$group.'&amp;func=deltransition&amp;transition_id='.$transition['transition_id'].'&amp;list_value=1&amp;field='.$field, '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" border="0" alt="'._("Delete this transition?").'" />').'</td>';
                  print '</tr>';
                }

	      print '</table>';
            }
          else
            {
              $reg_default_auth = '';
              printf ("\n\n<p>&nbsp;</p><h3>"._("No transition defined yet for %s").'</h3>',trackers_data_get_label($field));
            }


	  print '
                     <form action="'.$_SERVER['PHP_SELF'].'#registered" method="post">
                     <input type="hidden" name="post_transition_changes" value="y" />
                     <input type="hidden" name="list_value" value="y" />
                     <input type="hidden" name="field" value="'.$field.'" />
                     <input type="hidden" name="group_id" value="'.$group_id.'" />';

	  $result = db_execute("SELECT transition_default_auth FROM ".ARTIFACT."_field_usage "
			       ." WHERE group_id=? AND bug_field_id=?",
			       array($group_id, trackers_data_get_field_id($field)));
	  if (db_numrows($result) > 0 and db_result($result, 0, 'transition_default_auth') == "F")
	    {
              $transition_for_field = _("By default, for this field, the transitions not registered are forbidden. This setting can be changed when managing this field usage.");
	    }
	  else
	    {
              $transition_for_field = _("By default, for this field, the transitions not registered are allowed. This setting can be changed when managing this field usage.");
	    }
	  print "\n\n<p>&nbsp;</p><h3>".html_anchor(_("Create / Edit a transition"),"create").'</h3>';

	  print "<p>$transition_for_field</p>\n";
	  print '<p>'._("Once a transition created, it will be possible to set \"Others Fields Update\" for this transition.").'</p>';

	  $title_arr=array();
	  $title_arr[]=_("From");
	  $title_arr[]=_("To");
	  $title_arr[]=_("Is Allowed");
	  $title_arr[]=_("Carbon-Copy List");

	  $auth_label = array();
	  $auth_label[] = 'allowed';
	  $auth_label[] = 'forbidden';
	  $auth_val = array();
	  $auth_val[] = 'A';
	  $auth_val[] = 'F';

	  $hdr = html_build_list_table_top ($title_arr);

	  $from    = '<td>'.trackers_field_box($field,'from',$group_id,false,false, false, 1, _("* - Any")).'</td>';
	  $to      = '<td>'.trackers_field_box($field,'to',$group_id,false,false).'</td>';
	  print $hdr.'<tr>'.$from.$to;

	  print '<td>'.html_build_select_box_from_arrays ($auth_val,$auth_label,'allowed', 'allowed',false).'</td>';

	  $mlist   = '<td>'.'
              <input type="text" name="mail_list" value="" size="30" maxlength="60" />
              </td>';

	  print $mlist.'</tr></table>';

	  print '<div align="center"><input type="submit" name="submit" value="'._("Update Transition").'" /></div>
                     </form>';
        }
      else
        {

          print "\n\n<p>&nbsp;</p><h3>";
          printf (_("The Bug field you requested '%s' is not used by your project or you are not allowed to customize it"),$field);
          print '</h3>';
        }

    }
  else if ($update_value)
    {
# Show the form to update an existing field_value
# Display the List of values for a given bug field

      trackers_header_admin(array ('title'=>_("Edit Fields Values")));

# Get all attributes of this value
      $res = trackers_data_get_field_value($fv_id);

      print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">
      <input type="hidden" name="post_changes" value="y" />
      <input type="hidden" name="update_value" value="y" />
      <input type="hidden" name="list_value" value="y" />
      <input type="hidden" name="fv_id" value="'.$fv_id.'" />
      <input type="hidden" name="field" value="'.$field.'" />
      <input type="hidden" name="group_id" value="'.$group_id.'" />
      <p><span class="preinput">'
	._("Value:").' </span><br />'.
		form_input("text", "title", db_result($res,0,'value'), 'size="30" maxlength="60"').'
      &nbsp;&nbsp;
      <span class="preinput">'._("Rank:").' </span>'.
		form_input("text", "order_id", db_result($res,0,'order_id'), 'size="6" maxlength="6"').'
      &nbsp;&nbsp;
      <span class="preinput">'
	._("Status:").' </span>
      <select name="status">
	   <option value="A">'
	._("Active").'</option>
	   <option value="H"'.((db_result($res,0,'status') == 'H') ? ' selected="selected"':'').'>'
	._("Hidden").'</option>
      </select>
      <p>
      <span class="preinput">'._("Description: (optional)").'</span><br />
      <textarea name="description" rows="4" cols="65" wrap="soft">'.db_result($res,0,'description').'</textarea></p>';

      $count = trackers_data_count_field_value_usage($group_id, $field, db_result($res,0,'value_id'));
      if ($count > 0)
	{
	  print '<p class="warn">';
          printf(ngettext("This field value applies to %s item of your tracker.", "This field value applies to %s items of your tracker.", $count)." ", $count);
          printf(_("If you hide this field value, the related items will have no value in the field '%s'."), $field).'</p>';
	}
      print '
      <div class="center">
        <input type="submit" name="submit" value="'._("Submit").'" />
      </p>';


    }
  else if ($create_canned || $delete_canned)
    {
      /*
	  Show existing responses and UI form
      */
      trackers_header_admin(array ('title'=>_("Create/Modify Canned Responses")));

      $sql="SELECT * FROM ".ARTIFACT."_canned_responses WHERE group_id='$group_id' ORDER BY order_id ASC";
      $result=db_query($sql);
      $rows=db_numrows($result);

      if($result && $rows > 0)
	{
	  /*
	      Links to update pages
	  */
	  print "\n<h3>"._("Existing Responses:").'</h3><p>';

	  $title_arr=array();
	  $title_arr[]=_("Title");
	  $title_arr[]=_("Body (abstract)");
	  $title_arr[]=_("Rank");
	  $title_arr[]=_("Delete?");

	  print html_build_list_table_top ($title_arr);

	  for ($i=0; $i < $rows; $i++)
	    {
# FIXME: delete should use the basket, like it is done in many
# other places
	      print '<tr class="'. utils_get_alt_row_color($i) .'">'.
		'<td><a href="'.$_SERVER['PHP_SELF'].'?update_canned=1&amp;item_canned_id='.
		db_result($result, $i, 'bug_canned_id').'&amp;group_id='.$group_id.'">'.
		db_result($result, $i, 'title').'</A></TD>'.
		'<td>'.substr(db_result($result, $i, 'body'),0,360).'...'.
		'<td>'.db_result($result, $i, 'order_id').'<td class="center"><a href="'.$_SERVER['PHP_SELF'].'?delete_canned=1&amp;item_canned_id='.
		db_result($result, $i, 'bug_canned_id').'&amp;group_id='.$group_id.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" border="0" alt="'._("Delete this canned answer?").'" />
		</a></td></tr>';
	    }
	  print '</table>';

	}
      else
	{
	  print "\n<h3>"._("No canned bug responses set up yet").'</h3>';
	}
      /*
	  Escape to print the add response form
      */

      print '<h3>'._("Create a new response").'</h3>
     <p>
     '._("Creating generic quick responses can save a lot of time when giving common responses.").'</p>
     <form action="'.$_SERVER['PHP_SELF'].'" method="post">
     <input type="hidden" name="create_canned" value="y" />
     <input type="hidden" name="group_id" value="'.$group_id.'" />
     <input type="hidden" name="post_changes" value="y" />
     <span class="preinput">'._("Title:").'</span><br />
     &nbsp;&nbsp;<input type="text" name="title" value="" size="50" maxlength="50" /><br />
     <span class="preinput">'._("Rank (useful in multiple canned responses):").'</span><br />
     &nbsp;&nbsp;<input type="text" name="order_id" value="" maxlength="50" /><br />
     <span class="preinput">'._("Message Body:").'</span><br />
     &nbsp;&nbsp;<textarea name="body" rows="20" cols="65" wrap="hard"></textarea>
     <div class="center">
       <input type="submit" name="submit" value="'._("Submit").'" />
     </div>
     </form>';



    }
  else if ($update_canned)
    {
#  Allow change of canned responses
      trackers_header_admin(array ('title'=>_("Modify Canned Response")));

      $sql="SELECT bug_canned_id,title,body,order_id FROM ".ARTIFACT."_canned_responses WHERE ".
	"group_id='".addslashes($group_id)."' AND bug_canned_id='".addslashes($item_canned_id)."'";

      $result=db_query($sql);

      if (!$result || db_numrows($result) < 1)
	{
	  fb(_("No such response!"),1);
	}
      else
	{
# Escape to print update form

	  print '<p>'
	    ._("Creating generic messages can save you a lot of time when giving common responses.").'</p>
      <p>
      <form action="'.$_SERVER['PHP_SELF'].'" method="post">
      <input type="hidden" name="update_canned" value="y" />
      <input type="hidden" name="group_id" value="'.$group_id.'" />
      <input type="hidden" name="item_canned_id" value="'.$item_canned_id.'" />
      <input type="hidden" name="post_changes" value="y" />
      <span class="preinput">'._("Title").':</span><br />
      &nbsp;&nbsp;<input type="text" name="title" value="'.db_result($result,0,'title').'" size="50" maxlength="50" /></p>
      <p>
      <span class="preinput">'._("Rank").':</span><br />
      &nbsp;&nbsp;<input type="text" name="order_id" value="'.db_result($result,0,'order_id').'" /></p>
      <p>
      <span class="preinput">'._("Message Body:").'</span><br />
      &nbsp;&nbsp;<textarea name="body" rows="20" cols="65" wrap="hard">'.db_result($result,0,'body').'</textarea></p>
      <div class="center">
        <input type="submit" name="submit" value="Submit" />
      </div>
      </form>';

	}

    }
  else
    {

######## Complete list of fields

      trackers_header_admin(array ('title'=>_("Edit Fields Values")));

      # Add space to avoid overlaps
      print '<br />';

# Loop through the list of all used fields that are project manageable
      $i=0;
      $title_arr=array();
      $title_arr[]=_("Field Label");
      $title_arr[]=_("Description");
      $title_arr[]=_("Scope");
      print html_build_list_table_top ($title_arr);
      while ( $field_name = trackers_list_all_fields() )
	{

	  if ( trackers_data_is_select_box($field_name)
	       && ($field_name != 'submitted_by')
	       && ($field_name != 'assigned_to')
	       && trackers_data_is_used($field_name) )
	    {

	      $scope_label  = (trackers_data_is_project_scope($field_name)?
			       _("Project"):_("System"));

	      print '<tr class="'. utils_get_alt_row_color($i) .'">'.
		'<td><a href="'.$_SERVER['PHP_SELF'].'?group_id='.$group_id.'&list_value=1&field='.$field_name.'">'.trackers_data_get_label($field_name).'</a></td>'.
		"\n<td>".trackers_data_get_description($field_name).'</td>'.
		"\n<td>".$scope_label.'</td>'.
		'</tr>';
	      $i++;
	    }
	}

# Now the special canned response field
      print '<tr class="'. utils_get_alt_row_color($i) .'">';
      print "<td><a href=\"{$_SERVER['PHP_SELF']}?group_id=$group_id&amp;create_canned=1\">"._("Canned Responses").'</a></td>';
      print "\n<td>"._("Create or change generic quick response messages for this issue tracker. These pre-written messages can then be used to quickly reply to item submissions.").' </td>';
      print "\n<td>"._("Project").'</td></tr>';
      print '</table>';
    }

  trackers_footer(array());

}
else
{
#browse for group first message
  if (!$group_id)
    {
      exit_no_group();
    }
  else
    {
      exit_permission_denied();
    }

}

?>