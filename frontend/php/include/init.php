<?php
# Setup a minimal environment (database, configuration file...)
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gna.org>
# Copyright (C) 2017, 2018 Ineiev
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

/* Set up proper use of UTF-8, even if the webserver does
   not serve it by default.  */
header('Content-Type: text/html; charset=utf-8');
/* Disallow embedding in any frames.  */
header('X-Frame-Options: DENY');
/* Declare more restrictions on how browsers may assemble pages.  */
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; "
       ."img-src 'self' static.fsf.org");
# Database abstraction.
require_once(dirname(__FILE__).'/database.php');
# Security library.
require_once(dirname(__FILE__).'/session.php');
# User functions like get_name, logged_in, etc.
require_once(dirname(__FILE__).'/user.php');
# Title, helper to find out appropriate info depending on the context,
# like title.
require_once(dirname(__FILE__).'/context.php');
require_once(dirname(__FILE__).'/exit.php');
require_once(dirname(__FILE__).'/utils.php');

# Default values, so they cannot be found undefined in the code.
$sys_name = "Change-This-Site-Name-with-\$sys_name";
$sys_logo_name = 'floating.png';
$sys_use_pamauth = false;
$stone_age_menu = false;
$sys_spamcheck_spamassassin = false;
$sys_use_krb5 = false;
$sys_upload_max = 512;

$sys_dbhost = 'localhost';
$sys_dbname = 'savane';
$sys_dbuser = 'root';

$sys_incdir = '/etc/savane/content';
$sys_appdatadir = '/var/lib/savane';
$sys_trackers_attachments_dir = $sys_appdatadir . '/trackers_attachments';
$sys_themedefault = 'Emeraud';
$sys_enable_forum_comments = 1;
$sys_registration_captcha = 0;
$sys_registration_text_spam_test = 1;
$sys_securimagedir = '/usr/src/securimage';

$sys_default_domain = $_SERVER['SERVER_NAME'];
if ($_SERVER['SERVER_PORT'] != 80)
  $sys_default_domain .= ':'.$_SERVER['SERVER_PORT'];
$sys_unix_group_name = 'siteadmin';

$sys_mail_domain = 'localhost';
$sys_mail_admin = get_current_user();
$sys_mail_replyto = "NO-REPLY.INVALID-ADDRESS";
$sys_email_adress="$sys_mail_admin@$sys_mail_domain";
$sys_gpg_name = "gpg";

# Debug variables
# (add them in tests/minimal_configs/Makefile, possibly commented out).
# Print debug information before exiting:
$sys_debug_on = false;
# Prevent redirections like sv.gnu.org -> sv.nongnu.org.
$sys_debug_nobasehost = false;
# Prevent form duplicate checks which are a PITA during debugging.
$sys_debug_noformcheck = false;
# Log which queries are used the most, using XCache variables.
$sys_debug_sqlprofiler = false;

# Password strength checking.
# Do we have the pwqcheck(1) program from the passwdqc package?
$use_pwqcheck = TRUE;
/* We can override the default password policy
   max=40 is overridden because some users want longer passwords.
   min=default,24,11,8,7 is overridden for N0 passwords
   (the passwords consisting of characters from single class)
   because NIST Electronic Authentification Gudeline
   (Special Publication 800-63-1, Table A.1 on page 107
    http://csrc.nist.gov/publications/nistpubs/800-63-1/SP-800-63-1.pdf)
   suggests that user-chosen 7 characters long password passing extensive
   checks has 27 bits of entropy, the same as 22 characters long
   user-chosen password composed from 10-character alphabet with no checks
   implied, so we can safely admit any 24 characters long passwords. */
$pwqcheck_args = 'match=0 max=256 min=24,24,11,8,7';

# Default uploads directory for './register2/upload.html'.
$sys_upload_dir = "/var/www/submissions_uploads" ;

# autoconf-based:
require_once(dirname(__FILE__).'/ac_config.php');
# Backward compatibility for PHP4.
if (version_compare(PHP_VERSION, '5.0', '<')) require_once(dirname(__FILE__).'/php4.php');

# This needs to be loaded first because the lines below depend upon it.
if (getenv('SAVANE_CONF') and file_exists(getenv('SAVANE_CONF').'/.savane.conf.php'))
  include(getenv('SAVANE_CONF').'/.savane.conf.php');
// deprecated:
elseif (getenv('SV_LOCAL_INC_PREFIX')
        and file_exists(getenv('SV_LOCAL_INC_PREFIX').'/.savane.conf.php'))
  include(getenv('SV_LOCAL_INC_PREFIX').'/.savane.conf.php');
else
  {
    # Go back to default location.
    if (file_exists('/etc/savane/.savane.conf.php'))
      include('/etc/savane/.savane.conf.php');
  }

// Detect where we are, unless it's explicitely specified in the
// configuration file:
if (empty($sys_www_topdir))
  {
    $sys_www_topdir = getcwd();
    $sys_url_topdir = dirname($_SERVER['SCRIPT_NAME']);
    while ($sys_www_topdir != '/' && !file_exists("$sys_www_topdir/.topdir"))
      {
        $sys_www_topdir = dirname($sys_www_topdir);
        $sys_url_topdir = dirname($sys_url_topdir);
      }
    if (!file_exists("$sys_www_topdir/.topdir"))
      die("Could not find Savane's top directory (missing .topdir file)");
  }

# Add a trailing slash.
$sys_home = $GLOBALS['sys_url_topdir'];
if (!preg_match('|/$|', $GLOBALS['sys_url_topdir']))
  $sys_home = $GLOBALS['sys_url_topdir'].'/';

# Defines the https url, if available -- no path is added since this
# variable can be used with REQUEST_URI added. It's used when we need
# to point a https URL (cannot be expressed using a http-relative
# path) and in e-mails.
if (isset($GLOBALS['sys_https_host']))
  $sys_https_url = 'https://'.$GLOBALS['sys_https_host'];
else
  $sys_https_url = 'http://'.$GLOBALS['sys_default_domain'];

# Debug initialization.
if ($sys_debug_on == true)
  {
    # Initialize the variable (avoid later warnings).
    $GLOBALS['debug'] = '';
    $GLOBALS['debug_query_count'] = 0;
    $GLOBALS['debug_queries'] = array();

    # Save the input arrays in case they are emptied
    # (e.g. trackers_run/index.php).
    $GLOBALS['INPUT_SAVE'] = array('get' => $_GET,
                                   'post' => $_POST,
                                   'cookie' => $_COOKIE,
                                   'files' => $_FILES);
    function debug_dump()
    {
      global $INPUT_SAVE;

      print '<pre>';
      print '<hr />';
      print utils_size_readable(memory_get_usage(false)) . '/'
        . utils_size_readable(memory_get_peak_usage(false))
        . ' now/peak memory usage<br />';
      print utils_size_readable(memory_get_usage(true))  . '/'
        . utils_size_readable(memory_get_peak_usage(true))
        . ' now/peak real memory usage<br />';
      print '<hr />';

      # SQL queries counter.
      print "{$GLOBALS['debug_query_count']} database queries used:<br/>";
      foreach($GLOBALS['debug_queries'] as $query_data)
        {
          list($query, $location) = $query_data;
          print "$query [$location]<br />";
        }
      print '<hr />';
      print 'GET:<br />';
      print htmlspecialchars(print_r($INPUT_SAVE['get'], true), ENT_QUOTES);

      print '<hr />';
      print 'POST:<br />';
      print htmlspecialchars(print_r($INPUT_SAVE['post'], true), ENT_QUOTES);

      print '<hr />';
      print 'COOKIE:<br />';
      print htmlspecialchars(print_r($INPUT_SAVE['cookie'], true), ENT_QUOTES);

      print '<hr />';
      print 'FILES:<br />';
      print htmlspecialchars(print_r($INPUT_SAVE['files'], true), ENT_QUOTES);
      print '<hr />';

      # All debug messages.
      if ($GLOBALS['debug'])
        print 'DEBUG information:<br />'.$GLOBALS['debug'];
      print '</pre>';
    }
    register_shutdown_function("debug_dump");

    # Alternate PHP error handler that prints a backtrace.
    function btErrorHandler($errno, $errstr, $errfile, $errline, $context)
    {
      print '<strong>';
      switch ($errno)
        {
        case E_ERROR:             print "Error";                  break;
        case E_WARNING:           print "Warning";                break;
        case E_PARSE:             print "Parse Error";            break;
        case E_NOTICE:            print "Notice";                 break;
        case E_CORE_ERROR:        print "Core Error";             break;
        case E_CORE_WARNING:      print "Core Warning";           break;
        case E_COMPILE_ERROR:     print "Compile Error";          break;
        case E_COMPILE_WARNING:   print "Compile Warning";        break;
        case E_USER_ERROR:        print "User Error";             break;
        case E_USER_WARNING:      print "User Warning";           break;
        case E_USER_NOTICE:       print "User Notice";            break;
        case E_STRICT:            print "Strict Notice";          break;
        case E_RECOVERABLE_ERROR: print "Recoverable Error";      break;
        /* E_DEPRECATED - PHP >= 5.3 : */
        case 8192:                return false; // too much noise
        default:                  print "Unknown error ($errno)"; break;
        }
      print '</strong>';
      print ": $errstr in <strong>$errfile</strong> on line "
            . "<strong>$errline</strong><br />\n";
      print '<pre>';

      # Write my own backtrace function to avoid printing
      # btErrorHandler() in the stack trace.
      $bt = debug_backtrace();
      array_shift($bt); # Remove this very function.
      utils_debug_print_mybacktrace($bt);
      print '</pre>';
      # Don't execute PHP internal error handler.
      return true;
    }
  # Set to the user-defined error handler.
  $old_error_handler = set_error_handler("btErrorHandler");
}

# Stop an failed assertion.  We don't use much assertions though,
# because you can't provide additional feedback for debugging (like
# the value of the invalid variable). Check util_die() instead.
assert_options(ASSERT_BAIL, 1);

# require_directory
# sources (requires) all specific include files of a module from
# the include area (all include files of a module are arranged
# in subdirectories in the includes area, so this routine sources
# just all of the *.php files found in the module's subdirectory).
function require_directory ($module)
{
  if ($module=="")
    return;
  if (!empty($GLOBALS['directory_'.$module.'_is_loaded']))
    return;

  $dir = dirname(__FILE__).'/'.$module;
  if (is_dir($dir))
    {
      $odir = opendir($dir);
      while ($file = readdir($odir))
        {
          // - only include PHP scripts
          // - avoid Emacs temporary files .#filename.php
          if (preg_match("/^[^\.].*\.(php)$/i", $file))
            {
              require_once($dir."/".$file);
            }
        }
      closedir($odir);
    }
  $GLOBALS['directory_'.$module.'_is_loaded'] = 1;
}

function get_module_include_dir($script_name, $true_artifact=0, $true_dir=0)
{
  $guess = basename(dirname($script_name));

  if (!$true_dir && $guess == "admin")
    {
      # Need to go deeper.
      $guess = basename(dirname(dirname($script_name)));
    }

  if (!$true_artifact)
    {
    # We have some special cases:
    #  - bugs, patch, task go in trackers
    #  - news and forum go in news
      if (($guess == 'bugs')
          || ($guess == 'patch')
          || ($guess == 'task')
          || ($guess == 'cookbook')
          || ($guess == 'support'))
        $guess = 'trackers';
      elseif ($guess == 'forum')
        $guess = 'news';
  }
  return $guess;
}

# HTML layout class, may be overriden by the Theme class.
require_once(dirname(__FILE__).'/Layout.class');

$HTML = new Layout();
# Start user session.

# Connect to db.
db_connect();

# sys_unix_group_name is maybe defined
# in this case, we want sys_group_id.
if (isset($GLOBALS['sys_unix_group_name']))
  {
    $search_group = $GLOBALS['sys_unix_group_name'];
    $res = db_execute("SELECT group_id FROM groups WHERE unix_group_name=?",
                      array($search_group));
    if (db_numrows($res) != 0)
      $sys_group_id = db_result($res, 0, 'group_id');
  }

if (!isset($sys_group_id))
  fb(_("Your \$sys_unix_group_name configuration variable refers to a
non-existing project. Please update the configuration."), FB_ERROR);

# Determine if they're logged in.
session_set();

# If logged in, do a few setups.
if (user_isloggedin())
  {
    # Set timezone.
    putenv('TZ='.user_get_timezone());

    # Find out if the stone age menu is required.
    if (user_get_preference('stone_age_menu'))
      $GLOBALS['stone_age_menu'] = 1;
  }
else
  # Set default timezone - avoid PHP warning.
  putenv('TZ=UTC');

# Redirect them from http to https if they said so at login time.
if (!session_issecure() && isset($_COOKIE['redirect_to_https'])
    && $GLOBALS['sys_https_host'])
   header('Location: https://'.$GLOBALS['sys_https_host']
          .$_SERVER['REQUEST_URI']);

# Define every information useful
# in case of a project page.
extract(sane_import('get', array('comingfrom'))); #cookbook
extract(sane_import('request', array('group', 'group_id', 'item_id', 'forum_id')));

# Define the artifact we are using.
if (!defined('ARTIFACT'))
     define('ARTIFACT', get_module_include_dir($_SERVER['SCRIPT_NAME'], 1));

# If we are on an artifact index page and we have only one argument which is
# a numeric number, we suppose it is an item_id.
# Maybe it was a link shortcut like
# blabla.org/task/?nnnn (blabla.org/task/?#nnnn cannot work because # is
# not sent by the browser as it's a tag for html anchors).
# Necessary to determine group_id.
if ((ARTIFACT == "bugs"
     || ARTIFACT == "task"
     || ARTIFACT == "support"
     || ARTIFACT == "patch"
     || ARTIFACT == "cookbook")
    && !empty($_SERVER['QUERY_STRING'])
    && ctype_digit($_SERVER['QUERY_STRING']))
  $item_id = $_SERVER['QUERY_STRING'];

if (!empty($item_id) && !is_numeric($item_id))
  util_die(_("Invalid item ID."));

if (!empty($forum_id) && !is_numeric($forum_id))
  util_die(_("Invalid forum ID."));

# Set the CONTEXT and SUBCONTEXT constants, useful to guess page titles
# but also to find out if cookbook entries are relevant.

context_guess();
# Set the AUDIENCE constant.
user_guess();

# If we got an item_id and no group_id we need to get the appropriate
# group_id.
if (!isset($group_id) && !isset($group) && isset($item_id)
    && in_array(ARTIFACT, array('bugs', 'patch', 'task', 'cookbook', 'support')))
  {
    $result = db_execute("SELECT group_id FROM ".ARTIFACT." WHERE bug_id=?",
                         array($item_id));
    if (db_numrows($result))
      $group_id = db_result($result,0,'group_id');
    else
      exit_error(_("Item not found"));

  # Special case: if it the item is from the system group and we are on the
  # cookbook, we may want to pretend that an item belong a given group while
  # it actually belongs to the system group.
    if (ARTIFACT == 'cookbook'
        && $group_id == $sys_group_id && isset ($comingfrom)
        && $comingfrom && ctype_digit($comingfrom))
      $group_id = $comingfrom;
  }

# If we got a forum_id and no group_id, we need to get the appropriate
# group_id.
# (FIXME: in the future it could follow the naming scheme of trackers)
if (!isset($group_id) && !isset($group) && isset($forum_id))
  {
    $result = db_execute("SELECT group_id FROM forum_group_list "
                         ."WHERE group_forum_id=?",
                         array($forum_id));
    if ($result && db_numrows($result) > 0)
      $group_id = db_result(($result),0,'group_id');
  }

# If we got a msg_id and no group_id, we need to get the appropriate
# group_id.
# (FIXME: in the future it could follow the naming scheme of trackers)
if (!isset($group_id) && isset($msg_id))
  {
    $result = db_execute("SELECT forum_group_list.group_id,"
                         ."forum_group_list.forum_name,forum.group_forum_id,"
                         ."forum.thread_id FROM forum_group_list,forum "
                         ."WHERE forum_group_list.group_forum_id="
                         ."forum.group_forum_id AND forum.msg_id=?",
                         array($msg_id));
    if ($result)
      $group_id = db_result(($result),0,'group_id');
  }

# Define group_id if group is set.
# Define group_name if group_id is set.
$res_grp = null;
if (isset($group) && !isset($group_id))
  {
    $res_grp = db_execute("SELECT group_id,status FROM groups "
                          ."WHERE unix_group_name=?",
                          array($group));
    if (db_numrows($res_grp) > 0)
      $group_id = db_result($res_grp,0,'group_id');
  }
elseif (isset($group_id))
  {
    $res_grp = db_execute("SELECT unix_group_name,status FROM groups "
                          ."WHERE group_id=?",
                          array($group_id));
    if (db_numrows($res_grp) > 0)
      $group = db_result($res_grp,0,'unix_group_name');
  }

# See also $group_name definition in sane.php.
# TODO: don't deal with such variables sitewide, don't use several names

# If group_id is defined, we are on a project page, we have several checks
# to make.
if (isset($group_id))
  {
    if (!$res_grp)
      {
        $res_grp = db_execute("SELECT unix_group_name,status FROM groups "
                              ."WHERE group_id=?",
                              array($group_id));
      }
    # Check if the group truly exists.
    if (!db_numrows($res_grp))
      exit_error(_("Project not found"));

    # Ignore status of the project if being registered.
    if (db_result($res_grp,0,'status') != 'I')
      {
        # Check if the project is active.
        if (db_result($res_grp,0,'status') != 'A')
          {
            # No active but in Maintenance mode, it is ok for super user.
            if (db_result($res_grp,0,'status') == 'M' && !user_is_super_user())
              exit_error(_("This project is in maintenance mode"));
            elseif (db_result($res_grp,0,'status') == 'M' && user_is_super_user())
              fb(_("Note: this project is in maintenance mode"));
            elseif (!user_is_super_user())
              # Other cases, no access granted.
              exit_error(_("This project is not in active state"));
          }
      }

    # Check if we are on the correct page
    # (you can avoid it with $no_redirection=1).
    # If getTypeBaseHost() = "", we use the default host.
    if (isset($group_id) && empty($no_redirection) && !$sys_debug_nobasehost)
      {
        $project = project_get_object($group_id);
        if (strcasecmp($_SERVER['HTTP_HOST'], $project->getTypeBaseHost()) != 0
            && $project->getTypeBaseHost())
          {
            header ('Location: http'.(session_issecure()?'s':'').'://'
                    .$project->getTypeBaseHost().$_SERVER["REQUEST_URI"]);
            exit;
          }
      }
  } # if (isset($group_id))
?>
