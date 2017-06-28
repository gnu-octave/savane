<?php
# Vew jobs.
# 
# Copyright (C) 1999-2000 The SourceForge Crew
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

require_once('../include/init.php');
require_once('../include/people/general.php');
require_once('../include/vars.php');


extract(sane_import('get', array('group_id', 'job_id')));

if ($group_id && $job_id)
{

  /* Fill in the info to create a job */
  site_project_header(array('title'=>_("View a Job"),
                            'group'=>$group_id,'context'=>'home'));

  #for security, include group_id
  $result=db_execute("SELECT groups.group_name,groups.type,"
     ."groups.unix_group_name,people_job_category.name AS category_name,"
     ."people_job_status.name AS status_name,people_job.title AS job_title,"
     ."people_job.category_id AS category_id,"
     ."people_job.description,people_job.date,user.user_name,user.user_id "
     ."FROM people_job,groups,people_job_status,people_job_category,user "
     ."WHERE people_job_category.category_id=people_job.category_id "
     ."AND people_job_status.status_id=people_job.status_id "
     ."AND user.user_id=people_job.created_by "
     ."AND groups.group_id=people_job.group_id "
     ."AND people_job.job_id=? AND people_job.group_id=?",
		     array($job_id, $group_id));
  if (!$result || db_numrows($result) < 1)
    {
      print db_error();
      fb(_("POSTING fetch FAILED"));
      print '<h2>'._("No Such Posting For This Project").'</h2>';
    }
  else
    {
      $project=project_get_object($group_id);
      print '
<h2 class=toptitle>';
# TRANSLATORS: the first argument is job title (like Tester or Developer),
# the second argument is group name (like GNU Coreutils).
      printf ('%1$s for %2$s',
              db_result($result,0,'job_title'),
              '<a href="'.$GLOBALS['sys_home'].'projects/'
              . db_result($result,0,'unix_group_name')
              .'">'. db_result($result,0,'group_name') .'</a>');
      print'</h2>
<p><span class="preinput">'._("Category:").'</span> <a href="'
         . $GLOBALS['sys_home'] . 'people/?category_id='
         . db_result($result,0,'category_id') . '">'
         .db_result($result,0,'category_name').'</a><br />
<span class="preinput">'._("Submitted By:").'</span> <a href="'
         .$GLOBALS['sys_home'].'users/'. db_result($result,0,'user_name')
         .'">'. db_result($result,0,'user_name').'</a><br />
<span class="preinput">'._("Date:").'</span> '
         . utils_format_date(db_result($result,0,'date')) .'<br />
<span class="preinput">'._("Status:").'</span> '
         . db_result($result,0,'status_name').'</p>
';

      if ($project->getTypeDescription())
	{
	  print "<p>"
                . markup_full(htmlspecialchars($project->getTypeDescription()))
                ."</p>\n";
	}
      if ($project->getLongDescription())
	{
	  print "<p>"
                . markup_full(htmlspecialchars($project->getLongDescription()))
                ."</p>\n";
	}
      else
	{
	  if ($project->getDescription())
	    {
	      print "<p>" . $project->getDescription()
                    ."</p>\n";
	    }
	}
      $license = $project->getLicense();
      print '<p><span class="preinput">'._("License").'</span> ';
      if ($LICENSE_URL[$license] != "0")
	{
	  print '<a href="'.$LICENSE_URL[$license].'" target="_blank">'
                .$LICENSE[$license].'</a>';
	}
      else
	{
	  print $LICENSE[$license];
	}
      print "</p>\n";
      $devel_status_id = $project->getDevelStatus();
      $devel_status = (isset($DEVEL_STATUS[$devel_status_id]))
	? $DEVEL_STATUS[$devel_status_id]
	: _("&lt;Invalid status ID&gt;");
      print '<span class="preinput"><br />'
	._("Development Status").'</span>: '.$devel_status;

      print '<p><span class="preinput">'
            ._("Details (job description, contact ...):").'</span></p>
';
      print markup_full(htmlspecialchars(db_result($result,0,'description')));
      print '<h3>'._("Required Skills:").'</h3>
';
      print people_show_job_inventory($job_id);
    }

  site_project_footer(array());

}
else
{
  if (!$group_id)
    exit_no_group();
  else
    exit_error(_("Error"),_("Posting ID not found"));
}
?>
