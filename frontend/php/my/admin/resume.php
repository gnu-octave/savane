<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: resume.php 4977 2005-11-15 17:38:40Z yeupou $
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2006  (c) Mathieu Roy <yeupou--gnu.org>
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

require_once('../../include/init.php');
require_directory("people");

register_globals_off();

if ( ! user_isloggedin()) 
{
  exit_not_logged_in();
}

$update_profile = sane_post("update_profile");
$people_resume = sane_post("people_resume");
$people_view_skills = sane_post("people_view_skills");

$add_to_skill_inventory = sane_post("add_to_skill_inventory");
$update_skill_inventory = sane_post("update_skill_inventory");
$delete_from_skill_inventory = sane_post("delete_from_skill_inventory");
$skill_id = sane_post("skill_id");
$skill_level_id = sane_post("skill_level_id");
$skill_year_id = sane_post("skill_year_id");
$skill_inventory_id = sane_post("skill_inventory_id");

if ($update_profile) 
{
  if (!$people_resume) 
    {
      fb(_("Missing info: fill in all required fields"), 1);
    } 
  else 
    {
      $people_resume = utils_unconvert_htmlspecialchars($people_resume);
      $sql="UPDATE user SET people_view_skills='$people_view_skills',people_resume='$people_resume' ".
	 "WHERE user_id='".user_getid()."'";
      $result=db_query($sql);
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
      $sql="UPDATE people_skill_inventory SET skill_level_id='$skill_level_id',skill_year_id='$skill_year_id' ".
	 "WHERE user_id='". user_getid() ."' AND skill_inventory_id='$skill_inventory_id'";
      $result=db_query($sql);
      
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

  $sql="DELETE FROM people_skill_inventory WHERE user_id='". user_getid() ."' AND skill_inventory_id='$skill_inventory_id'";
  $result=db_query($sql);
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



$sql="SELECT * FROM user WHERE user_id='". user_getid() ."'";
$result=db_query($sql);
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
