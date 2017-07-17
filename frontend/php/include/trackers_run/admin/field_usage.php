<?php
# Edit field usage.
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
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

$is_admin_page='y';

extract(sane_import('request', array('field', 'update_field')));
extract(sane_import('post', array(
  'post_changes', 'submit', 'reset',
  'label', 'description',
  'status', 'keep_history', 'mandatory_flag', 'place',
  'form_transition_default_auth',
  'show_on_add_members', 'show_on_add', 'show_on_add_nologin',
  'n1', 'n2',
)));

if (!($group_id && user_ismember($group_id,'A')))
  {
    if (!$group_id)
      exit_no_group();
    exit_permission_denied();
  }

# Initialize global bug structures.
trackers_init($group_id);


if ($post_changes)
  {
    # A form was posted to update a field.
    if ($submit)
      {
        $display_size = null;
        if (isset($n1) && isset($n2))
          $display_size = "$n1/$n2";

        if (!trackers_data_is_required($field))
          {
             # Vote must be possible for members.
             # Vote cannot be possible for non-logged in.
            if ($field == "vote")
              {
                $show_on_add_nologin = 0;
                $show_on_add_members = 1;
              }
          }
        else
          {
            # Do not let the user change these field settings
            # if the field is required.
            $show_on_add_members = trackers_data_is_showed_on_add_members($field);
            $show_on_add = trackers_data_is_showed_on_add($field);
            $show_on_add_nologin = trackers_data_is_showed_on_add_nologin($field);
          }

        # The additional possibility of differently treating non project
        # members who have a savannah account and users without a
        # savannah account demanded a new handling of the values of
        # the show_on_add field:
        # bit 1 set: show for logged in non project members
        # bit 2 set: show for non logged in users.
        $show_on_add = $show_on_add | $show_on_add_nologin;

        trackers_data_update_usage($field,
                                   $group_id,
                                   $label,
                                   $description,
                                   $status,
                                   $place,
                                   $display_size,
                                   $mandatory_flag,
                                   $keep_history,
                                   $show_on_add_members,
                                   $show_on_add,
                                   $form_transition_default_auth);
      }
    else if ($reset)
      {
        trackers_data_reset_usage($field,$group_id);
      }
    # Force a re-initialization of the global structure after
    # the update and before we redisplay the field list.
    trackers_init($group_id);
  }

if ($update_field)
  {
    # Show the form to change a field setting.

    #   - "required" means the field must be used, no matter what
    #   - "special" means the field is not entered by the user but by the
    #   system
    trackers_header_admin(array ('title'=>_("Modify Field Usage")));

    print '<form action="'.htmlentities ($_SERVER['PHP_SELF'])
          .'" method="post">';
    print '<input type="hidden" name="post_changes" value="y" />
    <input type="hidden" name="field" value="'.$field.'" />
    <input type="hidden" name="group_id" value="'.$group_id.'" />
    <h2>'
      ._("Field Label:").' ';

    $closetag = '';
    if (trackers_data_is_select_box($field))
      {
        # Only selectboxes can have values configured.
        $closetag = ' &nbsp;&nbsp; <span class="smaller">('
                    .utils_link($GLOBALS['sys_home'].ARTIFACT
                                .'/admin/field_values.php?group='.$group
                                .'&amp;list_value=1&amp;field='.$field,
                                _("Jump to this field values")).')</span>';
      }
    $closetag .= "</h2>\n";
    # If it is a custom field let the user change the label and description.
    if (trackers_data_is_custom($field))
      {
        print '<input type="text" name="label" value="'
          .trackers_data_get_label($field).'" size="20" maxlength="85">'
          .$closetag;
        print '<span class="preinput">'._("Description:").' </span>';
        print '<br />
&nbsp;&nbsp;&nbsp;<input type="text" name="description" value="'
          .trackers_data_get_description($field)
          .'" size="70" maxlength="255" /><br />
';
      }
    else
      print trackers_data_get_label($field).$closetag;

    print '<span class="preinput">'._("Status:").' </span>&nbsp;&nbsp;';

    # Display the Usage box (Used, Unused select box  or hardcoded
    # "required").
    if (trackers_data_is_required($field))
      {
        print '<br />&nbsp;&nbsp;&nbsp;'._("Required");
        print '<input type="hidden" name="status" value="1" />';
      }
    else
      {
        print '<br />&nbsp;&nbsp;&nbsp;
<select name="status">
  <option value="1"'.(trackers_data_is_used($field)?' selected="selected"':'')
           .'>'._("Used").'</option>
  <option value="0"'.(trackers_data_is_used($field)?'':' selected="selected"')
           .'>'._("Unused").'</option>
<select>';
      }

      # Ask they want to save the history of the item.
    if (!trackers_data_is_special($field))
      {
        print '<br /><span class="preinput">'._("Item History:").' </span>
<br />&nbsp;&nbsp;&nbsp;
<select name="keep_history">
  <option value="1"'
  .(trackers_data_do_keep_history($field)?' selected="selected"':'')
  .'>'._("Keep field value changes in history").'</option>
  <option value="0"'
  .(trackers_data_do_keep_history($field)?'':' selected="selected"')
  .'>'._("Ignore field value changes in history").'</option>
</select>';
        }

    print "\n\n<p>&nbsp;</p><h3>"._("Access:").'</h3>';

    # Set mandatory bit: if the field is special, meaning it is entered
    # by the system, or if it is "priority", assume the
    # admin is not entitled to modify this behavior.
    if (!trackers_data_is_special($field))
      {
        # "Mandatory" is not really 100% mandatory, only if it is possible
        # for a user to fill the entry.
        # It is "Mandatory whenever possible".
        $mandatory_flag = trackers_data_mandatory_flag($field);
        print '<span class="preinput">'._("This field is:").' </span>
<br />&nbsp;&nbsp;&nbsp;
<select name="mandatory_flag">
  <option value="1"'.(($mandatory_flag == 1)?' selected="selected"':'').'>'
  ._("Optional (empty values are accepted)").'</option>
  <option value="3"'.(($mandatory_flag == 3)?' selected="selected"':'').'>'
  ._("Mandatory").'</option>
  <option value="0"'.(($mandatory_flag == 0)?' selected="selected"':'').'>'
  ._("Mandatory only if it was presented to the original submitter").'</option>
</select><br />
';
     }

    print '<span class="preinput">'
      ._("On new item submission, present this field to:").' </span>';
    $checkbox_members = '';
    $checkbox_loggedin = '';
    $checkbox_anonymous = '';
    if (!trackers_data_is_required($field))
      {
        # Some fields require specific treatment.
        if ($field != "vote" && $field != "originator_email")
          {
            $checkbox_members =
              '<input type="checkbox" name="show_on_add_members" value="1"'
              .(trackers_data_is_showed_on_add_members($field)?
                ' checked="checked"':'')
              .' />';
            $checkbox_anonymous =
              '<input type="checkbox" name="show_on_add_nologin" value="2"'
              .(trackers_data_is_showed_on_add_nologin($field)?
                ' checked="checked"':'')
              .' />';
            $checkbox_loggedin =
              '<input type="checkbox" name="show_on_add" value="1"'
              .(trackers_data_is_showed_on_add($field)?' checked="checked"':'')
              .' />';
          }
        else
          {
            # Vote must be possible for members.
            # Vote cannot be possible for non logged in.
            if ($field != "vote")
              {
                $checkbox_members = '+';
                $checkbox_anonymous = 0;
                $checkbox_loggedin =
                  '<input type="checkbox" name="show_on_add" value="1"'
                  .(trackers_data_is_showed_on_add($field)?
                    ' checked="checked"':'').' />';
              }

            # Originator email is, by the code, available only to
            # anonymous.
            if ($field != "vote")
              {
                $checkbox_members = 0;
                $checkbox_loggedin = 0;
                $checkbox_anonymous =
                  '<input type="checkbox" name="show_on_add_nologin" value="2"'
                  .(trackers_data_is_showed_on_add_nologin($field)?
                    ' checked="checked"':'').' />';
              }
          }
      }
    else
      {
        # Do not let the user change these field settings.
        if (trackers_data_is_showed_on_add_members($field))
          {
            $checkbox_members = '+';
          }
        if (trackers_data_is_showed_on_add($field))
          {
            $checkbox_loggedin = '+';
          }
        if (trackers_data_is_showed_on_add_nologin($field))
          {
            $checkbox_anonymous = '+';
          }
      }

      if ($checkbox_members)
        {
          print '<br />&nbsp;&nbsp;&nbsp;
'.$checkbox_members.' '._("<!-- present this field to --> Project Members");
        }
      if ($checkbox_loggedin)
        {
          print '<br />&nbsp;&nbsp;&nbsp;
'.$checkbox_loggedin.' '._("<!-- present this field to --> Logged-in Users");
        }
      if ($checkbox_anonymous)
        {
          print '<br />&nbsp;&nbsp;&nbsp;
'.$checkbox_anonymous.' '._("<!-- present this field to --> Anonymous Users");
        }

      print "\n\n<p>&nbsp;</p>\n<h3>"._("Display:")."</h3>\n";

      # yeupou--gnu.org 2005-07-18
      # I suspect that the is_special affect the way the field shown,
      # making useless the rank parameter.
      # Check the previous comments.
      if (!trackers_data_is_special($field))
        {
          print '<span class="preinput">'._("Rank on page:")
                .' </span><br />&nbsp;&nbsp;&nbsp;';
          print '<input type="text" name="place" value="'
                .trackers_data_get_place($field)
                .'" size="6" maxlength="6" /><br />'."\n";
        }
      else
        {
          print '<input type="hidden" name="place" value="'
                .trackers_data_get_place($field).'" />';
        }

      # Customize field size only for text fields and text areas.
      if (trackers_data_is_text_field($field))
        {
          list($size,$maxlength) = trackers_data_get_display_size($field);

          print '<span class="preinput">'._("Visible size of the field:")
                ." </span><br />&nbsp;&nbsp;&nbsp;\n";
          print '<input type="text" name="n1" value="'.$size
                .'" size="3" maxlength="3" /><br />'."\n";
          print '<span class="preinput">'
                ._("Maximum size of field text (up to 255):")
                .' </span><br />&nbsp;&nbsp;&nbsp;'."\n";
          print '<input type="text" name="n2" value="'.$maxlength
                .'" size="3" maxlength="3" /><br />'."\n";
        }
      else if (trackers_data_is_text_area($field))
        {
          list($rows,$cols) = trackers_data_get_display_size($field);

          print '<span class="preinput">'._("Number of columns of the field:")
                ." </span><br />&nbsp;&nbsp;&nbsp;\n";
          print '<input type="text" name="n1" value="'.$rows
                .'" size="3" maxlength="3" /><br />'."\n";
          print '<span class="preinput">'._("Number of rows  of the field:")
                .' </span><br />&nbsp;&nbsp;&nbsp;'."\n";
          print '<input type="text" name="n2" value="'.$cols
                .'" size="3" maxlength="3" /><br />'."\n";
        }

      # Transitions.

      # Only select boxes have transition management.
      if (trackers_data_is_select_box($field))
        {
          $transition_default_auth = '';
          $result = db_execute("SELECT transition_default_auth FROM "
                               .ARTIFACT
                               ."_field_usage WHERE group_id=? AND bug_field_id=?",
                               array($group_id,
                                     trackers_data_get_field_id($field)));
          if (db_numrows($result) > 0)
            $transition_default_auth = db_result($result, 0,
                                                 'transition_default_auth');

          print "\n\n<p>&nbsp;</p>\n<h3>"
                ._("By default, transitions (from one value to another) are:")
                ."</h3>\n";
          print '&nbsp;&nbsp;&nbsp;<input type="radio" '
                .'name="form_transition_default_auth" value="A" '
                .(($transition_default_auth!='F')?' checked="checked"':'')
                .' /> '._("Allowed").'<br />&nbsp;&nbsp;&nbsp;
<input type="radio" name="form_transition_default_auth" value="F" '
                .(($transition_default_auth=='F')?'checked="checked"':'')
                .' /> '._("Forbidden");
        }
      print '
              <p align="center">
              <input type="submit" name="submit" value="'._("Update").'" />
              &nbsp;&nbsp;
              <input type="submit" name="reset" value="'
        ._("Reset to defaults").'" /></p>
              </form>
';
      trackers_footer(array());
    }
  else
    {
      # Show main page.
      trackers_header_admin(array ('title'=>_("Select Fields")));

      # Add space to avoid overlaps.
      print "<br />\n";

      # Show all the fields currently available in the system.
      $i=0;
      $title_arr=array();
      $title_arr[]=_("Field Label");
      $title_arr[]=_("Type");
      $title_arr[]=_("Description");
      $title_arr[]=_("Rank on page");
      $title_arr[]=_("Scope");
      $title_arr[]=_("Status");

      $hdr = html_build_list_table_top ($title_arr);

      # Build HTML ouput for  Used fields first and Unused field second.
      $iu = $in = $inc = 0;
      $hu = $hn = $hnc = '';
      while ($field_name = trackers_list_all_fields())
        {
          # Do not show some special fields any way in the list,
          # because there is nothing to customize in them.
          if (($field_name == 'group_id')
              || ($field_name == 'comment_type_id')
              || ($field_name == 'bug_id')
              || ($field_name == 'date')
              || ($field_name == 'close_date')
              || ($field_name == 'submitted_by') )
            continue;

          # Show Used, Unused and Required fields on separate lists.
          # Show Unused Custom field in a separate list at the very end.
          $is_required = trackers_data_is_required($field_name);
          $is_custom = trackers_data_is_custom($field_name);

          $is_used = trackers_data_is_used($field_name);
          $status_label = ($is_required?_("Required"):($is_used?_("Used")
                                                                :_("Unused")));

          $scope_label  = (trackers_data_get_scope($field_name)=='S'?
                           _("System"):_("Project"));
          $place_label = ($is_used?trackers_data_get_place($field_name):'-');

          $html = '<td><a href="'.htmlentities ($_SERVER['PHP_SELF'])
             .'?group_id='.$group_id
             .'&update_field=1&field='.$field_name.'">'
             .trackers_data_get_label($field_name)."</a></td>\n"
             ."\n<td>".trackers_data_get_display_type_in_clear($field_name)
             .'</td>'."\n<td>".trackers_data_get_description($field_name)
             .(($is_custom && $is_used) ? ' - <strong>['
             ._("Custom Field").']</strong>':'')."</td>\n"
             ."\n<td align =\"center\">".$place_label."</td>\n"
             ."\n<td align =\"center\">".$scope_label."</td>\n"
             ."\n<td align =\"center\">".$status_label."</td>\n";

          if ($is_used)
            {
              $html = '<tr class="'
                 .utils_get_alt_row_color($iu) .'">'.$html."</tr>\n";
              $iu++;
              $hu .= $html;
            }
          else
            {
              if ($is_custom)
                {
                  $html = '<tr class="'
                     .utils_get_alt_row_color($inc) .'">'.$html."</tr>\n";
                  $inc++;
                  $hnc .= $html;
                }
              else
                {
                  $html = '<tr class="'
                     .utils_get_alt_row_color($in) .'">'.$html."</tr>\n";
                  $in++;
                  $hn .= $html;
                }
            }

        } #  while ($field_name = trackers_list_all_fields())

      # Print the HTML table.
      if ($iu == 0)
        {
          $html = '<p>'._("No extension field in use.").' '
                  ._("Choose one below.").'</p>'."\n".$html;
        }
      else
        {
          $hu= '<tr><td colspan="5"><center><strong>---- '._("USED FIELDS")
               .' ----</strong></center></tr>'.$hu;
          if ($in)
            {
              $hn = '<tr><td colspan="5"> &nbsp;</td></tr>'."\n"
                 .'<tr><td colspan="5"><center><strong>---- '
                 ._("UNUSED STANDARD FIELDS").' ----</strong></center></tr>'
                 ."\n".$hn;
            }

          if ($inc)
            {
              $hnc = '<tr><td colspan="5"> &nbsp;</td></tr>'."\n"
                 .'<tr><td colspan="5"><center><strong>---- '
                 ._("UNUSED CUSTOM FIELDS").' ----</strong></center></tr>'
                 ."\n".$hnc;
            }
        }
      print $hdr.$hu.$hn.$hnc."</table>\n";

      trackers_footer(array());
    }
?>
