<?php
# Add item to trackers.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017, 2022 Ineiev
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

extract (sane_import ('request',
  ['hash' => 'form_id', 'array' => [['prefill', [null, 'specialchars']]]]
));

if (!group_restrictions_check ($group_id, ARTIFACT))
  {
    $help = group_getrestrictions_explained ($group_id, ARTIFACT);
    # TRANSLATORS: the argument is a string that explains why the action is
    # unavailable.
    exit_error (sprintf (_("Action Unavailable: %s"), $help));
  }


trackers_header (['title' => _("Submit Item")]);
$fields_per_line = 2;
$max_size = 40;

$grp_field = ARTIFACT . "_preamble";
# First display the message preamble.
$res_preamble = db_execute (
  "SELECT $grp_field FROM groups WHERE group_id = ?", [$group_id]
);

$preamble = db_result ($res_preamble, 0, $grp_field);
if ($preamble)
  print '<h2>' . _("Preamble") . "</h2>\n" . markup_rich ($preamble);

print '<h2>' . _("Details") . "</h2>\n";

# Beginning of the submission form with fixed fields.
print form_header (
  $_SERVER['PHP_SELF'], $form_id, "post",
  'enctype="multipart/form-data" name="trackers_form"'
);
print form_input ("hidden", "func", "postadditem");
print form_input ("hidden", "group_id", $group_id);
print "\n<table cellpadding='0' width='100%'>";

# Now display the variable part of the field list (depending on the project).
$i = 0;
$j = 0;
$is_trackeradmin =
  member_check (0, $group_id, member_create_tracker_flag (ARTIFACT) . '2');

while ($field_name = trackers_list_all_fields ())
  {
    # If the field is a special field (except summary and original description)
    # or if not used by this project then skip it.
    # Plus only show fields allowed on the bug submit_form.
    if (!((!trackers_data_is_special ($field_name) || $field_name == 'summary'
           || $field_name == 'details')
          && trackers_data_is_used ($field_name)))
      continue;
    if (!(($is_trackeradmin
           && trackers_data_is_showed_on_add_members ($field_name))
          || (!$is_trackeradmin
              && trackers_data_is_showed_on_add ($field_name))
          || (!user_isloggedin ()
              && trackers_data_is_showed_on_add_nologin ($field_name))))
      continue;

    # Display the bug field with its default value.
    # If field size is greatest than max_size chars, then force it to
    # appear alone on a new line or it won't fit in the page.

    # We allow people to make urls with predefined values,
    # if the values are in the url, we override the default value.
    if (!empty ($$field_name))
      $field_value = htmlspecialchars ($$field_name);
    elseif (isset ($prefill[$field_name]))
      $field_value = $prefill[$field_name];
    else
      $field_value = trackers_data_get_default_value ($field_name);
    list ($sz,) = trackers_data_get_display_size ($field_name);
    $label = trackers_field_label_display (
      $field_name, $group_id, false, false
    );
    if ($field_name == 'details')
      $label .= ' <span class="preinput">' . markup_info ("full") . '<span>';

    $star = '';
    $mandatory_flag = trackers_data_mandatory_flag ($field_name);
    if ($mandatory_flag == 3 || $mandatory_flag == 0)
      {
        $star = '<span class="warn"> *</span>';
        $mandatory_flag = 0;
      }

    # Field display with special Unknown option, only for fields that
    # are no mandatory.
    $value = trackers_field_display (
      $field_name, $group_id, $field_value, false, false, false,
      false, false, false, false, false, true, $mandatory_flag
    );
    # Fields colors.
    $field_class = $row_class = '';
    if ($j % 2 && $field_name != 'details')
      {
        # We keep the original submission with the default
        # background color, for lisibility sake.
        #
        # We also use the boxitem background color only one time
        # out of two, to keep the page light.
        $row_class = ' class="' . utils_altrow ($j + 1) . '"';
      }

    # If we are working on the cookbook, present checkboxes to
    # defines context before the summary line.
    if (CONTEXT == 'cookbook' && $field_name == 'summary' && $is_trackeradmin)
      {
        cookbook_print_form ();
      }

    # We highlight fields that were not properly/completely
    # filled.
    if (!empty ($previous_form_bad_fields)
        && array_key_exists ($field_name, $previous_form_bad_fields))
      {
        $field_class = ' class="highlight"';
      }

    if ($sz > $max_size)
      {
        # Field getting one line for itself.
        # Each time prepare the background color change.
        $j++;

        print "\n<tr$row_class>"
          . "<td valign='middle'$field_class width='15%'>$label</td>\n"
          . "<td valign='middle'$field_class colspan=\""
          . (2 * $fields_per_line - 1) . '" width="75%">'
          . "$value$star</td>\n</tr>\n";
        $i = 0;
      }
    else
      {
        # Field getting half of a line for itself.
        if (!($i % $fields_per_line))
          {
            # Every one out of two, prepare the background color change.
            # We do that at this moment because we cannot be sure
            # there will be another field on this line.
            $j++;
          }

        print ($i % $fields_per_line ? "\n": "\n<tr$row_class>");
        print "<td valign='middle'$field_class width='15%'>$label</td>\n"
          . "<td valign='middle'$field_class width='35%'>$value$star</td>\n";
        $i++;
        print ($i % $fields_per_line ? "\n": "</tr>\n");
      }
  } # while ($field_name = trackers_list_all_fields ())

print "</table>\n";
print '<p class="warn"><span class="smaller">* ' . _("Mandatory Fields")
      . "</span></p>\n";

print "<p>&nbsp;</p>\n";
print '<h2>' . _("Attached Files") . "</h2>\n";
printf (
  _("(Note: upload size limit is set to %s kB, after insertion of\n"
    . "the required escape characters.)"),
  $GLOBALS['sys_upload_max']
);

print '<p><span class="preinput"> ' . _("Attach Files:") . "</span><br />\n";

for ($i = 1; $i < 5; $i++)
  {
    $odd = $i % 2;
    if ($odd)
      print "&nbsp;&nbsp;&nbsp;";
    print "<input type='file' name='input_file$i' size='10' />\n";
    if (!$odd)
      print "<br />\n";
  }
print '<span class="preinput">' . _("Comment:") . "</span><br />\n"
  . '&nbsp;&nbsp;&nbsp;'
  . "<input type='text' name='file_description' size='60' maxlength='255' />"
  . "\n</p>\n";

# Cc addresses.
if (user_isloggedin ())
  {
    print "<p>&nbsp;</p>\n";
    print '<h2>' . _("Mail Notification CC") . "</h2>\n";

    # TRANSLATORS: the argument is site name (like Savannah).
    print '<p>';
    printf (
      _("(Note: for %s users, you can use their login name\n"
        . "rather than their email addresses.)"),
      $sys_name
    );
    print "</p>\n";

    print '<p><span class="preinput">'
      . _("Add Email Addresses (use comma as separator):")
      . "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . '<input type="text" name="add_cc" size="40" value="'
      . htmlspecialchars ($add_cc) . "\" />&nbsp;&nbsp;&nbsp;\n"
      . "<br />\n<span class='preinput'>" . _("Comment:")
      . "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . '<input type="text" name="cc_comment" value="'
      . htmlspecialchars ($cc_comment) . '" size="40" maxlength="255" />';
    print "</p>\n";
  }

# Minimal anti-spam.
if (!user_isloggedin ())
  print '<p class="noprint">'
    . _("Please enter the title of <a "
        . "href='https://en.wikipedia.org/wiki/George_Orwell'>George Orwell</a>"
        . "'s famous dystopian book (it's a date):")
    . " <input type='text' name='check' /></p>\n";

print "<p>&nbsp;</p>\n";
print '<p><span class="warn">'
  . _("Did you check to see if this item has already been submitted?")
  . "</span></p>\n";
print '<div align="center">';
print form_submit (false, "submit", 'class="bold"');
print "</div>\n";
print "</form>\n";

trackers_footer ([]);
?>
