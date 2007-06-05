<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: title.php 4975 2005-11-15 17:25:35Z yeupou $
#
#  Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

#input_is_safe();
#mysql_is_safe();

# The point of this library is to reach the point where Savane will 
# no longer needs register globals set to on.
#
# This library will:
#            - do sanitization checks
#            - provide functions to access user input in a sane way

// Beuc: we only need sane_import. Check doc/devel/CLEANUP where I
// explain this cleaner approach.


###########################################################
# Sanitization checks
###########################################################

# Unset variables that users are not allowed to set in any cases
unset($feedback_html);

# Keep only numerical characters in the item_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off())
if (isset($item_id) && !ctype_digit($item_id))
{
  preg_match("/(\d+)/", $item_id, $match);
  $item_id = $match[0];
}

# Keep only numerical characters in the export_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off())
if (isset($export_id) && !ctype_digit($export_id))
{
  preg_match("/(\d+)/", $export_id, $match);
  $export_id = $match[0];
}


# Keep only numerical characters in the group_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off())
if (isset($group_id) && !ctype_digit($group_id))
{
  preg_match("/(\d+)/", $group_id, $match);
  $group_id = $match[0];
}

# Keep only numerical characters in the user_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off())
if (isset($user_id) && !ctype_digit($user_id) && !is_array($user_id))
{
  preg_match("/(\d+)/", $user_id, $match);
  $user_id = $match[0];
}



###########################################################
# Functions to access user input
###########################################################

// Check the existence of a series of input parameters, then return an
// array suitable for extract()
// Ex: extract(sane_import('post',
//       array('insert_group_name', 'rand_hash',
//             'form_full_name', 'form_unix_name')));
// Note: there's another import function to clean-up in trackers/general.php
function sane_import($method, $names) {
  if ($method == 'get')
    $input_array =& $_GET;
  else if ($method == 'post')
    $input_array =& $_POST;
  else if ($method == 'cookie')
    $input_array =& $_COOKIE;
  else if ($method == 'files')
    $input_array =& $_FILES;
  else
    $input_array =& $_REQUEST;

  $values = array();
  foreach ($names as $input_name) {
    if (isset($input_array[$input_name]))
      {
	if (get_magic_quotes_gpc())
	  {
	    $values[$input_name] = sane_nomagic($input_array[$input_name], $method);
	  } else {
	    $values[$input_name] = $input_array[$input_name];
	  }
      }
    else
      {
	$values[$input_name] = null;
      }
  }

  return $values;
}

// Cancel the effect of magic_quotes_gpc
// Technically is would be more efficient to edit arrays in place but
// PHP seems to suck at that, syntax-wise
function sane_nomagic($arg, $method) {
  if (is_array($arg))
    { // array
      $arr =& $arg;
      $arr_nomagic = array();
      if ($method == 'files' and array_key_exists('tmp_name', $_arg))
	{ // this is a file entry
	  // convert only a few entry - especially _not_ 'tmp_name'
	  $arr_nomagic['name']  = sane_nomagic($arr['name'], $method);
	  $arr_nomagic['type']  = sane_nomagic($arr['type'], $method);
	  $arr_nomagic['size']  = $arr['size'];
	  $arr_nomagic['error'] = $arr['error'];
	  $arr_nomagic['tmp_name'] = $arr['tmp_name'];
	}
      else
	{ // recursively convert the array
	  // (can be either an array of files or a regular array of values)
	  $arr_nomagic = array();
	  foreach ($arr as $key => $val)
	    {
	      $ret_val[$key] = sane_nomagic($val, $method);
	    }
	}
      return $arr_nomagic;
    }
  else
    { // scalar
      return stripslashes($arg);
    }
}

# Backward security function. This will sanitize input already passed via
# register globals.
# 
# In theory, this function should "disappear" from the code and be replaced by
# sane_XXX functions.
#
# This function should be used whenever user input is used:
#        - get
#        - post
#        - cookies
# This will escape the strings appropriately.

// Beuc: I'm using another, saner approach with sane_import, where the
// string is unquoted so that we manipulate the actual values (with
// correct results for str_len, etc.). The escaping is done in
// SQL-related functions, which is a good thing to do anyway. As the
// dovecot guys put it (http://dovecot.org/doc/securecoding.txt),
// "Don't rely on input validation. Maybe you missed something. Maybe
// someone calls your function somewhere else where you didn't
// originally intend it.  Maybe someone makes the input validation
// less restrictive for some reason.  Point is, it's not an excuse to
// cause a security hole just because input wasn't what you expected
// it to be.". Plus, addslashes() is not meant to escape SQL strings,
// mysql_real_escape_string() is. Short: don't use that function.
function safeinput ($string)
{
  # If magic_quotes is on, count on it to escape data
  if (get_magic_quotes_gpc()) 
    {
      return $string;
    } 

  return addslashes($string);
}

# Function to obtain user input that come from undefined method.
# This should be used only where user can legitimately send data by
# different methods.
# (this is why it is called sane_all, to avoid having it used everywhere)
# This does not take uploads depending on PHP version, so use sane_upload()
# instead, if necessary.
function sane_all($varname)
{
  if (sane_isset($varname))
    return safeinput($_REQUEST[$varname]);
  else
    return '';
}

# Function to obtain user input submitted as url args
# (like thispage.php?arg=userinput)
function sane_get($varname) 
{
  if (isset($_GET[$varname]))
    return safeinput($_GET[$varname]);
  else
    return '';
}

# Function to obtain user input submitted while posting a form
function sane_post($varname) 
{
  if (isset($_POST[$varname]))
    return safeinput($_POST[$varname]);
  else
    return '';
}

# Function to obtain user input submitted in a cookie
function sane_cookie($varname) 
{
  if (isset($_COOKIE[$varname]))
    return safeinput($_COOKIE[$varname]);
  else
    return '';
}

# Does an isset. Not really necessary, just for cohesion sake
function sane_isset($varname)
{
  return isset($_REQUEST[$varname]);
}


# Function to obtain info related to a file upload
function sane_upload($varname, $subvarname=false) 
{
  if (!$subvarname)
    { return safeinput($_FILES[$varname]); }

  return  safeinput($_FILES[$varname][$subvarname]);
}

# Function to set a variable in both $_REQUEST and global.
# The global may be deleted by a call to register_globals_off(),
# so the $_REQUEST will remain and should be safe
# (this function should be used only to set safe values! Normally
# it should be used only in include/ like pre.php)
function sane_set($varname, $value)
{
  $GLOBALS[$varname] = $value;
  $_REQUEST[$varname] = $value;
}


# Noop function to mark a page as input-sanitized.
# Warning: MySQL calls are not necessarily secure.
function register_globals_off()
{
  # This is unsecure: you can switch off existing globals
  # - unless that's the very first thing you do in the script
  # - and it's not always the case
#  foreach ($_REQUEST as $key => $value)
#    { 
#      unset($GLOBALS[$key]); 
#    }
}      

# Tag: mysql queries are safe here
#function mysql_is_safe() {
#}
# Tag: input is safe new/Beuc-style (no slashes)
#function input_is_safe() {
#}
# Those tags are used by devel/sv_check_security.pl
