<?php
# Function to define a generic VCS index.php.
#
# Copyright (C) 2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2019 Ineiev
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

# Get array of repository descriptions.
function vcs_get_repos ($vcs_exfix, $group_id)
{
  global $sys_etc_dir;

  $project = project_get_object ($group_id);
  $ret = array ();
  if ($vcs_exfix !== 'git')
    return $ret;

  exec ("grep -A 3 '^repo\.url=" . $project->getUnixName()
        . "\(/\|\.git$\)' " . $sys_etc_dir . "/cgitrepos", $output);
  $n = intval((count ($output) + 1) / 5);
  for ($i = 0; $i < $n; $i++)
    $ret[$i] =  array ('url' => preg_replace (':repo.url=:', '',
                                              $output[$i * 5]),
                       'path' => preg_replace (':repo.path=:', '',
                                               $output[$i * 5 + 1]),
                       'desc' => preg_replace (':repo.desc=:', '',
                                               $output[$i * 5 + 2]));
  return $ret;
}

function vcs_page ($vcs_name, $vcs_exfix, $group_id)
{
  global $repo_list;

  if (!$group_id)
    exit_no_group();

  $project = project_get_object($group_id);

  if (!$project->Uses($vcs_exfix) && !$project->UsesForHomepage($vcs_exfix))
    exit_error(_("This project doesn't use this tool."));

# Enable cache for this page if the user isn't logged in, because
# crawlers particularly like it:
  $file = utils_get_content_filename($vcs_exfix."/index");
  if ($file != null and !user_isloggedin())
    {
      # Get file stat.
      $stat = stat($file);
      $mtime = $stat['mtime'];
      http_exit_if_not_modified($mtime);
      header('Last-Modified: ' . date('r', $mtime));
    }
  site_project_header(array('group'=>$group_id,'context'=>$vcs_exfix));
  $repo_list = vcs_get_repos ($vcs_exfix, $group_id);

  if (($project->Uses($vcs_exfix)
       && $project->getUrl($vcs_exfix."_viewcvs") != 'http://'
       && $project->getUrl($vcs_exfix."_viewcvs") != '')
      || ($project->UsesForHomepage($vcs_exfix)
       && $project->getUrl("cvs_viewcvs_homepage") != 'http://'
       && $project->getUrl("cvs_viewcvs_homepage") != ''))
    {
# TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git, Subversion).
      print '<h2>'.sprintf(_("Browsing the %s Repository"), $vcs_name)."</h2>\n";
# TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git, Subversion).
      print '<p>'.sprintf(_("You can Browse the %s repository of this project with
your web browser. This gives you a good picture of the current status of the
source files. You may also view the complete histories of any file in the
repository as well as differences among two versions."), $vcs_name)."</p>\n";
      print "<ul>\n";
      if ($project->Uses($vcs_exfix)
          && $project->getUrl($vcs_exfix."_viewcvs") != 'http://'
          && $project->getUrl($vcs_exfix."_viewcvs") != '')
        {
          $n = count ($repo_list);
          if ($n > 1)
            {
              $url0 = preg_replace(':/[^/]*$:', '/',
                                   $project->getUrl($vcs_exfix . "_viewcvs"));

              for ($i = 0; $i < $n; $i++)
                print '<li><a href="' . $url0 . $repo_list[$i]['url'] . '">'
                      . $repo_list[$i]['desc'] . "</a></li>\n";
            }
          else
            print '<li><a href="' . $project->getUrl($vcs_exfix . "_viewcvs")
                  . '">' . _("Browse Sources Repository") . "</a></li>\n";
        }
      if ($project->UsesForHomepage($vcs_exfix)
          && $project->getUrl("cvs_viewcvs_homepage") != 'http://'
          && $project->getUrl("cvs_viewcvs_homepage") != '')
        print '<li><a href="'.$project->getUrl("cvs_viewcvs_homepage").'">'
              ._("Browse Web Pages Repository")."</a></li>\n";
      print "</ul>\n<p>&nbsp;</p>\n";
    }
# TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git, Subversion).
  print '<h2>'.sprintf(_("Getting a Copy of the %s Repository"),$vcs_name)
        ."</h2>\n";
  utils_get_content($vcs_exfix."/index");
  site_project_footer(array());
}
?>
