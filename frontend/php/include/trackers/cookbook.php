<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: votes.php 4645 2005-09-01 12:54:05Z toddy $
#
#  Copyright 2005      (c) Mathieu Roy <yeupou--gnu.org>
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

##
# This will return an array of possible values, like
#      anonymous, logged-in...
function cookbook_audience_possiblevalues() {
  return array("anonymous" => _("Anonymous Users"),
	       "loggedin" => _("Logged-in Users"),
	       "members" => _("All Project Members"),
	       "technicians" => _("Project Members who are technicians"),
	       "managers" => _("Project Members who are managers"));
}

##
# Same for context
# Guess all the possible values in theory on the site for a project
function cookbook_context_project_possiblevalues() {
  return array("project" => _("Project Main Pages"),
	       "homepage" => _("Project Homepage"),
	       "cookbook" => _("Cookbook"),
	       "download" => _("Download Area"),
	       "support" => _("Support Tracker"),
	       "bugs" => _("Bugs Tracker"),
	       "task" => _("Task Manager"),
	       "patch" => _("Patch Tracker"),
	       "news" => _("News Manager"),
	       "mail" => _("Mailing Lists"),
	       "cvs" => _("Source Code Manager: CVS Repositories"),
	       "arch" => _("Source Code Manager: GNU Arch Repositories"),
	       "svn" => _("Source Code Manager: Subversion Repositories"));
}

# Guess all the possible values in theory on the site for the site admin
function cookbook_context_site_possiblevalues() {
  return array("my" => _("My (User Personal Area)"),
	       "stats" => _("Site Statistics"),
	       "siteadmin" => _("Site Administration"));
}

# Guess all the impossible values in reality for a project
function cookbook_context_project_impossiblevalues() {
  global $group_id;
  
  # Site values are per definition impossible for a normal project
  $array_impossible = cookbook_context_site_possiblevalues();

  # Impossible values are values of unactivated features for the project
  $array_possible = cookbook_context_project_possiblevalues();
  $project = project_get_object($group_id);
  while(list($feature,) = each($array_possible))
    {
      # Cookbook cannot be deactivated
      if ($feature == 'cookbook')
	{ continue; }

      # Project main pages cannot be deactivated
      if ($feature == 'project')
	{ continue; }

      if (!$project->Uses($feature))
	{
	  $array_impossible[$feature] = 1;
	}
    }
  

  return $array_impossible;
}



##
# Find out the really possible value in the current context
function cookbook_context_possiblevalues() {
  global $group_id, $sys_group_id;

  # All projec-wide possible values, ordered in a clean way
  $array_possible = cookbook_context_project_possiblevalues();
  
  if ($group_id != $sys_group_id)
    {
      # If we are in a normal group, remove impossible values.
      # For instance, remove all unused features
      $array_impossible = cookbook_context_project_impossiblevalues();

      while(list($feature,) = each($array_impossible))
	{
	  unset($array_possible[$feature]);
	}
    }
  else
    {
      # For the site admin group, present all possible values
      $array_possible = array_merge(cookbook_context_site_possiblevalues(),
				    $array_possible);
    }


  reset($array_possible);
  return $array_possible;
}

##
# Same for subcontext
function cookbook_subcontext_possiblevalues() {
  return array("browsing" => _("Browsing"),
	       "postitem" => _("Posting New Items"),
	       "edititem" => _("Editing Items, Posting Comments"),
	       "search" => _("Doing Searches"),
	       "configure" => _("Configuring Features"));
}




##
# Return a bit of form that should be used inside the item post/edit forms
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
  # and we want to previous results from the database
  if ($item_id)
    {
      $result = db_query("SELECT * FROM cookbook_context2recipe WHERE recipe_id='$item_id' AND group_id='$group_id' LIMIT 1");
    }

  unset($content);
  while(list($field,$label) = each($possiblevalues))
    {
      unset($checked);

      # Take into account database content
      if ($item_id && db_result($result, 0, $which."_".$field) == 1)
	{ $checked = ' checked="checked"'; }

      # Ultimately take into account what was posted, in the case the form
      # was reprovided to the user because he forgot mandatory fields
      if ($previous_form_bad_fields)
	{
	  if (sane_post("recipe_".$which."_".$field) == true)
	    { $checked = ' checked="checked"'; }
	  else
	    { unset($checked); }
	}

      if (!defined('PRINTER'))
	{
	  # Normal output
	  $content .= '<input type="checkbox" name="recipe_'.$which.'_'.$field.'"'.$checked.' />'.$label.'<br />';
	}
      else
	{
	  # Printer mode output, show only selected entries
	  if ($checked)
	    { $content .= $label.'<br />'; }

	}
    }

  # remove the extra line break
  $content = rtrim($content, '<br />');

  return $content;
}

##
# Describe a field type
function cookbook_describe ($which="audience")
{
  unset($text);
  if ($which == "audience")
    {
      $text = _("Defines which users will actually get such recipe showing up as related recipe while browsing the site. It will not prevent other users to see the recipe in the big list inside the Cookbook.");
    }
  if ($which == "context")
    {
      $text = _("Defines on which pages such recipe will show up as related recipe. It will not prevent other users to see the recipe in the big list inside the Cookbook.");
    }
  if ($which == "subcontext")
    {
      $text = _("Defines while doing which actions such recipe will show up as related recipe. It will not prevent other users to see the recipe in the big list inside the Cookbook.");
    }

  return $text;
}

##
# Use cookbook_build_form to return a nice form that can be included in
# mod and post forms.
function cookbook_print_form ()
{
  global $j, $fields_per_line, $i, $row_class, $field_class;

  # Field getting one line for itself
  #  |            Audience                     |

  # prepare next background color change
  $j++;

  print "\n<tr".$row_class.">".
    '<td valign="middle" '.$field_class.' width="15%"><span class="preinput"><span class="help" title="'.cookbook_describe("audience").'">'._("Audience:").'</span></span></td>'.
    '<td valign="middle" '.$field_class.' colspan="'.(2*$fields_per_line-1).'" width="75%">'.
    cookbook_build_form("audience").'</td>'.
    "\n</tr>";

  $i = 0;

  # Field getting half of a line for itself
  #  | context, kind of pages | context, kind of action
  #       (CONTEXT)                   (SUBCONTEXT)

  # Change background color
  unset($row_class);
  if ($j % 2)
    {
      $row_class = ' class="'.utils_altrow($j+1).'"';
    }

  # prepare next background color change
  $j++;

  print ($i % $fields_per_line ? '':"\n<tr".$row_class.">");
  print '<td valign="middle"'.$field_class.' width="15%"><span class="preinput"><span class="help" title="'.cookbook_describe("context").'">'._("Feature:").'</span></span></td><td valign="middle"'.$field_class.' width="35%">'.cookbook_build_form("context").'</td>';
  $i++;
  print ($i % $fields_per_line ? '':"\n</tr>");
  print ($i % $fields_per_line ? '':"\n<tr".$row_class.">");
  print '<td valign="middle"'.$field_class.' width="15%"><span class="preinput"><span class="help" title="'.cookbook_describe("subcontext").'">'._("Action:").'</span></span></td><td valign="middle"'.$field_class.' width="35%">'.cookbook_build_form("subcontext").'</td>';
  $i++;
  print ($i % $fields_per_line ? '':"\n</tr>");

  $i = 0;

  # Change background color
  unset($row_class);
  if ($j % 2)
    {
      $row_class = ' class="'.utils_altrow($j+1).'"';
    }

}

##
# Handle update or create of cookbook specific things in items:
#     the related items links
function cookbook_handle_update($item_id, $group_id)
{
  global $change_exists;

  ## Pass thru all the available/configurable fields
  unset($cookbook_upd_list);

  # Find out the targetted audience
  $audience_cases = array();
  $possiblevalues = cookbook_audience_possiblevalues();
  while(list($field,) = each($possiblevalues))
    {
      $value = 0;
      if (sane_post("recipe_audience_".$field))
	{ $value = 1; }
      $cookbook_upd_list .= "audience_$field='$value',";
    }

  # Find out the targetted context (feature)
  $context_cases = array();
  $possiblevalues = cookbook_context_possiblevalues();
  while(list($field,) = each($possiblevalues))
    {
      $value = 0;
      if (sane_post("recipe_context_".$field))
	{ $value = 1; }
      $cookbook_upd_list .= "context_$field='$value',";
    }


  # Find out the targetted subcontext (action)
  $subcontext_cases = array();
  $possiblevalues = cookbook_subcontext_possiblevalues();
  while(list($field,) = each($possiblevalues))
    {
      $value = 0;
      if (sane_post("recipe_subcontext_".$field))
	{ $value = 1; }
      $cookbook_upd_list .= "subcontext_$field='$value',";
    }

  # Create from scratch a row, to be able to do a simple update afterwards
  if (!db_numrows(db_query("SELECT context_id FROM cookbook_context2recipe WHERE recipe_id='$item_id' AND group_id='$group_id' LIMIT 1")))
    {
      db_query("INSERT INTO cookbook_context2recipe (recipe_id,group_id) VALUES ('$item_id','$group_id')");
    }

  # Now do an update
  $cookbook_upd_list = rtrim($cookbook_upd_list, ",");
  $sql="UPDATE cookbook_context2recipe SET $cookbook_upd_list ".
    " WHERE recipe_id='$item_id' AND group_id='$group_id'";
  $result=db_affected_rows(db_query($sql));

  # If there was affected rows, it means we did an update
  # (ignoring the very unusual case where the SQL would fail)
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