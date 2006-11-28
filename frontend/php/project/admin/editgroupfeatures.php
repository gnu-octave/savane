<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
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


require "../../include/pre.php";

session_require(array('group'=>$group_id,'admin_flags'=>'A'));


# If this was a submission, make updates
if ($update)
{

  #FIXME: feeds the database with default values... instead of checkbox,
  # it should be select boxes "default/activated/deactivated"

  group_add_history('Changed Activated Features','',$group_id);

  # in the database, these all default to '1',
  # so we have to explicity set 0
  # (this is ugly)
  if (!$use_bugs)
    { $use_bugs=0; }
  if (!$use_mail)
    { $use_mail=0; }
  if (!$use_homepage)
    { $use_homepage=0; }
  if (!$use_download)
    { $use_download=0; }
  if (!$use_patch)
    { $use_patch=0; }
  if (!$use_forum)
    { $use_forum=0; }
  if (!$use_task)
    { $use_task=0; }
  if (!$use_cvs)
    { $use_cvs=0; }
  if (!$use_arch)
    { $use_arch=0; }
  if (!$use_svn)
    { $use_svn=0; }
  if (!$use_news)
    { $use_news=0; }
  if (!$use_news)
    { $use_news=0; }
  if (!$use_support)
    { $use_support=0; }
  if (!$use_extralink_documentation)
    { $use_extralink_documentation=0; }

  $cases = array("use_homepage",
		 "use_bugs",
		 "use_mail",
		 "use_patch",
		 "use_forum",
		 "use_task",
		 "use_cvs",
		 "use_arch",
		 "use_svn",
		 "use_news",
		 "use_support",
		 "use_download",
		 "use_extralink_documentation",
		 "url_homepage",
		 "url_bugs",
		 "url_mail",
		 "url_patch",
		 "url_forum",
		 "url_task",
		 "url_download",
		 "url_cvs",
		 "url_cvs_viewcvs",
		 "url_cvs_viewcvs_homepage",
		 "url_arch",
		 "url_arch_viewcvs",
		 "url_svn",
		 "url_svn_viewcvs",
		 "url_support",
		 "dir_download",
		 "url_extralink_documentation");

  unset($upd_list);
  while (list(,$field) = each($cases))
    {
      $field_name = substr($field, 4, strlen($field));
      $type = substr($field, 0, 3);

      if ($field_name == "bugs")
	{ $field_name = "bug"; }
      if ($field_name == "mail")
	{ $field_name = "mailing_list"; }

      if ($project->CanUse($field_name) ||
	  ($field_name == "extralink_documentation") ||
	  ($field == "url_cvs_viewcvs_homepage" && $project->CanUse("homepage")) ||
	  ($field == "url_cvs_viewcvs" && $project->CanUse("cvs")) ||
	  ($field == "url_arch_viewcvs" && $project->CanUse("arch")) ||
	  ($field == "url_svn_viewcvs" && $project->CanUse("svn")))
	{
	  if ($type == "use" ||
	      ($type == "use" && $field_name == "extralink_documentation" && $project->CanModifyUrl("extralink_documentation")))
	    {
	      $upd_list .= $field."='".$$field."', ";
	    }
	  elseif ($type == "url")
	    {
	      if ($field_name == "bug")
		{ $field_name = "bugs"; }
	      if ($field_name == "mail")
		{ $field_name = "mailing_list"; }

	      if ($project->CanModifyUrl($field_name))
		{
		  $upd_list .= $field."='".$$field."', ";
		}
	    }
	  elseif ($type == "dir" && $field == "dir_download" && $project->CanUse("download") && $project->CanModifyDir("download_dir"))
	    {
	      $upd_list .= $field."='".$$field."', ";
	    }
	}
    }

  if ($upd_list)
    {
      $upd_list = rtrim($upd_list, ", ");

      $sql = "UPDATE groups SET "
	 .$upd_list
	 ." WHERE group_id=$group_id";

      $result = db_numrows(db_query($sql));

      if ($result > 0)
	{ 
	  session_redirect($_SERVER['PHP_SELF']."?group=$group_name&feedback=".rawurlencode(_("Update failed.")));
	}
      else
	{ 
	  # To avoid the fact that we $project was already set and 
	  # that $project =& new Project($group_id); no longer works,
	  # we force reloading the page with a redirection
	  session_redirect($_SERVER['PHP_SELF']."?group=$group_name&feedback=".rawurlencode(_("Update successful.")));
	}

    }
  else
    { 
      fb(_("Nothing to update.")); 
    }
}

# FIXME: this does no longer seem to work, so we do a redirect instead
#$project =& new Project($group_id); # do not mess with previous values


site_project_header(array('title'=>_("Select Features"),'group'=>$group_id,'context'=>'ahome'));


function specific_line ($artifact, $explanation, $use, $increment=1)
{

  # just a little fonction to clean that part of the code, no
  # interest to generalize it
  global $i, $project;
  if ($increment)
	{ $i++; }
  print '<tr>';
  print ' <td class="'.utils_get_alt_row_color($i).'">'.$explanation.'</td>';
  print ' <td class="'.utils_get_alt_row_color($i).'">';
  # print the checkbox to de/activate it
  # (viewcvs cannot be activated or deactivated, they are not in the menu)
  if (!preg_match("/viewcvs/", $artifact))
    { html_build_checkbox("use_".$artifact, $use); }
  else
    { print "---"; }
  print '</td>';
  # print the default setting
  # (extralink_* does not have any default)
  print ' <td class="'.utils_get_alt_row_color($i).'">';
  if (!preg_match("/extralink/", $artifact))
    {
      print '<a href="'.group_get_artifact_url($artifact).'">'.group_get_artifact_url($artifact).'</a>';
    }
  print '</td>';
  # if allowed from the group type, add a text field to put a non-standard
  # url.
  # (news cannot be activated and using a non-standard url, it would
  # broke the news system)
  print ' <td class="'.utils_get_alt_row_color($i).'">';
  if ($project->CanModifyUrl($artifact))
    {
      if ($artifact == "homepage" ||
	  $artifact == "download" ||
	  $artifact == "cvs_viewcvs" ||
	  $artifact == "arch_viewcvs" ||
	  $artifact == "svn_viewcvs" ||
	  preg_match("/viewcvs/", $artifact) ||
	  preg_match("/extralink/", $artifact))
	{
	  $url = $project->getUrl($artifact);
	}
      else
	{
	  $url = $project->getArtifactUrl($artifact);
	}

      print form_input("text", 
		       "url_".$artifact, 
		       $url,
		       'size="20"');
    }
  else
    { print "---"; }
  print '</td>';
  print '</tr>';
}

print _("You can activate or deactivate feature/artifact for your project. In some case, depending on the system administrator's choices, you can even use change the URL for a feature/artifact. If the field 'alternative address' is empty, the standard is used.");
print '<p></p>';

print form_header($PHP_SELF).form_input("hidden", "group_id", $group_id);


$title_arr=array();
$title_arr[]=_("Feature, Artifact");
$title_arr[]=_("Activated?");
$title_arr[]=_("Standard Address");
$title_arr[]=_("Alternative Address");

print html_build_list_table_top ($title_arr);

if ($project->CanUse("homepage"))
{
  specific_line("homepage", _("Homepage"), $project->Uses("homepage"));
  specific_line("cvs_viewcvs_homepage", _("Homepage Source Code Web Browsing"), 0, 0);
}

if ($project->CanModifyUrl("extralink_documentation"))
{ specific_line("extralink_documentation", _("Documentation"), $project->Uses("extralink_documentation")); }

if ($project->CanUse("download"))
{ specific_line("download", _("Download Area"), $project->Uses("download")); }

if ($project->CanUse("download") && $project->CanModifyDir("download_dir"))
{
  $i++; print '<tr>';
  print ' <td class="'.utils_get_alt_row_color($i).'">'._("Download Area Directory").'</td>';
  print ' <td class="'.utils_get_alt_row_color($i).'">';
  print "---";
  print '</td>';
  print ' <td class="'.utils_get_alt_row_color($i).'">';
  print $project->getTypeDir("download");
  print ' </td>';

  print ' <td class="'.utils_get_alt_row_color($i).'">';

  print ' '.form_input("text", 
		       "dir_download", 
		       $project->getDir("download"), 
		       'size="20"');

  print ' </td>';
  print '</tr>';
}

if ($project->CanUse("support"))
{ specific_line("support", _("Support Tracker"), $project->Uses("support")); }

if ($project->CanUse("forum"))
{ specific_line("forum", _("Forum"), $project->Uses("forum")); }

if ($project->CanUse("mailing_list"))
{ specific_line("mail", _("Mailing-List"), $project->usesMail()); }

if ($project->CanUse("cvs") || $project->CanUse("homepage"))
{
  specific_line("cvs", _("CVS"), $project->Uses("cvs"));
  specific_line("cvs_viewcvs", _("CVS Web Browsing"), 0, 0);
}
if ($project->CanUse("arch"))
{
  specific_line("arch", _("GNU Arch"), $project->Uses("arch"));
  specific_line("arch_viewcvs", _("Arch Web Browsing"), 0, 0);
}

if ($project->CanUse("svn"))
{
  specific_line("svn", _("Subversion"), $project->Uses("svn"));
  specific_line("svn_viewcvs", _("Subversion Web Browsing"), 0, 0);
}


if ($project->CanUse("bug"))
{ specific_line("bugs", _("Bugs Tracker"), $project->Uses("bugs")); }

if ($project->CanUse("task"))
{ specific_line("task", _("Task Tracker"), $project->Uses("task")); }

if ($project->CanUse("patch"))
{ specific_line("patch", _("Patch Tracker"), $project->Uses("patch")); }

if ($project->CanUse("news"))
{ specific_line("news", _("News"), $project->Uses("news")); }

$HTML->box1_bottom();

print form_footer();

site_project_footer(array());

?>
