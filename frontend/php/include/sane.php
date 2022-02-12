<?php
# Sanitize input
#
# Copyright (C) 2005-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2022 Ineiev
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

# Beuc: we only need sane_import. Check doc/devel/CLEANUP where I
# explain this cleaner approach.

# Sanitization checks.

# Unset variables that users are not allowed to set in any cases.
unset($feedback_html);

# Keep only numerical characters in the item_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off()).
if (isset($item_id) && !ctype_digit($item_id))
  {
    preg_match("/(\d+)/", $item_id, $match);
    $item_id = $match[0];
  }

# Keep only numerical characters in the export_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off()).
if (isset($export_id) && !ctype_digit($export_id))
  {
    preg_match("/(\d+)/", $export_id, $match);
    $export_id = $match[0];
  }

# Keep only numerical characters in the group_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off()).
if (isset($group_id) && !ctype_digit($group_id))
  {
    preg_match("/(\d+)/", $group_id, $match);
    $group_id = $match[0];
  }

# Keep only numerical characters in the user_id
# (Set both the global and the _REQUEST vars, because the global may be
# unregistered by register_globals_off()).
if (isset($user_id) && !ctype_digit($user_id) && !is_array($user_id))
  {
    preg_match("/(\d+)/", $user_id, $match);
    $user_id = $match[0];
  }

# Clean our input values, centeral place to filter for XSS is here.
# This should be called by any function that touches $_GET, $_POST,
# $_REQUEST, $_COOOKIE or any other data that comes from the user.
function sane_clean($values)
{
  # Unset variables that users are not allowed to set in any cases
  unset($values['feedback_html']);

  # Keep only numerical characters in the item_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off()).
  if (isset($values['item_id']) && !ctype_digit($values['item_id']))
    {
      preg_match("/(\d+)/", $values['item_id'], $match);
      if(isset($matches))
        $values['item_id'] = $match[0];
      else
        unset($values['item_id']);
    }

  # Keep only numerical characters in the export_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off()).
  if (isset($values['export_id']) && !ctype_digit($values['export_id']))
    {
      preg_match("/(\d+)/", $values['export_id'], $match);
      if(isset($matches))
        $values['export_id'] = $match[0];
      else
        unset($values['export_id']);
    }

  # Keep only numerical characters in the comingfrom
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off()).
  if (isset($values['comingfrom']) && !ctype_digit($values['comingfrom']))
    {
      preg_match("/(\d+)/", $values['comingfrom'], $match);
      if(isset($matches))
        $values['comingfrom'] = $match[0];
      else
        unset($values['comingfrom']);
    }

  # Keep only numerical characters in the group_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off()).
  if (isset($values['group_id']) && !ctype_digit($values['group_id']))
    {
      preg_match("/(\d+)/", $values['group_id'], $match);
      if(isset($matches))
        $values['group_id'] = $match[0];
      else
        unset($values['group_id']);
    }

  # Keep only numerical characters in the user_id
  # (Set both the global and the _REQUEST vars, because the global may be
  # unregistered by register_globals_off()).
  if (isset($values['user_id']) && !ctype_digit($values['user_id'])
      && !is_array($values['user_id']))
    {
      preg_match("/(\d+)/", $values['user_id'], $match);
      if(isset($matches))
        $values['user_id'] = $match[0];
      else
        unset($values['user_id']);
    }
  return $values;
}

# Fuctions to sanitize user-supplied values.
# Return 0 when the variable was set, 1 otherwize (the caller will set
# the respective item to null).
# Arguments:
#   $in: input array
#   &$out: output array
#   $i: index of item to copy from $in to &$out sanitized
#   $arg: additional value, may modify function behavior
$sane_sanitizers = [];

# Never allow.
$sane_sanitizers['never'] = function ($in, &$out, $i, $arg)
{
  return 1;
};

# Filter through htmlspecialchars.
$sane_sanitizers['specialchars'] = function ($in, &$out, $i, $arg)
{
  if (!is_scalar ($in[$i]))
    return 1;
  $out[$i] = htmlspecialchars ($in[$i], ENT_QUOTES);
  return 0;
};

# Assign true when exists in input array.
$sane_sanitizers['true'] = function ($in, &$out, $i, $arg)
{
  $out[$i] = true;
  return 0;
};

# Pass first non-empty group of digits.
# $arg is maximum allowed number when $arg is scalar;
# $arg[0] and $arg[1] are minimum and maximum allowed values
# when $arg is an array.  When the value is out of bounds, it's dropped.
$sane_sanitizers['digits'] = function ($in, &$out, $i, $arg)
{
  if (!is_scalar ($in[$i]))
    return 1;
  if (!preg_match("/\d+/", $in[$i], $match))
    return 1;

  $out[$i] = $match[0];

  if ($arg === null)
    return 0;
  $mn = $out[$i];
  $mx = $out[$i];
  if (is_array ($arg))
    {
      if ($arg[0] !== null)
        $mn = $arg[0];
      if ($arg[1] !== null)
        $mx = $arg[1];
    }
  else
    $mx = $arg;
  if ($out[$i] <= $mx && $out[$i] >= $mn)
    return 0;
  unset ($out[$i]);
  return 1;
};

# Pass first non-empty group of xdigits.
$sane_sanitizers['xdigits'] = function ($in, &$out, $i, $arg)
{
  if (!is_scalar ($in[$i]))
    return 1;
  if (!preg_match("/[[:xdigit:]]+/", $in[$i], $match))
    return 1;
  $out[$i] = $match[0];
  return 0;
};

# Account name is expected.
# when $arg is a scalar, it's maximum name length (34 by default),
# when it's an array, $arg['max_len'] is maxumum name length,
# $arg['allow_dots'] is whether the dots are allowed.
$sane_sanitizers['name'] = function ($in, &$out, $i, $arg)
{
  $n = $in[$i];

  if (!is_scalar ($n))
    return 1;

  $max_len = 34;
  $allow_dots = false;

  if ($arg === null)
    $arg = $max_len;

  if (is_array ($arg))
    {
      if (isset ($arg['max_len']))
        $max_len = $arg['max_len'];
      if (isset ($arg['allow_dots']))
        $allow_dots = $arg['allow_dots'];
    }
  else
    $max_len = $arg;
  $reg_exp = "/^[_a-zA-Z-][";
  if ($allow_dots)
    $reg_exp .= '.';
  $reg_exp .= "_[:alnum:]-]*$/";

  if (strlen ($n) > $max_len)
    return 1;

  if (!preg_match ($reg_exp, $n))
    return 1;

  $out[$i] = $n;
  return 0;
};

# Set of strings provided in $arg (array or a single string);
# when isset ($arg['default']), $out[$i] is set to it if the input string
# equals to no string in $arg; otherwise, $out[$i] is to be nullified.
$sane_sanitizers['strings'] = function ($in, &$out, $i, $arg)
{
  if ($arg === null)
    return 1;
  if (!is_array ($arg))
    $arg = [$arg];
  $n = $in[$i];
  foreach ($arg as $str)
    if ($n == $str)
      {
        $out[$i] = $str;
        return 0;
      }
  if (!isset ($arg['default']))
    return 1;
  $out[$i] = $arg['default'];
  return 0;
};

# Strings used in 'func'.
$sane_sanitizers['funcs'] = function ($in, &$out, $i, $arg)
{
  global $sane_sanitizers;
  return $sane_sanitizers['strings'] ($in, $out, $i,
    [
      'additem', 'detailitem', 'addwatchee', 'browse', 'configure',
      'del', 'delete_cc', 'delete_dependency', 'delete_file',
      'deltransition', 'delwatchee', 'detailitem', 'digest', 'digestget',
      'digestselectfield', 'flagspam', 'manage', 'monitor', 'postaddcomment',
      'postadditem', 'postmoditem', 'search', 'subscribe', 'unflagspam',
      'unsubscribe', 'viewspam'
    ]);
};

# Names of tracker.
$sane_sanitizers['artifact'] = function ($in, &$out, $i, $arg)
{
  global $sane_sanitizers;
  return $sane_sanitizers['strings'] ($in, $out, $i,
    ['bugs', 'cookbook', 'news', 'patch', 'support', 'task']
  );
};

# Assign when match regex in $arg.
$sane_sanitizers['preg'] = function ($in, &$out, $i, $arg)
{
  if ($arg === null || !is_scalar($in[$i]))
    return 1;
  if (!preg_match($arg, $in[$i]))
    return 1;
  $out[$i] = $in[$i];
  return 0;
};

# MD5 hash.
$sane_sanitizers['hash'] = function ($in, &$out, $i, $arg)
{
  global $sane_sanitizers;
  return $sane_sanitizers['preg'] ($in, $out, $i, '/^[a-f\d]+$/');
};

# Only allow strings without quotes.
$sane_sanitizers['no_quotes'] = function ($in, &$out, $i, $arg)
{
  global $sane_sanitizers;
  return $sane_sanitizers['preg'] ($in, $out, $i, '/^[^\'"]*$/');
};

# Pass anything.  Use with caution.
$sane_sanitizers['pass'] = function ($in, &$out, $i, $arg)
{
  $out[$i] = $in[$i];
  return 0;
};

# Internal URI.
$sane_sanitizers['internal_uri'] = function ($in, &$out, $i, $arg)
{
  $uri = $in[$i];
  if (!is_scalar ($uri))
    return 1;
  if (
    strlen ($uri) < 2 || substr ($uri, 0, 1) !== '/'
    || substr ($uri, 1, 1) === '/'
  )
    $uri = "/";

  $out[$i] = $uri;
  return 0;
};

# Return function from $sane_sanitizers if it exists.
function sane_prefix_func ($x)
{
  global $sane_sanitizers;
  if (isset($sane_sanitizers[$x]))
    return $sane_sanitizers[$x];
  return null;
}

# Assign function and argument for scanning arrays
# (auxiliary function used in $sane_sanitizers['array']).
function sane_assign_arr_func ($arg_in, &$func_arg)
{
  $func_arg = null;
  $ret = $arg_in;
  if (is_array ($arg_in))
    {
      $ret = $arg_in[0];
      $func_arg = $arg_in[1];
    }
  return sane_prefix_func ($ret);
}

# Array; arg[0] is function and arg for keys,
# arg[1] is function and arg for values.
$sane_sanitizers['array'] = function ($in, &$out, $i, $arg)
{
  if (!(isset ($in[$i]) && is_array ($in[$i]) && is_array ($arg)))
    return 1;
  $out_arr = [];
  $key_func = sane_assign_arr_func ($arg[0], $key_arg);
  $val_func = sane_assign_arr_func ($arg[1], $val_arg);
  if ($key_func === null && $val_func === null)
    return 1;
  foreach ($in[$i] as $key => $val)
    {
      $tmp_key = [0 => $key];
      $tmp_val = [0 => $val];
      if ($key_func !== null)
        if ($key_func ([0 => $key], $tmp_key, 0, $key_arg))
          continue;
      if ($val_func !== null)
        if ($val_func ([0 => $val], $tmp_val, 0, $val_arg))
          continue;
      $out_arr[$tmp_key[0]] = $tmp_val[0];
    }
  $out[$i] = $out_arr;
  return 0;
};

# Functions to access user input.

function sane_input_array_name ($method)
{
  if ($method == 'get')
    return '_GET';
  if ($method == 'post')
    return '_POST';
  if ($method == 'cookie')
    return '_COOKIE';
  if ($method == 'files')
    return '_FILES';
  if ($method == 'test')
    return 'sane_test_input';
  return '_REQUEST';
}

# Apply function $func to a number of $input items.
# $func: function to apply
# $input: source array
# $values: destination array
# $name: set of indices to work on.
# when $name is a scalar, use it as the only item.
# when $name is an array, every item if it is processed,
#   when $name[$i] is a acalar, it's passed to $func,
#   when $name[$i] is an array, its last item is used as $arg,
#     the rest items are passed to $func.
#
# Examples of $func => $name:
#   'specialchars' => 'comment'
#   'true'         => ['basic', 'rich', 'full']
#   'digits'       =>
#     ['group_id', 'job_id',
#       ['ten_to_twelve', '10_to_dozen', 'tento12', [10, 12]]
#     ]
function sane_apply_func ($func, $input, $name, &$values)
{
  if (is_array ($name))
    $arr = $name;
  else
    $arr = [$name];

  foreach ($arr as $item)
    {
      $arg = null;
      if (is_array ($item))
        $arg = array_pop ($item);
      else
        $item = [$item];

      foreach ($item as $n)
        {
          $void = 1;
          if (isset ($input[$n]))
            $void = $func ($input, $values, $n, $arg);
          if ($void)
            $values[$n] = null;
        }
    }
}

# Check the existence of a series of input parameters, then return an
# array suitable for extract().
# Ex: extract(sane_import('post',
#       array('insert_group_name', 'rand_hash',
#             'form_full_name', 'form_unix_name')));
# Note: there's another import function to clean-up in trackers/general.php.
function sane_import($method, $names)
{
  $values = array();
  $sane_cleaned = array();
  $input =& $GLOBALS[sane_input_array_name ($method)];

  foreach ($names as $fn => $name)
    {
      $func = sane_prefix_func ($fn);
      if ($func !== null)
        {
          sane_apply_func ($func, $input, $name, $values);
          continue;
        }
      if (is_int($fn) && isset ($input[$name]))
        $sane_cleaned[$name] = $input[$name];
      else
        $sane_cleaned[$name] = null;
    }
  return array_merge (sane_clean($sane_cleaned), $values);
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
# (like thispage.php?arg=userinput).
function sane_get($varname)
{
  if (isset($_GET[$varname]))
    return $_GET[$varname];
  else
    return '';
}

# Function to obtain user input submitted while posting a form.
function sane_post($varname)
{
  if (isset($_POST[$varname]))
    return $_POST[$varname];
  else
    return '';
}

# Function to obtain user input submitted in a cookie.
function sane_cookie($varname)
{
  if (isset($_COOKIE[$varname]))
    return $_COOKIE[$varname];
  else
    return '';
}

# Does an isset. Not really necessary, just for cohesion sake.
function sane_isset($varname)
{
  return isset($_REQUEST[$varname]);
}

# Function to obtain info related to a file upload.
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
# it should be used only in include/ like pre.php).
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
?>
