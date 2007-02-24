<?php
# Register an account, part 1 (part 2 is e-mail confirmation)

#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gna.org>
#  Copyright (C) 2007  Sylvain Beucler

# This file is part of the Savane project

# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU Gebneral Public License as
# published by the Free Software Foundation; either version 2 of the
# License, or (at your option) any later version.

# The Savane project is distributed in the hope that it will be
# useful, but WITHOUT ANY WARRANTY; without even the implied warranty
# of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301
# USA

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
#input_is_safe();
#mysql_is_safe();

extract(sane_import('post',
  array('update', 'form_id',
	'form_loginname', 'form_pw', 'form_pw2', 'form_realname', 'form_email',
	'form_usepam')));


# Logged users have no business here
if (user_isloggedin())
{
  session_redirect($GLOBALS['sys_home'] . 'my/');
}


# Block here potential robots
dnsbl_check();
# Block banned IP
spam_bancheck();


$login_is_valid = false;
$pw_is_valid = false;
$email_is_valid = false;
$realname_is_valid = false;

if (!empty($update) and form_check($form_id))
// Form is submitted
{
  // feedback included by the check function

  // Login
  if ($form_loginname == '')
    {
      fb(_("You must supply a username."),1);
    }
  else if (!account_namevalid($form_loginname))
    {
      // feedback included by the check function
    }
  // Avoid duplicates
  else if (db_numrows(db_execute("SELECT user_id FROM user WHERE user_name = ?",
				 array($form_loginname))) > 0)
    {
      fb(_("That username already exists."),1);
    }
  else if (db_numrows(db_execute("SELECT group_list_id FROM mail_group_list WHERE "
				 . "list_name = ?", array($form_loginname))) > 0)
    {
      fb(_("That username is blocked to avoid conflict with mailing-list addresses."),1);
    }
  else
    {
      $login_is_valid = true;
    }

  // Password
  if ($form_pw == '')
    {
      fb(_("You must supply a password."),1);
    }
  // Password sanity checks - unless PAM is used
  else if ($GLOBALS['sys_use_pamauth'] != "yes" and $form_usepam != 1 and $form_pw != $form_pw2)
    {
      fb(_("Passwords do not match."),1);
    }
  else if ($GLOBALS['sys_use_pamauth'] != "yes" and $form_usepam != 1 and !account_pwvalid($form_pw))
    {
      // feedback included by the check function
    }
  else
    {
      $pw_is_valid = true;
    }

  // E-mail
  if (!$form_email)
    {
      fb(_("You must supply a valid email address."),1);
    }
  else if (!account_emailvalid($form_email))
    {
      // feedback included by the check function
    }
  else
    {
      $email_is_valid = true;
    }

  // Real name
  if ($form_realname == '')
    {
      fb(_("You must supply a real name."),1);
    }
  else
    {
      $realname_is_valid = true;
    }

  # Remove quotes from the realname, we do not want to allow that but
  # it is not a blocker issue.
  # Beuc 2007-02-24: enable quotes in realname, it's a perfect test for unsecure MySQL queries
  # $GLOBALS['form_realname'] = strtr($_POST['form_realname'], "\'\"\,", "     ");


  ####


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
	    fb(sprintf(_("User is a kerberos principal but password do not match. Please use your kerberos password for the first login and then change your %s password. This is necessary to prevent someone from stealing your account name."),$GLOBALS['sys_name']),1);

	  $pw_is_valid = false;
	}
      elseif ($krb5ret == "2")
	{
	  # KRB5_BAD_USER

	  /*

FIXME : this is broken and seems to be due to the kerberos module.
        we did not changed anything about that and we get 2 as return
        for any name.

	  if($_POST['form_loginname']."@".$GLOBALS['sys_lists_domain'])
	    {
	      $GLOBALS['register_error'] = sprintf(_("User %s is a known mail alias and cannot be used. If you own this alias (%s@%s) please create a another user (for instance xx%s) and ask %s@%s to rename it to %s."),
						   $_POST['form_loginname'],
						   $_POST['form_loginname'],

						   $GLOBALS['sys_lists_domain'],
						   $_POST['form_loginname'],
						   $GLOBALS['sys_admin_list'],
						   $GLOBALS['sys_lists_domain'],
						   $_POST['form_loginname']);
	      return 0;
	    }
	  */
	}
    }
}

$form_is_valid = $login_is_valid and $pw_is_valid
  and $email_is_valid and $realname_is_valid;

if ($form_is_valid)
{
  if ($GLOBALS['sys_use_pamauth'] == "yes" && $form_usepam == 1)
    {
      // if user chose PAM based authentication, set his encrypted
      // password to the specified string
      $passwd = 'PAM';
    }
  else
    {
      $passwd = md5($form_pw);
    }

  $confirm_hash = substr(md5(rand(0, 32768) . $passwd . time()), 0, 16);
  $result=db_autoexecute(
    'user',
    array(
      'user_name' => strtolower($form_loginname),
      'user_pw'   => $passwd,
      'realname'  => $form_realname,
      'email'     => $form_email,
      'add_date'  => time(),
      'status'    => 'P',
      'confirm_hash' => $confirm_hash),
    DB_AUTOQUERY_INSERT);

  if (!$result)
    {
      exit_error('error',db_error());
    }
  else
    {

      $newuserid = db_insertid($result);

      # clean id
      form_clean($form_id);

      # send mail
      $message = sprintf(_("Thank you for registering on the %s web site."),$GLOBALS['sys_name'])."\n"
	."("._("Your login is not mentioned in this mail to prevent account creation by robots").")\n\n"
#	.sprintf(_("Your login is: %s"), addslashes(strtolower($_POST[form_loginname])))."\n\n"
	._("In order to complete your registration, visit the following URL:\n\n")
	. $GLOBALS['sys_https_url']
	. $GLOBALS['sys_home']
	. "account/verify.php?confirm_hash=$confirm_hash\n\n"
	._("Enjoy the site").".\n\n"
	. sprintf(_("-- the %s team.")."\n",$GLOBALS['sys_name']);

      if ($krb5ret == 0) #KRB5_OK
	{
	  $message .= sprintf(_("P.S. Your kerberos password is now stored in encrypted form\nin the %s database."),$GLOBALS['sys_name']);
	  $message .= sprintf(_("For better security we advise you\nto change your %s password as soon as possible.\n"),$GLOBALS['sys_name']);
	}


      sendmail_mail($GLOBALS['sys_replyto']."@".$GLOBALS['sys_lists_domain'],
		    $form_email,
		    $GLOBALS['sys_name']." "._("Account Registration"),
		    $message);

    $HTML->header(array('title'=>_("Register Confirmation")));

    print '<h3>'.$GLOBALS['sys_name'].' : '._("New Account Registration Confirmation").'</h3>'
      .sprintf(_("Congratulations. You have registered on %s "),$GLOBALS['sys_name'])
      .sprintf(_("Your login is: %s"), '<strong>'.user_getname($newuserid).'</strong>');

    print '<p>'._("You are now being sent a confirmation email to verify your email address. Visiting the link sent to you in this email will activate your account.").' <span class="warn">'._("Accounts not confirmed after two days are deleted from the database.").'</span></p>';

    }
}

# not valid registration, or first time to page
else

{

  site_header(array('title'=>_("User account registration"),'context'=>'account'));


  print form_header($_SERVER['PHP_SELF'], $form_id);
  print '<p><span class="preinput">'._("Login Name:").'</span><br />&nbsp;&nbsp;';
  print form_input("text", "form_loginname", $form_loginname);
  print '<br /><span class="text">'.sprintf(_("If you have a %s account use that account name - Note that account names cannot consist of only numbers. At least one letter must be included."),$GLOBALS['sys_mail_domain']).'</span></p>';

  print '<p><span class="preinput">'._("Password:").'</span><br />&nbsp;&nbsp;';
  print form_input("password", "form_pw", $form_pw);
  print "</p>";

  print '<p><span class="preinput">'._("Re-type Password:").'</span><br />&nbsp;&nbsp;';
  print form_input("password", "form_pw2", $form_pw2);
  print "</p>";

  print '<p><span class="preinput">'._("Real Name:").'</span><br />&nbsp;&nbsp;';
  print '<input size="30" type="text" name="form_realname" value="'.$form_realname.'" /></p>';

  print '<p><span class="preinput">'._("Email Address:").'</span><br />&nbsp;&nbsp;';
  print '<input size="30" type="text" name="form_email" value="'.$form_email.'" />';
  print '<br /><span class="text">'._("This email address will be verified before account activation.").'</span></p>';

  # Extension for PAM authentication
  # FIXME: for now, only the PAM authentication that exists is for AFS.
  #  but PAM is not limited to AFS, so we should consider a way to configure
  #  this (to put it in site specific content probably).
  if ($sys_use_pamauth=="yes")
    {
      print "<p>Instead of providing a new password you
      may choose to authenticate via an <strong>AFS</strong> account you own
      at this site (this requires your new login name to be the
      same as the AFS account name):";

      print '<p>&nbsp;&nbsp;&nbsp;<INPUT type="checkbox"
      name="form_usepam" value="1" > use AFS based authentication';
    }


  print form_footer();

}



$HTML->footer(array());
