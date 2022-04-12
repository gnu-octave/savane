<?php
# Edit field values.
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
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
  [
    'strings' => [['func', ['deltransition', 'delcanned']]],
    'true' => ['update_value', 'create_canned', 'update_canned'],
    'digits' => ['fv_id', 'item_canned_id'],
    'name' => 'field'
  ]
));
extract (sane_import ('get',
  [
    'true' => ['list_value'],
    'digits' => 'transition_id'
  ]
));
extract (sane_import ('post',
  [
    'true' => ['post_changes', 'create_value', 'by_field_id'],
    'specialchars' => ['title', 'description', 'body'],
    'digits' => ['order_id', 'from', 'to'],
    'strings' =>
      [
        ['allowed', ['A', 'F']],
        ['status', ['A', 'P', 'H']]
      ],
    'preg' => [['mail_list', '/^[-+_@.,\s\da-zA-Z]*$/']]
  ]
));

if (!$group_id)
  exit_no_group ();
if (!user_ismember ($group_id, 'A'))
  exit_permission_denied ();

$server_self = htmlentities ($_SERVER['PHP_SELF']);
$delete_canned = $func === 'delcanned';

trackers_init ($group_id);

function delete_transition ($transition_id)
{
  $result = db_execute ("
    DELETE FROM trackers_field_transition WHERE transition_id = ? LIMIT 1",
    [$transition_id]
  );
  if ($result)
    fb (_("Transition deleted"));
  else
    fb (_("Error deleting transition"), 1);
}

function delete_response ($group_id, $item_canned_id)
{
  $result = db_execute ("
    DELETE FROM " . ARTIFACT . "_canned_responses
    WHERE group_id = ? AND bug_canned_id = ?",
    [$group_id, $item_canned_id]
  );
  if ($result)
    fb (_("Canned response deleted"));
  else
    fb (_("Error deleting canned response"), 1);
}

if ($func == "deltransition")
  delete_transition ($transition_id);
elseif ($delete_canned)
  delete_response ($group_id, $item_canned_id);
elseif ($post_changes)
  {
    # A form of some sort was posted to update or create
    # an existing value.
    # Deleted Canned doesn't need a form, so let switch
    # into this code.

    if ($create_value)
      {
        # A form was posted to update a field value.
        if ($title)
          trackers_data_create_value (
            $field, $group_id, $title, $description, $order_id, 'A'
          );
        else
          fb (_("Empty field value is not allowed"), 1);
      }
    elseif ($update_value)
      {
        # A form was posted to update a field value.
        if ($title)
          trackers_data_update_value (
            $fv_id, $field, $group_id, $title, $description, $order_id, $status
          );
        else
          fb (_("Empty field value is not allowed"), 1);
      }
    elseif ($create_canned)
      {
        # A form was posted to create a canned response.
        $result = db_autoexecute (
          ARTIFACT . '_canned_responses',
          [
            'group_id' => $group_id, 'title' => $title, 'body' => $body,
            'order_id' => $order_id,
          ],
          DB_AUTOQUERY_INSERT
        );
        if ($result)
          fb (_("Canned bug response inserted"));
        else
          fb (_("Error inserting canned bug response"), 1);
      }
    elseif ($update_canned)
      {
        # A form was posted to update a canned response.
        $result = db_autoexecute (
          ARTIFACT . '_canned_responses',
          ['title' => $title, 'body' => $body, 'order_id' => $order_id],
          DB_AUTOQUERY_UPDATE, 'group_id = ? AND bug_canned_id = ?',
          [$group_id,  $item_canned_id]
        );
        if (!$result)
          fb (_("Error updating canned bug response"), 1);
        else
          fb (_("Canned bug response updated"));
      }
  }

$field_id = $by_field_id? $field: trackers_data_get_field_id ($field);

if ($to != $from)
  {
    # A form was posted to update or create a transition.
    $res_value = db_execute (
     "SELECT from_value_id, to_value_id, is_allowed, notification_list
      FROM trackers_field_transition
      WHERE
        group_id = ? AND artifact = ? AND field_id = ?
        AND from_value_id = ? AND to_value_id = ?",
     [$group_id, ARTIFACT, $field_id, $from, $to]
    );
    $rows = db_numrows ($res_value);

    # If no entry for this transition, create one.
    if ($rows == 0)
      {
        $result = db_autoexecute (
          'trackers_field_transition',
          [
            'group_id' => $group_id, 'artifact' => ARTIFACT,
            'field_id' => $field_id, 'from_value_id' => $from,
            'to_value_id' => $to, 'is_allowed' => $allowed,
            'notification_list' => $mail_list,
          ],
          DB_AUTOQUERY_INSERT
        );

        if (db_affected_rows ($result) < 1)
          fb (_("Insert failed"), 1);
        else
          fb (_("New transition inserted"));
      }
    else
      {
        # Update the existing entry for this transition.
        $result = db_autoexecute (
          'trackers_field_transition',
           ['is_allowed' => $allowed, 'notification_list' => $mail_list],
           DB_AUTOQUERY_UPDATE,
           'group_id = ? AND artifact = ? AND field_id = ?
            AND from_value_id = ? AND to_value_id = ?',
           [$group_id, ARTIFACT, $field_id, $from, $to]
        );

        if (db_affected_rows ($result) < 1)
          fb (_("Update of transition failed"), 1);
        else
          fb (_("Transition updated"));
      }
  } # ($to != $from)

$td_select_box = function ($field)
{
  return trackers_data_get_field_id ($field)
    && trackers_data_is_select_box ($field);
};

if ($list_value)
  {
    # Display the list of values for a given bug field.
    # TRANSLATORS: the argument is field label.
    $hdr = sprintf (
      _("Edit Field Values for '%s'"), trackers_data_get_label ($field)
    );

    if ($td_select_box ($field))
      {
        # First check that this field is used by the project and
        # it is in the project scope.

        $is_project_scope = trackers_data_is_project_scope ($field);

        trackers_header_admin (array ('title' => $hdr));

        print '<h1>' . _("Field Label:") . ' '
          . trackers_data_get_label ($field) . "</h1>\n<p>"
          . '<span class="smaller">('
          . utils_link (
              $sys_home . ARTIFACT . "/admin/field_usage.php?group=$group"
              . "&amp;update_field=1&amp;field=$field",
              _("Jump to this field usage")
            )
          . ")</span></p>\n";

        $result = trackers_data_get_field_predefined_values (
          $field, $group_id, false, false, false
        );
        $rows = db_numrows ($result);

        if (!$result || $rows <= 0)
          # TRANSLATORS: the  argument is field label.
          printf (
            "\n<h1>" . _("No values defined yet for %s") . "</h1>\n",
            trackers_data_get_label ($field)
          );
        else
          {
            print "\n<h2>" . _("Existing Values") . "</h2>\n";

            $title_arr =  [];
            if (!$is_project_scope)
              $title_arr[] = _('ID');
            $title_arr[] = _("Value label");
            $title_arr[] = _("Description");
            $title_arr[] = _("Rank");
            $title_arr[] = _("Status");
            $title_arr[] = _("Occurrences");

            $hdr = html_build_list_table_top ($title_arr);

            $status_stg = [
              # TRANSLATORS: this is field status.
             'A' => _("Active"), 'P' => _("Permanent"), 'H' => _("Hidden")
           ];

            # Display the list of values in 2 blocks: active first,
            # hidden second.
            $ia = $ih = 0;
            $ha = $hh = '';
            while ( $fld_val = db_fetch_array ($result) )
              {
                $item_fv_id = $fld_val['bug_fv_id'];
                $status = $fld_val['status'];
                $value_id = $fld_val['value_id'];
                $value = $fld_val['value'];
                $description = $fld_val['description'];
                $order_id = $fld_val['order_id'];
                $usage = trackers_data_count_field_value_usage (
                  $group_id, $field, $value_id
                );
                $html = '';
                # Keep the rank of the 'None' value in mind if any.
                if ($value == 100)
                  $none_rk = $order_id;

                # Show the value ID only for system wide fields which
                # value id are fixed and serve as a guide.
                if (!$is_project_scope)
                  $html .= "<td>$value_id</td>\n";

                # The permanent values cant be modified (No link).
                $txt_val = $value;
                if ($status != 'P')
                  $txt_val = "<a href=\"$server_self?update_value=1"
                    . "&fv_id=$item_fv_id&field=$field&group_id=$group_id"
                    . "\">$value</a>";
                $html .= "<td>$txt_val</td>\n";

                $html .= "<td>$description&nbsp;</td>\n"
                  . "<td align='center'>$order_id</td>\n"
                  . "<td align='center'>{$status_stg[$status]}</td>\n";

                $us_str = $usage;
                if ($status == 'H' && $usage > 0)
                  $us_str = "<strong class='warn'>$usage</strong>";
                $html .= "<td align='center'>$us_str</td>\n";

                $suff = 'h';
                if ($status == 'A' || $status == 'P')
                  $suff = 'a';
                $class = utils_altrow (${"i$suff"});
                $html = "<tr class=\"$class\">$html</tr>\n";
                ${"i$suff"}++;
                ${"h$suff"} .= $html;
              }

            # Display the list of values now.
            if ($ia)
              $ha = '<tr><td colspan="4" class="center"><strong>'
                . _("---- ACTIVE VALUES ----") . "</strong></tr>\n$ha";
            else
              $hdr = '<p>'
                . _("No active value for this field. Create one or "
                    . "reactivate a hidden value (if\nany)")
                . "</p>\n$hdr";
            if ($ih)
              $hh = "<tr><td colspan=\"4\"> &nbsp;</td></tr>\n"
                .' <tr><td colspan="4"><center><strong>'
                . _("---- HIDDEN VALUES ----")
                . "</strong></center></tr>\n$hh";
            print "$hdr$ha$hh</table>\n";
          } # !(!$result || $rows <= 0)

        # Only show the add value form if this is a project scope field.
        if ($is_project_scope)
          {
            print '<h2>' . _("Create a new field value") . "</h2>\n";

            if ($ih)
              print '<p>'
                . _("Before you create a new value make sure there isn't one "
                . "in the hidden list\nthat suits your needs.")
                . "</p>\n";

            print "<form action=\"$server_self\" method='post'>";
            print form_hidden (
                [
                  'post_changes' => 'y', 'create_value' => 'y',
                  'list_value' => 'y', 'field' => $field,
                  'group_id' => $group_id
                ]
              );
            print '<span class="preinput"><label for="title">'
              . _("Value:") . '</label> </span>'
              . form_input ("text", "title", "", 'size="30" maxlength="60"')
              . "\n&nbsp;&nbsp;<span class='preinput'><label for='order_id'>"
              . _("Rank:") . '</label> </span>'
              . form_input ("text", "order_id", "", 'size="6" maxlength="6"');

            if (isset ($none_rk))
              {
                print "&nbsp;&nbsp;<strong> ";
                # TRANSLATORS: the argument is minimum rank value;
                # the string is used like "Rank: (must be > %s)".
                printf (_("(must be &gt; %s)"), $none_rk);
                print "</strong></p>\n";
              }

            print "<p><span class='preinput'><label for='description'>"
              . _("Description (optional):") . "</label></span><br />\n"
              . "<textarea id='description' name='description' rows='4' "
              . "cols='65' wrap='hard'></textarea></p>\n"
              . "<div class='center'>\n"
              . "<input type='submit' name='submit' value=\""
              . _("Update") . "\" />\n</div>\n</form>\n";
          } # $is_project_scope

        # If the project use custom values, propose to reset to the default.
        if (trackers_data_use_field_predefined_values ($field, $group_id))
          {
            print '<h2>' . _("Reset values") . "</h2>\n";
            print '<p>'
              . _("You are currently using custom values. If you want "
                  . "to reset values to the\ndefault ones, use the following "
                  . "form:")
              . "</p>\n\n"
              . "<form action='field_values_reset.php' method='post' "
              . "class='center'>\n"
              . form_hidden (['group_id' => $group_id, 'field' => $field])
              . '<input type="submit" name="submit" value="'
              . _("Reset values") . "\" />\n</form>\n<p>"
              . _("For your information, the default active values are:")
              . "</p>\n";

            $default_result = trackers_data_get_field_predefined_values (
              $field, '100', false, false, false
            );
            $default_rows = db_numrows ($default_result);
            $previous = false;
            if ($default_result && $default_rows > 0)
              {
                while ($fld_val = db_fetch_array ($default_result))
                  {
                    $status = $fld_val['status'];
                    $value = $fld_val['value'];
                    $description = $fld_val['description'];
                    $order = $fld_val['order_id'];

                    # Non-active value are not important here.
                    if ($status != "A")
                      continue;

                    if ($previous)
                      print ", ";

                    print "<b>$value</b> <span class='smaller'>"
                      . "($order, \"$description\")</span>";
                    $previous = true;
                  }
              }
            else
              fb (
                _("No default values found. You should report this problem "
                  . "to\nadministrators."),
                1
              );
          }
      }
    else # ! $td_select_box ($field)
      {
        # TRANSLATORS: the argument is field.
        $msg = sprintf (
          _("The field you requested '%s' is not used by your project "
            . "or you are not\nallowed to customize it"),
          $field
        );
        exit_error ($msg);
      }

    $field_id = $by_field_id ? $field: trackers_data_get_field_id ($field);
    if ($td_select_box ($field))
      {
        $sql = '
          SELECT value_id, value FROM ' . ARTIFACT . '_field_value
          WHERE group_id = ? AND bug_field_id = ?';
        # Get all the value_id - value pairs.
        $res_value = db_execute ($sql, [$group_id, $field_id]);

        $rows = db_numrows ($res_value);
        if ($rows <= 0)
          $res_value = db_execute ($sql, [100, $field_id]);

        if ($rows > 0)
          {
            $val_label = [];
            while ($val_row = db_fetch_array ($res_value))
              {
                $value_id = $val_row['value_id'];
                $value = $val_row['value'];
                $val_label[$value_id] = $value;
              }
          }
        $result = db_execute ('
          SELECT
            transition_id, from_value_id, to_value_id, is_allowed,
            notification_list
          FROM trackers_field_transition
          WHERE group_id = ? AND artifact = ?  AND field_id = ?',
          [$group_id, ARTIFACT, $field_id]
        );
        $rows = db_numrows ($result);

        if ($result && $rows > 0)
          {
            print "\n\n<p>&nbsp;</p><h2>"
              . html_anchor (_("Registered Transitions"), "registered")
              . "</h2>\n";

            $title_arr = [
              _("From"), _("To"), _("Is Allowed"),
              _("Other Field Update"), _("Carbon-Copy List"), _("Delete")
            ];

            print html_build_list_table_top ($title_arr);

            $reg_default_auth = '';
            $z = 1;
            while ($transition = db_fetch_array ($result))
              {
                $z++;
                if ($transition['is_allowed'] == 'A')
                  $allowed = _("Yes");
                else
                  $allowed = _("No");

                print '<tr class="' . utils_altrow ($z) . '">';
                if (empty ($val_label[$transition['from_value_id']]))
                  # TRANSLATORS: this refers to transitions.
                  $txt = _("* - Any");
                else
                  $txt = $val_label[$transition['from_value_id']];
                print "<td align='center'>$txt</td>\n";

                print '<td align="center">'
                  . $val_label[$transition['to_value_id']] . "</td>\n"
                  . "<td align='center'>$allowed</td>\n";

                if ($transition['is_allowed'] == 'A')
                  {
                    print '<td align="center">';
                    $registered =
                      trackers_transition_get_other_field_update (
                        $transition['transition_id']
                      );
                    $fields = '';
                    if ($registered)
                      {
                        while ($entry = db_fetch_array ($registered))
                          {
                            # Add one entry per registered other field update.
                            $ufn =  $entry['update_field_name'];
                            $l = trackers_data_get_label ($ufn);
                            $v = trackers_data_get_value (
                              $ufn, $group_id, $entry['update_value_id']
                            );
                            $fields .= "$l:$v, ";
                          }
                        $fields = trim ($fields, ", ");
                      }
                    else
                      $fields = _("Edit other fields update");

                    print utils_link (
                      $sys_home . ARTIFACT
                      . "/admin/field_values_transition-ofields-update.php?"
                      . "group=$group&amp;transition_id="
                      . $transition['transition_id'],
                      $fields
                    );
                    print "</td>\n<td align='center'>"
                      . $transition['notification_list'] . "</td>\n";
                  }
                else
                  print "<td align='center'>---------</td>\n"
                    .  "<td align='center'>--------</td>\n";
                print '<td align="center">';
                print utils_link (
                  "$server_self?group=$group&amp;transition_id="
                  . $transition['transition_id'] . '&amp;list_value=1&amp;'
                  . "func=deltransition&amp;field=$field",
                  html_image_trash (['alt' => _("Delete this transition")])
                );
                print "</td>\n</tr>\n";
              } # while ($transition = db_fetch_array ($result))
            print "</table>\n";
          } # $result && $rows > 0
        else
          {
            $reg_default_auth = '';
            printf (
              "\n\n<p>&nbsp;</p><h2>"
              # TRANSLATORS: the argument is field.
              . _("No transition defined yet for %s") . "</h2>\n",
              trackers_data_get_label ($field)
            );
          }

        print "<form action=\"$server_self#registered\" method='post'>";
        print form_hidden (
          [
            "post_transition_changes" => "y", "list_value" => "y",
            "field" => $field, "group_id" => $group_id
          ]
        );

        $result = db_execute ("
           SELECT transition_default_auth
           FROM " . ARTIFACT . "_field_usage
           WHERE group_id = ? AND bug_field_id = ?",
           [$group_id, trackers_data_get_field_id ($field)]
        );
        if (db_numrows ($result) > 0
            && db_result ($result, 0, 'transition_default_auth') == "F")
	  $transition_for_field = _("By default, for this field, the\n"
           . "transitions not registered are forbidden. This setting "
           . "can be changed when\nmanaging this field usage.");
        else
	  $transition_for_field = _("By default, for this field, the\n"
           . "transitions not registered are allowed. This setting can "
           . "be changed when\nmanaging this field usage.");
        print "\n\n<p>&nbsp;</p><h2>" . _("Create a transition") . "</h2>\n";
        print "<p>$transition_for_field</p>\n";
        print '<p>'
          . _("Once a transition created, it will be possible to set "
          . "&ldquo;Other Field\nUpdate&rdquo; for this transition.")
          . "</p>\n";

        $title_arr = [
          _("From"), _("To"), _("Is Allowed"), _("Carbon-Copy List")
        ];

        $auth_label = ['allowed', 'forbidden']; $auth_val = ['A', 'F'];

        $hdr = html_build_list_table_top ($title_arr);
        $from = '<td>'
          . trackers_field_box (
              $field, 'from', $group_id, false, false, false, 1, _("* - Any")
            )
          . "</td>\n";
        $to = '<td>'
          . trackers_field_box ($field, 'to', $group_id, false, false)
          . "</td>\n";
        print "$hdr<tr>$from$to";
        print '<td>'
          . html_build_select_box_from_arrays (
              $auth_val, $auth_label, 'allowed', 'allowed', false, 'None',
              false, 'Any', false, _("allowed or not")
            )
          . "</td>\n";
        $mlist   = "<td>\n<input type='text' value='' title=\""
          . _("Carbon-Copy List")
          . "\" name='mail_list' size='30' maxlength='60' />\n</td>\n";
        print "$mlist</tr>\n</table>\n";
        print '<div align="center"><input type="submit" name="submit" value="'
          . _("Update Transition") . "\" /></div>\n</form>\n";
      }
    else # !$td_select_box ($field)
      {
        print "\n\n<p><b>";
        # TRANSLATORS: the argument is field.
        printf (
          _("The Bug field you requested '%s' is not used by your project "
            . "or you are not\nallowed to customize it"),
          $field
        );
        print "</b></p>\n";
      }
    trackers_footer ([]);
    exit (0);
  } # if ($list_value)
if ($update_value)
  {
    # Show the form to update an existing field_value.
    # Display the List of values for a given bug field.
    trackers_header_admin (['title' => _("Edit Field Values")]);

    # Get all attributes of this value.
    $res = trackers_data_get_field_value ($fv_id);

    print "<form action=\"$server_self\" method='post'>\n"
      . form_hidden (
          [
            "post_changes" => "y", "update_value" => "y", "list_value" => "y",
            "fv_id" => $fv_id, "field" => $field, "group_id" => $group_id,
          ]
        );
    print '<p><span class="preinput"><label for="title">'
      . _("Value:") . "</label> </span><br />\n";
    print form_input (
       "text", "title",
       htmlspecialchars_decode (db_result ($res, 0, 'value'), ENT_QUOTES),
       'size="30" maxlength="60"'
    );
    print "\n&nbsp;&nbsp;\n"
      . '<span class="preinput"><label for="order_id">'
      . _("Rank:") . '</label> </span>';
    print form_input (
       "text", "order_id", db_result ($res, 0, 'order_id'),
       'size="6" maxlength="6"'
     );
    $h_selected = '';
    if (db_result ($res, 0, 'status') == 'H')
      $h_selected = ' selected="selected"';
    print "\n&nbsp;&nbsp;\n"
      . '<span class="preinput"><label for="status">'
      . _("Status:") . "</label></span>\n"
      . "<select name='status' id='status'>\n"
      # TRANSLATORS: this is field status.
      . "  <option value='A'>" . _("Active") . "</option>\n"
      . "  <option value='H'$h_selected>" . _("Hidden")
      . "</option>\n</select>\n<p>\n"
      . '<span class="preinput"><label for="description">'
      . _("Description (optional):") . "</label></span><br />\n"
      . '<textarea id="description" name="description" rows="4" '
      . 'cols="65" wrap="soft">'
      . db_result ($res, 0, 'description'). "</textarea></p>\n";
    $count = trackers_data_count_field_value_usage (
      $group_id, $field, db_result ($res, 0, 'value_id')
    );
    if ($count > 0)
      {
        print '<p class="warn">';
        printf (
          ngettext (
            "This field value applies to %s item of your tracker.",
            "This field value applies to %s items of your tracker.", $count
          ),
          $count
        );
        print ' ';
        printf (
          _("If you hide this field value, the related items will have no "
            . "value in the\nfield '%s'."),
          $field
        );
        print "</p>\n";
      }
    print "\n<div class='center'>\n"
      . '<input type="submit" name="submit" value="' . _("Submit")
      . "\" />\n</p>\n";

    trackers_footer ([]);
    exit (0);
  }
if ($create_canned || $delete_canned)
  {
    # Show existing responses and UI form.
    trackers_header_admin (['title' => _("Modify Canned Responses")]);
    $result = db_execute ('
      SELECT * FROM ' . ARTIFACT . '_canned_responses
      WHERE group_id = ? ORDER BY order_id ASC',
      [$group_id]
    );
    $rows = db_numrows ($result);

    if ($result && $rows > 0)
      {
        print "\n<h2>" . _("Existing Responses:") . "</h2>\n<p>\n";

        $title_arr = [
          _("Title"), _("Body (abstract)"), _("Rank"), _("Delete")
        ];

        print html_build_list_table_top ($title_arr);

        for ($i = 0; $i < $rows; $i++)
          {
            $s_body = substr (db_result ($result, $i, 'body'), 0, 360);
            print '<tr class="' . utils_altrow ($i) . '">'
              . "<td><a href=\"$server_self"
              . '?update_canned=1&amp;item_canned_id='
              . db_result ($result, $i, 'bug_canned_id')
              . "&amp;group_id=$group_id\">"
              . db_result ($result, $i, 'title') . "</a></td>\n"
              . "<td>$s_body...</td>\n"
              . '<td>' . db_result ($result, $i, 'order_id') . "</td>\n"
              . "<td class='center'><a href=\"$server_self"
              . '?func=delcanned&amp;item_canned_id='
              . db_result ($result, $i, 'bug_canned_id')
              . "&amp;group_id=$group_id\">"
              . html_image_trash (['alt' => _("Delete this canned response")])
              . "</a></td></tr>\n";
          }
        print "</table>\n";
      }
    else
      print "\n<h2>" . _("No canned bug responses set up yet") . "</h2>\n";
    print '<h2>' . _("Create a new response") . "</h2>\n<p>"
      . _("Creating generic quick responses can save a lot of time when "
          . "giving common\nresponses.")
      . "</p>\n<form action=\"$server_self\" method='post'>\n"
      . form_hidden (
          [
            "create_canned" => "y", "group_id" => $group_id,
            "post_changes" => "y",
          ]
        );
    print '<span class="preinput"><label for="title">'
      . _("Title:") . "</label></span><br />\n"
      . '&nbsp;&nbsp;<input type="text" name="title" id="title" value="" '
      . "size='50' maxlength='50' /><br />\n"
      . '<span class="preinput"><label for="order_id">'
      . _("Rank (useful in multiple canned responses):")
      . "</label></span><br />\n"
      . "&nbsp;&nbsp;<input type='text' name='order_id' id='order_id' "
      . "value='' maxlength='50' /><br />\n"
      . '<span class="preinput"><label for="body">' . _("Message Body:")
      . "</label></span><br />\n&nbsp;&nbsp;<textarea id='body' name='body' "
      . "rows='20' cols='65' wrap='hard'></textarea>\n<div class='center'>\n"
      . '<input type="submit" name="submit" value="' . _("Submit")
      . "\" />\n</div>\n</form>\n";
    trackers_footer ([]);
    exit (0);
  }
if ($update_canned)
  {
    #  Allow change of canned responses.
    trackers_header_admin (['title' => _("Modify Canned Response")]);

    $result = db_execute ('
      SELECT bug_canned_id, title, body, order_id
      FROM ' . ARTIFACT . '_canned_responses
      WHERE group_id = ? AND bug_canned_id = ?',
      [$group_id, $item_canned_id]
    );

    if (!$result || db_numrows ($result) < 1)
      fb (_("No such response!"), 1);
    else
      {
        print '<p>'
	  . _("Creating generic messages can save you a lot of time when giving\n"
              . "common responses.");
        print "</p>\n<p>\n<form action=\"$server_self\" method='post'>\n"
          . form_hidden (
              [
                "update_canned" => "y", "group_id" => $group_id,
                "item_canned_id" => $item_canned_id, "post_changes" => "y"
              ]
            );
        print '<span class="preinput">' . _("Title")
          . ":</span><br />\n&nbsp;&nbsp;"
          . '<input type="text" name="title" value="'
          . db_result ($result, 0, 'title')
          . "\" size='50' maxlength='50' /></p>\n<p>\n"
          . '<span class="preinput">' . _("Rank") . ":</span><br />\n"
          . '&nbsp;&nbsp;<input type="text" name="order_id" value="'
          . db_result ($result, 0, 'order_id') . "\" /></p>\n<p>\n"
          . '<span class="preinput">' . _("Message Body:") . "</span><br />\n"
          . '&nbsp;&nbsp;<textarea name="body" rows="20" cols="65" '
          . 'wrap="hard">' . db_result ($result, 0, 'body')
          . "</textarea></p>\n<div class='center'>\n"
          . "<input type='submit' name='submit' value=\"" . _("Submit")
          . "\" />\n</div>\n</form>\n";
      }
    trackers_footer ([]);
    exit (0);
  }

trackers_header_admin (['title' => _("Edit Field Values")]);

print "<br />\n";

# Loop through the list of all used fields that are project manageable.
$i = 0;
$title_arr = [_("Field Label"), _("Description"), _("Scope")];
print html_build_list_table_top ($title_arr);
while ($field_name = trackers_list_all_fields ())
  {
    if (
      !(trackers_data_is_select_box ($field_name)
        && ($field_name != 'submitted_by')
        && ($field_name != 'assigned_to')
        && trackers_data_is_used ($field_name))
    )
      continue;
    $scope_label  = _("System");
    if (trackers_data_is_project_scope ($field_name))
      $scope_label  = _("Project");
    $desc = trackers_data_get_description ($field_name);
    print '<tr class="' . utils_altrow ($i) . '">'
      . "<td><a href=\"$server_self?group_id=$group_id"
      . "&list_value=1&field=$field_name\">"
      . trackers_data_get_label ($field_name) . "</a></td>\n"
      . "<td>$desc</td>\n<td>$scope_label</td>\n</tr>\n";
    $i++;
  }

print '<tr class="' . utils_altrow ($i) . '"><td>';
print "<a href=\"$server_self?group_id=$group_id&amp;create_canned=1\">"
  . _("Canned Responses") . "</a></td>\n";
print "\n<td>"
  . _("Create or change generic quick response messages for this issue "
      . "tracker.\nThese pre-written messages can then be used to quickly "
      . "reply to item\nsubmissions.")
  . " </td>\n";
print "\n<td>" . _("Project") . "</td></tr>\n";
print "</table>\n";

trackers_footer ([]);
?>
