<?php
# Register an account, part 1 (part 2 is e-mail confirmation)
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gna.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2019 Ineiev
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
require_once('../include/sane.php');
require_once('../include/account.php');
require_once('../include/dnsbl.php');
require_once('../include/spam.php');
require_once('../include/form.php');
require_once('../include/utils.php');
require_once('../include/html.php');
require_once('../include/sendmail.php');

register_globals_off();

extract(sane_import('post',
  array('update', 'form_id',
        'form_loginname', 'form_pw', 'form_pw2', 'form_realname', 'form_email',
        'form_year',
        'form_usepam')));

$form_email = preg_replace ('/\s/', '', $form_email);

if (isset($GLOBALS['sys_https_host']) && !session_issecure())
  # Force use of TLS for login.
  header('Location: '.$GLOBALS['sys_https_url'].$_SERVER['REQUEST_URI']);

# Logged users have no business here.
if (user_isloggedin())
  session_redirect($GLOBALS['sys_home'] . 'my/');

# Block here potential robots.
dnsbl_check();

$login_is_valid = false;
$pw_is_valid = false;
$email_is_valid = false;
$realname_is_valid = false;
$antispam_is_valid = false;

if (!empty($update) and form_check($form_id))
# Form is submitted.
  {
    # Temporary spam block.
    if ($GLOBALS['sys_registration_text_spam_test'])
      {
        if ($form_year != 1983)
          fb(_("Please answer the antispam test!"),1);
        else
          $antispam_is_valid = true;
      }
    if ($GLOBALS['sys_registration_captcha'])
      {
        include_once $GLOBALS['sys_securimagedir'] . '/securimage.php';
        $securimage = new Securimage();

        if ($securimage->check($_POST['captcha_code']) == false)
          fb(_("Please correctly answer the antispam captcha!"),1);
        else
          $antispam_is_valid = true;
      }
    if (!$GLOBALS['sys_registration_captcha']
        && !$GLOBALS['sys_registration_text_spam_test'])
      $antispam_is_valid = true;

    # Login.
    if ($form_loginname == '')
      fb(_("You must supply a username."),1);
    elseif (!account_namevalid($form_loginname))
      {
        # Feedback included by the check function.
      }
  # Avoid duplicates.
    elseif (db_numrows(db_execute("SELECT user_id FROM user WHERE user_name = ?",
                                   array($form_loginname))) > 0)
      fb(_("That username already exists."),1);
    elseif (db_numrows(db_execute("SELECT group_list_id FROM mail_group_list "
                                 . "WHERE "
                                 . "list_name = ?", array($form_loginname))) > 0)
      fb(
_("That username is blocked to avoid conflict with mailing-list addresses."),1);
    else
      $login_is_valid = true;

    if ($form_pw == '')
      fb(_("You must supply a password."),1);
    # Password sanity checks - unless PAM is used.
    elseif ($GLOBALS['sys_use_pamauth'] != "yes" and $form_usepam != 1
            and $form_pw != $form_pw2)
      fb(_("Passwords do not match."),1);
    elseif ($GLOBALS['sys_use_pamauth'] != "yes" and $form_usepam != 1
            and !account_pwvalid($form_pw))
      {
        # Feedback included by the check function.
      }
    else
      $pw_is_valid = true;

    if (!$form_email)
      fb(_("You must supply a valid email address."),1);
    elseif (!account_emailvalid($form_email))
      {
        # Feedback included by the check function.
      }
    else
      $email_is_valid = true;

    if (!account_realname_valid ($form_realname))
      fb(_("You must supply a real name."), 1);
    else
      $realname_is_valid = true;
    $form_realname = account_sanitize_realname ($form_realname);

    $krb5ret = '';
    if ($GLOBALS['sys_use_krb5'] == "yes")
      {
        $krb5ret = krb5_login($form_loginname, $form_pw);
        if($krb5ret == -1)
          { # KRB5_NOTOK
            fb(_("phpkrb5 module failure"),1);
            $pw_is_valid = false;
          }
        elseif($krb5ret == 1)
          { # KRB5_BAD_PASSWORD
# TRANSLATORS: the argument is the name of the system (like "Savannah").
            fb(sprintf(_("User is a kerberos principal but password do not
match. Please use your kerberos password for the first login and then change
your %s password. This is necessary to prevent someone from stealing your
account name."), $GLOBALS['sys_name']), 1);
            $pw_is_valid = false;
          }
        elseif ($krb5ret == "2")
          {
          # KRB5_BAD_USER
          }
      }
  } # if (!empty($update) and form_check($form_id))

# Don't forget parenthesis to avoid precendence issues with 'and'.
$form_is_valid = ($login_is_valid and $pw_is_valid
                  and $email_is_valid and $realname_is_valid
                  and $antispam_is_valid);

if ($form_is_valid)
  {
    if ($GLOBALS['sys_use_pamauth'] == "yes" && $form_usepam == 1)
      # If user chose PAM based authentication, set his encrypted
      # password to the specified string.
      $passwd = 'PAM';
    else
      $passwd = account_encryptpw($form_pw);

    $confirm_hash = substr(md5(rand(0, 32768) . $passwd . time()), 0, 16);
    $result=db_autoexecute('user',
                           array('user_name' => strtolower($form_loginname),
                                 'user_pw'   => $passwd,
                                 'realname'  => $form_realname,
                                 'email'     => $form_email,
                                 'add_date'  => time(),
                                 'status'    => 'P',
                                 'confirm_hash' => $confirm_hash),
                           DB_AUTOQUERY_INSERT);

    if (!$result)
      exit_error('error',db_error());
    else
      {
        $newuserid = db_insertid($result);
        form_clean($form_id);

# TRANSLATORS: the argument is the name of the system (like "Savannah").
        $message_head = sprintf(_('Thank you for registering on the %s web site.
(Your login is not mentioned in this mail to prevent account creation by robots.)

In order to complete your registration, visit the following URL:'),
          $GLOBALS['sys_name'])
          ."\n"
          . $GLOBALS['sys_https_url'] . $GLOBALS['sys_home']
          . "account/verify.php?confirm_hash=";
        $message_tail = "\n\n"
          ._("Enjoy the site.")."\n\n";
# TRANSLATORS: the argument is the name of the system (like "Savannah").
          $message_tail .= sprintf(_("-- the %s team.")."\n\n",$GLOBALS['sys_name']);
        $message = $message_head . $confirm_hash . $message_tail;

        if ($krb5ret == 0) #KRB5_OK
          {
# TRANSLATORS: the argument is the name of the system (like "Savannah").
            $message .= sprintf(_('P.S. Your password is now stored in hashed form
in the %s database.'), $GLOBALS['sys_name'])." ";
            $message .= _('For better security we advise you to register
an encryption-capable GPG key and enable encryption when sending password reset
messages (if you do, please request password reset and make sure that you
actually can read those messages).'). "\n";
          }
        sendmail_mail($GLOBALS['sys_mail_replyto']."@"
                      .$GLOBALS['sys_mail_domain'],
                      $form_email,
                      $GLOBALS['sys_name']." "._("Account Registration"),
                      $message);

      $HTML->header(array('title'=>_("Register Confirmation")));
      print '<h2>'.$GLOBALS['sys_name'].' : '
        ._("New Account Registration Confirmation")."</h2>\n";
# TRANSLATORS: the argument is the name of the system (like "Savannah").
      printf(_("Congratulations. You have registered on %s."),
             $GLOBALS['sys_name']);
      print "\n".sprintf(_("Your login is %s."),
          '<strong>'.user_getname($newuserid).'</strong>')."\n";
      print '<p>'
.sprintf(_("You are now being sent a confirmation email to verify your
email address. Visiting the link sent to you in this email will activate your
account. The email is sent from &lt;%s&gt;, it contains a text like this:"),
  $GLOBALS['sys_mail_replyto']."@".$GLOBALS['sys_mail_domain'] )."</p>\n"
."<blockquote><pre>\n"
. $message_head . "XXXXXXXXXXXXXXXXXXXXXXXXX" . $message_tail
."\n</pre></blockquote>\n"
.'<p><em>'
._("If you don't receive it within a reasonable time, contact website
administration.")."</em></p>\n";
      }
  }
# Not valid registration, or first time to page.
else
  {
    site_header(array('title'=>_("User account registration"),
                      'context'=>'account'));
    print form_header($_SERVER['PHP_SELF'], htmlentities($form_id, ENT_QUOTES,
                                                         'UTF-8'));
    print '<p><span class="preinput">'._("Login Name:")
          .'</span>'."<br />\n".'&nbsp;&nbsp;';
    print form_input("text", "form_loginname", $form_loginname);
    print '<p><span class="preinput">'.account_password_help().'</span></p>';
    print '<p><span class="preinput">'._("Password / passphrase:")
           .'</span><br />&nbsp;&nbsp;';
    print form_input("password", "form_pw", $form_pw);
    print "</p>\n";
    print '<p><span class="preinput">'._("Re-type Password:")
          .'</span><br />&nbsp;&nbsp;';
    print form_input("password", "form_pw2", $form_pw2);
    print "</p>\n";
    print '<p><span class="preinput">'._("Real Name:").'</span><br />&nbsp;&nbsp;';
    print '<input size="30" type="text" name="form_realname" value="'
          .htmlentities($form_realname, ENT_QUOTES , 'UTF-8')."\" /></p>\n";
    print '<p><span class="preinput">'._("Email Address:")
          .'</span>'."<br />\n".'&nbsp;&nbsp;';
    print '<input size="30" type="text" name="form_email" value="'
          .htmlentities($form_email, ENT_QUOTES , 'UTF-8').'" />';
    print "<br />\n".'<span class="text">'
._("This email address will be verified before account activation. Check your
spam filters. Do not use a hotmail or comcast address here.")
."</span></p>\n";

    if ($GLOBALS['sys_registration_text_spam_test'])
      {
        print '<p><span class="preinput">'._("Antispam test:")
              .'</span><br />&nbsp;&nbsp;';
        print '<input size="30" type="text" name="form_year" value="'
              .htmlentities($form_year, ENT_QUOTES, 'UTF-8').'" />';
        print "<br />\n".'<span class="text">'
              .sprintf(
_("In what year was the GNU project announced? [<a href='%s'>hint</a>]"),
     'https://www.gnu.org/gnu/gnu-history.html')
            . "</span></p>\n";
      }
    if ($GLOBALS['sys_registration_captcha'])
      {
        print '<img id="captcha" src="' . $GLOBALS['sys_home']
              . 'gencaptcha.php" alt="CAPTCHA" /><br />';
        print '[ <a href="#" id="captcha_js_link">Different Image</a> ] '."\n"
             .'<script type="text/javascript" src="/js/captcha.php"></script>'
             ."\n";
        print '[ <a href="' . $GLOBALS['sys_home'] . 'playcaptcha.php">'
              . _("Play Captcha") . "</a> ]<br />\n";
        print _("Antispam test:")
              .'<input type="text" name="captcha_code" size="10" '
              .'maxlength="6" />';
      }
  # Extension for PAM authentication.
  # FIXME: for now, only the PAM authentication that exists is for AFS.
  #  but PAM is not limited to AFS, so we should consider a way to configure
  #  this (to put it in site specific content probably).
    if ($sys_use_pamauth=="yes")
      {
        print "<p>"._("Instead of providing a new password you
        may choose to authenticate via an <strong>AFS</strong> account you own
        at this site (this requires your new login name to be the
        same as the AFS account name):")."</p>\n";

        print '<p>&nbsp;&nbsp;&nbsp;<input type="checkbox"
        name="form_usepam" value="1" > '._('use AFS based authentication')
              ."</p>\n";
      }
    print form_footer();
  }
$HTML->footer(array());
?>
