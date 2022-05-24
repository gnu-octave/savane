<?php
# Utility functions.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>,
# Copyright (C) 2002-2006 Tobias Toedter <t.toedter--gmx.net>
# Copyright (C) 2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
# Copyright (C) 2006, 2007, 2008, 2010 Sylvain Beucler
# Copyright (C) 2017, 2018, 2019, 2020, 2022 Ineiev
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

# Clean initialization for globals.
$GLOBALS['feedback_count'] = 0;
$GLOBALS['feedback'] = '';
$GLOBALS['ffeedback'] = '';

define('FB_ERROR', 1);

# Get path for site-specific content.
function utils_get_content_filename ($file)
{
  global $sys_incdir;

  $f = dirname (__FILE__) . "/../../site-specific/$sys_incdir/$file.php";
  if (is_file ($f))
    return $f;

  $f = "$sys_incdir/php/$file.php"; # Deprecated old location.
  if (is_file ($f))
    return $f;
  return null;
}

# Include site-specific content.
function utils_get_content ($filename)
{
  $file = utils_get_content_filename($filename);
  if ($file != null)
    include($file);
}

# Make sure that to avoid malicious file paths.
function utils_check_path ($path)
{
  if (strpos ($path, "../") !== FALSE)
    exit_error (_('Error'),
      # TRANSLATORS: the argument is file path.
      sprintf (_('Malformed file path %s'), $path)
    );
}

# Return text for a control: a link when $available, else a <span>
# with 'unavailable' CSS class.  Unavailable "links" are written
# with different tags to let CSS-unaware browsers distinguish them.
function utils_link (
  $url, $title, $defaultclass = 0, $available = 1, $help = 0, $extra = ''
)
{
  $closing_tag = '</a>';
  $return = "<a href=\"$url\"";

  if (!$available)
    {
      $defaultclass = 'unavailable';
      $title = "<del>$title</del>";
      $return = '<span';
      $closing_tag = '</span>';
    }

  if ($defaultclass)
    $return .= " class=\"$defaultclass\"";
  if ($help)
    $return .= " title=\"$help\"";
  if ($extra)
    $return .= " $extra";
  return "$return>$title$closing_tag";;
}

# Make an clean email link depending on the authentification level of the user.
# Don't use this on normal text, just on field where only an email address is
# expected.  This may corrupt the text and does extensive search.
function utils_email ($address, $nohtml = 0)
{
  if  (user_isloggedin ())
    {
      if ($nohtml)
        return $address;

      # Remove eventual extra white spaces.
      $address = trim ($address);

      # If we have < > in the address, only this content must go in the mailto.
      $realaddress = null;;
      if (preg_match ("/\<([\w\d\-\@\.]*)\>/", $address, $matches))
        $realaddress = $matches[1];

      $addr = htmlspecialchars ($address);
      $raddr = htmlspecialchars ($realaddress);
      # We have a user name.
      if (!strpos ($address, "@"))
        {
          # We found a real address and it is a user login.
          $uid = user_getid ($realaddress);
          if ($realaddress && user_exists ($uid))
            return utils_user_link ($realaddress, user_getrealname ($uid));
          # The whole address is a user login.
          $uid = user_getid ($address);
          if (user_exists ($uid))
            return utils_user_link ($address, user_getrealname ($uid));

          # No @, no real addresses and spaces inside? Looks like someone
          # forgot commas.
          if (!$realaddress && strpos ($address, " "))
            return $addr . ' <span class="warn">'
              . _("(address seems invalid and will probably be ignored)")
              . '</span>';

          # No @ but and not a login? P
          # TRANSLATORS: the argument is mail domain (like localhost or
          # sv.gnu.org).
          $msg = sprintf (
            _("(address is unknown to Savane, will fail if not valid at %s)"),
            $GLOBALS['sys_mail_domain']
          );

          return "$addr <span class='warn'>$msg</span>";
        }

      # If we are here, it means that we have an @ in the address,
      # Even if the address is invalid, the system is likely to try to
      # send the mail, and we have no way to know if the address is valid.
      # We will only do a check on the address syntax.

      # We found a real address that is syntactically correct.
      if ($realaddress && validate_email ($realaddress))
        return "<a href=\"mailto:$raddr\">$addr</a>";

      # We found real address but it does not seem correct. Print a warning.
      if ($realaddress)
        return $addr . ' <span class="warn">'
          . _("(address seems invalid and will probably be ignored)")
          . '</span>';
      # No realaddress found, only one string that is an address.
      if (validate_email ($address))
        return "<a href=\"mailto:$addr\">$addr</a>";
      return $addr . ' <span class="warn">'
        . _("(address seems invalid and will probably be ignored)")
        . '</span>';
    }
  if ($nohtml)
    return _("-email is unavailable-");
  return utils_help (
    _("-email is unavailable-"),
    _("This information is not provided to anonymous users")
  );
}

# Like the previous, but does no extended search, just print as it comes.
function utils_email_basic ($address, $nohtml = 0)
{
  if (user_isloggedin () || CONTEXT == 'forum' || CONTEXT == 'news'
      || CONTEXT == '' # frontpage
  )
    {
      $addr = htmlspecialchars ($address);
      if ($nohtml)
        return $addr;
      # Make mailto without trying to find out whether it makes sense.
      return "<a href=\"mailto:$addr\">$addr</a>";
    }

  if ($nohtml)
    return _("-email is unavailable-");
  return utils_help (
    _("-email is unavailable-"),
    _("This information is not provided to anonymous users")
  );
}

# Find out if a string is pure ASCII or not.
function utils_is_ascii ($string)
{
  return preg_match ('%^[[:ascii:]]*$%', $string);
}

# Alias function.
function utils_altrow ($i)
{
  return html_get_alt_row_color ($i);
}

function utils_cutstring ($string, $length = 35)
{
  $string = rtrim ($string);
  if (strlen ($string) > $length)
    {
      $string = substr ($string, 0, $length);
      $string = substr ($string, 0, strrpos ($string, ' '));
      $string .= "...";
    }
  return $string;
}

# Return a formatted date for a unix timestamp.
#
# The given unix timestamp will be formatted according to
# the $format parameter. Note that this parameter is not one
# of the format strings supported by functions such as
# strftime(), but a description instead.
#
# Currently, you can use the following values for $format:
#   - default => localized Fri 18 November 2005 at 18:51
#   - natural => 2005-11-18 or (for recent events) 18:51.
#   - minimal => 2005-11-18
#
# @see utils_date_to_unixtime()
function utils_format_date ($timestamp, $format = "default")
{
  global $sys_datefmt;
  if ($timestamp == 0)
    return '-';

  # The installation configured a specific date format. This is not nice
  # this will prevent locales from being used.
  if ($sys_datefmt)
    return strftime ($sys_datefmt, $timestamp);

  # Go at task #2614 to discuss this.
  # Used by default.
  switch ($format)
    {
    case 'minimal':
      {
        # To be used where place is really lacking, like in feature boxes.
        # (Nowhere else, it is too uninformative.)
        # Let's use a non-ambiguous format, such as ISO 8601's YYYY-MM-DD
        # extended calendar format.
        # Previously we used %x, where MM and DD can be swapped
        # depending on locale, and users reported confusion.
        return strftime ('%Y-%m-%d', $timestamp);
      }
    case 'natural':
      {
        if (time () < 12 * 60 * 60 + $timestamp && time () > $timestamp)
        # Nearest past events are shown as time.
          $date_fmt = '%H:%M';
        else
          $date_fmt = '%Y-%m-%d';
        return strftime ($date_fmt, $timestamp);
      }
    default:
      {
        # %c  The preferred date and time representation for the current locale.
        # Cf. strftime(3)
        return strftime ('%c', $timestamp);
      }
    }
  return false;
}

# Convert a date as used in the bug tracking system and other services (YYYY-MM-DD)
# into a Unix time.
# Return a list with two values: the unix time and a boolean saying whether
# the conversion went well (true) or bad (false).
function utils_date_to_unixtime ($date)
{
  $res = preg_match ("/\s*(\d+)-(\d+)-(\d+)/", $date, $match_arr);
  if ($res == 0)
    return [0, false];
  list (, $year, $month, $day) = $match_arr;
  $time = mktime (0, 0, 0, $month, $day, $year);
  return [$time, true];
}

function utils_read_file ($filename)
{
  @$fp = fopen ($filename, "r");
  if (!$fp)
    return false;
  $val = fread ($fp, filesize ($filename));
  fclose ($fp);
  return $val;
}

function utils_filesize ($filename, $file_size = 0)
{
  # If file size is defined, assume that we just want an unit conversion.

  # Round results: Savane is not a math software.
  if (!isset ($file_size))
    $file_size = filesize ($filename);

  if ($file_size >= 1048576)
    # TRANSLATORS: this expresses file size.
    $file_size = sprintf (_("%sMiB"), round ($file_size / 1048576));
  elseif ($file_size >= 1024)
    # TRANSLATORS: this expresses file size.
    $file_size = sprintf(_("%sKiB"), round ($file_size / 1024));
  else
    # TRANSLATORS: this expresses file size.
    $file_size = sprintf(_("%sB"), round ($file_size));
  return $file_size;
}

# Return human-readable sizes.
# This is public domain, original version from:
# Author:      Aidan Lister <aidan@php.net>
# Version:     1.1.0
# Link:        http://aidanlister.com/repos/v/function.size_readable.php
# Param:       int    $size        Size
# Param:       int    $unit        The maximum unit
# Param:       int    $retstring   The return string format
# Param:       int    $si          Whether to use SI prefixes
function utils_size_readable ($size, $unit = null, $retstring = null, $si = false)
{
  # Units.
  if ($si === true)
    {
      $sizes = [
        # TRANSLATORS: this is file size unit (no prefix).
        _('B'),
        # TRANSLATORS: this is file size unit (with SI prefix.)
        _('kB'), _('MB'), _('GB'), _('TB'), _('PB')
      ];
      $mod   = 1000;
    }
  else
    {
      $sizes = [
        # TRANSLATORS: this is file size unit (no prefix).
        _('B'),
        # TRANSLATORS: this is file size unit (with binary prefix.)
        _('KiB'), _('MiB'), _('GiB'), _('TiB'), _('PiB')
      ];
      $mod   = 1024;
    }
  $ii = count ($sizes) - 1;

  # Find maximum unit applicable.
  $unit = array_search ((string) $unit, $sizes);
  if ($unit === null || $unit === false)
    $unit = $ii;

  if ($retstring === null)
    $retstring = '%01.2f%s';
  $i = 0;
  while ($unit != $i && $size >= 1024 && $i < $ii)
    {
      $size /= $mod;
      $i++;
    }
  return sprintf ($retstring, $size, $sizes[$i]);
}

function utils_fileextension ($filename)
{
  $base = basename ($filename); 
  $ext = substr ($base, strrpos ($base, ".") + 1);
  if ($ext == 'gz' || $ext == 'bz2')
    $ext = substr ($base, strrpos ($base, ".") - 3);
  if ($ext == 'rpm')
    # TRANSLATORS: this is used in contexts like "rpm package for i386 (ix86)"
    # or "source rpm package".
    $long_ext = _("rpm package");
  if ($ext == 'deb')
    # TRANSLATORS: this is used in contexts like "debian package for i386 (ix86)"
    # or "source debian package".
    $long_ext = _("debian package");
  if (!($ext == 'deb' || $ext == 'rpm'))
    return $ext;
  $arch_type = substr ($base, strrpos ($base, ".") - 3);
  if ($arch_type == "src.$ext")
    # TRANSLATORS: the argument is translation of either 'rpm package' or
    # 'debian package'.
    $long_ext = sprintf (_("source %s"), $long_ext);
  if ($arch_type == "rch.$ext")
    $long_ext = sprintf (_("arch independent %s"), $long_ext);
  if ($arch_type == "386.$ext")
    $long_ext = sprintf (_("%s for i386 (ix86)"), $long_ext);
  if ($arch_type == "586.$ext")
    $long_ext = sprintf (_("%s for i586"), $long_ext);
  if ($arch_type == "686.$ext")
    $long_ext = sprintf (_("%s for i686"), $long_ext);
  if ($arch_type == "a64.$ext")
    $long_ext = sprintf (_("%s for Itanium 64"), $long_ext);
  if ($arch_type == "arc.$ext")
    $long_ext = sprintf (_("%s for Sparc"), $long_ext);
  if ($arch_type == "pha.$ext")
    $long_ext = sprintf (_("%s for Alpha"), $long_ext);
  if ($arch_type == "ppc.$ext")
    $long_ext = sprintf (_("%s for PowerPC"), $long_ext);
  if ($arch_type == "390.$ext")
    $long_ext = sprintf (_("%s for s390"), $long_ext);
  $ext = $long_ext;
  return $ext;
}

function utils_prep_string_for_sendmail ($body)
{
  return str_replace (
    ["\\", "\"", "\$", "`"] , ["\\\\", "\\\"", "\\\$", "\\`"], $body
  );
}

function utils_unconvert_htmlspecialchars ($string)
{
  if (strlen ($string) < 1)
    return '';
  return str_replace (
    ['&nbsp;', '&quot;', '&gt;', '&lt;', '&amp;'], [' ', '"', '>', '<', '&',],
    $string
  );
}

function utils_remove_htmlheader ($string)
{
  return preg_replace (
    '#(^.*<html[^>]*>.*<body[^>]*>)|(</body[^>]*>.*</html[^>]*>.*$)#i', '',
    $string
  );
}

# Take a result set and turn the optional column into an array.
function utils_result_column_to_array ($result, $col = 0, $localize = false)
{
  $rows = db_numrows ($result);

  $arr = [];
  if ($rows <= 0)
    return $arr;
  for ($i = 0; $i < $rows; $i++)
    {
      $val = db_result ($result, $i, $col);
      if ($localize)
        $val = htmlentities (gettext ($val));
      $arr[$i] = $val;
    }
  return $arr;
}

# Backwards compatibility.
function result_column_to_array ($result, $col = 0)
{
  return utils_result_column_to_array ($result, $col);
}

function utils_wrap_find_space ($string, $wrap)
{
  $start = $wrap - 5;
  $try = 1;

  while (true)
    {
      $pos = @strpos ($string, ' ', $start);

      if (($pos > $wrap + 5) || !$pos)
        {
          $try++;
          $start = $wrap - $try * 5;
          if ($start <= 10)
            return $wrap;
        }
      else
        break;
    }
  return $pos;
}

function utils_line_wrap ($text, $wrap = 78, $break = "\n")
{
  $paras = explode ("\n", $text);
  $result = [];
  $i = 0;
  while ($i < count ($paras))
    {
      if (strlen ($paras[$i]) <= $wrap)
        {
          $result[] = $paras[$i];
          $i++;
        }
      else
        {
          $pos = utils_wrap_find_space ($paras[$i], $wrap);
          $result[] = substr($paras[$i], 0, $pos);

          $new = trim (substr ($paras[$i], $pos, strlen ($paras[$i]) - $pos));
          if ($new != '')
            {
              $paras[$i] = $new;
              $pos = utils_wrap_find_space ($paras[$i], $wrap);
            }
          else
            {
              $i++;
            }
        }
    }
  return implode ($break, $result);
}

function utils_user_link ($username, $realname = false, $noneisanonymous = false)
{
  global $sys_home;

  if ($username == 'None' || empty($username))
    {
      # Would be nice to always return _("Anonymous"); but in some cases it is
      # really none (assigned_to).
      if (!$noneisanonymous)
        # TRANSLATORS: Displayed when no user is selected.
        return _('None');
      # TRANSLATORS: anonymous user.
      return _("Anonymous");
    }
  $re = "<a href=\"${sys_home}users/$username\">";
  if ($realname)
    $re .= "$realname &lt;$username&gt;";
  else
    $re .= $username;
  return "$re</a>";
}
 
function utils_double_diff_array ($arr1, $arr2)
{
  # First transform both arrays in hashes.
  foreach ($arr1 as $v)
    $h2[$v] = $v;
  foreach ($arr2 as $v)
    $h2[$v] = $v;

  $deleted = [];
  foreach ($h2 as $k => $v)
    if (!isset ($h2[$k]))
      $deleted[] = $k;

  $added = [];
  foreach ($h2 as $k => $v)
    if (!isset ($h2[$k]))
      $added[] = $k;
  return [$deleted, $added];
}

function utils_registration_history ($unix_group_name)
{
  # Meaningless with chrooted system; all www system should be chrooted.
}

function show_priority_colors_key ()
{
  print '<p class="smaller">';
  print _("Open Items Priority Colors:") . "<br />&nbsp;&nbsp;&nbsp;\n";

  for ($i = 1; $i < 10; $i++)
    print '<span class="' . utils_get_priority_color ($i) . '">&nbsp;'
      . "$i&nbsp;</span>\n";

  print "<br />\n";
  print _("Closed Items Priority Colors:") . "<br />&nbsp;&nbsp;&nbsp;\n";

  for ($i = 11; $i < 20; $i++)
    print '<span class="' . utils_get_priority_color ($i) . '">&nbsp;'
      . ($i-10) . "&nbsp;</span>\n";
  print  "</p>\n";
}

function utils_get_tracker_icon ($tracker)
{
  if ($tracker == "bugs")
    return "bug";
  if ($tracker == "support")
    return "help";
  if ($tracker == "cookbook")
    return "man";
  return $tracker;
}

function utils_get_tracker_prefix ($tracker)
{
  if ($tracker == "bugs")
    return "bug";
  if ($tracker == "support")
    return "sr";
  if ($tracker == "cookbook")
    return "recipe";
  return $tracker;
}

# Return the localized name for the given tracker, if available.
# Otherwise, return the input string.
function utils_get_tracker_name ($tracker)
{
  if ($tracker == 'bugs')
    return _('bugs');
  if ($tracker == 'cookbook')
    return _('recipes');
  if ($tracker == 'patch')
    return _('patches');
  if ($tracker == 'support')
    return _('support requests');
  if ($tracker == 'task')
    return _('tasks');
  return $tracker;
}

function utils_get_priority_color ($index, $closed = "")
{
  global $bgpri;
  # If the item is closed, add ten to the index number to get closed colors.
  if ($closed == 3)
    $index = $index + 10;

  return $bgpri[$index];
}

# Very simple, plain way to show a generic result set.
# Accepts a result set and title.
# Makes certain items into HTML links.
function utils_show_result_set (
  $result, $title = "Untitled", $linkify = false, $level = false
)
{
  global $group_id,$HTML;

  if ($level === false)
    $level = '3';

  if ($title == "Untitled")
    $title = _("Untitled");

  if  (!$result)
    {
      print db_error();
      return;
    }
  $rows = db_numrows ($result);
  $cols = db_numfields ($result);

  print "<h$level>$title</h$level>\n";
  print "<table border='0' width='100%' summary=\"$title\">\n";

  print "<tr>\n";
  for ($i = 0; $i < $cols; $i++)
    print '<th>' . db_fieldname ($result,  $i) . "</th>\n";
  print "</tr>\n";

  $lhead = '<a href="' . htmlentities ($_SERVER['PHP_SELF'])
    . "?group_id=$group_id&";
  for ($j = 0; $j < $rows; $j++)
    {
      switch ($linkify)
        {
        case "bug_cat":
          $lhead .= "bug_cat_mod=y&bug_cat_id="
            . db_result ($result, $j, 'bug_category_id') . '">';
          break;
        case "bug_group":
          $lhead .= "bug_group_mod=y&bug_group_id="
            . db_result ($result, $j, 'bug_group_id') . '">';
          break;
        case "patch_cat":
          $lhead .= "patch_cat_mod=y&patch_cat_id="
            . db_result ($result, $j, 'patch_category_id') . '">';
          break;
        case "support_cat":
          $lhead .= "support_cat_mod=y&support_cat_id="
            . db_result ($result, $j, 'support_category_id') . '">';
          break;
        case "pm_project":
          $lhead .= "project_cat_mod=y&project_cat_id="
            . db_result ($result, $j, 'group_project_id') . '">';
          break;
        default:
          $linkify = false;
          $link = $linkend = '';
       }
      if ($linkify)
        {
          $link = $lhead;
          $linkend = '</a>';
        }
      print '<tr class="' . utils_altrow ($j) . '">';
      for ($i = 0; $i < $cols; $i++)
        {
          $res_ji = db_result ($result,  $j,  $i);
          print "<td>$link$res_ji$linkend</td>\n";
          $link = $linkend = '';
        }
      print "</tr>\n";
    }
  print "</table>\n";
}

# Clean up email address (remove spaces...) and put to lower case.
function utils_cleanup_emails ($addresses)
{
  # It was previously removing white spaces:
  # This is a bad idea. If we want to remove spaces, we have to check user
  # input, not to corrupt manually entered data afterwards.
  # If we allow white space to be entered, then we have to keep them.
  # For instance, if we allow to be entered: Robert <bob@bla.org>
  # it must not end up in Robert<bob@bla.org>.
  # (And we want to allow CC to be added like in a mail client).
  return strtolower ($addresses);
}

# Clean up email address (remove spaces...) and add @... if it is a simple
# login name.
function utils_normalize_email ($address)
{
  $address = utils_cleanup_emails($address);
  if (validate_email($address))
    return $address;
  return $address."@".$GLOBALS['sys_mail_domain'];
}

# Clean up email address (remove spaces...) and split comma separated emails.
function utils_split_emails ($addresses)
{
  $addresses = utils_cleanup_emails ($addresses);
  $addresses = str_replace (";", ",", $addresses);
  return explode (',', $addresses);
}

# Email Verification.
function validate_email ($address)
{
  # FIXME: this allows in domain names some characters that are not allowed
  return (preg_match (',^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'
                      . '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'
                      . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$,', $address));
}

# Verification of comma separated list of email addresses.
function validate_emails ($addresses)
{
  $arr = utils_split_emails ($addresses);

  foreach ($arr as $addr)
    if (!validate_email ($addr))
      return false;
  return true;
}

function utils_is_valid_filename ($file)
{
  if (preg_match ("/[]~`! ~@#\"$%^,&*();=|[{}<>?\/]/", $file))
    return false;
  if (strstr ($file,'..'))
    return false;
  return true;
}

# Add debugging information.
function util_debug ($msg)
{
  if (!$GLOBALS['sys_debug_on'])
    return;
  $backtrace = debug_backtrace ();
  $location = '';
  if (isset ($backtrace[1]))
    $location = $backtrace[1]['function'];
  else
    {
      $relative_path = str_replace (
        $GLOBALS['sys_www_topdir'] . '/', '', $backtrace[0]['file']
      );
      $location = "$relative_path:{$backtrace[0]['line']}";
    }
  $GLOBALS['debug'] .= "($location) $msg<br />";
}

function dbg ($msg)
{
  util_debug ($msg);
}

# Temporary debug.
# Use it instead of 'echo' so you can easily spot and remove them later after
# debugging is done.
function temp_dbg ($msg)
{
  print '<pre>';
  debug_print_backtrace ();
  var_dump ($msg);
  print '</pre>';
}

# Die with debug information.
function util_die ($msg)
{
  if (!$GLOBALS['sys_debug_on'])
    die ($msg);
  print "<strong>Fatal error:</strong> $msg<br />";
  print '<pre>';
  debug_print_backtrace ();
  print '</pre>';
  die ();
}

#  Modified to print any given backtrace.
#  Original comments:
#  Replace debug_print_backtrace()
#
#  @category    PHP
#  @package     PHP_Compat
#  @license     LGPL - http://www.gnu.org/licenses/lgpl.html
#  @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
#  @link        http://php.net/function.debug_print_backtrace
#  @author      Laurent Laville <pear@laurent-laville.org>
#  @author      Aidan Lister <aidan@php.net>
#  @version     $Revision: 1.6 $
#  @since       PHP 5
#  @require     PHP 4.3.0 (debug_backtrace)
function utils_debug_print_mybacktrace ($backtrace = null)
{
  # Get backtrace.
  if ($backtrace === null)
    {
      $backtrace = debug_backtrace ();
      # Unset call to debug_print_backtrace.
      array_shift ($backtrace);
    }

  if (empty ($backtrace))
    return '';

  $calls = [];
  foreach ($backtrace as $i => $call)
    {
      if (!isset ($call['file']))
        $call['file'] = '(null)';
      if (!isset ($call['line']))
        $call['line'] = '0';
      $location = $call['file'] . ':' . $call['line'];
      $function = (isset ($call['class'])) ?
      $call['class'] . (isset ($call['type']) ? $call['type'] : '.')
        . $call['function'] :
      $call['function'];

      $params = '';
      if (isset ($call['args']))
        {
          $args = [];
          foreach ($call['args'] as $arg)
            {
              if (is_array ($arg))
                $args[] = print_r ($arg, true);
              elseif (is_object ($arg))
                $args[] = get_class ($arg);
              else
                $args[] = $arg;
            }
          $params = implode (', ', $args);
        }
      $calls[] = sprintf (
        '#%d  %s(%s) called at [%s]', $i, $function, $params, $location
      );
    }
  echo implode("\n", $calls), "\n";
}

function util_feedback ($msg, $error = 0)
{
  fb ($msg, $error);
}

function feedback ($msg, $error = 0)
{
  fb ($msg, $error);
}

# Add feedback information.
function fb ($msg, $error = 0)
{
  $GLOBALS['feedback_count']++;

  if ($GLOBALS['sys_debug_on'])
    {
      $msg .= ' [#' . $GLOBALS['feedback_count'] . ']';
      dbg("Add feedback #" . $GLOBALS['feedback_count']);
    }
  $msg .= "\n";
  if ($error)
    $GLOBALS['ffeedback'] .= $msg;
  else
    $GLOBALS['feedback'] .= $msg;
}

# Fb function to be used about database error when context of error is obvious.
function fb_dberror ()
{
  fb (_("Error updating database"), 1);
}

# Fb function to be used about database success when context is obvious.
function fb_dbsuccess ()
{
  fb (_("Database successfully updated"));
}

function utils_help ($text, $explanation_array)
{
  return help ($text, $explanation_array);
}

# Print help about a word.
#   $text is the sentence where ballons are
#   $explanation_array is the table word->explanation, must be in the
#   array syntax.
function help ($text, $explanation_array)
{
  if (!is_array ($explanation_array))
    return "<span class='help' title=\"$explanation_array\">$text</span>";
  foreach ($explanation_array as $word => $explanation)
    $text = str_replace (
      $word, "<span class='help' title=\"$explanation\">$word</span>", $text
    );
  return $text;
}

# Analyse if we do need MSIE dirtyhacks.
# Put the result in cache so we shan't over and over analyse user agent.
# (This function will indeed think a browser that claims to be MSIE that it is
# MSIE. Users of browsers like Opera that pretend to be MSIE should configure
# properly their User Agent. There is nothing else to do about it).
function utils_is_broken_msie ()
{
  # If already set, return what we found.
  if (isset($GLOBALS['are_we_using_broken_msie']))
    {
      return $GLOBALS['are_we_using_broken_msie'];
    }

  # Otherwise, find out, assuming that by default we dont use broken MSIE.
  $is_broken = false;

  # Try to find the string MSIE.
  if (isset ($_SERVER['HTTP_USER_AGENT']))
    {
      $msie = strpos ($_SERVER['HTTP_USER_AGENT'], "MSIE");
      if ($msie !== false)
        {
          # Avoid MSIE > 6: look for the first integer after the MSIE
          # string, in the next characters.
          $msie = substr ($_SERVER['HTTP_USER_AGENT'], $msie, 10);
          preg_match ("/MSIE (\d*)/", $msie, $msie_version);
          if ((!isset ($msie_version[1]) || $msie_version[1] < 7))
            {
              $is_broken = true;
            }
        }
    }

  # Save globally.
  $GLOBALS['are_we_using_broken_msie'] = $is_broken;
  return $is_broken;
}

function is_broken_msie ()
{
  return utils_is_broken_msie ();
}

function utils_setcookie ($name, $value, $expire, $secure = false)
{
  setcookie ($name, $value, $expire, $GLOBALS['sys_home'], '', $secure, true);
}

function utils_set_csp_headers ()
{
  if (!empty ($GLOBALS['skip_csp_headers']) && $GLOBALS['skip_csp_headers'])
    return;
  # Set up proper use of UTF-8, even if the webserver doesn't serve it by default.
  header('Content-Type: text/html; charset=utf-8');
  # Disallow embedding in any frames.
  header('X-Frame-Options: DENY');
  # Declare more restrictions on how browsers may assemble pages.
  # Security issues apply even during fundrasing periods.  Please don't disable
  # this; instead, add some domain to img-src (or (better?) copy necessary
  # images to images/ or to submissions_uploads/).
  $policy = "Content-Security-Policy: default-src 'self'; frame-ancestors 'none'";
  if ($GLOBALS['sys_file_domain'] != $GLOBALS['sys_default_domain'])
    $policy .= "; img-src 'self' " . $GLOBALS['sys_file_domain'];
  header ($policy);
}

# Run a command $cmd, return its exit code; put its output and error streams
# to $out and $err.
function utils_run_proc ($cmd, &$out, &$err)
{
  $d_spec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];

  $out = null;
  $proc = proc_open ($cmd, $d_spec, $pipes, NULL, $_ENV);
  if ($proc === false)
    {
      $err = "can't run $cmd\n";
      return -1;
    }
  fclose ($pipes[0]);
  $out = stream_get_contents ($pipes[1]);
  $err = stream_get_contents ($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  return proc_close ($proc);
}

# Try to move $tmp_path to $path without overwriting if the latter exists;
# return $path when successful, $tmp_path otherwise.
function utils_try_move ($tmp_path, $path)
{
  $link_error_handler = function ($errno, $errstr, $errfile, $errline)
  {
    # Ignore warning.
  };
  $old_handler = set_error_handler ($link_error_handler, E_WARNING);
  $res = link ($tmp_path, $path);
  set_error_handler ($old_handler, E_WARNING);
  if (!$res) # Already exists; fallback to temporary file name.
    return $tmp_path;
  unlink ($tmp_path);
  return $path;
}

# Make a file with a name based on $tarball_name in $sys_upload_dir without
# overwriting existing files; the new file name is $tarball_name unless
# such file already exists, otherwise a name based on a template is used.
# Return the path to the new file; in case of failure return null and
# put a diagnostic string to $errors.
function utils_make_upload_file ($tarball_name, &$errors)
{
  global $sys_upload_dir;
  $name = $tarball_name;
  $path = "$sys_upload_dir/$name";
  # It might be easier to use tempnam (), but it has no --suffix feature.
  $name = strtr ($name, "'/", ".-");
  $res = utils_run_proc (
    "mktemp -p \"$sys_upload_dir\" --suffix='-$name' XXXXXX", $out, $err
  );
  if ($res)
    {
      # Can't create a temporary file; $path may work,
      # but it would at least create a race condition, so just don't proceed.
      $errors = "$res: $err";
      return null;
    }
  $out = substr ($out, 0, strlen ($out) - 1); # Remove trailing "\n".
  return utils_try_move ($out, $path);
}

# A helper function (mix of str_repeat and implode) used further.
function utils_str_join ($separator, $str, $n)
{
  return str_repeat ("$str$separator", $n - 1) . $str;
}
?>
