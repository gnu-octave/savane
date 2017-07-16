<?php
# Process field value transitions.
#
# Copyright (C) 2004 Mathieu Roy <yeupou--at--gnu.org>
# Copyright (C) 2004 Yves Perrin <yves.perrin--at--cern.ch>
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

extract(sane_import('request', array('transition_id')));
extract(sane_import('post', array('update',
'form_category_id',
'form_resolution_id',
'form_privacy',
'form_percent_complete',
'form_assigned_to',
'form_status_id',
'form_discussion_lock',
'form_comment_type_id',
)));

if (!($group_id && member_check(0,$group_id,'A')))
  {
    if (!$group_id)
      exit_no_group();
    exit_permission_denied();
  }

# Initialize global bug structures.
trackers_init($group_id);

if (!$transition_id)
  exit_missing_param();
$result = db_execute("SELECT field_id,from_value_id,to_value_id "
                     ."FROM trackers_field_transition "
                     ."WHERE group_id=? AND artifact='"
                     .ARTIFACT."' AND transition_id=?",
                     array($group_id, $transition_id));
if (!db_numrows($result))
  exit_error(_("Transition not found"));

$field_id = db_result($result, 0, 'field_id');
$field = trackers_data_get_field_name($field_id);

$registered = array();
$result2 = trackers_transition_get_other_field_update($transition_id);
while ($entry = db_fetch_array($result2))
  $registered[$entry['update_field_name']] = $entry['update_value_id'];

# Update the database.
if ($update)
  {
    while ($field_name = trackers_list_all_fields())
      {
        if (trackers_data_is_select_box($field_name)
            && ($field_name != 'submitted_by')
            && trackers_data_is_used($field_name)
            && ($field_name != $field))
          {
            $form_field="form_$field_name";
            if ($form_field && isset($$form_field))
              {
                # If there is no entry in the database, set the registered array
                # entry to 0 so it looks like the form entry "no update".
                if (empty($registered[$field_name]))
                  $registered[$field_name] = 0;

                # If we get here, we found a field that could need to be updated.
                # We first check if there is already an entry in the database for
                # this transition and field.
                # If what we have on database differs to the form, update.
                if ($registered[$field_name] != $$form_field)
                  {
                    trackers_transition_update_other_field($transition_id,
                                                           $field_name,
                                                           $$form_field);
                    $registered[$field_name] = $$form_field;
                  }
              }
          }
      }
    session_redirect($GLOBALS['sys_home'].ARTIFACT
                     .'/admin/field_values.php?group='.$group
                     .'&list_value=1&field='.$field.'#registered');
  }

# Display the UI form.

if (db_result($result, 0, 'from_value_id') == '100')
  $from = _("None");
elseif (db_result($result, 0, 'from_value_id') == '0')
  $from = _("Any");
else
  $from = trackers_data_get_value($field, $group_id,
                                  db_result($result, 0, 'from_value_id'));
$to = trackers_data_get_value($field, $group_id,
                              db_result($result, 0, 'to_value_id'));

trackers_header_admin(array('title'=>
                            _("Field Values Transitions: Others Fields Update")));
print '<h3>'
  .sprintf(_("Others Fields to update when '%s' change from '%s' to '%s':"),
           trackers_data_get_label($field),$from,$to).'</h3>';

print '<p class="warn">'
.sprintf(_("Note that if you set an automatic update of the field %s,
technicians will be able to close items by changing the value of the field
%s."),  trackers_data_get_label('status_id'), trackers_data_get_label($field))
 ."</p>\n";
print '<p>'
._("Note also the automatic update process will not override field values
specifically filled in forms. It means that if someone was able (depending on
his role in the project) to modify/set a specific field value, any automatic
update supposed to apply to this field will be disregarded.")."</p>\n";

print '<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
<input type="hidden" name="group" value="'.$group.'" />
<input type="hidden" name="transition_id" value="'.$transition_id.'" />
';

$title_arr=array();
$title_arr[]=_("Field Label");
$title_arr[]=_("New Value");

print html_build_list_table_top($title_arr);

$i = 0;
while ($field_name = trackers_list_all_fields())
  {
    if (trackers_data_is_select_box($field_name)
        && ($field_name != 'submitted_by')
        && trackers_data_is_used($field_name)
        && ($field_name != $field))
      {        
        print "\n".'<tr class="'. utils_get_alt_row_color($i)
              .'">\n<td width="25%"><span title="'
              .trackers_data_get_description($field_name).'" class="help">'
              .trackers_data_get_label($field_name)."</span></td>\n";

        # Checked to set!
	# Here, we abuse the "show any" possibility of trackers_field_box to
	# set the "No Update" possibility. It sounded more sensible to abuse
	# the 'show none', but "none" is a legitimate entry here -one could
	# want to change the field to none, if the field value exists, while
	# "any" does never make sense here.
        if (empty($registered['update_value_id']))
          $registered['update_value_id'] = null;
        if (empty($registered[$field_name]))
          $registered[$field_name] = null;
        print '<td>'.$registered['update_value_id'].
          trackers_field_box($field_name, "form_".$field_name, $group_id,
                             $registered[$field_name], false, false, true,
                             _("No automatic update")).
          "</td>\n</tr>\n";
        $i++;
      }
  }
print '</table>
<p align="center"><input type="submit" name="update" value="'._("Update").'" /></p>
';
trackers_footer(array());
?>
