<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2004      (c) ...
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

require "../include/pre.php";
require $GLOBALS['sys_www_topdir']."/include/vars.php";

if ($group_id && $job_id)
{

  /*
		Fill in the info to create a job
  */
  site_project_header(array('title'=>_("View a Job"),'group'=>$group_id,'context'=>'home'));

  #  people_header(array('title'=>'View a Job'));


  #for security, include group_id
  $sql="SELECT groups.group_name,groups.type,people_job_category.name AS category_name,".
     "people_job_status.name AS status_name,people_job.title,".
     "people_job.description,people_job.date,user.user_name,user.user_id ".
     "FROM people_job,groups,people_job_status,people_job_category,user ".
     "WHERE people_job_category.category_id=people_job.category_id ".
     "AND people_job_status.status_id=people_job.status_id ".
     "AND user.user_id=people_job.created_by ".
     "AND groups.group_id=people_job.group_id ".
     "AND people_job.job_id='$job_id' AND people_job.group_id='$group_id'";
  $result=db_query($sql);
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
      print db_result($result,0,'category_name');

      print  ' '._("wanted for").' <a href="'.$GLOBALS['sys_home'].'project/?group_id='. $group_id .'">'. db_result($result,0,'group_name') .'</a></h2>'.
	'<p><span class="preinput">'._("Submitted By:").'</span> <a href="'.$GLOBALS['sys_home'].'users/'. db_result($result,0,'user_name') .'">'. db_result($result,0,'user_name').'</a><br />'.
	'<span class="preinput">'._("Date:").'</span>'. format_date($sys_datefmt,db_result($result,0,'date')) .'<br />'.
	'<span class="preinput">'._("Status:").'</span> '. db_result($result,0,'status_name').'</p>';



      if ($project->getTypeDescription())
	{
	  print "<p>" . markup_full(htmlspecialchars($project->getTypeDescription()));
	}
      if ($project->getLongDescription())
	{
	  print "<p>" . markup_full(htmlspecialchars($project->getLongDescription()));
	}
      else
	{
	  if ($project->getDescription())
	    {
	      print "<p>" . $project->getDescription();
	    }
	}
      $license = $project->getLicense();
      print '<p><span class="preinput">'._("License").'</span> ';
      if ($LICENSE_URL[$license] != "0")
	{
	  print '<a href="'.$LICENSE_URL[$license].'" target="_blank">'.$LICENSE[$license].'</a>';
	}
      else
	{
	  print $LICENSE[$license];
	}
      $devel_status = $project->getDevelStatus();
      print '<span class="preinput"><br />'
	._("Development Status").'</span>: '.$DEVEL_STATUS[$devel_status];

      print '<p><span class="preinput">'._("Details (job description, contact ...):").'</span></p>';
      print markup_full(htmlspecialchars(db_result($result,0,'description')));
      print '<h3>'._("Required Skills:").'</h3>';
      #now show the list of desired skills
      print people_show_job_inventory($job_id);
    }

  site_project_footer(array());

}
else
{
  /*
		Not logged in or insufficient privileges
  */
  if (!$group_id)
    {
      exit_no_group();
    }
  else
    {
      exit_error(_("Error"),_("Posting ID not found"));
    }
}

?>
