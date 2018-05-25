<?php
# Utility functions.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>,
# Copyright (C) 2002-2006 Tobias Toedter <t.toedter--gmx.net>
# Copyright (C) 2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
# Copyright (C) 2006, 2007, 2008, 2010 Sylvain Beucler
# Copyright (C) 2017 Ineiev
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
  if (is_file($GLOBALS['sys_incdir'].'/php/'.$file.'.php'))
    return $GLOBALS['sys_incdir'].'/php/'.$file.'.php';
  if (is_file($GLOBALS['sys_incdir'].'/php/'.$file.'.txt'))
    return $GLOBALS['sys_incdir'].'/php/'.$file.'.txt';
# Fallback to legacy location.
  if (is_file($GLOBALS['sys_incdir'].'/'.$file.'.'.$GLOBALS['locale']))
    # There is a localized version of the file:
    return $GLOBALS['sys_incdir'].'/'.$file.'.'.$GLOBALS['locale'];
  if (is_file($GLOBALS['sys_incdir'].'/'.$file.'.txt'))
    return $GLOBALS['sys_incdir'].'/'.$file.'.txt';
  return null;
}

# Include site-specific content.
function utils_get_content ($filename)
{
  $file = utils_get_content_filename($filename);
  if ($file != null)
    include($file);
  else
    fb(sprintf(_("Warning: Savane was not able to read site-specific
information from file '%s', please contact administrators"),
       $filename), 1);
}

# Make sure that to avoid malicious file paths.
function utils_check_path ($path)
{
  if (eregi(".*\.\.\/.*", $path))
    {
      exit_error(_('Error'),
# TRANSLATORS: the argument is file path.
                 sprintf(_('Malformed file path %s'), $path));
    }
}

# Add unavailable css class to a link if required.
function utils_link ($url, $title, $defaultclass=0, $available=1, $help=0,
                     $extra='')
{
  if (!$available)
    $defaultclass = 'unavailable';

  $return = '<a href="'.$url.'"';

  if ($defaultclass)
    $return .= ' class="'.$defaultclass.'"';
  if ($help)
    $return .= ' title="'.$help.'"';
  if ($extra)
    $return .= ' '.$extra;
  $return .= '>'.$title.'</a>';
  return $return;
}

# Make an clean email link depending on the authentification level of the user,
# Don't use this on normal text, just on field where only an email address is
# expected. THis may corrupt the text and does extensive search.
function utils_email ($address, $nohtml=0)
{
  if (user_isloggedin())
    {
      if ($nohtml)
        return $address;

      # Remove eventual extra white spaces.
      $address = trim($address);

      # If we have < > in the address, only this content must go in the
      # mailto
      $realaddress = null;;
      if (preg_match("/\<([\w\d\-\@\.]*)\>/", $address, $matches))
        $realaddress = $matches[1];

      # We have a user name.
      if (!strpos($address, "@"))
        {
          # We found a real address and it is a user login.
          if ($realaddress && user_exists(user_getid($realaddress)))
            return utils_user_link($realaddress,
                                   user_getrealname(user_getid($realaddress)));
          # The whole address is a user login.
          if (user_exists(user_getid($address)))
            {
              return utils_user_link($address,
                                     user_getrealname(user_getid($address)));
            }

          # No @, no real addresses and spaces inside? Looks like someone
          # forgot commas.
          if (!$realaddress && strpos($address, " "))
            {
              return htmlspecialchars($address).' <span class="warn">'
                ._("(address seems invalid and will probably be ignored)").'</span>';
            }

          # No @ but and not a login? P
          return htmlspecialchars($address).' <span class="warn">'
            .sprintf(
# TRANSLATORS: the argument is mail domain (like localhost or sv.gnu.org).
                    _("(address is unknown to Savane, will fail if not valid at %s)"),
                     $GLOBALS['sys_mail_domain']).'</span>';
        }

      # If we are here, it means that we have an @ in the address,
      # Even if the address is invalid, the system is likely to try to
      # send the mail, and we have no way to know if the address is valid.
      # We will only do a check on the address syntax.

      # We found a real address that is syntaxically correct.
      if ($realaddress && validate_email($realaddress))
        return '<a href="mailto:'.htmlspecialchars($realaddress).'">'
               .htmlspecialchars($address).'</a>';

      # We found real address but it does not seem correct.
      # Print a warning.
      if ($realaddress)
        {
          return htmlspecialchars($address).' <span class="warn">'
            ._("(address seems invalid and will probably be ignored)").'</span>';
        }
      # We have no realaddress found, only one string that is an address.
      if (validate_email($address))
        {
          return '<a href="mailto:'.htmlspecialchars($address).'">'
                 .htmlspecialchars($address).'</a>';
        }
      # Nothing was valid, print a warning.
      return htmlspecialchars($address).' <span class="warn">'
        ._("(address seems invalid and will probably be ignored)").'</span>';
    }
  if ($nohtml)
    return _("-email is unavailable-");
  return utils_help(_("-email is unavailable-"),
                    _("This information is not provided to anonymous users"),
                    1);
}

# Like the previous, but does no extended search, just print as it comes.
function utils_email_basic ($address, $nohtml=0)
{
  if (user_isloggedin() || CONTEXT == 'forum' || CONTEXT == 'news'
      || CONTEXT == ''/*frontpage*/)
    {
      if ($nohtml)
        return htmlspecialchars($address);
      # Make mailto without trying to find out whether it makes sense.
      return '<a href="mailto:'.htmlspecialchars($address).'">'
             .htmlspecialchars($address).'</a>';
    }

  if ($nohtml)
    return _("-email is unavailable-");
  return utils_help(_("-email is unavailable-"),
                    _("This information is not provided to anonymous users"),
                    1);
}

# Find out if a string is pure ASCII or not.
function utils_is_ascii ($string)
{
  return preg_match('%^(?: [\x09\x0A\x0D\x20-\x7E] )*$%xs', $string);
}

# Alias function.
function utils_altrow ($i)
{
  return html_get_alt_row_color ($i);
}

function utils_cutstring ($string, $length=35)
{
  $string = rtrim($string);
  if (strlen($string) > $length)
    {
      $string = substr($string, 0, $length);
      $string = substr($string, 0, strrpos($string, ' '));
      $string .= "...";
    }
  return $string;
}

# Same as the previous but is used for links and does not try to cut
# because it annoys users who can't safely.
# copy/paste a complete paragraph with the links intact (check
# 105807@sv).
function utils_cutlink ($string, $length=35)
{
  $url = $string;
  unset($help);
  return '<a href="'.$url.'">'.$string.'</a>';
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
#   - short => localized 18/11/2005, 18:51
#   - minimal => localized 18/11/2005
#
# @see utils_date_to_unixtime()
function utils_format_date($timestamp, $format="default")
{
  global $sys_datefmt;
  if ($timestamp == 0)
    {
      return '-';
    }

  # The installation configured a specific date format. This is not nice
  # this will prevent locales from being used.
  if ($sys_datefmt)
    {
      return strftime($sys_datefmt, $timestamp);
    }

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
        return strftime('%Y-%m-%d', $timestamp);
      }
    case 'natural':
      {
        if (time () - $timestamp < 12 * 60 * 60)
          $date_fmt = '%X'; # Time without date, for recent events.
        else
          $date_fmt = '%x'; # Date without time.
        return strftime($date_fmt, $timestamp);
      }
    case 'short':
    default:
      {
        # %c  The preferred date and time representation for the current locale.
        # Cf. strftime(3)
        return strftime('%c', $timestamp);
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
  $res = preg_match("/\s*(\d+)-(\d+)-(\d+)/",$date,$match);
  if ($res == 0)
    return array(0,false);
  list(,$year,$month,$day) = $match;
  $time = mktime(0, 0, 0, $month, $day, $year);
  dbg("DBG Matching date $date -> year $year, month $month,"
      ."day $day -> time = $time<br />");
  return array($time,true);
}

function utils_read_file($filename)
{
  @$fp = fopen($filename, "r");
  if ($fp)
    {
      $val = fread($fp, filesize($filename));
      fclose ($fp);
      return $val;
    }
  return false;
}

function utils_filesize($filename, $file_size=0)
{
  # If file size is defined, assume that we just want an unit conversion.

  # Round results: Savane is not a math software.
  if (!isset($file_size))
    $file_size = filesize($filename);

  if ($file_size >= 1048576)
    {
# TRANSLATORS: this expresses file size.
      $file_size = sprintf(_("%sMiB"), round($file_size / 1048576));
    }
  elseif ($file_size >= 1024)
    {
# TRANSLATORS: this expresses file size.
      $file_size = sprintf(_("%sKiB"), round($file_size / 1024));
    }
  else
    {
# TRANSLATORS: this expresses file size.
      $file_size = sprintf(_("%sB"), round($file_size));
    }
  return $file_size;
}

# Return human readable sizes.
# This is public domain, original version from:
# Author:      Aidan Lister <aidan@php.net>
# Version:     1.1.0
# Link:        http://aidanlister.com/repos/v/function.size_readable.php
# Param:       int    $size        Size
# Param:       int    $unit        The maximum unit
# Param:       int    $retstring   The return string format
# Param:       int    $si          Whether to use SI prefixes
function utils_size_readable($size, $unit = null, $retstring = null, $si = false)
{
  # Units.
  if ($si === true)
    {
      $sizes = array(
# TRANSLATORS: this is file size unit (no prefix).
                    _('B'),
# TRANSLATORS: this is file size unit (with SI prefix.)
                    _('kB'),
# TRANSLATORS: this is file size unit (with SI prefix.)
                    _('MB'),
# TRANSLATORS: this is file size unit (with SI prefix.)
                    _('GB'),
# TRANSLATORS: this is file size unit (with SI prefix.)
                    _('TB'),
# TRANSLATORS: this is file size unit (with SI prefix.)
                    _('PB'));
      $mod   = 1000;
    }
  else
    {
      $sizes = array(
# TRANSLATORS: this is file size unit (no prefix).
                    _('B'),
# TRANSLATORS: this is file size unit (with binary prefix.)
                    _('KiB'),
# TRANSLATORS: this is file size unit (with binary prefix.)
                    _('MiB'),
# TRANSLATORS: this is file size unit (with binary prefix.)
                    _('GiB'),
# TRANSLATORS: this is file size unit (with binary prefix.)
                    _('TiB'),
# TRANSLATORS: this is file size unit (with binary prefix.)
                    _('PiB'));
      $mod   = 1024;
    }
  $ii = count($sizes) - 1;

  # Find maximum unit applicable.
  $unit = array_search((string) $unit, $sizes);
  if ($unit === null || $unit === false)
    {
      $unit = $ii;
    }

  if ($retstring === null)
    {
      $retstring = '%01.2f%s';
    }
  $i = 0;
  while ($unit != $i && $size >= 1024 && $i < $ii)
    {
      $size /= $mod;
      $i++;
    }
  return sprintf($retstring, $size, $sizes[$i]);
}

function utils_fileextension($filename)
{
  $ext = substr(basename($filename), strrpos(basename($filename),".") + 1);
  if ($ext=='gz' || $ext=='bz2')
    {
      $ext = substr(basename($filename), strrpos(basename($filename),".") - 3);
    }
  if ($ext=='rpm')
    {
# TRANSLATORS: this is used in contexts like "rpm package for i386 (ix86)"
# or "source rpm package".
      $long_ext = _("rpm package");
    }
  if ($ext=='deb')
    {
# TRANSLATORS: this is used in contexts like "debian package for i386 (ix86)"
# or "source debian package".
      $long_ext = _("debian package");
    }
  if ($ext=='deb' || $ext=='rpm')
    {
      $arch_type = substr(basename($filename), strrpos(basename($filename),".") - 3);
      if ($arch_type == "src.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("source %s"), $long_ext);
        }
      if ($arch_type == "rch.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("arch independent %s"), $long_ext);
        }
      if ($arch_type == "386.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for i386 (ix86)"), $long_ext);
        }
      if ($arch_type == "586.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for i586"), $long_ext);
        }
      if ($arch_type == "686.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for i686"), $long_ext);
        }
      if ($arch_type == "a64.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for Itanium 64"), $long_ext);
        }
      if ($arch_type == "arc.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for Sparc"), $long_ext);
        }
      if ($arch_type == "pha.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for Alpha"), $long_ext);
        }
      if ($arch_type == "ppc.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for PowerPC"), $long_ext);
        }
      if ($arch_type == "390.".$ext)
        {
# TRANSLATORS: the argument is translation of either 'rpm package' or 'debian
# package'.
          $long_ext = sprintf(_("%s for s390"), $long_ext);
        }
      $ext = $long_ext;
    }
  return $ext;
}

function utils_prep_string_for_sendmail($body)
{
  $body=str_replace("\\","\\\\",$body);
  $body=str_replace("\"","\\\"",$body);
  $body=str_replace("\$","\\\$",$body);
  $body=str_replace("`","\\`",$body);
  return $body;
}

function utils_unconvert_htmlspecialchars($string)
{
  if (strlen($string) < 1)
    return '';
  $string=str_replace('&nbsp;',' ',$string);
  $string=str_replace('&quot;','"',$string);
  $string=str_replace('&gt;','>',$string);
  $string=str_replace('&lt;','<',$string);
  $string=str_replace('&amp;','&',$string);
  return $string;
}

function utils_remove_htmlheader($string)
{
  $string = eregi_replace(
    '(^.*<html[^>]*>.*<body[^>]*>)|(</body[^>]*>.*</html[^>]*>.*$)', '',
    $string);
  return $string;
}

# Take a result set and turn the optional column into an array.
function utils_result_column_to_array($result, $col=0, $localize=false)
{
  $rows=db_numrows($result);

  if ($rows > 0)
    {
      $arr=array();
      for ($i=0; $i<$rows; $i++)
        {
          $val = db_result($result,$i,$col);
          if ($localize)
            $val = gettext ($val);
          $arr[$i] = $val;
        }
    }
  else
    {
      $arr=array();
    }
  return $arr;
}

# backwards compatibility
function result_column_to_array($result, $col=0)
{
  return utils_result_column_to_array($result, $col);
}

function utils_wrap_find_space($string,$wrap)
{
  $start=$wrap-5;
  $try=1;
  $found=false;

  while (!$found)
    {
      #find the first space starting at $start
      $pos=@strpos($string,' ',$start);

      #if that space is too far over, go back and start more to the left
      if (($pos > ($wrap+5)) || !$pos)
        {
          $try++;
          $start=($wrap-($try*5));
          #if we've gotten so far left , just truncate the line
          if ($start<=10)
            {
              return $wrap;
            }
          $found=false;
        }
      else
        {
          $found=true;
        }
    }
  return $pos;
}

function utils_line_wrap ($text, $wrap = 78, $break = "\n")
{
  $paras = explode("\n", $text);
  $result = array();
  $i = 0;
  while ($i < count($paras))
    {
      if (strlen($paras[$i]) <= $wrap)
        {
          $result[] = $paras[$i];
          $i++;
        }
      else
        {
          $pos=utils_wrap_find_space($paras[$i],$wrap);

          $result[] = substr($paras[$i], 0, $pos);

          $new = trim(substr($paras[$i], $pos, strlen($paras[$i]) - $pos));
          if ($new != '')
            {
              $paras[$i] = $new;
              $pos=utils_wrap_find_space($paras[$i],$wrap);
            }
          else
            {
              $i++;
            }
        }
    }
  return implode($break, $result);
}

function utils_user_link ($username, $realname=false, $noneisanonymous=false)
{
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
  $re = '<a href="'.$GLOBALS['sys_home'].'users/'.$username.'">';
  if ($realname)
    {
      $re .= $realname." &lt;".$username."&gt;";
    }
  else
    {
      $re .= $username;
    }
  $re .= '</a>';
  return $re;
}

function utils_double_diff_array($arr1, $arr2)
{
  # First transform both arrays in hashes.
  reset($arr1); reset($arr2);
  while ( list(,$v) = each($arr1))
    $h1[$v] = $v;
  while ( list(,$v) = each($arr2))
    $h2[$v] = $v;

  $deleted = array();
  while ( list($k,) = each($h1))
    {
      if (!isset($h2[$k]))
        $deleted[] = $k;
    }

  $added = array();
  while ( list($k,) = each($h2))
    {
      if (!isset($h1[$k]))
        $added[] = $k;
    }
  return array($deleted, $added);
}

function utils_registration_history ($unix_group_name)
{
  # Meaningless with chrooted system; all www system should be chrooted.
}

function show_priority_colors_key()
{
  print '<p class="smaller">';
  print _("Open Items Priority Colors:")."<br />&nbsp;&nbsp;&nbsp;\n";

  for ($i=1; $i<10; $i++)
    {
      print '<span class="'.utils_get_priority_color($i).'">&nbsp;'.$i
            .'&nbsp;</span>'."\n";
    }

  print "<br />\n";
  print _("Closed Items Priority Colors:")."<br />&nbsp;&nbsp;&nbsp;\n";

  for ($i=11; $i<20; $i++)
    {
      print '<span class="'.utils_get_priority_color($i).'">&nbsp;'.($i-10)
            .'&nbsp;</span>'."\n";
    }
  print  "</p>\n";
}

function get_priority_color ($index, $closed="")
{
  return utils_get_priority_color($index, $closed);
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

# Return the translation for the given tracker, if available.
# Otherwise, return the input string.
function utils_get_tracker_name($tracker)
{
  switch ($tracker)
    {
      case 'bugs':
        $name = _('bugs');
        break;
      case 'cookbook':
        $name = _('recipes');
        break;
      case 'patch':
        $name = _('patches');
        break;
      case 'support':
        $name = _('support requests');
        break;
      case 'task':
        $name = _('tasks');
        break;
      default:
        $name = $tracker;
    }
  return $name;
}

function utils_get_priority_color ($index, $closed="")
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
function utils_show_result_set ($result,$title="Untitled",$linkify=false)
{
  global $group_id,$HTML;

  if ($title == "Untitled")
    $title = _("Untitled");

  if  ($result)
    {
      $rows  =  db_numrows($result);
      $cols  =  db_numfields($result);

      # Show title.
      print "<h4>$title</h4>\n";
      print '<table border="0" width="100%" summary="'.$title.'">'."\n";

      # Create the headers.
      print "<tr>\n";
      for ($i=0; $i < $cols; $i++)
        print '<th>'.db_fieldname($result,  $i)."</th>\n";
      print "</tr>\n";

      # Create the rows.
      for ($j = 0; $j < $rows; $j++)
        {
          print '<tr class="'. html_get_alt_row_color($j) .'">';
          for ($i = 0; $i < $cols; $i++)
            {
              $link = $linkend = '';
              if ($linkify && $i == 0)
                {
                  $link = '<a href="'.htmlentities ($_SERVER['PHP_SELF']).'?';
                  $linkend = '</a>';
                  switch ($linkify)
                    {
                    case "bug_cat":
                      $link .= 'group_id='.$group_id.'&bug_cat_mod=y&bug_cat_id='
                               .db_result($result, $j, 'bug_category_id').'">';
                      break;
                    case "bug_group":
                      $link .= 'group_id='.$group_id
                               .'&bug_group_mod=y&bug_group_id='
                               .db_result($result, $j, 'bug_group_id').'">';
                      break;
                    case "patch_cat":
                      $link .= 'group_id='.$group_id
                               .'&patch_cat_mod=y&patch_cat_id='
                               .db_result($result, $j, 'patch_category_id').'">';
                      break;
                    case "support_cat":
                      $link .= 'group_id='.$group_id
                               .'&support_cat_mod=y&support_cat_id='
                               .db_result($result, $j, 'support_category_id').'">';
                      break;
                    case "pm_project":
                      $link .= 'group_id='.$group_id
                               .'&project_cat_mod=y&project_cat_id='
                               .db_result($result, $j, 'group_project_id').'">';
                      break;
                    default:
                      $link = $linkend = '';
                   }
                }
              print '<td>'.$link . db_result($result,  $j,  $i) . $linkend
                    ."</td>\n";
            }
          print "</tr>\n";
        }
      print "</table>\n";
    }
  else # !($result)
    {
      print db_error();
    }
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
  return strtolower($addresses);
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
function utils_split_emails($addresses)
{
  $addresses = utils_cleanup_emails($addresses);
  $addresses = ereg_replace(";", ",", $addresses);
  return split(',',$addresses);
}

# Email Verification.
function validate_email ($address)
{
  # FIXME: this allows in domain names some characters that are not allowed
  return (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'
               . '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'
               . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $address));
}

# Verification of comma separated list of email addresses.
function validate_emails ($addresses)
{
  $arr = utils_split_emails($addresses);
  while (list(, $addr) = each ($arr))
    {
      if (!validate_email($addr))
        return false;
    }
  return true;
}

function utils_is_valid_filename ($file)
{
  if (ereg("[]~`! ~@#\"$%^,&*();=|[{}<>?/]",$file))
    return false;
  if (strstr($file,'..'))
    return false;
  return true;
}

# Add debugging information.
function util_debug ($msg)
{
  if ($GLOBALS['sys_debug_on'])
    {
      $backtrace = debug_backtrace(); // stacktrace
      $location = '';
      if (isset($backtrace[1]))
        {
          $location = $backtrace[1]['function'];
        }
      else {
        $relative_path = str_replace($GLOBALS['sys_www_topdir'].'/', '',
                                     $backtrace[0]['file']);
        $location = "$relative_path:{$backtrace[0]['line']}";
      }
      $GLOBALS['debug'] .= "(" . $location . ") $msg<br />";
    }
}

# alias
function dbg ($msg)
{
  if ($GLOBALS['sys_debug_on'])
    {
      $backtrace = debug_backtrace();
      $location = '';
      if (isset($backtrace[1]))
        {
          $location = $backtrace[1]['function'];
        }
      else
        {
          $relative_path = str_replace($GLOBALS['sys_www_topdir'].'/', '',
                                       $backtrace[0]['file']);
          $location = "$relative_path:{$backtrace[0]['line']}";
        }
      $GLOBALS['debug'] .= "(" . $location . ") $msg<br />";
    }
}

# Temporary debug.
# Use it instead of 'echo' so you can easily spot and remove them later after
# debugging is done.
function temp_dbg($msg)
{
  print '<pre>';
  debug_print_backtrace();
  var_dump($msg);
  print '</pre>';
}

# Die with debug information.
function util_die($msg)
{
  if ($GLOBALS['sys_debug_on'])
    {
      print '<pre>';
      print "<strong>Fatal error:</strong> $msg<br />";
      debug_print_backtrace();
      print '</pre>';
      die();
    }
  else
    {
      die($msg);
    }
}

/*
   Modified to print any given backtrace.
   Original comments:
   Replace debug_print_backtrace()

   @category    PHP
   @package     PHP_Compat
   @license     LGPL - http://www.gnu.org/licenses/lgpl.html
   @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
   @link        http://php.net/function.debug_print_backtrace
   @author      Laurent Laville <pear@laurent-laville.org>
   @author      Aidan Lister <aidan@php.net>
   @version     $Revision: 1.6 $
   @since       PHP 5
   @require     PHP 4.3.0 (debug_backtrace) */
function utils_debug_print_mybacktrace($backtrace=null)
{
  # Get backtrace.
  if ($backtrace === null)
    {
      $backtrace = debug_backtrace();
      # Unset call to debug_print_backtrace.
      array_shift($backtrace);
    }

  if (empty($backtrace))
    return '';

  # Iterate backtrace.
  $calls = array();
  foreach ($backtrace as $i => $call)
    {
      if (!isset($call['file']))
        $call['file'] = '(null)';
      if (!isset($call['line']))
        $call['line'] = '0';
      $location = $call['file'] . ':' . $call['line'];
      $function = (isset($call['class'])) ?
      $call['class'] . (isset($call['type']) ? $call['type'] : '.')
        . $call['function'] :
      $call['function'];

      $params = '';
      if (isset($call['args']))
        {
          $args = array();
          foreach ($call['args'] as $arg)
            {
              if (is_array($arg))
                $args[] = print_r($arg, true);
              elseif (is_object($arg))
                $args[] = get_class($arg);
              else
                $args[] = $arg;
            }
          $params = implode(', ', $args);
        }
      $calls[] = sprintf('#%d  %s(%s) called at [%s]',
                         $i,
                         $function,
                         $params,
                         $location);
    }
  echo implode("\n", $calls), "\n";
}

function util_feedback ($msg, $error=0)
{
  fb($msg, $error);
}

function feedback ($msg, $error=0)
{
  fb($msg, $error);
}

# Add feedback information.
function fb ($msg, $error=0)
{
  $GLOBALS['feedback_count']++;

  if ($GLOBALS['sys_debug_on'])
    {
      $msg .= ' [#'.$GLOBALS['feedback_count'].']';
      dbg("Add feedback #".$GLOBALS['feedback_count']);
    }
  $msg .= "\n";
  if (!$error)
    {
      $GLOBALS['feedback'] .= $msg;
    }
  else
    {
      $GLOBALS['ffeedback'] .= $msg;
    }
}

# Fb function to be used about database error when context of error is obvious.
function fb_dberror()
{
  fb(_("Error updating database"),1);
}

# Fb function to be used about database success when context is obvious.
function fb_dbsuccess()
{
  fb(_("Database successfully updated"));
}

function utils_help ($text, $explanation_array, $noarray=0)
{
  return help($text, $explanation_array, $noarray);
}

# Print help about a word.
#   $text  is the sentence where ballons are
#   $explanation_array is the table word->explanation, must be in the
#   array syntax.
function help ($text, $explanation_array, $noarray=0)
{
  if (!$noarray)
    {
      while (list($word,$explanation) = each($explanation_array))
        {
          $text = str_replace($word,
                              '<span class="help" title="'.$explanation.'">'
                              .$word.'</span>',
                              $text);
        }
      return $text;
    }
  return '<span class="help" title="'.$explanation_array.'">'.$text.'</span>';
}

# Analyse if we do need MSIE dirtyhacks.
# Put the result in cache so we wont over and over analyse user agent.
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
  if (isset($_SERVER['HTTP_USER_AGENT']))
    {
      $msie = strpos($_SERVER['HTTP_USER_AGENT'], "MSIE");
      if ($msie !== false)
        {
          # Avoid MSIE > 6: look for the first integer after the MSIE
          # string, in the next characters.
          $msie = substr($_SERVER['HTTP_USER_AGENT'], $msie, 10);
          preg_match("/MSIE (\d*)/", $msie, $msie_version);
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

function is_broken_msie()
{
  return utils_is_broken_msie();
}
?>
