<?php
# Check your configuration against recommended values
#
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007 Sylvain Beucler
# Copyright (C) 2018 Ineiev
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

function return_bytes($val)
{
  $val = trim($val);
  $last = strtolower($val{strlen($val)-1});
  switch($last) {
    // The 'G' modifier is available since PHP 5.1.0
  case 'g':
    $val *= 1024;
  case 'm':
    $val *= 1024;
  case 'k':
    $val *= 1024;
  }

  return $val;
}


print "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"
    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n\n";

print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en_US\">\n";
print "<head>\n";
print "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n";
print "<title>Basic PHP tests</title>\n";
print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/internal/testconfig.css\" />\n";
print "</head>\n\n";
print "<body>\n";


print "<h1>Basic PHP pre-tests for Savane installation</h1>\n";
if (empty($inside_siteadmin))
  print "<p>This page should help you to check whether your
installation is properly configured. Once your installation is running,
you should remove this file or restrict its access, since it could give
details about your setup to anybody.</p>\n";

print "<h2>Base PHP configuration</h2>\n";

# cf. http://php.net/manual/en/ini.php
$phptags = array (
	'register_globals' => 0,
	'file_uploads' => 1,
	'magic_quotes_gpc' => 0,
);

// Get all php.ini values:
$all_inis = ini_get_all();
// Define missing constant to interpret the 'access' field
define('PHP_INI_SYSTEM', 4);
// Cf. http://www.php.net/manual/en/ini.core.php

print "<table border=\"1\" summary=\"PHP configuration\">\n";
print "<tr><th>PHP Tag name</th><th>Local value</th><th>Suggested/Required value</th></tr>\n";
$unset = 0;
ksort($phptags);
foreach ( $phptags as $tag => $goodval ) {
  if ((htmlentities(ini_get($tag)) == htmlentities($goodval))
      or ($goodval==0 and !(bool)ini_get($tag)))
    {
      # OK
      printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",$tag,htmlentities(ini_get($tag)),htmlentities($goodval));
    }
  else if (isset($all_inis[$tag]))
    {
      # Different
      printf ("<tr><td>%s</td><td class=\"different\">%s</td><td>%s",$tag,htmlentities(ini_get($tag)),htmlentities($goodval));
      if ($all_inis[$tag]['access'] > PHP_INI_SYSTEM) {
          echo " (can be set in php.ini, .htaccess or httpd.conf)";
      } else {
          echo " (can be set in php.ini or httpd.conf - but not in .htaccess)";
      }
      echo "</td></tr>\n";
    }
  else
    {
      # Non existing ini value
      printf ("<tr><td>%s</td><td class=\"unset\">Unknown</td><td>%s</td></tr>\n",$tag,htmlentities($goodval));
      $unset = 1;
    }
}

# Check against minimum sizes
$phptags = array (
	'post_max_size' => '3M',
	'upload_max_filesize' => '2M',
);
ksort($phptags);
foreach ( $phptags as $tag => $goodval ) {
  if (return_bytes(ini_get($tag)) >= return_bytes($goodval))
    {
      # OK
      printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",$tag,htmlentities(ini_get($tag)),htmlentities($goodval));
    }
  else if (isset($all_inis[$tag]))
    {
      # Different
      printf ("<tr><td>%s</td><td class=\"different\">%s</td><td>%s</td></tr>\n",$tag,htmlentities(ini_get($tag)),htmlentities($goodval));
    }
  else
    {
      # Non existing ini value
      printf ("<tr><td>%s</td><td class=\"unset\">Unknown*</td><td>%s</td></tr>\n",$tag,htmlentities($goodval));
      $unset = 1;
    }
}
print "</table>\n";
if ($unset)
{
  echo "<blockquote>* This tag was not found at all. It is probably irrelevant to your PHP version so you may ignore this entry.</blockquote>";
}


#==============================================================================
print "<h2>PHP functions</h2>\n";

$phpfunctions = array (
        'mysql_connect' => 'You must install/configure php-mysql ! [REQUIRED]',
        'gettext' => 'You should install/configure php with gettext support '
                         . '! [RECOMMENDED]',
        'ctype_digit' => 'You must have a PHP version supporting ctype '
                         . '(--enable-ctype) ! [REQUIRED]',
        'pam_auth' => 'You must have a PHP version supporting pam_auth '
                      . 'only if you set up authentification via '
                      . 'PAM (kerberos, AFS, etc)',
);

foreach ( $phpfunctions as $func => $comment ) {

	if (function_exists($func)) {
		print "function <strong>".$func."()</strong> exist.<br />\n";
	} else {
		print "function <strong>".$func."()</strong> does not exist. $comment <br />\n";
	}
}

#==============================================================================
print "<h2>Apache environment vars</h2>\n";

$configfile = '/etc/savane/';

if (getenv('SAVANE_CONF'))
  {
    $configfile = getenv('SAVANE_CONF');
    print "SAVANE_CONF configured to ".$configfile."<br />\n";
  }
elseif (getenv('SV_LOCAL_INC_PREFIX'))
  {
    $configfile = getenv('SV_LOCAL_INC_PREFIX');
    print "SV_LOCAL_INC_PREFIX configured to ".$configfile."<br />\n";
  }
else
  print "SAVANE_CONF or SV_LOCAL_INC_PREFIX are not set, "
        . "falling back to default <strong>".$configfile."</strong>) <br />\n";

# Add a trailing slash.
if (!preg_match ('#/$#', $configfile))
  $configfile .= '/';

$configfile .= '.savane.conf.php';

if (is_readable ($configfile))
  print "File <strong>$configfile</strong> exists and is readable.";
else
  print "File <strong>$configfile</strong> does not exist or is not readable!";

print "<h2>Savane configuration:</h2>\n";

if (!is_readable ($configfile))
  print "Since $configfile does not exist or is not readable, "
        . "this part cannot be checked.";
else
{
  include $configfile;
  $variables = array (
	# Name  / required
		      'sys_default_domain',
		      'sys_https_host',
		      'sys_dbhost',
		      'sys_dbname',
		      'sys_dbuser',
		      'sys_dbpasswd',
		      'sys_www_topdir',
		      'sys_url_topdir',
		      'sys_incdir',
		      'sys_name',
		      'sys_unix_group_name',
		      'sys_themedefault',
		      'sys_mail_domain',
		      'sys_mail_admin',
		      'sys_mail_replyto',
		      'sys_upload_max',
		      );

  print "<table border=\"1\">\n";
  print "<tr><th>Conf variable</th><th>Current value</th></tr>\n";
  unset($unset);
  foreach ($variables as $tag) {
    if (isset($GLOBALS[$tag]))
      $value = $GLOBALS[$tag];
    else
      $value = '';
    // Is set
    if ($tag == "sys_dbpasswd")
      $value = "**************";

    printf ("<tr><td>%s</td><td>%s</td></tr>\n", $tag, htmlentities($value));
  }

  print "</table>\n";
  print "Savane uses safe defaults values when variables are not set in the
configuration file.";
}


#=============================================================================
print "<h2>Optional PHP configuration</h2>\n";

print "The following is not required to run Savane but could enhance security
of your production server. Displaying errors is recommended: they may
annoy the user with warnings but allow you to spot and report
potentially harmful bugs (concerns about \"security\" or information
leak are void since this is free software and the source code is
available to all).";

$phptags = array (
	'display_errors' => '1',
	'log_errors' => '1',
	'error_reporting' => E_ALL|E_STRICT,
	'allow_url_fopen' => '0',
	'disable_functions' => 'exec,passthru,popen,shell_exec,system',
);

print "<table border=\"1\">\n";
print "<tr><th>PHP Tag name</th><th>Local value</th><th>Suggested/Required value</th></tr>\n";
$unset = 0;
ksort($phptags);
foreach ( $phptags as $tag => $goodval ) {
  if (htmlentities(ini_get($tag)) == htmlentities($goodval))
    {
      # OK
      printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",$tag,htmlentities(ini_get($tag)),htmlentities($goodval));
    }
  else if (isset($all_inis[$tag]))
    {
      # Different
      printf ("<tr><td>%s</td><td class=\"different\">%s</td><td><code>%s</code>",$tag,htmlentities(ini_get($tag)),htmlentities($goodval));
      if ($all_inis[$tag]['access'] > PHP_INI_SYSTEM) {
          echo " (can be set in php.ini, .htaccess or httpd.conf)";
      } else {
          echo " (can be set in php.ini or httpd.conf - but not in .htaccess)";
      }
      echo "</td></tr>\n";
    }
  else
    {
      # Non existing ini value
      printf ("<tr><td>%s</td><td class=\"unset\">Unknown*</td><td>%s</td></tr>\n",$tag,htmlentities($goodval));
      $unset = 1;
    }
}
print "</table>\n";
if ($unset)
{
  echo "<blockquote>* This tag was not found at all. It is probably irrelevant to your PHP version so you may ignore this entry.</blockquote>";
}


#==============================================================================

print "<h2>That's it!</h2>";
print "</body>\n<html>\n";
