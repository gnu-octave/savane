<?php
# Project homepage
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2016  Karl Berry (#devtools anchor for "Source code")
# Copyright (C) 2017 Ineiev
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
require_once(dirname(__FILE__).'/vcs.php');

# If we are at wrong url, redirect.
if (!$sys_debug_nobasehost && strcasecmp($_SERVER['HTTP_HOST'],
                                         $project->getTypeBaseHost()) != 0
    && $project->getTypeBaseHost())
  {
    header ("Location: http".(session_issecure()?'s':'')."://"
            .$project->getTypeBaseHost().$_SERVER['PHP_SELF']);
    exit;
  }
$project=new Project($group_id);
site_project_header(array());

# Members of this project (little box on the right).
$res_admin = db_execute("SELECT user.user_id AS user_id,user.user_name "
                        . "AS user_name, user.realname AS realname "
                        . "FROM user,user_group "
                        . "WHERE user_group.user_id=user.user_id "
                        . "AND user_group.group_id=? AND "
                        . "user_group.admin_flags = 'A' "
                        . "AND user_group.onduty = 1", array($group_id));
print '
<div class="indexright">
';
print $HTML->box_top(_("Membership Info"));
print '<span class="smaller">';
$adminsnum = db_numrows($res_admin);
$j = 0;
if ($adminsnum > 0)
  {
    print $adminsnum < 2? _("Project Admin:"): _("Project Admins:");
    while ($row_admin = db_fetch_array($res_admin))
      {
        print '</span></div>
<div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
        print "&nbsp; - ".utils_link($GLOBALS['sys_home']."users/"
              .$row_admin['user_name'], $row_admin['realname']);
      }
  }

# Count of developers on this project.
$membersnum = db_fetch_array(db_execute("SELECT COUNT(*) AS count "
                                        ."FROM user_group WHERE group_id=? "
                                        ."AND admin_flags<>'P' "
                                        ."AND admin_flags<>'SQD' "
                                        ."AND user_group.onduty = 1",
                                        array($group_id)));
print '</span></div>
<div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
printf(ngettext("%s active member", "%s active members", $membersnum['count']),
       '<strong>'.$membersnum['count'].'</strong>');

# If member = 1, it's obviously (or it should be) the project admin.
# If there's no admin, we need to get access to the list.
# But we show it anyway: this page can be used for request for membership,
# provide more info that the little infobox.
print '</span></div>
<div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';

print '['.utils_link($GLOBALS['sys_home'].'project/memberlist.php?group='
                     .$group, _("View Members")).']</span>';
print $HTML->box_bottom();
print "<br />\n";
print $HTML->box_top(_("Group identification"));
print '<span class="smaller">';
# TRANSLATORS: the argument is group id (a number).
printf (_("Id: <strong>#%s</strong>"), $group_id);
$j = 0;
print '</span></div>
<div class="'.utils_get_alt_row_color($j++)
      .'"><span class="smaller">';
print _("System Name:").' <strong>'.$group.'</strong>';
print '</span></div>
<div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
print _("Name:").' <strong>'.$project->GetName().'</strong>';
print '</span></div>
<div class="'.utils_get_alt_row_color($j++).'"><span class="smaller">';
print _("Group Type:").' <strong>'.$project->getTypeName().'</strong>';
print '</span>';
print $HTML->box_bottom();
print "<br />\n";

if (search_has_group_anything_to_search ($group_id))
  {
    print $HTML->box_top (_("Search in this Group"));
    print '<span class="smaller">' . search_box () . '</span>';
    print $HTML->box_bottom ();
  }

print '
</div><!-- end indexright -->
<div class="indexcenter">
';

# General Information.
if ($project->getTypeDescription())
  print '<p>'.$project->getTypeDescription()."</p>\n";

if ($project->getLongDescription())
  {
    print "<p>".markup_full(htmlspecialchars($project->getLongDescription()))
          ."</p>\n";
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
        printf(
_("This project has not yet submitted a short description. You can <a
href=\"%s\">submit it</a> now."),
               $GLOBALS['sys_home'].'project/admin/editgroupinfo.php?group='
               .$group);
        print "</p>\n";
      }
  }

print '<p>'._("Registration Date:").' '
      .utils_format_date($project->getStartDate())."\n";

if ($project->CanUse("license"))
  {
    $license = $project->getLicense();
    print '<br />'._("License:").' ';
    if (!empty($LICENSE_URL[$license]))
      print utils_link($LICENSE_URL[$license], $LICENSE[$license]);
    else
      {
        if (!empty($LICENSE[$license]))
          print $LICENSE[$license];
        else
          print _("Project license is unknown!");
        if ($license == "other")
          print " - " . $project->getLicense_other();
      }
  }

if ($project->CanUse("devel_status"))
  {
    $devel_status = $project->getDevelStatus();
    print '<br />'._("Development Status:").' '
          .$DEVEL_STATUS[$devel_status]."\n";
  }
print '</p>
</div><!-- end indexcenter -->
<p class="clearr">&nbsp;</p>
';

# News.
if ($project->Uses("news"))
  {

    print '
<div class="splitright">';

    print $HTML->box_top(_("Latest News")
                         . "&nbsp;<a href='{$GLOBALS['sys_home']}news/atom.php?"
                         . "group=$group'"
                         . " class='inline-link'><img alt='rss feed'"
                         . " src='{$GLOBALS['sys_home']}images/common/feed16.png'"
                         . " /></a>");
    print news_show_latest($group_id,4,"true");
    print $HTML->box_bottom();

    print '
</div><!-- end splitright -->
<div class="splitleft">
';
  }

# Admin area.
$odd = 0;
$even = 1;
if ($GLOBALS['sys_group_id'] == $group_id)
  {
    if (member_check(0, $group_id, 'A'))
      {
        require $GLOBALS['sys_www_topdir']."/include/features_boxes.php";
# TRANSLATORS: the argument is site name (like Savannah).
        print $HTML->box_top(sprintf(_("Administration: %s server"),
                             $GLOBALS['sys_name']));
# TRANSLATORS: the argument is site name (like Savannah).
        print '<div class="justify">'.sprintf(_("Since you are administrator of
this project, which one is the &ldquo;system project,&rqduo; you are
administrator of the whole %s server."), $GLOBALS['sys_name'])."</div>\n";

        print $HTML->box_nextitem(utils_get_alt_row_color($odd));
        print utils_link($GLOBALS['sys_home'].'siteadmin/',
                         html_image("contexts/admin.png",
                                     array('width'=>'24', 'height'=>'24',
                                           'alt'=>'')).'&nbsp;'
                                           ._("Server Main Administration Page"));
        print $HTML->box_nextitem(utils_get_alt_row_color($even));
        print utils_link($GLOBALS['sys_home'].'task/?group='
                         .$GLOBALS['sys_unix_group_name']
                         .'&amp;category_id=1&amp;status_id=1&amp;set=custom',
                         html_image("contexts/admin.png",
                                    array('width'=>'24', 'height'=>'24',
                                          'alt'=>'')).'&nbsp;'
                                          ._("Pending Projects List"));
        $registration_count = number_format(stats_getprojects_pending());
        printf(' '.ngettext("(%s registration pending)",
                            "(%s registrations pending)",
                            $registration_count),
               "<strong>$registration_count</strong>");
        print $HTML->box_bottom();
        print "<br />\n";
      }
  }

if (member_check(0, $group_id, 'A'))
  {
# TRANSLATORS: the argument is group name (like GNU Coreutils).
    print $HTML->box_top(sprintf(_("Administration: %s project"),
                         $project->getName()));
    print '<div class="justify">'
          ._("As administrator of this project, you can manage members and
activate, deactivate and configure your project's tools.")."</div>\n";

    print $HTML->box_nextitem(utils_get_alt_row_color($odd));
    print utils_link($GLOBALS['sys_home'].'project/admin/?group='.$group,
                     html_image("contexts/main.png",
                                array('width'=>'24', 'height'=>'24',
                                      'alt'=>'')).'&nbsp;'
                                        ._("Project Main Administration Page"));
    print $HTML->box_bottom();
    print "<br />\n";
  }

# Public areas.
function specific_makesep ()
{
  # Too specific to be general function.
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

if ($project->Uses("homepage")
    && $project->getUrl("homepage") != 'http://'
    && $project->getUrl("homepage") != '')
  {
    print utils_link($project->getUrl("homepage"),
                     html_image("misc/www.png",
                                array('width'=>'24', 'height'=>'24',
                                      'alt'=>'')).'&nbsp;'
                                      ._("Project Homepage"));
    $i++;
  }

if ($project->Uses("download"))
  {
    specific_makesep();

    # The pointer is always the filelist, this page will handle redirect
    # appropriately in case that no download area is here.
    print utils_link($project->getArtifactUrl("files"),
                     html_image("contexts/download.png",
                                array('width'=>'24', 'height'=>'24',
                                      'alt'=>'')).'&nbsp;'
                                      ._("Download Area"));
    $i++;
  }

# Cookbook Documentation (internal).
# Projects dont have the choice to use it, as there maybe site recipe that
# applies to features they use.
# FIXME: this should print the number of recipes available.
specific_makesep();
if ($project->Uses("extralink_documentation")
    && !$project->getUrl("extralink_documentation"))
  {
    print utils_link($project->getArtifactUrl("cookbook"),
                     html_image("contexts/man.png",
                                array('width'=>'24', 'height'=>'24',
                                'alt'=>'')).'&nbsp;'._("Docs"));
    $i++;
  }
elseif ($project->Uses("extralink_documentation")
        && $project->getUrl("extralink_documentation"))
  {
  # The project have an external doc? Print it first. See pagemenu.php
  # for explanations about this.
    print html_image("contexts/man.png",array('width'=>'24', 'height'=>'24',
                                              'alt'=>'')).'&nbsp;'._("Docs");
    print '<br /> &nbsp; - '.utils_link($project->getUrl("extralink_documentation"),
                                        _("Browse docs (External to Savane)"));
    print '<br /> &nbsp; - '.utils_link($project->getArtifactUrl("cookbook"),
                                        _("Browse the cookbook"));
    $i++;
  }

specific_makesep();
print utils_link($GLOBALS['sys_home'].'project/memberlist.php?group='.$group,
                 html_image("contexts/people.png",
                            array('width'=>'24', 'height'=>'24', 'alt'=>''))
                 .'&nbsp;'._("Project Memberlist"));
printf(' '.ngettext("(%s member)", "(%s members)", $membersnum['count']),
       "<strong>{$membersnum['count']}</strong>");
$i++;

if (group_get_preference ($group_id, 'gpg_keyring'))
  {
    specific_makesep();
    print utils_link ($GLOBALS['sys_home']
                       . 'project/release-gpgkeys.php?group=' . $group,
                     html_image("contexts/keys.png",
                                array('width'=>'24', 'height'=>'24', 'alt'=>''))
                     . '&nbsp;' . _("Project Release GPG Keyring"));
    $i++;
  }

print $HTML->box_bottom();
print '<br />';

function open_vs_total_items ($url, $group_id, $artifact)
{
  $res_count = db_execute("SELECT count(*) AS count FROM ".$artifact."
                           WHERE group_id=? AND status_id != 3",
                          array($group_id));
  $row_count = db_fetch_array($res_count);
  $open_num = '<strong>'.$row_count['count'].'</strong>';
  $res_count = db_execute("SELECT count(*) AS count FROM ".$artifact
                          ." WHERE group_id=?",
                          array($group_id));
  $row_count = db_fetch_array($res_count);
  $total_num = '<strong>'.$row_count['count'].'</strong>';
# TRANSLATORS: the arguments are numbers of items.
  printf (' '._('(open items: %1$s, total: %2$s)')."\n", $open_num,
          $total_num);

  print '<br /> &nbsp; - '
        .utils_link($url.'&amp;func=browse&amp;set=open',
                    _("Browse open items"));
  print '<br /> &nbsp; - '
        .utils_link($url.'&amp;func=additem', _("Submit a new item"),
                    0, group_restrictions_check($group_id, $artifact));
}

# Communication.
if ($GLOBALS['sys_unix_group_name'] == $group
    || $project->Uses("support")
    || $project->Uses("forum")
    || $project->usesMail()
    || people_project_jobs_rows($group_id) != 0)
  {
    print $HTML->box_top(_("Communication Tools"));
    $i = 1;
    $j = $i;

    if ($project->Uses("support"))
      {
        specific_makesep();
        $url = $project->getArtifactUrl("support");

        print utils_link($url,
                         html_image("contexts/help.png",
                                    array('width'=>'24', 'height'=>'24',
                                          'alt'=>''))
                       .'&nbsp;'._("Tech Support Manager"));
        if (group_get_artifact_url("support", 0) == $url)
          open_vs_total_items ($url, $group_id, 'support');
        $i++;
      }

    # Fora are disabled on Savannah.
    if ($project->Uses("forum"))
      {
        specific_makesep();
        $url = $project->getArtifactUrl("forum");
        print utils_link($url,
                         html_image("contexts/help.png",
                                    array('width'=>'24', 'height'=>'24',
                                          'alt'=>''))
                         .'&nbsp;'._("Public Forum"));
        if (group_get_artifact_url("forum", 0) == $url)
          {
            $res_count = db_execute("SELECT count(forum.msg_id) AS count "
                                    . "FROM forum,forum_group_list WHERE "
                                    . "forum_group_list.group_id=? "
                                    . "AND forum.group_forum_id="
                                    . "forum_group_list.group_forum_id "
                                    . "AND forum_group_list.is_public=1",
                                    array($group_id));
            $row_count = db_fetch_array($res_count);
            $msg_count = '<strong>'.$row_count['count'].'</strong>';
            $res_count = db_execute("SELECT count(*) AS count "
                                    . "FROM forum_group_list "
                                    . "WHERE group_id=? "
                                    . "AND is_public=1", array($group_id));
            $row_count = db_fetch_array($res_count);
            $fora_count = '<strong>'.$row_count['count'].'</strong>';
# TRANSLATORS: the arguments are numbers of messages and forums.
            printf (' '._('(messages: %1$s, forums: %2$s)')."\n", $msg_count,
                    $fora_count);
          }
        $i++;
      }

    if ($project->usesMail())
      {
        specific_makesep();
        $url = $project->getArtifactUrl("mail");
        print utils_link($url,
                         html_image("contexts/mail.png",
                                    array('width'=>'24', 'height'=>'24',
                                          'alt'=>''))
                         .'&nbsp;'._("Mailing Lists"));
        $res_count = db_execute("SELECT count(*) AS count FROM mail_group_list "
                                ."WHERE group_id=? AND is_public=1",
                                array($group_id));
        $row_count = db_fetch_array($res_count);
        print " ";
        printf(ngettext("(%s public mailing list)", "(%s public mailing lists)",
                        $row_count['count']),
               "<strong>{$row_count['count']}</strong>");
        $i++;
      }

    if (people_project_jobs_rows($group_id) != 0)
      {
        specific_makesep();
        print utils_link($GLOBALS['sys_home'].'people/?group='.$group,
                           html_image("contexts/people.png",
                                      array('width'=>'24', 'height'=>'24',
                                            'alt'=>''))
                           .'&nbsp;'._("This project is looking for people"));
        $job_count = people_project_jobs_rows($group_id);
        printf(ngettext("(%s contributor wanted)", "(%s contributors wanted)",
                        $job_count), "<strong>$job_count</strong>");
        $i++;
      }
    print $HTML->box_bottom();
    print '<br />';
  }

# Development.
if ($project->Uses("patch")
    || $project->Uses("cvs")
    || $project->Uses("homepage")
    || $project->Uses("bugs")
    || $project->Uses("task")
    || $project->Uses("patch"))
  {
    print $HTML->box_top("<div id='devtools'>" . _("Development Tools")
                         . "</div>");
    $i = 1;
    $j = $i;

    function print_scm_entry ($project, &$i, $scm, $scm_name)
    {
      if (!($project->Uses($scm) || $project->UsesForHomepage($scm)))
        return;

      $group_id = $project->getGroupId ();

      specific_makesep();
      $url = $project->getArtifactUrl($scm);

      html_image("contexts/cvs.png",array('width'=>'24', 'height'=>'24',
                 'alt'=>''));
      print '&nbsp;<a href="'.$url.'">';
# TRANSLATORS: the argument is name of VCS (like Git or Bazaar).
      printf (_("%s Repository"), $scm_name);
      print "</a>\n";

      if ($project->Uses($scm) && $project->getUrl($scm."_viewcvs") != 'http://'
          && $project->getUrl($scm."_viewcvs") != '')
        {
          $repos = vcs_get_repos ($scm, $group_id);
          $n = count ($repos);
          if ($n < 2)
            print "<br />\n" . '&nbsp; - <a href="'
                  . $project->getUrl($scm . "_viewcvs")
                  . '">' . _("Browse Sources Repository") . "</a>\n";
          else
            {
              $url0 = preg_replace(':/[^/]*$:', '/',
                                   $project->getUrl($scm . "_viewcvs"));
              print '<p>' . _("Browse Sources Repository") . "</p>\n";
              print "<ul>\n";
              for ($k = 0; $k < $n; $k++)
                print '<li><a href="' . $url0 . $repos[$k]['url']
                      . '">' . $repos[$k]['desc'] . "</a></li>\n";
              print "</ul>\n";
            }
        }
      if ((($scm != 'cvs' && $project->UsesForHomepage($scm))
           || ($scm == 'cvs' && $project->Uses("homepage")))
          && $project->getUrl("cvs_viewcvs_homepage") != 'http://'
          && $project->getUrl("cvs_viewcvs_homepage") != '')
        {
          print '<br /> &nbsp; - <a href="'
                .$project->getUrl("cvs_viewcvs_homepage").'">'
                ._("Browse Web Pages Repository").'</a>';
        }
      $i++;
    } # print_scm_entry

# TRANSLATORS: the string is used as the argument of "%s Repository".
  print_scm_entry ($project, $i, 'git', _("Git"));
# TRANSLATORS: the string is used as the argument of "%s Repository".
    print_scm_entry ($project, $i, 'hg', _("Mercurial"));
# TRANSLATORS: the string is used as the argument of "%s Repository".
    print_scm_entry ($project, $i, 'bzr', _("Bazaar"));
# TRANSLATORS: the string is used as the argument of "%s Repository".
    print_scm_entry ($project, $i, 'svn', _("Subversion"));
# TRANSLATORS: the string is used as the argument of "%s Repository".
    print_scm_entry ($project, $i, 'arch', _("GNU Arch"));
# TRANSLATORS: the string is used as the argument of "%s Repository".
    print_scm_entry ($project, $i, 'cvs', _("CVS"));

    if ($project->Uses("bugs"))
      {
        specific_makesep();
        $url = $project->getArtifactUrl("bugs");
        print utils_link($url,
                           html_image("contexts/bug.png",
                                      array('width'=>'24', 'height'=>'24',
                                            'alt'=>''))
                           .'&nbsp;'._("Bug Tracker"));
        if (group_get_artifact_url("bugs", 0) == $url)
          open_vs_total_items ($url, $group_id, 'bugs');
        $i++;
      }
    if ($project->Uses("task"))
      {
        specific_makesep();
        $url = $project->getArtifactUrl("task");
        print utils_link($url,
                           html_image("contexts/task.png",
                                      array('width'=>'24', 'height'=>'24',
                                            'alt'=>''))
                           .'&nbsp;'._("Task Manager"));
        if (group_get_artifact_url("task", 0) == $url)
          open_vs_total_items ($url, $group_id, 'task');
        $i++;
      }
    if ($project->Uses("patch"))
      {
        specific_makesep();
        $url = $project->getArtifactUrl("patch");
        print utils_link($url,
                           html_image("contexts/patch.png",
                                      array('width'=>'24', 'height'=>'24',
                                            'alt'=>''))
                           .'&nbsp;'._("Patch Manager"));
        if (group_get_artifact_url("patch", 0) == $url)
          open_vs_total_items ($url, $group_id, 'patch');
        $i++;
      }
    print $HTML->box_bottom();
  }

if ($project->Uses("news"))
  print '
</div><!-- end splitleft -->
';
site_project_footer(array());
?>
