<?php
# View a tracker item - alternate view. Used by cookbook.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2002-2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2005 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007  Sylvain Beucler
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

require_once('../include/trackers/cookbook.php');

# If we are a comingfrom=$group_id defined, it means that we want to show a
# recipe from the system group as if it were from the current group
extract(sane_import('get', array('comingfrom')));

if (defined('PRINTER'))
  $printer = 1;

if (!isset ($comingfrom))
  $comingfrom = false;

# Site doc pretending to be project doc: set the group id to the one of the
# system group, so its setup will prevail (private right etc)
if ($comingfrom)
  $group_id = $sys_group_id;

$result = db_execute("SELECT * FROM ".ARTIFACT." WHERE bug_id=? AND group_id=?",
                     array($item_id, $group_id));

if (db_numrows($result) == 0)
  {
    if (!$comingfrom)
      exit_error(_("Item not found"));
    # If comingfrom was set, if we are using site doc pretending to be
    # project doc, we may in fact be actually having comingfrom set by
    # mistake and wanting to see a project item.
    # This kind of "mistakes" happen when we want to pretend an item is
    # project wide while it is likely to be site wide but we cannot test
    # if this item is site wide because it would be requires to many
    # sql checks.
    # Typical case:  when recipe #nnn is converted to a link to the recipe
    #    in a comment of an item

    # Set the group id to be the comingfrom one
    $group_id = $comingfrom;

    # If it works, we are not in case were comingfrom was appropriate.
    $comingfrom = false;

    # See if it works
    $result = db_execute("SELECT * FROM ".ARTIFACT
                         ." WHERE bug_id=? AND group_id=?",
                         array($item_id, $group_id));
    if (db_numrows($result) == 0)
      {
        exit_error(_("Item not found"));
      }
  }

##
# Check if the item is "Approved"
if (db_result($result, 0, 'resolution_id') != '1')
  {
    # If the user is not member of the project, kick him out
    # Otherwise, provide a link to the edition page
    if (!member_check(0, $group_id))
      exit_error(_("This item was not approved"));
    fb(sprintf(
_("If you want to edit or comment this recipe, go to [%s this item's edit page]"),
   $GLOBALS['sys_https_url'].$GLOBALS['sys_home'].ARTIFACT
   ."/edit.php?func=detailitem&item_id=$item_id"),1);
    exit_error(_("This item was not approved"));
  }

##
# Check whether this item is private or not. If it is private, show only to
# allowed project members
if (db_result($result,0,'privacy') == "2")
  {
    if (member_check_private(0, $group_id))
      {
         # Nothing worth being mentioned
      }
    else
      {
         # Explain to project member why they cant read
        if (member_check(0, $group_id))
            exit_error(_("This item is private. You are not listed as member
allowed to read private items."));
        exit_error(_("This item is private."));
      }
  }

##
# Defines the item name, converting bugs to bug.
# (Ideally, the artifact bugs should be named bug)
$item_name = utils_get_tracker_prefix(ARTIFACT)." #".$item_id;
# Defines the item link
$item_link = utils_link("?".$item_id, $item_name);

# Restablish the current group name, so we get a nice page like it is was
# not a site wide recipe
if ($comingfrom)
  $group_id = $comingfrom;

trackers_header(array ('title'=>$item_name.", "
                       .utils_cutstring(db_result($result,0,'summary'))));

# Site doc pretending to be project doc: set the group id to the one of the
# system group, so its setup will prevail (private right etc)
if ($comingfrom)
  $group_id = $sys_group_id;

##
# Print the recipe
$category = '';
if (db_result($result,0,'category_id') != '100')
  $category = ", ".trackers_field_display('category_id',
                                          $group_id,
                                          db_result($result,0,'category_id'),
                                          false, # no line break
                                          false, # no label
                                          true,  # read only
                                          true);  # ascii

print '<h2 class="'.utils_get_priority_color(db_result($result,0,'priority'))
  .'"><em>'.$item_link.$category.':</em> '
  .db_result($result,0,'summary').'</h2>';
print markup_full(db_result($result,0,'details'));

# latest update: when the item was posted or the latest history change
# (only content changes, other changes are not relevant for the end user)
$result_history = db_execute("SELECT date FROM ".ARTIFACT
  ."_history WHERE bug_id=? AND (field_name='realdetails' "
  ."OR field_name='summary') ORDER BY date DESC LIMIT 1", array($item_id));
unset($last_update);
if (db_numrows($result_history))
  $last_update = db_result($result_history, 0, 'date');
else
  $last_update = db_result($result, 0, 'date');

print '<div align="right" class="smaller">'
# TRANSLATORS: the argument is date.
  .sprintf(_("Last update: %s"), utils_format_date($last_update)).'</div>';

if ($comingfrom)
  # Mention it is documentation from the site
  print '<div align="right" class="smaller">'
# TRANSLATORS: the argument is site name (like Savannah).
    .sprintf(_("This recipe comes from %s User Docs"), $sys_name).'</div>';

print '<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>';

##
# Show attached files that may be useful to describe the content
$result=trackers_data_get_attached_files($item_id);
if (db_numrows($result))
  {
    print '<h3>'.html_anchor(_("Attached Files"), "attached")."</h3>\n";
    print format_item_attached_files($item_id,$group_id,false,true);

    print '<p>&nbsp;</p>';
  }

##
# Give info about the context
if (ARTIFACT == 'cookbook')
  {
    print '<h3>'.html_anchor(_("Audience and Context"), "context").'</h3>';

  # Obtain selected context
    $context_result = db_execute("SELECT * FROM cookbook_context2recipe
                                  WHERE recipe_id=? AND group_id=? LIMIT 1",
                                 array($item_id, $group_id));

    $cases = array("audience", "context", "subcontext");
    $possiblevalues_audience = cookbook_audience_possiblevalues();
    $possiblevalues_context = cookbook_context_possiblevalues();
    $possiblevalues_subcontext = cookbook_subcontext_possiblevalues();
    $case_result = array();

    $is_user_good_audience = false;
    if ($group_id == $sys_group_id)
      {
      # If we are showing site doc, ignore the test of audience:
      # the readers are likely to get confused if we tell them they are not
      # project members while they are member of a one project, even though
      # they are not members of the site project
        $is_user_good_audience = true;
      }

    while(list(,$case) = each($cases))
      {
        $possiblevalues = array();
        if ($case == "audience")
          { $possiblevalues = $possiblevalues_audience; }
        if ($case == "context")
          { $possiblevalues = $possiblevalues_context; }
        if ($case == "subcontext")
          { $possiblevalues = $possiblevalues_subcontext; }

        while(list($field,$label) = each($possiblevalues))
          {
            if (db_result($context_result, 0, $case."_".$field) == 1)
              {
                $case_result[$case] = (isset($case_result[$case])
                                       ? $case_result[$case] : '')
                  . $label . ", ";

                # Keep in memory whether the user is the targetted audience
                if ($case == 'audience' && $field == AUDIENCE)
                  {
                    $is_user_good_audience = true;
                  }
              }

          }
        if (isset($case_result[$case]))
          $case_result[$case] = rtrim($case_result[$case], ", ");
        else
          $case_result[$case] = null;
      }

  ##
  # Provide info to the reader
    if ($case_result['audience'])
      {
        print '<span class="preinput"><span class="help" title="'
          .cookbook_describe("audience").'">'._("Audience:").'</span></span>';

      # If the current user is not in the audience of the item, warn
      # (but only if we are not on the Site Doc, otherwise info may look
      # flawed, as people understand them as "project member" even if they
      # are not member of the site group)
        if (!$is_user_good_audience)
          { print '<span class="warn">'; }

        print '<br />&nbsp;&nbsp;&nbsp;'.$case_result['audience'].'<br />';

        if (!$is_user_good_audience)
          { print '</span>'; }
      }
    if ($case_result['context'])
      {
        print '<span class="preinput"><span class="help" title="'
              .cookbook_describe("context").'">'._("Feature:")
              .'</span></span><br />&nbsp;&nbsp;&nbsp;'
              .$case_result['context'].'<br />';
      }
    if ($case_result['subcontext'])
      {
        print '<span class="preinput"><span class="help" title="'
              .cookbook_describe("subcontext").'">'._("Action:")
              .'</span></span><br />&nbsp;&nbsp;&nbsp;'
              .$case_result['subcontext'].'<br />';
      }

  # Warn if the recipe have no chance to show up in related recipes
    if (!$case_result['audience'] ||
        !$case_result['context'] ||
        !$case_result['subcontext'])
      print '<p>'._("(As there is at least one of the Audience/Feature/Action
context information not set, this recipe will not show up in related recipes
links)").'</p>';
}

##
# Provide a link to item edition. Only for project members. It would waste
# the recipe management if people was posting SR as comments of it, instead of
# posting a proper SR.
if (member_check(0, $group_id))
  {
    print '<h3>'.html_anchor(_("Item Edition"), "edit").'</h3>';
    print '<p>'.utils_link($GLOBALS['sys_home'].ARTIFACT
                         ."/edit.php?func=detailitem&amp;item_id=$item_id",
                         _("Edit this page")).'</p>';
  }

trackers_footer(array());
?>
