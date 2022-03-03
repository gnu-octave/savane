<?php
# Front page - news, latests projects, etc.
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2006, 2007  Sylvain Beucler
# Copyright (C) 2017, 2022  Ineiev
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

require_once('../include/init.php');
require_once('../include/account.php');
require_once('../include/sane.php');

Header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
Header("Cache-Control: no-cache");
Header("Cache-Control: must-revalidate");

extract(sane_import('request', ['true' => 'from_brother']));

# Logged users have no business here.
if (user_isloggedin() && !$from_brother)
  session_redirect($GLOBALS['sys_home']."my/");

# Input checks.
extract(sane_import('request',
  [
    'true' => [
      'stay_in_ssl', 'brotherhood', 'cookie_for_a_year', 'login',
      'cookie_test'
    ],
    'name' => 'form_loginname',
    'pass' => 'form_pw',
    'internal_uri' => 'uri'
  ]
));

$uri_enc = urlencode ($uri);

# Check cookie support.
if (!$from_brother and !isset($_COOKIE["cookie_probe"]))
  {
    if (!$cookie_test)
      {
        # Attempt to set a cookie to go to a new page to see
        # if the client will indeed send that cookie.
        session_cookie('cookie_probe', 1);
        # $uri used to be not url-encoded, it caused login problems,
        # see sr#108277 (https://savannah.gnu.org/support/?108277).
        header('Location: login.php?uri=' . $uri_enc . '&cookie_test=1');
      }
    else
      {
# TRANSLATORS: the first argument is a domain (like "savannah.gnu.org"
# vs. "savannah.nongnu.org"); the second argument
# is a URL ("[URL label]" transforms to a link).
        fb(sprintf(_("Savane thinks your cookies are not activated for %s.
Please activate cookies in your web browser for this website
and [%s try to login again]."), $sys_default_domain,
$GLOBALS['sys_https_url'].$GLOBALS['sys_home'].'account/login.php?uri='
.$uri), 1);
      }
  }

if (!empty($login))
  {
    if ($from_brother)
      {
        extract(sane_import('get',
          ['digits' => 'session_uid', 'xdigits' => 'session_hash']
        ));
        if (!ctype_digit($session_uid))
          exit("Invalid session_uid");
        if (!ctype_alnum($session_hash))
          exit("Invalid session_hash");
      }

    if (isset($session_uid) and session_exists($session_uid, $session_hash))
      {
        $GLOBALS['session_hash'] = $session_hash;
        session_set_new_cookies($session_uid, $cookie_for_a_year, $stay_in_ssl);
        $success = 1;
      }
    else
      $success = session_login_valid($form_loginname, $form_pw, 0,
                                     $cookie_for_a_year, 0, $stay_in_ssl);
    if ($success)
      {
      # Set up the theme, if the user has selected any in the user
      # preferences -- but give priority to a cookie, if set.
        if (!isset($_COOKIE['SV_THEME']))
          {
            $theme_result = user_get_result_set(user_getid());
            $theme = db_result($theme_result, 0, 'theme');
            if (strlen($theme) > 0)
              utils_setcookie('SV_THEME', $theme, time() + 60*60*24);
          }
      # We return to our brother 'my', where we login originally,
      # unless we are request to go to an uri.
        if (!$uri)
          {
            $uri = $GLOBALS['sys_home'] . 'my/';
            $uri_enc = urlencode ($uri);
          }
      # If a brother server exists, login there too, if we are not
      # already coming from there.
        if (!empty($GLOBALS['sys_brother_domain']) && $brotherhood)
          {
            if (session_issecure())
              $http = "https";
            else
              $http = "http";

            if (!$from_brother)
              {
                # Go there saying hello to your brother.
                header ("Location: ".$http."://".$GLOBALS['sys_brother_domain']
                        .$GLOBALS['sys_home']."/account/login.php?session_uid="
                        .user_getid()."&session_hash=".$GLOBALS['session_hash']
                        ."&cookie_for_a_year=$cookie_for_a_year&from_brother=1"
                        ."&login=1&stay_in_ssl=$stay_in_ssl&brotherhood=1&uri="
                        . $uri_enc);
                exit;
              }
            else
              {
                header("Location: ".$http."://".$GLOBALS['sys_brother_domain']
                       .$uri);
                exit;
              }
          }
        else
          {
          # If No brother server exists, just go to 'my' page
          # unless we are request to go to an uri.

          # Optionally stay in TLS mode.
            if ($stay_in_ssl)
              {
                # Switch to requested HTTPs mode.
                header("Location: {$GLOBALS['sys_https_url']}$uri");
              }
            else
              {
                # Stay in current http mode (also avoids mentioning
                # hostname&port, which can be useful in test
                # environments with port forwarding).
                header("Location: $uri");
              }
            exit;
          }
      } # if ($success)
  } # if (!empty($login))

if (isset($session_hash))
  {
    # Nuke their old session securely.
    session_delete_cookie('session_hash');
    if (isset ($user_id))
      db_execute ("DELETE FROM session WHERE session_hash=? AND user=?",
                  array($session_hash, $user_id));
  }

site_header(array('title'=>_("Login")));
if (!empty($login) && !$success)
  {
    if (isset ($GLOBALS['signal_pending_account'])
        && $GLOBALS['signal_pending_account'] == 1)
      {
        print '<h2>'._("Pending Account").'</h2>';
        print '<p>'._("Your account is currently pending your email confirmation.
Visiting the link sent to you in this email will activate your account.")
              .'</p>';
        print '<p><a href="pending-resend.php?form_user='
              . "$form_loginname\">["
              ._("Resend Confirmation Email").']</a></p>';
      }
    else
      {
        # Print helpful error message.
        print '<div class="splitright"><div class="boxitem">';
        print '<div class="warn">'._("Troubleshooting:")
.'</div></div><ul class="boxli"><li class="boxitemalt">'
._("Is the &ldquo;Caps Lock&rdquo; or &ldquo;A&rdquo; light on your keyboard on?")
        .'<br />'
        ._("If so, hit &ldquo;Caps Lock&rdquo; key before trying again.").'</li>'.
        '<li class="boxitem">'._("Did you forget or misspell your password?")
        .'<br />'.utils_link('lostpw.php',
             _("You can recover your password using the lost password form."))
        .'</li>'
        .'<li class="boxitemalt">'._("Still having trouble?").'<br />'
        .utils_link($GLOBALS['sys_home'].'support/?group='
                    .$GLOBALS['sys_unix_group_name'],
                    _("Fill a support request.")).'</li>';
        print '</ul></div>';
      }
  }

if (isset($GLOBALS['sys_https_host']))
  utils_get_content("account/login");
print '<form action="'.$GLOBALS['sys_https_url'].$GLOBALS['sys_home']
      .'account/login.php" method="post">';
print '<input type="hidden" name="uri" value="'.htmlspecialchars($uri, ENT_QUOTES)
      .'" />';

# Shortcuts to New Account and Lost Password have a tabindex superior to
# the rest of form,
# so they dont mess with the normal order when you press TAB on the keyboard
# (login -> password -> post).
print '<p><span class="preinput">'._("Login Name:").'</span><br />&nbsp;&nbsp;';
print '<input type="text" name="form_loginname" value="' . $form_loginname
      .'" tabindex="1" /> <a class="smaller" href="register.php" tabindex="2">['
      ._("No account yet?").']</a></p>';

print '<p><span class="preinput">'._("Password:").'</span><br />&nbsp;&nbsp;';
print '<input type="password" name="form_pw" tabindex="1" /> '
      .'<a class="smaller" href="lostpw.php" tabindex="2">['
      ._("Lost your password?").']</a></p>';

$attr_list = ['tabindex' => '1'];

if (isset($GLOBALS['sys_https_host']))
  {
    print '<p>'
      . form_checkbox ('stay_in_ssl', $stay_in_ssl || !$login, $attr_list)
      . '<span class="preinput">';
    print _("Stay in secure (https) mode after login")."</span><br />\n";
  }
else
  {
    print '<p class="warn"><input type="hidden" name="stay_in_ssl" value="0" />';
    print _("This server does not encrypt data (no https), so the password you
sent may be viewed by other people. Do not use any important
passwords.").'</p>';
  }

print '<p>'
  . form_checkbox ('cookie_for_a_year', $cookie_for_a_year, $attr_list)
  . '<span class="preinput">' . _("Remember me") . "</span><br />\n";
print '<span class="text">'
      ._("For a year, your login information will be stored in a cookie. Use
this only if you are using your own computer.").'</span>';

if (!empty($GLOBALS['sys_brother_domain']))
  {
    print '<p>'
      .  form_checkbox ('brotherhood', $brotherhood || !$login, $attr_list)
      . '<span class="preinput">';
# TRANSLATORS: the argument is a domain (like "savannah.gnu.org"
# vs. "savannah.nongnu.org").
    printf (_("Login also in %s").'</span><br />', $GLOBALS['sys_brother_domain']);
  }
print form_footer (_("Login"), 'login');
$HTML->footer(array());
?>
