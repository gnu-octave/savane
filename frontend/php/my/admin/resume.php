<?php
# Resume editor.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2019, 2022 Ineiev
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

require_once('../../include/init.php');
require_directory("people");
register_globals_off();

if (!user_isloggedin())
  exit_not_logged_in();

extract(sane_import('post',
  array('update_profile', 'people_resume', 'people_view_skills',
        'add_to_skill_inventory', 'update_skill_inventory',
        'delete_from_skill_inventory', 'skill_id', 'skill_level_id',
        'skill_year_id', 'skill_inventory_id')));

# Check if resume should be editable at all.
$allow_resume = false;
# Let edit resume when it already exists.
$result = db_execute ("SELECT people_resume FROM user WHERE user_id=?",
                      array(user_getid()));
if ($result && db_numrows ($result) > 0)
  {
    if ('' != db_result ($result, 0, 'people_resume'))
      $allow_resume = true;
  }
# Let members of any group edit their resume.
if (!$allow_resume)
  {
    $result = db_execute ("SELECT groups.group_id FROM user_group,groups
                           WHERE groups.group_id = user_group.group_id
                                 AND groups.status = 'A'
                                 AND user_id=? AND admin_flags != 'P' LIMIT 1",
                          array(user_getid()));
    if ($result && db_numrows ($result) > 0)
      $allow_resume = true;
  }

if ($update_profile)
  {
    $arg_arr = array ($people_view_skills, user_getid());
    $sql_str = "people_view_skills=?";
    if ($allow_resume)
      {
        if (!$people_resume)
          $people_resume = '';
        else
          $people_resume = utils_unconvert_htmlspecialchars ($people_resume);
        $arg_arr = array ($people_view_skills, $people_resume, user_getid());
        $sql_str = $sql_str . ", people_resume=?";
      }
    $result = db_execute (
      "UPDATE user SET " . $sql_str . " WHERE user_id=?", $arg_arr
    );
    if ($result)
      fb(_("Updated successfully"));
    else
      fb(_("Update failed"), 1);
  }
elseif ($add_to_skill_inventory)
  {
    if ($skill_id==100 || $skill_level_id==100 || $skill_year_id==100)
      fb(_("Missing info: fill in all required fields"),1);
    else
      people_add_to_skill_inventory($skill_id,$skill_level_id,$skill_year_id);
  }
elseif ($update_skill_inventory)
  {
  # Change Skill level, experience etc.
    if ($skill_level_id==100 || $skill_year_id==100  || !$skill_inventory_id)
      fb(_("Missing info: fill in all required fields"));
    else
      {
        $result = db_execute("UPDATE people_skill_inventory "
           ."SET skill_level_id=?,skill_year_id=? "
           ."WHERE user_id=? AND skill_inventory_id=?",
           array($skill_level_id, $skill_year_id, user_getid(),
                 $skill_inventory_id));

        if (!$result || db_affected_rows($result) < 1)
          fb(_("User Skill update failed"),1);
        else
          fb(_("User Skills updated successfully"));
      }
  }
elseif ($delete_from_skill_inventory)
  {
    if (!$skill_inventory_id)
      exit_error(_("Missing information: fill in all required fields"));

    $result = db_execute("DELETE FROM people_skill_inventory "
                         ."WHERE user_id=? AND skill_inventory_id=?",
                         array(user_getid(), $skill_inventory_id));
    if (!$result || db_affected_rows($result) < 1)
      fb(_("User Skill Delete failed"),1);
    else
     fb(_("User Skill Deleted successfully"));
  }

# Fill in the info to edit the resume.
site_user_header(array('title'=>_("Edit Your Resume & Skills"),
                       'context'=>'account'));
print '<p>'
._("Details about your experience and skills may be of interest to other users
or visitors.").'</p>
';

$result = db_execute("SELECT * FROM user WHERE user_id=?", array(user_getid()));
if (!$result || db_numrows($result) < 1)
  exit_error(_("No such user"));
utils_get_content("people/editresume");

$viewableoptions = array("0" => _("No"),
                         "1" => _("Yes"));

print '<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">'
.'<h2>'._("Publicly Viewable").'</h2>
'
.'<span class="preinput">'._("Do you want your resume to be activated?")
.'</span>&nbsp;&nbsp;'
.html_build_select_box_from_array(array("0" => _("No"),"1" => _("Yes")),
                                  'people_view_skills',
                                  db_result($result,0,'people_view_skills'), 0,
                                  _("Activate resume"));

if ($allow_resume)
  {
    print '<h2><label for="people_resume">'
          ._("Resume - Description of Experience")
          ."</label></h2>\n<p>".markup_info("full")."</p>\n"
          .'<textarea id="people_resume" name="people_resume" rows="15" '
          .'cols="60" wrap="soft">'.db_result($result, 0, 'people_resume')
          ."</textarea>\n<br /><br />\n";
  }

print '<div class="center"><input type="submit" name="update_profile" '
  . 'value="' . _("Update Profile") . '" /></div></form>' . "\n";

print '<h2>'._("Skills").'</h2>';
# Now show the list of desired skills.
people_edit_skill_inventory(user_getid());

site_user_footer(array());
?>
