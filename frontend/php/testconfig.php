<?php
# Check your configuration against recommended values.
#
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007 Sylvain Beucler
# Copyright (C) 2018, 2019, 2022 Ineiev
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

include ("include/ac_config.php");

function return_bytes ($v)
{
  $val = trim ($v);
  if (is_int ($val))
    return $val;
  $last = strtolower (substr ($val, -1));
  $val = substr ($val, 0, -1);
  if (!is_int ($val) || !in_array ($last, ['g', 'm', 'k']))
    return ">$v<";
  switch ($last)
    {
      # Fall through all cases.
      case 'g':
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
  print "\n<h3>GnuPG</h3>\n\n";

  if (!isset ($GLOBALS['sys_gpg_name']))
    {
      print "<p><strong>GnuPG is not configured.</strong></p>\n";
      return;
    }

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

function test_cgitrepos()
{
  if (!isset ($GLOBALS['sys_etc_dir']))
    {
      print '<strong>no $sys_etc_dir set</strong>';
      return;
    }
  if (!file_exists ($GLOBALS['sys_etc_dir']))
    {
      print '<strong>no $sys_etc_dir directory exists</strong>';
      return;
    }
  $fname = $GLOBALS['sys_etc_dir'] . '/cgitrepos';
  if (!file_exists ($fname))
    {
      print '<strong>no cgitrepos file exists in $sys_etc_dir</strong>';
      return;
    }
  if (!is_readable ($fname))
    {
      print '<strong>cgitrepos in $sys_etc_dir is not readable</strong>';
      return;
    }
  $mtime = time () - filemtime ($fname);
  if ($mtime > 3600)
    {
      print ('<strong>cgitrepos has not been updated for ');
      if ($mtime < 100)
        printf ('%.0f minutes</strong>', $mtime / 60);
      else if ($mtime < 24 * 3600)
        printf ('%.0f hours</strong>',  $mtime / 3600);
      else
        printf ('%.1f days</strong>',  $mtime / 24. / 3600);
      return;
    }
  print 'OK';
}

function test_sys_upload_dir ()
{
  $path = utils_make_upload_file ("test.txt", $errors);
  if ($path === null)
    {
      print "<b>can't make file:</b> $errors";
      return;
    }
  $error_handler = function ($errno, $errstr)
  {
    print "<b>unlink failed:</b> $errstr";
  };
  $old_handler = set_error_handler ($error_handler, E_WARNING);
  $res = unlink ($path);
  set_error_handler ($old_handler, E_WARNING);
  if ($res)
    print 'OK';
}

function test_captcha ()
{
  global $sys_securimagedir;
  $default_dir = '/usr/src/securimage';

  print "<h2>Captcha</h2>\n\n";
  if (empty ($sys_securimagedir))
    {
      print "<p><strong>sys_securimagedir isn't set.</strong></p>\n";
      print "<p>Falling back to default, $default_dir</p>\n";
      $sys_securimagedir = $default_dir;
    }
  else
    print "<p><b>sys_securimagedir</b> is set to $sys_securimagedir</p>\n";
  if (!is_dir ($sys_securimagedir))
    {
      print "<p><strong>No $sys_securimagedir directory found.</strong></p>\n";
      return;
    }
  $f = "$sys_securimagedir/securimage.php";
  if (!is_file ($f))
    {
      print "<p><strong>No $f file found.</strong></p>\n";
      return;
    }
  print "<p>Sample image:</p>\n"
    . "<p><img id='captcha' src='/captcha.php' alt='CAPTCHA' /></p>";
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

print "\n<h2>Basic PHP configuration</h2>\n\n";

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
        'mysqli_connect' =>
          'You must install/configure php-mysqli ! [REQUIRED]',
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
      print "function <strong>$f</strong> exists.<br />\n";
    else
      print
        "function <strong>$func</strong> not found. <em>$comment</em><br />\n";
  }

print "</p>\n\n<h2>Apache environment vars</h2>\n\n<p>";
if (getenv ('SAVANE_CONF'))
  {
    $conf_var = getenv ('SAVANE_CONF');
    print "SAVANE_CONF configured to $conf_var<br />\n";
  }
if (getenv('SV_LOCAL_INC_PREFIX'))
  {
    $conf_var = getenv ('SV_LOCAL_INC_PREFIX');
    print "SV_LOCAL_INC_PREFIX configured to $conf_var<br />\n";
  }
print "</p>\n\n<h2>Savane configuration:</h2>\n\n<p>";

if (empty ($sys_conf_file))
  print "<strong>sys_conf_file not set!</strong>\n";
else
  {
    print "sys_conf_file is set to $sys_conf_file<br />\n";
    print "File <strong>$sys_conf_file</strong> ";

    if (is_readable ($sys_conf_file))
      print "exists and is readable.";
    else
      print "does not exist or is not readable!";
  }
print "</p>\n";

if (!is_readable ($sys_conf_file))
  print "Since $sys_conf_file does not exist or is not readable, "
        . "this part cannot be checked.";
else
  {
    include $sys_conf_file;
    $variables = array (# Name  / required
                      'sys_default_domain',
                      'sys_https_host',
                      'sys_file_domain',
                      'sys_dbhost',
                      'sys_dbname',
                      'sys_dbuser',
                      'sys_dbpasswd',
                      'sys_www_topdir',
                      'sys_url_topdir',
                      'sys_etc_dir',
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
        if (isset ($GLOBALS[$tag]))
          $value = htmlentities ($GLOBALS[$tag]);
        else
          $value = '<strong>unset</strong>';
        if ($tag == "sys_dbpasswd")
          $value = "**************";

        printf ("<tr><td>%s</td><td>%s</td></tr>\n", $tag, $value);
      }
    if (!isset ($GLOBALS['sys_debug_on']))
      $GLOBALS['sys_debug_on'] = false;

    print "</table>\n";
    print "<p>Savane uses safe defaults values when variables are not set "
      . "in the configuration file.</p>\n";
    print "<p><img src='/file?file_id=test.png' alt='Test image'/></p>\n";
    test_captcha ();

    print "\n<h2>MySQL configuration</h2>\n\n";
    require_once ("include/utils.php");
    require_once ("include/database.php");
    utils_set_csp_headers ();
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
            print '<dt>' . $param . "</dt><dd>'" . $value . "'";
            if ($comment !== NULL)
              print "\n$comment";
            print "</dd>\n";
          }
        print "</dl>\n";
      } # db_connect ()
    print "\n<h2>Other tests</h2>\n\n";
    print "<table border=\"1\">\n";
    print "<tr><th>Test</th><th>Result</th></tr>\n";
    print "<tr id='cgitrepos'><td>cgitrepos</td><td>\n";
    test_cgitrepos ();
    print "</td></tr>\n";
    print "<tr id='sys-upload-dir'><td>sys_upload_dir writability</td><td>\n";
    test_sys_upload_dir ();
    print "</td></tr>\n";
    print "</table>\n";
    test_gpg ();
  } # is_readable ($sys_conf_file)

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
