<?php
# Check your configuration against recommended values.
#
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007 Sylvain Beucler
# Copyright (C) 2018, 2019 Ineiev
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
  switch($last)
    {
      # Fall through all cases.
      case 'g': # The 'G' modifier is available since PHP 5.1.0.
        $val *= 1024;
      case 'm':
        $val *= 1024;
      case 'k':
        $val *= 1024;
    }

  return $val;
}

function test_gpg()
{
  print "\n<h2>GnuPG</h2>\n\n";

  print "<dl><dt>GPG command</dt>\n<dd><code>" . $GLOBALS['sys_gpg_name']
        . "</code></dd>\n";

  $d_spec = array (0 => array("pipe", "r"), 1 => array("pipe", "w"),
                   2 => array("pipe", "w"));

  $gpg_proc = proc_open ("'" . $GLOBALS['sys_gpg_name'] . "' --version",
                         $d_spec, $pipes, NULL, $_ENV);
  if ($gpg_proc === false)
    {
      print "</dl>\n\n<p><strong>Can't run GPG.</strong></p>\n";
      return;
    }
  fclose ($pipes[0]);
  $gpg_output = stream_get_contents ($pipes[1]);
  $gpg_stderr = stream_get_contents($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  $gpg_result = proc_close($gpg_proc);
  $dd_pre = "<dd style='border: thin dashed black; border-right: none'>"
            ."<pre style='padding-left: 1em'>\n";
  print "<dt><code>--version</code> output</dt>\n";
  print $dd_pre . htmlentities ($gpg_output) . "</pre></dd>\n";
  print "<dt>Exit code</dt><dd><code>" . $gpg_result . "</code></dd>\n";
  print "<dt><code>stderr</code> output</dt>\n";
  print $dd_pre . htmlentities ($gpg_stderr) . "</pre></dd>\n";
  print "</dl>\n";
}

print "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"
    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n\n";

print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en_US\">\n"
. "<head>\n"
. "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n"
. "<title>Basic configuration tests</title>\n"
. "<link rel=\"stylesheet\" type=\"text/css\" "
. "href=\"/css/internal/testconfig.css\" />\n"
. "</head>\n\n"
. "<body>\n";

print "<h1>Basic pre-tests for Savane installation</h1>\n\n";
if (empty($inside_siteadmin))
  print "<p>This page should help you to check whether your
installation is properly configured. It shouldn't display any sensitive
information, since it could give details about your setup to anybody.</p>\n";

print "\n<h2>Base PHP configuration</h2>\n\n";

print "<p>PHP version: " . phpversion() . "</p>\n";

# cf. http://php.net/manual/en/ini.php
$phptags = array (
        'register_globals' => 0,
        'file_uploads' => 1,
        'magic_quotes_gpc' => 0,
);

# Get all php.ini values.
$all_inis = ini_get_all();
# Define missing constant to interpret the 'access' field.
define('PHP_INI_SYSTEM', 4);
# Cf. http://www.php.net/manual/en/ini.core.php

print "\n<table border=\"1\" summary=\"PHP configuration\">\n";
print "<tr><th>PHP Tag name</th><th>Local value</th>"
    . "<th>Suggested/Required value</th></tr>\n";
$unset = 0;
ksort($phptags);
foreach ($phptags as $tag => $goodval)
  {
    if ((htmlentities(ini_get($tag)) == htmlentities($goodval))
        or ($goodval==0 and !(bool)ini_get($tag)))
      printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",
              $tag, htmlentities(ini_get($tag)), htmlentities($goodval));
    elseif (isset($all_inis[$tag]))
      {
        printf ("<tr><td>%s</td><td class=\"different\">%s</td><td>%s",
                $tag, htmlentities(ini_get($tag)), htmlentities($goodval));
        if ($all_inis[$tag]['access'] > PHP_INI_SYSTEM)
          echo " (can be set in php.ini, .htaccess or httpd.conf)";
        else
          echo " (can be set in php.ini or httpd.conf - but not in .htaccess)";
        echo "</td></tr>\n";
      }
    else
      {
        # non-existing ini value
        printf ("<tr><td>%s</td><td class=\"unset\">Unknown</td>"
                . "<td>%s</td></tr>\n",
                $tag, htmlentities($goodval));
        $unset = 1;
      }
  }

# Check against minimum sizes.
$phptags = array ('post_max_size' => '3M',
                  'upload_max_filesize' => '2M');
ksort($phptags);
foreach ($phptags as $tag => $goodval)
  {
    if (return_bytes(ini_get($tag)) >= return_bytes($goodval))
      printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",
              $tag, htmlentities(ini_get($tag)), htmlentities($goodval));
    elseif (isset($all_inis[$tag]))
      printf ("<tr><td>%s</td><td class=\"different\">%s</td>"
              . "<td>%s</td></tr>\n",
              $tag, htmlentities(ini_get($tag)), htmlentities($goodval));
    else
      {
        printf ("<tr><td>%s</td><td class=\"unset\">Unknown*</td>"
                . "<td>%s</td></tr>\n",
                $tag,htmlentities($goodval));
        $unset = 1;
      }
  }
print "</table>\n\n";
if ($unset)
  echo "<blockquote>* This tag was not found at all. It is probably "
       . "irrelevant to your PHP version so you may ignore this "
       . "entry.</blockquote>\n";

print "\n<h2>PHP functions</h2>\n\n";

$phpfunctions = array (
        'mysqli_connect|mysql_connect' =>
          'You must install/configure php-mysql ! [REQUIRED]',
        'gettext' => 'You should install/configure php with gettext support '
                         . '! [RECOMMENDED]',
        'ctype_digit' => 'You must have a PHP version supporting ctype '
                         . '(--enable-ctype) ! [REQUIRED]',
        'pam_auth' => 'You must have a PHP version supporting pam_auth '
                      . 'only if you set up authentification via '
                      . 'PAM (kerberos, AFS, etc)');

print "<p>";
foreach ($phpfunctions as $func => $comment)
  {
    $funcs = explode ("|", $func);
    $have_func = false;
    foreach ($funcs as $i => $f)
      if (function_exists ($f))
        {
          $have_func = true;
          break;
        }
    if ($have_func)
      print "function <strong>" . $f . "</strong> exists.<br />\n";
    else
      print "function <strong>" . $func
            . "</strong> not found. <em>$comment</em> <br />\n";
  }
print "</p>\n";

print "\n<h2>Apache environment vars</h2>\n\n";
$configfile = '/etc/savane/';

print "<p>";
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
print "</p>\n";

print "\n<h2>Savane configuration:</h2>\n\n";

if (!is_readable ($configfile))
  print "Since $configfile does not exist or is not readable, "
        . "this part cannot be checked.";
else
  {
    include $configfile;
    $variables = array (# Name  / required
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
    foreach ($variables as $tag)
      {
        if (isset($GLOBALS[$tag]))
          $value = $GLOBALS[$tag];
        else
          $value = '';
        if ($tag == "sys_dbpasswd")
          $value = "**************";

        printf ("<tr><td>%s</td><td>%s</td></tr>\n", $tag, htmlentities($value));
      }
    if (!isset ($GLOBALS['sys_debug_on']))
      $GLOBALS['sys_debug_on'] = false;

    print "</table>\n";
    print "<p>Savane uses safe defaults values when variables are not set
in the configuration file.</p>\n";

    print "\n<h2>MySQL configuration</h2>\n\n";
    require_once ("include/utils.php");
    require_once ("include/database.php");
    if (!db_connect ())
      print "<blockquote>Can't connect to database.</blockquote>\n";
    else
      {
        # When sql_mode contains 'ONLY_FULL_GROUP_BY', queries like
        # "SELECT groups.group_name,"
        # . "groups.group_id,"
        # . "groups.unix_group_name,"
        # ...
        # . "GROUP BY groups.unix_group_name "
        # . "ORDER BY groups.unix_group_name"
        # used e.g. in my/groups.php result in an error.
        #
        # Since MySQL 5.7, this is default. We could use ANY_VALUE ()
        # to workaround this, but it is only introduced in 5.7,
        # so won't work with older MySQLs.
        $mysql_params = array ('@@GLOBAL.version' => NULL,
                               '@@GLOBAL.sql_mode' => NULL,
                               '@@SESSION.sql_mode' =>
                "<em>This should</em> <strong>not</strong> <em>include</em> "
                . "<code>ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES</code><em>.</em>");
        $mysql_highlight = array ('@@GLOBAL.sql_mode' =>
                                  'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES',
                                  '@@SESSION.sql_mode' =>
                                  'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES');
        print "<dl>\n";
        foreach ($mysql_params as $param => $comment)
          {
            $result = db_query ('SELECT ' . $param);
            $value = db_result ($result, 0, $param);
            if (isset ($mysql_highlight[$param]))
              {
                $vals = explode (",", $mysql_highlight[$param]);
                foreach ($vals as $i => $v)
                  $value = str_replace ($v, '<strong>' . $v . '</strong>',
                                        $value);
              }
            print '<dt>' . $param . "</dt>\n<dd>'" . $value . "'";
            if ($comment !== NULL)
              print " $comment";
            print "</dd>\n";
          }
        print "</dl>\n";
      } # db_connect ()
    test_gpg ();
  } # is_readable ($configfile)

print "\n<h2>Optional PHP configuration</h2>\n\n";

print "<p>The following is not required to run Savane but could enhance security
of your production server. Displaying errors is recommended: they may
annoy the user with warnings but allow you to spot and report
potentially harmful bugs (concerns about &ldquo;security&rdquo; or information
leak are void since this is free software and the source code is
available to all).</p>\n";

$phptags = array (
        'display_errors' => '1',
        'log_errors' => '1',
        'error_reporting' => E_ALL|E_STRICT,
        'allow_url_fopen' => '0',
        'disable_functions' => 'exec,passthru,popen,shell_exec,system',
);

print "\n<table border=\"1\">\n"
. "<tr><th>PHP Tag name</th><th>Local value</th>"
. "<th>Suggested/Required value</th></tr>\n";
$unset = 0;
ksort($phptags);
foreach ($phptags as $tag => $goodval)
  {
    if (htmlentities(ini_get($tag)) == htmlentities($goodval))
      printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",
              $tag, htmlentities(ini_get($tag)), htmlentities($goodval));
    elseif (isset($all_inis[$tag]))
      {
        printf ("<tr><td>%s</td><td class=\"different\">%s</td>"
                . "<td><code>%s</code>",
                $tag, htmlentities(ini_get($tag)) ,htmlentities($goodval));
        if ($all_inis[$tag]['access'] > PHP_INI_SYSTEM)
          echo " (can be set in php.ini, .htaccess or httpd.conf)";
        else
          echo " (can be set in php.ini or httpd.conf - but not in .htaccess)";
        echo "</td></tr>\n";
      }
    else
      {
        # non-existing ini value
        printf ("<tr><td>%s</td><td class=\"unset\">Unknown*</td>"
                . "<td>%s</td></tr>\n",
                $tag, htmlentities($goodval));
        $unset = 1;
      }
  }
print "</table>\n\n";
if ($unset)
  echo "<blockquote>* This tag was not found at all. It is probably irrelevant "
       . "to your PHP version so you may ignore this entry.</blockquote>\n\n";

print "\n<h2>That's it!</h2>\n";
print "</body>\n<html>\n";
