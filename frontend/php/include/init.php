<?php
# Setup a minimal environment (database, configuration file...)
#
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gna.org>
#
# This file is part of the Savane project
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

/**************************************************************
       Set up proper use of UTF-8, even if the webserver does
       not serve it by default
**************************************************************/

header('Content-Type: text/html; charset=utf-8');


# database abstraction
require_once(dirname(__FILE__).'/database.php');
# security library
require_once(dirname(__FILE__).'/session.php');
# user functions like get_name, logged_in, etc
require_once(dirname(__FILE__).'/user.php');
# title, helper to find out appropriate info depending on the context,
# like title
require_once(dirname(__FILE__).'/context.php');
require_once(dirname(__FILE__).'/exit.php');

# Default values, so they cannot be found undefined in the code
$sys_name = "Change-This-Site-Name-with-\$sys_name";
$sys_logo_name = 'floating.png';
$sys_debug_on = false;
$sys_use_google = false;
$sys_use_pamauth = false;
$stone_age_menu = false;
$sys_spamcheck_spamassassin = false;
$sys_use_krb5 = false;
$sys_upload_max = 512;

$sys_dbhost = 'localhost';
$sys_dbname = 'savane';
$sys_dbuser = 'root';

$sys_incdir = '/etc/savane/content';
$sys_themedefault = 'Emeraud';

$sys_default_domain = $_SERVER['SERVER_NAME'];
if ($_SERVER['SERVER_PORT'] != 80)
     $sys_default_domain .= ':'.$_SERVER['SERVER_PORT'];
$sys_unix_group_name = 'siteadmin';

#print "<pre>";
#print_r($_SERVER);
#print "</pre>";

$sys_mail_domain = 'localhost';
$sys_mail_admin = get_current_user();
$sys_mail_replyto = "NO-REPLY.INVALID-ADDRESS";

# autoconf-based:
require_once(dirname(__FILE__).'/ac_config.php');

# This needs to be loaded first because the lines below depend upon it.
if (getenv('SAVANE_CONF'))
{ require_once(getenv('SAVANE_CONF').'/.savane.conf.php'); }
elseif (getenv('SV_LOCAL_INC_PREFIX'))
{ require_once(getenv('SV_LOCAL_INC_PREFIX').'/.savane.conf.php'); }
else
{
  # go back to default location
  require_once('/etc/savane/.savane.conf.php');
}

// Detect where we are, unless it's explicitely specified in the
// configuration file:
if (empty($sys_www_topdir))
{
  $sys_www_topdir = getcwd();
  $sys_url_topdir = dirname($_SERVER['SCRIPT_NAME']);
  while ($sys_www_topdir != '/' && !file_exists("$sys_www_topdir/.topdir"))
    {
      // cd ..
      $sys_www_topdir = dirname($sys_www_topdir);
      $sys_url_topdir = dirname($sys_url_topdir);
    }
  if (!file_exists("$sys_www_topdir/.topdir"))
    die("Could not find Savane's top directory (missing .topdir file)");
}

// Add a trailing slash
$sys_home = $GLOBALS['sys_url_topdir'];
if (!preg_match('|/$|', $GLOBALS['sys_url_topdir']))
{
  $sys_home = $GLOBALS['sys_url_topdir'].'/';
}

// Defines the https url, if available -- no path is added since this
// variable can be used with REQUEST_URI added. It's used when we need
// to point a https URL (cannot be expressed using a http-relative
// path) and in e-mails.
if (isset($GLOBALS['sys_https_host']))
{
  $sys_https_url = 'https://'.$GLOBALS['sys_https_host'];
}
else
{
  $sys_https_url = 'http://'.$GLOBALS['sys_default_domain'];
}

# require_directory
# sources (requires) all specific include files of a module from
# the include area (all include files of a module are arranged
# in subdirectories in the includes area, so this routine sources
# just all of the *.php files found in the module's subdirectory).

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
	  // - only include PHP scripts
	  // - avoid Emacs temporary files .#filename.php
	  if (eregi("^[^\.].*\.(php)$", $file))
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



# HTML layout class, may be overriden by the Theme class
require_once(dirname(__FILE__).'/Layout.class');

$HTML = new Layout();


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
  $res = db_execute("SELECT group_id FROM groups WHERE unix_group_name=?",
		    array($search_group));
  if (db_numrows($res) != 0)
    $sys_group_id = db_result($res, 0, 'group_id');
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
  $result = db_execute("SELECT group_id FROM ".ARTIFACT." WHERE bug_id=?", array($item_id));
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
  $result = db_execute("SELECT group_id FROM forum_group_list WHERE group_forum_id=?",
		       array($forum_id));
  if ($result)
    {  sane_set("group_id", db_result(($result),0,'group_id')); }
}

# if we got a msg_id and no group_id, we need to get the appropriate
# group_id
# (FIXME: in the future it could follow the naming scheme of trackers)
if (!isset($group_id) && !isset($group_name) && isset($msg_id))
{
  $result = db_execute("SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id,forum.thread_id FROM forum_group_list,forum WHERE forum_group_list.group_forum_id=forum.group_forum_id AND forum.msg_id=?",
		       array($msg_id));
  if ($result)
    {  sane_set("group_id", db_result(($result),0,'group_id')); }
}

# defines group_id if group is set
# defines group_name if group_id is set
unset($res_grp);
if (isset($group) && !isset($group_id))
{
  $res_grp = db_execute("SELECT group_id,status FROM groups WHERE unix_group_name=?",
			array($group));
  sane_set("group_id", db_result($res_grp,0,'group_id'));
  sane_set("group_name", $group);
}
elseif (isset($group_id))
{
  $res_grp = db_execute("SELECT unix_group_name,status FROM groups WHERE group_id=?",
			array($group_id));
  sane_set("group_name", db_result($res_grp,0,'unix_group_name'));
}

# If group_id is defined, we are on a project page, we have several checks
# to make
if (isset($group_id))
{
  if (!$res_grp)
    {
      $res_grp = db_execute("SELECT unix_group_name,status FROM groups WHERE group_id=?",
			    array($group_id));
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
#require_directory(get_module_include_dir($_SERVER['PHP_SELF']));
