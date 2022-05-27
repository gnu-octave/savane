<?php
# Edit field usage.
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2018, 2022 Ineiev
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

require_once ('../../include/init.php');
require_once ('../../include/trackers/general.php');
$is_admin_page = 'y';

extract (sane_import ('request',
  ['name' => 'field', 'true' => 'update_field']
));
extract (sane_import ('post',
  [
    'true' =>
      [
        'post_changes', 'submit', 'reset'
      ],
    'specialchars' => ['label', 'description'],
    'digits' =>
      [
        ['status', 'keep_history', [0, 1]],
        ['mandatory_flag', [0, 3]],
        'place', 'n1', 'n2'
      ],
     'strings' =>
       [
         ['form_transition_default_auth', ['A', 'F']],
         ['show_on_add','show_on_add_members',  ['1']],
         ['show_on_add_logged', ['2']]
       ]
  ]
));

if (!$group_id)
  exit_no_group ();
if (!user_ismember ($group_id, 'A'))
  exit_permission_denied ();

trackers_init ($group_id);

if ($post_changes)
  {
    # A form was posted to update a field.
    if ($submit)
      {
        $display_size = null;
        if (isset ($n1) && isset ($n2))
          $display_size = "$n1/$n2";

        if (trackers_data_is_required ($field))
          {
            # Do not let the user change these field settings
            # if the field is required.
            $show_on_add_members =
              trackers_data_is_showed_on_add_members ($field);
            $show_on_add = trackers_data_is_showed_on_add ($field);
            $show_on_add_logged =
              trackers_data_is_showed_on_add_nologin ($field);
          }
        else
          {
             # Vote must be possible for members.
             # Vote cannot be possible for non-logged in.
            if ($field == "vote")
              {
                $show_on_add_logged = 0;
                $show_on_add_members = 1;
              }
          }

        # The additional possibility of differently treating non-project
        # members who have accounts and anonymous visitors demanded
        # a new handling of the values of the show_on_add field:
        # bit 1 set: show for logged in non project members
        # bit 2 set: show for non logged in users.
        $show_on_add = $show_on_add | $show_on_add_logged;

        trackers_data_update_usage (
          $field, $group_id, $label, $description, $status, $place,
          $display_size, $mandatory_flag, $keep_history, $show_on_add_members,
          $show_on_add, $form_transition_default_auth
        );
      }
    elseif ($reset)
      trackers_data_reset_usage ($field, $group_id);
    # Force a re-initialization of the global structure after
    # the update and before we redisplay the field list.
    trackers_init ($group_id);
  } # if ($post_changes)

if ($update_field)
  {
    # Show the form to change a field setting.
    # - "required" means the field must be used, no matter what
    # - "special" means the field is not entered by the user but by the system
    trackers_header_admin (['title' => _("Modify Field Usage")]);

    print '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
      . '" method="post">';
    print form_hidden (
      ['post_changes' => 'y', 'field' => $field, 'group_id' => $group_id]
    );
    print "\n<h1>" . _("Field Label:") . ' ';
    $closetag = "</h1>\n";
    if (trackers_data_is_select_box ($field))
      {
        # Only selectboxes can have values configured.
        $closetag .= '<p><span class="smaller">'
          . utils_link (
             $sys_home . ARTIFACT
             . "/admin/field_values.php?group=$group"
             . "&amp;list_value=1&amp;field=$field",
             _("Jump to this field values")
          )
          . "</span></p>\n";
      }
    # If it is a custom field let the user change the label and description.
    if (trackers_data_is_custom ($field))
      {
        print '<input type="text" title="' . _("Field Label")
          . '" name="label" value="'
          . trackers_data_get_label( $field) . '" size="20" maxlength="85">'
          . $closetag;
        print '<span class="preinput"><label for="description">'
          . _("Description:") . '</label> </span>';
        print "<br />\n&nbsp;&nbsp;&nbsp;<input type='text' id='description'> "
          . "name='description' value=\""
          . trackers_data_get_description ($field)
          . "\" size='70' maxlength='255' /><br />\n";
      }
    else
      print trackers_data_get_label ($field) . $closetag;

    print '<span class="preinput">' . _("Status:") . ' </span>&nbsp;&nbsp;';

    # Display the Usage box (Used, Unused select box  or hardcoded
    # "required").
    $sel = ' selected="selected"';
    if (trackers_data_is_required ($field))
      {
        print "<br />\n&nbsp;&nbsp;&nbsp;" . _("Required");
        print form_hidden (["status" => "1"]);
      }
    else
      {
        $ck = trackers_data_is_used ($field)? $sel: '';
        print "<br />&nbsp;&nbsp;&nbsp;\n"
          . ' <select title="' . _("Usage status") . "\" name='status'>\n"
          . "<option value='1'$ck>" . _("Used") . "</option>\n";
        $ck = trackers_data_is_used ($field)? '': $sel;
        print "<option value='0'$ck>" . _("Unused")
          . "</option>\n<select>\n";
      }

    # Ask they want to save the history of the item.
    if (!trackers_data_is_special ($field))
      {
        print "<br />\n<span class='preinput'>" . _("Item History:")
          . "</span>\n<br />&nbsp;&nbsp;&nbsp;\n"
          . "<select title=\"" . _("whether to keep in history")
          . "\" name='keep_history'>\n";
        $ck = trackers_data_do_keep_history ($field)? $sel: '';
        print "<option value='1'$ck>"
          . _("Keep field value changes in history") . "</option>\n";
        $ck = trackers_data_do_keep_history ($field)? '': $sel;
        print "<option value='0'$ck>"
          . _("Ignore field value changes in history")
          . "</option>\n</select>\n";
        }

    print "\n\n<p>&nbsp;</p><h2>" . _("Access:") . "</h2>\n";

    # Set mandatory bit: if the field is special, meaning it is entered
    # by the system, or if it is "priority", assume the
    # admin is not entitled to modify this behavior.
    if (!trackers_data_is_special ($field))
      {
        # "Mandatory" is not really 100% mandatory, only if it is possible
        # for a user to fill the entry.
        # It is "Mandatory whenever possible".
        $mandatory_flag = trackers_data_mandatory_flag ($field);
        print '<span class="preinput">' . _("This field is:")
          . "</span>\n<br />&nbsp;&nbsp;&nbsp;\n"
          . "<select title=\"" . _("whether the field is mandatory")
          . "\" name='mandatory_flag'>\n";
        $ck =($mandatory_flag == 1)? $sel: '';
        print "<option value='1'$sel>"
          . _("Optional (empty values are accepted)") . "</option>\n";
        $ck =($mandatory_flag == 3)? $sel: '';
        print "<option value='3'$sel>" . _("Mandatory") . "</option>\n";
        $ck =($mandatory_flag == 0)? $sel: '';
        print "<option value='0'$sel>"
          . _("Mandatory only if it was presented to the original submitter")
          . "</option>\n</select><br />\n";
     }

    print '<span class="preinput">'
      . _("On new item submission, present this field to:") . '</span> ';
    $checkbox_members = $checkbox_loggedin = $checkbox_anonymous = '';
    $sh_add_mem = trackers_data_is_showed_on_add_members ($field);
    $sh_add = trackers_data_is_showed_on_add ($field);
    $sh_anon = trackers_data_is_showed_on_add_nologin ($field);
    if (trackers_data_is_required ($field))
      {
        # Do not let the user change these field settings.
        if ($sh_add_mem)
          $checkbox_members = '+';
        if ($sh_add)
          $checkbox_loggedin = '+';
        if ($sh_anon)
          $checkbox_anonymous = '+';
      }
    else
      {
        # Some fields require specific treatment.
        if ($field == "vote")
          {
            # Vote is always available for members.
            # Vote is impossible unless logged in.
            $checkbox_members = '+';
            $checkbox_anonymous = 0;
            $checkbox_loggedin = form_checkbox ("show_on_add", $sh_add);
          }
        elseif ($field == "originator_email")
          {
            # Originator email is, by the code, available only to anonymous.
            $checkbox_members = 0;
            $checkbox_loggedin = 0;
            $checkbox_anonymous =
              form_checkbox ("show_on_add_logged", $sh_anon, ['value' => "2"]);
          }
        else
          {
            $checkbox_members =
              form_checkbox (
                "show_on_add_members", $sh_add_mem,
                ['title' => _("Show field to members")]
              );
            $checkbox_anonymous =
              form_checkbox (
                "show_on_add_logged", $sh_anon,
                ['title' => _("Show field to logged-in users"), 'value' => "2"]
              );
            $checkbox_loggedin =
              form_checkbox (
                "show_on_add_members", $sh_add,
                ['title' => _("Show field to anonymous users")]
              );
          }
      } # !trackers_data_is_required ($field)

    if ($checkbox_members)
      print "<br />\n&nbsp;&nbsp;&nbsp;$checkbox_members "
        . _("<!-- present this field to --> Project Members");
    if ($checkbox_loggedin)
      print "<br />\n&nbsp;&nbsp;&nbsp;$checkbox_loggedin "
        . _("<!-- present this field to --> Logged-in Users");
    if ($checkbox_anonymous)
      print "<br />\n&nbsp;&nbsp;&nbsp;$checkbox_anonymous "
         . _("<!-- present this field to --> Anonymous Users");

    if (trackers_data_is_special ($field))
      print '<input type="hidden" name="place" value="'
        . trackers_data_get_place ($field) . '" />';
    else
      {
        print "\n\n<p>&nbsp;</p>\n<h2>" . _("Display:") . "</h2>\n";

        print '<span class="preinput"><label for="place">'
          . _("Rank on page:")
          . "</label></span><br />\n&nbsp;&nbsp;&nbsp;";
        print '<input type="text" id="place" name="place" value="'
          . trackers_data_get_place ($field)
          . "\" size='6' maxlength='6' /><br />\n";
      }

    # Customize field size only for text fields and text areas.
    if (trackers_data_is_text_field ($field))
      {
        list ($size, $maxlength) = trackers_data_get_display_size ($field);

        print '<span class="preinput"><label for="n1">'
          . _("Visible size of the field:")
          . "</label> </span><br />&nbsp;&nbsp;&nbsp;\n";
        print '<input type="text" id="n1" name="n1" value="' . $size
          . "\" size='3' maxlength='3' /><br />\n";
        print '<span class="preinput"><label for="n2">'
          . _("Maximum size of field text (up to 255):")
          . "</label></span>\n<br />&nbsp;&nbsp;&nbsp;\n";
        print '<input type="text" id="n2" name="n2" value="' . $maxlength
          . "\" size='3' maxlength='3' /><br />\n";
      }
    else if (trackers_data_is_text_area($field))
      {
        list ($rows, $cols) = trackers_data_get_display_size ($field);

        print '<span class="preinput"><label for="n1">'
          ._ ("Number of columns of the field:")
          . "</label></span>\n<br />&nbsp;&nbsp;&nbsp;\n";
        print '<input type="text" id="n1" name="n1" value="' . $rows
          . "\" size='3' maxlength='3' /><br />\n";
        print '<span class="preinput"><label for="n2">'
          . _("Number of rows  of the field:")
          . "</label></span>\n<br />&nbsp;&nbsp;&nbsp;\n";
        print '<input type="text" id="n2" name="n2" value="' . $cols
          . "\" size='3' maxlength='3' /><br />\n";
      }

    # Transitions.

    # Only select boxes have transition management.
    if (trackers_data_is_select_box ($field))
      {
        $transition_default_auth = '';
        $result = db_execute ("
          SELECT transition_default_auth
          FROM " . ARTIFACT . "_field_usage
          WHERE group_id = ? AND bug_field_id = ?",
          [$group_id, trackers_data_get_field_id ($field)]
        );
        if (db_numrows($result) > 0)
          $transition_default_auth =
            db_result ($result, 0, 'transition_default_auth');
        $ck_str = ' checked="checked"';
        $ck = $transition_default_auth !='F';
        $ck1 = $ck? $ck_str: '';
        $ck2 = $ck? '': $ck_str;
        print "\n\n<p>&nbsp;</p>\n<h2>"
          . _("By default, transitions (from one value to another) are:")
          . "</h2>\n";
        print '&nbsp;&nbsp;&nbsp;<input type="radio" '
          . "name='form_transition_default_auth'\n"
          . "id='form_transition_default_auth_allowed' value='A'$ck1"
          . ' /><label for="form_transition_default_auth_allowed">'
          . _("Allowed") . "</label><br />\n&nbsp;&nbsp;&nbsp;<input "
          . "type='radio' id='form_transition_default_auth_forbidden'\n"
          . "name='form_transition_default_auth' value='F'$ck2"
          . ' /><label for="form_transition_default_auth_forbidden">'
          . _("Forbidden") . "</label>\n";
      }
    print "\n<p align='center'>"
      . "<input type='submit' name='submit' value=\""
      . _("Update") . "\" />\n&nbsp;&nbsp;\n<input type='submit' "
      . "name='reset' value=\"" . _("Reset to defaults")
      . "\" /></p>\n</form>\n";
    trackers_footer ([]);
    exit (0);
  } # if ($update_field)

trackers_header_admin (['title' => _("Select Fields")]);

print "<br />\n";

# Show all the fields currently available in the system.
$i = 0;
$title_arr = [
  _("Field Label"), _("Type"), _("Description"), _("Rank on page"),
  _("Scope"), _("Status")
];

$hdr = html_build_list_table_top ($title_arr);

# Build HTML for used fields, then unused fields.
$iu = $in = $inc = 0;
$hu = $hn = $hnc = '';
while ($field_name = trackers_list_all_fields ())
  {
    # Do not show some special fields any way in the list,
    # because there is nothing to customize in them.
    if (
        in_array (
          $field_name,
          [
            'group_id', 'comment_type_id', 'bug_id', 'date', 'close_date',
            'submitted_by', 'updated'
          ]
        )
    )
      continue;

    # Show used, unused and required fields on separate lists.
    # Show unused custom fields in a separate list at the very end.
    $is_required = trackers_data_is_required ($field_name);
    $is_custom = trackers_data_is_custom ($field_name);

    $is_used = trackers_data_is_used ($field_name);
    $status_label =
      $is_required? _("Required"): ($is_used? _("Used"): _("Unused"));

    $scope_label  =
      trackers_data_get_scope ($field_name) == 'S'?
      _("System"): _("Project");
    $place_label = $is_used? trackers_data_get_place ($field_name): '-';

    $html = '<td><a href="' . htmlentities ($_SERVER['PHP_SELF'])
      . "?group_id=$group_id"
      . '&update_field=1&field=' . urlencode ($field_name) . '">'
      . trackers_data_get_label ($field_name) . "</a></td>\n"
      . "\n<td>" . trackers_data_get_display_type_in_clear ($field_name)
      . "</td>\n<td>" . trackers_data_get_description ($field_name)
      . (($is_custom && $is_used)?
         ' - <strong>[' . _("Custom Field") . ']</strong>':
         '')
      . "</td>\n"
      . "\n<td align =\"center\">$place_label</td>\n"
      . "\n<td align =\"center\">$scope_label</td>\n"
      . "\n<td align =\"center\">$status_label</td>\n";

    if ($is_used)
      {
        $html = '<tr class="' . utils_altrow ($iu) . "\">$html</tr>\n";
        $iu++;
        $hu .= $html;
      }
    else
      {
        if ($is_custom)
          {
            $html = '<tr class="' . utils_altrow ($inc)
              . "\">$html</tr>\n";
            $inc++;
            $hnc .= $html;
          }
        else
          {
            $html = '<tr class="'
              . utils_altrow ($in) . "\">$html</tr>\n";
            $in++;
            $hn .= $html;
          }
      }
  } #  while ($field_name = trackers_list_all_fields())

$rule0 = '<tr><td colspan="5"><center><strong>---- ';
$rule1 = " ----</strong></center></tr>\n";
$tr = "<tr><td colspan='5'> &nbsp;</td></tr>\n$rule0";
$hu =  $rule0 . _("USED FIELDS") . "$rule1$hu";
if ($in)
  $hn = "$tr" . _("UNUSED STANDARD FIELDS") . "$rule1$hn";

if ($inc)
  $hnc = "$tr" . _("UNUSED CUSTOM FIELDS") . "$rule1$hnc";
print "$hdr$hu$hn$hnc</table>\n";

trackers_footer ([]);
?>
