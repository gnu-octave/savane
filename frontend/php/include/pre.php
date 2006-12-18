<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gna.org>
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

# No variable beginning by $sys_ should be set so far because these
# are system configuration from /etc conffile.
# Normally, the code is well thought enough so it makes no real profound
# different if some malicious user define a $sys_ variable before the
# conffile is read: the conffile will overwrite any important variable.
# But it is cleaner to simply unset any $sys_ variable set before we
# read the conffile, to tighten security as soon as possible.
# It also avoid register_globals_off() to unset by mistake these variables
# 
# It use strstr so it is very fast. 
#
# This means that it will never possible to set $sys_ outside of the conffile.
# (which is not a problem)
foreach ($GLOBALS as $key => $value)
{ 
  # Search for sys_ (conffile settings)
  if (!strstr($key, "sys_"))
    { continue; }

  # Search for int_ (internal global)
  if (!strstr($key, "int_"))
    { continue; }
  
  # Stop here otherwise, with no detail whatsoever
  error_log("attempt to set a sys_ or int_ variable using globals, exit - ".$_SERVER['REMOTE_ADDR']." at ".$_SERVER['REQUEST_URI']);
  exit;
}



# Defines all of the Savane hosts, databases, etc.

# Default values, so they cannot be found undefined in the code
$sys_name = 'ChangeMyName';
$sys_logo_name = 'floating.png';
$sys_debug_on = FALSE;
$sys_use_google = FALSE;
$sys_use_pamauth = FALSE;
$stone_age_menu = FALSE;

# This needs to be loaded first because the lines below depend upon it.
if (getenv('SAVANE_CONF'))
{ require getenv('SAVANE_CONF').'/.savane.conf.php'; }
elseif (getenv('SV_LOCAL_INC_PREFIX'))
{ require getenv('SV_LOCAL_INC_PREFIX').'/.savane.conf.php'; }
else
{
  # go back to default location
  require '/etc/savane/.savane.conf.php';
}

if ($GLOBALS['sys_url_topdir'] != '/')
{
  $sys_home = $GLOBALS['sys_url_topdir'].'/';
}
else
{
  $sys_home = $GLOBALS['sys_url_topdir'];
}

# Defines the https url, if available -- no path is added since this
# variable can be used with REQUEST_URI added.
if (isset($GLOBALS['sys_https_host']))
{
  $sys_https_url = 'https://'.$GLOBALS['sys_https_host'];
}
else
{
  $sys_https_url = 'http://'.$GLOBALS['sys_default_domain'];
}

# If file upload limit was not defined in the configuration file
# we set it arbitrarily to 1/2 MB, something that should work out of the
# box on most systems.
# (depends on MySQL max_allowed_packet and PHP upload_max_filesize
if (!isset($GLOBALS['sys_upload_max']))
{
  $GLOBALS['sys_upload_max'] = 512;
}


# require_directory
# sources (requires) all specific include files of a module from
# the include area (all include files of a module are arranged
# in subdirectories in the includes area, so this routine sources
# just all of the *.php files found in the module's subdirectory).

# Prevent declaration by users.
if (isset($_GET['module']) ||
    isset($_POST['module']) ||
    isset($_COOKIE['module']) ||
    isset($_SERVER['module']) ||
    isset($_ENV['module']) ||
    isset($_FILES['module']) ||
    isset($_REQUEST['module']))
{ exit(); }

function require_directory ($module)
{
  if ($module=="")
    { return; }
  if (!empty($GLOBALS['directory_'.$module.'_is_loaded']))
    { return; }

  $dir = dirname(__FILE__).'/'.$module;
  if (is_dir($dir))
    {
      $odir = opendir($dir);
      while ($file = readdir($odir))
	{
	  if (eregi(".*\.(php)$", $file))
	    {
	      require_once($dir."/".$file);
	    }
	}
      closedir($odir);
    }

  $GLOBALS['directory_'.$module.'_is_loaded'] = 1;
}

function get_module_include_dir ($phpself, $true_artifact=0, $true_dir=0)
{
  $guess = basename(dirname($phpself));

  if (!$true_dir && $guess == "admin")
    {
      # Need to go deeper
      $guess = basename(dirname(dirname($phpself)));
    }

  if (!$true_artifact) {
    # we have some special cases:
    #  - bugs, patch, task go in trackers
    #  - news and forum go in news
    if (($guess == 'bugs') ||
	($guess == 'patch') ||
	($guess == 'task') ||
	($guess == 'cookbook') ||
	($guess == 'support'))
      {
	$guess = 'trackers';
      }
    else if ($guess == 'forum')
      {
	$guess = 'news';
      }
  }

  return $guess;
}

/**************************************************************
       Usual requires, always useful
**************************************************************/

# sanitize user input, focusing register globals set to off
require dirname(__FILE__).'/sane.php';

# version info
require dirname(__FILE__).'/version.php';

# i18n setup
require dirname(__FILE__).'/i18n.php';

# base error library for new objects
require dirname(__FILE__).'/Error.class';

# database abstraction
require dirname(__FILE__).'/database.php';

# user functions like get_name, logged_in, etc
require dirname(__FILE__).'/user.php';

# various utilities
require dirname(__FILE__).'/utils.php';

# security library
require dirname(__FILE__).'/session.php';

# theme - color scheme informations
require dirname(__FILE__).'/theme.php';

# title, helper to find out appropriate info depending on the context,
# like title
require dirname(__FILE__).'/context.php';

# left-hand and top menu nav library (requires context to be set)
require dirname(__FILE__).'/sitemenu.php';
require dirname(__FILE__).'/pagemenu.php';

# HTML layout class, may be overriden by the Theme class
require dirname(__FILE__).'/Layout.class';

$HTML = new Layout();

# group functions like get_name, etc
require dirname(__FILE__).'/Group.class';

# member functions like member_add, member_approve, etc
require dirname(__FILE__).'/member.php';

# exit_error library
require dirname(__FILE__).'/exit.php';

#  send mail library
require dirname(__FILE__).'/sendmail.php';

# various html libs like button bar, themable
require dirname(__FILE__).'/html.php';
require dirname(__FILE__).'/markup.php';

# graphics library
require dirname(__FILE__).'/graphs.php';

# calendar library
require dirname(__FILE__).'/calendar.php';

# forms library
require dirname(__FILE__).'/form.php';

# spam filtering library
require dirname(__FILE__).'/spam.php';
require dirname(__FILE__).'/dnsbl.php';

# search tools, frequently needed
require_directory('search');

/**************************************************************
       Set up proper use of UTF-8, even if the webserver does
       not serve it by default
**************************************************************/

header("Content-Type: text/html; charset=utf-8");


/**************************************************************
       Start user session
**************************************************************/

# Connect to db
db_connect();

# sys_unix_group_name is maybe defined
# in this case, we want sys_group_id
if (isset($GLOBALS['sys_unix_group_name']))
{
  $search_group = $GLOBALS['sys_unix_group_name'];
  $res_grp = db_query("SELECT group_id FROM groups WHERE unix_group_name='$search_group'");
  $sys_group_id = db_result($res_grp,0,'group_id');
}

# determine if they're logged in
session_set();


# If logged in, do a few setups
if (user_isloggedin())
{
  # Set timezone
  putenv('TZ='.user_get_timezone());

  # Find out if the stone age menu is required
  if (user_get_preference("stone_age_menu"))
    { $GLOBALS['stone_age_menu'] = 1; }
} else {
  # Set default timezone - avoid PHP warning
  putenv('TZ=UTC');
}

# redirect them from http to https if they said so at login time
if (!session_issecure() && isset($_COOKIE['redirect_to_https']) && $GLOBALS['sys_https_host'])
     header('Location: https://'.$GLOBALS['sys_https_host'].$REQUEST_URI);

/**************************************************************
       Defines every information useful
       in case of a project page
**************************************************************/

# defines the artifact we are using
if(!defined('ARTIFACT'))
     define('ARTIFACT', get_module_include_dir($_SERVER['REQUEST_URI'], 1));

# if we are on an artifact index page and we have only one argument which is
# a numeric number, we suppose it is an item_id
# Maybe it was a link shortcut like
# blabla.org/task/?nnnn (blabla.org/task/?#nnnn cannot work because # is 
# not sent by the browser as it's a tag for html anchors)
if ((ARTIFACT == "bugs" ||
     ARTIFACT == "task" ||
     ARTIFACT == "support" ||
     ARTIFACT == "patch" ||
     ARTIFACT == "cookbook")
    && !empty($_SERVER['QUERY_STRING'])
    && ctype_digit($_SERVER['QUERY_STRING']))
{
  sane_set("item_id", $_SERVER['QUERY_STRING']);
  sane_set("func", "detailitem");
}


# Set the CONTEXT and SUBCONTEXT constants, useful to guess page titles
# but also to find out if cookbook entries are relevant

context_guess();
# Set the AUDIENCE constant
user_guess();


# if we got an item_id and no group_id we need to get the appropriate
# group_id
if (!isset($group_id) && !isset($group_name) && isset($item_id))
{
  $result = db_query("SELECT group_id FROM ".ARTIFACT." WHERE bug_id='$item_id'");
  if (db_numrows($result))
    {  
      sane_set("group_id", db_result($result,0,'group_id')); }
  else
    {
      exit_error(_("Item not found"));
    }

  # Special case: if it the item is from the system group and we are on the
  # cookbook, we may want to pretend that an item belong a given group while
  # it actually belongs to the system group.
  if (ARTIFACT == 'cookbook' &&
      $group_id == $sys_group_id &&
      sane_get("comingfrom"))
    {
      sane_set("group_id", sane_get("comingfrom"));
    }

}

# if we got a forum_id and no group_id, we need to get the appropriate
# group_id
# (FIXME: in the future it could follow the naming scheme of trackers)
if (!isset($group_id) && !isset($group_name) && isset($forum_id))
{
  $result = db_query("SELECT group_id FROM forum_group_list WHERE group_forum_id='$forum_id'");
  if ($result)
    {  sane_set("group_id", db_result(($result),0,'group_id')); }
}

# if we got a msg_id and no group_id, we need to get the appropriate
# group_id
# (FIXME: in the future it could follow the naming scheme of trackers)
if (!isset($group_id) && !isset($group_name) && isset($msg_id))
{
  $result = db_query("SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id,forum.thread_id FROM forum_group_list,forum WHERE forum_group_list.group_forum_id=forum.group_forum_id AND forum.msg_id='$msg_id'");
  if ($result)
    {  sane_set("group_id", db_result(($result),0,'group_id')); }
}

# defines group_id if group is set
# defines group_name if group_id is set
unset($res_grp);
if (isset($group) && !isset($group_id))
{
  $res_grp = db_query("SELECT group_id,status FROM groups WHERE unix_group_name='$group'");
  sane_set("group_id", db_result($res_grp,0,'group_id'));
  sane_set("group_name", $group);
}
elseif (isset($group_id))
{
  $res_grp = db_query("SELECT unix_group_name,status FROM groups WHERE group_id='$group_id'");
  sane_set("group_name", db_result($res_grp,0,'unix_group_name'));
}

# If group_id is defined, we are on a project page, we have several checks
# to make
if (isset($group_id))
{
  if (!$res_grp)
    {
      $res_grp = db_query("SELECT unix_group_name,status FROM groups WHERE group_id='$group_id'");
    }
  # Check if the group truly exists
  if (!db_numrows($res_grp))
    {
      exit_error(_("Project not found"));
    }

  # Ignore status of the project if being registered
  if (db_result($res_grp,0,'status') != 'I')
    {
      # Check if the project is active
      if (db_result($res_grp,0,'status') != 'A')
	{
          # No active but in Maintenance mode, it is ok for super user
	  if (db_result($res_grp,0,'status') == 'M' && !user_is_super_user())
	    {
	      exit_error(_("This project is in maintenance mode"));
	    }
	  elseif (db_result($res_grp,0,'status') == 'M' && user_is_super_user())
	    {
	      fb(_("Note: this project is in maintenance mode"));
	    }
	  elseif (!user_is_super_user())
	    {
              # Other cases, no access granted
	      exit_error(_("This project is not in active state"));
	    }
	}
    }

  # check if we are on the correct page
  # (you can avoid it with $no_redirection=1)
  # if getTypeBaseHost() = "", we use the default host
  if (isset($group_id) && empty($no_redirection))
    {
      $project = project_get_object($group_id);
      if (strcasecmp($_SERVER['HTTP_HOST'], $project->getTypeBaseHost()) != 0 && $project->getTypeBaseHost())
	{
	  header ('Location: http'.(session_issecure()?'s':'').'://'.$project->getTypeBaseHost().$_SERVER["REQUEST_URI"]);
	  exit;
	}
    }
}

# If requires/include for an artifact exists, load them all
# In any case, set the ARTIFACT constant.
require_directory(get_module_include_dir($_SERVER['PHP_SELF']));

?>
