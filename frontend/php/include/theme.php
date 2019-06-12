<?php
# Theme functions.
#
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2019 Ineiev
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

# theme value is fetched by getting the cookie. But we keep in the
# database the setting, so someone using another computer can easily
# remember the theme he previously chose.

require_once(dirname(__FILE__).'/utils.php');

# Jump to the next theme available and set cookie appropriately.
function theme_rotate_jump()
{
  extract(sane_import('cookie', array('SV_THEME_ROTATE_NUMERIC')));
  $num = intval($SV_THEME_ROTATE_NUMERIC);

  utils_get_content("forbidden_theme");
  $theme = theme_list();

  $num++;

  # If the num is a value superior of the number of themes
  # we reset to 0.
  if ($num == count($theme))
    $num = "0";

  # Keep in mind the new number.
  utils_setcookie('SV_THEME_ROTATE_NUMERIC', $num, time() + 60*60*24*365);

  # Associate this number with a theme.
  utils_setcookie('SV_THEME_ROTATE', $theme[$num], time() + 60*60*24);
}

# Return an array with all the themes, but not the special case "rotate"
# and "random".
function theme_list ()
{
  utils_get_content("forbidden_theme");

  # Feed the array.
  $theme = array();
  $dir = opendir($GLOBALS['sys_www_topdir']."/css/");
  while ($file = readdir($dir))
    {
      # Ignore symlinks.
      if (is_link($GLOBALS['sys_www_topdir']."/css/$file"))
	continue;

      # Take only correct css files.
      if (!preg_match("/^(.*)\.css$/", $file, $matches))
	continue;

      # base.css and printer.css are always ignored
      # (as of nov 2006, there are in the subdirectory internal, so this
      # is only here for backward compat).
      if ($matches[1] == "base" || $matches[1] == "printer"
          || $matches[1] == "msie-dirtyhacks")
	continue;

      # Forbidden themes are also ignored.
      if (preg_match($GLOBALS['forbid_theme_regexp'], strtolower($matches[1])))
	continue;

      $theme[] = $matches[1];
    }
  closedir($dir);

  # Sort themes - case insensitive.
  natcasesort($theme);

  # No result? Return only the default theme.
  # (If there were no result, there is a problem anyway somewhere in the
  # installation.)
  if (!count($theme))
    $theme[] = $GLOBALS['sys_themedefault'];
  return $theme;
}

# Check whether a theme follows latest GUIDELINES.
function theme_guidelines_check ($theme)
{
  # Get from the README the latest GUIDELINES number.
  preg_match("/VERSION: (.*)/",
             utils_read_file($GLOBALS['sys_www_topdir']."/css/README"),
             $latest);
  # Get from the css the current GUIDELINES number.
  preg_match("/\/\* GUIDELINES VERSION FOLLOWED: (.*) \*\//",
             utils_read_file($GLOBALS['sys_www_topdir']."/css/".$theme.".css"),
             $current);

  if ($latest[1] != $current[1])
    return false;
  return true;
}

# TODO: move to init.php

# THEME SELECTION
# First check if the printer mode is asked. If not, proceed to the usual
# theme selection.
extract(sane_import('request', array('printer')));
if ($printer == 1)
  {
    define('SV_THEME', 'printer');
    define('PRINTER', 1);
    return true;
  }

if (isset($_COOKIE['SV_THEME']))
{
  # The user selected a theme.
  if ($_COOKIE['SV_THEME'] == 'random')
    {
      # The user selected random theme.
      # We set randomly a theme and a cookie for a day.
      if (isset($_COOKIE['SV_THEME_RANDOM']))
	{
	  if (!defined('SV_THEME'))
	    define('SV_THEME', $_COOKIE['SV_THEME_RANDOM']);
	}
      else
	{
	  $theme = theme_list();
	  mt_srand ((double)microtime()*1000000);
	  $num = mt_rand(0,count($theme)-1);
	  $random_theme = $theme[$num];
	  utils_setcookie('SV_THEME_RANDOM', $random_theme, time() + 60*60*24);
	  if (!defined('SV_THEME'))
	    define('SV_THEME', $random_theme);
	}
    }
  elseif ($_COOKIE['SV_THEME'] == 'rotate')
    {
      # The user want a rotation between themes.
      if (isset($_COOKIE['SV_THEME_ROTATE']))
	{
	  if (!defined('SV_THEME'))
	    define('SV_THEME', $_COOKIE['SV_THEME_ROTATE']);
	}
      else
	{
	  $theme = theme_list();

	  # We get a number and set a cookie with this number.
	  # If this number exist, +1 to its value.
	  if (!isset($_COOKIE['SV_THEME_ROTATE_NUMERIC']))
	    $num = '0';
	  else
	    {
	      $num = $_COOKIE['SV_THEME_ROTATE_NUMERIC']+1;
	      # If the num is a value superior of the number of themes
	      # we reset to 0.
	      if ($num == count($theme))
		$num = '0';
	    }
	  utils_setcookie('SV_THEME_ROTATE_NUMERIC', $num,
                          time() + 60*60*24*365);
	  # We associate this number with a theme.
	  $rotate_theme = $theme[$num];
	  utils_setcookie('SV_THEME_ROTATE', $rotate_theme, time() + 60*60*24);
	  if (!defined('SV_THEME'))
	    define('SV_THEME', $rotate_theme);
	}
    }
  else
    {
      # The user picked a particular theme.
      $cookie_theme = $_COOKIE['SV_THEME'];

      # Look for invalid / outdated cookies.
      # TODO; stop using a constant for SV_THEME.
      if (!file_exists($GLOBALS['sys_www_topdir'] . "/css/"
                       . $cookie_theme . ".css"))
	{
	  if (!defined('SV_THEME')) # defined by the /my/admin/ page
	    define('SV_THEME', $GLOBALS['sys_themedefault']);
	  utils_setcookie('SV_THEME', SV_THEME, time() + 60*60*24*365);
	}
      else
	{
	  if (!defined('SV_THEME')) # defined by the /my/admin/ page
	    define('SV_THEME', $cookie_theme);
	}
    }
}
else
  {
    # No theme was defined, we use the default one, unless already
    # manual set (i.e. my/admin/index.php).
    if (!defined('SV_THEME'))
      define('SV_THEME', $GLOBALS['sys_themedefault']);
  }
?>
