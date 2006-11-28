<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require '../include/pre.php';

if ($_POST['group_id'])
   { 
   $group_id = $_POST['group_id']; 
   }
elseif ($_GET['group_id'])
   { 
   $group_id = $_GET['group_id']; 
   }

if (!$group_id) 
{
  $group_id = $GLOBALS['sys_group_id'];
}

# yeupou--gnu.org 2004-09-06: the following simply break the form, see 
# bug #703.
#
#if ($_POST['limit'])
#   { 
#   $limit= $_POST['limit']; 
#   }
#elseif ($_GET['limit'])
#   { 
#   $group_id = $_GET['limit']; 
#   }
   
if (!isset($limit)) 
{ 
  $limit = 10;  
}

if ($_POST['feedback'])
   { 
   $feedback = $_POST['feedback']; 
   }
elseif ($_GET['feedback'])
   { 
   $feedback = $_GET['feedback']; 
   }
   
if ($_POST['group'])
   { 
   $group = $_POST['group']; 
   }
elseif ($_GET['group'])
   { 
   $group = $_GET['group']; 
   }
   
$project=project_get_object($group_id);
if (!$project->Uses("news"))
{ exit_error(_("This project has turned off the news tool.")); }
 
site_project_header(array('group'=>$group_id,
			  'context'=>'news'));


/* permit to the user to specify something */

$form_opening = '<form action="'. $PHP_SELF .'#options" method="get">';
# I18N
# %s is an input field
$form = sprintf(_("Print summaries for the %s latest news."), '<input type="text" name="limit" size="4" value="'.$limit.'" />');
if ($group_name)
{ $form .= '<input type="hidden" name="group" value="'.$group_name.'" />'; }
$form_submit = '<input class="bold" type="submit" value="'._("Apply").'"  />';

print html_show_displayoptions($form, $form_opening, $form_submit);

print "<br />\n";

print $HTML->box_top(_("Latest News Approved - With Summaries"));
print news_show_latest($group_id, $limit, "true", $start_from="nolinks");
print $HTML->box_bottom();

/* A box with no summaries, if they are not all already shown */
if ($limit < news_total_number($group_id)) 
{
  print "<br />\n";
  print $HTML->box_top(_("Older News Approved"));
  print news_show_latest($group_id, 0, "false", $start_from=$limit);
  print $HTML->box_bottom();
}

site_project_footer(array());

?>
