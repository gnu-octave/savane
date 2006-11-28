<?php

if (function_exists("register_globals_off"))
{ register_globals_off(); }

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
print "<style type=\"text/css\">\n";
print "<!--\n";
print ".different { background-color: #ffadad; color: black; }\n";
print ".unset { background-color: #ffdada; color: black; }\n";
print "-->\n";
print "</style>\n";
print "</head>\n\n";
print "<body>\n";


print "<h1>Basic PHP pre-tests for Savane installation</h1>\n";
if (!$inside_siteadmin)
{
  print "<p>This page should help you to check whether your installation is properly configured. Once your installation is running, you should remove this file or restrict its access, since it could give details about your setup to anybody.</p>";
}
 
#==============================================================================
print "<h2>Base PHP configuration:</h2>\n";

# cf. http://php.net/manual/en/ini.php
$phptags = array (
	'register_globals' => 1,
	'file_uploads' => 1,
	'magic_quotes_gpc' => 1,  # not good for perfs, but need until we are
	                          # 100% sure that all forms are clean (using
				  # sane_())
#	'arg_separator.output' => '&amp;', # we don't use it, no need to worry the user
);

# http://php.net/manual/en/ini.core.php#ini.register-long-arrays:
# "This directive became available in PHP 5.0.0 and was dropped in PHP 6.0.0."
if (ereg('^5', phpversion())) {
  $phptags['register_long_arrays'] = 1;
}
$all_inis = ini_get_all();
define('PHP_INI_SYSTEM', 4);

print "<table border=\"1\" summary=\"PHP configuration\">\n";
print "<tr><th>PHP Tag name</th><th>Local value</th><th>Suggested/Required value</th></tr>\n";
unset($unset);
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
	'upload_max_filesize' => '3M',
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
print "<h2>PHP functions:</h2>\n";

$phpfunctions = array (
	'mysql_connect' => 'You must install/configure php-mysql ! [REQUIRED]',
	'gettext' => 'You should install/configure php with gettext support ! [RECOMMENDED]',
	'ctype_digit' => 'You must have a PHP version supporting ctype (--enable-ctype) ! [REQUIRED]',
	'pam_auth' => 'You must have a PHP version supporting pam_auth only if you set up authentification via PAM (kerberos, AFS, etc)',  
);

foreach ( $phpfunctions as $func => $comment ) {

	if (function_exists($func)) {
		print "function <strong>".$func."()</strong> exist.<br />\n";
	} else {
		print "function <strong>".$func."()</strong> does not exist. $comment <br />\n";
	}
}

#==============================================================================
print "<h2>Apache environment vars:</h2>\n";

$configfile = '/etc/savane/';

if ( getenv('SAVANE_CONF') ) {
	$configfile = getenv('SAVANE_CONF');
	print "SAVANE_CONF configured to ".$configfile."<br />\n";
} elseif ( getenv('SV_LOCAL_INC_PREFIX') ) {
	$configfile = getenv('SV_LOCAL_INC_PREFIX');
	print "SV_LOCAL_INC_PREFIX configured to ".$configfile."<br />\n";
} else {
	print "SAVANE_CONF or SV_LOCAL_INC_PREFIX are not set, falling back to default <strong>".$configfile."</strong>) <br />\n";
}
# add a trailing slash
if (!ereg('/$', $configfile))
  $configfile .= '/';

$configfile .= '.savane.conf.php';

if (is_readable($configfile)) {
   print "File <strong>$configfile</strong> exists and is readable.";
} else {
   print "File <strong>$configfile</strong> does not exist or is not readable!";
}

#==============================================================================
print "<h2>Savane configuration:</h2>\n";

if (!is_readable($configfile))
{
  print "Since $configfile does not exist or is not readable, this part cannot be checked.";
}
else
{
  include $configfile;
  $variables = array (
	# Name  / required
		      'sys_default_domain' => 1,
		      'sys_https_host' => 0,
		      'sys_dbhost' => 1,
		      'sys_dbname' => 1,
		      'sys_dbuser' => 1,
		      'sys_dbpasswd' => 1,
		      'sys_www_topdir' => 1,
		      'sys_url_topdir' => 1,
		      'sys_incdir' => 1,
		      'sys_name' => 1,
		      'sys_unix_group_name' => 1,
		      'sys_themedefault' => 1,
		      'sys_mail_domain' => 1,
		      'sys_mail_admin' => 1,
		      'sys_mail_replyto' => 1,
		      'sys_upload_max' => 0,
		      );

  print "<table border=\"1\">\n";
  print "<tr><th>Conf variable</th><th>Current value</th><th>Is required?</th></tr>\n";
  unset($unset);
  foreach ( $variables as $tag => $required ) {
    if (!$required || htmlentities($GLOBALS[$tag]))
      {
        # Is set
	$value = $GLOBALS[$tag];
	if ($tag == "sys_dbpasswd")
	  { $value = "**************"; }

	printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",$tag,htmlentities($value),$required);
      }
    else
      {
        # Is not set, and should be set
	printf ("<tr><td>%s</td><td class=\"unset\">%s</td><td>%s</td></tr>\n",$tag," ",$required);
      }
  }

  print "</table>\n";
}


#=============================================================================
print "<h2>Securing PHP configuration:</h2>\n";

print "The following is not required to run Savane but could enhance security of your production server. Some of these makes harder to debug an installation and, as such, should be avoided on a test installation, or if your installation is not working.";

$phptags = array (
	'display_errors' => '0',
	'log_errors' => '1',
	'error_reporting' => E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE,	
	'allow_url_fopen' => '0',
	'disable_functions' => 'exec, passthru, popen, shell_exec, system',
);

print "<table border=\"1\">\n";
print "<tr><th>PHP Tag name</th><th>Local value</th><th>Suggested/Required value</th></tr>\n";
unset($unset);
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
