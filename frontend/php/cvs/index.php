<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2004 (c) Mathieu Roy <yeupou--gnu.org>
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


require_once('../include/init.php');

if (!$group_id)
{
  exit_no_group();
}

if (!$project->Uses("cvs") && !$project->UsesForHomepage("cvs"))
{
  exit_error(_("This project has turned off this tool"));
}

site_project_header(array('group'=>$group_id,'context'=>'cvs'));



# ####################### CVS Browsing



$type = "CVS";

if (($project->Uses("cvs") && 
     $project->getUrl("cvs_viewcvs") != 'http://' &&
     $project->getUrl("cvs_viewcvs") != '') ||
    ($project->UsesForHomepage("cvs") && 
     $project->getUrl("cvs_viewcvs_homepage") != 'http://' &&
     $project->getUrl("cvs_viewcvs_homepage") != ''))
{
    
  print '<h2>'.sprintf(_("Browsing the %s Repository"), $type).'</h2>';
  print '<p>'.sprintf(_("You can Browse the %s repository of this project with your web browser. This gives you a good picture of the current status of the source files. You may also view the complete histories of any file in the repository as well as differences among two versions."), $type).'</p>';
  
  print '<ul>';
  
  if ($project->Uses("cvs") &&
      $project->getUrl("cvs_viewcvs") != 'http://' &&
      $project->getUrl("cvs_viewcvs") != '')
    {
      print '<li><a href="'.$project->getUrl("cvs_viewcvs").'">'._("Browse Sources Repository").'</a></li>';
    }
  if ($project->UsesForHomepage("cvs") &&
      $project->getUrl("cvs_viewcvs_homepage") != 'http://' &&
      $project->getUrl("cvs_viewcvs_homepage") != ''
      )
    {
      print '<li><a href="'.$project->getUrl("cvs_viewcvs_homepage").'">'._("Browse Web Pages Repository").'</a></li>';
    }
  print '</ul>';
  print '<p>&nbsp;</p>';
}


print '<h2>'.sprintf(_("Getting a Copy of the %s Repository"),$type).'</h2>';


utils_get_content("cvs/index");

site_project_footer(array());


?>
