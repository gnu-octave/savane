<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: index.php 4567 2005-06-30 17:19:37Z toddy $
#
# Copyright 2002-2003 (c) Mathieu Roy <yeupou--at--gnu.org>
# Copyright 2005      (c) Sylvain Beucler <beuc--at--beuc.net>
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
require "../include/faq.php";

function print_faq_list($gr_id, $excluded_question)
{
  $GLOBALS[sys_debug_where] = __FILE__.':'.__LINE__.':print_faq_list($gr_id, $question)';
  unset($questions_array);

  print "<ul>";
  
  foreach (faq_get_files() as $file)
    {
      if ($file != $excluded_question)
	{
	  print '<li><a href="'.$GLOBALS['sys_home'].'faq/?group_id='.$gr_id.'&amp;question='.$file.'">'
	    .faq_filename2question($file)."</a><br />\n";
	}
    }
  
  print "</ul>";
}


############## content

$group_id = $GLOBALS['sys_group_id'];
project_get_object($group_id);
site_project_header(array('group'=>$group_id,'toptab'=>'faq'));

if (isset($question)) 
{
  $faq_files = faq_get_files();

  // Search the file in the faq files. If it's not, somebody might be
  // trying to access unauthorized files
  $i = 0;
  while (($i < count($faq_files)) && ($question_file = $faq_files[$i++]))
    {
      if ($question == $question_file)
	{
	  if (faq_print_html($question_file)) {
	    print "<p>&nbsp;</p><h3>"._("Other questions:")."</h3>";
	  }
	  break;
	}
    }
# Doesn't look like a good idea:
# if(!$res)
#   { print_faq($files_dir.$subdir, $group_id, $question); }
}

print_faq_list($group_id, $question);

#if(file_exists($files_dir)){
#print_faq_list($files_dir, $project, $group_id, $subdir, $question, 0);
#}



print '
<p>&nbsp;</p><h3>'._("The FAQ is also available in the following formats:").'</h3>
<ul>
<li><a href="export_html.php?group_id='.$group_id.'">HTML</a>
<li><a href="export_texinfo.php?group_id='.$group_id.'">Texinfo</a>
</ul>';


# other stuff

/* temporarily removed these hardcoded links
if($GLOBALS['allhelp'] == 1){
  if($GLOBALS['admin'] == 1){
  print "<h1>Other documentation</h1>
  <ul>
  <li><a href=\"../docs/admin.php\">Savannah administrator's guide</a>
  <li><a href=\"../cvs/?group_id=$group_id\">Local CVS information</a>
  <li><a href=\"../files/admin/?group=$group_name\">Local File List information</a>.
  </ul>";
  }
  else
  {
  print "<h1>Other documentation</h1>
  <ul>
  <li><a href=\"https://pcitapi34.cern.ch/savannah/cvs/?group_id=$group_id\">Local CVS information</a>.
  <li><a href=\"https://pcitapi34.cern.ch/savannah/files/admin/?group=$group_name\">Local File List information</a>.</ul>";
  }
}
*/

site_project_footer(array());
?>
