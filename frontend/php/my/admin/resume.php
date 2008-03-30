<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

if ( ! user_isloggedin()) 
{
  exit_not_logged_in();
}

extract(sane_import('post',
  array('update_profile', 'people_resume', 'people_view_skills',
	'add_to_skill_inventory', 'update_skill_inventory',
	'delete_from_skill_inventory', 'skill_id', 'skill_level_id',
	'skill_year_id', 'skill_inventory_id')));

if ($update_profile) 
{
  if (!$people_resume) 
    {
      fb(_("Missing info: fill in all required fields"), 1);
    } 
  else 
    {
      $people_resume = utils_unconvert_htmlspecialchars($people_resume);
      $result = db_execute("UPDATE user SET people_view_skills=?, people_resume=? ".
	 "WHERE user_id=?", array($people_view_skills, $people_resume, user_getid()));
      if (!$result || db_affected_rows($result) < 1) 
	{
	  fb(_("Update failed"), 1);
	} 
      else 
	{
	  fb(_("Updated successfully"));
	}
    }
  
} 
else if ($add_to_skill_inventory) 
{
  if ($skill_id==100 || $skill_level_id==100 || $skill_year_id==100) 
    {
      fb(_("Missing info: fill in all required fields"),1);
    } 
  else 
    {
      people_add_to_skill_inventory($skill_id,$skill_level_id,$skill_year_id);
    }
} 
else if ($update_skill_inventory) 
{
  # Change Skill level, experience etc.
  if ($skill_level_id==100 || $skill_year_id==100  || !$skill_inventory_id) 
    {
      fb(_("Missing info: fill in all required fields"));
    } 
  else 
    {
      $result = db_execute("UPDATE people_skill_inventory SET skill_level_id=?,skill_year_id=? ".
	 "WHERE user_id=? AND skill_inventory_id=?",
	 array($skill_level_id, $skill_year_id, user_getid(), $skill_inventory_id));
      
      if (!$result || db_affected_rows($result) < 1) 
	{
	  fb(_("User Skill update failed"),1);
	} 
      else 
	{
	  fb(_("User Skills updated successfully"));
	}
    }
} else if ($delete_from_skill_inventory) 
{
  if (!$skill_inventory_id) 
    {
      exit_error(_("Missing information: Fill in all required fields"));
    }

  $result = db_execute("DELETE FROM people_skill_inventory WHERE user_id=? AND skill_inventory_id=?",
		       array(user_getid(), $skill_inventory_id));
  if (!$result || db_affected_rows($result) < 1) 
    {
      fb(_("User Skill Delete failed"),1);
    } 
  else 
    {
     fb(_("User Skill Deleted successfully"));
    }

}


# Fill in the info to edit the resume

site_user_header(array('title'=>_("Edit Your Resume & Skills"),'context'=>'account'));


print '<p>'._("Details about your experience and skills may be of interest to others users or visitors.").'</p>';



$result = db_execute("SELECT * FROM user WHERE user_id=?", array(user_getid()));
if (!$result || db_numrows($result) < 1)
{
  exit_error(_("No such user"));
}

# we get site-specific content
utils_get_content("people/editresume");


$viewableoptions = array("0" => _("No"),
			 "1" => _("Yes"));



print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">'
.'<h3>'._("Publicly Viewable").'</h3>'
.'<span class="preinput">'._("Do you want your resume to be activated:").'</span>&nbsp;&nbsp;'
.html_build_select_box_from_array(array("0" => _("No"),"1" => _("Yes")),
				  'people_view_skills',
				  db_result($result,0,'people_view_skills'));

print '<h3>'.sprintf(_("Resume - Description of Experience %s"), markup_info("full")).'</h3>
	<textarea name="people_resume" rows="15" cols="60" wrap="soft">'. db_result($result,0,'people_resume') .'</textarea>';

print '<br /><br /><div class="center"><input type="submit" name="update_profile" value="'._("Update Profile").'" /></div></form>';

print '<h3>'._("Skills").'</h3>';

#now show the list of desired skills
people_edit_skill_inventory(user_getid());


site_user_footer(array());
