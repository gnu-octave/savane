<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 2002-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
#                         Derek Feichtinger <derek.feichtinger--at--cern.ch>
#                         Yves Perrin <yves.perrin--at--cern.ch>
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

if ($group_id) {

  $files_dir = $project->getDir("download");
  $files_path = $project->getUrl("download");

  dbg("dir:$files_dir path:$files_path");

  # If nothing is found, redirect on the configured area
  $i = 0;
  $content = '';
  if (!$project->CanUse("download") || $files_dir == '/' || $files_dir == '')
    { Header("Location: $files_path"); }
  else
    {

      # ################################### BUILD CONTENT

      $content .= '<p>'._("Below is a list of files of the project. You can access the project download area directly at").' <a href="'.$files_path.'">'.$files_path.'</a>';

      /* check if variables are defined */
      /* thread_max define the number of versions details to show */
      if (!isset($thread_max)) {
	$thread_max = "4";
      } else {
	$thread_max = $thread_max - 1;
      }
      if (!isset($thread_show)) {
	$thread_show = "0";
      }

      if (!isset($highlight)) {
	$highlight = "?.?.?";
      }

      /* permit to the user to specify something */
      $content .=  '<form action="'. $_SERVER['PHP_SELF'] .'" method="get">';
      $content .=  '<input type="hidden" NAME="group_id" value="'.$group_id.'" />';
      $content .=  '<h3>'.sprintf(_("Show %s and highlight version %s."),'<select name="thread_max"><option value="1"> 1</option><option value="3"> 3</option><option value="5" selected> 5</option><option value="10">10</option><option value="20">20</option><option value="50">50</option></select>', '<input type="text" size="6" name="highlight" value="'.$highlight.'" />');

      $content .= '&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="'._("Apply").'" /></form>';
      $content .= '</h3>';

      $content .= '<p>';

      if (is_dir($files_dir))
	{
	  $package_dir = opendir($files_dir);
	  $ext = ".pkg";
	  $lext = strlen($ext);
	  while ($package_name = readdir($package_dir))
	    {
	      $lfile=strlen($package_name)-$lext;
	      if(@is_dir($files_dir."/".$package_name) && substr($package_name,$lfile,$lext)==$ext)
		{
		  /* we create a box for each package */
		  $content .= $HTML->box1_top(ereg_replace(".pkg","",$package_name), 0);
		  $version_array = ""; /* initialise array for version listing */
		  $content .= '<tr><td><table width="100%">'."\n";

		  $i=0;
		  $content .= '<tr class="'. utils_get_alt_row_color($i) .'"><td>&nbsp;&nbsp;</td><td width="55%" class="bold">&nbsp;</td><td width="15%" class="center"><em class="smaller">Filesize</em></td><td width="15%" class="center"><em class="smaller">File Type</em></td><td width="15%" class="center"><em class="smaller">Date</em></td></tr>'."\n";

		  /* create an array of versions */
		  $version_dir = opendir($files_dir."/".$package_name);
		  while ($version_name = readdir($version_dir))
		    {
		      if(is_dir($files_dir."/".$package_name."/".$version_name) && $version_name != ".." && $version_name != ".")
			{
			  $version_array[] = "$version_name";
			}
		    }

		  /* an entry for each version in the array */
		  $version_max = count($version_array);
		  if (is_array($version_array))
		    { rsort($version_array); }
		  for ($x = 0; $x <$version_max; $x++)
		    {

		      /* we create a row for each version */
		      $content .= '<tr class="';
		      if ($highlight==$version_array[$x]) {

			$this_row_color = 'boxhighlight';
		      }
		      else
			{
			  $this_row_color = utils_get_alt_row_color($i);
			}
		      /* if > $thread_max, we dont show any package details */
		      /* if = $thead_show, we show package details */
		      if ($thread_max < $x && $thread_show != $package_name.$x)
			{
			  $content .= $this_row_color.'"><td>&nbsp;&nbsp;</td><td width="55%" class="bold"><a href="'.$_SERVER['PHP_SELF'].'?group_id='.$group_id.'&amp;thread_show='.$package_name.$x.'#'.ereg_replace(".pkg","",$package_name).$version_array[$x].'" name="'.ereg_replace(".pkg","",$package_name).$version_array[$x].'">';
			  $content .= $version_array[$x];
			  $content .= '</a></td><td width="15%" class="center">&nbsp;</td><td width="15%" class="center">&nbsp;</td><td width="15%" class="center">&nbsp;</td></tr>'."\n";
			}
		      else
			{
			  $content .= $this_row_color.'"><td>&nbsp;&nbsp;</td><td width="55%" class="bold"><a name="'.ereg_replace(".pkg","",$package_name).$version_array[$x].'"></a>';
			  $content .= $version_array[$x];
			  $file_array = ""; /* initialise array for files  */
			  $content .=  '</td><td width="15%" class="center">&nbsp;</td><td width="15%" class="center">&nbsp;</td><td width="15%" class="center">&nbsp;</td></tr>'."\n";

			  /* create an array of files */
			  $final_dir = opendir($files_dir."/".$package_name."/".$version_array[$x]);
			  while ($file_name = readdir($final_dir))
			    {
			      if($file_name != ".." && $file_name != ".")
				{
				  $file_array[] = "$file_name";
				}
			    }
			  closedir($final_dir);

			  /* an entry for each file in the array */
			  $file_max = count($file_array);
			  if(is_array($file_array))
			    { sort($file_array); }
			  for ($z = 0; $z <$file_max; $z++)
			    {
			      # Allow directories as items
			      # if (is_file($files_dir."/".$package_name."/".$version_array[$x]."/".$file_array[$z]))
			      #{
				  $content .= '<tr class="'. $this_row_color .'"><td>&nbsp;&nbsp;</td><td>';
				  $content .=  '&nbsp;&nbsp;&nbsp;<a href="'.$files_path.'/'.$package_name.'/'.$version_array[$x].'/'.$file_array[$z].'">'.$file_array[$z].'</a>';
				  $content .= '</td><td class="center">';
				  $content .= utils_filesize($files_dir."/".$package_name."/".$version_array[$x]."/".$file_array[$z]).'</td>';
				  $content .= '<td class="center">'.utils_fileextension($files_dir."/".$package_name."/".$version_array[$x]."/"
											.$file_array[$z]).'</td>';
				  $content .= '<td class="center">'.utils_format_date(@filemtime($files_dir."/".$package_name."/".$version_array[$x]."/".$file_array[$z])).'</td>';
				  $content .= '</tr>'."\n";
			      #}
			    }

			}
		      $i++;
		    }
		  closedir($version_dir);

		  $content .= '</table>'."\n";
		  $content .= $HTML->box1_bottom(0);
		  $content .= '<br /><br /><br />'."\n";
		}
	    }
	  closedir($package_dir);
	}


    }


  # ################################### REDIRECT IF NO RESULTS

  if (!$i)
    {
      Header("Location: $files_path");
    }


  # ################################### PRINT

  site_project_header(array('group'=>$group_id,'context'=>'download'));


  print $content;

  site_project_footer(array());
}
else
{

  exit_no_group();

}

?>
