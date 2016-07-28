<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


# Cleans our input values, centeral place to filter for XSS is here.
# This should be called by any function that touches $_GET, $_POST,
# $_REQUEST, $_COOOKIE or any other data that comes from the user.
function sane_clean($values) {
  # Unset variables that users are not allowed to set in any cases
  unset($values['feedback_html']);

  # Keep only numerical characters in the item_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off())
  if (isset($values['item_id']) && !ctype_digit($values['item_id']))
  {
    preg_match("/(\d+)/", $values['item_id'], $match);
    if(isset($matches))
    {
      $values['item_id'] = $match[0];
    }
    else
    {
      unset($values['item_id']);
    }
  }

  # Keep only numerical characters in the export_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off())
  if (isset($values['export_id']) && !ctype_digit($values['export_id']))
  {
    preg_match("/(\d+)/", $values['export_id'], $match);
    if(isset($matches))
    {
      $values['export_id'] = $match[0];
    }
    else
    {
      unset($values['export_id']);
    }
  }


  # Keep only numerical characters in the group_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off())
  if (isset($values['group_id']) && !ctype_digit($values['group_id']))
  {
    preg_match("/(\d+)/", $values['group_id'], $match);
    if(isset($matches))
    {
      $values['group_id'] = $match[0];
    }
    else
    {
      unset($values['group_id']);
    }
  }

  # Keep only numerical characters in the user_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off())
  if (isset($values['userid']) && !ctype_digit($values['userid']) && !is_array($values['userid']))
  {
    preg_match("/(\d+)/", $values['userid'], $match);
    if(isset($matches))
    {
      $values['userid'] = $match[0];
    }
    else
    {
      unset($values['userid']);
    }
  }

  return $values;
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
        $values[$input_name] = $input_array[$input_name];
      }
    else
      {
	$values[$input_name] = null;
      }
  }

  return sane_clean($values);
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

# Function to obtain user input that come from undefined method.
# This should be used only where user can legitimately send data by
# different methods.
# (this is why it is called sane_all, to avoid having it used everywhere)
# This does not take uploads depending on PHP version, so use sane_upload()
# instead, if necessary.
function sane_all($varname)
{
  if (sane_isset($varname))
    return $_REQUEST[$varname];
  else
    return '';
}

# Function to obtain user input submitted as url args
# (like thispage.php?arg=userinput)
function sane_get($varname) 
{
  if (isset($_GET[$varname]))
    return $_GET[$varname];
  else
    return '';
}

# Function to obtain user input submitted while posting a form
function sane_post($varname) 
{
  if (isset($_POST[$varname]))
    return $_POST[$varname];
  else
    return '';
}

# Function to obtain user input submitted in a cookie
function sane_cookie($varname) 
{
  if (isset($_COOKIE[$varname]))
    return $_COOKIE[$varname];
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
    return $_FILES[$varname];

  return $_FILES[$varname][$subvarname];
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
