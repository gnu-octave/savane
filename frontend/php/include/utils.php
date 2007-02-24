<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>,
#                          Tobias Toedter <t.toedter--gmx.net>
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

# Clean initialization for globals
$GLOBALS['feedback_count'] = 0;
$GLOBALS['feedback'] = '';
$GLOBALS['ffeedback'] = '';

function utils_safeinput ($string)
{
  return safeinput($string);
}


# This function permit including site specific content with ease
function utils_get_content ($file)
{
  if (is_file($GLOBALS['sys_incdir'].'/'.$file.'.'.$GLOBALS['locale']))
    {
      # there is localized version of the file :
      include($GLOBALS['sys_incdir'].'/'.$file.'.'.$GLOBALS['locale']);
    }
  elseif (is_file($GLOBALS['sys_incdir'].'/'.$file.'.txt'))
    {
      include($GLOBALS['sys_incdir'].'/'.$file.'.txt');
    }
  else
    {
      fb(sprintf(_("Warning: Savane was not able to read \"%s\" site-specific information, please contact administrators"), $file), 1);
    }
}

# Make sure that to avoid malicious file paths
function utils_check_path ($path)
{
  if (eregi(".*\.\.\/.*", $path))
    {
      exit_error('Error','Malformed url');
    }
}

# In a string, replace %PROJECT by the group_name
# (useful for group type configuration)
function utils_makereal ($data, $string="%PROJECT", $replacement=0)
{
  if (!$replacement)
    { $replacement = $GLOBALS['group_name']; }
  return ereg_replace($string, $replacement, $data);
}

# Add unavailable css class to a link if required
function utils_link ($url, $title, $defaultclass=0, $available=1, $help=0, $extra='')
{
  if (!$available)
    { $defaultclass = 'unavailable'; }

  $return = '<a href="'.$url.'"';

  if ($defaultclass)
    { $return .= ' class="'.$defaultclass.'"'; }
  if ($help)
    { $return .= ' title="'.$help.'"'; }
  if ($extra)
    { $return .= ' '.$extra; }
  $return .= '>'.$title.'</a>';
  return $return;
}

# Make an clean email link depending on the authentification level of the user,
# Dont use this on normal text, just on field where only an email address is
# expected. THis may corrupt the text and does extensive search.
function utils_email ($address, $nohtml=0)
{
  if (user_isloggedin())
    {
      if ($nohtml)
	{ return $address; }

      # Remove eventual extra white spaces
      $address = trim($address);

      # If we have < > in the address, only this content must go in the 
      # mailto
      unset($realaddress);
      if (preg_match("/\<([\w\d\-\@\.]*)\>/", $address, $matches))
	{ $realaddress = $matches[1]; }

      # We have a user name
      if (!strpos($address, "@"))
	{
          # We found a real address and it is a user login
	  if ($realaddress && user_exists(user_getid($realaddress)))
	    {
	      return utils_user_link($realaddress, user_getrealname(user_getid($realaddress)));  
	    }
	  
          # The whole address is a user login
	  if (user_exists(user_getid($address)))
	    {
	      return utils_user_link($address, user_getrealname(user_getid($address)));
	    }

	  # No @, no real addresses and spaces inside? Looks like someone
	  # forgot commas
	  if (!$realaddress && strpos($address, " "))
	    {
	      return htmlspecialchars($address).
		' <span class="warn">'._("(seems invalid and will probably be ignored)").'</span>';
	    }

	  # No @ but and not a login? P
	  return htmlspecialchars($address).
	    ' <span class="warn">'.sprintf(_("(unknown to Savane, will fail if not valid at %s)"), $GLOBALS['sys_mail_domain']).'</span>';

	}

      # If we are here, it means that we have an @ in the address,
      # Even if the address is invalid, the system is likely to try to
      # send the mail, and we have no way to know if the address is valid.
      # We will only do a check on the address syntax

      # We found a real address that is syntexically correct
      if ($realaddress && validate_email($realaddress))
	{
	  return '<a href="mailto:'.htmlspecialchars($realaddress).'">'.htmlspecialchars($address).'</a>';

	}

      # We found real address but it does not seem correct
      # Print a warning
      if ($realaddress)
	{
	  return htmlspecialchars($address).
	    ' <span class="warn">'._("(seems invalid and will probably be ignored)").'</span>';
	}


      # We have no realaddress found, only one string that is an address
      if (validate_email($address))
	{
	  return '<a href="mailto:'.htmlspecialchars($address).'">'.htmlspecialchars($address).'</a>';
	}
       
      # Nothing was valid, print a warning
      return htmlspecialchars($address).
	' <span class="warn">'._("(seems invalid and will probably be ignored)").'</span>';

    }
  else
    {
      if ($nohtml)
	{ return _("-unavailable-"); }

      return utils_help(_("-unavailable-"),
			_("This information is not provided to anonymous users"),
			1);
    }

}

# like the previous but does no extended search, just print as it comes
function utils_email_basic ($address, $nohtml=0)
{
  if (user_isloggedin())
    {
      if ($nohtml)
	{ return htmlspecialchars($address); }
	  
      
      # Make mailto without trying to find out whether it makes sense
      return '<a href="mailto:'.htmlspecialchars($address).'">'.htmlspecialchars($address).'</a>';
    }
  
  if ($nohtml)
    { return _("-unavailable-"); }
  
  return utils_help(_("-unavailable-"),
		    _("This information is not provided to anonymous users"),
		    1);

}
# Found out if a string is pure ASCII or not
function utils_is_ascii ($string)
{
  return preg_match('%^(?: [\x09\x0A\x0D\x20-\x7E] )*$%xs', $string);
}

# Alias function
function utils_altrow ($i)
{
  return html_get_alt_row_color ($i);
}

function utils_cutstring ($string, $lenght=35)
{
  $string = rtrim($string);
  if (strlen($string) > $lenght)
    {
      $string = substr($string, 0, $lenght);
      $string = substr($string, 0, strrpos($string, ' '));
      $string .= "...";
    }
  return $string;
}

# Same as the previous but does not try to cut after a white space
# (useful to cut a string with one long word, like URL)
function utils_cutlink ($string, $lenght=35)
{
  $url = $string;
  unset($help);
  # In printer mode, return as it were, because the link must be intact on
  # the printout
  if (!sane_all("printer") && strlen($string) > $lenght)
    { 
      $string = substr($string, 0, $lenght)."..."; 
      $help = ' title="'.$url.'"';
    }
  
  return '<a href="'.$url.'"'.$help.'>'.$string.'</a>';
}


##
# Returns a formatted date for a unix timestamp
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
  # this will prevent locales from being used
  if ($sys_datefmt)
    {
      return strftime($sys_datefmt, $timestamp);
    }

  ## Go at task #2614 to discuss about this
  # Used by default
  switch ($format)
    {
    case 'short':
      {
	# To be used in tables, nowhere else
	return strftime('%a %x, %R', $timestamp);
      }
    case 'minimal':
      {
        # To be used where place is really lacking, like in feature boxes.
        # (Nowhere else, it is too uninformative)
	return strftime('%x', $timestamp);
      }
    default:
      {
        # Used by default
	# Mention timezone to non-logged in users or in printer mode.
	# Logged-in users have this as account setting, so we can assume they
	# know and dont want time wasted by that
	if (user_isloggedin() && !defined('PRINTER'))
	  {
	    return strftime('%A %x '._("at").' %R', $timestamp);
	  }
	else
	  {
	    return strftime('%A %x '._("at").' %R %Z', $timestamp);
	  }
      }
    }

  return false;
}


# Convert a date as used in the bug tracking system and other services (YYYY-MM-DD)
# into a Unix time
# Returns a list with two values: the unix time and a boolean saying whether the conversion
# went well (true) or bad (false)
function utils_date_to_unixtime ($date)
{
  $res = preg_match("/\s*(\d+)-(\d+)-(\d+)/",$date,$match);
  if ($res == 0)
    { return array(0,false); }
  list(,$year,$month,$day) = $match;
  $time = mktime(0, 0, 0, $month, $day, $year);
  dbg("DBG Matching date $date -> year $year, month $month,day $day -> time = $time<br />");
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

  # Round results: Savane is not a math software

  if (!$file_size)
    {   $file_size = filesize($filename); }

  if ($file_size >= 1048576)
    {
      $file_size = sprintf(_("%sMB"), round($file_size / 1048576));
    }
  elseif ($file_size >= 1024)
    {
      $file_size = sprintf(_("%skB"), round($file_size / 1024));
    }
  else
    {
      $file_size = sprintf(_("%sB"), round($file_size));
    }

  return $file_size;
}

function utils_fileextension($filename)
{

  $ext = substr(basename($filename), strrpos(basename($filename),".") + 1);
  if ($ext==gz || $ext==bz2)
    {
      $ext = substr(basename($filename), strrpos(basename($filename),".") - 3);
    }
  if ($ext==rpm)
    {
      $long_ext = _("rpm package");
    }
  if ($ext==deb)
    {
      $long_ext = _("debian package");
    }
  if ($ext==deb || $ext==rpm)
    {
      $arch_type = substr(basename($filename), strrpos(basename($filename),".") - 3);
      if ($arch_type == "src.".$ext)
	{
	  $long_ext = sprintf(_("source %s"), $long_ext);
	}
      if ($arch_type == "rch.".$ext)
	{
	  $long_ext = sprintf(_("arch independant %s"), $long_ext);
	}
      if ($arch_type == "386.".$ext)
	{
	  $long_ext = sprintf(_("%s for i386 (ix86)"), $long_ext);
	}
      if ($arch_type == "586.".$ext)
	{
	  $long_ext = sprintf(_("%s for i586"), $long_ext);
	}
      if ($arch_type == "686.".$ext)
	{
	  $long_ext = sprintf(_("%s for i686"), $long_ext);
	}
      if ($arch_type == "a64.".$ext)
	{
	  $long_ext = sprintf(_("%s for Itanium 64"), $long_ext);
	}
      if ($arch_type == "arc.".$ext)
	{
	  $long_ext = sprintf(_("%s for Sparc"), $long_ext);
	}
      if ($arch_type == "pha.".$ext)
	{
	  $long_ext = sprintf(_("%s for Alpha"), $long_ext);
	}
      if ($arch_type == "ppc.".$ext)
	{
	  $long_ext = sprintf(_("%s for PowerPC"), $long_ext);
	}
      if ($arch_type == "390.".$ext)
	{
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
    {
      return '';
    }
  else
    {
      $string=str_replace('&nbsp;',' ',$string);
      $string=str_replace('&quot;','"',$string);
      $string=str_replace('&gt;','>',$string);
      $string=str_replace('&lt;','<',$string);
      $string=str_replace('&amp;','&',$string);
      return $string;
    }
}

function utils_remove_htmlheader($string)
{
  $string = eregi_replace('(^.*<html[^>]*>.*<body[^>]*>)|(</body[^>]*>.*</html[^>]*>.*$)', '', $string);
  return $string;
}

function utils_result_column_to_array($result, $col=0)
{
  /*
		Takes a result set and turns the optional column into
		an array
  */
  $rows=db_numrows($result);

  if ($rows > 0)
    {
      $arr=array();
      for ($i=0; $i<$rows; $i++)
	{
	  $arr[$i]=db_result($result,$i,$col);
	}
    }
  else
    {
      $arr=array();
    }
  return $arr;
}

function result_column_to_array($result, $col=0)
{
  /*
		backwards compatibility
  */
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

function utils_make_links($data='', $deprecated=0)
{
#  fb("Using deprecated function utils_make_links(), please post a bug report", 1);
  # Group_id may be necessary for recipe #nnn links
  global $group_id;

  if ($group_id)
    {
      $comingfrom = "&amp;comingfrom=$group_id";
    }

  if (empty($data))
    { return $data; }

  $lines = split("\n",$data);
  foreach ($lines as $key => $line)
    {
      # 1 and 2 could maybe be replaced by a better regexp, as they may in
      # some very rare case, break content. But we havent found such case.
      # Feel free to propose better regexp.

      # 1. Don't mess with HTML links already there by escaping
      # the protocol separator with ":##"
      $line = eregi_replace("(<a href=\"[a-z]+)://([^<[:space:]]+)://([^<[:space:]]+)</a>", '\1:##\2:##\3</a>', $line);
      $line = eregi_replace("(<a href=\"[a-z]+)://([^<\"[:space:]]+)\"", '\1:##\2"', $line);

      # 2. Dont mess with links surrounded by < >
      # (Which are in fact html special chars)
      $line = str_replace('&gt;', ' ##>', $line);
      $line = str_replace('&lt;', '<## ', $line);

      # Make usual links: prefix every "www." with "http://"
      $line = eregi_replace("(^|[[:space:]]+)www\.","\\1http://www.",$line);

      # replace the @ sign with an HTML entity, if it is used within
      # an url (e.g. for pointers to mailing lists). This way, the
      # @ sign doesn't get mangled in the e-mail markup code
      # below. See bug #2689 on http://gna.org/ for reference.
      $line = eregi_replace("([a-z]+://[^<>[:space:]]+)@", "\\1&#64;", $line);

      # Don't mess with HTML links already there by escaping
      # the protocol separator with ":##"
      $line = eregi_replace("(<a href=\"[a-z]+)://([^<[:space:]]+)://([^<[:space:]]+)</a>", '\1:##\2:##\3</a>', $line);
      $line = eregi_replace("(<a href=\"[a-z]+)://([^<\"[:space:]]+)\"", '\1:##\2"', $line);

      # do a markup for normal links, e.g. http://test.org
      $line = eregi_replace("([a-z]+://[^<>[:space:]]+[a-z0-9/]+)", '<a href="\1">\1</a>', $line);

      # do a markup for mail links, e.g. info@support.org
      $line = eregi_replace("([a-z0-9_+-.]+@([a-z0-9_+-]+\.)+[a-z]+)", '<a href="mailto:\1">\1</a>', $line);

      # Revert the escaping of already provided HTML links, done above
      $line = str_replace(":##", "://", $line);
      $line = str_replace(' ##>', '&gt;', $line);
      $line = str_replace('<## ', '&lt;', $line);


      # Links between items
      # FIXME: it should be i18n, but in a clever way, meaning that everytime
      # a form is submitted with such string, the string get converted in
      # english so we always get the links found without having a regexp
      # including every possible language.
      $line = eregi_replace("(bugs|bug)[ ]?#([0-9]+)", '<a href="'.$GLOBALS['sys_home']."bugs/?func=detailitem&amp;item_id=\\2\" class=\"italic\">bug&nbsp;#\\2</a>", $line);
      $line = eregi_replace("(support|sr)[ ]?#([0-9]+)", '<a href="'.$GLOBALS['sys_home']."support/?func=detailitem&amp;item_id=\\2\" class=\"italic\">sr&nbsp;#\\2</a>", $line);
      $line = eregi_replace("task[ ]?#([0-9]+)", '<a href="'.$GLOBALS['sys_home']."task/?func=detailitem&amp;item_id=\\1\" class=\"italic\">task&nbsp;#\\1</a>", $line);
      $line = eregi_replace("(recipe|rcp)[ ]?#([0-9]+)", '<a href="'.$GLOBALS['sys_home']."cookbook/?func=detailitem$comingfrom&amp;item_id=\\2\" class=\"italic\">recipe&nbsp;#\\2</a>", $line);
      $line = eregi_replace("patch[ ]?#([0-9]+)", '<a href="'.$GLOBALS['sys_home']."patch/?func=detailitem&amp;item_id=\\1\" class=\"italic\">patch&nbsp;#\\1</a>", $line);
      # In this case, we make the link pointing to support, it wont matter,
      # the download page is in every tracker and does not check if the tracker
      # is actually used
      $line = eregi_replace("file[ ]?#([0-9]+)", '<a href="'.$GLOBALS['sys_home']."support/download.php?file_id=\\1\" class=\"italic\">file&nbsp;#\\1</a>", $line);
      $line = eregi_replace("comment[ ]?#([0-9]+)", '<a href="#comment\\1">comment&nbsp;#\\1</a>', $line);

      $lines[$key] = $line;
    }
  return join("\n", $lines);
}

function utils_user_link ($username, $realname=false, $noneisanonymous=false)
{
  if ($username == 'None' || empty($username))
    {
      
      # Would be nice to always return _("Anonymous"); but in some cases it is
      # really none (assigned_to).
      if (!$noneisanonymous)
	{ return $username; }
      else
	{ return _("Anonymous"); }

    }
  else
    {
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
}

function utils_double_diff_array($arr1, $arr2)
{
  # first transform both arrays in hashes
  reset($arr1); reset($arr2);
  while ( list(,$v) = each($arr1))
    { $h1[$v] = $v; }
  while ( list(,$v) = each($arr2))
    { $h2[$v] = $v; }

  $deleted = array();
  while ( list($k,) = each($h1))
    {
      if (!isset($h2[$k]))
	{ $deleted[] = $k; }
    }

  $added = array();
  while ( list($k,) = each($h2))
    {
      if (!isset($h1[$k]))
	{ $added[] = $k; }
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
      print '<span class="'.utils_get_priority_color($i).'">&nbsp;'.$i.'&nbsp;</span>'."\n";
    }

  print '<br />';
  print _("Closed Items Priority Colors:")."<br />&nbsp;&nbsp;&nbsp;\n";

  for ($i=11; $i<20; $i++)
    {
      print '<span class="'.utils_get_priority_color($i).'">&nbsp;'.($i-10).'&nbsp;</span>'."\n";
    }

  print  "</p>";
}

function get_priority_color ($index, $closed="")
{ return utils_get_priority_color($index, $closed); }


function utils_get_tracker_icon ($tracker)
{
  if ($tracker == "bugs")
    { return "bug"; }
  if ($tracker == "support")
    { return "help"; }
  if ($tracker == "cookbook")
    { return "man"; }
  return $tracker;
}

function utils_get_tracker_prefix ($tracker)
{
  if ($tracker == "bugs")
    { return "bug"; }
  if ($tracker == "support")
    { return "sr"; }
  if ($tracker == "cookbook")
    { return "recipe"; }
  return $tracker;
}



##
# Returns the translation for the given tracker, if available.
# Otherwise, return the input string.
#
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
  # If the item is closed, add ten to the index number to get closed colors
  if ($closed == 3)
    { $index = $index + 10; }

  return $bgpri[$index];
}

function build_priority_select_box ($name="priority", $checked_val="5")
{
  /*
		Return a select box of standard priorities.
		The name of this select box is optional and so is the default checked value
  */

  print "<select name=\"$name\">\n";
  print '<option value="1"'.($checked_val=="1" ?" selected":"").'>1 - '._("Lowest").'</option>';
  print '<option value="2"'.($checked_val=="2" ?" selected":"").'>2</option>';
  print '<option value="3"'.($checked_val=="3" ?" selected":"").'>3</option>';
  print '<option value="4"'.($checked_val=="4" ?" selected":"").'>4</option>';
  print '<option value="5"'.($checked_val=="5" ?" selected":"").'>5 - '._("Medium").'</option>';
  print '<option value="6"'.($checked_val=="6" ?" selected":"").'>6</option>';
  print '<option value="7"'.($checked_val=="7" ?" selected":"").'>7</option>';
  print '<option value="8"'.($checked_val=="8" ?" selected":"").'>8</option>';
  print '<option value="9"'.($checked_val=="9" ?" selected":"").'>9 - '._("Highest").'</option>';
  print '</select>';


}

# ########################################### checkbox array
# ################# mostly for group languages and environments

function utils_buildcheckboxarray($options,$name,$checked_array)
{
  $option_count=count($options);
  $checked_count=count($checked_array);

  for ($i=1; $i<=$option_count; $i++)
    {
      print '
			<BR><INPUT type="checkbox" name="'.$name.'" value="'.$i.'"';
      for ($j=0; $j<$checked_count; $j++)
	{
	  if ($i == $checked_array[$j])
	    {
	      print ' CHECKED';
	    }
	}
      print '> '.$options[$i];
    }
}

# deprecated name
function GraphResult($result,$title)
{
  utils_graph_result($result, $title);
}

# DEPRECATED:
function utils_graph_result ($result,$title)
{

  /*
	GraphResult by Tim Perdue, PHPBuilder.com

	Takes a database result set.
	The first column should be the name,
	and the second column should be the values

	####
	####   Be sure to include (HTML_Graphs.php) before hitting these graphing functions
	####
  */

  /*
		db_ should be replaced with your database, aka mysql_ or pg_
  */
  $rows=db_numrows($result);

  if ((!$result) || ($rows < 1))
    {
      print 'None Found.';
    }
  else
    {
      $names=array();
      $values=array();

      for ($j=0; $j<db_numrows($result); $j++)
	{
	  if (db_result($result, $j, 0) != '' && db_result($result, $j, 1) != '' )
	    {
	      $names[$j]= db_result($result, $j, 0);
	      $values[$j]= db_result($result, $j, 1);
	    }
	}

      /*
		This is another function detailed below
      */
      GraphIt($names,$values,$title);
    }
}


# DEPRECATED: deprecated name
function GraphIt ($name_string,$value_string,$title)
{
  utils_graph_it($name_string,$value_string,$title);
}


# DEPRECATED:
function utils_graph_it ($name_string,$value_string,$title)
{

  /*
		GraphIt by Tim Perdue, PHPBuilder.com
  */
  $counter=count($name_string);

  /*
		Can choose any color you wish
  */
  $bars=array();

  for ($i = 0; $i < $counter; $i++)
    {
      $bars[$i]=$GLOBALS['COLOR_LTBACK1'];
    }

  $counter=count($value_string);

  /*
		Figure the max_value passed in, so scale can be determined
  */

  $max_value=0;

  for ($i = 0; $i < $counter; $i++)
    {
      if ($value_string[$i] > $max_value)
	{
	  $max_value=$value_string[$i];
	}
    }

  if ($max_value < 1)
    {
      $max_value=1;
    }

  /*
		I want my graphs all to be 800 pixels wide, so that is my divisor
  */

  $scale=(400/$max_value);

  /*
		I create a wrapper table around the graph that holds the title
  */

  $title_arr=array();
  $title_arr[]=$title;

  print html_build_list_table_top ($title_arr);
  print '<TR><TD>';
  /*
		Create an associate array to pass in. I leave most of it blank
  */

  $vals =  array(
		 'vlabel'=>'',
		 'hlabel'=>'',
		 'type'=>'',
		 'cellpadding'=>'',
		 'cellspacing'=>'0',
		 'border'=>'',
		 'width'=>'',
		 'background'=>'',
		 'vfcolor'=>'',
		 'hfcolor'=>'',
		 'vbgcolor'=>'',
		 'hbgcolor'=>'',
		 'vfstyle'=>'',
		 'hfstyle'=>'',
		 'noshowvals'=>'',
		 'scale'=>$scale,
		 'namebgcolor'=>'',
		 'valuebgcolor'=>'',
		 'namefcolor'=>'',
		 'valuefcolor'=>'',
		 'namefstyle'=>'',
		 'valuefstyle'=>'',
		 'doublefcolor'=>'');

  /*
		This is the actual call to the HTML_Graphs class
  */

  html_graph($name_string,$value_string,$bars,$vals);

  print '
		</TD></TR></TABLE>
		<!-- end outer graph table -->';
}



function utils_show_result_set ($result,$title="Untitled",$linkify=false)
{
  global $group_id,$HTML;
  /*
		Very simple, plain way to show a generic result set
		Accepts a result set and title
		Makes certain items into HTML links
  */

  if  ($result)  {
    $rows  =  db_numrows($result);
    $cols  =  db_numfields($result);

    # Show title
    print "<h4>$title</h4>\n";
    print '<table border="0" width="100%" summary="'.$title.'">'."\n";

    /*  Create  the  headers  */
    print "<tr>\n";
    for ($i=0; $i < $cols; $i++)
      {
	print '<th>'.db_fieldname($result,  $i)."</th>\n";
      }
    print "</tr>\n";

    /*  Create the rows  */
    for ($j = 0; $j < $rows; $j++)
      {
	print '<tr class="'. html_get_alt_row_color($j) .'">';
	for ($i = 0; $i < $cols; $i++)
	  {
	    if ($linkify && $i == 0)
	      {
		$link = '<a href="'.$_SERVER['PHP_SELF'].'?';
		$linkend = '</a>';
		if ($linkify == "bug_cat")
		  {
		    $link .= 'group_id='.$group_id.'&bug_cat_mod=y&bug_cat_id='.db_result($result, $j, 'bug_category_id').'">';
		  } else if($linkify == "bug_group")
		    {
		      $link .= 'group_id='.$group_id.'&bug_group_mod=y&bug_group_id='.db_result($result, $j, 'bug_group_id').'">';
		    } else if($linkify == "patch_cat")
		      {
			$link .= 'group_id='.$group_id.'&patch_cat_mod=y&patch_cat_id='.db_result($result, $j, 'patch_category_id').'">';
		      } else if($linkify == "support_cat")
			{
			  $link .= 'group_id='.$group_id.'&support_cat_mod=y&support_cat_id='.db_result($result, $j, 'support_category_id').'">';
			} else if($linkify == "pm_project")
			  {
			    $link .= 'group_id='.$group_id.'&project_cat_mod=y&project_cat_id='.db_result($result, $j, 'group_project_id').'">';
			  }
		else
		  {
		    $link = $linkend = '';
		  }
	      }
	    else
	      {
		$link = $linkend = '';
	      }
	    print '<td>'.$link . db_result($result,  $j,  $i) . $linkend.'</td>';

	  }
	print '</tr>';
      }
    print "</table>\n";
  }
  else
    {
      print db_error();
    }
}


# Clean up email address (remove spaces...) and put to lower case
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
# login name
function utils_normalize_email ($address)
{
  $address = utils_cleanup_emails($address);
  if (validate_email($address))
    return $address;
  else
    return $address."@".$GLOBALS['sys_mail_domain'];
}


# Clean up email address (remove spaces...) and split comma separated emails
function utils_split_emails($addresses)
{
  $addresses = utils_cleanup_emails($addresses);
  $addresses = ereg_replace(";", ",", $addresses);
  return split(',',$addresses);
}

# Email Verification
function validate_email ($address)
{
  # FIXME: this allows in domain names some characters that are not allowed
  return (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'. '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $address));
}

# Verification of comma separated list of email addresses
function validate_emails ($addresses)
{
  $arr = utils_split_emails($addresses);
  while (list(, $addr) = each ($arr))
    {
      if (!validate_email($addr))
	{ return false;}
    }
  return true;
}

function utils_is_valid_filename ($file)
{
  if (ereg("[]~`! ~@#\"$%^,&*();=|[{}<>?/]",$file))
    {
      return false;
    }
  else
    {
      if (strstr($file,'..'))
	{
	  return false;
	}
      else
	{
	  return true;
	}
    }
}

# alias
function util_debug ($msg)
{
  dbg($msg);
}

# alias
function debug ($msg)
{
  dbg($msg);
}

# add debugging information
function dbg ($msg)
{
  if ($GLOBALS['sys_debug_on'])
    {
      $GLOBALS['debug'] .= "<br /><br />latest func called: ".ereg_replace($_SERVER["DOCUMENT_ROOT"].$GLOBALS['sys_home'], "",$GLOBALS['sys_debug_where']);
      $GLOBALS['debug'] .= "<br />msg: $msg";
    }
}

# temporary debug
# use it instead of 'echo' so you can easily spot and remove them later after debugging is done
function temp_dbg($msg) {
  echo $msg;
}


# alias
function util_feedback ($msg, $error=0)
{
  fb($msg, $error);
}

# alias
function feedback ($msg, $error=0)
{
  fb($msg, $error);
}

# add feedback information
function fb ($msg, $error=0)
{
  # Increment feedback count
  $GLOBALS['feedback_count']++;

  if ($GLOBALS['sys_debug_on'])
    {
      $msg .= ' [#'.$GLOBALS['feedback_count'].']';
      dbg("Add feedback #".$GLOBALS['feedback_count']);
    }

  $msg .= '<br />';

  # feed
  if (!$error)
    {
      $GLOBALS['feedback'] .= $msg;
    }
  else
    {
      $GLOBALS['ffeedback'] .= $msg;
    }
}

# fb function to be used about database error when context of error is obvious
function fb_dberror()
{
  fb(_("Error updating database"),1);
}

# fb function to be used about database error when context of error is obvious
function fb_dbsuccess()
{
  fb(_("Database successfully updated"));
}


# alias
function utils_help ($text, $explanation_array, $noarray=0)
{
  return help($text, $explanation_array, $noarray);
}

# print help about a word
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
			      '<span class="help" title="'.$explanation.'">'.$word.'</span>',
			      $text);
	}
      return $text;
    }


  return '<span class="help" title="'.$explanation_array.'">'.$text.'</span>';

}

# Analyse if we do need MSIE dirtyhacks
# Put the result in cache so we wont over and over analyse user agent.
# (This function will indeed think a browser that claims to be MSIE that it is
# MSIE. Users of browsers like Opera that pretend to be MSIE should configure
# properly their User Agent. There is nothing else to do about it)
function utils_is_broken_msie ()
{
  # If already set, return what we found
  if (isset($GLOBALS['are_we_using_broken_msie']))
    {
      return $GLOBALS['are_we_using_broken_msie'];
    }
  
  # Otherwise, find out, assuming that by default we dont use broken MSIE
  $is_broken = false;

  # Try to find the string MSIE
  $msie = strpos($_SERVER['HTTP_USER_AGENT'], "MSIE");
  if ($msie)
    { 
      # Avoid MSIE > 6: look for the first integer after the MSIE string,
      # in the next characters
      $msie = substr($_SERVER['HTTP_USER_AGENT'], $msie, 10);
      preg_match("/MSIE (\d*)/", $msie, $msie_version);
      if ($msie_version[1] < 7)
	{
	  $is_broken = true;
	}     

    }
  
  # Save for later
  $GLOBALS['are_we_using_broken_msie'] = $is_broken;

  # Return the result
  return $is_broken;
}

# alias 
function is_broken_msie()
{
  return utils_is_broken_msie();
}
