<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2004 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
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
require_once('../include/http.php');


if (!$group_id)
{
  exit_no_group();
}

$project = project_get_object($group_id);

if (!$project->Uses("cvs") && !$project->UsesForHomepage("cvs"))
{
  exit_error(_("This project doesn't use this tool"));
}

// Enable cache for this page if the user isn't logged in, because
// crawlers particularly like it:
$file = utils_get_content_filename("cvs/index");
if ($file != null and !user_isloggedin())
  {
    /* Get file stat */
    $stat = stat($file);
    $mtime = $stat['mtime'];
    http_exit_if_not_modified($mtime);
    header('Last-Modified: ' . date('r', $mtime));
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
