<?php
# Edit field values.
#
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017, 2018 Ineiev
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

if (!($group_id && user_ismember($group_id,'A')))
  {
    if (!$group_id)
      exit_no_group();
    else
      exit_permission_denied();
  }
# Initialize global bug structures
trackers_init($group_id);

# ################################ Update the database

if ($func == "deltransition")
  {
    $result = db_execute("DELETE FROM trackers_field_transition
                          WHERE transition_id = ? LIMIT 1",
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
# an existing value.
# Deleted Canned doesn't need a form, so let switch
# into this code.

    if ($create_value)
      {
# A form was posted to update a field value.
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
          fb(_("Empty field value is not allowed"), 1);
      }
    elseif ($update_value)
      {
# A form was posted to update a field value.
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
          fb(_("Empty field value is not allowed"), 1);
      }
    elseif ($create_canned)
      {
# A form was posted to create a canned response.
        $result = db_autoexecute(ARTIFACT.'_canned_responses',
          array(
            'group_id' => $group_id,
            'title' => htmlspecialchars($title),
            'body' => htmlspecialchars($body),
            'order_id' => $order_id,
          ), DB_AUTOQUERY_INSERT);
        if (!$result)
          fb(_("Error inserting canned bug response"),1);
        else
          fb(_("Canned bug response inserted"));
      }
    elseif ($update_canned)
      {
# A form was posted to update a canned response.
        $result = db_autoexecute(ARTIFACT.'_canned_responses',
          array(
            'title' => htmlspecialchars($title),
            'body' => htmlspecialchars($body),
            'order_id' => $order_id,
          ), DB_AUTOQUERY_UPDATE,
          'group_id = ? AND bug_canned_id = ?',
          array($group_id,  $item_canned_id));
        if (!$result)
          fb(_("Error updating canned bug response"),1);
        else
          fb(_("Canned bug response updated"));
      }
# Delete Response.
    elseif ($delete_canned == '1')
      {
        $result = db_execute("DELETE FROM ".ARTIFACT."_canned_responses "
                             ."WHERE group_id=? AND bug_canned_id=?",
                             array($group_id, $item_canned_id));
        if (!$result)
          fb(_("Error deleting canned bug response"),1);
        else
          fb(_("Canned bug response deleted"));
      }
  }

$field_id = ($by_field_id ? $field : trackers_data_get_field_id($field));

if ($to != $from) {
# A form was posted to update or create a transition.
  $res_value = db_execute(
   "SELECT from_value_id, to_value_id, is_allowed,notification_list
    FROM trackers_field_transition
    WHERE group_id = ? AND artifact = ? AND field_id = ?
      AND from_value_id = ? AND to_value_id = ?",
   array($group_id, ARTIFACT, $field_id,
         $from, $to));
  $rows=db_numrows($res_value);

# If no entry for this transition, create one.
  if ($rows == 0)
    {
      $result = db_autoexecute('trackers_field_transition',
        array(
          'group_id' => $group_id,
          'artifact' => ARTIFACT,
          'field_id' => $field_id,
          'from_value_id' => $from,
          'to_value_id' => $to,
          'is_allowed' => $allowed,
          'notification_list' => $mail_list,
        ), DB_AUTOQUERY_INSERT);

      if (db_affected_rows($result) < 1)
        fb(_("Insert failed"), 1);
      else
        fb(_("New transition inserted"));
    }
  else
    {
# Update the existing entry for this transition.
      $result = db_autoexecute('trackers_field_transition',
         array(
           'is_allowed' => $allowed,
           'notification_list' => $mail_list,
         ), DB_AUTOQUERY_UPDATE,
         'group_id = ? AND artifact = ? AND field_id = ?
                       AND from_value_id = ? AND to_value_id = ?',
         array($group_id, ARTIFACT, $field_id, $from, $to));

      if (db_affected_rows($result) < 1)
        fb(_("Update of transition failed"), 1);
      else
        fb(_("Transition updated"));
    }
}

# ################################  Display the UI form.
if ($list_value)
  {
# Display the List of values for a given bug field.

# TRANSLATORS: the argument is field label.
    $hdr = sprintf(_("Edit Field Values for '%s'"),
                   trackers_data_get_label($field));

    if (trackers_data_get_field_id($field)
        && trackers_data_is_select_box($field))
      {
        # First check that this field is used by the project and
        # it is in the project scope.

        $is_project_scope = trackers_data_is_project_scope($field);

        trackers_header_admin(array ('title'=>$hdr));

        print '<h1>'._("Field Label:").' '.trackers_data_get_label($field)
."</h1>\n<p>".'<span class="smaller">('
.utils_link($GLOBALS['sys_home'].ARTIFACT.'/admin/field_usage.php?group='
            .$group.'&amp;update_field=1&amp;field='.$field,
            _("Jump to this field usage")).")</span></p>\n";

        $result = trackers_data_get_field_predefined_values($field, $group_id,
                                                            false,false,false);
        $rows = db_numrows($result);

        if ($result && $rows > 0)
          {
            print "\n<h2>"._("Existing Values")."</h2>\n";

            $title_arr=array();
            if (!$is_project_scope)
              $title_arr[]=_('ID');
            $title_arr[]=_("Value label");
            $title_arr[]=_("Description");
            $title_arr[]=_("Rank");
            $title_arr[]=_("Status");
            $title_arr[]=_("Occurrences");

            $hdr = html_build_list_table_top ($title_arr);

            $ia = $ih = 0;
            $status_stg = array('A' =>
# TRANSLATORS: this is field status.
                                 _("Active"),
                                'P' =>
# TRANSLATORS: this is field status.
                                 _("Permanent"),
                                'H' =>
# TRANSLATORS: this is field status.
                                 _("Hidden"));

# Display the list of values in 2 blocks : active first
# Hidden second.
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
                  $usage = trackers_data_count_field_value_usage($group_id,
                                                                 $field,
                                                                 $value_id);
                $html = '';
# Keep the rank of the 'None' value in mind if any (see below).
                if ($value == 100)
                  $none_rk = $order_id;

# Show the value ID only for system wide fields which
# value id are fixed and serve as a guide.
                if (!$is_project_scope)
                  $html .='<td>'.$value_id."</td>\n";

# The permanent values cant be modified (No link).
                if ($status == 'P')
                  {
                    $html .= '<td>'.$value."</td>\n";
                  }
                else
                  {
                    $html .= '<td><a href="'.htmlentities ($_SERVER['PHP_SELF'])
                      .'?update_value=1'
                      .'&fv_id='.$item_fv_id.'&field='.$field
                      .'&group_id='.$group_id.'">'.$value."</a></td>\n";
                  }

                $html .= '<td>'.$description."&nbsp;</td>\n"
                  .'<td align="center">'.$order_id."</td>\n"
                  .'<td align="center">'.$status_stg[$status]."</td>\n";

                if ($status == 'H' && $usage > 0)
                  {
                    $html .= '<td align="center"><strong class="warn">'.$usage
                             ."</strong></td>\n";
                  }
                else
                  {
                    $html .= '<td align="center">'.$usage."</td>\n";
                  }

                if ($status == 'A' || $status == 'P')
                  {
                    $html = '<tr class="'
                      .utils_get_alt_row_color($ia) .'">'.$html."</tr>\n";
                    $ia++;
                    $ha .= $html;
                  }
                else
                  {
                    $html = '<tr class="'
                      .utils_get_alt_row_color($ih) .'">'.$html."</tr>\n";
                    $ih++;
                    $hh .= $html;
                  }

              }

# Display the list of values now.
            if ($ia == 0)
              {
                $hdr = '<p>'
._("No active value for this field. Create one or reactivate a hidden value (if
any)")."</p>\n".$hdr;
              }
            else
              {
                $ha = '<tr><td colspan="4" class="center"><strong>'
                      ._("---- ACTIVE VALUES ----")."</strong></tr>\n".$ha;
              }
            if ($ih)
              {
                $hh = "<tr><td colspan=\"4\"> &nbsp;</td></tr>\n"
                      .'<tr><td colspan="4"><center><strong>'
                      ._("---- HIDDEN VALUES ----")."</strong></center></tr>\n"
                      .$hh;
              }
            print $hdr.$ha.$hh."</table>\n";
          }
        else
          {
# TRANSLATORS: the  argument is field label.
            printf ("\n<h1>"._("No values defined yet for %s")
                   ."</h1>\n",trackers_data_get_label($field));
          }

# Only show the add value form if this is a project scope field.
        if ($is_project_scope)
          {
            print '<h2>'._("Create a new field value")."</h2>\n";

            if ($ih)
              {
                print '<p>'
._("Before you create a new value make sure there isn't one in the hidden list
that suits your needs.")."</p>\n";
              }

# yeupou--gnu.org 2004-09-12: a red star should mark mandatory fields.

            print '
      <form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
      <input type="hidden" name="post_changes" value="y" />
      <input type="hidden" name="create_value" value="y" />
      <input type="hidden" name="list_value" value="y" />
      <input type="hidden" name="field" value="'.$field.'" />
      <input type="hidden" name="group_id" value="'.$group_id.'" />
      <span class="preinput"><label for="title">'._("Value:").'</label> </span>'
              .form_input("text", "title", "", 'size="30" maxlength="60"').'
      &nbsp;&nbsp;
      <span class="preinput"><label for="order_id">'._("Rank:")
              .'</label> </span>'
              .form_input("text", "order_id", "", 'size="6" maxlength="6"');

            if (isset($none_rk))
              {
                print "&nbsp;&nbsp;"
                      ."<strong> "
                     # TRANSLATORS: the argument is minimum rank value;
                     # the string is used like "Rank: (must be > %s)".
                      .sprintf(_("(must be &gt; %s)"),$none_rk)
                      ."</strong></p>\n";
              }

            print '
      <p>
      <span class="preinput"><label for="description">'
      ._("Description (optional):").'</label></span><br />
      <textarea id="description" name="description" rows="4" cols="65"
                wrap="hard"></textarea></p>
      <div class="center">
        <input type="submit" name="submit" value="'._("Update").'" />
      </div>
      </form>';
          }

        # If the project use custom values, propose to reset to the default.
        if (trackers_data_use_field_predefined_values($field,$group_id))
          {
            print '<h2>'._("Reset values")."</h2>\n";
            print '<p>'
._("You are currently using custom values. If you want to reset values to the
default ones, use the following form:").'</p>

<form action="field_values_reset.php" method="post" class="center">
<input type="hidden" name="group_id" value="'.$group_id.'" />
<input type="hidden" name="field" value="'.$field.'" />
<input type="submit" name="submit" value="'._("Reset values").'" />
</form>
<p>'._("For your information, the default active values are:")."</p>\n";

            $default_result =
              trackers_data_get_field_predefined_values($field, '100',
                                                        false,false,false);
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
                      continue;

                    if ($previous)
                      print ", ";

                    print '<strong>'.$value.'</strong> <span class="smaller">('
                          .$order.', "'.$description.'")</span>';
                    $previous = true;
                  }
              }
            else
              fb(
_("No default values found. You should report this problem to
administrators."), 1);
          }
      }
    else
      exit_error(sprintf(
# TRANSLATORS: the argument is field.
_("The field you requested '%s' is not used by your project or you are not
allowed to customize it"),$field));

# Transitions
# yeupou--gnu.org 2004-09-12: where the hell is by_field_id set?
    $field_id = ($by_field_id ? $field : trackers_data_get_field_id($field));
    if (trackers_data_get_field_id($field)
        && trackers_data_is_select_box($field))
      {
# First get all the value_id - value pairs.
        $res_value = db_execute('SELECT value_id,value FROM '.ARTIFACT
                                .'_field_value
                                 WHERE group_id = ? AND bug_field_id = ?',
                                array($group_id, $field_id));
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
            $res_value = db_execute('SELECT value_id,value FROM '.ARTIFACT
                                    .'_field_value
                                     WHERE group_id = 100 AND bug_field_id = ?',
                                    array($field_id));
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

        $result = db_execute('SELECT transition_id,from_value_id,to_value_id,'
                             .'is_allowed,notification_list
                              FROM trackers_field_transition
                              WHERE group_id = ? AND artifact = ?
                              AND field_id = ?',
                             array($group_id, ARTIFACT, $field_id));
        $rows = db_numrows($result);

        if ($result && $rows > 0)
          {
            print "\n\n<p>&nbsp;</p><h2>"
              .html_anchor(_("Registered Transitions"),"registered")
              ."</h2>\n";

            $title_arr=array();
            $title_arr[]=_("From");
            $title_arr[]=_("To");
            $title_arr[]=_("Is Allowed");
            $title_arr[]=_("Other Fields Update");
            $title_arr[]=_("Carbon-Copy List");
            $title_arr[]=_("Delete");

            $hdr = html_build_list_table_top ($title_arr);
            print $hdr;

            $reg_default_auth = '';
            $z = 1;
            while ($transition = db_fetch_array($result))
              {
                $z++;
                if ($transition['is_allowed'] == 'A')
                  $allowed = _("Yes");
                else
                  $allowed = _("No");

                print '<tr class="'.utils_altrow($z).'">';
                if (!empty($val_label[$transition['from_value_id']]))
                  print '<td align="center">'
                    .$val_label[$transition['from_value_id']]."</td>\n";
                else
                  # TRANSLATORS: this refers to transitions.
                  print '<td align="center">'._("* - Any")."</td>\n";

                print '<td align="center">'
                  .$val_label[$transition['to_value_id']]."</td>\n"
                  .'<td align="center">'.$allowed."</td>\n";

                if ($transition['is_allowed'] == 'A')
                  {
                    print '<td align="center">';
# Get list of registered field updates.
                    $registered =
      trackers_transition_get_other_field_update($transition['transition_id']);
# Make sure $content is clean.
                    $content = '';
# No result? Print only a link.
                    if ($registered)
                      {
                        while ($entry = db_fetch_array($registered))
# Add one entry per registered other field update.
                          $content .=
                            trackers_data_get_label($entry['update_field_name'])
                            .":"
                            .trackers_data_get_value($entry['update_field_name'],
                            $group_id, $entry['update_value_id']).", ";
# Remove extra comma.
                        $content = trim($content, ", ");
                      }
                    else
                      {
                        $content = _("Edit other fields update");
                      }

                    print utils_link($GLOBALS['sys_home'].ARTIFACT
  ."/admin/field_values_transition-ofields-update.php?group=".$group
  ."&amp;transition_id=".$transition['transition_id'], $content);
                    print '</td>
<td align="center">'
                      .$transition['notification_list']."</td>\n";
                  }
                else
                  {
                    print '<td align="center">---------</td>
<td align="center">--------</td>
';
                  }
                print '<td align="center">'
.utils_link(htmlentities ($_SERVER['PHP_SELF'])
.'?group='.$group.'&amp;func=deltransition&amp;transition_id='
.$transition['transition_id'].'&amp;list_value=1&amp;field='.$field,
'<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
.'.theme/misc/trash.png" border="0" alt="'._("Delete this transition").'" />')
."</td>\n";
                print "</tr>\n";
              }
            print "</table>\n";
          }
        else
          {
            $reg_default_auth = '';
            printf ("\n\n<p>&nbsp;</p><h2>"
                    # TRANSLATORS: the argument is field.
                    ._("No transition defined yet for %s")."</h2>\n",
                    trackers_data_get_label($field));
          }

        print '
<form action="'.htmlentities ($_SERVER['PHP_SELF']).'#registered" method="post">
<input type="hidden" name="post_transition_changes" value="y" />
<input type="hidden" name="list_value" value="y" />
<input type="hidden" name="field" value="'.$field.'" />
<input type="hidden" name="group_id" value="'.$group_id.'" />';

        $result = db_execute("SELECT transition_default_auth FROM ".ARTIFACT
                             ."_field_usage "
                             ." WHERE group_id=? AND bug_field_id=?",
                             array($group_id,
                                   trackers_data_get_field_id($field)));
        if (db_numrows($result) > 0
            and db_result($result, 0, 'transition_default_auth') == "F")
          {
	    $transition_for_field = _("By default, for this field, the
transitions not registered are forbidden. This setting can be changed when
managing this field usage.");
          }
        else
          {
	    $transition_for_field = _("By default, for this field, the
transitions not registered are allowed. This setting can be changed when
managing this field usage.");
          }
        print "\n\n<p>&nbsp;</p><h2>"._("Create a transition")."</h2>\n";
        print "<p>$transition_for_field</p>\n";
        print '<p>'
._("Once a transition created, it will be possible to set &ldquo;Other Field
Update&rdquo; for this transition.")."</p>\n";

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
        $from = '<td>'.trackers_field_box($field,'from',$group_id,false,
                                          false, false, 1, _("* - Any"))
                ."</td>\n";
        $to = '<td>'.trackers_field_box($field,'to',$group_id,false,false)
              ."</td>\n";
        print $hdr.'<tr>'.$from.$to;
        print '<td>'.html_build_select_box_from_arrays ($auth_val,$auth_label,
                                                        'allowed', 'allowed',
                                                        false, 'None', false,
                                                        'Any', false,
                                                        _("allowed or not"))
."</td>\n";
        $mlist   = '<td>'.'
<input type="text" title="'._("Carbon-Copy List")
       .'" name="mail_list" value="" size="30" maxlength="60" />
</td>
';
        print $mlist."</tr>\n</table>\n";
        print '<div align="center"><input type="submit" name="submit" value="'
              ._("Update Transition").'" /></div>
</form>
';
      }
    else
      {
        print "\n\n<p>&nbsp;</p><h2>";
        printf (
# TRANSLATORS: the argument is field.
_("The Bug field you requested '%s' is not used by your project or you are not
allowed to customize it"),$field);
        print "</h2>\n";
      }
  }
elseif ($update_value)
  {
# Show the form to update an existing field_value.
# Display the List of values for a given bug field.

    trackers_header_admin(array ('title'=>_("Edit Fields Values")));

# Get all attributes of this value.
    $res = trackers_data_get_field_value($fv_id);

    print '<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
    <input type="hidden" name="post_changes" value="y" />
    <input type="hidden" name="update_value" value="y" />
    <input type="hidden" name="list_value" value="y" />
    <input type="hidden" name="fv_id" value="'.$fv_id.'" />
    <input type="hidden" name="field" value="'.$field.'" />
    <input type="hidden" name="group_id" value="'.$group_id.'" />
    <p><span class="preinput"><label for="title">'
._("Value:").'</label> </span><br />
'
.form_input("text", "title", db_result($res,0,'value'),
            'size="30" maxlength="60"')
.'
    &nbsp;&nbsp;
    <span class="preinput"><label for="order_id">'._("Rank:").'</label> </span>'
.form_input("text", "order_id", db_result($res,0,'order_id'),
            'size="6" maxlength="6"').'
    &nbsp;&nbsp;
    <span class="preinput"><label for="status">'
      ._("Status:").'</label></span>
    <select name="status" id="status">
         <option value="A">'
# TRANSLATORS: this is field status.
      ._("Active").'</option>
         <option value="H"'
.((db_result($res,0,'status') == 'H') ? ' selected="selected"':'').'>'
# TRANSLATORS: this is field status.
      ._("Hidden").'</option>
    </select>
    <p>
    <span class="preinput"><label for="description">'
   ._("Description (optional):").'</label></span><br />
    <textarea id="description" name="description" rows="4" cols="65" wrap="soft">'
.db_result($res,0,'description').'</textarea></p>';
    $count = trackers_data_count_field_value_usage($group_id, $field,
                                                   db_result($res,0,
                                                             'value_id'));
    if ($count > 0)
      {
        print '<p class="warn">';
        printf(ngettext(
"This field value applies to %s item of your tracker.",
"This field value applies to %s items of your tracker.", $count)." ", $count);
        printf(
_("If you hide this field value, the related items will have no value in the
field '%s'."), $field)."</p>\n";
      }
    print '
    <div class="center">
      <input type="submit" name="submit" value="'._("Submit").'" />
    </p>
';
  }
elseif ($create_canned || $delete_canned)
  {
    #   Show existing responses and UI form.
    trackers_header_admin(array ('title'=>_("Modify Canned Responses")));
    $result=db_execute('SELECT * FROM '.ARTIFACT.'_canned_responses
                        WHERE group_id = ? ORDER BY order_id ASC',
                       array($group_id));
    $rows=db_numrows($result);

    if($result && $rows > 0)
      {
        #   Links to update pages.
        print "\n<h2>"._("Existing Responses:")."</h2>\n<p>\n";

        $title_arr=array();
        $title_arr[]=_("Title");
        $title_arr[]=_("Body (abstract)");
        $title_arr[]=_("Rank");
        $title_arr[]=_("Delete");

        print html_build_list_table_top ($title_arr);

        for ($i=0; $i < $rows; $i++)
          {
# FIXME: delete should use the basket, like it is done in many
# other places.
            print '<tr class="'. utils_get_alt_row_color($i) .'">'
              .'<td><a href="'.htmlentities ($_SERVER['PHP_SELF'])
              .'?update_canned=1&amp;item_canned_id='
              .db_result($result, $i, 'bug_canned_id').'&amp;group_id='
              .$group_id.'">'
              .db_result($result, $i, 'title')."</a></td>\n"
              .'<td>'.substr(db_result($result, $i, 'body'),0,360)."...</td>\n"
              .'<td>'.db_result($result, $i, 'order_id')."</td>\n"
              .'<td class="center"><a href="'
              .htmlentities ($_SERVER['PHP_SELF'])
              .'?delete_canned=1&amp;item_canned_id='
              .db_result($result, $i, 'bug_canned_id').'&amp;group_id='
              .$group_id.'"><img src="'.$GLOBALS['sys_home'].'images/'
              .SV_THEME.'.theme/misc/trash.png" border="0" alt="'
              ._("Delete this canned answer").'" />
              </a></td></tr>
';
          }
        print "</table>\n";
      }
    else
      print "\n<h2>"._("No canned bug responses set up yet")."</h2>\n";
#       Escape to print the add response form.

    print '<h2>'._("Create a new response").'</h2>
<p>
'
._("Creating generic quick responses can save a lot of time when giving common
responses.")
.'</p>
<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
<input type="hidden" name="create_canned" value="y" />
<input type="hidden" name="group_id" value="'.$group_id.'" />
<input type="hidden" name="post_changes" value="y" />
<span class="preinput"><label for="title">'._("Title:").'</label></span><br />
&nbsp;&nbsp;<input type="text" name="title" id="title" value="" '
.'size="50" maxlength="50" /><br />
<span class="preinput"><label for="order_id">'
._("Rank (useful in multiple canned responses):").'</label></span><br />
&nbsp;&nbsp;<input type="text" name="order_id" id="order_id"
                   value="" maxlength="50" /><br />
<span class="preinput"><label for="body">'._("Message Body:")
      .'</label></span><br />
&nbsp;&nbsp;<textarea id="body" name="body" rows="20" cols="65"
                      wrap="hard"></textarea>
<div class="center">
  <input type="submit" name="submit" value="'._("Submit").'" />
</div>
</form>
';
  }
elseif ($update_canned)
  {
#  Allow change of canned responses.
    trackers_header_admin(array ('title'=>_("Modify Canned Response")));

    $result=db_execute('SELECT bug_canned_id,title,body,order_id
                        FROM '.ARTIFACT.'_canned_responses
                        WHERE group_id = ? AND bug_canned_id = ?',
                       array($group_id, $item_canned_id));

    if (!$result || db_numrows($result) < 1)
      {
        fb(_("No such response!"),1);
      }
    else
      {
# Escape to print update form.
        print '<p>'
	  ._("Creating generic messages can save you a lot of time when giving
common responses.").'</p>
<p>
<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
<input type="hidden" name="update_canned" value="y" />
<input type="hidden" name="group_id" value="'.$group_id.'" />
<input type="hidden" name="item_canned_id" value="'.$item_canned_id.'" />
<input type="hidden" name="post_changes" value="y" />
<span class="preinput">'._("Title").':</span><br />
&nbsp;&nbsp;<input type="text" name="title" value="'
.db_result($result,0,'title').'" size="50" maxlength="50" /></p>
<p>
<span class="preinput">'._("Rank").':</span><br />
&nbsp;&nbsp;<input type="text" name="order_id" value="'
.db_result($result,0,'order_id').'" /></p>
<p>
<span class="preinput">'._("Message Body:").'</span><br />
&nbsp;&nbsp;<textarea name="body" rows="20" cols="65" wrap="hard">'
.db_result($result,0,'body').'</textarea></p>
<div class="center">
  <input type="submit" name="submit" value="Submit" />
</div>
</form>
';
      }
  }
else
  {
######## Complete list of fields.
    trackers_header_admin(array ('title'=>_("Edit Fields Values")));

    # Add space to avoid overlaps.
    print "<br />\n";

# Loop through the list of all used fields that are project manageable.
    $i=0;
    $title_arr=array();
    $title_arr[]=_("Field Label");
    $title_arr[]=_("Description");
    $title_arr[]=_("Scope");
    print html_build_list_table_top ($title_arr);
    while ($field_name = trackers_list_all_fields())
      {
        if (trackers_data_is_select_box($field_name)
            && ($field_name != 'submitted_by')
            && ($field_name != 'assigned_to')
            && trackers_data_is_used($field_name))
          {
            $scope_label  = (trackers_data_is_project_scope($field_name)
                             ? _("Project"): _("System"));

            print '<tr class="'. utils_get_alt_row_color($i) .'">'
              .'<td><a href="'.htmlentities ($_SERVER['PHP_SELF'])
              .'?group_id='.$group_id
              .'&list_value=1&field='.$field_name.'">'
              .trackers_data_get_label($field_name)."</a></td>\n"
              ."\n<td>".trackers_data_get_description($field_name)."</td>\n"
              ."\n<td>".$scope_label."</td>\n"
              ."</tr>\n";
            $i++;
          }
      }

# Now the special canned response field.
    print '<tr class="'. utils_get_alt_row_color($i) .'">';
    print "<td><a href=\"";
    print htmlentities ($_SERVER['PHP_SELF'])
."?group_id=$group_id&amp;create_canned=1\">"
._("Canned Responses")."</a></td>\n";
    print "\n<td>"
._("Create or change generic quick response messages for this issue tracker.
These pre-written messages can then be used to quickly reply to item
submissions.")." </td>\n";
    print "\n<td>"._("Project")."</td></tr>\n";
    print "</table>\n";
  }

trackers_footer(array());
?>
