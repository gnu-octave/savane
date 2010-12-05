<?php
# Generic user settings editor
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007  Sylvain Beucler
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


require_once('../../include/init.php');
require_once('../../include/sendmail.php');
register_globals_off();
# should be mysql-safe, needs various input validation + !!tests!!

########################################################################
# Preliminary checks
# Check if the user is logged in.
session_require(array('isloggedin'=>'1'));

extract(sane_import('request',
  array('item', 'update', 'newvalue', 'newvaluecheck', 'oldvalue', 'step',
	'session_hash', 'confirm_hash', 'form_id')));

if (!$item)
{
  exit_missing_param();
}

$success = FALSE;

# To delete the account, the user must have first quitted all groups.
# Yes, this form could do automatically this, but when a user quit his group
# it send mails to people that should be informed, so it is best to push
# the user to use the relevant form than to reimplement everything here
if ($item == 'delete')
{
  $res_check = db_execute("SELECT group_id FROM user_group WHERE user_id=?", array(user_getid()));
  if (db_numrows($res_check) != 0)
    {      
      exit_error(_("You must quit groups that your are member of before requesting account deletion. If you registered a project that was not approved or discarded yet, you must ask admins to cancel the registration"));
    }
  
}

########################################################################
# Update the database
if ($update)
{
  if (!form_check($form_id))
    { exit_error(_("Exiting")); }

  # Update the database and redirect to account conf page
  if ($item == "realname")
    {
      ################# Realname
      if (!$newvalue)
	{ fb(_("You must supply a new real name."), 1); }
      else
	{
	  $newvalue = strtr($newvalue, "\'\"\,", "     ");
	  $success = db_autoexecute('user', array('realname' => $newvalue),
				    DB_AUTOQUERY_UPDATE,
				    "user_id=?", array(user_getid()));
	  if ($success)
	    { fb(_("Real Name updated.")); }
	  else
	    { fb(_("Failed to update the database."), 1); }
	}

    }
  else if ($item == "timezone")
    {
      ################# Timezone
      if ($newvalue == 100)
	{ $newvalue = "GMT"; }
      
      $success = db_autoexecute('user', array('timezone' => $newvalue),
				    DB_AUTOQUERY_UPDATE,
				    "user_id=?", array(user_getid()));
      if ($success)
	{ fb(_("Timezone updated.")); }
      else
	{ fb(_("Failed to update the database."), 1); }
    }
  else if ($item == "password")
    {
      ################# password

      require_once('../../include/account.php');

      $success = 1;

      # check against old pw
      db_execute("SELECT user_pw, status FROM user WHERE user_id=?", array(user_getid()));
      $row_pw = db_fetch_array();

      # CERN_SPECIFIC: sys_use_pamauth have to be included in the
      # configuration file and sv_update_conf
      if ($GLOBALS['sys_use_pamauth']=='yes' && $row_pw[user_pw] == 'PAM')
	{
	  # use pam authentication
	  unset($pam_error);
	  if (!pam_auth(user_getname(), $oldvalue, &$pam_error))
	    {
	      ' '._("Old password is incorrect.").' '
		 . $pam_error;
	      $success = 0;
	    }

	}
      else if (!account_validpw($row_pw['user_pw'], $oldvalue))
	{
	  # use basic authentication via user table
	  fb(_("Old password is incorrect."), 1);
	  $success = 0;
	}

      if($GLOBALS['sys_use_pamauth'])
	{
	  # allow user to set authentication to be PAM based
	  $success = db_autoexecute('user', array('user_pw' => 'PAM'),
				    DB_AUTOQUERY_UPDATE,
				    "user_id=?", array(user_getid()));
	}
      else
	{
	  # do standard password sanity checks and update table
	  if (!$newvalue)
	    {
	      fb(_("You must supply a password."), 1);
	      $success = 0;
	    }
	  if ($newvalue != $newvaluecheck)
	    {
	      fb(_("New Passwords do not match."), 1);
	      $success = 0;
	    }
	  if (!account_pwvalid($newvalue))
	    {
	      $success = 0;
	    }

	  # Update only if everything was ok before
	  if ($success)
	    {
	      $success = db_autoexecute('user', array('user_pw' => account_encryptpw($newvalue)),
				        DB_AUTOQUERY_UPDATE,
				        "user_id=?", array(user_getid()));
	      if ($success)
		{ fb(_("Password updated.")); }
	      else
		{ fb(_("Failed to update the database."), 1); }
	    }

	}
    }
  else if ($item == "gpgkey")
    {
      ################# GPG Key

      $success = db_autoexecute('user', array('gpg_key' => $newvalue),
			        DB_AUTOQUERY_UPDATE,
			        "user_id=?", array(user_getid()));
      if ($success)
	{ fb(_("GPG Key updated.")); }
      else
	{ fb(_("Failed to update the database."), 1); }
    }
  else if ($item == "email")
    {
      ################# Email

      # First step
      if (!$step)
	{
	  require_once('../../include/account.php');

	  # Proceed only if it is a valid email address
	  if (account_emailvalid($newvalue))
	    {
	      
              # Build a new confirm hash
	      $confirm_hash = substr(md5($session_hash . time()),0,16);
	      $res_user = db_execute("SELECT * FROM user WHERE user_id=?", array(user_getid()));
	      if (db_numrows($res_user) < 1)
		{ exit_error("Invalid User","That user does not exist."); }
	      
	      $row_user = db_fetch_array($res_user);
	      $success = db_autoexecute('user', array('confirm_hash' => $confirm_hash,
						      'email_new' => $newvalue),
				        DB_AUTOQUERY_UPDATE,
				        "user_id=?", array(user_getid()));

	      
	      
	      if (!$success)
		{
		  fb(_("Failed to update the database."), 1);
		}
	      else
		{
		  fb(_("Database updated."));
		  
		  if (!empty($GLOBALS['sys_https_host']))
		    { $url = 'https://'.$GLOBALS['sys_https_host']; }
		  else
		    { $url = 'http://'.$GLOBALS['sys_default_domain']; }
		  $url .= $GLOBALS['sys_home'].'my/admin/change.php?item=email&confirm_hash='.$confirm_hash;
		  $message = sprintf(_("You have requested a change of email address on %s.\nPlease visit the following URL to complete the email change:"), $GLOBALS['sys_name']) . "\n\n"
		    . $url."&step=confirm\n\n"
		    . sprintf(_("-- the %s team."), $GLOBALS['sys_name']) . "\n";
		  
		  $warning_message = sprintf(_("Someone, presumably you, has requested a change of email address on %s.\nIf it wasn't you, maybe someone is trying to steal your account...\n\nYour current address is %s, the supposedly new address is %s.\n\n"), $GLOBALS['sys_name'], $row_user['email'], $newvalue)
		    . _("If you did not request that change, please visit the following URL to discard\nthe email change and report the problem to us:")."\n\n"
		    . $url."&step=discard\n\n"
		    . sprintf(_("-- the %s team."), $GLOBALS['sys_name']) . "\n";
		  
		  $success = sendmail_mail($GLOBALS['sys_mail_replyto']."@".$GLOBALS['sys_mail_domain'],
					   $newvalue,
					   $GLOBALS['sys_name'] .' '._("Verification"),
					   $message);
		  
	      # yeupou--gnu.org 2003-11-09:
	      # Send also a warning to the current mail address, just in case:
	      # You can call that paranoia but
	      #  - someone can find a session open on a computer
	      #  - ask for change the mail address
	      #  - after the change, use the lost password process
	      #  ... and so change the password without knowing and
	      #  without having the user noticing that something bad is going
	      # on.
	      # The next step is probably to print the mail change request
	      # on account/ with the possibility to discard
		  sendmail_mail($GLOBALS['sys_mail_replyto']."@".$GLOBALS['sys_mail_domain'],
				$row_user['email'],
				$GLOBALS['sys_name'] .' '._("Verification"),
				$warning_message);
		  
		  
		  if ($success)
		    {
		      sprintf(_("Confirmation mailed to %s."), $newvalue);
		      fb(_("Follow the instructions in the email to complete the email change."));
		    }
		  else
		    {
		      fb(_("The system reported a failure when trying to send the confirmation mail. Please retry and report that problem to administrators."), 1);
		    }
		}


	    }
	}
      else if ($step == "confirm")
	{
	  // Cf. form at the end
	}
      // additional step with a direct POST request to avoid CSRF attacks
      else if ($step == "confirm2")
	{
	  $success = false;
	  
	  if (ereg("^[a-f0-9]{16}$",$confirm_hash)) {
	    $res_user = db_execute("SELECT * FROM user WHERE confirm_hash=?",
				   array($confirm_hash));
	    if (db_numrows($res_user) > 1)
	      {
		$ffeedback = (" This confirm hash exists more than once.");
	      }
	    else if (db_numrows($res_user) < 1)
	      {
		exit_error(" Invalid confirmation hash.");
	      }
	    else
	      {
		$success = true;
	      }
	  } else {
	    exit_error(" Invalid confirmation hash.");
	  }
	  if ($success)
	    {
	      $row_user = db_fetch_array($res_user);
	      $success = db_autoexecute('user',
		array(
                  'email' => $row_user['email_new'],
		  'confirm_hash' => null,
		  'email_new' => null
		), DB_AUTOQUERY_UPDATE,
		"user_id=? AND confirm_hash=?", array(user_getid(), $confirm_hash));

	      if ($success)
		{ fb(_("Email address updated.")); }
	      else
		{ fb(_("Failed to update the database."), 1); }

	    }
	}
      else if ($step == "discard")
	{
	  # Just remove stuff added
	  $success = db_autoexecute('user', array(
	      'confirm_hash' => null,
	      'email_new' => null
	    ), DB_AUTOQUERY_UPDATE,
	    "user_id=? AND confirm_hash=?", array(user_getid(), $confirm_hash));
	   if ($success)
	     { fb(_("Address change process discarded.")); }
	   else
	     { fb(_("Failed to discard the address change process, please contact administrators."), 1); }
	
	}
      else
	{
	  fb(_("Unable to understand what to do, parameters are probably missing"), 1);
	}
    }
  else if ($item == "delete")
    {
      
      ################# Account Deletion

      # First step
      if (!$step && $newvalue == 'deletionconfirmed')
	{
          # Build a new confirm hash
	  $confirm_hash = substr(md5($session_hash . time()),0,16);
	  $res_user = db_execute("SELECT * FROM user WHERE user_id=?", array(user_getid()));
	  if (db_numrows($res_user) < 1)
	    { exit_error("Invalid User","That user does not exist."); }
	  
	  $row_user = db_fetch_array($res_user);
	  
	  $success = db_autoexecute('user', array('confirm_hash' => $confirm_hash),
				    DB_AUTOQUERY_UPDATE,
				    "user_id=?", array(user_getid()));
	  
	  if (!$success)
	    {
	      fb(_("Failed to update the database."), 1);
	    }
	  else
	    {
	      fb(_("Database updated."));
	      
	      if (!empty($GLOBALS['sys_https_host']))
		{ $url = 'https://'.$GLOBALS['sys_https_host']; }
	      else
		{ $url = 'http://'.$GLOBALS['sys_default_domain']; }
	      $url .= $GLOBALS['sys_home'].'my/admin/change.php?item=delete&confirm_hash='.$confirm_hash;
	      
	      $message = sprintf(_("Someone, presumably you, has requested your %s account deletion.\nIf it wasn't you, it probably means that someone stole your account.\n\n"), $GLOBALS['sys_name']).
		sprintf(_("If you did request your %s account deletion, visit the following URL to finish\nthe deletion process:"), $GLOBALS['sys_name']) . "\n\n"
		. $url."&step=confirm\n\n"
		
		. _("If you did not request that change, please visit the following URL to discard\nthe process and report ASAP the problem to us:")."\n\n"
		. $url."&step=discard\n\n"
		. sprintf(_("-- the %s team."), $GLOBALS['sys_name']) . "\n";
	      
	      $success = sendmail_mail($GLOBALS['sys_mail_replyto']."@".$GLOBALS['sys_mail_domain'],
				       $row_user['email'],
				       $GLOBALS['sys_name'] .' '._("Verification"),
				       $message);
	      
	      
	      if ($success)
		{
		  fb(_("Follow the instructions in the email to complete the account deletion."));
		}
	      else
		{
		  fb(_("The system reported a failure when trying to send the confirmation mail. Please retry and report that problem to administrators."), 1);
		}
	    
	    }
	}
      else if ($step == "confirm")
	{
	  // Cf. form below
	}
      // additional step with a direct POST request to avoid CSRF attacks
      else if ($step == "confirm2")
	{
	  $success = 1;
	  $res_user = db_execute("SELECT * FROM user WHERE confirm_hash=?", array($confirm_hash));
	  if (db_numrows($res_user) > 1)
	    {
	      $ffeedback = ("This confirm hash exists more than once.");
	      $success = 0;
	    }
	  if (db_numrows($res_user) < 1)
	    {
	      exit_error("Invalid confirmation hash.");
	      $success = 0;
	    }
	  if ($success)
	    {
	      user_delete(0, $confirm_hash);
	    }
	}
      else if ($step == "discard")
	{
	  # Just remove stuff added
	  $success = db_autoexecute('user', array('confirm_hash' => null),
				    DB_AUTOQUERY_UPDATE,
				    "confirm_hash=?", array($confirm_hash));
	   if ($success)
	     { fb(_("Account deletion process discarded.")); }
	   else
	     { fb(_("Failed to discard account deletion process, please contact administrators."), 1); }
	}

      else
	{
	  fb(_("Unable to understand what to do, parameters are probably missing"), 1);
	}
    }


  # Success is set, it means that we can safely go back to the main
  # configuration page.
  if ($success)
    {
      session_redirect($GLOBALS['sys_home']."my/admin/?feedback=".rawurlencode($feedback));
    }

}

########################################################################
# If we reach this point, it means that not sucessful update has been
# already made.

# Texts to be displayed
$preamble = '';
$input_specific = '';

# Defines some information if not specific
$form_item_name = "newvalue";
$input_title = '';
$input_type = "text";
$input2_type = NULL;
$input3_type = NULL;
$input4_type = NULL;


# Defines the page depending on the item given
if ($item == "realname")
{
  ################# Realname

  $title = _("Change Real Name");
  $input_title = _("New Real Name:");
}
else if ($item == "timezone")
{
  ################# Timezone

  require_once('../../include/timezones.php');
  $title = _("Change Timezone");
  $input_title = _("No matter where you live, you can see all dates and times as if it were in your neighborhood:");
  $input_specific = html_build_select_box_from_arrays ($TZs,$TZs,'newvalue',user_get_timezone(), true, 'GMT');
}
else if ($item == "password")
{
  ################# Password

  $title = _("Change Password");

  $input_title = _("Current password:");
  $input2_title = _("New password / passphrase:") . " " . account_password_help();
  $input3_title = _("Re-type new password:");

  $form_item_name = "oldvalue";
  $form_item2_name = "newvalue";
  $form_item3_name = "newvaluecheck";

  $input_type = "password";
  $input2_type = "password";
  $input3_type = "password";

  # AFS CERN Stuff
  if ($sys_use_pamauth == "yes") {
    $input4_title = "<br />Instead of providing a new Savannah password you
      may choose to authenticate via an <strong>AFS</strong> account you own
      at this site (this requires your Savannah login name to be the
      same as the AFS account name). In this case, you don't need to fill the two \"New Password\" fields. Instead, check the following box:"; 

    db_execute("SELECT user_pw FROM user WHERE user_id=?", array(user_getid()));
    $row_pw = db_fetch_array();
    $uses_pam_auth = 0;
    if ($row_pw[user_pw] == 'PAM')
      {	$input4_type = 'checkbox" CHECKED'; }
    else
      { $input4_type = 'checkbox"'; }

    $form_item4_name = "usepam";
  }

}
else if ($item == "gpgkey")
{
  ################# GPG Key

  $res_user = db_execute("SELECT gpg_key FROM user WHERE user_id=?", array(user_getid()));
  $row_user = db_fetch_array($res_user);


  $title = _("Change GPG Key");
  $input_title = _("You can write down here your (ASCII) public key (gpg --export --armor keyid):");
  $input_specific = '<textarea cols="70" rows="10" wrap="virtual" name="newvalue">'.$row_user['gpg_key'].'</textarea>';

}
else if ($item == "email")
{
  ################# Email

  # First step
  if (!$step)
    {
      $title = _("Change Email Address");
      $input_title = _('New Email Address:');
      $preamble = _("Changing your email address will require confirmation from your new email address, so that we can ensure we have a good email address on file.").'</p><p>'._("We need to maintain an accurate email address for each user due to the level of access we grant via this account. If we need to reach a user for issues related to this server, it is important that we be able to do so.").'</p><p>'._("Submitting the form below will mail a confirmation URL to the new email address. Visiting this link will complete the email change.");
    }
  else if ($step == "confirm")
    {
      $title = _("Confirm Email change");
      $preamble = _('Click update to confirm your e-mail change');
      $input_title = _('Confirmation hash:');
      $input_specific = "<input type='text' readonly='readonly' name='confirm_hash' value='"
	. htmlentities($confirm_hash, ENT_QUOTES) . "' />";
      $input_specific .= "<input type='hidden' name='step' value='confirm2' />";
    }
}
else if ($item == "delete")
{
  ################# Account deletion

  # First step
  if (!$step)
    {
      $title = _("Delete Account");
      $input_title = _('Do you really want to delete your user account:');
      $input_specific = form_input("checkbox", "newvalue", "deletionconfirmed").' '._("Yes, I really do");
      $preamble = _("This process will require email confirmation.");
    }
  else if ($step == "confirm")
    {
      $title = _("Confirm account deletion");
      $preamble = _('Click update to confirm your account deletion');
      $input_title = _('Confirmation hash:');
      $input_specific = "<input type='text' readonly='readonly' name='confirm_hash' value='$confirm_hash' />";
      $input_specific .= "<input type='hidden' name='step' value='confirm2' />";
    }
}



########################################################################
# Actually prints the HTML page
site_user_header(array('title'=>$title,
		       'context'=>'account'));

if (!$input_title)
     $input_title = $title;

if ($preamble)
{
  print '<p>'.$preamble.'</p>';
}

print form_header($_SERVER['PHP_SELF'], false, "post");
print '<span class="preinput">'.$input_title.'</span>';

# Print the usual input unless we have something specific
if (!$input_specific)
{
  print '<br />&nbsp;&nbsp;&nbsp;<input name="'.$form_item_name.'" type="'.$input_type.'" />';
}
else
{
  print '<br />&nbsp;&nbsp;&nbsp;'.$input_specific;
}

# Add one more input if required
if ($input2_type)
{
  print '<br /><span class="preinput">'.$input2_title.'</span>';
  print '<br />&nbsp;&nbsp;&nbsp;<input type="'.$input2_type.'" name="'.$form_item2_name.'" />';

}

# Add one more input if required
if ($input3_type)
{
  print '<br /><span class="preinput">'.$input3_title.'</span>';
  print '<br />&nbsp;&nbsp;&nbsp;<input type="'.$input3_type.'" name="'.$form_item3_name.'" />';
}

# Add one more input if required
if ($input4_type)
{
  print '<br /><span class="preinput">'.$input4_title.'</span>';
  print '<br />&nbsp;&nbsp;&nbsp;<input type="'.$input4_type.'" name="'.$form_item4_name.'" />';
}

print '<p><input type="hidden" name="item" value="'.$item.'" /></p>';
print '<p><input type="submit" name="update" value="'._("Update").'" /></p>';
print '</form>';


site_user_footer(array());
