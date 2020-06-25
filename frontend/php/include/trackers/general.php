<?php
# General tracker functions.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
#
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
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

require_once(dirname(__FILE__).'/../calendar.php');
require_once(dirname(__FILE__).'/../sendmail.php');
require_once(dirname(__FILE__).'/data.php');
require_once(dirname(__FILE__).'/format.php');

# Return the file that should be included, according to the URL
# requested. If the file start with ?, it's an index.
function trackers_include()
{
  # Keep the dirname only if it's admin.
  $dir = get_module_include_dir($_SERVER['SCRIPT_NAME'], 0, 1);
  $pre = '';
  if ($dir != "admin")
    $dir = '';
  else
    {
      $dir = $dir."/";
      $pre = "../";
    }

  return $pre."../include/trackers_run/".$dir.basename($_SERVER['SCRIPT_NAME']);
}

# Does like trackers_include() but load an arbitrary page of the common
# tracker code. This is useful for trackers that have non-standard behavior
# needs to present some standard page inside a non standard location.
function trackers_bastardinclude($page, $is_admin_page='0')
{
  $pre = '';
  if ($is_admin_page)
    $pre = "../";

  return $pre."../include/trackers_run/".$page.".php";
}


# Generate URL arguments from a variable wether scalar or array.
function trackers_convert_to_url_arg($varname, $var)
{

  if (is_array($var))
    {
      foreach ($var as $v)
        $ret .= '&' . $varname . '[]=' . $v;
    }
  else
    $ret .= '&' . $varname . '=' . $var;
  return $ret;
}

function trackers_header($params)
{
  global $group_id,$is_bug_page,$DOCUMENT_ROOT,$advsrch;

  # Used so the search box will add the necessary element to the pop-up box.
  # yeupou, 2005-09-11: is that still useful?
  $is_bug_page=1;

  # Required params for site_project_header().
  $params['group']=$group_id;
  $params['context']=ARTIFACT;

  $project=project_get_object($group_id);

  #needs to be turned  on
  if (ARTIFACT == "bugs"  && !$project->Uses("bugs")
      || ARTIFACT == "support" &&  !$project->Uses("support")
      || ARTIFACT == "task" && !$project->Uses("task")
      || ARTIFACT == "patch" && !$project->Uses("patch"))
    exit_error(_("This project has turned off this tracker."));
  print site_project_header($params);
}

function trackers_header_admin($params)
{
  global $group_id,$is_bug_page,$DOCUMENT_ROOT;

  # Used so the search box will add the necessary element to the pop-up box.
  $is_bug_page=1;

  #required params for site_project_header();
  $params['group']=$group_id;
  $params['context']='a'.ARTIFACT;

  $project=project_get_object($group_id);

  # need to be turned on
  if (ARTIFACT == "bugs"  && !$project->Uses("bugs")
      || ARTIFACT == "support" &&  !$project->Uses("support")
      || ARTIFACT == "task" && !$project->Uses("task")
      || ARTIFACT == "patch" && !$project->Uses("patch"))
    exit_error(_("This project has turned off this tracker."));
  print site_project_header($params);
}

function trackers_footer($params)
{
  site_project_footer($params);
}

function trackers_init($group_id)
{
  # Set the global arrays for faster processing at init time.
  trackers_data_get_all_fields($group_id, true);
}

function trackers_report_init($report_id)
{
  # Set the global array with report information for faster processing.
  return trackers_data_get_all_report_fields($report_id);
}

function trackers_list_all_fields($sort_func=false,$by_field_id=false)
{
  global $BF_USAGE_BY_ID, $BF_USAGE_BY_NAME, $AT_START;

  # If its the first element we fetch then apply the sort
  # function.
  if ($AT_START)
    {
      if (!$sort_func)
        $sort_func = 'cmp_place';
      uasort($BF_USAGE_BY_ID, $sort_func);
      uasort($BF_USAGE_BY_NAME, $sort_func);
      $AT_START=false;
    }

  # Return the next bug field in the list.  If the global
  # bug field usage array is not set then set it the
  # first time.
  # by_field_id: true return the list of field id, false returns the
  # list of field names.

  if (current ($BF_USAGE_BY_ID) !== FALSE)
    {
      $field_array = current ($BF_USAGE_BY_ID);
      $key = key ($BF_USAGE_BY_ID);
      next ($BF_USAGE_BY_ID);
      return($by_field_id ? $field_array['bug_field_id']
             : $field_array['field_name']);
    }
  # Rewind internal pointer for the next time.
  reset($BF_USAGE_BY_ID);
  reset($BF_USAGE_BY_NAME);
  $AT_START=true;
  return(false);
}

function trackers_field_label_display ($field_name, $group_id,$break=false,
                                       $ascii=false, $tab=25)
{
  $label = trackers_data_get_label($field_name).':';
  $output = '';

  if (!$ascii)
    $output .= '<span class="preinput"><span class="help" title="'
               .trackers_data_get_description($field_name).'">'.$label
               .'</span></span>';

  if ($break)
    $output .= ($ascii?"\n":'<br />');
  else
    {
      if (!$ascii)
        $output .= '&nbsp;';
      else
        $output .= sprintf("%".$tab."s", $label).' ';
    }
  return $output;
}

function trackers_field_display ($field_name,
                                 $group_id,
                                 $value='xyxy',
                                 $break=false, #4
                                 $label=true,
                                 $ro=false, #6
                                 $ascii=false,
                                 $show_none=false, #8
                                 $text_none='None',
                                 $show_any=false, #10
                                 $text_any='Any',
                                 $allowed_transition_only=false, #12
                                 $show_unknown=false,
                                 $tab=25)
{
/*
  Display a bug field either as a read-only value or as a read-write
  making modification possible.
  - field_name : name of the bug field (column name).
  - group_id : the group id (project id).
  - value: the current value stored in this field (for select boxes type of field
          it is the value_id actually. It can also be an array with mutliple values.
  - break: true if a break line is to be inserted between the field label
         and the field value.
  - label: if true display the field label.
  - ro: true if only the field value is to be displayed. Otherwise
         display an HTML select box, text field or text area to modify the value.
  - ascii: if true do not use any HTML decoration just plain text (if true
         then read-only (ro) flag is forced to true as well).
  - show_none: show the None entry in the select box if true (value_id 100).
  - text_none: text associated with the none value_id to display in the select box
  - show_any: show the Any entry in the select box if true (value_id 0).
  - text_any: text associated with the any value_id to display in the select box
  - allowed_transition_only: print only transition allowed.  */

  global $sys_datefmt;
  $output = '';

  if ($label)
    {
      $output = trackers_field_label_display($field_name,
                                             $group_id,
                                             $break,
                                             $ascii,
                                             $tab);
    }

  # Display depends upon display type of this field.
  switch (trackers_data_get_display_type($field_name))
    {
    case 'SB':
      if ($ro)
        {
          # If multiple values are selected, return a list
          # of <br />-separated values.
          $arr = ( is_array($value) ? $value : array($value));
          for ($i=0;$i < count($arr); $i++)
            {
              if ($arr[$i] == 0 )
                $arr[$i] = $text_any;
              else if ($arr[$i] == 100 && $field_name != 'percent_complete')
                $arr[$i] = $text_none;
              else
                $arr[$i] = trackers_data_get_value($field_name,$group_id,
                                                   $arr[$i]);
            }
          $output .= join('<br />', $arr);
        }
      else
        {
          # If it is a user name field (assigned_to, submitted_by) then make
          # sure to add the "None" entry in the menu 'coz it's not in the DB.
          if (trackers_data_is_username_field($field_name))
            {
              $show_none=true;
              $text_none=_('None');
            }

          if (is_array($value))
            {
              $output .= trackers_multiple_field_box($field_name,'',$group_id, $value,
                                                     $show_none,$text_none,$show_any,
                                                     $text_any);
            }
          else
            {
              $output .= trackers_field_box($field_name,
                                            '',
                                            $group_id,
                                            $value, #4
                                            $show_none,
                                            $text_none,
                                            $show_any,
                                            $text_any, #8
                                            $allowed_transition_only,
                                            $show_unknown);
            }
        }
      break;

    case 'DF':
      if ($ascii)
          $output .= ( ($value == 0) ? '' : utils_format_date($value));
      else
        {
          if ($ro)
            $output .= utils_format_date($value);
        else
          {
            $output .= trackers_field_date($field_name,
                                           (($value == 0) ? '' :
                                            strftime("%Y-%m-%d",$value)));
          }
        }
      break;

    case 'TF':
      if ($ascii)
        $output .= utils_unconvert_htmlspecialchars($value);
      else
        $output .= ($ro ? $value: trackers_field_text($field_name,$value));
      break;

    case 'TA':
      if ($ascii)
        $output .= utils_unconvert_htmlspecialchars($value);
      else
        $output .= ($ro ? markup_full($value):
                    trackers_field_textarea($field_name,$value));
      break;

    default:
      $output .= 'Unknown '.ARTIFACT.' Field Display Type';
    }
  return($output);
}

function trackers_field_date($field_name,$value='',$size=0,$maxlength=0,$ro=false)
{
  # Value is formatted as Y-m-d.
  $t = preg_split('/-/', $value);
  $year = isset($t[0]) ? $t[0] : null;
  $month = isset($t[1]) ? $t[1] : null;
  $day = isset($t[2]) ? $t[2] : null;

  if ($ro)
    {
      $html = $value;
    }
  else
    {
      if (!$size || !$maxlength)
        {
          $t = trackers_data_get_display_size($field_name);
          $size = isset($t[0]) ? $t[0] : null;
          $$maxlength = isset($t[1]) ? $t[1] : null;
        }

      # Date part are missing, take the date of the day.
      $today = localtime();
      if (!$day)
        $day = ($today[3]);
      if (!$month)
        $month = ($today[4]+1);
      if (!$year)
        $year = ($today[5]+1900);

      $html = calendar_select_date($day, $month, $year,
                                   array ($field_name.'_dayfd',
                                          $field_name.'_monthfd',
                                          $field_name.'_yearfd'));
    }
  return($html);
}

function trackers_multiple_field_date($field_name,$date_begin='',$date_end='',
                                      $size=0,$maxlength=0,$ro=false)
{
  # FIXME: this is broken, should be made as trackers_field_date.

  if ($ro)
    if ($date_begin || $date_end)
      $html = _("Start:")."&nbsp;$date_begin<br />"._("End:")."&nbsp;$date_end";
    else
      $html = _('Any time');
  else
    {
      if (!$size || !$maxlength)
        list($size, $maxlength) = trackers_data_get_display_size($field_name);

      $html = '<label for="'.$field_name.'">'._('Start:').'</label><br />
<input type="text" name="'.$field_name.'" id="'.$field_name
        . '" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$date_begin.'">'
        ._('(yyyy-mm-dd)')
        .'</td></tr><tr><td>'
        ._('End:').'<br />
<input type="text" name="'.$field_name.'_end" id="'.$field_name.'_end'
        .'" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$date_end.'">'
        ._('(yyyy-mm-dd)');

      $html = '<table><tr><td>'.$html.'</td></tr></table>';
    }
  return($html);
}

function trackers_field_date_operator($field_name,$value='',$ro=false)
{
  if ($ro)
    $html = htmlspecialchars($value);
  else
    $html = '<select title="'._("comparison operator")
.'" name="'.$field_name.'_op">
<option value=">"'.(($value == '>') ? ' selected':'').'>&gt;</option>
<option value="="'.(($value == '=') ? ' selected':'').'>=</option>
<option value="<"'.(($value == '<') ? ' selected':'').'>&lt;</option>
</select>
';
  return($html);
}

function trackers_field_text($field_name,$value='',$size=0,$maxlength=0)
{
  if (!$size || !$maxlength)
    list($size, $maxlength) = trackers_data_get_display_size($field_name);

  $html = '<input type="text" name="'.$field_name
     .'" title="'.trackers_data_get_description($field_name)
     .'" size="'.$size.'" maxlength="'.$maxlength.'" value="'.$value.'" />';
  return($html);
}

function trackers_field_textarea($field_name,$value='',$cols=0,$rows=0, $title=false)
{
  if ($title === false)
    $title = trackers_data_get_description($field_name);
  if (!$cols || !$rows)
    {
      $t = trackers_data_get_display_size($field_name);
      $cols = isset($t[0]) ? $t[0] : null;
      $rows = isset($t[1]) ? $t[1] : null;

      # Nothing defined for this field? Use hardcoded default values.
      if (!$cols || !$rows)
        {
          $cols = "65";
          $rows = "16";
        }
    }

  $html = '<textarea id="'.$field_name.'" name="'.$field_name
     .'" title="'.$title
     .'" rows="'.$rows.'" cols="'.$cols.'" wrap="soft">'.$value.'</textarea>';
  return($html);
}

# Return a select box populated with field values for this project.
# If box_name is given, then impose this name in the select box
# of the  HTML form otherwise use the field_name.
function trackers_field_box ($field_name,
                             $box_name='',
                             $group_id,
                             $checked=false, #4
                             $show_none=false,
                             $text_none='None',
                             $show_any=false,
                             $text_any='Any', #8
                             $allowed_transition_only=false,
                             $show_unknown=false)
{
  if (!$group_id)
    return _('Error: no group defined');

  $title = trackers_data_get_description ($field_name);
  if ($title == '')
    $title= trackers_data_get_label ($field_name);

  $result = trackers_data_get_field_predefined_values($field_name,
                                                      $group_id,$checked);
  if ($box_name == '')
    $box_name = $field_name;

  if ($allowed_transition_only)
    {
      $field_id = trackers_data_get_field_id($field_name);

      # First check if group has defined transitions for this field.
      $res = db_execute("SELECT transition_default_auth "
                        ."FROM ".ARTIFACT."_field_usage "
                        ."WHERE group_id=? AND bug_field_id=?",
                        array($group_id, $field_id));
      $default_auth = 'A';
      if (db_numrows($res) > 1)
        $default_auth = db_result($res, 0, 'transition_default_auth');
      # Avoid corrupted database content, if its not F, it must be A.
      if ($default_auth != "F")
        $default_auth = "A";

      $trans_result = db_execute(
        "SELECT from_value_id,to_value_id,is_allowed,notification_list
         FROM trackers_field_transition
         WHERE group_id=? AND artifact=? AND field_id=?
         AND (from_value_id=? OR from_value_id='0')",
        array($group_id, ARTIFACT, $field_id, $checked));
      $forbidden_to_id = array();
      $allowed_to_id = array();
      $rows = db_numrows($trans_result);
      if ($trans_result && $rows > 0 || $default_auth == "F")
        {
          while ($transition = db_fetch_array($trans_result))
            {
              if ($transition['is_allowed'] == 'F')
                {
                  $forbidden_to_id[$transition['to_value_id']] = 0;
                }
              else
                {
                  $allowed_to_id[$transition['to_value_id']] = 0;
                }
            }

          # Get all the predefined values for this field.
          $rows=db_numrows($result);

          if ($rows > 0)
            {
              $val_label = array();
              while ($val_row = db_fetch_array($result))
                {
                  $value_id = $val_row['value_id'];
                  $value   = $val_row['value'];
                  if ((($default_auth == 'A')
                        && (!array_key_exists($value_id, $forbidden_to_id)))
                      ||
                      (($default_auth == 'F')
                        && (array_key_exists($value_id, $allowed_to_id)))
                      ||
                      ($value_id == $checked))
                    $val_label[$value_id] = $value;
                }

              # Always add the any values cases.
              return html_build_select_box_from_arrays(array_keys($val_label),
                                                       array_values($val_label),
                                                       $box_name,
                                                       $checked, #4
                                                       $show_none,
                                                       $text_none, #6
                                                       $show_any,
                                                       $text_any, #8
                                                       $show_unknown,$title);
            }
        } # if ($trans_result && $rows > 0 || $default_auth == "F")
    } # if ($allowed_transition_only)

# If no transition is defined, use 'normal' code.
  return html_build_select_box ($result,$box_name,$checked,$show_none,
                                $text_none,$show_any, $text_any,$show_unknown,
                                $title);
}

# Return a multiple select box populated with field values for this project.
# If box_name is given then impose this name in the select box
# of the  HTML form otherwise use the field_name.
function trackers_multiple_field_box($field_name,
                                     $box_name='',
                                     $group_id,
                                     $checked=false,
                                     $show_none=false,
                                     $text_none='None',
                                     $show_any=false,
                                     $text_any='Any',
                                     $show_value=false)
{
  if (!$group_id)
    return _("Internal error: no group id");
  $result = trackers_data_get_field_predefined_values($field_name,$group_id,
                                                      $checked);
  if ($box_name == '')
    {
      $box_name = $field_name.'[]';
    }
  return html_build_multiple_select_box($result,$box_name,$checked,6,
                                        $show_none,$text_none, $show_any,
                                        $text_any,$show_value);
}

# Similar to trackers_multiple_field_box except that it will use checkboxes
# instead of a multiple select field. Multiple select field is nice for
# expert users, but it is not simple user-friendly, unlike checkboxes.
function trackers_multiple_field_box2 ($field_name,
                                       $box_name='',
                                       $group_id,
                                       $checked=false,
                                       $show_none=false,
                                       $text_none='None',
                                       $show_any=false,
                                       $text_any='Any',
                                       $show_value=false)
{
  if (!$group_id)
    return _("Internal error: no group id");
  $result = trackers_data_get_field_predefined_values($field_name,$group_id,
                                                      $checked);
  if ($box_name == '')
    {
      $box_name = $field_name.'[]';
    }
  return html_build_checkbox($result,$box_name,$checked,6,$show_none,
                             $text_none, $show_any,$text_any,$show_value);
}

# Returns the list of field names in the HTML Form corresponding to a
# field used by this project
function trackers_extract_field_list($post_method=true)
{
  global $BF_USAGE_BY_NAME;
  # Specific: it must build the date fields if it finds _dayfd, _monthfd
  # or _yearfd, because date fields comes from 3 separated input.
  $vfl = array();
  $date = array();
  if ($post_method)
    $superglobal =& $_POST;
  else
    $superglobal =& $_GET;

  foreach ($superglobal as $key => $val)
    {
      if (preg_match("/^(.*)_(day|month|year)fd$/", $key, $found))
        {
          # Must build the date field key.
          $field_name = $found[1];
          $field_name_part = $found[2];

          # We also must increment $day and $month, because the select
          # starts from zero.

          # Get what we already have.
          if (!isset($vfl[$field_name]))
            $vfl[$field_name] = '--';
          list($year, $month, $day) = preg_split("/-/", $vfl[$field_name]);
          if ($field_name_part  == 'day')
            $vfl[$field_name] = "$year-$month-$val";
          elseif ($field_name_part == 'month')
            $vfl[$field_name] = "$year-$val-$day";
          elseif ($field_name_part == 'year')
            $vfl[$field_name] = "$val-$month-$day";
        }
      elseif (isset($BF_USAGE_BY_NAME[$key]) || $key == 'comment')
        {
          $vfl[$key] = $val;
        }
      else
        {
          $k = print_r ($key, true);
          $v = print_r ($val, true);
          dbg("Rejected key = " . $k . " val = " . $v);
        }
    }
  return($vfl);
}

# Check whether a field was shown to the submitter
# (useful if a field is mandatory if shown to the submitter).
function trackers_check_is_shown_to_submitter ($field_name, $group_id,
                                               $submitter_id)
{
  if ($submitter_id == 100)
    {
      # Anonymous user.
      if (trackers_data_is_showed_on_add_nologin($field_name))
        return true;
    }
  else
    {
      if (!member_check($submitter_id, $group_id))
        {
          # Not a member of the group.
          if (trackers_data_is_showed_on_add($field_name))
            return true;
        }
      else
        {
          # Group member.
          if (trackers_data_is_showed_on_add_members($field_name))
            return true;
        }
    }
  # If we reach this point, it was not mandatory.
  return false;
}

# Check whether empty values are allowed for the bug fields
# field_array: associative array of field_name -> value.
function trackers_check_empty_fields($field_array, $new_item=true)
{
  unset($previous_form_bad_fields);
  global $previous_form_bad_fields;
  $previous_form_bad_fields = array();

  foreach ($field_array as $field_name => $val)
    {
      # Only the field percent_complete is allowed to use the special value
      # hundred.
      # FIXME: maybe it should not use that value at all, however it would
      # require one more database migration. Something that should indeed be
      # done if at some point we feel the need for one more exception.
      if ($field_name == "percent_complete")
        continue;

      # Check if it is empty.
      $is_empty = (trackers_data_is_select_box($field_name) ? ($val==100)
                                                            : ($val==''));
      if (!$is_empty)
        continue;

      # Check if it is mandatory.
      $mandatory_flag = trackers_data_mandatory_flag($field_name);
      $is_mandatory = false;
      if ($mandatory_flag == 1)
        {
          # Not mandatory.
          continue;
        }
      elseif ($mandatory_flag == 3)
        {
          # Mandatory whenever possible.
          $is_mandatory = 1;
        }
      elseif ($new_item)
        {
          # Mandatory when shown to the submitter while we are creating
          # a new item.
          # ($mandatory_flag = 0)
          $is_mandatory = 1;
        }
      else
        {
          # Mandatory when shown to the submitter, we are updating an item
          # ($mandatory_flag = 0).

          global $item_id, $group_id, $mandatorycheck_submitter_id;
          if (!$mandatorycheck_submitter_id)
            {
              # Save that information for further mandatory checks,
              # to avoid avoid a SQL request per field checked.
              $submitter_res = db_execute("SELECT submitted_by FROM ".ARTIFACT
                                          ." WHERE bug_id=? AND group_id=?",
                                          array($item_id, $group_id));
              $mandatorycheck_submitter_id = db_result($submitter_res,0,
                                                       'submitted_by');
            }

          if (trackers_check_is_shown_to_submitter($field_name, $group_id,
                                                   $mandatorycheck_submitter_id))
            {
              $is_mandatory = 1;
            }
        }

      if ($is_mandatory)
        {
          $value = trackers_data_get_label($field_name);
          $previous_form_bad_fields[$field_name] = $value;
        }
    }

  if (count($previous_form_bad_fields) <= 0)
    return true;
  # If not_new_item is true, it mean that there was no previous value to
  # reset the entry.
  if ($new_item)
    {
      if (count($previous_form_bad_fields) > 1)
        $msg = sprintf(
# TRANSLATORS: The argument is comma-separated list of field names.
_("These fields are mandatory: %s.
Fill them and re-submit the form."), join(', ',$previous_form_bad_fields));
      else
        $msg = sprintf(_("The field %s is mandatory.
Fill it and re-submit the form."), implode ($previous_form_bad_fields));
      fb($msg, 1);
    }
  else
    {
      if (count($previous_form_bad_fields) > 1)
        $msg = sprintf(
# TRANSLATORS: The argument is comma-separated list of field names.
_("These fields are mandatory: %s.
They have been reset to their previous value.
Check them and re-submit the form."), join(', ',$previous_form_bad_fields));
      else
        $msg = sprintf(_("The field %s is mandatory.
It has been reset to its previous value.
Check it and re-submit the form."), $previous_form_bad_fields);
      fb($msg, 1);
    }
  return false;
}

function trackers_canned_response_box ($group_id,$name='canned_response')
{
  if (!$group_id)
    {
      fb(_("Error, no group_id"),1);
      return 0;
    }
  $vals = array();
  $texts = array();
  $result = trackers_data_get_canned_responses($group_id);
  if (db_numrows($result) > 0)
    {
      if (db_numrows($result) > 1)
        {
          $vals[] = '!multiple!';
          $texts[] = "> "._("Multiple Canned Responses");
        }

      while ($entry = db_fetch_array($result))
        {
          $vals[] = $entry['bug_canned_id'];
          $texts[] = $entry['title'];

        }
      return html_build_select_box_from_arrays($vals, $texts ,$name,
                                               'xzxz',true,'None',false,'Any',
                                               false, _("Canned Responses"));
    }
  return form_input("hidden", "canned_response", "100")
           ._("No canned response available");
}

function trackers_build_notification_list($item_id, $group_id, $changes,
                                          $artifact=null)
{
  # Any person in the CC list and the assignee should be notified.
  #
  #   - unless this person is the one that made the update and does not
  #   want to be get notifications for his own work
  #   - unless this person wants to know only if the item is closed and the
  #   item is getting closed
  #   - unless this person wants to know only if the item status changed and
  #   the item status changed

  if ($artifact == null)
    $artifact = ARTIFACT;
  if (!ctype_alnum($artifact))
# TRANSLATORS: the argument is name of artifact (like bugs or patches).
    util_die(sprintf(_('Invalid artifact %s'),
                     '<em>' . htmlspecialchars($artifact) . '</em>'));

  $addresses = array();
  $addresses_to_skip = array();

  # The current user may not want receive CC for his own doings. Find if
  # if it is the case.
  $current_uid = user_getid();
  if (user_get_preference("notify_unless_im_author"))
    $addresses_to_skip[$current_uid] = true;

  # The current assignee will always be included (unless indeed if it is the
  # current user that does not want CC) no matter what: why would be
  # assignee if he is not interested in the item updates, for god sakes!
  # As this function is called after updated was handled, if the update
  # changed the assignee, the new assignee is the current assignee.
  # The previous assignee may or may not receive updates, if he update the
  # item (if so, he is in CC).
  $assignee_uid = db_result(db_execute("SELECT assigned_to from $artifact
                                       WHERE bug_id=?",
                                     array($item_id)),
                            0, 'assigned_to');
  # Assigned to 100 == unassigned.
  if ($assignee_uid != "100"
      && !array_key_exists($assignee_uid, $addresses_to_skip))
    $addresses[$assignee_uid] = true;

  # Now go through the CC list:
  # (automatically added CC will be in numerical
  # form and email = added_by).
  $result = db_execute("SELECT email,added_by FROM {$artifact}_cc
                        WHERE bug_id=? GROUP BY email LIMIT 150",
                       array($item_id));
  $rows = db_numrows($result);
  for ($i=0; $i < $rows; $i++)
    {
      $email = db_result($result, $i, 'email');
      $added_by = db_result($result, $i, 'added_by');

      # Remove extra white spaces.
      $email = trim($email);

      # The CC may have been added in the form like:
      #    THIS NAME <this@address.net>
      # So the validation check must be made only on the part in < >, if
      # it exists.
      if (preg_match("/\<([\w\d\-\@\.]*)\>/", $email, $realaddress))
        $email = $realaddress[1];

      # Ignore if in the to be ignored list or already caught
      # (do that now and later, here to
      # save time, later to makre sure we do not make dupes.
      # Ignore if already registered.
      if (array_key_exists($email, $addresses))
        continue;
      # Ignore if in the to be ignored list.
      if (array_key_exists($email, $addresses_to_skip))
        continue;

      if ($email == $added_by && ctype_digit($email))
        {
          # Here we have an integer as email address, it is likely to be a
          # CC automatically added.
          # (if an integer is passed by is not conform to added_by, we let
          # sendmail_mail() determine what to do with it).

          # Check if the users exists.
          if (!user_exists($email))
            continue;

          # Always ignore anonymous.
          if ($email == "100")
            continue;
        }

      # If we have a valid username, convert it to an uid.
      if (!ctype_digit($email) && user_getid($email))
        {
          # Since is is will be registered, we can ignore it in further check.
          $addresses_to_skip[$email] = true;
          $email = user_getid($email);
        }

      # If we have a string that contains @, try to find it in the database
      # and convert it to an uid if found.
      if (!ctype_digit($email)
          && strpos($email, "@"))
        {
          $res = db_execute("SELECT user_id FROM user
                             WHERE email=? LIMIT 1", array($email));
          if ($res != false and db_numrows($res) > 0)
            {
              $email_search = db_result($res, 0, 'user_id');

              if ($email_search)
                {
                  $addresses_to_skip[$email] = true;
                  $email = $email_search;
                }
            }
        }

      # Ignore if already registered.
      if (array_key_exists($email, $addresses))
        continue;
      # Ignore if in the to be ignored list.
      if (array_key_exists($email, $addresses_to_skip))
        continue;

      # Check specific users prefs, if we have a UID.
      if (ctype_digit($email))
         {
           $should_not_skip = false;

           # Do not want to be notified unless the item is closed
           # (first check values, then check prefs, as it requires an
           # an extra SQL select).

           $unless_closed = user_get_preference("notify_item_closed", $email);
           $unless_status_changed = user_get_preference("notify_item_statuschanged",
                                                        $email);

           if ((!$unless_closed && !$unless_status_changed))
             $should_not_skip = true;

           if ($unless_closed && isset($changes['status_id'])
               && $changes['status_id']['add-val'] == '3')
             $should_not_skip = true;

           if ($unless_status_changed && isset($changes['resolution_id']))
             $should_not_skip = true;

           if (!$should_not_skip)
             {
               $addresses_to_skip[$email] = true;
               continue;
             }
         }

      # If we get here, the address seem valid enough to let sendmail_mail()
      # deal with it.
      $addresses[$email] = true;
    }
  return (array_keys($addresses));
}

function trackers_mail_followup ($item_id,$more_addresses=false,$changes=false,
                                 $force_exclude_list=false, $artifact=0)
{
  global $sys_datefmt, $int_probablyspam;

  # If presumed to be a spam, no notifications.
  if ($int_probablyspam)
    {
      fb(_("Presumed spam: no mail will be sent"), 1);
      return false;
    }

  if (!$artifact)
    $artifact = ARTIFACT;

  if (!ctype_alnum($artifact))
# TRANSLATORS: the argument is name of artifact (like bugs or patches).
    util_die(sprintf(_('Invalid artifact %s'),
                     '<em>' . htmlspecialchars($artifact) . '</em>'));

  $result = db_execute("SELECT * from $artifact WHERE bug_id=?", array($item_id));
  $bug_href = "https://".$GLOBALS['sys_default_domain'].$GLOBALS['sys_home']
              ."$artifact/?$item_id";

  if ($result && db_numrows($result) <= 0)
    {
      fb(_("Could not send item update."), 0);
      return false;
    }
  $group_id = db_result($result,0,'group_id');

  unset($content_type);
  # CERN SPECIFIC (at least for now) BEGIN
  # Maybe later we ll implement a way to select mail templates, or prepared
  # mail format (like: text / html).
  # But it will have to be done in a well planned way that take into account
  # necessary cases and is not encumbered by very very specific things.
  # Until this happen, cern will use its own functions to deals with notif.
  # Indeed, this part will maintained and modified by CERN only.
  #
  # To ease maintainance, such specific things should usually not be added.
  # Please write to savane-dev if you intend to make such changes.
  # The upstream code cannot be cluttered by tons of things like that.
  # This is a one time exception, or almost, needed because this cannot
  # be directly merged in a generic way right now.
  if ($GLOBALS['sys_default_domain'] == "savannah.cern.ch"
      || !empty($GLOBALS['sys_debug_cerntest']))
    {
      $content_type = group_get_preference($group_id, "notif_content");
      if ($content_type == "")
        {
          # By default select maximum.
          $content_type = '2';
        }

      # Now, if the content type is 0, go on with Savane standard notif.
      # If it's something else, use trackers_mail_followup_cernspecifichack().
      if ($content_type > 0)
        {
          return trackers_mail_followup_cernspecifichack($group_id,$bug_href,
                                                         $result,$content_type,
                                                         $item_id,$more_addresses,
                                                         $changes,
                                                         $force_exclude_list);
        }
    }
  # CERN SPECIFIC (at least for now) END
  # Content of the mail must not be translated.
  $body = '';

  if ($changes)
    {
      $body = format_item_changes($changes, $item_id, $group_id)."\n";
    }
  else
    {
      $body .= "URL:\n  <".$bug_href.">\n\n";
      $body .= trackers_field_display('summary', $group_id,
                                      db_result($result,0,'summary'),false,
                                      true,true,true)."\n";
      $body .= sprintf("%25s", "Project:").' '.group_getname($group_id)."\n";
      $body .= trackers_field_display('submitted_by', $group_id,
                                      db_result($result,0,'submitted_by'),false,
                                      true,true,true)."\n";
      $body .= trackers_field_display('date', $group_id,
                                      db_result($result,0,'date'),false,true,
                                      true,true)."\n";
      # All other regular fields now
      $i=0;
      while ($field_name = trackers_list_all_fields())
        {
          # If the field is a special field or if not used by his project
          # then skip it. Otherwise print it in ASCII format.
          if (!trackers_data_is_special($field_name) &&
              trackers_data_is_used($field_name))
            {
              $body .= trackers_field_display($field_name,
                                              $group_id,
                                              db_result($result,0,$field_name),
                                              false,
                                              true,
                                              true,
                                              true);
              $i++;
              $body .= "\n";
            }
        }
      $body .= "\n";

      # Now display other special fields
      $body .=
"    _______________________________________________________

Details:\n".trackers_field_display('details',
                                   $group_id,
                                   db_result($result,0,'details'),
                                   true,true,true,true);

      # Then output the history of bug details from newest to oldest.
      $body .= "\n\n".format_item_details($item_id, $group_id, true);

      # Then output the history of bug details from newest to oldest.
      $body .= "\n\n".format_item_attached_files($item_id, $group_id, true);
    }

  # Finally output the message trailer.
  $body .= "\n    _______________________________________________________\n\n";
  $body .= "Reply to this item at:";
  $body .= "\n\n  <".$bug_href.">";

  # See who is going to receive the notification.
  # Plus append any other email given at the end of the list.
  $arr_addresses = trackers_build_notification_list($item_id,$group_id,
                                                    $changes,$artifact);
  $to = join(',',$arr_addresses);
  $from = user_getrealname(0,1).' <'.$GLOBALS['sys_mail_replyto'].'@'
          .$GLOBALS['sys_mail_domain'].'>';
  $subject = utils_unconvert_htmlspecialchars(db_result($result,0,'summary'));

  if ($more_addresses)
    {
      $to .= ($to ? ',':'').$more_addresses;
    }

  # If the item is private, take into account the exclude-list.
  $exclude_list = '';
  if (db_result($result,0,'privacy') == '2')
    {
      $exclude_list = db_result(db_execute("SELECT ".$artifact
                                           ."_private_exclude_address
                                            FROM groups WHERE group_id=?",
                                           array($group_id)),
                                0, $artifact."_private_exclude_address");

    }

  # Disallow mail notification for an address, private or not.
  if ($force_exclude_list)
    {
      if ($exclude_list)
        $exclude_list .= ",".$force_exclude_list;
      else
        $exclude_list = $force_exclude_list;
    }

  # Necessary to mention the comment id (for delayed mails).
  if ($GLOBALS['int_delayspamcheck_comment_id'])
    $item_id .= ":".$GLOBALS['int_delayspamcheck_comment_id'];

  sendmail_mail($from, $to, $subject, $body, group_getunixname($group_id),
                $artifact, $item_id, 0, 0, $exclude_list);
}

# Wrapper for trackers_attach_file that will find out if one or more files
# were attached.
function trackers_attach_several_files($item_id, $group_id, &$changes)
{
  # Reset the global used to count the current upload size.
  $GLOBALS['current_upload_size'] = 0;

  $changed = false;
  $comment = '';

  $filenames = array();
  for ($i = 1; $i < 5; $i++)
    $filenames[] = "input_file$i";
  $files = sane_import('files', $filenames);
  extract(sane_import('post', array('file_description')));
  foreach ($files as $file)
    {
      if ($file['error'] != UPLOAD_ERR_OK)
        continue;

      $file_id = trackers_attach_file($item_id,
                                      $group_id,
                                      $file['tmp_name'],
                                      $file['name'],
                                      $file['type'],
                                      $file['size'],
                                      $file_description,
                                      $changes);
      if ($file_id)
        {
          $comment .= "file #$file_id, ";
          $changes['attach'][] = array('name' => $file['name'],
                                       'size' => $file['size'],
                                       'id' => $file_id);
        }
    }

  if ($comment)
    {
      $changed = true;
      $comment = "\n\n(".rtrim($comment, ", ").")";
    }

  # Trash the used global.
  unset($GLOBALS['current_upload_size']);
  return array($changed, $comment);
}

function trackers_attach_file($item_id,
                              $group_id,
                              $input_file,
                              $input_file_name, # 4
                              $input_file_type,
                              $input_file_size, # 6
                              $file_description,
                              &$changes)
{
  global $sys_trackers_attachments_dir;

  $input_file_name = preg_replace ('/[&<\s"\';?!*]/', '@', $input_file_name);

  $user_id = (user_isloggedin() ? user_getid(): 100);

  if (!is_writable($sys_trackers_attachments_dir))
    {
      fb(sprintf(_("The upload directory '%s' is not writable."),
                 $sys_trackers_attachments_dir), 1);
      return false;
    }
  if (!is_uploaded_file($input_file))
    {
      fb(sprintf(_("File %s not attached: unable to open it"),
                 $input_file_name), 1);
      return false;
    }

  # Found of the previous upload count.
  # It could not be inferior to 0. If it is, someone obviously find a way
  # to tamper, ignore the file.
  $current_upload_size = $GLOBALS['current_upload_size'];
  $current_upload_size_comment = '';
  if ($current_upload_size < 0)
    {
# TRANSLATORS: the argument is file name.
      fb(sprintf(_("Unexpected error, disregarding file %s attachment"),
                 $input_file_name), 1);
      return false;
    }
  if ($current_upload_size > 0)
    {
      # Explanation added when an upload is refused, if the upload count
      # is involved.
      $current_upload_size_comment = ' '
       .sprintf (ngettext("You already uploaded %s kilobyte.",
                          "You already uploaded %s kilobytes.",
                          $current_upload_size),
                 $current_upload_size);
    }


  # Check file size.
  # Note: in english, use the expression kilobytes, and not kB, because
  # feedback is in lowercase for the whole string.
  # We always add the current upload count.
  $filesize = round(filesize($input_file) / 1024);
  $uploadsize = $filesize + $current_upload_size;
  if ($uploadsize > $GLOBALS['sys_upload_max'])
    {
      fb(sprintf(ngettext("File %s not attached: its size is %s kilobyte.",
                          "File %s not attached: its size is %s kilobytes.",
                          $filesize), $input_file_name, $filesize).' '
          .sprintf(ngettext("Maximum allowed file size is %s kilobyte,
after escaping characters as required.",
                            "Maximum allowed file size is %s kilobytes,
after escaping characters as required.",
                            $GLOBALS['sys_upload_max']),
                   $GLOBALS['sys_upload_max']).$current_upload_size_comment, 1);
      return false;
    }
  if (filesize($input_file) == 0)
    {
      fb(sprintf(_("File %s is empty."), $input_file_name)
         .$current_upload_size_comment, 1);
      return false;
    }

  # Update the upload count value (before the actual database insert, safer).
  $GLOBALS['current_upload_size'] = $uploadsize;

  $res = db_autoexecute('trackers_file',
    array(
      'item_id' => $item_id,
      'artifact' => ARTIFACT,
      'submitted_by' => $user_id,
      'date' => time(),
      'description' => htmlspecialchars($file_description),
      'filename' => $input_file_name,
      'filesize' => $input_file_size,
      'filetype' => $input_file_type
    ), DB_AUTOQUERY_INSERT);

  if (!$res)
    {
      fb(sprintf(_("Error while attaching file %s"), $input_file_name), 1);
      return false;
    }
  $file_id = db_insertid($res);
  if (!move_uploaded_file($input_file, "$sys_trackers_attachments_dir/$file_id"))
    {
      fb(sprintf(_("Error while saving file %s on disk"), $input_file_name), 1);
      return false;
    }

  $file_id = db_insertid($res);
# TRANSLATORS: the argument is file id (a number).
  fb(sprintf(_("file #%s attached"), $file_id));

  trackers_data_add_history("Attached File",
                            "-",
                            "Added ".$input_file_name.", #".$file_id,
                            $item_id,
                            0,0,1);
  # Add the guy in CC.
  if (user_isloggedin() &&
      !user_get_preference("skipcc_updateitem"))
    {
      trackers_add_cc($item_id,
                      $group_id,
                      user_getid(),
                      "-UPD-");
     # Use a flag as comment, because if we
     # translate the string now, people will get
     # the translation of the submitter when they
     # read the item, not necessarily the one they
     # want.
    }
  return $file_id;
}

function trackers_exist_cc($item_id,$cc)
{
  $res = db_execute("SELECT bug_cc_id FROM ".ARTIFACT."_cc WHERE bug_id=?
                    AND email=?",
                    array($item_id, $cc));
  return (db_numrows($res) >= 1);
}

function trackers_insert_cc($item_id,$cc,$added_by,$comment,$date)
{
  $res = db_autoexecute(ARTIFACT."_cc",
    array(
      'bug_id' => $item_id,
      'email' => $cc,
      'added_by' => $added_by,
      'comment' => htmlspecialchars($comment),
      'date' => $date
    ), DB_AUTOQUERY_INSERT);

  # Store the change in history only if the CC was a manual add, not a direct
  # effect of another action.
  if ($comment != "-SUB-"
      && $comment != "-UPD-"
      && $comment != "-COM-")
    {
      trackers_data_add_history("Carbon-Copy",
                                "-",
                                "Added ".$cc,
                                $item_id,
                                0,0,1);
    }
  return ($res);
}

function trackers_add_cc($item_id,$group_id,$email,$comment)
{
  global $feedback,$ffeedback;

  $user_id = (user_isloggedin() ? user_getid(): 100);

  $arr_email = utils_split_emails($email);
  $date = time();
  $ok = true;
  $changed = false;

  foreach ($arr_email as $cc)
    {
      # Add this cc only if not there already.
      if (!trackers_exist_cc($item_id,$cc))
        {
          $changed = true;
          $res = trackers_insert_cc($item_id,$cc,$user_id,$comment,$date);
          if (!$res)
            $ok = false;
        }
    }

  if (!$ok)
    {
      fb(_("CC addition failed."), 0);
    }
  else
    {
      if ($changed)
        fb(_("CC added."));
    }
  return $ok;
}

function trackers_delete_cc($group_id=false,$item_id=false,$item_cc_id=false)
{
  global $feedback,$ffeedback;

  # Extract data about the CC.
  $res1 = db_execute("SELECT * from ".ARTIFACT."_cc WHERE bug_cc_id=?",
                     array($item_cc_id));
  if (!db_numrows($res1))
    {
      # No result? Stop here silently (assume that someone tried to remove
      # an already removed CC).
      return false;
    }

  # If both bug_id and bug_cc_id are given make sure the cc belongs
  # to this bug (it is a bit paranoid but...)
  if ($item_id)
    {
      if (db_result($res1,0,'bug_id') != $item_id)
        {
          # No feedback, too weird case, probably malicious.
          return false;
        }
    }

  # If group id was passed, do checks on users privileges.
  if ($group_id)
    {
      $email = db_result($res1,0,'email');
      $added_by = db_result($res1,0,'added_by');
      $user_id = user_getid();

     # Remove if
     # - current user is a tracker manager
     # - the CC name is the current user
     # - the CC email address matches the one of the current user
     # - the current user is the person who added a given name in CC list
      if (!member_check(0,$group_id,member_create_tracker_flag(ARTIFACT).'2'))
        {
          if ($user_id != $email
              && user_getname($user_id) != $email
              && user_getemail($user_id) != $email
              && $user_id != $added_by)
            {
              fb(_("Removing CC is not allowed"), 1);
              return false;
            }
        }
    }

  # Now delete the CC address.
  $res2 = db_execute("DELETE FROM ".ARTIFACT."_cc WHERE bug_cc_id=?",
                     array($item_cc_id));
  if (!$res2)
    {
      fb(_("Failed to remove CC"), 1);
      return false;
    }
  fb(_("CC Removed"));
  trackers_data_add_history("Carbon-Copy",
                            "Removed ".db_result($res1, 0, 'email'),
                            "-",
                            $item_id,
                            0,0,1);
  return true;
}

# Remove the uid from an item CC list.
function trackers_delete_cc_by_user ($item_id, $user_id)
{
  # An user may be in CC of an item in different ways
  #  - as uid
  #  - as username
  #  - as email
  # We will try them all, to make sure the user is properly removed from CC.

  if (!user_exists($user_id))
    return false;

  $result = db_execute("DELETE  FROM ".ARTIFACT."_cc WHERE bug_id=?
                        AND (email=? OR email=? OR email=?)",
                       array($item_id, $user_id, user_getname($user_id),
                             user_getemail($user_id)));

  # Return the success or failure.
  return (db_numrows($result) >= 1);
}

function trackers_delete_dependancy ($group_id, $item_id, $item_depends_on,
                                     $item_depends_on_artifact, &$changes)
{
  # Can be done only by at least technicians.
  # Note that is it possible to fake the system by providing a false group_id.
  # But well, consequences would be small an it will be easy to identify
  # the criminal.

  if (member_check(0,$group_id, member_create_tracker_flag(ARTIFACT).'1'))
    {
      $result = db_execute("DELETE FROM ".ARTIFACT."_dependencies WHERE item_id=?
                            AND is_dependent_on_item_id=?
                            AND is_dependent_on_item_id_artifact=?",
                           array($item_id, $item_depends_on,
                                 $item_depends_on_artifact));
    }

  if (!$result)
    {
      fb(_("Failed to delete dependency.").db_error($result), 0);
      return false;
    }
  fb(_("Dependency Removed."));
  trackers_data_add_history("Dependencies",
                            "Removed dependency to "
                            .$item_depends_on_artifact." #"
                            .$item_depends_on,
                            "-",
                            $item_id,
                            0,0,1);
  trackers_data_add_history("Dependencies",
                            "Removed dependency from ".ARTIFACT." #".$item_id,
                            "-",
                            $item_depends_on,
                            0,0,1);

  $changes['Dependency Removed']['add'] = $item_depends_on_artifact." #"
                                          .$item_depends_on;
  return true;
}

# The ANY value is 0. The simple fact that
# ANY (0) is one of the value means it is Any even if there are
# other non zero values in the array.
function trackers_isvarany($var)
{
  if (!is_array($var))
    return ($var == 0);
  foreach ($var as $v)
    {
      if ($v == 0)
        return true;
    }
  return false;
}

# Check is a sort criteria is already in the list of comma
# separated criterias. If so invert the sort order, if not then
# simply add it.
function trackers_add_sort_criteria($criteria_list, $order, $msort)
{
  $found = false;
  if ($criteria_list)
    {
      $arr = explode(',',$criteria_list);
      $i = 0;
      foreach ($arr as $attr)
        {
          preg_match("/\s*([^<>]*)([<>]*)/", $attr,$match);
          list(,$mattr,$mdir) = $match;
          if ($mattr == $order)
            {
              if ( ($mdir == '>') || (!isset($mdir)) )
                {
                  $arr[$i] = $order.'<';
                }
              else
                $arr[$i] = $order.'>';
              $found = true;
            }
          $i++;
        }
    }

  if (!$found)
    {
      if (!$msort)
        unset($arr);
      if ( ($order == 'severity') || ($order == 'hours')
          || (trackers_data_is_date_field($order)) )
        {
          # Severity, effort and dates sorted in descending order by default.
          $arr[] = $order.'<';
        }
      else
        {
          $arr[] = $order.'>';
        }
    }
  return(join(',', $arr));
}

# Transform criteria list to SQL query (+ means ascending
# - is descending).
function trackers_criteria_list_to_query($criteria_list)
{
  $criteria = preg_split('/,/', $criteria_list);
  $criteria_filtered = array();
  foreach ($criteria as $cr)
    {
      if (preg_match('/^[a-z_]+[<>]?$/i', $cr))
        $criteria_filtered[] = $cr;
    }
  $criteria_list = join(',', $criteria_filtered);
  $criteria_list = str_replace('>',' ASC',$criteria_list);
  $criteria_list = str_replace('<',' DESC',$criteria_list);
  # Undo the uid->user_name trick to avoid "Column 'submitted_by' in
  # order clause is ambiguous" error. This is pretty ugly. Also check
  # trackers_data_is_username_field().
  $criteria_list = str_replace('submitted_by ','user_submitted_by.user_name ',
                               $criteria_list);
  $criteria_list = str_replace('assigned_to ','user_assigned_to.user_name ',
                               $criteria_list);
  return $criteria_list;
}

# Return image name and alt text for sorting order.
function trackers_sorting_order ($crit)
{
  if (substr($crit, -1) == '>')
    return array('image' => 'down',
              #TRANSLATORS: this string specifies sorting order.
                 'text' => _('down'));
  return array('image' => 'up',
            #TRANSLATORS: this string specifies sorting order.
               'text' => _('up'));
}

# Transform criteria list to readable text statement.
# $url must not contain the morder parameter.
function trackers_criteria_list_to_text($criteria_list, $url)
{
  if ($criteria_list)
    {
      $arr = explode(',',$criteria_list);
      $morder = '';

      foreach ($arr as $crit)
        {
          $morder .= ($morder ? ",".$crit : $crit);
          $attr = str_replace('>','',$crit);
          $attr = str_replace('<','',$attr);
          $morder = htmlspecialchars($morder);
          $so = trackers_sorting_order ($crit);
          $arr_text[] = '<a href="'.$url.'&amp;morder='.$morder.'#results">'
             .trackers_data_get_label($attr).'</a><img class="icon" src="'
             .$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/'
             .$so['image'].'.png" border="0" alt="'.$so['text'].'" />';
        }
    }
  return join(' &gt; ',$arr_text);
}

function trackers_build_match_expression($field, &$to_match)
{
  # First get the field type.
  $res = db_execute("SHOW COLUMNS FROM ".ARTIFACT." LIKE ?", array($field));
  $type = db_result($res,0,'Type');

  $expr = '';
  $params = array();

  if (preg_match('/text|varchar|blob/i', $type))
    {
      # If it is sourrounded by /.../ the assume a regexp
      # else transform into a series of LIKE %word%.
      if (preg_match('/\/(.*)\#/', $to_match, $matches))
        {
          $expr = "$field RLIKE ? ";
          $params[] = $matches[1];
        }
      else
        {
          $words = preg_split('/\s+/', $to_match);
          reset($words);

          foreach ($words as $l => $w)
            {
              $words[$i] = "$field LIKE ?";
              $params[] = "%$w%";
            }
          $expr = join(' AND ', $words);
        }
    }
  else if (preg_match('/int/i', $type))
    {
      # If it is sourrounded by /.../ then assume a regexp
      # else assume an equality.
      if (preg_match('/\/(.*)\#/', $to_match, $matches))
        {
          $expr = "$field RLIKE ? ";
          $params[] = $matches[1];
        }
      else
        {
          $int_reg = '[+\-]*[0-9]+';
          if (preg_match("/\s*(<|>|>=|<=)\s*($int_reg)/", $to_match, $matches))
            {
              # It's < or >,  = and a number then use as is.
              $matches[2] = (string)((int)$matches[2]);
              $expr = "$field ".$matches[1]." ? ";
              $params[] = $matches[2];
              $to_match = $matches[1].' '.$matches[2];
            }
          else if (preg_match("/\s*($int_reg)\s*-\s*($int_reg)/", $to_match,
                   $matches))
            {
              # It's a range number1-number2.
              $matches[1] = (string)((int)$matches[1]);
              $matches[2] = (string)((int)$matches[2]);
              $expr = "$field >= ? AND $field <= ? ";
              $params[] = $matches[1];
              $params[] = $matches[2];
              $to_match = $matches[1].'-'.$matches[2];
            }
          else if (preg_match("/\s*($int_reg)/", $to_match, $matches))
            {
              # It's a number so use equality.
              $matches[1] = (string)((int)$matches[1]);
              $expr = "$field = ? ";
              $params[] = $matches[1];
              $to_match = $matches[1];
            }
          else
            {
              # Invalid syntax - no condition.
              $expr = '1';
              $to_match = '';
            }
        }
    }
  else if (preg_match('/float/i', $type))
    {
      # If it is sourrounded by /.../ the assume a regexp
      # else assume an equality.
      if (preg_match('/\/(.*)\#', $to_match, $matches))
        {
          $expr = "$field RLIKE ? ";
          $params[] = $matches[1];
        }
      else
        {
          $flt_reg = '[+\-0-9.eE]+';

          if (preg_match("/\s*(<|>|>=|<=)\s*($flt_reg)/", $to_match, $matches))
            {
              # It's < or >,  = and a number then use as is.
              $matches[2] = (string)((float)$matches[2]);
              $expr = "$field ".$matches[1]." ? ";
              $params[] = $matches[2];
              $to_match = $matches[1].' '.$matches[2];
            }
          else if (preg_match("/\s*($flt_reg)\s*-\s*($flt_reg)/", $to_match,
                              $matches))
            {
              # It's a range number1-number2.
              $matches[1] = (string)((float)$matches[1]);
              $matches[2] = (string)((float)$matches[2]);
              $expr = "$field >= ? AND $field <= $matches[2] ";
              $params[] = $matches[1];
              $params[] = $matches[2];
              $to_match = $matches[1].'-'.$matches[2];
            }
          else if (preg_match("/\s*($flt_reg)/", $to_match, $matches))
            {
              # It's a number so use  equality.
              $matches[1] = (string)((float)$matches[1]);
              $expr = "$field = ? ";
              $params[] = $matches[1];
              $to_match = $matches[1];
            }
          else
            {
              # Invalid syntax - no condition.
              $expr = '1';
              $to_match = '';
            }
        }
    }
  else
    {
      # All the rest (???) use =.
      $expr = "$field = ?";
      $params[] = $to_match;
    }
  $expr = ' ('.$expr.') ';
  return array($expr, $params);
}

# The function moved to data.
function trackers_delete_file($group_id=false,$item_id=false,$item_file_id=false)
{
  return trackers_data_delete_file($group_id, $item_id, $item_file_id);
}

# Register a msg id for an item update notification.
function trackers_register_msgid ($msgid, $artifact, $item_id)
{
  return db_affected_rows(db_autoexecute("trackers_msgid",
    array(
      'msg_id' => $msgid,
      'artifact' => $artifact,
      'item_id' => $item_id
    ), DB_AUTOQUERY_INSERT));
}

# Get a list, separated  a msg id for an item update notification.
function trackers_get_msgid ($artifact, $item_id, $latest="")
{
  if ($latest)
    $latest = "ORDER BY id DESC LIMIT 1";

  $result = db_execute("SELECT msg_id FROM trackers_msgid WHERE artifact=?
                        AND item_id=? $latest",
                       array($artifact, $item_id));
  $list = '';
  while ($id = db_fetch_array($result))
    {
      if (isset($list))
        $list .= " ";
      $list .= "<".$id['msg_id'].">";
    }
  return $list;
}

############## NASTY HACK

  # CERN SPECIFIC (at least for now) BEGIN '
  # Maybe later we ll implement a way to select mail templates, or prepared
  # mail format (like: text / html).
  # But it will have to be done in a well planned way that take into account
  # necessary cases and is not encumbered by very very specific things.
  # Until this happen, cern will use its own functions to deals with notif.
  # Indeed, this part will maintained and modified by CERN only.
  #
  # To ease maintainance, such specific things should usually not be added.
  # Please write to savane-dev if you intend to make such changes.
  # The upstream code cannot be cluttered by tons of things like that.
  # This is a one time exception, or almost, needed because this cannot
  # be directly merged in a generic way right now.
function trackers_mail_followup_cernspecifichack ($group_id, $bug_href,
   $result,$content_type, $item_id, $more_addresses=false, $changes=false,
   $force_exclude_list=false)
{
  global $sys_datefmt;

  # MUST BE DEFINED HERE, dont ask me why.
  $subject = utils_unconvert_htmlspecialchars(db_result($result,0,'summary'));

  if ($content_type == '2')
    {   # for now means CERN present format
                               # (last + overview + all followups)

      $body = "This is an automated notification sent by "
              .$GLOBALS['sys_name']. ".
It relates to:\n\t\t".ARTIFACT." #".$item_id.", project "
              .group_getname($group_id)."\n";
      if ($changes)
        {
          $body .=
"\n==============================================================================
 LATEST MODIFICATIONS of ".ARTIFACT." #".$item_id.":
==============================================================================\n\n";

          ### format_item_changes of savane 1.0.6
          # FIXME: strange, with %25s it does not behave exactly like
          # trackers_field_label_display.
          $fmt = "%24s: %23s => %-23s\n";

          $separator = "\n    _______________________________________________________\n\n";

          # Process most of the fields.
          reset($changes);

          foreach ($changes as $field => $h)
            {
              # If both removed and added items are empty skip - Sanity check.
              if (!$h['del'] && !$h['add'])
                continue;

              if ($field == "details" || $field == "attach")
                continue;

              $label = trackers_data_get_label($field);
              if (!$label)
                $label = $field;
              $out .= sprintf($fmt, $label, $h['del'],$h['add']);
            }
          if ($out)
            {
              $out = "Update of ".utils_get_tracker_prefix(ARTIFACT)." #"
                     .$item_id
                     ." (project ".group_getunixname($group_id)."):\n\n".$out;
            }
          # Process special cases: follow-up comments.
          if ($changes['details'])
            {
              if ($out)
                $out .= $separator;

              $out_com = "Follow-up Comment #"
                         .db_numrows(trackers_data_get_followups($item_id));
              if (!$out)
                {
                  $out_com .= ", ".utils_get_tracker_prefix(ARTIFACT)." #"
                              .$item_id
                              ." (project ".group_getunixname($group_id).")";
                }

              $out_com .= ":\n\n";
              if ($changes['details']['type'] != 'None'
                  && $changes['details']['type'] != '(Error - Not Found)')
                {
                  $out_com .= '['.$changes['details']['type']."]\n";
                }
              $out_com .=
                utils_unconvert_htmlspecialchars($changes['details']['add']);
              unset($changes['details']);

              $out .= $out_com;
            }

          # Process special cases: file attachment.
          if ($changes['attach'])
            {
              if ($out)
                $out .= $separator;

              $out_att = "Additional Item Attachment";
              if (!$out)
                {
                  $out_att .= ", ".utils_get_tracker_prefix(ARTIFACT)." #"
                              .$item_id
                              ." (project ".group_getunixname($group_id).")";
                }
              $out_att .= ":\n\n";
              $out_att .= sprintf("File name: %-30s Size:%d KB\n",
                                  $changes['attach']['name'],
                                  intval($changes['attach']['size']/1024) );
              $out_att .= $changes['attach']['description']."\n".'<'
                          .$changes['attach']['href'].'>';
              unset($changes['attach']);

              $out .= $out_att;
            }

          $body .= $out;
          ### format_item_changes of savane 1.0.6

          $body .= "\n";
        }

      $body .=
"\n==============================================================================
 OVERVIEW of ".ARTIFACT." #".$item_id.":
==============================================================================\n
URL:\n  <".$bug_href.">\n\n";
      $body .= trackers_field_display('summary', $group_id,
                                      db_result($result,0,'summary'),
                                      false,true,true,true)."\n";
      $body .= sprintf("%25s", "Project:").' '.group_getname($group_id)."\n";
      $body .= trackers_field_display('submitted_by', $group_id,
                                      db_result($result,0,'submitted_by'),
                                      false,true,true,true)."\n";
      $body .= trackers_field_display('date', $group_id,
                                       db_result($result,0,'date'),
                                      false,true,true,true)."\n";

      # All other regular fields now
      $i=0;
      while ($field_name = trackers_list_all_fields())
        {
          # If the field is a special field or if not used by his project
          # then skip it. Otherwise print it in ASCII format.
          if (!trackers_data_is_special($field_name)
              && trackers_data_is_used($field_name))
            {

              $body .= trackers_field_display($field_name,
                                              $group_id,
                                              db_result($result,0,$field_name),
                                              false,
                                              true,
                                              true,
                                              true);
              $i++;
              $body .= "\n";
            }
        }
      $body .= "\n";

      # Now display other special fields.
      $body .= "    _______________________________________________________\n\n"
               .trackers_field_display('details',
                                       $group_id,
                                       db_result($result,0,'details'),
                                       true,true,true,true);
      # Then output the history of bug details from newest to oldest.
      $body .= "\n\n";
      # format_item_details($item_id, $group_id, true); of savane 1.0.6
      $result=trackers_data_get_followups($item_id);
      $rows=db_numrows($result);

      # No followup comment -> return now.
      if ($rows > 0)
        {
          unset($out);
          $out .= "    _______________________________________________________\n
Follow-up Comments:\n\n";

          # Loop throuh the follow-up comments and format them.
          for ($i=$rows-1; $i >= 0; $i--)
            {
              $comment_type = db_result($result, $i, 'comment_type');
              if ($comment_type == 'None')
                $comment_type = '';
              else
                $comment_type = '['.$comment_type.']';

              $fmt = "\n-------------------------------------------------------\n"
                 ."Date: %-30sBy: %s\n";
              if ($comment_type)
                $fmt .= "%s\n%s";
              else
                $fmt .= "%s%s";
              $fmt .= "\n";
              # I wish we had sprintf argument swapping in PHP3 but
              # we dont so do it the ugly way...

              if (db_result($result, $i, 'realname'))
                {
                  $name = db_result($result, $i, 'realname')." <"
                          .db_result($result, $i, 'user_name').">";
                }
              else
                $name = "Anonymous"; # must no be translated, part of mails notifs
              $out .= sprintf($fmt,
                              utils_format_date(db_result($result, $i, 'date')),
                              $name,
                              $comment_type,
                              utils_unconvert_htmlspecialchars(
                                db_result($result, $i, 'old_value'))
                              );

            }
           $out .=  "\n\n\n";
           $body .= $out;
        }
      # format_item_details($item_id, $group_id, true); of savane 1.0.6 end

      # Then output the CC list.
      $body .= "\n\n";
      # format_item_cc_list($item_id, $group_id, true); of savane 1.0.6
      $result=trackers_data_get_cc_list($item_id);
      $rows=db_numrows($result);

      # No file attached -> return now.
      if ($rows > 0)
        {
          unset($out);
          $out .=
"    _______________________________________________________\n\n"
                  ."Carbon-Copy List:\n\n";
          $fmt = "%-35s | %s\n";
          $out .= sprintf($fmt, 'CC Address', 'Comment');
          $out .=
"------------------------------------+-----------------------------\n";

          # Loop through the cc and format them.
          for ($i=0; $i < $rows; $i++)
            {
              $email = db_result($result, $i, 'email');
              $item_cc_id = db_result($result, $i, 'bug_cc_id');

              # If the CC is a user point to its user page.
              # Do not build mailto, we do not need to help spammers.
              $res_username = user_get_result_set_from_unix($email);
              if ($res_username && (db_numrows($res_username) == 1))
                $href_cc = utils_user_link($email);
              else
                $href_cc = $email;

              $out .= sprintf($fmt, $email, db_result($result, $i, 'comment'));
            }
          $out .= "\n";
          $body .= $out;
        }
      # format_item_cc_list($item_id, $group_id, true); of savane 1.0.6 end

      # Then output the history of bug details from newest to oldest.
      $body .= "\n\n";
      # format_item_attached_files of savane 1.0.6
      $result=trackers_data_get_attached_files($item_id);
      $rows=db_numrows($result);

      # No file attached -> return now.
      if ($rows > 0)
        {
          unset($out);
          $out .=
"    _______________________________________________________\n
File Attachments:\n\n";
          $fmt = "\n-------------------------------------------------------\n"
          ."Date: %s  Name: %s  Size: %s   By: %s\n%s\n%s";
          # Loop throuh the attached files and format them.

          for ($i=0; $i < $rows; $i++)
            {
              $item_file_id = db_result($result, $i, 'file_id');
              $href = $GLOBALS['sys_home'] . ARTIFACT
                      . "/download.php?file_id=$item_file_id";

              $out .= sprintf($fmt,
                              utils_format_date(db_result($result, $i, 'date')),
                              db_result($result, $i, 'filename'),
                              utils_filesize(0, intval(db_result($result, $i,
                                                             'filesize'))),
                              db_result($result, $i, 'user_name'),
                              db_result($result, $i, 'description'),
                              '<https://' . $GLOBALS['sys_default_domain']
                              . utils_unconvert_htmlspecialchars($href) . '>');
            }
          $out .= "\n";
          $body .= $out;
        }
      # format_item_attached_files of savane 1.0.6 end
      # Finally output the message trailer.
      $body .=
"\n==============================================================================

This item URL is:";
      $body .= "\n  <".$bug_href.">";
    }

  if ($content_type == '1')
    {   # For now means ROOT wishes (UGLY ... I know!).
      $body = "";
      $was_new_item = false;

      if (user_isloggedin())
        $body .= "Posted by: ".user_getrealname($user_id).' <'
                 .user_getname($user_id).">";
      else
        $body .= "Posted by an anonymous user";
      $body .= "\n";
      $body .= "Related to: [".group_getname($group_id)." ".ARTIFACT." #".$item_id
               ."] "
               .utils_unconvert_htmlspecialchars(db_result($result,0,'summary'))
               ."\n";
      $body .= "URL: <".$bug_href.">\n\n";

      if ($changes)
        {
          #Process special cases first: follow-up comment.
          $fmt = "%s: %23s -> %-23s\n";
          $was_followup = false;
          if ($changes['details'])
            {
              $body .= "Follow-up Comment:\n\n";
              if ($changes['details']['type'] != 'None'
                  && $changes['details']['type'] != '(Error - Not Found)')
                {
                  $body .= '['.$changes['details']['type']."]\n";
                }
              $body .=
                utils_unconvert_htmlspecialchars($changes['details']['add']);
              $body .= "\n";
              unset($changes['details']);
              # Set flag to skip output of this followup comment at the end.
              $was_followup = true;
            }
        }
      else
        {
          # If new submission start with description.
          $body .= trackers_field_display('details',
                                           $group_id,
                                           db_result($result,0,'details'),
                                           true,true,true,true);
          $body .= "\n";
          # Set flag to skip output of original submission at the end.
          $was_new_item = true;
        }

      if ($changes)
        {
          # Process special cases first: bug file attachment.
          if ($changes['attach'])
            {
              $body .= sprintf("Attachment of file: %s   Size:%d KB  ",
                         $changes['attach']['name'],
                         intval($changes['attach']['size']/1024) );
              $body .= $changes['attach']['description']."\n".'<'
                       .$changes['attach']['href'].'>';
              unset($changes['attach']);
              $body .= "\n";
            }

          # All the rest of the fields now.
          reset($changes);
          if (count($changes))
            {
              $body .= "\n";

              foreach ($changes as $field => $h)
                {
                  # If both removed and added items are empty skip - Sanity check.
                  if (!$h['del'] && !$h['add'])
                    continue;
                  $label = trackers_data_get_label($field);
                  if (!$label)
                   $label = $field;
                  $off = sprintf("%d", 23-strlen($label)-2);
                  $fmt = "%s: %".$off."s -> %-23s\n";
                  $body .= sprintf($fmt, $label, $h['del'],$h['add']);
                }
            }
          else
            $body .= "\n";
          $body .= "\n";
        }
      else
        $body .= "\n";

      $body .= trackers_field_display('submitted_by', $group_id,
                                      db_result($result,0,'submitted_by'),
                                     false,true,true,true,false,'',false,'',false,
                                     false,-3)."\n";

      # All other regular fields now.
      $i=0;
      while ($field_name = trackers_list_all_fields())
        {
          # If the field is a special field or if not used by his project
          # then skip it. Otherwise print it in ASCII format.
          if (!trackers_data_is_special($field_name)
              && trackers_data_is_used($field_name))
            {
              $body .= trackers_field_display($field_name, $group_id,
                                              db_result($result,0,$field_name),
                                              false, true, true, true, false,
                                              '',false,'',false,false,-3);
              $i++;
              $body .= "\n";
            }
        }

      # Then output the history of bug details from newest to oldest.
      $fu_result=trackers_data_get_followups($item_id);
      $fu_rows=db_numrows($fu_result);
      if ($fu_rows > 0)
        {
          # Loop throuh the follow-up comments and format them.
          $fmt = "\n-----Reply from %s on %s-----\n%s\n";
          for ($i=$fu_rows-1; $i >= 0; $i--)
            {
              # Prevent output of most recent if already shown.
              if ($was_followup && ($i == $fu_rows-1))
                continue;
              if (db_result($fu_result, $i, 'realname'))
                $name = db_result($fu_result, $i, 'realname')." <"
                        .db_result($fu_result, $i, 'user_name').">";
              else
                $name = "Anonymous"; # Must no be translated, part of mails notifs.
              if (user_get_timezone())
                $tz = ' ('.user_get_timezone().')';
              else
                $tz = '';
              $body .= sprintf($fmt, $name,
                               utils_format_date(db_result($fu_result,
                                                           $i, 'date')).$tz,
                               utils_unconvert_htmlspecialchars(
                                 db_result($fu_result, $i, 'old_value'))
                              );
            }
        }

      if (!$was_new_item)
        {
          # Display Original Submission.
          $body .= "\n  -----Original Message-----"
                   .trackers_field_display('details', $group_id,
                                           db_result($result,0,'details'),
                                           true,true,true,true);
        }
    }

  # See who is going to receive the notification.
  # Plus append any other email given at the end of the list.
  $arr_addresses = trackers_build_notification_list($item_id,$group_id,
                                                    $changes);
  $to = join(',',$arr_addresses);
# CERN SPECIFIC HACK
  $from = '"noreply ['.user_getrealname(0,0).']" <'
          .$GLOBALS['sys_mail_replyto'].'@'.$GLOBALS['sys_mail_domain'].'>';

# Replace usernames with user_ids (as expected by sendmail_mail).
  $repl_addresses = '';

  # -YPE- necessary because explode returns a one element array in case
  # it has to explode an empty string and this screws the code later on.
  if ($more_addresses != "")
    {
      $more_addr_arr = explode(',',$more_addresses);

      foreach ($more_addr_arr as $maddr)
        {
          $maddr = str_replace (" ", "", $maddr);
          if (validate_email($maddr))
            $repl_addresses .= ($repl_addresses ? ',':'').$maddr;
          else
            {
              $maddr_user_id = user_getid($maddr);
              if (user_exists($maddr_user_id))
                $repl_addresses .= ($repl_addresses ? ',':'').$maddr_user_id;
              else
                $repl_addresses .= ($repl_addresses ? ',':'').$maddr;
            }
        } # while (current ($more_addr_arr) !== FALSE)
    }

  if ($repl_addresses)
    $to .= ($to ? ',':'').$repl_addresses;

  # If the item is private, take into account the exclude-list.
  if (db_result($result,0,'privacy') == '2')
    {
       $exclude_list = db_result(db_execute("SELECT ".ARTIFACT
                                            ."_private_exclude_address
                                            FROM groups WHERE group_id=?",
                                            array($group_id)), 0,
                                 ARTIFACT."_private_exclude_address");
    }
  # Disallow mail notification for an address, private or not.
  if ($force_exclude_list)
    {
      if ($exclude_list)
        $exclude_list .= ",".$force_exclude_list;
      else
        $exclude_list = $force_exclude_list;
    }
  sendmail_mail($from, $to, $subject, $body, group_getunixname($group_id),
                ARTIFACT, $item_id, 0, 0, $exclude_list);
  return true;
}
# CERN SPECIFIC (at least for now) END
?>
