<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2001-2002 (c) Laurent Julliard, CodeX Team, Xerox
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
#
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

require_once(dirname(__FILE__).'/../trackers/transition.php');
 
/*

Simple way of wrapping our SQL so it can be
	shared among the XML outputs and the PHP web front-end

	Also abstracts controls to update data

*/
function trackers_data_get_all_fields ($group_id=false,$reload=false)
{

  /*
           Get all the possible bug fields for this project both used and unused. If
           used then show the project specific information about field usage
           otherwise show the default usage parameter
           Make sure array element are sorted by ascending place
  */

  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME, $AT_START;

# Do nothing if already set and reload not forced
  if (isset($BF_USAGE_BY_ID) && !$reload)
    {
      return;
    }

# Clean up the array
  $BF_USAGE_BY_ID=array();
  $BF_USAGE_BY_NAME=array();

# First get the all the defaults.
  $sql='SELECT '.ARTIFACT.'_field.bug_field_id, field_name, display_type, '.
    'display_size,label, description,scope,required,empty_ok,keep_history,special, custom, '.
    'group_id, use_it,show_on_add,show_on_add_members, place, custom_label,'.
    'custom_description,custom_display_size,custom_empty_ok,custom_keep_history '.
    'FROM '.ARTIFACT.'_field, '.ARTIFACT.'_field_usage '.
    'WHERE group_id=100  '.
    'AND  '.ARTIFACT.'_field.bug_field_id='.ARTIFACT.'_field_usage.bug_field_id ';

  $res_defaults = db_query($sql);

# Now put all used fields in a global array for faster access
# Index both by field_name and bug_field_id
  while ($field_array = db_fetch_array($res_defaults))
    {
      $BF_USAGE_BY_ID[$field_array['bug_field_id'] ] = $field_array;
      $BF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

# Then select  all project specific entries
  $sql='SELECT  '.ARTIFACT.'_field.bug_field_id, field_name, display_type, '.
    'display_size,label, description,scope,required,empty_ok,keep_history,special, custom, '.
    'group_id, use_it, show_on_add, show_on_add_members, place, custom_label,'.
    'custom_description,custom_display_size,custom_empty_ok,custom_keep_history '.
    'FROM '.ARTIFACT.'_field,  '.ARTIFACT.'_field_usage '.
    'WHERE group_id='.$group_id.
    ' AND  '.ARTIFACT.'_field.bug_field_id= '.ARTIFACT.'_field_usage.bug_field_id ';


  $res_project = db_query($sql);

# And override entries in the default array
  while ($field_array = db_fetch_array($res_project))
    {
      $BF_USAGE_BY_ID[$field_array['bug_field_id'] ] = $field_array;
      $BF_USAGE_BY_NAME[$field_array['field_name'] ] = $field_array;
    }

#Debug code
#print "<br />DBG - At end of bug_get_all_fields: $rows";
#reset($BF_USAGE_BY_NAME);
#while (list($key, $val) = each($BF_USAGE_BY_NAME))
#{
#print "<br />DBG - $key -> use_it: $val[use_it], $val[place]";
#}

# rewind internal pointer of global arrays
  reset($BF_USAGE_BY_ID);
  reset($BF_USAGE_BY_NAME);
  $AT_START = true;
}


function trackers_data_get_item_group($item_id)
{
  return  db_result(
		    db_query("SELECT group_id FROM ".ARTIFACT." WHERE bug_id='$item_id'"),
		    0,
		    'group_id');
}

function &trackers_data_get_notification_settings($group_id, $tracker_name)
{
  $result = db_query("SELECT * FROM groups WHERE group_id=$group_id");
  if (db_numrows($result) < 1)
    {
      exit_no_group();
    }

  $settings = array();

  $settings['glnotif'] = db_result($result,0,$tracker_name."_glnotif");
  $settings['glsendall'] = db_result($result,0,"send_all_".$tracker_name);
  $settings['glnewad'] = db_result($result,0,"new_".$tracker_name."_address");
  $settings['private_exclude'] = db_result($result,0,$tracker_name."_private_exclude_address");

  $cat_field_name = "category_id";
# Warning: The hardcoded fiels names: bug_fv_id and bug_field_id will need to be changed
#          one day to generic names since they apply to bugs but also to suuports,tasks,
#          and patch related tables. For now these fileds are called bug_xxx whatever
#          the service related tables. Too much work to make all the changes in the code.
  $sql="SELECT ".$tracker_name."_field_value.bug_fv_id,".$tracker_name."_field_value.value,".$tracker_name."_field_value.email_ad,".$tracker_name."_field_value.send_all_flag ".
    "FROM ".$tracker_name."_field, ".$tracker_name."_field_value ".
    "WHERE ".$tracker_name."_field_value.group_id='$group_id' ".
    "AND ".$tracker_name."_field.field_name='$cat_field_name' ".
    "AND ".$tracker_name."_field_value.bug_field_id=".$tracker_name."_field.bug_field_id ".
    "AND ".$tracker_name."_field_value.status!='H'";

  $result=db_query($sql);
  $settings['nb_categories']=db_numrows($result);
  $settings['category'] = array();
  for ($i=0; $i < $settings['nb_categories'] ; $i++) {

    $settings['category'][$i] = array();
    $settings['category'][$i]['name'] = db_result($result, $i, 'value');
    $settings['category'][$i]['fv_id'] = db_result($result, $i, 'bug_fv_id');
    $email = db_result($result, $i, 'email_ad');
    if ($email == '100')
      { $email = ""; }
    $settings['category'][$i]['email'] = $email;
    $settings['category'][$i]['send_all_flag'] = db_result($result, $i, 'send_all_flag');
  }
  return $settings;
}

function trackers_data_show_notification_settings($group_id, $tracker_name, $show_intro_msg)
{
  $grtrsettings = &trackers_data_get_notification_settings($group_id, $tracker_name);

  if (user_ismember($group_id,'A'))
    {
      if ($grtrsettings['glnotif'] == 0) {
	$categoryradio = "CHECKED";
	$globalradio = "";
	$bothradio = "";
      }
      if ($grtrsettings['glnotif'] == 1) {
	$categoryradio = "";
	$globalradio = "CHECKED";
	$bothradio = "";
      }
      if ($grtrsettings['glnotif'] == 2) {
	$categoryradio = "";
	$globalradio = "";
	$bothradio = "CHECKED";
      }
      if ($grtrsettings['nb_categories'] > 0) {
	if ($show_intro_msg != 0) {
	  print '<p>'.sprintf(_("As a project administrator you must decide if the list of persons to be systematically notified on new %s submissions (and possibly updates) depend on the categories or not and you must provide the corresponding email addresses (comma separated list)."), utils_get_tracker_name($tracker_name)).'</p>';

	}
	print '
           <INPUT TYPE="RADIO" NAME="'.$tracker_name.'_notif_scope" VALUE="global" '.$globalradio.' />&nbsp;&nbsp;<span class="preinput">'._("Notify persons in the global list only").'</span><br />
          <INPUT TYPE="RADIO" NAME="'.$tracker_name.'_notif_scope" VALUE="category" '.$categoryradio.' />&nbsp;&nbsp;<span class="preinput">'._("Notify persons in the category related list instead of the global list").'</span><br />
	  <INPUT TYPE="RADIO" NAME="'.$tracker_name.'_notif_scope" VALUE="both" '.$bothradio.' />&nbsp;&nbsp;<span class="preinput">'._("Notify persons in the category related list in addition to the global list").'</span><br />


	  <h4>'._("Category related lists").'</h4>';
	print '<INPUT TYPE="HIDDEN" NAME="'.$tracker_name.'_nb_categories" VALUE="'.$grtrsettings['nb_categories'].'" />';

	for ($i=0; $i < $grtrsettings['nb_categories'] ; $i++)
	  {
	    print '<INPUT TYPE="HIDDEN" NAME="'.$tracker_name.'_cat_'.$i.'_bug_fv_id" VALUE="'.$grtrsettings['category'][$i]['fv_id'].'" />';
	    print '<span class="preinput">'.$grtrsettings['category'][$i]['name'].'</span><br />&nbsp;&nbsp;<INPUT TYPE="TEXT" NAME="'.$tracker_name.'_cat_'.$i.'_email" VALUE="'.$grtrsettings['category'][$i]['email'].'" SIZE="50" MAXLENGTH="255" />
          &nbsp;&nbsp;<span class="preinput">(
	  <INPUT TYPE="CHECKBOX" NAME="'.$tracker_name.'_cat_'.$i.'_send_all_flag" VALUE="1" '. (($grtrsettings['category'][$i]['send_all_flag'])?'checked="checked"':'') .' />'._("Send on all updates").')</span><br />
';
	  }

	print '<h4>'._("Global list").'</h4>';

      } else {
	if ($show_intro_msg != 0) {
	  print '<p>'.sprintf(_("As a project administrator you must decide if the list of persons to be systematically notified on new %s submissions (and possibly updates) depend on the categories or not and you must provide the corresponding email addresses (comma separated list)."), utils_get_tracker_name($tracker_name)).'</p>';
	}
      }
      print '<span class="preinput">'._("Global List:").'</span><br />&nbsp;&nbsp;<INPUT TYPE="TEXT" NAME="'.$tracker_name.'_new_item_address" VALUE="'.$grtrsettings['glnewad'].'" SIZE="50" MAXLENGTH="255" />
      &nbsp;&nbsp;<span class="preinput">(<INPUT TYPE="CHECKBOX" NAME="'.$tracker_name.'_send_all_changes" VALUE="1" '. (($grtrsettings['glsendall'])?'CHECKED':'') .'>'._("Send on all updates").')</span>';

      print '<h4>'._("Private items exclude list").'</h4>';
      if ($show_intro_msg != 0) {
	print '<p>'._("Addresses registered in this list will be excluded from default mail notification for private items.").'</p>';
      }

      print '<span class="preinput">'._("Exclude List:").'</span><br />&nbsp;&nbsp;<INPUT TYPE="TEXT" NAME="'.$tracker_name.'_private_exclude_address" VALUE="'.$grtrsettings['private_exclude'].'" SIZE="50" MAXLENGTH="255" /><br />';

    }
}

function trackers_data_post_notification_settings($group_id, $tracker_name)
{

  global $feedback;

  $local_feedback = "";
# build the variable names related to elements always present in the form
# and get their values

  $notif_scope_name = $tracker_name."_notif_scope";
  $notif_scope = $GLOBALS[$notif_scope_name];
  $new_item_address_name = $tracker_name."_new_item_address";
  $new_item_address = $GLOBALS[$new_item_address_name];
  $send_all_changes_name = $tracker_name."_send_all_changes";
  $send_all_changes = $GLOBALS[$send_all_changes_name];
  $nb_categories_name = $tracker_name."_nb_categories";
  $nb_categories = $GLOBALS[$nb_categories_name];
  $private_exclude_address_name = $tracker_name."_private_exclude_address";
  $private_exclude_address = $GLOBALS[$private_exclude_address_name];

  if (isset($GLOBALS[$notif_scope_name])) {
    if ($notif_scope != "global") {
      if ($notif_scope == "category") {
        $notif_value = 0;
      }
      if ($notif_scope == "both") {
        $notif_value = 2;
      }
    } else {
      $notif_value = 1; # global only
			  }
  } else {
    $notif_value = 1; # global only (scope not proposed = no categories)
			}

# set global notification info for this group
  $res_gl=db_query("UPDATE groups SET "
		   .$tracker_name."_glnotif='$notif_value', "
		   ."send_all_".$tracker_name."='$send_all_changes', "
		   ."new_".$tracker_name."_address=".($new_item_address? "'$new_item_address' " : "''").", "
		   .$private_exclude_address_name.'='.($private_exclude_address? "'$private_exclude_address' " : "''")
		   . " WHERE group_id=$group_id");
  if (!$res_gl)
    { $local_feedback .= _("groups table Update failed.").' '.db_error(); }

  $ok = 0;
  if ($nb_categories > 0) {
    for ($i=0; $i<$nb_categories; $i++) {
      $current_fv_name = $tracker_name."_cat_".$i."_bug_fv_id";
      $current_fv_id = $GLOBALS[$current_fv_name];
      $current_email_name = $tracker_name."_cat_".$i."_email";
      $current_email = $GLOBALS[$current_email_name];
#      if ($current_email && !validate_email($current_email))
#        {
#          $local_feedback .= _("[".$tracker_name."]  notification address: ".$current_email." appeared Invalid");
#          $current_email='';
#        }
      $current_send_all_name = $tracker_name."_cat_".$i."_send_all_flag";
      $current_send_all_flag = $GLOBALS[$current_send_all_name];

      $res_cat=db_query("UPDATE ".$tracker_name."_field_value SET "
			."email_ad='$current_email', "
			."send_all_flag='$current_send_all_flag' "
			." WHERE bug_fv_id=$current_fv_id");
      if ($res_cat) {
        $ok++;
      } else {
        $local_feedback .= _($tracker_name."_field_value table Update failed.").' '.db_error();
      }
    }
  }
  if (($res_gl) && ($ok == $nb_categories) && ($local_feedback == ""))  {
    return 1;
  } else {
    if ($local_feedback != "") { fb($local_feedback); }
    return 0;
  }
}

function trackers_data_get_item_notification_info($item_id, $artifact, $updated)
{
  $emailad = "";
  $sendemail = 0;
# Get group information bur new entity notification settings
  $sql="SELECT groups.$artifact"."_glnotif, groups.send_all_"."$artifact, groups.new_"."$artifact"."_address ".
    "FROM "."$artifact, groups ".
    "WHERE "."$artifact.bug_id='$item_id' ".
    "AND groups.group_id="."$artifact.group_id";
  $result=db_query($sql);

  $glnotif = db_result($result,0,$artifact."_glnotif");
  $glsendall = db_result($result,0,"send_all_".$artifact);
  $glnewad = db_result($result,0,"new_".$artifact."_address");
  if ($glnotif != 1) {   # if not 'global only'
			   $cat_field_name = "category_id";

  $sql="SELECT "."$artifact"."_field_value.email_ad, "."$artifact"."_field_value.send_all_flag ".
    "FROM "."$artifact"."_field_value, "."$artifact"."_field, $artifact ".
    "WHERE "."$artifact.bug_id='$item_id' ".
    "AND "."$artifact"."_field.field_name='$cat_field_name' ".
    "AND "."$artifact"."_field_value.bug_field_id="."$artifact"."_field.bug_field_id ".
    "AND "."$artifact"."_field_value.group_id="."$artifact.group_id ".
    "AND "."$artifact"."_field_value.value_id="."$artifact.category_id";

  $result=db_query($sql);
  $rows=db_numrows($result);
  if ($rows > 0) {
    $sendallflag = db_result($result, 0, 'send_all_flag');
    if (($updated == 0) || (($updated == 1) && ($sendallflag == 1))) {
      $emailad .= db_result($result, 0, 'email_ad');
    }
  } else {
# could be that administrator closes category notification and forgot
# to define categories BUT in most cases it means the submitter selected
# the 'NONE' category for this bug
    if (($updated == 0) || (($updated == 1) && ($glsendall == 1))) {
      $emailad .= $glnewad;
    }
  }
  }
  if ($glnotif > 0) {   # if not 'category only'
			  if (($updated == 0) || (($updated == 1) && ($glsendall == 1))) {
			    if ($emailad != "") {
			      $emailad .= ',';
			    }
			    $emailad .= $glnewad;
			  }
  }
  if (trim($emailad) != "") {
    $sendemail = 1;
  }
#  print "EMAILAD=$emailad SENDEMAIL=$sendemail";
  return array($emailad, $sendemail);
}


function cmp_place($ar1, $ar2)
{
  if ($ar1['place']< $ar2['place'])
    return -1;
  else if ($ar1['place']>$ar2['place'])
    return 1;
  return 0;
}

function cmp_place_query($ar1, $ar2)
{
  $place1 = isset($ar1['place_query']) ? $ar1['place_query'] : 0;
  $place2 = isset($ar2['place_query']) ? $ar2['place_query'] : 0;
  if ($place1 < $place2)
    return -1;
  else if ($place1 > $place2)
    return 1;
  return 0;
}

function cmp_place_result($ar1, $ar2)
{
  $place1 = isset($ar1['place_result']) ? $ar1['place_result'] : 0;
  $place2 = isset($ar2['place_result']) ? $ar2['place_result'] : 0;
  if ($place1< $place2)
    return -1;
  else if ($place1>$place2)
    return 1;
  return 0;
}

function trackers_data_get_all_report_fields($group_id=false,$report_id=100)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  /*
           Get all the bug fields involved in the bug report.
	   WARNING: This function must only be called after bug_init()
  */

  # Build the list of fields involved in this report
  $sql = "SELECT * FROM ".ARTIFACT."_report_field WHERE report_id='".safeinput($report_id)."'";
  $res = db_query($sql);

  while ($arr = db_fetch_array($res))
    {
      $field = $arr['field_name'];
      $field_id = trackers_data_get_field_id($field);
      $BF_USAGE_BY_NAME[$field]['show_on_query'] =
	 $BF_USAGE_BY_ID[$field_id]['show_on_query'] = $arr['show_on_query'];

      $BF_USAGE_BY_NAME[$field]['show_on_result'] =
	 $BF_USAGE_BY_ID[$field_id]['show_on_result'] = $arr['show_on_result'];

      $BF_USAGE_BY_NAME[$field]['place_query'] =
	 $BF_USAGE_BY_ID[$field_id]['place_query'] = $arr['place_query'];

      $BF_USAGE_BY_NAME[$field]['place_result'] =
	 $BF_USAGE_BY_ID[$field_id]['place_result'] = $arr['place_result'];

      $BF_USAGE_BY_NAME[$field]['col_width'] =
	 $BF_USAGE_BY_ID[$field_id]['col_width'] = $arr['col_width'];
    }
}

function trackers_data_get_field_predefined_values ($field, $group_id=false, $checked=false,$by_field_id=false,$active_only=true)
{

  /*
             Return all possible values for a select box field
             Rk: if the checked value is given then it means that we want this value
                  in the list in any case (even if it is hidden and active_only is requested)
  */
  $field_id = ($by_field_id ? $field : trackers_data_get_field_id($field));
  $field_name = ($by_field_id ? trackers_data_get_field_name($field) : $field);

  # The "Assigned_to" box requires some special processing
  # because possible values  are project members) and they are
  # not stored in the trackers_field_value table but in the user_group table
  if ($field_name == 'assigned_to')
    {
      $res_value = trackers_data_get_technicians($group_id);
    }
  else if ($field_name == 'submitted_by')
    {
      $res_value = trackers_data_get_submitters($group_id);
    }
  else
    {
      $status_cond = '';

      # If only active field
      if ($active_only)
	{
	  if ($checked)
	    {
	      $status_cond = "AND  (status IN ('A','P') OR value_id='$checked') ";
	    }
	  else
	    {
	      $status_cond = "AND  status IN ('A','P') ";
	    }
	}

      # CAUTION !! the fields value_id and value must be first in the
      # select statement because the output is used in the html_build_select_box
      # function

      # yeupou@gnu.org 2003-11-24
      # FIXME!!!!! WHAT IS THIS CRAP!
      # It _on purpose_ ignores the permanent values for the
      # system when a group have his own values.
      # And  when creating group specific values, it insert the permanent
      # system values in the group specific values.
      #
      # Can someone bring a reasonnable explanation for such a behavior?
      #   - permanent field must by nature be permanent, in any case!
      #   - database must never duplicates information without good reason
      #
      # When improving this code, please change that so it uses the permanent
      # values in any case, whatever the group specific values may be.

      # Look for project specific values first
      $sql="SELECT value_id,value,bug_fv_id,bug_field_id,group_id,description,order_id,status ".
	 "FROM ".ARTIFACT."_field_value ".
	 "WHERE group_id=$group_id AND bug_field_id=$field_id ".
	 $status_cond." ORDER BY order_id,value ASC";
      $res_value = db_query($sql);
      $rows=db_numrows($res_value);

      # If no specific value for this group then look for default values
      if ($rows == 0)
	{
	  $sql="SELECT value_id,value,bug_fv_id,bug_field_id,group_id,description,order_id,status ".
	     "FROM ".ARTIFACT."_field_value ".
	     "WHERE group_id=100 AND bug_field_id=$field_id ".
	     $status_cond." ORDER BY order_id,value ASC";
	  $res_value = db_query($sql);
	  $rows=db_numrows($res_value);
	}
    }

  return($res_value);

}

function trackers_data_use_field_predefined_values ($field, $group_id)
{
  # Check whether a group field values are the default one or not.
  # If no entry in the database for the relevant field value belong to the
  # group, then it uses default values (fallback)
  $field_id = trackers_data_get_field_id($field);
  $sql="SELECT bug_fv_id FROM ".ARTIFACT."_field_value ".
             "WHERE group_id=".$group_id." AND bug_field_id=$field_id";

  $result = db_query($sql);
  return db_numrows($result);
}


function trackers_data_is_custom($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['custom']: $BF_USAGE_BY_NAME[$field]['custom']);
}

function trackers_data_is_special($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id
	 ? !empty($BF_USAGE_BY_ID[$field]['special'])
	 : !empty($BF_USAGE_BY_NAME[$field]['special']));
}

# deprecated
function trackers_data_is_empty_ok ($field, $by_field_id=false)
{
  return trackers_data_mandatory_flag($field, $by_field_id);
}

function trackers_data_mandatory_flag ($field, $by_field_id=false)
{
  # 1 = not mandatory
  # 0 = relaxed mandatory (mandatory if it was to the submitter)
  # 3 = mandatory whenever possible
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  if ($by_field_id)
    {
      $val = $BF_USAGE_BY_ID[$field]['custom_empty_ok'];
      if (!isset($val))
	{ $val = $BF_USAGE_BY_ID[$field]['empty_ok']; }
    }
  else
    {
      if (isset($BF_USAGE_BY_NAME[$field]['custom_empty_ok']))
	$val = $BF_USAGE_BY_NAME[$field]['custom_empty_ok'];
      else if (isset($BF_USAGE_BY_NAME[$field]['empty_ok']))
	$val = $BF_USAGE_BY_NAME[$field]['empty_ok'];
      else
	$val = null;
    }
  return($val);
}


function trackers_data_do_keep_history($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  if ($by_field_id)
    {
      $val = $BF_USAGE_BY_ID[$field]['custom_keep_history'];
      if (!isset($val))
	{ $val = $BF_USAGE_BY_ID[$field]['empty_keep_history']; }
    }  else
      {
	$val = $BF_USAGE_BY_NAME[$field]['custom_keep_history'];
	if (!isset($val))
	  { $val = $BF_USAGE_BY_NAME[$field]['keep_history']; }
      }
  return($val);
}

function trackers_data_is_required($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['required']: $BF_USAGE_BY_NAME[$field]['required']);
}

function trackers_data_is_used($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['use_it']: $BF_USAGE_BY_NAME[$field]['use_it']);
}

function trackers_data_is_showed_on_query($field)
{
  global $BF_USAGE_BY_NAME;
  # show_on_query can be unset if not in the DB
  return !empty($BF_USAGE_BY_NAME[$field]['show_on_query']);
}

function trackers_data_is_showed_on_result($field)
{
  global $BF_USAGE_BY_NAME;
  return !empty($BF_USAGE_BY_NAME[$field]['show_on_result']);
}

# return a TRUE value if non project members who still are
# logged in users should be able to access this field
# (first bit of show_on_add set)
function trackers_data_is_showed_on_add($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['show_on_add'] & 1: $BF_USAGE_BY_NAME[$field]['show_on_add'] & 1);
}

# return a TRUE value if non logged in users should be able to
# access this field (second bit of show_on_add set)
function trackers_data_is_showed_on_add_nologin($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  $by_id = isset($BF_USAGE_BY_ID[$field]['show_on_add'])
    ? $BF_USAGE_BY_ID[$field]['show_on_add']
    : null;
  $by_id = $by_id & 2;
  $by_val = isset($BF_USAGE_BY_NAME[$field]['show_on_add'])
    ? $BF_USAGE_BY_NAME[$field]['show_on_add']
    : null;
  $by_val = $by_val & 2;
  return $by_field_id ? $by_id : $by_val;
}

# return a TRUE value if project members should be able to
# access this field
function trackers_data_is_showed_on_add_members($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  $by_id = isset($BF_USAGE_BY_ID[$field]['show_on_add_members'])
    ? $BF_USAGE_BY_ID[$field]['show_on_add_members']
    : null;
  $by_val = isset($BF_USAGE_BY_NAME[$field]['show_on_add_members'])
    ? $BF_USAGE_BY_NAME[$field]['show_on_add_members']
    : null;
  return $by_field_id ? $by_id : $by_val;
}

function trackers_data_is_date_field($field, $by_field_id=false)
{
  return(trackers_data_get_display_type($field, $by_field_id) == 'DF');
}

function trackers_data_is_text_field($field, $by_field_id=false)
{
  return(trackers_data_get_display_type($field, $by_field_id) == 'TF');
}

function trackers_data_is_text_area($field, $by_field_id=false)
{
  return(trackers_data_get_display_type($field, $by_field_id) == 'TA');
}

function trackers_data_is_select_box($field, $by_field_id=false)
{
  return(trackers_data_get_display_type($field, $by_field_id) == 'SB');
}

function trackers_data_is_username_field($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID;
  if ($by_field_id)
    {
      $field = trackers_data_get_field_name($field);
    }
  return(($field == 'assigned_to') || ($field == 'submitted_by'));
}

function trackers_data_is_project_scope($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  if ($by_field_id)
    {
      return($BF_USAGE_BY_ID[$field]['scope'] == 'P');
    }
  else
    {
      return($BF_USAGE_BY_NAME[$field]['scope'] == 'P');
    }
}

function trackers_data_is_status_closed($status)
{
  if ($status == '3')
    { return 1; }
  return 0;
}

function trackers_data_get_field_name($field_id)
{
  global $BF_USAGE_BY_ID;
  return($BF_USAGE_BY_ID[$field_id]['field_name']);
}

function trackers_data_get_field_id($field_name)
{
  global $BF_USAGE_BY_NAME;
  return($BF_USAGE_BY_NAME[$field_name]['bug_field_id']);
}

function trackers_data_get_group_id($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['group_id'] : $BF_USAGE_BY_NAME[$field]['group_id']);
}

function trackers_data_get_label($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  if ($by_field_id)
    {
      $lbl = $BF_USAGE_BY_ID[$field]['custom_label'];
      if (!isset($lbl))
	{ $lbl = $BF_USAGE_BY_ID[$field]['label']; }
    }  else
      {
	$lbl = $BF_USAGE_BY_NAME[$field]['custom_label'];
	if (!isset($lbl))
	  { $lbl = $BF_USAGE_BY_NAME[$field]['label']; }
      }
  return($lbl);
}

function trackers_data_get_description($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  if ($by_field_id)
    {
      $desc = $BF_USAGE_BY_ID[$field]['custom_description'];
      if (!isset($desc))
	{ $desc = $BF_USAGE_BY_ID[$field]['description']; }
    }  else
      {
	$desc = $BF_USAGE_BY_NAME[$field]['custom_description'];
	if (!isset($desc))
	  { $desc = $BF_USAGE_BY_NAME[$field]['description']; }
      }
  return($desc);
}

function trackers_data_get_display_type($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID, $BF_USAGE_BY_NAME;
  $by_id = isset($BF_USAGE_BY_ID[$field]['display_type'])
    ? $BF_USAGE_BY_ID[$field]['display_type']
    : null;
  $by_val = isset($BF_USAGE_BY_NAME[$field]['display_type'])
    ? $BF_USAGE_BY_NAME[$field]['display_type']
    : null;
  return $by_field_id ? $by_id : $by_val;
}

function trackers_data_get_display_type_in_clear($field, $by_field_id=false)
{
  if (trackers_data_is_select_box($field, $by_field_id))
    {
      return 'Select Box';
    }
  else if (trackers_data_is_text_field($field, $by_field_id))
    {
      return 'Text Field';
    }
  else if (trackers_data_is_text_area($field, $by_field_id))
    {
      return 'Text Area';
    }
  else if (trackers_data_is_date_field($field, $by_field_id))
    {
      return 'Date Field';
    }
  else
    {
      return '?';
    }
}


function trackers_data_get_keep_history($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  if ($by_field_id)
    {
      $val = $BF_USAGE_BY_ID[$field]['custom_keep_history'];
      if (!isset($val))
	{ $val = $BF_USAGE_BY_ID[$field]['keep_history']; }
    }
  else
    {
      $val = $BF_USAGE_BY_NAME[$field]['custom_keep_history'];
      if (!isset($val))
	{ $val = $BF_USAGE_BY_NAME[$field]['keep_history']; }
    }
  return($val);
}

function trackers_data_get_place($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['place'] : $BF_USAGE_BY_NAME[$field]['place']);
}

function trackers_data_get_scope($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['scope'] : $BF_USAGE_BY_NAME[$field]['scope']);
}

function trackers_data_get_col_width($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  return($by_field_id ? $BF_USAGE_BY_ID[$field]['col_width'] : $BF_USAGE_BY_NAME[$field]['col_width']);
}

function trackers_data_get_display_size($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  if ($by_field_id)
    {
      $val = $BF_USAGE_BY_ID[$field]['custom_display_size'];
      if (!isset($val))
	{ $val = $BF_USAGE_BY_ID[$field]['display_size']; }
    }
  else
    {
      if (isset($BF_USAGE_BY_NAME[$field]['custom_display_size']))
	$val = $BF_USAGE_BY_NAME[$field]['custom_display_size'];
      else if (isset($BF_USAGE_BY_NAME[$field]['display_size']))
	$val = $BF_USAGE_BY_NAME[$field]['display_size'];
      else
	$val = null;
    }
  return(explode('/',$val));
}

function trackers_data_get_default_value($field, $by_field_id=false)
{
  global $BF_USAGE_BY_ID,$BF_USAGE_BY_NAME;
  /*
      Return the default value associated to a field_name as defined in the
      bug table (SQL definition)
  */
  if ($by_field_id)
    {
      $field = trackers_data_get_field_name($field);
    }

  $result = db_query('DESCRIBE '.ARTIFACT.' '.$field);
  // eg: DESCRIBE bugs originator_name
  return (db_result($result,0,'Default'));

}

function trackers_data_get_max_value_id($field, $group_id, $by_field_id=false)
{

  /*
      Find the maximum value for the value_id of a field for a given group
      Return -1 if  no value exist yet
  */

  if (!$by_field_id)
    {
      $field_id = trackers_data_get_field_id($field);
    }

  $sql="SELECT max(value_id) as max FROM ".ARTIFACT."_field_value ".
     "WHERE bug_field_id='$field_id' AND group_id='$group_id' ";
  $res = db_query($sql);
  $rows = db_numrows($res);

  # If no max value found then it means it's the first value for this field
  # in this group. Return -1 in this case
  if ($rows == 0)
    {
      return(-1);
    }  else
      {
	return(db_result($res,0,'max'));
      }

}

function trackers_data_is_value_set_empty($field, $group_id, $by_field_id=false)
{

  /*
      Return true if there is an existing set of values for given field for a
      given group and false if it is empty
  */

  if (!$by_field_id)
    {
      $field_id = trackers_data_get_field_id($field);
    }
  $sql="SELECT value_id FROM ".ARTIFACT."_field_value ".
     "WHERE bug_field_id='$field_id' AND group_id='$group_id' ";
  $res = db_query($sql);
  $rows=db_numrows($res);

  return (($rows<=0));
}


function trackers_data_copy_default_values($field, $group_id, $by_field_id=false)
{
  /*
      Initialize the set of values for a given field for a given group by using
      the system default (default values belong to group_id 'None' =100)
  */

  if (!$by_field_id)
    {
      $field_id = trackers_data_get_field_id($field);
    }

  # if group_id=100 (None) it is a null operation
  # because default values belong to group_id 100 by definition
  if ($group_id != 100)
    {

      # First delete the exisiting value if any
      $sql="DELETE FROM ".ARTIFACT."_field_value ".
	 "WHERE bug_field_id='$field_id' AND group_id='$group_id' ";
      $res = db_query($sql);

      # Second insert default values (if any) from group 'None'
      # Rk: The target table of the INSERT statement cannot appear in
      # the FROM clause of the SELECT part of the query because it's forbidden
      # in ANSI SQL to SELECT . So do it by hand !
      #

      $sql = "SELECT value_id,value,description,order_id,status ".
	 "FROM ".ARTIFACT."_field_value ".
	 "WHERE bug_field_id='$field_id' AND group_id='100'";
      $res = db_query($sql);
      $rows = db_numrows($res);

      for ($i=0; $i<$rows; $i++)
	{

	  $value_id = addslashes(db_result($res,$i,'value_id'));
	  $value = db_result($res,$i,'value');
	  $description = addslashes(db_result($res,$i,'description'));
	  $order_id = db_result($res,$i,'order_id');
	  $status  = db_result($res,$i,'status');


	  $sql="INSERT INTO ".ARTIFACT."_field_value ".
	     "(bug_field_id,group_id,value_id,value,description,order_id,status) ".
	     "VALUES ('$field_id','$group_id','$value_id','$value','$description','$order_id','$status')";
	  #print "<BR>DBG - $sql";
	  $res_insert = db_query($sql);

	  if (db_affected_rows($res_insert) < 1)
	    {
	      fb(_("Insert of default value failed."), 0);
	      db_error();
	    }
	}
    }
}

function trackers_data_get_cached_field_value($field,$group_id,$value_id)
{
  global $BF_VALUE_BY_NAME;

  if (!isset($BF_VALUE_BY_NAME[$field][$value_id]))
    {
      $res = trackers_data_get_field_predefined_values ($field, $group_id,false,false,false);

      while ($fv_array = db_fetch_array($res))
	{
	  # $fv_array[0] has the value_id and [1] is the value
	  $BF_VALUE_BY_NAME[$field][$fv_array['value_id']] = $fv_array[1];
	}
    }

  return $BF_VALUE_BY_NAME[$field][$value_id];
}

function trackers_data_get_field_value ($item_fv_id)
{
  /*
      Get all the columns associated to a given field value
  */

  $sql = "SELECT * FROM ".ARTIFACT."_field_value WHERE bug_fv_id='$item_fv_id'";
  $res = db_query($sql);
  return($res);
}

function trackers_data_is_default_value ($item_fv_id)
{
  /*
      See if this field value belongs to group None (100). In this case
      it is a so called default value.
  */

  $sql = "SELECT bug_field_id,value_id FROM ".ARTIFACT."_field_value WHERE bug_fv_id='$item_fv_id' AND group_id='100'";
  $res = db_query($sql);

  return ( (db_numrows($res) >= 1) ? $res : false);
}

function trackers_data_create_value ($field, $group_id, $value, $description,$order_id,$status='A',$by_field_id=false)
{

  global $feedback,$ffeedback;

  /*
      Insert a new value for a given field for a given group
  */

  # An empty field value is not allowed
  if (preg_match ("/^\s*$/", $value))
    {
      fb(_("Empty field value not allowed"), 0);
      return;
    }

  if (!$by_field_id)
    {
      $field_id = trackers_data_get_field_id($field);
    }

  # if group_id=100 (None) then do nothing
  # because no real project should have the group number '100'
  if ($group_id != 100)
    {

      # if the current value set for this project is empty
      # then copy the default values first (if any)
      if (trackers_data_is_value_set_empty($field,$group_id))
	{
	  trackers_data_copy_default_values($field,$group_id);
	}

      # Find the next value_id to give to this new value. (Start arbitrarily
      # at 200 if no value exists (and therefore max is undefined)
      $max_value_id = trackers_data_get_max_value_id($field, $group_id);

      if ($max_value_id < 0)
	{
	  $value_id = 200;
	}
      else
	{
	  $value_id = $max_value_id +1;
	}


      $sql = "INSERT INTO ".ARTIFACT."_field_value ".
	 "(bug_field_id,group_id,value_id,value,description,order_id,status) ".
	 "VALUES ('$field_id','$group_id','$value_id','$value','$description','$order_id','$status')";
      db_query($sql);

      if (db_affected_rows($result) < 1)
	{
	  fb(_("Insert failed."), 0);
	  ' - '.db_error();
        }
      else

	{
	  fb(_("New field value inserted."));
	}
    }
}


function trackers_data_update_value ($item_fv_id,$field,$group_id,$value,$description,$order_id,$status='A')
{

  global $feedback,$ffeedback;
  /*
      Insert a new value for a given field for a given group
  */

  # An empty field value is not allowed
  if (preg_match ("/^\s*$/", $value))
    {
      fb(_("Empty field value not allowed"), 0);
      return;
    }

  # Updating a bug field value that belong to group 100 (None) is
  # forbidden. These are default values that cannot be changed so
  # make sure to copy the default values first in the project context first

  if ($res = trackers_data_is_default_value($item_fv_id))
    {
      trackers_data_copy_default_values($field,$group_id);

      $arr = db_fetch_array($res);
      $where_cond = 'bug_field_id='.$arr['bug_field_id'].
	 ' AND value_id='.$arr['value_id']." AND group_id='$group_id' ";
    }
  else
    {
      $where_cond = "bug_fv_id='$item_fv_id' AND group_id<>'100'";
    }

  # Now perform the value update
  $sql = "UPDATE ".ARTIFACT."_field_value ".
     "SET value='$value',description='$description',order_id='$order_id',status='$status' ".
     "WHERE $where_cond";
  $result = db_query($sql);

  #print "<BR>DBG - $sql";

  if (db_affected_rows($result) < 1)
    {
      fb(_("Update of field value failed."));
    }
  else
    {
      fb(_("New field value updated."));
    }
}

function trackers_data_reset_usage($field_name,$group_id)
{
  global $feedback;
  /*
      Reset a field settings to its defaults usage (values are untouched). The defaults
      always belong to group_id 100 (None) so make sure we don;t delete entries for
      group 100
  */
  $field_id = trackers_data_get_field_id($field_name);
  if ($group_id != 100)
    {
      $sql = "DELETE FROM ".ARTIFACT."_field_usage ".
	 "WHERE group_id='$group_id' AND bug_field_id='$field_id'";
      db_query($sql);
      fb(_("Field value successfully reset to defaults."));

    }
}

function trackers_data_update_usage($field_name,
				    $group_id,
				    $label,
				    $description,
				    $use_it,
				    $rank,
				    $display_size,
				    $empty_ok,
				    $keep_history,
				    $show_on_add_members=0,
				    $show_on_add=0,
				    $transition_default_auth='A')

{

  /*
      Update a field settings in the trackers_usage_table
      Rk: All the show_on_xxx boolean parameters are set to 0 by default because their
           values come from checkboxes and if not checked the form variable
           is not set at all. It must be 0 to be ok with the SQL statement
  */

  # if it's a required field then make sure the use_it flag is true
  if (trackers_data_is_required($field_name))
    {
      $use_it = 1;
    }

  $field_id = trackers_data_get_field_id($field_name);

  # if it's a custom field then take label into account else store NULL
  #    if (trackers_data_is_custom($field_name))  {
  $lbl = isset($label) ? "'$label'" : 'NULL';
  $desc = isset($description) ? "'$description'" : 'NULL';
  $disp_size = isset($display_size) ? "'$display_size'" : 'NULL';
  $empty = isset($empty_ok) ? "'$empty_ok'" : 'NULL';
  $keep_hist = isset($keep_history) ? "'$keep_history'" : 'NULL';
  #    }  else    {
  #	$lbl = $desc = $disp_size = $empty = $keep_hist = "NULL";
  #    }

  # See if this field usage exists in the table for this project
  $sql = 'SELECT bug_field_id FROM '.ARTIFACT.'_field_usage '.
     "WHERE bug_field_id='$field_id' AND group_id='$group_id'";
  $result = db_query($sql);
  $rows = db_numrows($result);

  # if it does exist then update it else insert a new usage entry for this field.
  if ($rows)
    {
      $sql = 'UPDATE '.ARTIFACT.'_field_usage '.
	 "SET use_it='$use_it',show_on_add='$show_on_add',".
	 "show_on_add_members='$show_on_add_members',place='$rank', ".
	 "custom_label=$lbl,  custom_description=$desc,".
	 "custom_display_size=$disp_size,  custom_empty_ok=$empty,".
	 "custom_keep_history=$keep_hist, ".
	 "transition_default_auth='$transition_default_auth' ".
	 "WHERE bug_field_id='$field_id' AND group_id='$group_id'";
      $result = db_query($sql);
    }
  else
    {
      $sql = 'INSERT INTO '.ARTIFACT.'_field_usage '.
	 "VALUES ('$field_id','$group_id','$use_it','$show_on_add',".
	 "'$show_on_add_members','$rank',$lbl,$desc,$disp_size,$empty,$keep_hist,'$transition_default_auth')";
      $result = db_query($sql);
    }

  if (db_affected_rows($result) < 1)
    { fb(_("Update of field usage failed."), 1); }
  else
    { fb(_("Field usage updated.")); }

}

# Get a list of technicians for a tracker
function trackers_data_get_technicians ($group_id)
{
  # FIXME: The cleanest thing would be to issue one SQL command.
  # But we have to handle the fact that "no setting" = get back
  # to the group, or even group type, setting.

  # In fact, this is terrible, we cannot return something else than
  # a mysql result if we do not want to rewrite 25 functions.
  # So we get the appropriate list of users... and finally issue a
  # mysql command only to be able to return a mysql result.
  # Please, propose something better at savannah-dev@gnu.org


  # Get list of members
  $members_res = db_execute("SELECT user.user_id FROM user,user_group
    WHERE user.user_id=user_group.user_id AND user_group.group_id=?",
			    array($group_id));
  # Build the sql command
  $sql = "SELECT user_id,user_name FROM user WHERE ";
  $notfirst = FALSE;
  while ($member = db_fetch_array($members_res))
    {
      if (member_check($member['user_id'], $group_id, member_create_tracker_flag(ARTIFACT).'1'))
	{
	  if ($notfirst)
	    { $sql .= " OR "; }
	  $sql .= " user_id='".$member['user_id']."'";
	  $notfirst = true;
	}
    }
  $sql .= " ORDER BY user_name";
  return db_query($sql);
}

# Get transitions valid for a given tracker as an array
# DEPRECATED, moved to transition.php
function trackers_data_get_transition ($group_id)
{
  return trackers_transition_get_update($group_id);
}

function trackers_data_get_submitters ($group_id=false)
{
  $sql="SELECT DISTINCT user.user_id,user.user_name ".
     "FROM user,".ARTIFACT." ".
     "WHERE user.user_id=".ARTIFACT.".submitted_by ".
     "AND ".ARTIFACT.".group_id='$group_id' ".
     "ORDER BY user.user_name";
  return db_query($sql);
}

function trackers_data_get_items ($group_id=false, $artifact)
{
  /*
		Get the items for this project
  */
  $sql="SELECT bug_id,summary ".
     "FROM ".$artifact." ".
     "WHERE group_id='".$group_id."'".
     " AND status_id <> '3' ".
     " ORDER BY bug_id DESC LIMIT 100";
  return db_query($sql);
}

function trackers_data_get_dependent_items ($item_id=false, $artifact, $notin=false)
{
  /*
		Get the list of ids this is dependent on
  */
  $sql="SELECT is_dependent_on_item_id FROM ".ARTIFACT."_dependencies WHERE item_id='$item_id' AND is_dependent_on_item_id_artifact='".$artifact."' ";
  if ($notin)
    {
      $sql .= ' AND is_dependent_on_item_id NOT IN ('. join(',',$notin).')';
    }
  #  print $sql; #DBG
  return db_query($sql);
}

function trackers_data_get_valid_bugs ($group_id=false,$item_id='')
{
  $sql="SELECT bug_id,summary ".
     "FROM ".ARTIFACT." ".
     "WHERE group_id='$group_id' ".
     "AND bug_id <> '$item_id' AND ".ARTIFACT.".resolution_id <> '2' ORDER BY bug_id DESC LIMIT 200";
  return db_query($sql);
}


function trackers_data_get_followups ($item_id=false, $rorder=false)
{
  if ($rorder == true)
    { $rorder = "DESC"; }
  else
    { $rorder = "ASC"; }

  $sql="SELECT DISTINCT ".ARTIFACT."_history.bug_history_id,".ARTIFACT."_history.field_name,".ARTIFACT."_history.old_value,".ARTIFACT."_history.spamscore,".ARTIFACT."_history.new_value,".ARTIFACT."_history.date,user.user_name,user.realname,user.user_id,".ARTIFACT."_field_value.value AS comment_type ".
     "FROM ".ARTIFACT."_history,".ARTIFACT."_field_value,".ARTIFACT."_field,".ARTIFACT.",user ".
     "WHERE ".ARTIFACT."_history.bug_id='$item_id' ".
     "AND (".ARTIFACT."_history.field_name = 'details' OR ".ARTIFACT."_history.field_name = 'svncommit') ".
     "AND ".ARTIFACT."_history.mod_by=user.user_id ".
     "AND ".ARTIFACT."_history.bug_id=".ARTIFACT.".bug_id ".
     "AND ".ARTIFACT."_history.type = ".ARTIFACT."_field_value.value_id ".
     "AND ".ARTIFACT."_field_value.bug_field_id = ".ARTIFACT."_field.bug_field_id ".
     "AND (".ARTIFACT."_field_value.group_id = ".ARTIFACT.".group_id OR ".ARTIFACT."_field_value.group_id = '100') ".
     "AND  ".ARTIFACT."_field.field_name = 'comment_type_id' ".
     "ORDER BY ".ARTIFACT."_history.date $rorder";

  return db_query($sql);
}

function trackers_data_get_commenters($item_id)
{
  $sql="SELECT DISTINCT mod_by FROM ".ARTIFACT."_history ".
     "WHERE ".ARTIFACT."_history.bug_id='$item_id' ".
     "AND ".ARTIFACT."_history.field_name = 'details' ";
  return db_query($sql);
}

function trackers_data_get_history ($item_id=false)
{
  $sql="select ".ARTIFACT."_history.field_name,".ARTIFACT."_history.old_value,".ARTIFACT."_history.date,".ARTIFACT."_history.type,user.user_name,".ARTIFACT."_history.new_value ".
     "FROM ".ARTIFACT."_history,user ".
     "WHERE ".ARTIFACT."_history.mod_by=user.user_id ".
     "AND ".ARTIFACT."_history.field_name <> 'details' ".
     "AND ".ARTIFACT."_history.field_name <> 'svncommit' ".
     "AND bug_id='$item_id' ORDER BY ".ARTIFACT."_history.date DESC";
  return db_query($sql);
}

function trackers_data_get_attached_files ($item_id=false, $order='DESC')
{
  $sql="SELECT file_id,filename,filesize,filetype,description,date,user.user_name ".
     "FROM trackers_file,user ".
     "WHERE submitted_by=user.user_id ".
     "AND artifact='".ARTIFACT."' ".
     "AND item_id='$item_id' ORDER BY date $order";
  return db_query($sql);
}

function trackers_data_get_cc_list ($item_id=false)
{
  $sql="SELECT bug_cc_id,".ARTIFACT."_cc.email,".ARTIFACT."_cc.added_by,".ARTIFACT."_cc.comment,".ARTIFACT."_cc.date,user.user_name ".
     "FROM ".ARTIFACT."_cc,user ".
     "WHERE added_by=user.user_id ".
     "AND bug_id='$item_id' ORDER BY date DESC";
  return db_query($sql);
}

function trackers_data_add_history ($field_name,
				    $old_value,
				    $new_value,
				    $item_id,
				    $type=false,
				    $artifact=0,
				    $force=0)

{
  # If no artifact is defined, get the default one
  if (!$artifact)
    {
      $artifact = ARTIFACT;
    }

  # If field is not to be kept in bug change history then do nothing
  if (!$force && !trackers_data_get_keep_history($field_name))
    { return; }

  if (!user_isloggedin())
    {
      $user=100;
    }
  else
    {
      $user=user_getid();
    }

  # If spamscore is relevant (if it is a comment), set it, otherwise go with
  # 0
  $spamscore = 0;
  if ($field_name == 'details')
    {
      $spamscore = spam_get_user_score($user);
    }

  # If type has a value add it into the sql statement (this is only for
  # the follow up comments (details field))
  $val_type = 'NULL';
  if ($type)
    {
      $val_type = $type;
    }
  else
    {
	# No comment type specified for a followup comment
	# so force it to None (100)
      if ($field_name == 'details')
	{
	  $val_type = 100;
	}
    }


  $result = db_query_escape("
    INSERT INTO ".$artifact."_history
      (bug_id, field_name, old_value, new_value,
       mod_by, date, spamscore, ip, type)
    VALUES
      ('%s','%s','%s','%s',
       '%s','%s','%s','%s', %s)",
     $item_id, $field_name, $old_value, $new_value,
     $user, time(), $spamscore, $_SERVER['REMOTE_ADDR'], $val_type);
  
  spam_set_item_default_score($item_id, 
			      db_insertid($result),
			      $artifact,
			      $spamscore, 
			      $user);

  # Add to spamcheck queue if necessary (will temporary set the spamscore to
  # 5, if necessary)
  # Useless if already considered to be spam.
  if ($spamscore < 5)
    {  
      $result = db_query("SELECT group_id FROM $artifact WHERE bug_id='$item_id'");
      if (db_numrows($result))
	$group_id = db_result($result,0,'group_id');
      else
	exit_error(_("Item not found"));
      spam_add_to_spamcheck_queue($item_id, db_insertid($result), $artifact, $group_id, $spamscore); 
    }

  return $result;
}


#  Handles update of most usual fields.
function trackers_data_handle_update ($group_id,
				      $item_id,
				      $dependent_on_task,
				      $dependent_on_bugs,
				      $dependent_on_support,
				      $dependent_on_patch,
				      $canned_response,
				      $vfl,
				      &$changes,
				      &$extra_addresses)
{
  # variable to track changes made inside the function
  $change_exists = false;

  # Update an item. Rk: vfl is an variable list of fields, Vary from one
  # project to another
  # return true if bug updated, false if nothing changed or
  # DB update failed

  # Make sure absolutely required fields are not empty
  # yeupou, 2005-11: why is canned_response absolutely required?
  if (!$group_id || !$item_id || !$canned_response)
    {
      dbg("params were group_id:$group_id item_id:$item_id canned_response:$canned_response");
      exit_missing_param();
    }

  # Make sure mandatory fields are not empty, otherwise we want the form
  # to be re-submitted
  if ((trackers_check_empty_fields($vfl, false) == false))
    {
      # In such circonstances, we reprint the form
      # highligthing missing fields.
      # (It is important that trackers_check_empty_fields set the global var
      # previous_form_bad_fields)
      return false;
    }

  # Get this bug from the db

  $result=db_execute("SELECT * FROM ".ARTIFACT." WHERE bug_id=?", array($item_id));

  if (!((db_numrows($result) > 0) && (member_check(0,db_result($result,0,'group_id'), member_create_tracker_flag(ARTIFACT).'2'))))
    {
      # Verify permissions
      dbg("no management/techn. rights");
      exit_permission_denied();
    }

  # Extract field transitions possibilities:
  $field_transition = array();
  $field_transition = trackers_data_get_transition($group_id);
  # We will store in an array the transition_id accepted, to check
  # other fields updates
  $field_transition_accepted = array();

  # See which fields changed during the modification
  # and if we must keep history then do it. Also add them to the update
  # statement
  # ($changes was initialized in index, as it is used by other functions)
  reset($vfl);
  $upd_list = array();
  while (list($field,$value) = each($vfl))
    {
      # $field_transition_id needed to be reset for every field in the loop
      # and $field_transition_accepted filled only if $field_transition_id
      # is not empty (otherwise transition automatic updates risk to be
      # done by error if a transition is defined for any field!)
      unset($field_transition_id);

      # skip over special fields  except for summary which in this
      # particular case can be processed normally
      if (trackers_data_is_special($field) && ($field != 'summary'))
	{ continue; }

      # skip over comment, which is also a special field but not known as
      # special by the database
      if ($field == 'comment')
	{ continue; }

      $old_value = db_result($result,0,$field);

      # Handle field transitions checks+cc notif,
      # register id of transition to execute
      $field_id = trackers_data_get_field_id($field);
      $field_transition_cc = '';
      if (array_key_exists($field_id, $field_transition))
	{
          # First check basic transition
          # And check multiple transition, override other transition
	  if (array_key_exists($old_value, $field_transition[$field_id])||
	      array_key_exists("any", $field_transition[$field_id]))
	    {
	      if (array_key_exists("any", $field_transition[$field_id]) && array_key_exists($value, $field_transition[$field_id]["any"]))
		{
		  $field_transition_cc = $field_transition[$field_id]["any"][$value]['notification_list'];

		  # Register the transition, but only if the field it is about
                  # was not filled in the form
		  if (!is_array($changes[$field]) ||
		      (!array_key_exists('del', $changes[$field]) &&
		       !array_key_exists('add', $changes[$field])))
		    {

		      $field_transition_id = $field_transition[$field_id]["any"][$value]['transition_id'];
		    }
		}
	      else if (array_key_exists($old_value, $field_transition[$field_id]) && array_key_exists($value, $field_transition[$field_id][$old_value]))
		{

		  $field_transition_cc = $field_transition[$field_id][$old_value][$value]['notification_list'];

                  # Register the transition, but only if the field it is about
                  # was not filled in the form
		  if (!is_array($changes[$field]) ||
		      (!array_key_exists('del', $changes[$field]) &&
		       !array_key_exists('add', $changes[$field])))
		    {
		      $field_transition_id = $field_transition[$field_id][$old_value][$value]['transition_id'];
		    }

		}

	    }
	}

      $is_text = (trackers_data_is_text_field($field) || trackers_data_is_text_area($field));
      if  ($is_text)
	{
	  $differ = ($old_value != stripslashes(htmlspecialchars($value)));
	}
      else if (trackers_data_is_date_field($field))
	{
	  # if it is a date we must convert the format to unix time
          $date_value = $value;
	  list($value,$ok) = utils_date_to_unixtime($value);

          # Users can be on different timezone ; The form
          # save only the day, month, year.
          # We cannot compare the timestamp (affected by timezone changes)
          $date_old_value = date("Y-n-j", $old_value);

	  $differ = ($date_old_value != $date_value);
	}
      else
	{
	  $differ = ($old_value != $value);
	}

      if ($differ)
	{

          if ((trim($extra_addresses) != "") && (trim($field_transition_cc) != ""))
            {
	      $extra_addresses .= ", ";
            }
	  $extra_addresses .= $field_transition_cc;


	  if ($is_text)
	    {
	      $upd_list[$field] = htmlspecialchars($value);
	      trackers_data_add_history($field,
					addslashes($old_value),
					$value,
					$item_id);
	      $value = stripslashes($value);
	    }
	  else
	    {
	      $upd_list[$field] = $value;
	      trackers_data_add_history($field,$old_value,$value,$item_id);
	    }

          # Keep track of the change
	  $changes[$field]['del']=
	    trackers_field_display($field,$group_id,$old_value,false,false,true,true);
	  $changes[$field]['add']=
	     trackers_field_display($field,$group_id,$value,false,false,true,true);
	  
	  # Keep track of the change real numeric values
	  $changes[$field]['del-val']= $old_value;
	  $changes[$field]['add-val']= $value;


          # Register transition id, if not empty
          if ($field_transition_id != '')
            {
	      $field_transition_accepted[] = $field_transition_id;
            }
	}
    }

  # Now we run transitions other fields update. This function does check
  # what already changed and that we wont automatically update
  trackers_transition_update_item($item_id, $field_transition_accepted, $changes);

  # Comments field history is handled a little differently. Followup comments
  # are added in the bug history along with the comment type.
  # Comments are called 'details' here for historical reason.
  $details = $vfl['comment'];
  if ($canned_response != 100)
    {
      if ($details)
        {
          # Add a separator between user sbumitted comment and canned
          # response
          $details .= "\n\n";
        }

      # Make sure we have an array even for a unique answer
      if (!is_array($canned_response))
        {
          $tempvalue = $canned_response;
          $canned_response = array();
	  $canned_response[] = $tempvalue;
          unset($tempvalue);
        }

      # Browse the canned responses
      while (list(,$thiscanned) = each($canned_response))
        {
	  $res3 = db_execute("SELECT * FROM ".ARTIFACT."_canned_responses WHERE bug_canned_id=?".
			     array(addslashes($thiscanned)));
	  
	  if ($res3 && db_numrows($res3) > 0)
	    {
              # add a data separator
	      if ($details)
		{ $details .= "\n\n";  }
	      $details .= addslashes(utils_unconvert_htmlspecialchars(db_result($res3,0,'body')));
	      fb(_("Canned response used"));
	    }
	  else
	    {
	      fb(_("Unable to use canned response"), 0);
	    }
        }
    }

  # Comment field history is handled a little differently. Followup comments
  # are added in the bug history along with the comment type.
  if ($details != '')
    {
      $change_exists = 1;
      fb(_("Comment added"), 0);
      trackers_data_add_history('details',
				htmlspecialchars($details),
				'',
				$item_id,
				$vfl['comment_type_id']);
      $changes['details']['add'] = stripslashes($details);
      $changes['details']['type'] =
	trackers_data_get_value('comment_type_id',$group_id, $vfl['comment_type_id']);
      
      # Add poster in CC
      if (!user_get_preference("skipcc_postcomment"))
	{
	  trackers_add_cc($item_id,
			  $group_id,
			  user_getid(),
			  "-COM-",
			  $changes); 
                               # use a flag as comment, because if we 
		               # translate the string now, people will get
 		               # the translation of the submitter when they
                               # read the item, not necessarily the one they
		               # want
	}
    }

  # If we are on the cookbook, the original submission have been details
  if (ARTIFACT == 'cookbook')
    {
      $details = htmlspecialchars($vfl['details']);
      $previous_details = db_result($result,0,'details');

      if ($details != $previous_details)
	{
        $change_exists = 1;
        $upd_list['details'] = $details;

	# We should use "details" but since details are used for comment
	# (which is really nasty), we simply cant.

	# How should be print the change?
	# The way we do it here is to show the previous recipe cut to 25 chars
	# and after the -> we say the number of characters that have been added
	$del_cut = utils_cutstring($previous_details, 25);
	$change = strlen($details)-strlen($previous_details);
	if ($change >= 0)
	  { $change = "+".$change; }
	$change .= " chars";

	trackers_data_add_history('realdetails',
				  htmlspecialchars($del_cut),
				  htmlspecialchars($change),
				  $item_id,
				  false,
				  false,
				  true);
	$changes['realdetails']['add'] = stripslashes($change);
	$changes['realdetails']['del'] = stripslashes($del_cut);

	}
    }


  # Enter the timestamp if we are changing to closed or declined
  # (if not already set)
  if (trackers_data_is_status_closed($vfl['status_id']) &&
      $vfl['status_id'] != db_result($result,0,'status_id'))
    {
      $now=time();
      $upd_list['close_date'] = $now;
      trackers_data_add_history ('close_date',
				 db_result($result,0,'close_date'),
				 $now,
				 $item_id);
    }

  # Enter new dependencies
  $artifacts = array("support", "bugs", "task", "patch");
  $address = '';
  while (list(, $dependent_on) = each($artifacts))
    {
      $art = $dependent_on;
      $dependent_on = "dependent_on_".$dependent_on;
      if ($$dependent_on)
	{
	  while (list(, $dep) = each($$dependent_on))
	    {
	      trackers_data_update_dependent_items($dep, $item_id, $art);

              $changes['Depends on']['add'] = $art." #".$dep;
              $change_exists = 1;

	      # Check if we are
	      # supposed to send all modifications to an address
	      list($address, $sendall) = trackers_data_get_item_notification_info($dep, $art, 1);
	      if (($sendall == 1) && (trim($address) != ""))
                {
	          if (trim($extra_addresses) != "")
		    {
		      $extra_addresses .= ", ";
		    }
                  $extra_addresses .= $address;
		}
	    }
	}
    }

  # If we are on the cookbook, Store related links
 if (ARTIFACT == 'cookbook')
   {
     cookbook_handle_update($item_id, $group_id);
   }

  # Finally, build the full SQL query and update the bug itself (if need be)
  dbg("UPD LIST: $upd_list");
  if (count($upd_list) > 0)
    {
      $res = db_autoexecute(ARTIFACT, $upd_list, DB_AUTOQUERY_UPDATE,
		     "bug_id=? AND group_id=?",
		     array($item_id, $group_id));
      $result=db_affected_rows($res);

      # Add CC (CC in case of comment would have been already entered,
      # if there is only a comment, we should not end up here)
      if (!user_get_preference("skipcc_updateitem"))
	{
	  trackers_add_cc($item_id,
			  $group_id,
			  user_getid(),
			  "-UPD-"); 
                               # use a flag as comment, because if we 
		               # translate the string now, people will get
 		               # the translation of the submitter when they
                               # read the item, not necessarily the one they
		               # want
	}

    }
  else
    {
      if (!$change_exists)
	{
	  fb(_("No field to update"));
          # must return false, otherwise a notif would be sent
	  return false;
	}
      else
	{
	  return true;
	}
    }

  if (!$result)
    {
      exit_error(_("Item Update failed"));
      return false;
    }
  else
    {
      fb(_("Item Successfully Updated"));
      return true;
    }

}

function trackers_data_reassign_item ($item_id,
				      $reassign_change_project,
				      $reassign_change_artifact)
{

  global $group_id;

  # Can only be done by a tracker manager
  if (member_check(0,$group_id, member_create_tracker_flag(ARTIFACT).'2'))
    {
      # If the new group_id is equal to the current one, nothing need
      # to be done, unless the artifact changed.
      # If the new group_id does not exists, nothing to be done either,
      # unless the artifact changed: if no new valid group_id, let
      # consider that it does not require a change.
      $new_group_id = group_getid($reassign_change_project);
      if (!$new_group_id)
	{
	  $new_group_id = $group_id;
	}


      if ($new_group_id == $group_id && ARTIFACT == $reassign_change_artifact)
	{
	  fb(_("No reassignation required or possible."), 1);
	  return false;
	}

      $now=time();

      # To reassign an item, we close the item and we reopen a new one
      # at the appropriate place, copying information from the previous one
      # We do this because trackers may have specific fields not compatible
      # each others. Simply erase previous information could cause data loss.

      # Fetch all the information
      $sql = "SELECT * FROM ".ARTIFACT." WHERE bug_id='".$item_id."'";
      $res_data = db_query($sql);
      $row_data = db_fetch_array($res_data);

      # Duplicate the report
      if (!$reassign_change_project)
	{ $reassign_change_project = $group_id; }

      if (!$reassign_change_artifact)
	{
	  fb(_("Unable to find out to which artifact the item is to be reassigned, exiting."), 1);
	  return false;
	}

      # move item
      $result = db_query("INSERT INTO ".$reassign_change_artifact.
			 " (group_id,status_id,date,severity,submitted_by,summary,details,priority,planned_starting_date,planned_close_date,percent_complete,originator_email)".
			 " VALUES ".
			 "(".$new_group_id.",'".
			 "1','".
			 $now."','".
			 $row_data['severity']."','".
			 $row_data['submitted_by']."','".
			 addslashes($row_data['summary'])."','".
			 addslashes($row_data['details'])."','".
			 $row_data['priority']."','".
			 $row_data['planned_starting_date']."','".
			 $row_data['planned_close_date']."','".
			 $row_data['percent_complete']."','".
                         $row_data['originator_email']."')");

      if (!$result)
	{
	  fb(_("Unable to create a new item."), 1);
	  return false;
	}
      else
	{
	  fb(_("New item created."));
	}


      # Need to get the new item value
      $new_item_id =  mysql_insert_id();
      if (!$new_item_id)
	{
	  fb(_("Unable to find the ID of the new item."), 1);
	  return false;
	}

      # Update items history
      trackers_data_add_history('Reassign Item',
				group_getname($group_id).', '.utils_get_tracker_prefix(ARTIFACT).' #'.$item_id,
				group_getname($new_group_id).', '.utils_get_tracker_prefix($reassign_change_artifact).' #'.$new_item_id,
				$item_id,
				false,
				ARTIFACT,
				1);

      trackers_data_add_history('Reassign item',
				group_getname($group_id).', '.utils_get_tracker_prefix(ARTIFACT).' #'.$item_id,				
				group_getname($new_group_id).', '.utils_get_tracker_prefix($reassign_change_artifact).' #'.$new_item_id,
				$new_item_id,
				false,
				$reassign_change_artifact,
				1);

      # Duplicate the comments
      $sql = "SELECT * FROM ".ARTIFACT."_history WHERE bug_id='".$item_id."' AND type='100'";
      $res_history = db_query($sql);
      while ($row_history = db_fetch_array($res_history))
	{
	  $sql = "INSERT INTO ".$reassign_change_artifact."_history ".
	     "(bug_id,field_name,old_value,mod_by,date,type) ".
	     "VALUES ".
	     "('".$new_item_id."','".
	     $row_history['field_name']."','".
	     addslashes($row_history['old_value'])."','".
	     $row_history['mod_by']."','".
	     $row_history['date']."','".
	     $row_history['type']."')";

	  $result = db_query($sql);

	  if (!$result)
	    {
	      fb(_("Unable to duplicate a comment  from the original item report information."), 1);
	    }
	}

      # Add a comment giving every original information
      $comment = "This item has been reassigned from the project ".group_getname($row_data['group_id'])." ".ARTIFACT." tracker to your tracker.\n\nThe original report is still available at ".ARTIFACT." #$item_id\n\nFollowing are the information included in the original report:\n\n";

      $sql  = "SHOW COLUMNS FROM ".ARTIFACT;
      $res_show = db_query($sql);
      $list = array();
      while ($row_show = db_fetch_array($res_show))
	{
	  # Build a list of any possible field
	  $list[] = $row_show['Field'];
	}

      while (list($l,$v) = each($list))
	{
	  if ($row_data[$l])
	    {
	      $comment .= "[field #".$l."] ";
	      $comment .= trackers_field_display($v,
						 $group_id,
						 $row_data[$l],
						 false,
						 true,
						 true, #6
						 true);
	      $comment .= "<br />";
	    }
	}

      # Make sure there is no \' remaining
      $comment = ereg_replace("'", " ", $comment);
      $sql = "INSERT INTO ".$reassign_change_artifact."_history ".
	 "(bug_id,field_name,old_value,mod_by,date,type)".
	 " VALUES ".
	 "('".$new_item_id."',".
	 "'details',".
	 "'".addslashes($comment)."',".
	 "'".user_getid()."',".
	 "'".$now."',".
	 "'100')";

      $result = db_query($sql);

      if (!$result)
	{
	  fb(_("Unable to add a comment with the original item report information."), 0);
	}

      # Usually, reassigning means duplicating data.
      # In case of attached files, we simply reassign the file to another
      # item. This could avoid wasting too much disk space as file are expected
      # to be much bigger than CC list and alike.
      $sql = "UPDATE trackers_file SET item_id='".$new_item_id."', artifact='".$reassign_change_artifact."' WHERE item_id='".$item_id."' AND artifact='".ARTIFACT."'";
      $result = db_query($sql);
      if (!$result)
	{
	  fb(sprintf(_("Unable to duplicate an attached file (%s) from the original item report information."), $row_attachment['filename']), 1);
	  dbg("sql: $sql");
	}

      # Duplicate CC List
      $sql = "SELECT * FROM ".ARTIFACT."_cc WHERE bug_id='".$item_id."'";
      $res_cc = db_query($sql);
      while ($row_cc = db_fetch_array($res_cc))
	{
	  $sql = "INSERT INTO ".$reassign_change_artifact."_cc ".
	     "(bug_id,email,added_by,comment,date) ".
	     "VALUES ".
	     "('".$new_item_id."','".
	     $row_cc['email']."','".
	     $row_cc['added_by']."','".
	     addslashes($row_cc['comment'])."','".
	     $row_cc['date']."')";

	  $result = db_query($sql);

	  if (!$result)
	    {
	      fb(sprintf(_("Unable to duplicate a CC address (%s) from the original item report information."), $row_cc['email']), 1);
	    }
	}

      # Update data of the original to make sure people dont get confused
      # Close the original item
      $result = db_query("UPDATE ".ARTIFACT." SET status_id='3',close_date='".$now."',summary='Reassigned to another tracker [was: ".addslashes($row_data['summary'])."]',details='<p class=\"warn\">THIS ITEM WAS REASSIGNED TO ".strtoupper(utils_get_tracker_prefix($reassign_change_artifact)).' #'.$new_item_id.".</p> ".addslashes($row_data['details'])."' WHERE bug_id='".$item_id."'");
      trackers_data_add_history('close_date',$now,$now,$item_id);      

      if (!$result)
	{
	  fb(_("Unable to close the original item report."), 1);
	}
      else
	{
	  fb(_("Original item is now closed."));
	}
      
      # Finally put an extra comment so people dont get confused
      # (it is not important, so run the sql without checks)
      $sql = "INSERT INTO ".ARTIFACT."_history ".
	 "(bug_id,field_name,old_value,mod_by,date,type)".
	 " VALUES ".
	 "('".$item_id."',".
	 "'details',".
	 "'<p class=\"warn\">THIS ITEM WAS REASSIGNED TO ".strtoupper(utils_get_tracker_prefix($reassign_change_artifact)).' #'.$new_item_id.".</p><p>Please, do not post any new comments to this item.</p>',".
	 "'".user_getid()."',".
	 "'".$now."',".
	 "'100')";
      db_query($sql);

      # Now send the notification (this must be done here, because we got
      # here the proper new id, etc)
      list($additional_address, $sendall) = trackers_data_get_item_notification_info($new_item_id, $reassign_change_artifact, 1);
      if (($sendall == 1) && (trim($address) != "") && (trim($additional_address) != "")) 
	{ $address .= ", "; }
      $address .= $additional_address;
      trackers_mail_followup($new_item_id, $address, false, false, $reassign_change_artifact);
      

      # If we get here, assume everything went properly
      return true;
    }

  return false;
}

function trackers_data_update_dependent_items ($depends_on, $item_id, $artifact)
{
  global $feedback,$ffeedback;

  # Check if the dependency does not already exists.
  $sql = "SELECT item_id FROM ".ARTIFACT."_dependencies WHERE item_id='".$item_id."' AND is_dependent_on_item_id='".$depends_on."' AND is_dependent_on_item_id_artifact='".$artifact."'";

  # If there is no dependency know, insert it.
  if (!db_numrows(db_query($sql)))
    {
      $sql="INSERT INTO ".ARTIFACT."_dependencies VALUES ('$item_id','$depends_on', '$artifact')";
      $result=db_query($sql);
      if (!$result)
	{
	  fb(_("Error inserting dependency"), 1);
	}
      else
	{
          fb(_("Dependency added"));
	  trackers_data_add_history("Dependencies",
				    "-",
				    "Depends on ".$artifact." #".$depends_on,
				    $item_id,
				    0,0,1);
	  trackers_data_add_history("Dependencies",
				    "-",
				    ARTIFACT." #".$item_id." is dependent",
				    $depends_on,
				    0,0,1);
	}
    }
}

# yeupou--gnu.org 2004-11-10: this function should probably removed
# and handle_update be used instead.
function trackers_data_create_item($group_id,$vfl,&$extra_addresses)
{

  # we dont force them to be logged in to submit a bug
  unset($ip);
  if (!user_isloggedin())
    { $user = 100; }
  else
    { $user=user_getid(); }

  # make sure required fields are not empty
  if (trackers_check_empty_fields($vfl) == false)
    {
      # In such circonstances, we reprint the form
      # highligthing missing fields.
      # (It is important that trackers_check_empty_fields set the global var
      # previous_form_bad_fields)
      return false;
    }

  # Finally, create the bug itself
  # Remark: this SQL query only sets up the values for fields used by
  # this project. For other unused fields we assume that the DB will set
  # up an appropriate default value (see bug table definition)

  # Extract field transitions possibilities:
  $field_transition = trackers_data_get_transition($group_id);
  # We will store in an array the transition_id accepted, to check
  # other fields updates
  $field_transition_accepted = array();
  $changes = array();

  # Build the variable list of fields and values
  # Remark: we must add open/closed by ourselves, as it is missing from the
  # form for obvious reasons while automatic transitions may rely on its
  # presence
  $vfl['status_id'] = '1';
  reset($vfl);
  $insert_fields = array();
  $field_transition_id = '';
  while (list($field,$value) = each($vfl))
    {
      if (trackers_data_is_special($field))
	{ continue; }

      # If value is the special string default,*
      # take the default from the database
      if ($value == "!unknown!")
	{ continue; }

      # COPIED from handle_update transition code, with one exception:
      # old_value is equal to "none".
      # handle field transitions checks/changes
      $field_id = trackers_data_get_field_id($field);
      $field_transition_cc = '';
      if (array_key_exists($field_id, $field_transition))
	{
          # First check basic transition
	  # And check multiple transition, override other transition
	  if (array_key_exists("100", $field_transition[$field_id])||
	      array_key_exists("any", $field_transition[$field_id]))
	    {
	      if (array_key_exists("any", $field_transition[$field_id]) && array_key_exists($value, $field_transition[$field_id]["any"]))
		{
		  $field_transition_cc = $field_transition[$field_id]["any"][$value]['notification_list'];

		  # Register the transition, but only if the field it is about
                  # was not filled in the form
		  if (!is_array($changes[$field]) ||
		      (!array_key_exists('del', $changes[$field]) &&
		      !array_key_exists('add', $changes[$field])))
		    {

		      $field_transition_id = $field_transition[$field_id]["any"][$value]['transition_id'];
		    }
		}
	      else if (array_key_exists("100", $field_transition[$field_id]) && array_key_exists($value, $field_transition[$field_id]["100"]))
		{

		  $field_transition_cc = $field_transition[$field_id]["100"][$value]['notification_list'];
                  # Register the transition, but only if the field it is about
                  # was not filled in the form
		  if (!is_array($changes[$field]) ||
		      (!array_key_exists('del', $changes[$field]) &&
		       !array_key_exists('add', $changes[$field])))
		    {
		      $field_transition_id = $field_transition[$field_id]["100"][$value]['transition_id'];
		    }
		}

	    }
	}

      if (trackers_data_is_text_area($field) ||
	  trackers_data_is_text_field($field))
	{
	  $value = htmlspecialchars($value);
	}
      else if (trackers_data_is_date_field($field))
	{
	  # if it is a date we must convert the format to unix time
	  list($value,$ok) = utils_date_to_unixtime($value);
	}

      $insert_fields[$field] = $value;

      # Keep track of the change:
      $changes[$field]['del']=
	trackers_field_display($field,$group_id,'',false,false,true,true);
      $changes[$field]['add']=
	trackers_field_display($field,$group_id,$value,false,false,true,true);

      $changes[$field]['del-val']= '';
      $changes[$field]['add-val']= $value;

      # Register transition id
      $field_transition_accepted[] = $field_transition_id;


      if ($field_transition_cc)
        { $extra_addresses .= $field_transition_cc; }
    }

  # Get the default spamscore
  $spamscore = spam_get_user_score($user);
  if ($spamscore > 4)
    {
      $vfl['summary'] = "[SPAM] ".$vfl['summary'];
    }


  # Add all special fields that were not handled in the previous block
  $insert_fields['close_date'] = 0;
  $insert_fields['group_id'] = $group_id;
  $insert_fields['submitted_by'] = $user;
  $insert_fields['date'] = time();
  $insert_fields['summary'] = htmlspecialchars($vfl['summary']);
  $insert_fields['details'] = htmlspecialchars($vfl['details']);
  $insert_fields['spamscore'] = $spamscore;

  # If not project member, save the IP
  if (!member_check(0, $group_id))
    {  
      $insert_fields['ip'] = $_SERVER['REMOTE_ADDR'];
    }

  # Actually insert the entry
  $result=db_autoexecute(ARTIFACT, $insert_fields, DB_AUTOQUERY_INSERT);
  $item_id=db_insertid($result);

  if (!$item_id)
    {
      fb(_("New item insertion failed, please report this issue to the administrator"), 1);
      return false;
    }

  $item = utils_get_tracker_prefix(ARTIFACT)." #".$item_id;;
  fb(sprintf(_("New item posted (%s)"), $item));

  # Register the spam score
  spam_set_item_default_score($item_id, 
			      '0',
			      ARTIFACT,
			      $spamscore, 
			      $user);

  # Add to spamcheck queue if necessary (will temporary set the spamscore to
  # 5, if necessary)
  # Useless if already considered to be spam.
  if ($spamscore < 5)
    {  spam_add_to_spamcheck_queue($item_id, 0, ARTIFACT, $group_id, $spamscore); }

  # If we are on the cookbook, Store related links
  if (ARTIFACT == 'cookbook')
   {
     cookbook_handle_update($item_id,$group_id);
   }

  # Now we run transitions other fields update. This function does check
  # what already changed and that we wont automatically update
  trackers_transition_update_item($item_id, $field_transition_accepted, $changes);

  # Add the submitter in CC
  # (currently, no option to avoid this, but we could make this a notif
  # configuration option, if wanted)
  if (user_isloggedin())
    {
      trackers_add_cc($item_id,
		      $group_id,
		      user_getid(),
		      "-SUB-"); # use a flag as comment, because if we 
		               # translate the string now, people will get
		               # the translation of the submitter when they
		               # read the item, not necessarily the one they
		               # want
			}
  
  #now return the trackers_id
  return $item_id;
}

function trackers_data_get_value($field,$group_id,$value_id,$by_field_id=false)
{
  /*
      simply return the value associated with a given value_id
      for a given field of a given group. If associated value not
      found then return value_id itself.
      By doing so if this function is called by mistake on a field with type
      text area or text field then it returns the text itself.
  */

  # close_date and assigned_to fields are special select box fields
  if (($field == 'assigned_to') || ($field == 'submitted_by'))
    {
      return user_getname($value_id);
    }
  else if (trackers_data_is_date_field($field))
    {
      return utils_format_date($value_id);
    }

  if ($by_field_id)
    {
      $field_id = $field;
    }  else
      {
	$field_id = trackers_data_get_field_id($field);
      }

  # Look for project specific values first...
  $sql="SELECT * FROM ".ARTIFACT."_field_value ".
     "WHERE  bug_field_id='$field_id' AND group_id='$group_id' ".
     "AND value_id='$value_id'";
  $result=db_query($sql);
  if ($result && db_numrows($result) > 0)
    {
      return db_result($result,0,'value');
    }

  # ... if it fails, look for system wide default values (group_id=100)...
  $sql="SELECT * FROM ".ARTIFACT."_field_value ".
     "WHERE  bug_field_id='$field_id' AND group_id='100' ".
     "AND value_id='$value_id'";
  $result=db_query($sql);
  if ($result && db_numrows($result) > 0)
    {
      return db_result($result,0,'value');
    }

  # No value found for this value id !!!
  return $value_id.'(Error - Not Found)';

}

function trackers_data_get_canned_responses ($group_id)
{
  /*
      Show defined and site-wide responses
  */
  $sql="SELECT bug_canned_id,title,body,order_id FROM ".ARTIFACT."_canned_responses WHERE ".
     "(group_id='$group_id' OR group_id='0') ORDER BY order_id ASC";

  # return handle for use by select box
  return db_query($sql);
}

function trackers_data_get_reports($group_id, $user_id=100, $sober=false)
{
  # Currently, reports are group based.
  # Print first system reports.

  # OUTDATED: currently personal query forms are deactivated in the code
  # If user is unknown then get only project-wide and system wide reports
  # else get personal reports in addition  project-wide and system wide.

  # We usually want non-sober specific query forms.
  $system_scope = 'S';
  if ($sober)
    {
      $system_scope = 'SSB';
    }

  $sql = 'SELECT report_id,name FROM '.ARTIFACT.'_report WHERE ';

#  if (!$user_id || ($user_id == 100))
#    {

 $sql .= "(group_id=$group_id AND scope='P') OR scope='$system_scope' ORDER BY scope DESC , report_id ASC ";

  # OUTDATED: currently personal query forms are deactivated in the code
#    }  else
#      {
#        $sql .= "(group_id=$group_id AND (user_id=$user_id OR scope='P')) OR ".
#	   "scope='S' ORDER BY scope,report_id";
#      }
# print "DBG sql report = $sql";

  return db_query($sql);
}

function trackers_data_get_notification($user_id)
{
  $sql = "SELECT role_id,event_id,notify FROM trackers_notification WHERE user_id='$user_id'";
  return db_query($sql);
}

function trackers_data_get_notification_with_labels($user_id)
{
  $sql = 'SELECT role_label,event_label,notify FROM trackers_notification_role, trackers_notification_event, trackers_notification '.
     "WHERE trackers_notification.role_id=trackers_notification_role.role_id AND trackers_notification.event_id=trackers_notification_event.event_id AND user_id='$user_id'";
  return db_query($sql);
}

function trackers_data_get_notification_roles()
{
  $sql = 'SELECT * FROM trackers_notification_role ORDER BY rank ASC;';
  return db_query($sql);
}

function trackers_data_get_notification_events()
{
  $sql = 'SELECT * FROM trackers_notification_event ORDER BY rank ASC;';
  return db_query($sql);
}

function trackers_data_delete_notification($user_id)
{
  $sql = "DELETE FROM trackers_notification WHERE user_id='$user_id'";
  return db_query($sql);
}

function trackers_data_insert_notification($user_id, $arr_roles, $arr_events,$arr_notification)
{

  $sql = 'INSERT INTO  trackers_notification (user_id,role_id,event_id,notify) VALUES ';

  $num_roles = count($arr_roles);
  $num_events = count($arr_events);
  for ($i=0; $i<$num_roles; $i++)
    {
      $role_id = $arr_roles[$i]['role_id'];
      for ($j=0; $j<$num_events; $j++)
	{
	  $event_id = $arr_events[$j]['event_id'];
	  $sql .= "('$user_id','$role_id','$event_id','".$arr_notification[$role_id][$event_id]."'),";
	}
    }
  $sql = substr($sql,0,-1); # remove extra comma at the end
  return db_query($sql);
}

function trackers_data_get_watchers($user_id)
{
  $sql = "SELECT user_id,group_id FROM trackers_watcher WHERE watchee_id='$user_id'";
  return db_query($sql);
}

function trackers_data_get_watchees($user_id)
{
  $sql = "SELECT watchee_id,group_id FROM trackers_watcher WHERE user_id='$user_id'";
  return db_query($sql);
}

function trackers_data_insert_watchees($user_id, $arr_watchees)
{
  # No longer really used
  $sql = 'INSERT INTO trackers_watcher (user_id,watchee_id) VALUES ';
  $num_watchees = count($arr_watchees);
  for ($i=0; $i<$num_watchees; $i++)
    {
      $sql .= "('$user_id','".$arr_watchees[$i]."'),";
    }
  $sql = substr($sql,0,-1); # remove extra comma at the end
  return db_query($sql);
}


function trackers_data_add_watchees ($user_id, $watchee_id, $group_id)
{
  if (member_check(0,$group_id) && !trackers_data_is_watched($user_id,$watchee_id,$group_id))
    {
      # Only accept the request from a member of the project
      # Note that a user can trick the URL to watch himself
      # It has no consequences, so we do not care.
      $sql = 'INSERT INTO trackers_watcher (user_id,watchee_id,group_id) VALUES '.
	 "('$user_id','$watchee_id','$group_id')";
      return db_query($sql);
    }
  else
    {
      return 0;
    }
}


function trackers_data_delete_watchees ($user_id, $watchee_id, $group_id)
{
  $sql = "DELETE FROM trackers_watcher WHERE user_id='$user_id' AND watchee_id='$watchee_id' AND group_id='$group_id'";
  return db_query($sql);
}


function trackers_data_is_watched ($user_id, $watchee_id, $group_id)
{
  $sql = "SELECT watchee_id FROM trackers_watcher WHERE user_id='$user_id' AND watchee_id='$watchee_id' AND group_id='$group_id'";
  return db_result(db_query($sql),0,'watchee_id');
}


function trackers_data_delete_file($group_id, $item_id, $item_file_id)
{
  # Make sure the attachment belongs to the group
  $res = db_query("SELECT bug_id from ".ARTIFACT." WHERE bug_id=$item_id AND group_id=$group_id");
  if (db_numrows($res) <= 0)
    {
      sprintf(_("Item #%s doesn't belong to project"), $item_id);
      return;
    }

  # Now delete the attachment
  $result = db_query("DELETE FROM trackers_file WHERE item_id='$item_id' AND file_id='$item_file_id'");
  if (!$result)
    {
      "Error deleting attachment #$item_file_id: ".db_error($res);
    }
  else
    {
      fb(_("File successfully deleted"));
      trackers_data_add_history("Attached File",
				"#".$item_file_id,
				"Removed",
				$item_id,
				0,0,1);
    }

}



function trackers_data_count_field_value_usage ($group_id, $field, $field_value_value_id)
{
  return db_numrows(db_query("SELECT bug_id FROM ".ARTIFACT." WHERE $field='$field_value_value_id' AND group_id='$group_id'"));
}
