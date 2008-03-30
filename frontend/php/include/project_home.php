<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
#
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                         Yves Perrin <yves.perrin--cern.ch>
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


require_directory("people");
require_directory("news");
require_directory("stats");
require_once(dirname(__FILE__).'/vars.php');


# if we are at wrong url, redirect
if (!$sys_debug_nobasehost && strcasecmp($_SERVER['HTTP_HOST'], $project->getTypeBaseHost()) != 0 && $project->getTypeBaseHost())
{
  header ("Location: http".(session_issecure()?'s':'')."://".$project->getTypeBaseHost().$_SERVER['PHP_SELF']);
  exit;
}

$project=new Project($group_id);

site_project_header(array());

# ########################### Members of this project
# (little box on the right)

$res_admin = db_execute("SELECT user.user_id AS user_id,user.user_name AS user_name, user.realname AS realname "
			. "FROM user,user_group "
			. "WHERE user_group.user_id=user.user_id AND user_group.group_id=? AND "
			. "user_group.admin_flags = 'A'", array($group_id));


print '
<div class="indexright">
';

print $HTML->box_top(_("Membership Info"));
print '<span class="smaller">';
$adminsnum = db_numrows($res_admin);
$j = 0;
if ($adminsnum > 0)
  {
    print ngettext("Project Admin:", "Project Admins:", $adminsnum);
    while ($row_admin = db_fetch_array($res_admin))
      {
        print '</span></div><div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
        print "&nbsp; - ".utils_link($GLOBALS['sys_home']."users/".$row_admin['user_name'], $row_admin['realname']);
      }
  }

#count of developers on this project
$membersnum = db_fetch_array(db_execute("SELECT COUNT(*) AS count FROM user_group WHERE group_id=? AND admin_flags<>'P' AND admin_flags<>'SQD'", array($group_id)));
print '</span></div><div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
printf(ngettext("%s member", "%s members", $membersnum['count']),'<strong>'.$membersnum['count'].'</strong>');

# if member = 1, it's obviously (or it should be) the project admin
# if there's no admin, we need to get access to the list
# But we show it anyway: this page can be used for request for membership,
# provide more info that the little infobox.
print '</span></div><div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
print '['.utils_link($GLOBALS['sys_home'].'project/memberlist.php?group='.$group, _("View Members")).']</span>'; # [ and ] are cosmetics, not supposed to be affected by any translation

print $HTML->box_bottom();
print '<br />
';
print $HTML->box_top(_("Group identification"));
print '<span class="smaller">';
print _("Id:").' <strong>#'.$group_id.'</strong>';
$j = 0;
print '</span></div><div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
print _("System Name:").' <strong>'.$group.'</strong>';
print '</span></div><div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
print _("Name:").' <strong>'.$project->GetName().'</strong>';
print '</span></div><div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
print _("Group Type:").' <strong>'.$project->getTypeName().'</strong>';
print '</span>';
print $HTML->box_bottom();
print '<br />
';


# As all projects use the cookbook, this is accurate is all cases
print $HTML->box_top(_("Search in this Group"));
print '<span class="smaller">';
print search_box('','');
print '</span>';
print $HTML->box_bottom();

print '
</div><!-- end indexright -->
<div class="indexcenter">
';


# ########################### General Informations

if ($project->getTypeDescription())
{ print '<p>'.$project->getTypeDescription()."</p>\n"; }

if ($project->getLongDescription())
  {
    print "<p>".markup_full(htmlspecialchars($project->getLongDescription()))."</p>\n";
  }
else
  {
    if ($project->getDescription())
      {
        print "<p>" .$project->getDescription()."</p>\n";
      }
    else
      {
        print '<p>';
        printf(_("This project has not yet submitted a short description. You can %ssubmit it%s now."), '<a href="'.$GLOBALS['sys_home'].'project/admin/editgroupinfo.php?group='.$group.'">', '</a>');
        print "</p>\n";
      }
  }

print '<p>'._("Registration Date")._(": ").' '.utils_format_date($project->getStartDate())."\n";

if ($project->CanUse("license"))
{
  $license = $project->getLicense();
  print '<br />'._("License:").' ';
  if (!empty($LICENSE_URL[$license])) {
    print utils_link($LICENSE_URL[$license], $LICENSE[$license]);
  } else {
    if (!empty($LICENSE[$license]))
      print $LICENSE[$license];
    else
      print "Unknown!";
    if ($license == "other") {
      print $project->getLicense_other();
    }
  }
}


if ($project->CanUse("devel_status"))
{
  $devel_status = $project->getDevelStatus();
  print '<br />'._("Development Status")._(": ").' '.$DEVEL_STATUS[$devel_status]."\n";
}
print '</p>
</div><!-- end indexcenter -->
<p class="clearr">&nbsp;</p>
';

# ############################## ###########################
# #######################################################################
# ##############################  ###########################
# #######################################################################

# ########################### News

if ($project->Uses("news")) {

  print '
<div class="splitright">';

  print $HTML->box_top(_("Latest News"));
  print news_show_latest($group_id,4,"true");
  print $HTML->box_bottom();

  print '
</div><!-- end splitright -->
<div class="splitleft">
';

}

# ############################## ADMIN AREA ###########################
# #######################################################################

$odd = 0;
$even = 1;
if ($GLOBALS['sys_group_id'] == $group_id)
{
  if (member_check(0, $group_id, 'A'))
    {
      require $GLOBALS['sys_www_topdir']."/include/features_boxes.php";
      print $HTML->box_top(sprintf(_("Administration: %s server"), $GLOBALS['sys_name']));
      print '<div class="justify">'.sprintf(_("Since you are administrator of this project, which one is the \"system project\", you are administrator of the whole %s server."), $GLOBALS['sys_name']).'</div>';

      print $HTML->box_nextitem(utils_get_alt_row_color($odd));

      print utils_link($GLOBALS['sys_home'].'siteadmin/',
		      html_image("contexts/admin.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Server Admin"))).'&nbsp;'._("Server Main Administration Page"));

      print $HTML->box_nextitem(utils_get_alt_row_color($even));

      print utils_link($GLOBALS['sys_home'].'task/?group='.$GLOBALS['sys_unix_group_name'].'&amp;category_id=1&amp;status_id=1&amp;set=custom',
		       html_image("contexts/admin.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Server Admin"))).'&nbsp;'._("Pending Projects List"));
      $registration_count = number_format(stats_getprojects_pending());
      print " (";
      printf(ngettext("%s registration pending", "%s registrations pending", $registration_count), "<strong>$registration_count</strong>");
      print ")";
      print $HTML->box_bottom();
      print '<br />';
    }
}

if (member_check(0, $group_id, 'A'))
{
  print $HTML->box_top(sprintf(_("Administration: %s project"), $project->getName()));
  print '<div class="justify">'._("As administrator of this project, you can manage members and activate, deactivate and configure your project's tools.").'</div>';

  print $HTML->box_nextitem(utils_get_alt_row_color($odd));

  print utils_link($GLOBALS['sys_home'].'project/admin/?group='.$group,
		  html_image("contexts/main.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Admin Page"))).'&nbsp;'._("Project Main Administration Page"));
  print $HTML->box_bottom();
  print '<br />';
}


# ############################## PUBLIC AREAS ###########################
# #######################################################################

# ################################## QUICK OVERVIEW

function specific_makesep ()
{
  # too specific to be general function
  global $i, $j, $HTML;
  if ($i != $j)
    {
      print $HTML->box_nextitem(utils_get_alt_row_color($i));
      $j = $i;
    }
}

print $HTML->box_top(_("Quick Overview"));
$i = 1;
$j = $i;

# Homepage Link
if ($project->Uses("homepage")
    && $project->getUrl("homepage") != 'http://'
    && $project->getUrl("homepage") != '')
{
  print utils_link($project->getUrl("homepage"),
		   html_image("misc/www.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Homepage"))).'&nbsp;'._("Project Homepage"));
  $i++;
}

# Download area
if($project->Uses("download")) 
{
  specific_makesep();

  # the pointer is always the filelist, this page will handle redirect
  # appropriately in case that no download area is here
  print utils_link($project->getArtifactUrl("files"),
		   html_image("contexts/download.png",array('width'=>'24', 'height'=>'24', 'alt'=>'Filelist')).'&nbsp;'._("Download Area"));
  $i++;
}

# Cookbook Documentation (internal)
# Projects dont have the choice to use it, as there maybe site recipe that
# applies to features they use.
# FIXME: this should print the number of recipes available
specific_makesep();
if (!$project->Uses("extralink_documentation"))
{
  print utils_link($project->getArtifactUrl("cookbook"),
		   html_image("contexts/man.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Docs"))).'&nbsp;'._("Docs"));
  $i++;
}
else
{
  # The project have an external doc? Print it first. See pagemenu.php
  # for explanations about this
  print html_image("contexts/man.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Docs"))).'&nbsp;'._("Docs");

  print '<br /> &nbsp; - '.utils_link($project->getUrl("extralink_documentation"), _("Browse docs (External to Savane)"));
  print '<br /> &nbsp; - '.utils_link($project->getArtifactUrl("cookbook"), _("Browse the cookbook"));
  $i++;

}


# Memberlist
specific_makesep();
print utils_link($GLOBALS['sys_home'].'project/memberlist.php?group='.$group,
	 html_image("contexts/people.png",array('width'=>'24', 'height'=>'24', 'alt'=>'Memberlist')).'&nbsp;'._("Project Memberlist"));
print " (";
printf(ngettext("%s member", "%s members", $membersnum['count']), "<strong>{$membersnum['count']}</strong>");
print ")";
$i++;

if ($project->getGPGKeyring())
{
  specific_makesep();
  print utils_link($GLOBALS['sys_home'].'project/memberlist-gpgkeys.php?group='.$group,
		   html_image("contexts/keys.png",array('width'=>'24', 'height'=>'24', 'alt'=>'GPG Keyring')).'&nbsp;'._("Project Members GPG Keyring"));
  $i++;

}


print $HTML->box_bottom();
print '<br />';


# ################################## COMMUNICATION

if ($GLOBALS['sys_unix_group_name'] == $group ||
    $project->Uses("support") ||
    $project->Uses("forum") ||
    $project->usesMail() ||
    people_project_jobs_rows($group_id) != 0)
{

  print $HTML->box_top(_("Communication Tools"));
  $i = 1;
  $j = $i;

  # Support Manager

  if ($project->Uses("support")) {
    specific_makesep();
    $url = $project->getArtifactUrl("support");

    print utils_link($url,
		     html_image("contexts/help.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Tech Support Manager"))).'&nbsp;'._("Tech Support Manager"));

    if (group_get_artifact_url("support", 0) == $url)
      {

	$res_count = db_execute("SELECT count(*) AS count FROM support WHERE group_id=? AND status_id != 3",
				array($group_id));
	$row_count = db_fetch_array($res_count);


        print " (";
        printf(ngettext("%s open item", "%s open items", $row_count['count']), "<strong>{$row_count['count']}</strong>");
	$res_count = db_execute("SELECT count(*) AS count FROM support WHERE group_id=?", array($group_id));
	$row_count = db_fetch_array($res_count);
	print ', ';
        printf(ngettext("%s total", "%s total", $row_count['count']), "<strong>{$row_count['count']}</strong>");
        print ")\n";

	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=browse&amp;set=open', _("Browse open items"));
	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=additem', _("Submit a new item"), 0, group_restrictions_check($group_id, "support"));
      }

    $i++;
  }


  # Fora
  # Could be reactivated on Savannah.

  if ($project->Uses("forum")) {
    specific_makesep();

    $url = $project->getArtifactUrl("forum");

    print utils_link($url,
		     html_image("contexts/help.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Public Forum"))).'&nbsp;'._("Public Forum"));

    if (group_get_artifact_url("forum", 0) == $url)
      {

	$res_count = db_execute("SELECT count(forum.msg_id) AS count FROM forum,forum_group_list WHERE "
				. "forum_group_list.group_id=? AND forum.group_forum_id=forum_group_list.group_forum_id "
				. "AND forum_group_list.is_public=1", array($group_id));
	$row_count = db_fetch_array($res_count);
	print " (";
        printf(ngettext("%s message in", "%s messages in", $row_count['count']), "<strong>{$row_count['count']}</strong>");

	$res_count = db_execute("SELECT count(*) AS count FROM forum_group_list WHERE group_id=? "
				. "AND is_public=1", array($group_id));
	$row_count = db_fetch_array($res_count);

        print " ";
	printf(ngettext("%s forum", "%s forums", $row_count['count']), "<strong>{$row_count['count']}</strong>");
        print ")\n";
      }
    $i++;

  }

  # Mailing lists

  if ($project->usesMail())
    {
      specific_makesep();
      $url = $project->getArtifactUrl("mail");

      print utils_link($url,
		       html_image("contexts/mail.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Mailing Lists"))).'&nbsp;'._("Mailing Lists"));
      $res_count = db_execute("SELECT count(*) AS count FROM mail_group_list WHERE group_id=? AND is_public=1",
			      array($group_id));
      $row_count = db_fetch_array($res_count);
      print " (";
      printf(ngettext("%s public mailing-list", "%s public mailing-lists", $row_count['count']), "<strong>{$row_count['count']}</strong>");
      print ")";

      $i++;
    }

  # Looking for people

  if (people_project_jobs_rows($group_id) != 0) {
    specific_makesep();
    print utils_link($GLOBALS['sys_home'].'people/?group='.$group,
		     html_image("contexts/people.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("People"))).'&nbsp;'._("This project is looking for people"));

    $job_count = people_project_jobs_rows($group_id);

    print " (";
    printf(ngettext("%s contributor wanted", "%s contributors wanted", $job_count), "<strong>$job_count</strong>");
    print ")";
    $i++;
  }

  print $HTML->box_bottom();
  print '<br />';
}

# ################################## DEVELOPMENT

if ($project->Uses("patch") ||
    $project->Uses("cvs") ||
    $project->Uses("homepage") ||
    $project->Uses("bugs") ||
    $project->Uses("task") ||
    $project->Uses("patch"))
{
  print $HTML->box_top(_("Development Tools"));
  $i = 1;
  $j = $i;

  # SCMs
  #  - check if the SCM is selected by the project
  #  - check if the SCM is the default SCM, if the project use the standard
  #    webpage
  if ($project->Uses("git") || $project->UsesForHomepage("git"))
    {
      specific_makesep();
      $url = $project->getArtifactUrl("git");

      html_image("contexts/cvs.png",array('width'=>'24', 'height'=>'24', 'alt'=>'Git'));
      print '&nbsp;<a href="'.$url.'">'._("Source Code Manager: Git Repository").'</a>';

      if ($project->Uses("git") && $project->getUrl("git_viewcvs") != 'http://' && $project->getUrl("git_viewcvs") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("git_viewcvs").'">'._("Browse Sources Repository").'</a>';
	}
      if ($project->UsesForHomepage("git") && $project->getUrl("cvs_viewcvs_homepage") != 'http://' && $project->getUrl("cvs_viewcvs_homepage") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("cvs_viewcvs_homepage").'">'._("Browse Web Pages Repository").'</a>';
	}
      $i++;
    }

  if ($project->Uses("svn") || $project->UsesForHomepage("svn"))
    {
      $url = $project->getArtifactUrl("svn");
      specific_makesep();

      html_image("contexts/cvs.png",array('width'=>'24', 'height'=>'24', 'alt'=>'Subversion'));
      print '&nbsp;<a href="'.$url.'">'._("Source Code Manager: Subversion Repository").'</a>';

      if ($project->Uses("svn") && $project->getUrl("svn_viewcvs") != 'http://' && $project->getUrl("svn_viewcvs") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("svn_viewcvs").'">'._("Browse Sources Repository").'</a>';
	}
      if ($project->UsesForHomepage("svn") && $project->getUrl("cvs_viewcvs_homepage") != 'http://' && $project->getUrl("cvs_viewcvs_homepage") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("cvs_viewcvs_homepage").'">'._("Browse Web Pages Repository").'</a>';
	}
      $i++;
    }

  if ($project->Uses("arch") || $project->UsesForHomepage("arch"))
    {
      specific_makesep();
      $url = $project->getArtifactUrl("arch");

      html_image("contexts/cvs.png",array('width'=>'24', 'height'=>'24', 'alt'=>'Arch'));
      print '&nbsp;<a href="'.$url.'">'._("Source Code Manager: GNU Arch Repository").'</a>';

      if ($project->Uses("arch") && $project->getUrl("arch_viewcvs") != 'http://' && $project->getUrl("arch_viewcvs") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("arch_viewcvs").'">'._("Browse Sources Repository").'</a>';
	}
      if ($project->UsesForHomepage("arch") && $project->getUrl("cvs_viewcvs_homepage") != 'http://' && $project->getUrl("cvs_viewcvs_homepage") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("cvs_viewcvs_homepage").'">'._("Browse Web Pages Repository").'</a>';
	}
      $i++;
    }

  if ($project->Uses("cvs") || $project->UsesForHomepage("cvs"))
    {

      specific_makesep();
      $url = $project->getArtifactUrl("cvs");

      html_image("contexts/cvs.png",array('width'=>'24', 'height'=>'24', 'alt'=>'CVS'));
      print '&nbsp;<a href="'.$url.'">'._("Source Code Manager: CVS Repository").'</a>';
      if ($project->Uses("cvs") && $project->getUrl("cvs_viewcvs") != 'http://' && $project->getUrl("cvs_viewcvs") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("cvs_viewcvs").'">'._("Browse Sources Repository").'</a>';
	}
      if ($project->UsesForHomepage("cvs") && $project->getUrl("cvs_viewcvs_homepage") != 'http://' && $project->getUrl("cvs_viewcvs_homepage") != '')
	{
	  print '<br /> &nbsp; - <a href="'.$project->getUrl("cvs_viewcvs_homepage").'">'._("Browse Web Pages Repository").'</a>';
	}
      $i++;
    }



  # Bug tracking

  if ($project->Uses("bugs")) {
    specific_makesep();
    $url = $project->getArtifactUrl("bugs");

    print utils_link($url,
		     html_image("contexts/bug.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Bug Tracking"))).'&nbsp;'._("Bug Tracker"));

    if (group_get_artifact_url("bugs", 0) == $url)
      {

	$res_count = db_execute("SELECT count(*) AS count FROM bugs WHERE group_id=? AND status_id != 3",
				array($group_id));
	$row_count = db_fetch_array($res_count);

        print " (";
        printf(ngettext("%s open item", "%s open items", $row_count['count']), "<strong>{$row_count['count']}</strong>");
        $res_count = db_execute("SELECT count(*) AS count FROM bugs WHERE group_id=?",
				array($group_id));
	$row_count = db_fetch_array($res_count);
	print ', ';
        printf(ngettext("%s total", "%s total", $row_count['count']), "<strong>{$row_count['count']}</strong>");
        print ")\n";

	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=browse&amp;set=open', _("Browse open items"));
	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=additem', _("Submit a new item"), 0, group_restrictions_check($group_id, "bugs"));
      }
    $i++;
  }


  # Task Manager

  if ($project->Uses("task")) {
    specific_makesep();
    $url = $project->getArtifactUrl("task");

    print utils_link($url,
		     html_image("contexts/task.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Task Manager"))).'&nbsp;'._("Task Manager"));

    if (group_get_artifact_url("task", 0) == $url)
      {

	$res_count = db_execute("SELECT count(*) AS count FROM task WHERE group_id=? AND status_id != 3", 
	  array($group_id));
	$row_count = db_fetch_array($res_count);

	print " (";
        printf(ngettext("%s open item", "%s open items", $row_count['count']), "<strong>{$row_count['count']}</strong>");
        $res_count = db_execute("SELECT count(*) AS count FROM task WHERE group_id=?",
	  array($group_id));
	$row_count = db_fetch_array($res_count);
	print ', ';
        printf(ngettext("%s total", "%s total", $row_count['count']), "<strong>{$row_count['count']}</strong>");
        print ")\n";

	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=browse&amp;set=open', _("Browse open items"));
	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=additem', _("Submit a new item"), 0, group_restrictions_check($group_id, "task"));
      }
    $i++;
  }


  # Patch Manager

  if ($project->Uses("patch")) {
    specific_makesep();
    $url = $project->getArtifactUrl("patch");

    print utils_link($url,
		     html_image("contexts/patch.png",array('width'=>'24', 'height'=>'24', 'alt'=>_("Patch Manager"))).'&nbsp;'._("Patch Manager"));


    if (group_get_artifact_url("patch", 0) == $url)
      {
	$res_count = db_execute("SELECT count(*) AS count FROM patch WHERE group_id=? AND status_id != 3",
	  array($group_id));
	$row_count = db_fetch_array($res_count);

        print " (";
        printf(ngettext("%s open item", "%s open items", $row_count['count']), "<strong>{$row_count['count']}</strong>");
	$res_count = db_execute("SELECT count(*) AS count FROM patch WHERE group_id=?",
	  array($group_id));
	$row_count = db_fetch_array($res_count);
	print ', ';
        printf(ngettext("%s total", "%s total", $row_count['count']), "<strong>{$row_count['count']}</strong>");
        print ")\n";

	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=browse&amp;set=open', _("Browse open items"));
	print '<br /> &nbsp; - '.utils_link($url.'&amp;func=additem', _("Submit a new item"), 0, group_restrictions_check($group_id, "patch"));
      }
    $i++;
  }

  print $HTML->box_bottom();
}


# ########################### News

if ($project->Uses("news")) {
  print '
</div><!-- end splitleft -->
';
}


site_project_footer(array());

?>
