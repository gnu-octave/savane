<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2003 (c) Mathieu Roy <yeupou--gnu.org>
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
require_once('../include/people/general.php');


if ($group_id && (user_ismember($group_id, 'A'))) {

  /*
		Fill in the info to create a job
  */

  site_project_header(array('title'=>_("Create a job for your project"),'group'=>$group_id,'context'=>'ahome'));


  # we get site-specific content
  utils_get_content("people/createjob");

  print '
		<form action="'.$GLOBALS['sys_home'].'people/editjob.php" method="POST">
		<input type="HIDDEN" name="group_id" value="'.$group_id.'" />
		<strong>'
    ._("Category:").'</strong><br />'
    . people_job_category_box('category_id') .'
		<p>
		<strong>'
    ._("Summary").':</strong><br />
		<input type="text" name="title" value="" size="40" maxlength="60" />
		<p>'
    ._("Your project description will be inserted on the announce.").'
        <p>
		<strong>'
    ._("Details (job description, contact...):").'</strong><br />
		<textarea name="description" rows="10" cols="60" wrap="soft"></textarea>
		<p>
		<input type="submit" name="add_job" value="'
    ._("continue >>").'">
		</form>';

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
