<?php
# Page-specific menu (Bugs/Tasks/Admin/Source Code/...)
#
# Copyright (C) 1999-2000 The SourceForge Crew (was in Layout.class)
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2015, 2016 Karl Berry (tiny reordering, downcasing, #devtools)
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

# Note about submenus: they should always contain a verb, enlightening the
# action they permit to do.
# The related pages the submenus point to should have title that are the
# same as the submenu, or almost.

# Menu specific to the current page: group if group page, my if my pages etc.
function pagemenu ($params)
{
  # Skip topmenu if passed as parameter.
  if (isset($params['notopmenu']) && $params['notopmenu'])
    return;

  # Reset important variables.
  unset($GLOBALS['stone_age_menu_submenu_content'],
        $GLOBALS['stone_age_menu_lastcontext']);
  $GLOBALS['submenucount'] = 0;

  # Print topmenu title.
  # We use javascript for browsers that does not support CSS correctly.
  if (is_broken_msie()
      && empty($_GET['printer'])
      && !$GLOBALS['stone_age_menu'])
    print "<!-- begin pagemenu -->\n";

  print '
<h1 class="toptitle"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
  .'.theme/contexts/'.context_icon().'.orig.png" width="48" height="48" alt="'
  .context_alt().'" class="pageicon" />';
  $title = context_title();
  if ($title)
    print $title;
  if ($title && !empty($params['title']))
    print
      # TRANSLATORS: this string is used to separate context from
      # further description, like _("Bugs")._(": ").$bug_title.
          _(": ");
  if (!empty($params['title']))
    print $params['title'];
  print '</h1>';

  # Print topmenu subtitle.
  unset($scope);
  switch (CONTEXT)
    {
    case 'my': $scope = _("My"); break;
    case isset($GLOBALS['group_id']): $scope = _("Group"); break;
    case 'siteadmin': $scope = _("Site Admin"); break;
    default: $scope = _("Site Wide");
    }
# TRANSLATORS: the argument is context like "My", "Group", "Site Admin",
# "Site wide", "Submenu".
  print '
    <div class="topmenu" id="topmenu">
      <span class="topmenutitle" title="'.sprintf(_("%s Scope"), $scope).'">
        '.$scope.'
      </span><!-- end topmenutitle -->
';

  $have_context = false;
  switch (CONTEXT)
    {
    case 'my': case 'siteadmin': $have_context = true;
    }
  if (isset($GLOBALS['group_id']))
    {
      $project = project_get_object($GLOBALS['group_id']);
      if (!$project->isError())
        $have_context = true;
    }
  if ($have_context)
    print '       <div class="topmenuitem"><ul id="topmenuitem">

';
  # Call the relevant menu.
  switch (CONTEXT)
    {
    case 'my': pagemenu_my(); break;
    case 'siteadmin': pagemenu_siteadmin(); break;
    case isset($GLOBALS['group_id']): pagemenu_group(); break;
    }
  if ($have_context)
    print  '      </ul></div><!-- end topmenuitem -->
';
print ' </div><!-- end pagemenu -->
';
  # Add the stone age submenu if relevant.
  if (!empty($GLOBALS['stone_age_menu'])
      && !empty($GLOBALS['stone_age_menu_submenu_content']))
    {
      $scope = _("Submenu");
# TRANSLATORS: the argument is context like "My", "Group", "Site Admin",
# "Site wide", "Submenu".
      print '<!--stone age submenu begin --><br />
    <div class="topmenu" id="topmenu">
      <span class="topmenutitle" title="'.sprintf(_("%s Scope"), $scope).'">
        '.$scope.'
      </span><!-- end topmenutitle -->
      <div class="topmenuitem"><ul id="topmenuitem">
      '.$GLOBALS['stone_age_menu_submenu_content'].'
      </ul></div><!-- end topmenuitem -->
    </div>
<!-- end stone age subemenu -->
';
    }

  # Here we do something quite strange to avoid an overlap of the menu:
  #    We add two divs, one in float right, the other with clear
  #    right.
  #    Ideally, only a clear left would have done trick, but it does not
  #    because the menu is a float left like the menu.
  # This is required for Mozilla and Konqueror. Please, don't change that.
  print '<div id="topmenunooverlap">&nbsp;</div>'
        .'<div id="topmenunooverlapbis">&nbsp;</div>';
}

# Column title.
function pagemenu_submenu_title ($title, $url, $selected=0, $available=1, $help='')
{
  $GLOBALS['submenucount']++;
  if ($selected)
    $class = "tabselect";
  else
    $class = "tabs";

  # If we use the stone age menu, we need to be able to determine later
  # the current submenu.
  # As the current code was not planned to be forced to make context guessing
  # for submenus, we are forced to do it in a quite awkward way.
  if (!empty($GLOBALS['stone_age_menu']))
    {
      $GLOBALS['stone_age_menu_lastcontext'] = context_guess_from_url($url, true);
    }

# yeupou--gnu.org 2006-09-08:
# Dirty hack to get rid of serious issue in MSIE handling of
# CSS.
# Please, do never add such an hack in savane somewhere else without
# talking about it on savane-dev.
# DON'T CHANGE width, margin and padding size, or you'll be very sorry
# for MSIE users.
  if (is_broken_msie() && empty($_GET['printer']))
    {
      # Normally we should have white-space: nowrap; but then MSIE make
      # the text disappear on mouse out.
      $title = preg_replace("/\s/", "&nbsp;", $title);
    }
  # We make appear the submenu with both CSS and javascript. That is because
  # some browsers (MSIE) have poor CSS supports and cannot do it otherwise.
  # (When it gains focus, the submenu appears.)
   print '        <li class="topmenuitemmainitem">
          '.utils_link($url, $title, $class, $available, $help);
}

function pagemenu_submenu_end ()
{
  print '        </li><!-- end topmenuitemmainitem -->

';
}

function pagemenu_submenu_content ($content)
{
  # Stone age menu got submenu in a new menu line below, just like if there
  # was two menus.
  # So when asked to print the content, we determine if this is the content
  # that is supposed to show up in the submenu line (as there is only one
  # submenu, it means that the only submenu available is the one of the
  # current content) and if it is the case, we save it a global to be used
  # later (that was unset at the begin of this page).
  if ($GLOBALS['stone_age_menu'])
    {
      if ($GLOBALS['stone_age_menu_lastcontext'] == CONTEXT)
        $GLOBALS['stone_age_menu_submenu_content'] = $content;
      return;
    }
  print '
          <ul id="submenu'.$GLOBALS['submenucount']
          .'" class="topmenuitemsubmenu">'.$content.'
          </ul><!-- end submenu -->
';
}

function pagemenu_submenu_entry ($title, $url, $available=1, $help="")
{
  $class = "topmenuitemsubmenu";

  if ($GLOBALS['stone_age_menu'])
    $class = "topmenuitemmainitem";
  return '
            <li class="'.$class.'">'.
              utils_link($url, $title, '', $available, $help).'
            </li>';
}

function pagemenu_submenu_entry_separator ()
{
  if ($GLOBALS['stone_age_menu'])
    return '<br />';
  return '
            <li class="topmenuitemsubmenuseparator">&nbsp;</li>';
}

# Menu specific to My pages.
function pagemenu_my ()
{
  pagemenu_submenu_title(_("Incoming Items"),
                         $GLOBALS['sys_home'].'my/',
                         SUBCONTEXT == 'browsing',
                         1,
                         _("What's new for me?"));
  pagemenu_submenu_end();

  pagemenu_submenu_title(_("Items"),
                         $GLOBALS['sys_home'].'my/items.php',
                         SUBCONTEXT == 'items',
                         1,
                         _("Browse my items (bugs, tasks, bookmarks...)"));
  pagemenu_submenu_end();

  if (user_use_votes())
    {
      pagemenu_submenu_title(_("Votes"),
                             $GLOBALS['sys_home'].'my/votes.php',
                             SUBCONTEXT == 'votes',
                             1,
                             _("Browse items I voted for"));
      pagemenu_submenu_end();
    }

  pagemenu_submenu_title(_("Group Membership"),
                         $GLOBALS['sys_home'].'my/groups.php',
                         SUBCONTEXT == 'groups',
                         1,
                         _("List the groups I belong to"));
  pagemenu_submenu_end();

  if (user_get_preference("use_bookmarks"))
    {
      pagemenu_submenu_title(_("Bookmarks"),
                             $GLOBALS['sys_home'].'my/bookmarks.php',
                             SUBCONTEXT == 'bookmarks',
                             1,
                             _("List my bookmarks"));
      pagemenu_submenu_end();
    }

  pagemenu_submenu_title(_("Account Configuration"),
                         $GLOBALS['sys_home'].'my/admin/',
                         SUBCONTEXT == 'configure',
                         1,
         _("Account configuration: authentication, cosmetics preferences..."));
  pagemenu_submenu_end();
}

# Menu specific to Group pages.
function pagemenu_group ()
{
  global $group_id, $sys_group_id, $project;

  if (CONTEXT == 'userguide')
    {
      $GLOBALS['group_id'] = $sys_group_id;
      $GLOBALS['group_name'] = $GLOBALS['sys_unix_group_name'];
    }

  $is_admin = FALSE;
  if (member_check(0, $group_id, 'A'))
    $is_admin = TRUE;

  $project = project_get_object($group_id);
  if ($project->isError())
    return;
  pagemenu_submenu_title(_("Main"),
                         $GLOBALS['sys_home'].'projects/'.$project->getUnixName().'/',
                         CONTEXT == 'project',
                         1,
# TRANSLATORS: the argument is site name like Savannah.
                         sprintf(_("Project Main Page at %s"), $GLOBALS['sys_name']));
  unset($ret);

  $ret = pagemenu_submenu_entry(_("Main"),$GLOBALS['sys_home'].'projects/'
                                          .$project->getUnixName().'/')
    .pagemenu_submenu_entry(_("View members"),$GLOBALS['sys_home']
                           .'project/memberlist.php?group='
                           .$project->getUnixName())
    .pagemenu_submenu_entry(_("Search"),$GLOBALS['sys_home']
                           .'project/search.php?group='.$project->getUnixName());

  if (member_check(0, $group_id, 'A'))
    {
      # If admin, print a link to the admin main page and an extra useless
      # link to main page.  Use &nbsp; to avoid bad line breaks in
      # stone age menu.
      $ret .=
        pagemenu_submenu_entry_separator()
        .pagemenu_submenu_entry('<strong>'._("Administer:").'</strong>',
                               $GLOBALS['sys_home'].'project/admin/?group='
                               .$project->getUnixName())
        .pagemenu_submenu_entry(_("Edit public info"),$GLOBALS['sys_home']
                               .'project/admin/editgroupinfo.php?group='
                               .$project->getUnixName())
        .pagemenu_submenu_entry(_("Select features"),$GLOBALS['sys_home']
                                .'project/admin/editgroupfeatures.php?group='
                                .$project->getUnixName())
        .pagemenu_submenu_entry(_("Manage&nbsp;members"),$GLOBALS['sys_home']
                                .'project/admin/useradmin.php?group='
                                .$project->getUnixName())
        .pagemenu_submenu_entry(_("Manage&nbsp;squads"),$GLOBALS['sys_home']
                                .'project/admin/squadadmin.php?group='
                                .$project->getUnixName())
        .pagemenu_submenu_entry(_("Set&nbsp;permissions"),$GLOBALS['sys_home']
                                .'project/admin/userperms.php?group='
                                .$project->getUnixName())
        .pagemenu_submenu_entry(_("Set&nbsp;notifications"),$GLOBALS['sys_home']
                                .'project/admin/editgroupnotifications.php?group='
                                .$project->getUnixName())
        .pagemenu_submenu_entry(_("Show&nbsp;history"),$GLOBALS['sys_home']
                                .'project/admin/history.php?group='
                                .$project->getUnixName())
        .pagemenu_submenu_entry(_("Copy&nbsp;configuration"),$GLOBALS['sys_home']
                                .'project/admin/conf-copy.php?group='
                                .$project->getUnixName())
        .pagemenu_submenu_entry(_("Post&nbsp;jobs"),$GLOBALS['sys_home']
                                .'people/createjob.php?group='
                                .$project->getUnixName(),1,
           _("Post a request for contribution"))
        .pagemenu_submenu_entry(_("Edit jobs"),$GLOBALS['sys_home']
                                .'people/editjob.php?group='
                                .$project->getUnixName(),1,
           _("Edit previously posted request for contribution"));
    }
  pagemenu_submenu_content($ret);
  pagemenu_submenu_end();

  if ($project->Uses("homepage")
      && $project->getUrl("homepage") != 'http://'
      && $project->getUrl("homepage") != '')
    {
      pagemenu_submenu_title(_("Homepage"),
                             $project->getUrl("homepage"),
                             0,
                             1,
                             _("Browse project homepage (outside of Savane)"));
      pagemenu_submenu_end();
    }

  if ($project->Uses("download"))
    {
      pagemenu_submenu_title(_("Download"),
                             $project->getArtifactUrl("files"),
                             CONTEXT == 'download',
                             1,
                             _("Visit download area: files released"));
      pagemenu_submenu_end();
    }

  # The cookbook is the default and cannot be deactivate as it contains
  # site docs useful for the project depending on the used features.
  #
  # However, if external doc is set, the link will have no effect
  # (See pagemenu_group_trackers() for more details about the document menu
  # behavior).
  $url = $project->getArtifactUrl("cookbook");
  if ($project->Uses("extralink_documentation"))
    $url = '#';

  if ($project->getUrl("extralink_documentation"))
    {
      pagemenu_submenu_title(_("Docs"), $url,
                             CONTEXT == 'cookbook', 1,
                             _("Docs: Cookbook, etc"));
      pagemenu_submenu_content(pagemenu_group_trackers("cookbook"));
      pagemenu_submenu_end();
    }

  if ($project->Uses("support"))
    {
      pagemenu_submenu_title(_("Support"),
                             $project->getArtifactUrl("support"),
                             CONTEXT == 'support',
                             1,
          _("Tech Support Tracker: post, search and manage support requests"));
      pagemenu_submenu_content(pagemenu_group_trackers("support"));
      pagemenu_submenu_end();
    }

  # Fora are normally deprecated on savane.
  if ($project->Uses("forum"))
    {
      pagemenu_submenu_title(_("Forum"),
                             $project->getArtifactUrl("forum"),
                             CONTEXT == 'forum');
      pagemenu_submenu_end();
    }

  if ($project->usesMail())
    {
      pagemenu_submenu_title(_("Mailing lists"),
                             $project->getArtifactUrl("mail"),
                             CONTEXT == 'mail',
                             1,
                             _("List existing mailing lists"));
      if ($is_admin)
        {
          $ret = '';
          $ret .=
            pagemenu_submenu_entry(_("Browse"),
                                   $GLOBALS['sys_home'].'mail/?group='
                                   .$project->getUnixName(),
                                   _("List existing mailing lists"))
            .pagemenu_submenu_entry_separator()
            .pagemenu_submenu_entry('<strong>'._("Configure:").'</strong>',
                                   $GLOBALS['sys_home'].'mail/admin/?group='
                                   .$project->getUnixName());
          pagemenu_submenu_content($ret);
        }
      pagemenu_submenu_end();
    }

  if ($project->Uses("cvs")
      || $project->UsesForHomepage("cvs")
      || $project->Uses("arch")
      || $project->UsesForHomepage("arch")
      || $project->Uses("svn")
      || $project->UsesForHomepage("svn")
      || $project->Uses("git")
      || $project->UsesForHomepage("git")
      || $project->Uses("hg")
      || $project->UsesForHomepage("hg")
      || $project->Uses("bzr")
      || $project->UsesForHomepage("bzr"))
    {
      # If it uses only one SCM, main link points to it.
      $cvs = FALSE;
      $svn = FALSE;
      $arch = FALSE;
      $git = FALSE;
      $hg = FALSE;
      $bzr = FALSE;
      if ($project->Uses("cvs") || $project->UsesForHomepage("cvs"))
        $cvs = 1;
      if ($project->Uses("arch") || $project->UsesForHomepage("arch"))
        $arch = 1;
      if ($project->Uses("svn") || $project->UsesForHomepage("svn"))
        $svn = 1;
      if ($project->Uses("git") || $project->UsesForHomepage("git"))
        $git = 1;
      if ($project->Uses("hg") || $project->UsesForHomepage("hg"))
        $hg = 1;
      if ($project->Uses("bzr") || $project->UsesForHomepage("bzr"))
        $bzr = 1;

      $count = 0;
      if ($cvs)  $count++;
      if ($arch) $count++;
      if ($svn)  $count++;
      if ($git)  $count++;
      if ($hg)   $count++;
      if ($bzr)  $count++;
      if ($count == 1)
        {
          # Only one SCM - direct link.
          unset($tool);
          if ($cvs)
            $tool = "cvs";
          if ($arch)
            $tool = "arch";
          if ($svn)
            $tool = "svn";
          if ($git)
            $tool = "git";
          if ($hg)
            $tool = "hg";
          if ($bzr)
            $tool = "bzr";
          pagemenu_submenu_title(_("Source code"),
                                 $project->getArtifactUrl($tool),
                                 CONTEXT == $tool,
                                 1,
                                 _("Source code management"));
        }
      else
        {
          pagemenu_submenu_title(_("Source code"),
  $GLOBALS['sys_home'].'projects/'.$project->getUnixName().'/#devtools',
                                 (CONTEXT == 'cvs' || CONTEXT == 'arch'
                                  || CONTEXT == 'svn' || CONTEXT == 'git'
                                  || CONTEXT == 'hg' || CONTEXT == 'bzr'),
                                 1,
                                 _("Source code management"));
        }

      $ret = '';
      $count = 0;
      function vcs_entry ($project, &$count, &$ret, $vcs, $vcs_name)
      {
        $count++;
                 # TRANSLATORS: the argument is VCS name (like Git or Bazaar).
        $ret .= pagemenu_submenu_entry(sprintf(_("Use %s"), $vcs_name),
                                       $project->getArtifactUrl($vcs),
                                       1,
                  # TRANSLATORS: the argument is VCS name (like Git or Bazaar).
                                       sprintf (_("%s Repository"), $vcs_name));
        # Do we need links to browse repositories?
        if ($project->Uses($vcs) &&
            $project->getUrl($vcs."_viewcvs") != 'http://' &&
            $project->getUrl($vcs."_viewcvs") != '')
          {
            $count++;
            $ret .= pagemenu_submenu_entry(_("Browse Sources Repository"),
                                           $project->getUrl($vcs."_viewcvs"));
          }
        if ((($vcs != 'cvs' && $project->UsesForHomepage($vcs))
             || ($vcs == 'cvs' && $project->Uses("homepage")))
            && $project->getUrl("cvs_viewcvs_homepage") != 'http://'
            && $project->getUrl("cvs_viewcvs_homepage") != '')
          {
            $count++;
            $ret .= pagemenu_submenu_entry(_("Browse Web Pages Repository"),
                                           $project->getUrl("cvs_viewcvs_homepage"));
          }
      }

      if ($git)
        vcs_entry ($project, $count, $ret, 'git',
# TRANSLATORS: this string is used as argument in messages 'Use %s' and
# '%s Repository'.
                   _('Git'));
      if ($hg)
        vcs_entry ($project, $count, $ret, 'hg',
# TRANSLATORS: this string is used as argument in messages 'Use %s' and
# '%s Repository'.
                   _('Mercurial'));
      if ($bzr)
        vcs_entry ($project, $count, $ret, 'bzr',
# TRANSLATORS: this string is used as argument in messages 'Use %s' and
# '%s Repository'.
                   _('Bazaar'));
      if ($svn)
        vcs_entry ($project, $count, $ret, 'svn',
# TRANSLATORS: this string is used as argument in messages 'Use %s' and
# '%s Repository'.
                   _('Subversion'));
      if ($arch)
        vcs_entry ($project, $count, $ret, 'arch',
# TRANSLATORS: this string is used as argument in messages 'Use %s' and
# '%s Repository'.
                   _('GNU Arch'));
      if ($cvs)
        vcs_entry ($project, $count, $ret, 'cvs',
# TRANSLATORS: this string is used as argument in messages 'Use %s' and
# '%s Repository'.
                   _('CVS'));
      # Add a submenu only if there is more than one item.
      if ($ret && $count > 1)
        pagemenu_submenu_content($ret);
      pagemenu_submenu_end();
    }

  if ($project->Uses("bugs"))
    {
      pagemenu_submenu_title(_("Bugs"),
                             $project->getArtifactUrl("bugs"),
                             CONTEXT == 'bugs',
                             1,
                             _("Bug Tracker: report, search and track bugs"));
      pagemenu_submenu_content(pagemenu_group_trackers("bugs"));
      pagemenu_submenu_end();
    }

  if ($project->Uses("task"))
    {
      pagemenu_submenu_title(_("Tasks"),
                             $project->getArtifactUrl("task"),
                             CONTEXT == 'task',
                             1,
                             _("Task Manager: post, search and manage tasks"));
      pagemenu_submenu_content(pagemenu_group_trackers("task"));
      pagemenu_submenu_end();
    }

  if ($project->Uses("patch"))
    {
      pagemenu_submenu_title(_("Patches"),
                             $project->getArtifactUrl("patch"),
                             CONTEXT == 'patch',
                             1,
                             _("Patch Manager: post, search and manage patches"));
      pagemenu_submenu_content(pagemenu_group_trackers("patch"));
      pagemenu_submenu_end();
    }

  if ($project->Uses("news"))
    {
      pagemenu_submenu_title(_("News"),
                             $GLOBALS['sys_home'].'news/?group='
                             .$project->getUnixName(),
                             CONTEXT == 'news',
                             1,
                             _("Read latest News, post News"));
      $ret = '';
      $ret .= pagemenu_submenu_entry(_("Browse"),
                                     $GLOBALS['sys_home'].'news/?group='
                                     .$project->getUnixName());
      $ret .= pagemenu_submenu_entry(_("Atom feed"),
                                     $GLOBALS['sys_home'].'news/atom.php?group='
                                     .$project->getUnixName());
      $ret .= pagemenu_submenu_entry(_("Submit"),
                                     $GLOBALS['sys_home'].'news/submit.php?group='
                                     .$project->getUnixName(),
                                     group_restrictions_check($group_id, "news"));
      $ret .= pagemenu_submenu_entry(_("Manage"),
                                     $GLOBALS['sys_home'].'news/approve.php?group='
                                     .$project->getUnixName(),
                                     member_check(0, $group_id, "N3"));
      if ($is_admin)
        {
          $ret .= pagemenu_submenu_entry_separator().
            pagemenu_submenu_entry('<strong>'._("Configure").'</strong>',
                                   $GLOBALS['sys_home'].'news/admin/?group='
                                   .$project->getUnixName(),
                                   1,
                                   _("News Manager: edit notifications"));
        }
      pagemenu_submenu_content($ret);
      pagemenu_submenu_end();
    }
}

# Menu-specific to the trackers pages.
function pagemenu_group_trackers ($tracker)
{
  global $project, $group_id, $sys_group_id;

  $is_admin = FALSE;
  if (member_check(0, $group_id, 'A'))
    $is_admin = TRUE;

  # FIXME: this should first check if the standard savane tool is used
  $ret = '';
  if ($tracker == "bugs"
      || $tracker == "support"
      || $tracker == "patch"
      || $tracker == "task")
    {
      $ret .= pagemenu_submenu_entry(_("Submit new"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/?func=additem&amp;group='
                                     .$project->getUnixName(),
                                     group_restrictions_check($group_id, $tracker));

      $ret .= pagemenu_submenu_entry(_("Browse"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/?group='.$project->getUnixName());

      $ret .= pagemenu_submenu_entry(_("Reset to open"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/?func=browse&amp;set=open&amp;group='
                                     .$project->getUnixName());

      $ret .= pagemenu_submenu_entry(_("Digest"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/?func=digest&amp;group='
                                     .$project->getUnixName());

      $ret .= pagemenu_submenu_entry(_("Export"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/export.php?group='
                                     .$project->getUnixName(),
                                     member_check(0, $group_id));

      $ret .= pagemenu_submenu_entry(_("Get statistics"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/reporting.php?group='
                                     .$project->getUnixName());

      # At the end of the submenu, for cohesion with the "search" in the
      # menu that is also at the end.
      $ret .= pagemenu_submenu_entry(_("Search"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/?func=search&amp;group='
                                     .$project->getUnixName());
    }
  elseif ($tracker == "cookbook")
    {
      # Quite similar to other trackers, the cookbook have some specific
      # links.

      # If there are external docs (extra link), consider them prior
      # to the cookbook: if the users use two doc tool, there is no
      # reason to consider the external less important than the Savane,
      # at the contrary, we can assume that they made the choice to
      # use another one for good reasons and we do not have to enforce
      # anything at this point.
      if ($project->Uses("extralink_documentation"))
        {
          $ret .= pagemenu_submenu_entry(_("Browse (External to Savane)"),
                         $project->getUrl("extralink_documentation"),
                                         1,
         _("Browse Documentation that is located outside of Savane"))
            .pagemenu_submenu_entry_separator();
        }

      $ret .= pagemenu_submenu_entry(_("Browse"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/?group='.$project->getUnixName());

      $ret .= pagemenu_submenu_entry(_("Submit"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/edit.php?func=additem&amp;group='
                                     .$project->getUnixName(),
                                     group_restrictions_check($group_id,
                                                              $tracker));

      $ret .= pagemenu_submenu_entry(_("Edit"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/edit.php?func=browse&amp;group='
                                     .$project->getUnixName(),
                                     group_restrictions_check($group_id,
                                                              $tracker));

      $ret .= pagemenu_submenu_entry(_("Digest"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/edit.php?func=digest&amp;group='
                                     .$project->getUnixName(),
                                     _("Digest recipes"));

      $ret .= pagemenu_submenu_entry(_("Export"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/export.php?group='
                                     .$project->getUnixName(),
                                     member_check(0, $group_id));
      # At the end of the submenu, for cohesion with the "search" in the
      # menu that is also at the end.
      $ret .= pagemenu_submenu_entry(_("Search"),
                                     $GLOBALS['sys_home'].$tracker
                                     .'/?func=search&amp;group='
                                     .$project->getUnixName());
      # If it is the site admin project, link to savane-doc.
      if ($group_id == $sys_group_id)
        {

          $ret .= pagemenu_submenu_entry_separator()
            .pagemenu_submenu_entry(_("Savane In Depth Guide"),
                                    $GLOBALS['sys_home'].'userguide/');
        }
    }

  if ($is_admin)
    {
      $ret .= pagemenu_submenu_entry_separator()
        .pagemenu_submenu_entry('<strong>'._("Configure:").'</strong>',
                               $GLOBALS['sys_home'].$tracker.'/admin/?group='
                               .$project->getUnixName())
        .pagemenu_submenu_entry(_("Select fields"),
                               $GLOBALS['sys_home'].$tracker
                               .'/admin/field_usage.php?group='
                               .$project->getUnixName(),
                               1,
       _("Define what fields you want to use in this tracker"))
        .pagemenu_submenu_entry(_("Edit field values"),
                               $GLOBALS['sys_home'].$tracker
                               .'/admin/field_values.php?group='
                               .$project->getUnixName(),
                               1,
_("Define the set of possible values for the fields you have decided to use in
this tracker"))
        .pagemenu_submenu_entry(_("Edit query forms"),
                               $GLOBALS['sys_home'].$tracker
                               .'/admin/editqueryforms.php?group='
                               .$project->getUnixName(),
                               1,
_("Define project-wide query form: what search criteria to use and what item
fields to show in the query form table"))
        .pagemenu_submenu_entry(_("Set&nbsp;permissions"),
                               $GLOBALS['sys_home'].$tracker
                               .'/admin/userperms.php?group='
                               .$project->getUnixName(),
                               1,
                               _("Define posting restrictions"))
        .pagemenu_submenu_entry(_("Set&nbsp;notifications"),
                               $GLOBALS['sys_home'].$tracker
                               .'/admin/notification_settings.php?group='
                               .$project->getUnixName())
        .pagemenu_submenu_entry(_("Copy&nbsp;configuration"),
                               $GLOBALS['sys_home'].$tracker
                               .'/admin/conf-copy.php?group='
                               .$project->getUnixName(),
                               1,
                               _("Copy the configuration of another tracker"))
        .pagemenu_submenu_entry(_("Other settings"),
                               $GLOBALS['sys_home'].$tracker
                               .'/admin/other_settings.php?group='
                               .$project->getUnixName(),
                               1,
       _("Modify the preamble shown on the item submission form"));
    }

  return $ret;
}

# Menu specific to the site admin pages.
function pagemenu_siteadmin ()
{
  pagemenu_submenu_title(_("Configuration"),
                         $GLOBALS['sys_home'].'siteadmin/?func=configure',
                         SUBCONTEXT == 'configure');
  pagemenu_submenu_content(pagemenu_submenu_entry(_("Test System Configuration"),
                                                  $GLOBALS['sys_home']
                                                  .'siteadmin/retestconfig.php')
                           .pagemenu_submenu_entry(_("Configure Group Types"),
                                                  $GLOBALS['sys_home']
                                                  .'siteadmin/group_type.php')
                           .pagemenu_submenu_entry(_("Configure People Area"),
                                                  $GLOBALS['sys_home']
                                                  .'people/admin/'));
  pagemenu_submenu_end();

  pagemenu_submenu_title(_("Management"),
                         $GLOBALS['sys_home'].'siteadmin/?func=manage',
                         SUBCONTEXT == 'manage');
  # If the current page shows a group edition page, add extra links.
  $extralinks = '';
  if (SUBCONTEXT == 'manage' && !empty($GLOBALS['group_name']))
    {

      $extralinks = pagemenu_submenu_entry_separator()
        .pagemenu_submenu_entry('<strong>'._("Currently Shown Project:")
                               .'</strong>', '#')
        .pagemenu_submenu_entry(_("Administer"),
                                $GLOBALS['sys_home'].'project/admin/?group='
                                .$GLOBALS['group_name'])
        .pagemenu_submenu_entry(_("Edit Public Info"),$GLOBALS['sys_home']
                                .'project/admin/editgroupinfo.php?group='
                                .$GLOBALS['group_name'])
        .pagemenu_submenu_entry(_("Select Features"),
                                $GLOBALS['sys_home']
                                .'project/admin/editgroupfeatures.php?group='
                                .$GLOBALS['group_name'])
        .pagemenu_submenu_entry(_("Manage Members"),
                                $GLOBALS['sys_home']
                                .'project/admin/useradmin.php?group='
                                .$GLOBALS['group_name'])
        .pagemenu_submenu_entry(_("Show History"),
                                $GLOBALS['sys_home']
                                .'project/admin/history.php?group='
                                .$GLOBALS['group_name']);
    }

  pagemenu_submenu_content(
    pagemenu_submenu_entry(
        _("Browse Pending Project Registrations"),
        $GLOBALS['sys_home'].'task/?group='.$GLOBALS['sys_unix_group_name']
        .'&amp;category_id=1&amp;status_id=1&amp;go_report=Apply')
    .pagemenu_submenu_entry(_("Approve News"),
                           $GLOBALS['sys_home'].'news/approve.php?group='
                           .$GLOBALS['sys_unix_group_name'])
    .pagemenu_submenu_entry_separator()
    .pagemenu_submenu_entry(_("Browse Groups List"),
                           $GLOBALS['sys_home'].'siteadmin/grouplist.php')
    .pagemenu_submenu_entry(_("Browse Users List"),
                           $GLOBALS['sys_home'].'siteadmin/userlist.php')
    .$extralinks);

  pagemenu_submenu_end();
  pagemenu_submenu_title(_("Monitoring"),
                         $GLOBALS['sys_home'].'siteadmin/?func=monitor',
                         SUBCONTEXT == 'monitor');

  pagemenu_submenu_content(pagemenu_submenu_entry(_("Monitor Spams"),
                                                  $GLOBALS['sys_home']
                                                  .'siteadmin/spamlist.php')
                           .pagemenu_submenu_entry(_("Check Last Logins"),
                                                   $GLOBALS['sys_home']
                                                   .'siteadmin/lastlogins.php'));
  pagemenu_submenu_end();
}
?>
