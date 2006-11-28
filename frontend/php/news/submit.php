<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

# FIXME: this should use register_globals_off() instead
if ($_POST['group_id'])
   { $group_id= $_POST['group_id'];  }
elseif ($_GET['group_id'])
   { $group_id = $_GET['group_id'];  }

if ($_POST['post_changes'])
   { $post_changes = $_POST['post_changes']; }
if ($_POST['summary'])
   { $summary = $_POST['summary']; }
if ($_POST['details'])
   { $details = $_POST['details']; }
if ($_POST['feedback'])
   { $feedback = $_POST['feedback'];  }
elseif ($_GET['feedback'])
   { $feedback = $_GET['feedback']; }
if ($_POST['group'])
   {   $group = $_POST['group']; }
elseif ($_GET['group'])
   { $group = $_GET['group'];  }
if ($_POST['id'])
   { $id = $_POST['id'];  }
elseif ($_GET['id'])
   { $id = $_GET['id']; }

if (!group_restrictions_check($group_id, "news"))
    {
      exit_error(sprintf(_("Action Unavailable: %s"), group_getrestrictions_explained($group_id, ARTIFACT)));
    }


if (!group_restrictions_check($group_id, "news"))
    {
      exit_error(sprintf(_("Action Unavailable: %s"), group_getrestrictions_explained($group_id, "news")));
    }


if ($update)
{
  $valid = form_check($form_id);

# Insert the new item, with 5 as status: project admin
# must moderate it.
# There must be a title.

  if (!$summary)
    {
      fb(_("Title is missing"),1);
      $valid = 0;
    }

  if ($valid)
    {
      $new_id=forum_create_forum($group_id,$summary,1,0);
      $sql="INSERT INTO news_bytes (group_id,submitted_by,is_approved,date,forum_id,summary,details) ".
	 " VALUES ('$group_id','".user_getid()."','5','".time()."','$new_id','".htmlspecialchars($summary)."','".htmlspecialchars($details)."')";
      $result=db_query($sql);
    }

  if (!$result)
    {
      fb(_("Error doing insert"),1);
    }
  else
    {
#

# $details needs to be keept only if there was an error
      unset($details);
      $feedback = _("News Posted: it will need to be approved by a news manager of this project before it shows on the project front page.");

      form_clean($form_id);

      session_redirect($GLOBALS['sys_home'].'news/?group='.$group_name.'&feedback='.urlencode($feedback));
    }
}

# News must be submitted from a project page


if (!$group_id)
{
  exit_no_group();
}

# Show the submit form
site_project_header(array('title'=>_("Submit News"),
			  'group'=>$group_id,
			  'context'=>'news'));


if($feedback)
{
  print '<h3 class="warn">'.$feedback.'</h3>';
}

print '
		<p class="warn">'._("A news manager of this project will have to review and approve the news.")."</p>\n<p>"
		._("You may include URLs, emails, that will be made clickable, but not HTML.").'
		</p>'.
form_header($PHP_SELF, $form_id).
form_input("hidden", "group_id", $group_id).'
               	<span class="preinput">'._("Subject:").'</span><br/>&nbsp;&nbsp;
		<input type="text" name="summary" value="'.$summary.'" size="65" maxlenght="80" />
		<br />
		<span class="preinput">'.sprintf(_("Details %s:"), markup_info("full")).'</span><br />&nbsp;&nbsp;
		<textarea name="details" rows="20" cols="65" wrap="soft">'.$details.'</textarea><br />'.
form_footer();

site_project_footer(array());


?>
