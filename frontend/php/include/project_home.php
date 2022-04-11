<?php
# Project homepage
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2016  Karl Berry (#devtools anchor for "Source code")
# Copyright (C) 2017, 2022 Ineiev
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

require_directory ("people");
require_directory ("news");
require_directory ("stats");
require_once (dirname (__FILE__) . '/vars.php');
require_once (dirname (__FILE__) . '/vcs.php');

# If we are at wrong url, redirect.
$host = $project->getTypeBaseHost ();
if (
    $host && !$sys_debug_nobasehost
    && strcasecmp ($_SERVER['HTTP_HOST'], $host)
)
  {
    $prot = session_issecure()? 'https://': 'http://';
    header ("Location: $prot$host{$_SERVER['PHP_SELF']}");
    exit;
  }
$project=new Project ($group_id);
site_project_header (array ());

# Members of this project (little box on the right).
$res_admin = db_execute ("
  SELECT
    user.user_id AS user_id, user.user_name AS user_name,
    user.realname AS realname
  FROM user, user_group
  WHERE
    user_group.user_id = user.user_id AND user_group.group_id = ?
    AND user_group.admin_flags = 'A' AND user_group.onduty = 1",
  [$group_id]
);
print "\n<div class='indexright'>\n";
print $HTML->box_top (_("Membership Info"));
print '<span class="smaller">';
$next_div = function (&$j)
{
  print "</span></div>\n<div class=\""
    . utils_altrow ($j++) . '"><span class="smaller">';
};
$adminsnum = db_numrows ($res_admin);
$j = 0;
if ($adminsnum > 0)
  {
    print $adminsnum < 2? _("Project Admin:"): _("Project Admins:");
    while ($row_admin = db_fetch_array ($res_admin))
      {
        $next_div ($j);
        print "&nbsp; - "
          . utils_link (
              "{$sys_home}users/{$row_admin['user_name']}",
              $row_admin['realname']
            );
      }
  }

# Count of developers on this project.
$membersnum = db_fetch_array (db_execute ("
  SELECT COUNT(*) AS count
  FROM user_group
  WHERE
    group_id = ? AND admin_flags <> 'P' AND admin_flags <> 'SQD'
    AND user_group.onduty = 1",
  [$group_id]
));

$membersnum = $membersnum['count'];

$next_div ($j);
printf (
  ngettext ("%s active member", "%s active members", $membersnum),
  "<strong>$membersnum</strong>"
);

# If member = 1, it's obviously (or it should be) the project admin.
# If there's no admin, we need to get access to the list.
# But we show it anyway: this page can be used for request for membership,
# provide more info that the little infobox.
$next_div ($j);

print '['
  . utils_link (
     "${sys_home}project/memberlist.php?group=$group", _("View Members")
    )
  . ']</span>';
print $HTML->box_bottom ();
print "<br />\n";
print $HTML->box_top (_("Group identification"));
print '<span class="smaller">';
# TRANSLATORS: the argument is group id (a number).
printf (_("Id: <strong>#%s</strong>"), $group_id);
$j = 0;

$item_arr = [
  _("System Name:") => $group,
  _("Name:") => $project->GetName (),
  _("Group Type:") => $project->getTypeName ()
];

foreach ($item_arr as $key => $val)
  {
    $next_div ($j);
    print "$key <strong>$val</strong>";
  }
unset ($next_div);
print '</span>'. $HTML->box_bottom () . "<br />\n";

if (search_has_group_anything_to_search ($group_id))
  {
    print $HTML->box_top (_("Search in this Group"));
    print '<span class="smaller">' . search_box () . '</span>';
    print $HTML->box_bottom ();
  }

print "\n</div><!-- end indexright -->\n<div class='indexcenter'>\n";

# General Information.
if ($project->getTypeDescription ())
  print '<p>' . $project->getTypeDescription () . "</p>\n";

if ($project->getLongDescription ())
  print "<p>"
    . markup_full (htmlspecialchars ($project->getLongDescription ()))
    . "</p>\n";
else
  {
    if ($project->getDescription ())
      {
        print "<p>" . $project->getDescription () . "</p>\n";
      }
    else
      {
        print '<p>';
        printf (
          _("This project has not yet submitted a short description. "
            . "You can <a\nhref=\"%s\">submit it</a> now."),
          "{$sys_home}project/admin/editgroupinfo.php?group=$group"
        );
        print "</p>\n";
      }
  }

print '<p>' . _("Registration Date:") . ' '
  . utils_format_date ($project->getStartDate ()) . "\n";

if ($project->CanUse ("license"))
  {
    $license = $project->getLicense();
    print "<br />\n" . _("License:") . ' ';
    if (!empty ($LICENSE_URL[$license]))
      print utils_link ($LICENSE_URL[$license], $LICENSE[$license]);
    else
      {
        if (!empty ($LICENSE[$license]))
          print $LICENSE[$license];
        else
          print _("Project license is unknown!");
        if ($license == "other")
          print " - " . $project->getLicense_other ();
      }
  }

if ($project->CanUse ("devel_status"))
  {
    $devel_status = $project->getDevelStatus ();
    print "<br />\n" . _("Development Status:") . ' '
      . $DEVEL_STATUS[$devel_status] . "\n";
  }
print "</p>\n</div><!-- end indexcenter -->\n<p class='clearr'>&nbsp;</p>\n";

if ($project->Uses("news"))
  {

    print "\n<div class='splitright'>";

    print
      $HTML->box_top (
        _("Latest News") . "&nbsp;"
        . "<a href='{$sys_home}news/atom.php?group=$group' "
        . "class='inline-link'><img alt='rss feed' "
        . "src='{$sys_home}images/common/feed16.png' /></a>"
      );
    print news_show_latest ($group_id, 4, "true");
    print $HTML->box_bottom();

    print "\n</div><!-- end splitright -->\n<div class='splitleft'>\n";
  }

$odd = 0;
$even = 1;
function proj_home_img ($ctx)
{
  $img_attr = ['width' => '24', 'height' => '24', 'alt' => ''];
  return html_image ("contexts/admin.png", $img_attr) . '&nbsp;';
}
if ($sys_group_id == $group_id && member_check (0, $group_id, 'A'))
  {
    require "$sys_www_topdir/include/features_boxes.php";
    print $HTML->box_top (
      # TRANSLATORS: the argument is site name (like Savannah).
      sprintf (_("Administration: %s server"), $sys_name));
    print '<div class="justify">';
    # TRANSLATORS: the argument is site name (like Savannah).
    printf (
      _("Since you are administrator of\nthis project, which one is the "
        . "&ldquo;system project,&rqduo; you are\nadministrator of the "
        . "whole %s server."),
      $sys_name
    );
    print "</div>\n";

    print $HTML->box_nextitem (utils_altrow ($odd));
    $img = proj_home_img ("contexts/admin.png");
    print utils_link (
      "${sys_home}siteadmin/", $img . _("Server Main Administration Page")
    );
    print $HTML->box_nextitem (utils_altrow ($even));
    print utils_link (
      "${sys_home}task/?group={$sys_unix_group_name}"
      . '&amp;category_id=1&amp;status_id=1&amp;set=custom',
      $img . _("Pending Projects List")
    );
    $reg_count = number_format (stats_getprojects_pending ());
    print ' ';
    printf (
      ngettext (
         "(%s registration pending)", "(%s registrations pending)", $reg_count
      ),
      "<strong>$reg_count</strong>"
    );
    print $HTML->box_bottom ();
    print "<br />\n";
  }

if (member_check (0, $group_id, 'A'))
  {
    print $HTML->box_top (
      # TRANSLATORS: the argument is group name (like GNU Coreutils).
      sprintf (_("Administration: %s project"), $project->getName())
    );
    print '<div class="justify">'
      . _("As administrator of this project, you can manage members and\n"
          . "activate, deactivate and configure your project's tools.")
      . "</div>\n";

    print $HTML->box_nextitem (utils_altrow ($odd));
    $img = proj_home_img ("contexts/main.png");
    print utils_link (
      "${sys_home}project/admin/?group=$group",
       $img . _("Project Main Administration Page")
    );
    print $HTML->box_bottom();
    print "<br />\n";
  }

# Public areas.
function specific_makesep ()
{
  # Too specific to be general function.
  global $i, $HTML;
  static $j = 0;
  $j_prev = $j;
  $j = $i;
  if ($i <= $j_prev)
    return;
  print $HTML->box_nextitem (utils_altrow ($i));
}

print $HTML->box_top (_("Quick Overview"));
$i = 1;

if ($project->Uses ("homepage")
    && $project->getUrl ("homepage") != 'http://'
    && $project->getUrl ("homepage") != '')
  {
    $img = proj_home_img ("misc/www.png");
    print utils_link (
      $project->getUrl ("homepage"), $img . _("Project Homepage")
    );
    $i++;
  }

if ($project->Uses("download"))
  {
    specific_makesep ();

    # The pointer is always the filelist, this page will handle redirect
    # appropriately in case that no download area is here.
    print utils_link (
      $project->getArtifactUrl ("files"),
      proj_home_img ("contexts/download.png") . _("Download Area")
    );
    $i++;
  }

# Cookbook Documentation (internal).
# Projects don't have the choice to use it, as there maybe site recipe that
# applies to features they use.
# FIXME: this should print the number of recipes available.
specific_makesep ();
if ($project->Uses ("extralink_documentation"))
  {
    $cb_url = $project->getArtifactUrl ("cookbook");
    $extra_link = $project->getUrl ("extralink_documentation");
    $img = proj_home_img ("contexts/man.png") . _("Docs");
    if ($extra_link)
      {
        # The group has an external doc? Print it first. See pagemenu.php
        # for explanations about this.
        print $img;
        $br = "<br />\n&nbsp; - ";
        print $br
          . utils_link ($extra_link, _("Browse docs (External to Savane)"));
        print $br . utils_link ($cb_url, _("Browse the cookbook"));
      }
    else
      print utils_link ($cb_url, $img);
    $i++;
  }

specific_makesep ();
print utils_link(
  "${sys_home}project/memberlist.php?group=$group",
  proj_home_img ("contexts/people.png") . _("Project Memberlist")
);

print ' ';
printf (
  ngettext ("(%s member)", "(%s members)", $membersnum),
  "<strong>$membersnum</strong>"
);
$i++;

if (group_get_preference ($group_id, 'gpg_keyring'))
  {
    specific_makesep ();
    print utils_link (
      "${sys_home}project/release-gpgkeys.php?group=$group",
      proj_home_img ("contexts/keys.png") . _("Project Release GPG Keyring")
    );
    $i++;
  }

print $HTML->box_bottom ();
print "<br />\n";

function open_vs_total_items ($url, $group_id, $artifact)
{
  $res_count = db_execute ("
    SELECT count(*) AS count FROM $artifact
    WHERE group_id = ? AND status_id != 3",
    [$group_id]);
  $row_count = db_fetch_array($res_count)['count'];
  $open_num = "<strong>$row_count</strong>";
  $res_count = db_execute (
    "SELECT count(*) AS count FROM $artifact WHERE group_id = ?",
    [$group_id]
  );
  $row_count = db_fetch_array($res_count)['count'];
  $total_num = "<strong>$row_count</strong>";
  print ' ';
  # TRANSLATORS: the arguments are numbers of items.
  printf (_('(open items: %1$s, total: %2$s)'), $open_num, $total_num);

  print "<br />\n&nbsp; - "
    . utils_link ("$url&amp;func=browse&amp;set=open", _("Browse open items"));
  print "<br />\n&nbsp; - "
    . utils_link (
       "$url&amp;func=additem", _("Submit a new item"),
       0, group_restrictions_check ($group_id, $artifact)
      );
}

$job_num = people_project_jobs_rows ($group_id);

if ($sys_unix_group_name == $group
  || $project->Uses ("support") || $project->Uses ("mail")
  || $job_num)
  {
    $i = 0; specific_makesep (); $i++;
    print $HTML->box_top (_("Communication Tools"));

    if ($project->Uses ("support"))
      {
        specific_makesep ();
        $url = $project->getArtifactUrl("support");

        print utils_link (
          $url, proj_home_img ("contexts/help.png") . _("Tech Support Manager")
        );
        if (group_get_artifact_url ("support", 0) == $url)
          open_vs_total_items ($url, $group_id, 'support');
        $i++;
      }

    if ($project->Uses ("mail"))
      {
        specific_makesep ();
        $url = $project->getArtifactUrl ("mail");
        print utils_link (
          $url, proj_home_img ("contexts/mail.png") . _("Mailing Lists")
        );
        $res_count = db_execute ("
          SELECT count(*) AS count FROM mail_group_list
          WHERE group_id = ? AND is_public = 1",
          [$group_id]
        );
        $row_count = db_fetch_array ($res_count)['count'];
        print " ";
        printf (
          ngettext (
            "(%s public mailing list)", "(%s public mailing lists)", $row_count
           ),
           "<strong>$row_count</strong>"
        );
        $i++;
      }

    if ($job_num)
      {
        specific_makesep ();
        print utils_link (
          "${sys_home}people/?group=$group",
          proj_home_img ("contexts/people.png")
          . _("This project is looking for people")
        );
        printf (
          ngettext (
            "(%s contributor wanted)", "(%s contributors wanted)", $job_num
          ),
          "<strong>$job_num</strong>"
        );
        $i++;
      }
    print $HTML->box_bottom ();
    print "<br />\n";
  }

# Development.
$uses_dev = false;
foreach (['patch', 'cvs', 'homepage', 'bugs', 'task', 'patch'] as $art)
  if ($project->Uses ($art))
    {
      $uses_dev = true;
      break;
    }
if ($uses_dev)
  {
    $i = 0; specific_makesep (); $i++;
    print $HTML->box_top (
      "<div id='devtools'>" . _("Development Tools") . "</div>"
    );
    $i = 1;

    function print_scm_entry ($project, &$i, $scm, $scm_name)
    {
      if (!($project->Uses ($scm) || $project->UsesForHomepage ($scm)))
        return;

      $group_id = $project->getGroupId ();

      specific_makesep ();
      $url = $project->getArtifactUrl ($scm);

      print proj_home_img ("contexts/cvs.png") . "<a href=\"$url\">";
      # TRANSLATORS: the argument is name of VCS (like Git or Bazaar).
      printf (_("%s Repository"), $scm_name);
      print "</a>\n";

      $scm_url = $project->getUrl ("${scm}_viewcvs");
      if (
        $project->Uses ($scm) && $scm_url != 'http://' && $scm_url != ''
      )
        {
          $repos = vcs_get_repos ($scm, $group_id);
          $n = count ($repos);
          if ($n < 2)
            print "<br />\n&nbsp; - <a href=\"$scm_url\">"
              . _("Browse Sources Repository") . "</a>\n";
          else
            {
              $u = preg_replace(':/[^/]*$:', '/', $scm_url);
              print '<p>' . _("Browse Sources Repository") . "</p>\n";
              print "<ul>\n";
              foreach ($repos as $r)
                print "<li><a href=\"$u{$r['url']}\">{$r['desc']}</a></li>\n";
              print "</ul>\n";
            }
        }
      $view_url = $project->getUrl ("cvs_viewcvs_homepage");
      if ((($scm != 'cvs' && $project->UsesForHomepage ($scm))
           || ($scm == 'cvs' && $project->Uses ("homepage")))
          && $view_url != 'http://' && $view_url != '')
        {
          print "<br />\n&nbsp; - <a href=\"$view_url\">"
            . _("Browse Web Pages Repository") . '</a>';
        }
      $i++;
    } # print_scm_entry

    $vcses = [
      # TRANSLATORS: the string is used as the argument of "%s Repository".
      'git' => _("Git"), 'hg' => _("Mercurial"), 'bzr' => _("Bazaar"),
      'svn' => _("Subversion"), 'arch' => _("GNU Arch"), 'cvs' => _("CVS"),
    ];
    foreach ($vcses as $v => $label)
      print_scm_entry ($project, $i, $v, $label);

    $tracker_arr = [
      'bugs' => ['bug', _("Bug Tracker")],
      'task' => ['task', _("Task Manager")],
      'patch' => ['patch', _("Patch Manager")],
    ];
    foreach ($tracker_arr as $k => $val)
      if ($project->Uses ($k))
        {
          specific_makesep ();
          $url = $project->getArtifactUrl ($k);
          $img = proj_home_img ("contexts/{$val[0]}.png");
          print utils_link ($url,  $img . $val[1]);
          if (group_get_artifact_url ($k, 0) == $url)
            open_vs_total_items ($url, $group_id, $k);
          $i++;
        }
    print $HTML->box_bottom();
  } # $uses_dev

if ($project->Uses ("news"))
  print "\n</div><!-- end splitleft -->\n";
site_project_footer(array());
?>
