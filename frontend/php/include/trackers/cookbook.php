<?php
# Cookbook functions
#
# Copyright (C) 2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2020 Ineiev
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

# This will return an array of possible values, like
#      anonymous, logged-in...
function cookbook_audience_possiblevalues()
{
  return array("anonymous" => _("Anonymous Users"),
               "loggedin" => _("Logged-in Users"),
               "members" => _("All Project Members"),
               "technicians" => _("Project Members who are technicians"),
               "managers" => _("Project Members who are managers"));
}

# Same for context.
# Guess all the possible values in theory on the site for a project.
function cookbook_context_project_possiblevalues()
{
  return array("project" => _("Project Main Pages"),
               "homepage" => _("Project Homepage"),
               "cookbook" => _("Cookbook"),
               "download" => _("Download Area"),
               "support" => _("Support Tracker"),
               "bugs" => _("Bug Tracker"),
               "task" => _("Task Manager"),
               "patch" => _("Patch Tracker"),
               "news" => _("News Manager"),
               "mail" => _("Mailing Lists"),
               "cvs" => _("Source Code Manager: CVS Repositories"),
               "arch" => _("Source Code Manager: GNU Arch Repositories"),
               "svn" => _("Source Code Manager: Subversion Repositories"));
}

# Guess all the possible values in theory on the site for the site admin.
function cookbook_context_site_possiblevalues()
{
  return array("my" => _("User Personal Area"),
               "stats" => _("Site Statistics"),
               "siteadmin" => _("Site Administration"));
}

# Guess all the impossible values in reality for a project.
function cookbook_context_project_impossiblevalues()
{
  global $group_id;

  # Site values are per definition impossible for a normal project.
  $array_impossible = cookbook_context_site_possiblevalues();

  # Impossible values are values of unactivated features for the project.
  $array_possible = cookbook_context_project_possiblevalues();
  $project = project_get_object($group_id);

  foreach ($array_possible as $feature => $value)
    {
      # Cookbook cannot be deactivated.
      if ($feature == 'cookbook')
        continue;

      # Project main pages cannot be deactivated.
      if ($feature == 'project')
        continue;

      if (!$project->Uses($feature))
        {
          $array_impossible[$feature] = 1;
        }
    }
  return $array_impossible;
}

# Find out the really possible value in the current context.
function cookbook_context_possiblevalues()
{
  global $group_id, $sys_group_id;

  # All projec-wide possible values, ordered in a clean way.
  $array_possible = cookbook_context_project_possiblevalues();

  if ($group_id != $sys_group_id)
    {
      # If we are in a normal group, remove impossible values.
      # For instance, remove all unused features.
      $array_impossible = cookbook_context_project_impossiblevalues();

      foreach ($array_impossible as $key => $value)
        unset($array_possible[$key]);
    }
  else
    {
      # For the site admin group, present all possible values.
      $array_possible = array_merge(cookbook_context_site_possiblevalues(),
                                    $array_possible);
    }

  reset($array_possible);
  return $array_possible;
}

# Same for subcontext.
function cookbook_subcontext_possiblevalues()
{
  return array("browsing" => _("Browsing"),
               "postitem" => _("Posting New Items"),
               "edititem" => _("Editing Items, Posting Comments"),
               "search" => _("Doing Searches"),
               "configure" => _("Configuring Features"));
}

# Return a bit of form that should be used inside the item post/edit forms.
function cookbook_build_form ($which="audience")
{
  global $item_id, $group_id, $previous_form_bad_fields;

  $possiblevalues = array();
  if ($which == "audience")
    {
      $possiblevalues = cookbook_audience_possiblevalues();
    }
  if ($which == "context")
    {
      $possiblevalues = cookbook_context_possiblevalues();
    }
  if ($which == "subcontext")
    {
      $possiblevalues = cookbook_subcontext_possiblevalues();
    }

  # If there is an item id available, it means we are editing an item
  # and we want to previous results from the database.
  if ($item_id)
    {
      $result = db_execute("SELECT * FROM cookbook_context2recipe
                            WHERE recipe_id=? AND group_id=? LIMIT 1",
                           array($item_id, $group_id));
    }

  $content = '';

  foreach ($possiblevalues as $field => $label)
    {
      $checked = '';

      # Take into account database content.
      if ($item_id && db_result($result, 0, $which."_".$field) == 1)
        $checked = ' checked="checked"';

      # Ultimately take into account what was posted, in the case the form
      # was reprovided to the user because he forgot mandatory fields.
      if ($previous_form_bad_fields)
        {
          if ($_POST["recipe_".$which."_".$field] == true)
            $checked = ' checked="checked"';
          else
            unset($checked);
        }

      if (!defined('PRINTER'))
        {
          # Normal output.
          $content .= '<input type="checkbox" name="recipe_'.$which.'_'.$field
                      .'"'.$checked.' />'.$label."<br />\n";
        }
      else
        {
          # Printer mode output, show only selected entries.
          if ($checked)
            $content .= $label.'<br />';

        }
    }
  # Remove the extra line break.
  $content = rtrim($content, '<br />');
  return $content;
}

# Describe a field type.
function cookbook_describe ($which="audience")
{
  unset($text);
  if ($which == "audience")
    {
      $text = _("Defines which users will actually get such recipe showing up
as related recipe while browsing the site. It will not prevent other users to
see the recipe in the big list inside the Cookbook.");
    }
  if ($which == "context")
    {
      $text = _("Defines on which pages such recipe will show up as related
recipe. It will not prevent other users to see the recipe in the big list
inside the Cookbook.");
    }
  if ($which == "subcontext")
    {
      $text = _("Defines while doing which actions such recipe will show up as
related recipe. It will not prevent other users to see the recipe in the big
list inside the Cookbook.");
    }
  return $text;
}

# Use cookbook_build_form to return a nice form that can be included in
# mod and post forms.
function cookbook_print_form ()
{
  global $j, $fields_per_line, $i, $row_class, $field_class;

  # Field getting one line for itself
  #  |            Audience                     |

  # Prepare next background color change.
  $j++;

  print "<tr".$row_class.">".
    '<td valign="middle" '.$field_class
    .' width="15%"><span class="preinput"><span class="help" title="'
    .cookbook_describe("audience").'">'._("Audience:")."</span></span></td>\n"
    .'<td valign="middle" '.$field_class.' colspan="'.(2*$fields_per_line-1)
    .'" width="75%">'
    .cookbook_build_form("audience")."</td>\n"
    ."</tr>\n";

  $i = 0;

  # Field getting half of a line for itself
  #  | context, kind of pages | context, kind of action
  #       (CONTEXT)                   (SUBCONTEXT)

  # Change background color.
  unset($row_class);
  if ($j % 2)
    {
      $row_class = ' class="'.utils_altrow($j+1).'"';
    }

  # Prepare next background color change.
  $j++;

  print ($i % $fields_per_line ? '':"<tr".$row_class.">");
  print '<td valign="middle"'.$field_class
    .' width="15%"><span class="preinput"><span class="help" title="'
    .cookbook_describe("context").'">'._("Feature:")
    .'</span></span></td><td valign="middle"'.$field_class
    .' width="35%">'.cookbook_build_form("context")."</td>\n";
  $i++;
  print ($i % $fields_per_line ? '':"\n</tr>\n");
  print ($i % $fields_per_line ? '':"<tr".$row_class.">");
  print '<td valign="middle"'.$field_class
    .' width="15%"><span class="preinput"><span class="help" title="'
    .cookbook_describe("subcontext").'">'._("Action:")
    .'</span></span></td><td valign="middle"'.$field_class.' width="35%">'
    .cookbook_build_form("subcontext")."</td>\n";
  $i++;
  print ($i % $fields_per_line ? '':"</tr>\n");

  $i = 0;
  # Change background color.
  unset($row_class);
  if ($j % 2)
    {
      $row_class = ' class="'.utils_altrow($j+1).'"';
    }
}

# Handle update or create of cookbook specific things in items:
#     the related items links.
function cookbook_handle_update($item_id, $group_id)
{
  global $change_exists;

  $in = sane_import('post',
    array(
      'recipe_audience_technicians',
      'recipe_audience_managers',
      'recipe_audience_anonymous',
      'recipe_audience_loggedin',
      'recipe_audience_members',
      'recipe_context_stats',
      'recipe_context_siteadmin',
      'recipe_context_my',
      'recipe_context_project',
      'recipe_context_homepage',
      'recipe_context_download',
      'recipe_context_mail',
      'recipe_context_cvs',
      'recipe_context_arch',
      'recipe_context_svn',
      'recipe_context_support',
      'recipe_context_bugs',
      'recipe_context_task',
      'recipe_context_patch',
      'recipe_context_cookbook',
      'recipe_context_news',
      'recipe_subcontext_browsing',
      'recipe_subcontext_search',
      'recipe_subcontext_postitem',
      'recipe_subcontext_edititem',
      'recipe_subcontext_configure',
  ));

  # Pass through all the available/configurable fields.
  $cookbook_upd_list = array();

  # Find out the targetted audience.
  $audience_cases = array();
  $possiblevalues = cookbook_audience_possiblevalues();

  foreach ($possiblevalues as $field => $val)
    {
      $value = 0;
      if ($in["recipe_audience_".$field])
        $value = 1;
      $cookbook_upd_list["audience_$field"] = $value;
    }

  # Find out the targetted context (feature).
  $context_cases = array();
  $possiblevalues = cookbook_context_possiblevalues();

  foreach ($possiblevalues as $field => $val)
    {
      $value = 0;
      if ($in["recipe_context_".$field])
        $value = 1;
      $cookbook_upd_list["context_$field"] = $value;
    }

  # Find out the targetted subcontext (action).
  $subcontext_cases = array();
  $possiblevalues = cookbook_subcontext_possiblevalues();

  foreach ($possiblevalues as $field => $val)
    {
      $value = 0;
      if ($in["recipe_subcontext_".$field])
        $value = 1;
      $cookbook_upd_list["subcontext_$field"] = $value;
    }

  # Create from scratch a row, to be able to do a simple update afterwards.
  if (!db_numrows(db_execute("SELECT context_id FROM cookbook_context2recipe
                              WHERE recipe_id=? AND group_id=? LIMIT 1",
                             array($item_id, $group_id))))
    {
      db_autoexecute('cookbook_context2recipe',
                     array('recipe_id' => $item_id,
                           'group_id' => $group_id),
                     DB_AUTOQUERY_INSERT);
    }
  # Now do an update.
  $result = db_affected_rows(db_autoexecute('cookbook_context2recipe',
                                            $cookbook_upd_list,
                                            DB_AUTOQUERY_UPDATE,
                                            "recipe_id=? AND group_id=?",
                                            array($item_id, $group_id)));

  # If there was affected rows, it means we did an update
  # (ignoring the very unusual case where the SQL would fail).
  if ($result)
    {
      $change_exists = 1;
      fb(_("Audience/Feature/Action updated"));
      trackers_data_add_history("Audience/Feature/Action",
                                '',
                                '',
                                $item_id);
    }
}
?>
