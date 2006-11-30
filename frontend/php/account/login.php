<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

Header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
Header("Cache-Control: no-cache");
Header("Cache-Control: must-revalidate");

require '../include/pre.php';
require '../include/account.php';

register_globals_off();
$from_brother = sane_all('from_brother');

# Block here potential robots
dnsbl_check();

# Logged users have no business here
if (user_isloggedin() && !$from_brother)
{ session_redirect($GLOBALS['sys_home']."my/"); }

# Input checks
$form_loginname = sane_all("form_loginname");
$form_pw = sane_all("form_pw");
$cookie_for_a_year = sane_all("cookie_for_a_year");
$stay_in_ssl = sane_all("stay_in_ssl");
$brotherhood = sane_all("brotherhood");
$uri = sane_all("uri");
$login = sane_all("login");
$cookie_test = sane_all("cookie_test");


if ($GLOBALS['sys_https_host'] != "" && !session_issecure())
{
  # Force use of TLS for login
  header('Location: '.$GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'account/login.php?uri='.$uri);
}

# Check cookie support
if (!$from_brother and !isset($_COOKIE["cookie_probe"]))
{
  if (!$cookie_test)
    {
    // Attempt to set a cookie to go to a new page to see if the client will indeed send that cookie.
    session_cookie('cookie_probe', 1);
    header('Location: '.$GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'account/login.php?uri='.$uri.'&cookie_test=1');
    }
  else # 
    {
      fb(sprintf(_("Savane thinks your cookie are not activated for %s. To log-in, we need you to activate cookies in your web browser for this website. Please do so and click here:"), $sys_default_domain).' '.$GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'account/login.php?uri='.$uri, 1);
    }
}

if (sane_all("login"))
{
  if ($from_brother) 
  {
    $session_uid  = sane_get("session_uid");
    if (!ctype_digit($session_uid))
      { exit("Invalid session_uid"); }
    $session_hash = sane_get("session_hash");
    if (!ctype_alnum($session_hash))
      { exit("Invalid session_hash"); }
  }

  if (isset($session_uid) and session_exists($session_uid, $session_hash)) 
  {
    $GLOBALS['session_hash'] = $session_hash;
    session_set_new_cookies($session_uid, $cookie_for_a_year, $stay_in_ssl);
    $success = 1;
  } 
  else 
  {
    $success = session_login_valid($form_loginname, $form_pw, 0, $cookie_for_a_year, 0, $stay_in_ssl);
  }

  if ($success)
    {
      # Set up the theme, if the user has selected any in the user
      # preferences -- but give priority to a cookie, if set.
      if (!isset($_COOKIE["SV_THEME"]))
        {
          $theme_result = user_get_result_set(user_getid());
          $theme = db_result($theme_result, 0, 'theme');
          if (strlen($theme) > 0)
            {
              setcookie('SV_THEME', $theme, time() + 60*60*24,
                $GLOBALS['sys_home'], $GLOBALS['sys_default_domain']);
            }
	}

      # Optionally stay in TLS mode
      if ($GLOBALS['sys_https_host'] != "" && $stay_in_ssl)
	{ $http = "https"; }
      else
	{ $http = "http"; }

      # If a brother server exists, login there too, if we are not
      # already coming from there
      if ($GLOBALS['sys_brother_domain'] && $brotherhood)
	{
	  if (!$from_brother)
	    {
	      # Go there saying hello to your brother
	      header ("Location: ".$http."://".$GLOBALS['sys_brother_domain'].$GLOBALS['sys_home']."/account/login.php?form_loginname=$form_loginname&form_pw=$form_pw&session_uid=".user_getid()."&session_hash=".$GLOBALS['session_hash']."&cookie_for_a_year=$cookie_for_a_year&from_brother=1&login=1&stay_in_ssl=$stay_in_ssl&brotherhood=1&uri=".urlencode($uri));
	      exit;
	    }
	  else
	    {
	      # We return to our brother 'my', where we login originally,
              # unless we are request to go to an uri
              if (!$uri) 
                {            
	         header ("Location: ".$http."://".$GLOBALS['sys_brother_domain'].$GLOBALS['sys_home']."my/");
                }
              else
                {
	         header ("Location: ".$http."://".$GLOBALS['sys_brother_domain'].$uri);
                }
	      exit;
	    }
	}
      else
	{
	  # If No brother server exists, just go to 'my' page 
          # unless we are request to go to an uri
          if (!$uri) 
           {  
	      header ("Location: ".$http."://".$GLOBALS['sys_default_domain'].$GLOBALS['sys_home']."my/");
           }
          else
           {
	      header ("Location: ".$http."://".$GLOBALS['sys_default_domain'].$uri);
           }
	  exit;
	}

    }
}

if ($session_hash)
{
   # Nuke their old session securely. 
   session_delete_cookie('session_hash');
   db_query("DELETE FROM session WHERE session_hash='$session_hash' AND user='$user_id'");
}


site_header(array('title'=>_("Login")));

if (sane_all("login") && !$success)
{

  if ("Account Pending" == $feedback)
    {

      print '<h3>'._("Pending Account").'</h3>';
      print '<p>'._("Your account is currently pending your email confirmation. Visiting the link sent to you in this email will activate your account.").'</p>';
      print '<p>'._("If you need this email resent, please click below and a confirmation email will be sent to the email address you provided in registration.").'</p>';
      print '<p><a href="pending-resend.php?form_user='.$form_loginname.'">['._("Resend Confirmation Email").']</a></p>';

    }
  else
    {
      # print helpful error message
      print '<div class="splitright"><div class="boxitem">';
      print '<div class="warn">'._("Troubleshooting:").'</div></div><ul class="boxli">'.
	'<li class="boxitemalt">'._("Is the \"Caps Lock\" or \"A\" light on your keyboard on?").'<br />'._("If so, hit \"Caps Lock\" key before trying again.").'</li>'.
	'<li class="boxitem">'._("Did you forget or misspell your password?").'<br />'.utils_link('lostpw.php', _("You can recover your password using the lost password form.")).'</li>'.
	'<li class="boxitemalt">'._("Still having trouble?").'<br />'.utils_link($GLOBALS['sys_home'].'support/?group='.$GLOBALS['sys_unix_group_name'],  _("Fill a support request.")).'</li>';
      print '</ul></div>';
    }

}

if (session_issecure())
{
  print '<p>'._("You're going to be connected with a secure (https) server and your password will not be visible to other users.").'</p>';

}
print '<form action="'.$GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'account/login.php" method="post">';
print '<input type="hidden" name="uri" value="'.$uri.'" />';

# Shortcuts to New Account and Lost Password have a tabindex superior to 
# the rest of form, 
# so they dont mess with the normal order when you press TAB on the keyboard
# (login -> password -> post)
print '<p><span class="preinput">'._("Login Name:").'</span><br />&nbsp;&nbsp;';
print '<input type="text" name="form_loginname" value="'.$form_loginname.'" tabindex="1" /> <a class="smaller" href="register.php" tabindex="2">['._("No account yet?").']</a></p>';

print '<p><span class="preinput">'._("Password:").'</span><br />&nbsp;&nbsp;';
print '<input type="password" name="form_pw" tabindex="1" /> <a class="smaller" href="lostpw.php" tabindex="2">['._("Lost your password?").']</a></p>';

if (session_issecure())
{

  $checked = 'checked="checked" ';
  if ($login and !$stay_in_ssl)
    { $checked = ''; }

  print '<p><input type="checkbox" name="stay_in_ssl" value="1" tabindex="1" '.$checked.'/><span class="preinput">';
  print _("Stay in secure (https) mode after login")."</span><br />\n";
  print '<span class="text">'._("Lynx, Emacs w3 and Microsoft Internet Explorer users will have intermittent https problems, so they should leave https after login. Gecko-based browser (Mozilla, Galeon, Netscape...) and Konqueror users should stay in https mode permanently for maximum security.").'</span></p>';
}
else
{
  print '<p class="warn"><input type="hidden" name="stay_in_ssl" value="0" />';
  print _("This server does not encrypt data (no https), so the password you sent may be viewed by other people. Do not use any important passwords.").'</p>';
}

$checked = '';
if ($cookie_for_a_year)
  { $checked = 'checked="checked" '; }

print '<p><input type="checkbox" name="cookie_for_a_year" tabindex="1" value="1" '.$checked.'/><span class="preinput">'._("Remember me").'</span><br />';
print '<span class="text">'._("For a year, your login information will be stored in a cookie. Use this only if you are using your own computer.").'</span>';

if ($GLOBALS['sys_brother_domain'])
{
  $checked = 'checked="checked" ';
  if ($login and !$brotherhood)
     $checked = '';

  print '<p><input type="checkbox" name="brotherhood" value="1" '.$checked.'/><span class="preinput">';
  printf (_("Login also in %s").'</span><br />', $GLOBALS['sys_brother_domain']);
  print '<span class="text">';
  printf (_("Do not use this if you are using kerberos. Do not use this until you already successfully logged in on %s, the result would be unpredictable."), $GLOBALS['sys_brother_domain']);
  print '</span>';
}

print '<div class="center"><input type="submit" name="login" value="'._("Login").'" tabindex="1" /></div>';
print '</form>';

$HTML->footer(array());

?>
