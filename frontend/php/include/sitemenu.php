<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: sitemenu.php 5517 2006-03-09 22:58:11Z yeupou $
#
#  Copyright 1999-2000 (c) The SourceForge Crew
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

require_once(dirname(__FILE__).'/html.php');
# search tools, frequently needed
require_directory('search');

function menu_print_sidebar($params) 
{ return sitemenu($params); }

function sitemenu ($params)
{
  global $HTML;

  # Define variables
  if (!isset($params['title']))
    $params['title'] = '';
  if (!isset($params['toptab']))
    $params['toptab'] = '';
  if (!isset($params['group']))
    $params['group'] = '';

  print '
 <ul class="menu">
        <li class="menulogo">
';

  if ($GLOBALS['sys_logo_name'])
    {
      print '          '.utils_link($GLOBALS['sys_home'], html_image($GLOBALS['sys_logo_name'],array('alt'=>sprintf(_("Back to %s Homepage"), $GLOBALS['sys_name'])), 0));
    }
  else
    {
      print '          <br />'.utils_link($GLOBALS['sys_home'], $GLOBALS['sys_name'], 0, 1, sprintf(_("Back to %s Homepage"), $GLOBALS['sys_name']));
    }

  print '
        </li><!-- end menulogo -->';
  if (!user_isloggedin())
    {
      print menu_notloggedin();
    }
  else
    {
      print menu_loggedin($params['title'], $params['toptab'], $params['group']);
    }
  print menu_thispage($params['title'], $params['toptab'], $params['group']);
  print menu_search();
  # Site Admin menu added here
  if (user_can_be_super_user())
    {
      print menu_site_admin();
    }
  print menu_projects();
  print menu_help();
  # Valid HTML is now part of the site specific content
  utils_get_content("menu");

  print '
 </ul><!-- end menu -->
';

}

# Extract some context information that could be necessary to provide links
# that need to keep such context
function sitemenu_extraurl ($only_with_post=false) 
{
  # If only_with_post is set, it means that we will return nothing if the
  # page was loaded with a get
  if ($only_with_post && $_SERVER["REQUEST_METHOD"] != "POST")
    { return; }

  $extraurl = '';
  if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
      if (!empty($GLOBALS['group_name']))
	{ $extraurl .= "&amp;group=".htmlspecialchars($GLOBALS['group_name'])."&amp;"; }
      if (!empty($GLOBALS['item_id']))
	{ $extraurl .= "&amp;func=detailitem&amp;item_id=".htmlspecialchars($GLOBALS['item_id'])."&amp;"; }
    }
  else
    {
      if (!empty($GLOBALS['item_id']) && ctype_digit($_SERVER['QUERY_STRING']))
	{
          # Short link case (like /bugs/?212)
	  $extraurl .= "&amp;func=detailitem&amp;item_id=".htmlspecialchars($GLOBALS['item_id']);
	}
      else
	{
	  $extraurl = htmlspecialchars($_SERVER['QUERY_STRING']);
	  $extraurl = str_replace("reload=1&amp;", "", $extraurl);
	  $extraurl = str_replace("printer=1&amp;", "", $extraurl);
	  $extraurl = "&amp;".$extraurl;	
	}
    }

  return $extraurl;
}

function menu_site_admin() 
{ return sitemenu_site_admin(); }

# Menu entry  for all admin tasks when logged as site administor
function sitemenu_site_admin()
{
  # This menu is shown whether the user is a logged as super user or not
  global $HTML;
  $is_su = user_is_super_user();
  $HTML->menuhtml_top(_("Site Administration"));
  $HTML->menu_entry($GLOBALS['sys_home'].'siteadmin/',_("Main page"), $is_su);
  $HTML->menu_entry($GLOBALS['sys_home'].'task/?group='.$GLOBALS['sys_unix_group_name'].'&amp;category_id=1&amp;status_id=1&amp;set=custom#results',_("Pending projects"));
  $HTML->menu_entry($GLOBALS['sys_home'].'news/approve.php?group='.$GLOBALS['sys_unix_group_name'],_("Site news approval"), $is_su);

  $HTML->menuhtml_bottom();

}


function menu_search() 
{ return sitemenu_search(); }

function sitemenu_search()
{
  global $HTML,$group_id;
  $HTML->menuhtml_top(_("Search"));
  print '       <li class="menusearch">';
  print search_box("", "menu");
  print '       </li><!-- end menusearch -->';
  $HTML->menuhtml_bottom();
}

function menuhtml_top($string) 
{ return sitemenuhtml_top($string); }

#deprecated - theme wrapper
function sitemenuhtml_top($title)
{
  /*
		Use only for the top most menu
  */
  theme_menuhtml_top($title);
}


function menu_projects() 
{ return sitemenu_projects(); }

# Hosted projects
function sitemenu_projects()
{
  global $HTML;
  $HTML->menuhtml_top(_("Hosted Projects"));
  $HTML->menu_entry($GLOBALS['sys_home'].'register/',
		    _("Register New Project"),
		    user_isloggedin(),
		    sprintf(_("Register your project at %s"),$GLOBALS['sys_name']));
 $HTML->menu_entry($GLOBALS['sys_home'].'search/index.php?type_of_search=soft&amp;words=%%%',
		   _("Full List"),
		   1,
		   _("Browse the full list of hosted projects"));
  $HTML->menu_entry($GLOBALS['sys_home'].'people/',
		    _("Contributors Wanted"),
		    1,
		    _("Browse the list of request for contributions"));
 $HTML->menu_entry($GLOBALS['sys_home'].'stats/',
		   _("Statistics"),
		   1,
		   sprintf(_("Browse statistics about %s"),$GLOBALS['sys_name']));
  $HTML->menuhtml_bottom();
}


function menu_thispage($page_title, $page_toptab=0, $page_group=0) 
{ return sitemenu_thispage($page_title, $page_toptab, $page_group); }

##
# Page specific toolbox
function sitemenu_thispage($page_title, $page_toptab=0, $page_group=0)
{
  global $HTML, $sys_group_id, $group_id;

  $HTML->menuhtml_top(_("This Page"));
  $extraurl = sitemenu_extraurl();

  $HTML->menu_entry($_SERVER['SCRIPT_NAME']."?reload=1".$extraurl,
		    _("Clean Reload"),
		    1,
		    _("Reload the page without risk of reposting data"));
  $HTML->menu_entry($_SERVER['SCRIPT_NAME']."?printer=1".$extraurl,
		    _("Printer Version"),
		    1,
		    _("Show this page with a style adapted to printers"));
  $HTML->menuhtml_bottom();

  if (empty($_POST))
    {
      if (user_isloggedin() && user_get_preference("use_bookmarks"))
	{
	  $bookmark_title = urlencode(context_title());
	  
	  if ($page_title)
	    { $bookmark_title .= urlencode(_(": ").$page_title); }
	    
	    $HTML->menu_entry($GLOBALS['sys_home'].'my/bookmarks.php?add=1&amp;url='.urlencode($_SERVER['REQUEST_URI']).'&amp;title='.$bookmark_title,
			      _("Bookmark It"),
			      1,
			      _("Add this page to my bookmarks"));
	}
    }
  
  ##
  # Show related recipes. Maybe not the best way to put it, but in "this page"
  # it makes sense.
  # And it is hard to find a place elsewhere where it would not be really nasty
  $sql_role = '';
  $sql_groupid = '';
  if ($group_id)
    {
      # We are on a group page
      $sql_groupid = "OR group_id='$group_id'"; 
    }
  if (defined('ARTIFACT') && AUDIENCE == 'members')
    {
      # We are on a tracker and we have a project member:
      #  - it may be a manager or a technician, or both
      # We must select
      #  - items for all members
      #  + items for manager if we have a manager
      #  + items for technicians if we have a technician
      # Which leads to
      #  allmembers=1 OR (manager=1 if manager) OR (technician=1 if technician)
      if (member_check(0,$group_id,member_create_tracker_flag(ARTIFACT).'1'))
	{
          # It is a technician
	  $sql_role = "OR audience_technicians='1'";
	}
      if (member_check(0,$group_id,member_create_tracker_flag(ARTIFACT).'3'))
	{
          # It is a manager
	  $sql_role .= " OR audience_managers='1'";
	}      
    }

  # If CONTEXT or SUBCONTEXT was set to non-existent context,
  # the SQL should not fail - so error reporting works in other usages of db_query.
  $result = db_query("DESCRIBE cookbook_context2recipe");
  $valid_contexts = array();
  while($row = mysql_fetch_array($result))
    $valid_contexts[] = $row['Field'];

  if (in_array('context_'.CONTEXT, $valid_contexts)
      and in_array('subcontext_'.SUBCONTEXT, $valid_contexts))
    {
      $sql = "SELECT recipe_id FROM cookbook_context2recipe WHERE (group_id='$sys_group_id' $sql_groupid) AND context_".CONTEXT."='1' AND subcontext_".SUBCONTEXT."='1' AND (audience_".AUDIENCE."='1' $sql_role)";
      
      $result = db_query($sql);
      $rows = db_numrows($result);
    }
  else
    {
      // No recipe found? End here
      return;
    }

  # Put a limit on the number of shown recipe to 25
  $limit = 25;
  
  # Build a sql to obtain summaries
  $sql_itemid = '';
  $sql_privateitem = '';
  # Check whether the user is authorized to read private items for the active
  # project, if there is an active project
  if ($group_id)
    {
      if (!member_check_private(0, $group_id))
	{
	  $sql_privateitem = "AND privacy<>'2'";
	}
    }

  for ($i = 0; $i < $rows; $i++) 
    {
      if ($sql_itemid)
	{ $sql_itemid .= " OR "; }
      $sql_itemid .= "bug_id='".db_result($result, $i, 'recipe_id')."'";
    }

  $rows = 0;
  if ($sql_itemid) {
    $sql = "SELECT bug_id,priority,summary FROM cookbook WHERE ($sql_itemid) AND resolution_id='1' $sql_privateitem ORDER BY priority DESC, summary ASC LIMIT $limit";
    $result = db_query($sql);
    $rows = db_numrows($result);
  }

  # No recipe found? End here
  # Such test has been made before, but before we did not knew if the item
  # was actually approved
  if ($rows < 1)
    { return; }

  print "\n";
  print '<li class="relatedrecipes">';
  print '<div><a name="relatedrecipes"></a><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/contexts/help.png" alt="'._("Related Recipes:").'" class="icon" />'._("Related Recipes:").'</div>';
  for ($i = 0; $i < $rows; $i++) 
    {
      print '<div class="relatedrecipesitem">';
      # Show specific background color only for high priority item, no
      # need to disturb the eye otherwise
      $priority = db_result($result, $i, 'priority');
      if ($priority > 4)
	{
	  print '<div class="priore">';
	}

      # The full summary will only be in a help balloon, the summary directly
      # shown will be cut to 40 characters.
      # Summaries should be kept short.
      print utils_link($GLOBALS['sys_home'].'cookbook/?func=detailitem&amp;comingfrom='.$group_id.'&amp;item_id='.db_result($result, $i, 'bug_id'),
		       utils_cutstring(db_result($result, $i, 'summary'),40),
		       "menulink",
		       '1',
		       db_result($result, $i, 'summary'));
      if ($priority > 4)
	{
	  print '</div>';
	}
      print '</div>';
    }
  print '</li><!-- end relatedrecipes -->';

}

function menu_help() 
{ return sitemenu_help(); }

# Help / Docs
function sitemenu_help()
{
  global $HTML;
  $HTML->menuhtml_top(_("Site Help"));

  $HTML->menu_entry($GLOBALS['sys_home'].'cookbook/?group='.$GLOBALS['sys_unix_group_name'],
		    _("User Docs: Cookbook"),
		    1,
		    _("Recipes dedicated to any users, including Project Admins"));
  $HTML->menu_entry($GLOBALS['sys_home'].'userguide/',
		    _("User Docs: In Depth Guide"),
		    1,
		    _("In-depth Documentation dedicated to any users, including Project Admins"));

  $HTML->menu_entry($GLOBALS['sys_home'].'support/?group='.$GLOBALS['sys_unix_group_name'],
		    _("Get Support"),
		    1,
		    sprintf(_("Get help from the Admins of %s, when documentation is not enough"), $GLOBALS['sys_name']));
  $HTML->menuhtml_bottom();
  $HTML->menu_entry($GLOBALS['sys_home'].'contact.php',
		    _("Contact Us"),
		    1,
		    sprintf(_("Contact address of %s Admins"),$GLOBALS['sys_name']));
  $HTML->menuhtml_bottom();
}

function menu_loggedin($page_title, $page_toptab=0, $page_group=0) 
{ return sitemenu_loggedin($page_title, $page_toptab, $page_group); }

function sitemenu_loggedin($page_title, $page_toptab=0, $page_group=0)
{
  global $HTML;
  /*
		Show links appropriate for someone logged in, like account maintenance, etc
  */
  if (!user_is_super_user())
    {
      $HTML->menuhtml_top(sprintf(_("Logged in as %s"), user_getname()));
    }
  else
    {
      $HTML->menuhtml_top('<span class="warn">'.sprintf(_("%s logged in as superuser"), user_getname()).'</span>');
    }
  if (user_can_be_super_user() && !user_is_super_user())
    {
      $HTML->menu_entry($GLOBALS['sys_home'].'account/su.php?action=login&amp;uri='.urlencode($_SERVER['REQUEST_URI']),
			_("Become Superuser"),
			1,
			_("Superuser rights are required to perform site admin tasks"));
    }
  $HTML->menu_entry($GLOBALS['sys_home'].'my/',
		    _("My Incoming Items"),
		    1,
		    _("What's new for me: new items I should have a look at"));
  $HTML->menu_entry($GLOBALS['sys_home'].'my/items.php',
		    _("My Items"),
		    1,
		    _("Browse my items (submitted by me or assigned to me)"));
  if (user_use_votes())
    {
      $HTML->menu_entry($GLOBALS['sys_home'].'my/votes.php',
			_("My Votes"),
			1,
			_("Browse items I voted for"));
    }

  $HTML->menu_entry($GLOBALS['sys_home'].'my/groups.php',
		    _("My Groups"),
		    1,
		    _("List the groups I belong to"));
  if (user_get_preference("use_bookmarks"))
    {
      $HTML->menu_entry($GLOBALS['sys_home'].'my/bookmarks.php',
			_("My Bookmarks"),
			1,
			_("Show my bookmarks"));
    }

  $HTML->menu_entry($GLOBALS['sys_home'].'my/admin/',
		    _("My Account Conf"),
		    1,
		    _("Account configuration: authentication, cosmetics preferences..."));

 if (user_is_super_user())
    {
      $HTML->menu_entry($GLOBALS['sys_home'].'account/su.php?action=logout&amp;uri='.urlencode($_SERVER['REQUEST_URI']),
			_("Logout Superuser"),
			1,
			_("End the Superuser session, go back to normal user session"));
    }
  $HTML->menu_entry($GLOBALS['sys_home'].'account/logout.php',
		    _("Logout"),
		    1,
		    _("End the session, remove the session cookie"));
  $HTML->menuhtml_bottom();
}

function menu_notloggedin() 
{ return sitemenu_notloggedin(); }

function sitemenu_notloggedin()
{
  global $HTML;
  $HTML->menuhtml_top(_("Login Status:"));

  # Get settings not present in REQUEST_URI in case of a POST form
  $extraurl = sitemenu_extraurl(true);
  if ($extraurl)
    { $extraurl = "?$extraurl"; }

  print '
        <li class="menuitem"> <span class="error">'._("Not Logged In").'</span></li>';

  $HTML->menu_entry($GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'account/login.php?uri='.urlencode($_SERVER['REQUEST_URI'].$extraurl),
		    _("Login"),
		    1,
		    _("Login page - you must have registered an account first"));

  $HTML->menu_entry($GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'account/register.php',
		    _("New User"),
		    1,
		    _("Account registration form"));
  $HTML->menuhtml_bottom();
}

?>
