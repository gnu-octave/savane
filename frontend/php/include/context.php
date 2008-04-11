<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
# Copyright (C) 2008 Alex Conchillo Flaque
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


require_once(dirname(__FILE__).'/Group.class');

# Guess the context of the current page
function context_guess () 
{
  return context_guess_from_url($_SERVER['SCRIPT_NAME']);
}

# Get the context given the url. Fpr best efficiency, this function will 
# return guessed values as soon as possible.
# As contest should be always available in pages, it will be set as constants
function context_guess_from_url ($page, $dontset=false) 
{
  # By default, we consider that the action, called subcontext, is browsing.
  # Only trackers allows actions that are not browsing or configuration.
  $subcontext = "browsing";
  
  # Obtain the name of the current page
  $page_basename = basename($page);

  # Try a first guess of the context
  $context = basename(dirname($page));

  # If context is projects, it is actually a the project/ index page
  # that is available at the url localhost/projects/thisgroup
  if ($context == "projects")
    {
      $context = "project";
      return context_set($context, $subcontext, $dontset);
    }

  # If we are in project, we need to look at the actuel script pagename
  # as it may gives subcontext details
  # This is because we want to print very short title for this specific 
  # part of the interface, breaking the principle of having generic context
  # and page subtitles added after semicolon.

  if ($context == "project")
    {
      if ($page_basename == "search.php")
	{
	  $subcontext = "search";
	  return context_set($context, $subcontext, $dontset);
	}
      if ($page_basename == "memberlist.php")
	{
	  $subcontext = "members";
	  return context_set($context, $subcontext, $dontset);
	}
      if ($page_basename == "memberlist-gpgkeys.php")
	{
	  $subcontext = "members-gpgkeys";
	  return context_set($context, $subcontext, $dontset);
	}
    }

  # If we are in my, we need to look at the actuel script pagename
  # To find out the subcontext. 
  # This is because we want to print very short title for this specific 
  # part of the interface, breaking the principle of having generic context
  # and page subtitles added after semicolon.
  if ($context == "my")
    {
      if ($page_basename == "bookmarks.php")
	{
	  $subcontext = "bookmarks";
	  return context_set($context, $subcontext, $dontset);
	}
      if ($page_basename == "items.php")
	{
	  $subcontext = "items";
	  return context_set($context, $subcontext, $dontset);
	}
      if ($page_basename == "groups.php")
	{
	  $subcontext = "groups";
	  return context_set($context, $subcontext, $dontset);
	}
      if ($page_basename == "votes.php")
	{
	  $subcontext = "votes";
	  return context_set($context, $subcontext, $dontset);
	}
    }

  extract(sane_import('request', array('func')));

  # Same with site administration part  
  if ($context == "siteadmin")
  {
      if ($page_basename == "group_type.php" || 
	  $page_basename == "retestconfig.php")
	{
	  $subcontext = "configure";
	  return context_set($context, $subcontext, $dontset);
	}
      if ($page_basename == "grouplist.php" ||
	  $page_basename == "groupedit.php" ||
	  $page_basename == "userlist.php" ||
	  $page_basename == "usergroup.php")
	{
	  $subcontext = "manage";
	  return context_set($context, $subcontext, $dontset);
	}
      if ($page_basename == "spamlist.php" ||
	  $page_basename == "lastlogins.php")
	{
	  $subcontext = "monitor";
	  return context_set($context, $subcontext, $dontset);
	}
      if (isset($func))
	{
	  $subcontext = $func;
	}
      return context_set($context, $subcontext, $dontset);
    }


  # If we are in usual trackers pages, try to guess the action (subcontext) 
  # from the arguments passed in the request.
  # We want to know if the guy is:
  #          - posting new items
  #          - editing items / posting comments
  #          - doing searches 
  #          - doing configuration
  # This is relevant if ARTIFACT has been already defined, which means
  # we are for sure in trackers pages.
  if (defined('ARTIFACT') && $context != "admin") 
    {
      if ($func == 'additem')
	{ 
	  $subcontext = 'postitem';
	  return context_set($context, $subcontext, $dontset);
	}
      if ($func == 'detailitem')
	{ 
	  $subcontext = 'edititem'; 
	  return context_set($context, $subcontext, $dontset);
	}
      if ($func == 'search')
	{ 
	  $subcontext = 'search';
	  return context_set($context, $subcontext, $dontset);
	}
    }

  # If we are in admin pages, we need to go deeped to find the appropriate
  # main context
  if ($context == 'admin')
    {
      $subcontext = 'configure';

      # If ARTIFACT has been defined, we are in a tracker configuration for
      # sure.
      # Otherwise, we have to go deeper
      if (defined('ARTIFACT'))
	{
	  $context = ARTIFACT;
	  return context_set($context, $subcontext, $dontset);

	}
      else
	{
	  $context = basename(dirname(dirname($page)));
	  return context_set($context, $subcontext, $dontset);
	}
    }

  # Normally, context should have been guessed already
  return context_set($context, $subcontext, $dontset);
}

# Defines context
function context_set ($context, $subcontext, $dontset=false) 
{
  # Dont set special mode is used? Then simply return the information
  # (yes, this part is a bit complicated but it is only because of the 
  # unplanned stone age menu that this things have a use)
  if ($dontset)
    {
      return $context;
    }

  # Defines main context, kind of pages (cvs, bug tracker...)
  if (!defined('CONTEXT'))
    define('CONTEXT', $context);
  # Defines subcontext, kind of action done (postitem...)
  define('SUBCONTEXT', $subcontext);

  return true;
}

# Get title depending on the context
function context_title ()
{
  global $group_id;

  switch (CONTEXT)
    { 
    case 'siteadmin': $title = _("Site Administration"); break;

    case 'project': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Administration Summary"); break;
	case 'search': $title = _("Search in this Group"); break;
	default: $title = _("Summary"); break;
	}
      break;

    case 'download': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Filelist Administration"); break;
	default: $title = _("Filelist"); break;
	}
      break;

    case 'cvs': $title = _("CVS Repositories"); break;

    case 'arch': $title = _("GNU Arch Repositories"); break;

    case 'svn': $title = _("Subversion Repositories"); break;

    case 'git': $title = _("Git Repositories"); break;

    case 'hg': $title = _("Mercurial Repositories"); break;
      
    case 'userguide': 
      $title = _("In Depth Guide"); 
      $group_id = $GLOBALS['sys_group_id']; 
      break;

    case 'cookbook': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Cookbook Administration"); break;
	default: $title = _("Cookbook"); break;
	}
      break;

    case 'support': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Support Tracker Administration"); break;
	default: $title = _("Support"); break;
	}
      break;
      
    case 'bugs': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Bugs Tracker Administration"); break;
	default: $title = _("Bugs"); break;
	}
      break;

    case 'bugs': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Bugs Tracker Administration"); break;
	default: $title = _("Bugs"); break;
	}
      break;

    case 'task': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Tasks Manager Administration"); break;
	default: $title = _("Tasks"); break;
	}
      break;

    case 'patch': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Patch Manager Administration"); break;
	default: $title = _("Patches"); break;
	}
      break;

    case 'news': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("News Manager Administration"); break;
	default: $title = _("News"); break;
	}
      break;
      
      # For now, forum case do as news do. 
      # FIXME: if we were to use forum, it should state forum but only
      # if we are sure it is not a news item. In upstream Savane, there is
      # no forum activated.
    case 'forum': 
      {
	$title = _("News"); 
	# For site-wide news, Unset group_id so the name of the administration
	# group is not printed in the title, redundant with the [sys_name]
	if ($group_id == $GLOBALS['sys_group_id'])
	  { unset($group_id); }
	break;	
      }
      
    case 'mail': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("Mailing Lists Administration"); break;
	default: $title = _("Mailing Lists"); break;
	}
      break;

    case 'searchingroup': $title = _("Search"); break;

    case 'people': $title = sprintf(_("People at %s"), $GLOBALS['sys_name']); break;
      
    case 'my': 
      switch (SUBCONTEXT)
	{
	case 'configure': $title = _("My Account Configuration"); break;
 	case 'items': $title = _("My Items"); break;
 	case 'votes': $title = _("My Votes"); break;
 	case 'groups': $title = _("My Group Membership"); break;
 	case 'bookmarks': $title = _("My Bookmarks"); break;
	default: $title = _("My Incoming Items"); break;
	}
      break;
      
    default: $title = false;
    }
	  

#	case 'admin': $title = _("Site Administration"); break;
  
  if (isset($group_id))
    {
      $project = project_get_object($group_id);
      # I18N
      # This is "<projectname> - <title>"
      $title = sprintf("%s - %s", $project->getPublicName(), $title);
    }
  
  return $title;

}

function context_icon ()
{
  switch (CONTEXT)
    {
    case 'siteadmin': return 'admin'; break;
    case 'my': 
	switch (SUBCONTEXT)
	  {
	  case 'groups': return 'people'; break;
	  case 'configure': return 'preferences'; break;
	  default: return 'desktop'; break;
	  }
	break;
    case 'project': 
	switch (SUBCONTEXT)
	  {
	  case 'search': return 'directory'; break;
	  case 'members': return 'people'; break;
	  case 'members-gpgkeys': return 'keys'; break;
	  case 'configure': return 'preferences'; break;
	  default: return 'main'; break;
	  }
	break;
    case 'forum': return 'news'; break;
    case 'bugs': return 'bug'; break;
    case 'doc': return 'man'; break;
    case 'userguide': return 'man'; break;
    case 'cookbook': return 'man'; break;
    case 'support': return 'help'; break;
    case 'mail': return 'mail'; break;
    case 'task': return 'task'; break;
    case 'cvs':
    case 'arch':
    case 'svn':
    case 'git':
    case 'hg': return 'cvs'; break;
    case 'news': return 'news'; break;
    case 'special': return 'news'; break;
    case 'patch': return 'patch'; break;
    case 'download': return 'download'; break;
    case 'people': return 'people'; break;
    case 'search': return 'directory'; break;
    default: return 'main'; break;
    }
}
