<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: menu.php 5379 2006-02-15 14:11:03Z yeupou $
#
#  Copyright 1999-2000 (c) The SourceForge Crew (was in Layout.class)
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
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


# Note about submenus: they should always contain a verb, enlightening the
#  action they permit to do
# The related pages the submenus point to should have title that are the
# same as the submenu, or almost


# Disallow anyone to mess with the submenu content used with the stone age
# menu
unset($GLOBALS['stone_age_menu_submenu_content'],
      $GLOBALS['stone_age_menu_lastcontext']);

# Menu specific to the current page: group if group page, my if my pages etc
function pagemenu ($params)
{
  # Skip topmenu if passed as parameter
  if ($params['notopmenu'])
    { return; }

  # Reset important variables
  unset($GLOBALS['stone_age_menu_submenu_content'],
	$GLOBALS['stone_age_menu_lastcontext']);
  $GLOBALS['submenucount'] = 0;

  # Print topmenu title 
  # We use javascript for browsers that does not support CSS correctly
  if (is_broken_msie() && 
      !$_GET["printer"] &&
      !$GLOBALS['stone_age_menu'])
    {

  print '<!-- begin pagemenu -->
<SCRIPT type=text/javascript><!--//--><![CDATA[//><!--
 
sfHover = function() {
        var sfEls = document.getElementById("topmenuitem").getElementsByTagName("LI");
        for (var i=0; i<sfEls.length; i++) {
                sfEls[i].onmouseover=function() {
                        this.className+=" sfhover";
                }
                sfEls[i].onmouseout=function() {
                        this.className=this.className.replace(new RegExp(" sfhover\\\\b"), "");
                }
        }
}
if (window.attachEvent) window.attachEvent("onload", sfHover);
 
//--><!]]></SCRIPT>';
    }

print '
<h2 class="toptitle"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/'.context_icon().'.orig.png" width="48" height="48" alt="'.context_icon().'" class="pageicon" />';
  $title = context_title();
  if ($title)
    { print $title; }
  if ($title && $params['title'] != "")
    { print _(": "); }
  if ($params['title'] != "")
    { print $params['title']; }
  print '</h2>';
  
  # Print topmenu subtitle
  unset($scope);
  switch (CONTEXT)
    {
    case 'my': $scope = _("My"); break;
    case isset($GLOBALS['group_id']): $scope = _("Group"); break;
    case 'siteadmin': $scope = _("Site Admin"); break;
    default: $scope = _("Site Wide");
    }
 
  print '
    <div class="topmenu" id="topmenu">
      <span class="topmenutitle" title="'.sprintf(_("%s Scope"), $scope).'">
        '.$scope.'
      </span><!-- end topmenutitle -->
      <div class="topmenuitem"><ul id="topmenuitem">

';

  # Call the relevant menu
  switch (CONTEXT)
    {
    case 'my': pagemenu_my(); break;
    case 'siteadmin': pagemenu_siteadmin(); break;
    case isset($GLOBALS['group_id']): pagemenu_group(); break;
    }
  
  print  '      </ul></div><!-- end topmenuitem -->
    </div>
<!-- end pagemenu -->
';
  
  # Add the stone age submenu if relevant
  if ($GLOBALS['stone_age_menu'] && $GLOBALS['stone_age_menu_submenu_content'])
    {
      $scope = _("Submenu");
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
  #    because the menu is a float left like the menu
  # This is required for Mozilla and Konqueror. Please, dont change that.
  print '<div id="topmenunooverlap">&nbsp;</div><div id="topmenunooverlapbis">&nbsp;</div>';
}


# Column title
function pagemenu_submenu_title ($title, $url, $selected=0, $available=1, $help='')
{
  $GLOBALS['submenucount']++;
 
  if ($selected)
    { $class = "tabselect"; }
  else
    { $class = "tabs"; }

  # If we use the stone age menu, we need to be able to determine later
  # the current submenu
  # As the current code was not planned to be forced to make context guessing
  # for submenus, we are forced to do it in a quite awkward way
  if ($GLOBALS['stone_age_menu'])
    {
      $GLOBALS['stone_age_menu_lastcontext'] = context_guess_from_url($url, true);
    }

# yeupou--gnu.org 2006-09-08:
# Dirty hack to get rid of serious issue in MSIE handling of
# CSS.
# Please, do never add such an hack in savane somewhere else without
# talking about it on savane-dev.
# DONT CHANGE width, margin and padding size, or you ll be very sorry
# for MSIE users
  if (is_broken_msie() && !$_GET["printer"])
    {
      # normally we should have white-space: nowrap; but then MSIE make 
      # the text disappear on mouse out.
      $title = preg_replace("/\s/", "&nbsp;", $title);  
    }


  # We make appear the submenu with both CSS and javascript. That is because
  # some browsers (MSIE) have poor CSS supports and cannot do it otherwise.
  # (When it gains focus, the submenu appears)
   print '        <li class="topmenuitemmainitem">
          '.utils_link($url, $title, $class, $available, $help);
}

function pagemenu_submenu_end () 
{
  print '        </li><!-- end topmenuitemmainitem -->

';

}

# Column title
function pagemenu_submenu_content ($content)
{
  # Stone age menu got submenu in a new menu line below, just like if there
  # was two menus
  # So when asked to print the content, we determine if this is the content
  # that is supposed to show up in the submenu line (as there is only one
  # submenu, it means that the only submenu available is the one of the 
  # current content) and if it is the case, we save it a global to be used
  # later (that was unset at the begin of this page)
  if ($GLOBALS['stone_age_menu'])
    { 
      if ($GLOBALS['stone_age_menu_lastcontext'] == CONTEXT)
	{ $GLOBALS['stone_age_menu_submenu_content'] = $content; }
      return;
    }

  print '
          <ul id="submenu'.$GLOBALS['submenucount'].'" class="topmenuitemsubmenu">'.$content.'
          </ul><!-- end submenu -->
';

}

# Column title
function pagemenu_submenu_entry ($title, $url, $available=1, $help="")
{
  $class = "topmenuitemsubmenu";

  if ($GLOBALS['stone_age_menu'])
    { $class = "topmenuitemmainitem"; }

  return '
            <li class="'.$class.'">'.
              utils_link($url, $title, '', $available, $help).'
            </li>'; 
}

# Column title
function pagemenu_submenu_entry_separator ()
{
  if ($GLOBALS['stone_age_menu'])
    { return '<br />'; }
  
  return '
            <li class="topmenuitemsubmenuseparator">&nbsp;</li>'; 
}


# Menu specific to the My pages
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


# Menu specific to the Group pages
function pagemenu_group ()
{
  global $group_id, $sys_group_id, $project;
  
  if (CONTEXT == 'userguide')
    {
      $GLOBALS['group_id'] = $sys_group_id;
      $GLOBALS['group_name'] = $GLOBALS['sys_unix_group_name'];
    }
  
  unset($is_admin);
  if (member_check(0, $group_id, 'A'))
    { $is_admin = 1; }

  $project = project_get_object($group_id);
  if ($project->isError())
    { return; }


  # MAIN
  pagemenu_submenu_title(_("Main"), 
			 $GLOBALS['sys_home'].'projects/'.$project->getUnixName().'/', 
			 CONTEXT == 'project',
			 1,
			 sprintf(_("Project Main Page at %s"), $GLOBALS['sys_name']));
  unset($ret);

  $ret = pagemenu_submenu_entry(_("Main"),$GLOBALS['sys_home'].'projects/'.$project->getUnixName().'/').
    pagemenu_submenu_entry(_("View Members"),$GLOBALS['sys_home'].'project/memberlist.php?group='.$project->getUnixName()).
    pagemenu_submenu_entry(_("Search"),$GLOBALS['sys_home'].'project/search.php?group='.$project->getUnixName());  
  
  if (member_check(0, $group_id, 'A'))
    {
      # If admin, print a link to the admin main page and an extra useless
      # link to main page
      $ret .= 
	pagemenu_submenu_entry_separator().
	pagemenu_submenu_entry('<strong>'._("Administer:").'</strong>',$GLOBALS['sys_home'].'project/admin/?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Edit Public Info"),$GLOBALS['sys_home'].'project/admin/editgroupinfo.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Select Features"),$GLOBALS['sys_home'].'project/admin/editgroupfeatures.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Manage Members"),$GLOBALS['sys_home'].'project/admin/useradmin.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Manage Squads"),$GLOBALS['sys_home'].'project/admin/squadadmin.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Set Permissions"),$GLOBALS['sys_home'].'project/admin/userperms.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Set Notifications"),$GLOBALS['sys_home'].'project/admin/editgroupnotifications.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Show History"),$GLOBALS['sys_home'].'project/admin/history.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Copy Configuration"),$GLOBALS['sys_home'].'project/admin/conf-copy.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Post Jobs"),$GLOBALS['sys_home'].'people/createjob.php?group='.$project->getUnixName(),1,_("Post a request for contribution")).
	pagemenu_submenu_entry(_("Edit Jobs"),$GLOBALS['sys_home'].'people/editjob.php?group='.$project->getUnixName(),1,_("Edit previously posted request for contribution"));
    } 
  pagemenu_submenu_content($ret);
  pagemenu_submenu_end();


  # HOMEPAGE
  if ($project->Uses("homepage") 
      && $project->getUrl("homepage") != 'http://'
      && $project->getUrl("homepage") != '')
    {
      pagemenu_submenu_title(_("Homepage"), 
			     $project->getUrl("homepage"), 
			     0,
			     1,
			     _("Browse Project Homepage (outside of Savane)"));
      pagemenu_submenu_end();
    }

  # DOWNLOAD AREA
  if ($project->Uses("download"))
    {
      pagemenu_submenu_title(_("Download"), 
			     $project->getArtifactUrl("files"), 
			     CONTEXT == 'download',
			     1,
			     _("Download Area: files released"));
      pagemenu_submenu_end();
    }


  # DOCS
  # the cookbook is the default and cannot be deactivate as it contains
  # site docs useful for the project depending on the used features
  # 
  # However, if external doc is set, the link will have no effect 
  # (See pagemenu_group_trackers() for more details about the document menu
  # behavior)
  $url = $project->getArtifactUrl("cookbook");
  if ($project->Uses("extralink_documentation"))
    { $url = '#'; }
  pagemenu_submenu_title(_("Docs"), 
			 $url, 
			 CONTEXT == 'cookbook',
			 1,
			 _("Docs: Cookbook, etc"));
  pagemenu_submenu_content(pagemenu_group_trackers("cookbook"));
  pagemenu_submenu_end();

  # SUPPORT
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

  # FORA: normally deprecated on savane
  if ($project->Uses("forum"))
    {
      pagemenu_submenu_title(_("Forum"), 
			     $project->getArtifactUrl("forum"), 
			     CONTEXT == 'forum');
      pagemenu_submenu_end();
    }

  # MAILING LIST
  if ($project->usesMail())
    {
      pagemenu_submenu_title(_("Mailing Lists"), 
			     $project->getArtifactUrl("mail"),
			     CONTEXT == 'mail',
			     1,
			     _("List existing Mailing Lists"));
      if ($is_admin)
	{
	  unset($ret);
	  $ret .= 
	    pagemenu_submenu_entry(_("Browse"),
				   $GLOBALS['sys_home'].'mail/?group='.$project->getUnixName(),
				   
				   _("List existing Mailing Lists")).
	    pagemenu_submenu_entry_separator().
	    pagemenu_submenu_entry('<strong>'._("Configure:").'</strong>',
				   $GLOBALS['sys_home'].'mail/admin/?group='.$project->getUnixName()).
	    pagemenu_submenu_entry(_("Add"),
				   $GLOBALS['sys_home'].'mail/admin/?add_list=1&amp;group='.$project->getUnixName()).
	    pagemenu_submenu_entry(_("Edit"),
				   $GLOBALS['sys_home'].'mail/admin/?change_status=1&amp;group='.$project->getUnixName());
	  pagemenu_submenu_content($ret);
	}
      pagemenu_submenu_end();
    }
  
  # SCMs
  if ($project->Uses("cvs") || 
      $project->UsesForHomepage("cvs") || 
      $project->Uses("arch") ||
      $project->UsesForHomepage("arch") ||
      $project->Uses("svn") ||
      $project->UsesForHomepage("svn"))
    {
      # If it uses only one SCM, main link points to it
      unset($cvs, $svn, $arch);
      if ($project->Uses("cvs") || $project->UsesForHomepage("cvs"))
	{ $cvs = 1; }
      if ($project->Uses("arch") || $project->UsesForHomepage("arch"))
	{ $arch = 1; }
      if ($project->Uses("svn") || $project->UsesForHomepage("svn"))
	{ $svn = 1; }

      if (($cvs && !$arch && !$svn) ||
	  (!$cvs && $arch && !$svn) ||
	  (!$cvs && !$arch && $svn))
	{
	  unset($tool);
	  if ($cvs)
	    { $tool = "cvs"; }
	  if ($arch)
	    { $tool = "arch"; }	    
	  if ($svn)
	    { $tool = "svn"; }

	  pagemenu_submenu_title(_("Source Code"), 
				 $project->getArtifactUrl($tool),
				 CONTEXT == $tool,
				 1,
				 _("Source Code Management"));
	}
      else
	{
	  
	  pagemenu_submenu_title(_("Source Code"), 
				 '#', # non-link
				 (CONTEXT == 'cvs' || CONTEXT == 'arch' || CONTEXT == 'svn'),
				 1,
				 _("Source Code Management"));
	}
      
      unset($ret, $count);
      

      if ($svn)
	{
	  $count++;
	  $ret .= pagemenu_submenu_entry(_("Use Subversion"),
					 $project->getArtifactUrl("svn"),
					 1,
					 _("Source Code Manager: Subversion Repository"));
	  # Do we need links to browse repositories?
	  if ($project->Uses("svn") && 
	      $project->getUrl("svn_viewcvs") != 'http://' && 
	      $project->getUrl("svn_viewcvs") != '')
	    {
	      $count++;
	      $ret .= pagemenu_submenu_entry(_("Browse Sources Repository"),
					     $project->getUrl("svn_viewcvs"));
	    }
	  if ($project->UsesForHomepage("svn") && 
	      $project->getUrl("cvs_viewcvs_homepage") != 'http://' && 
	      $project->getUrl("cvs_viewcvs_homepage") != '')
	    {
	      $count++;
	      $ret .= pagemenu_submenu_entry(_("Browse Web Pages Repository"),
					     $project->getUrl("cvs_viewcvs_homepage"));
	    }	  

	} 
      if ($arch)
	{
	  $count++;
	  $ret .= pagemenu_submenu_entry(_("Use GNU Arch"),
					 $project->getArtifactUrl("arch"),
					 1,
					 _("Source Code Manager: GNU Arch Repository"));

	  # Do we need links to browse repositories?
	  if ($project->Uses("arch") && 
	      $project->getUrl("arch_viewcvs") != 'http://' && 
	      $project->getUrl("arch_viewcvs") != '')
	    {
	      $count++;
	      $ret .= pagemenu_submenu_entry(_("Browse Sources Repository"),
					     $project->getUrl("arch_viewcvs"));
	    }
	  if ($project->UsesForHomepage("arch") && 
	      $project->getUrl("cvs_viewcvs_homepage") != 'http://' && 
	      $project->getUrl("cvs_viewcvs_homepage") != '')
	    {
	      $count++;
	      $ret .= pagemenu_submenu_entry(_("Browse Web Pages Repository"),
					     $project->getUrl("cvs_viewcvs_homepage"));
	    }
	} 
      
      # Outdated CVS goes last in the list 
      if ($cvs)
	{
	  $count++;
	  $ret .= pagemenu_submenu_entry(_("View CVS Instructions"),
					 $project->getArtifactUrl("cvs"),
					 1,
					 _("Source Code Manager: CVS Repository"));

	  # Do we need links to browse repositories?
	  if ($project->Uses("cvs") && 
	      $project->getUrl("cvs_viewcvs") != 'http://' && 
	      $project->getUrl("cvs_viewcvs") != '')
	    {
	      $count++;
	      $ret .= pagemenu_submenu_entry(_("Browse Sources Repository"),
					     $project->getUrl("cvs_viewcvs"));
	    }
	  if ($project->UsesForHomepage("cvs") && 
	      $project->getUrl("cvs_viewcvs_homepage") != 'http://' && 
	      $project->getUrl("cvs_viewcvs_homepage") != '')
	    {
	      $count++;
	      $ret .= pagemenu_submenu_entry(_("Browse Web Pages Repository"),
					     $project->getUrl("cvs_viewcvs_homepage"));
	    }
	}

      # Add a submenu only if there is more than one item
      if ($ret && $count > 1)
	{ pagemenu_submenu_content($ret); }

      pagemenu_submenu_end();
    }

  # BUG Tracking
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
	
  # TASK Tracking
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
	
  # PATCH Tracking
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

  # NEWS
  if ($project->Uses("news"))
    {
      pagemenu_submenu_title(_("News"), 
			     $GLOBALS['sys_home'].'news/?group='.$project->getUnixName(), 
			     CONTEXT == 'news',
			     1,
			     _("Read latest News, post News"));
      unset($ret);
      $ret .= pagemenu_submenu_entry(_("Browse"),
				     $GLOBALS['sys_home'].'news/?group='.$project->getUnixName());
      $ret .= pagemenu_submenu_entry(_("Submit"),
				     $GLOBALS['sys_home'].'news/submit.php?group='.$project->getUnixName(),
				     group_restrictions_check($group_id, "news"));
      $ret .= pagemenu_submenu_entry(_("Manage"),
				     $GLOBALS['sys_home'].'news/approve.php?group='.$project->getUnixName(),
				     member_check(0, $group_id, "N3"));
      if ($is_admin)
	{
	  $ret .= pagemenu_submenu_entry_separator().
	    pagemenu_submenu_entry('<strong>'._("Configure").'</strong>',
				   $GLOBALS['sys_home'].'news/admin/?group='.$project->getUnixName(),
				   1,
				   _("News Manager: edit notifications"));

	}
      pagemenu_submenu_content($ret);
      pagemenu_submenu_end();
    }	
  
  # Search
					     
}


# Menu specific to the trackers pages
function pagemenu_group_trackers ($tracker)
{
  global $project, $group_id, $sys_group_id;

  unset($is_admin);
  if (member_check(0, $group_id, 'A'))
    { $is_admin = 1; }
  
  # FIXME: this should first check if the standard savane tool is used 

  unset($ret);
  if ($tracker == "bugs" ||
      $tracker == "support" ||
      $tracker == "patch" ||
      $tracker == "task")
	  {

	    $ret .= pagemenu_submenu_entry(_("Browse"),
					   $GLOBALS['sys_home'].$tracker.'/?group='.$project->getUnixName());

	    $ret .= pagemenu_submenu_entry(_("Reset to all open ones"),
					   $GLOBALS['sys_home'].$tracker.'/?func=browse&amp;set=open&amp;group='.$project->getUnixName());


	    $ret .= pagemenu_submenu_entry(_("Submit"),
					   $GLOBALS['sys_home'].$tracker.'/?func=additem&amp;group='.$project->getUnixName(),
					   group_restrictions_check($group_id, $tracker));

	    $ret .= pagemenu_submenu_entry(_("Digest"),
					   $GLOBALS['sys_home'].$tracker.'/?func=digest&amp;group='.$project->getUnixName());

	    $ret .= pagemenu_submenu_entry(_("Export"),
					   $GLOBALS['sys_home'].$tracker.'/export.php?group='.$project->getUnixName(),
					   member_check(0, $group_id));

	    $ret .= pagemenu_submenu_entry(_("View Statistics"),
					   $GLOBALS['sys_home'].$tracker.'/reporting.php?group='.$project->getUnixName());

	    # At the end of the submenu, for cohesion with the "search" in the
	    # menu that is also at the end
	    $ret .= pagemenu_submenu_entry(_("Search"),
					   $GLOBALS['sys_home'].$tracker.'/?func=search&amp;group='.$project->getUnixName());

	  }
	else if ($tracker == "cookbook")
	  {
         # Quite similar to other trackers, the cookbook have some specific
         # links

	    
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
					       _("Browse Documentation that is located outside of Savane")).
		  pagemenu_submenu_entry_separator();
	      }




	    $ret .= pagemenu_submenu_entry(_("Browse"),
					   $GLOBALS['sys_home'].$tracker.'/?group='.$project->getUnixName());
 

	    $ret .= pagemenu_submenu_entry(_("Submit"),
					   $GLOBALS['sys_home'].$tracker.'/edit.php?func=additem&amp;group='.$project->getUnixName(),
					   group_restrictions_check($group_id, $tracker));

	    
	    $ret .= pagemenu_submenu_entry(_("Edit"),
					   $GLOBALS['sys_home'].$tracker.'/edit.php?func=browse&amp;group='.$project->getUnixName(),
					   group_restrictions_check($group_id, $tracker));
	    
	    $ret .= pagemenu_submenu_entry(_("Digest"),
					   $GLOBALS['sys_home'].$tracker.'/edit.php?func=digest&amp;group='.$project->getUnixName(),
					   _("Digest recipes"));

	    $ret .= pagemenu_submenu_entry(_("Export"),
					   $GLOBALS['sys_home'].$tracker.'/export.php?group='.$project->getUnixName(),
					   member_check(0, $group_id));

#  Does it make sense on a documentation tool?
#	    $subTabDatas[] = $this->maintab_entry('reporting.php?group='.$project->getUnixName(),
#						  _("Statistics"), 0);

	    # At the end of the submenu, for cohesion with the "search" in the
	    # menu that is also at the end
	    
	    $ret .= pagemenu_submenu_entry(_("Search"),
					   $GLOBALS['sys_home'].$tracker.'/?func=search&amp;group='.$project->getUnixName());

	    # If it is the site admin project, link to savane-doc
	    if ($group_id == $sys_group_id)
	      {

		$ret .= pagemenu_submenu_entry_separator().
		  pagemenu_submenu_entry(_("Savane In Depth Guide"),
					 $GLOBALS['sys_home'].'userguide/');
	      }

	  }	
  
  if ($is_admin)
    {
      $ret .= pagemenu_submenu_entry_separator().
	pagemenu_submenu_entry('<strong>'._("Configure:").'</strong>',
			       $GLOBALS['sys_home'].$tracker.'/admin/?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Select Fields"),
			       $GLOBALS['sys_home'].$tracker.'/admin/field_usage.php?group='.$project->getUnixName(),
			       1,
			       _("Define what fields you want to use in this tracker")).
	pagemenu_submenu_entry(_("Edit Fields Values"),
			       $GLOBALS['sys_home'].$tracker.'/admin/field_values.php?group='.$project->getUnixName(),
			       1,
			       _("Define the set of possible values for the fields you have decided to use in this tracker")).
	pagemenu_submenu_entry(_("Edit Query Forms"),
			       $GLOBALS['sys_home'].$tracker.'/admin/editqueryforms.php?group='.$project->getUnixName(),
			       1,
			       _("Define project-wide query form: what search criteria to use and what item fields to show in the query form table")).
	pagemenu_submenu_entry(_("Set Permissions"),
			       $GLOBALS['sys_home'].$tracker.'/admin/userperms.php?group='.$project->getUnixName(),
			       1,
			       _("Defines posting restrictions")).
	pagemenu_submenu_entry(_("Set Notifications"),
			       $GLOBALS['sys_home'].$tracker.'/admin/notification_settings.php?group='.$project->getUnixName()).
	pagemenu_submenu_entry(_("Copy Configuration"),
			       $GLOBALS['sys_home'].$tracker.'/admin/conf-copy.php?group='.$project->getUnixName(),
			       1,
			       _("Copy the configuration of another tracker")).
	pagemenu_submenu_entry(_("Other Settings"),
			       $GLOBALS['sys_home'].$tracker.'/admin/other_settings.php?group='.$project->getUnixName(),
			       1,
			       _("Modify the preamble shown on the item submission form"));
	
      
    }

  return $ret;
}

# Menu specific to the site admin pages
function pagemenu_siteadmin ()
{
  pagemenu_submenu_title(_("Configuration"), 
			 $GLOBALS['sys_home'].'siteadmin/?func=configure', 
			 SUBCONTEXT == 'configure');
  pagemenu_submenu_content(pagemenu_submenu_entry(_("Test System Configuration"),
						  $GLOBALS['sys_home'].'siteadmin/retestconfig.php').


			   pagemenu_submenu_entry(_("Configure Group Types"),
						  $GLOBALS['sys_home'].'siteadmin/group_type.php').
			   pagemenu_submenu_entry(_("Configure People Area"),
						  $GLOBALS['sys_home'].'people/admin/'));
  pagemenu_submenu_end();

  pagemenu_submenu_title(_("Management"), 
			 $GLOBALS['sys_home'].'siteadmin/?func=manage',
			 SUBCONTEXT == 'manage');

  # If the current page shows a group edition page, add extra links
  unset($extralinks);
  if (SUBCONTEXT == 'manage' && $GLOBALS['group_name'])
    {
      
      $extralinks = pagemenu_submenu_entry_separator().
	pagemenu_submenu_entry('<strong>'._("Currently Shown Project:").'</strong>',
			       '#').
	pagemenu_submenu_entry(_("Administer"),
			       $GLOBALS['sys_home'].'project/admin/?group='.$GLOBALS['group_name']).
	pagemenu_submenu_entry(_("Edit Public Info"),$GLOBALS['sys_home'].'project/admin/editgroupinfo.php?group='.$GLOBALS['group_name']).

	pagemenu_submenu_entry(_("Select Features"),
			       $GLOBALS['sys_home'].'project/admin/editgroupfeatures.php?group='.$GLOBALS['group_name']).
	pagemenu_submenu_entry(_("Manage Members"),
			       $GLOBALS['sys_home'].'project/admin/useradmin.php?group='.$GLOBALS['group_name']).
	pagemenu_submenu_entry(_("Show History"),
			       $GLOBALS['sys_home'].'project/admin/history.php?group='.$GLOBALS['group_name']);	
    }
  
  pagemenu_submenu_content(pagemenu_submenu_entry(_("Browse Pending Project Registrations"),
						  $GLOBALS['sys_home'].'task/?group='.$GLOBALS['sys_unix_group_name'].'&amp;category_id=1&amp;status_id=1&amp;go_report=Apply').
			   pagemenu_submenu_entry(_("Approve News"),
						  $GLOBALS['sys_home'].'news/approve.php?group='.$GLOBALS['sys_unix_group_name']).
			   pagemenu_submenu_entry_separator().
			   pagemenu_submenu_entry(_("Browse Groups List"), 
						  $GLOBALS['sys_home'].'siteadmin/grouplist.php').
			   pagemenu_submenu_entry(_("Browse Users List"), 
						  $GLOBALS['sys_home'].'siteadmin/userlist.php').
			   $extralinks);
			   

  pagemenu_submenu_end();
  

  pagemenu_submenu_title(_("Monitoring"), 
			 $GLOBALS['sys_home'].'siteadmin/?func=monitor', 
			 SUBCONTEXT == 'monitor');
  
  pagemenu_submenu_content(pagemenu_submenu_entry(_("Monitor Spams"),
						  $GLOBALS['sys_home'].'siteadmin/spamlist.php').


			   pagemenu_submenu_entry(_("Check Last Logins"), 
						  $GLOBALS['sys_home'].'siteadmin/lastlogins.php'));
  pagemenu_submenu_end();

}

?>
