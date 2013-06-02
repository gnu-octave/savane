<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2004      (c) ...
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

require_once('../include/init.php');
require_once('../include/sane.php');
require_once('../include/people/general.php');


extract(sane_import('request', array('job_id')));
extract(sane_import('post',
  array(
    'add_job', 'update_job', 'add_to_job_inventory', 'update_job_inventory', 'delete_from_job_inventory',
    'title', 'description', 'status_id', 'category_id',
    'job_inventory_id', 'skill_id', 'skill_level_id', 'skill_year_id')));
   
if ($group_id && (user_ismember($group_id, 'A'))) {

  if ($add_job) {
    /*
			create a new job
    */
    if (!$title || !$description || $category_id==100) {
      #required info
      exit_error(_("error - missing info"),_("Fill in all required fields"));
    }
    $result = db_autoexecute('people_job',
      array('group_id' => $group_id,
	    'created_by' => user_getid(),
	    'title' => $title,
	    'description' => $description,
	    'date' => time(),
	    'status_id' => 1,
	    'category_id' => $category_id,
      ), DB_AUTOQUERY_INSERT);
    if (!$result || db_affected_rows($result) < 1) {
      fb(_("JOB insert FAILED"));
      print db_error();
    } else {
      $job_id=db_insertid($result);
      fb(_("JOB inserted successfully"));
    }

  } else if ($update_job) {
    /*
			update the job's description, status, etc
    */
    if (!$title || !$description || $category_id==100 || $status_id==100 || !$job_id) {
      #required info
      exit_error(_("error - missing info"),_("Fill in all required fields"));
    }

    $result=db_autoexecute('people_job',
      array(
        'title' => $title,
	'description' => $description,
	'status_id' => $status_id,
	'category_id' => $category_id,
	), DB_AUTOQUERY_UPDATE,
      "job_id=? AND group_id=?",
      array($job_id, $group_id));
    if (!$result || db_affected_rows($result) < 1) {
      fb(_("JOB update FAILED"));
      print db_error();
    } else {
      fb(_("JOB updated successfully"));
    }

  } else if ($add_to_job_inventory) {
    /*
			add item to job inventory
    */
    if ($skill_id==100 || $skill_level_id==100 || $skill_year_id==100  || !$job_id) {
      #required info
      exit_error(_("error - missing info"),_("Fill in all required fields"));
    }

    if (people_verify_job_group($job_id,$group_id)) {
      people_add_to_job_inventory($job_id,$skill_id,$skill_level_id,$skill_year_id);
      fb(_("JOB updated successfully"));
    } else {
      fb(_("JOB update failed - wrong project_id"));
    }

  } else if ($update_job_inventory) {
    /*
			Change Skill level, experience etc.
    */
    if ($skill_level_id==100 || $skill_year_id==100  || !$job_id || !$job_inventory_id) {
      #required info
      exit_error(_("error - missing info"),_("Fill in all required fields"));
    }

    if (people_verify_job_group($job_id,$group_id)) {
      $result = db_autoexecute('people_job_inventory',
        array(
          'skill_level_id' => $skill_level_id,
	  'skill_year_id' => $skill_year_id,
	  ), DB_AUTOQUERY_UPDATE,
       "job_id= AND job_inventory_id=?",
       array($job_id, $job_inventory_id));
      if (!$result || db_affected_rows($result) < 1) {
	fb(_("JOB skill update FAILED"));
	print db_error();
      } else {
	fb(_("JOB skill updated successfully"));
      }
    } else {
      fb(_("JOB skill update failed - wrong project_id"));
    }

  } else if ($delete_from_job_inventory) {
    /*
			remove this skill from this job
    */
    if (!$job_id) {
      #required info
      exit_error(_("error - missing info"),_("Fill in all required fields"));
    }

    if (people_verify_job_group($job_id,$group_id)) {
      $result = db_execute("DELETE FROM people_job_inventory WHERE job_id=? AND job_inventory_id=?",
			   array($job_id, $job_inventory_id));
      if (!$result || db_affected_rows($result) < 1) {
	fb(_("JOB skill delete FAILED"));
	print db_error();
      } else {
	fb(_("JOB skill deleted successfully"));
      }
    } else {
      fb(_("JOB skill delete failed - wrong project_id"));
    }
  }

  /*
		Fill in the info to create a job
                Only if we have a job id specified
                If not, it means that we are looking for a project to edit
  */
  if ($job_id) {

    site_project_header(array('title'=>_("Edit a job for your project"),'group'=>$group_id,'context'=>'ahome'));

    #for security, include group_id
    $result=db_execute("SELECT * FROM people_job WHERE job_id=? AND group_id=?",
		       array($job_id, $group_id));
    if (!$result || db_numrows($result) < 1) {
      print db_error();
      fb(_("POSTING fetch FAILED"));
      print '<h2>'._("No Such Posting For This Project").'</h2>';
    } else {

      # we get site-specific content
      utils_get_content("people/editjob");

      print '
		<form action="'.$_SERVER['PHP_SELF'].'" method="POST">
		<input type="hidden" name="group_id" value="'.$group_id.'" />
		<input type="hidden" name="job_id" value="'.$job_id.'" />
		<strong>'
	._("Category:").'</strong><br />'
	. people_job_category_box('category_id',db_result($result,0,'category_id')) .'
		<p>
		<strong>'
	._("Status").':</strong><br />'
	. people_job_status_box('status_id',db_result($result,0,'status_id')) .'
		<p>
		<strong>'
	._("Short Description:").'</strong><br />
		<input type="text" name="title" value="'. db_result($result,0,'title') .'" size="40" maxlength="60" />
		<p>
		<strong>'
	._("Long Description:").'</strong><br />
		<textarea name="description" rows="10" cols="60" wrap="soft">'.
                htmlspecialchars(db_result($result,0,'description')) .'</textarea>
		<p>
		<input type="submit" name="update_job" value="'._("Update Descriptions").'" />
		</form>';

      #now show the list of desired skills
      print '<P>'.people_edit_job_inventory($job_id,$group_id);
      print '<p>[<a href="/people">Back to jobs listing</a>]</p>';

    }
  } else {

    site_project_header(array('title'=>_("Looking for a job to Edit"),'group'=>$group_id,'context'=>'ahome'));


    print '<p>'
      ._("Here is a list of positions available for this project, choose the one you want to modify.").'</p>';
    print people_show_project_jobs($group_id, $edit=1);
  }

  site_project_footer(array());

} else {
  /*
		Not logged in or insufficient privileges
  */
  if (!$group_id) {
    exit_no_group();
  } else {
    exit_permission_denied();
  }
}
