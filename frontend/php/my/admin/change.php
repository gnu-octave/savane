<?php
# Generic user settings editor.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007, 2013  Sylvain Beucler
# Copyright (C) 2016 Karl Berry
# Copyright (C) 2017, 2018 Ineiev
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
        'session_hash', 'confirm_hash', 'form_id', 'test_gpg_key')));

if (!$item)
  exit_missing_param();

function test_gpg_listing ($gpg_name, $temp_dir, &$ret)
{
  $ret .= "<h2>"._("Listing key")."</h2>\n"
                     ."<p>". _("Output:")."</p>";
  $cmd = $gpg_name . " --home " . $temp_dir
                   . " --list-keys --fingerprint ";
  $d_spec = array (0 => array("pipe", "r"), 1 => array("pipe", "w"),
                   2 => array("pipe", "w"));
  $my_env = $_ENV;
  # Let non-ASCII user IDs show up in a readable way.
  $my_env['LC_ALL'] = "C.UTF-8";
  $gpg_proc = proc_open ($cmd, $d_spec, $pipes, NULL, $my_env);
  fclose ($pipes[0]);
  $gpg_output = stream_get_contents ($pipes[1]);
  $gpg_errors = stream_get_contents ($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  $gpg_result = proc_close($gpg_proc);
  $ret .= "<pre>\n";
  $ret .= htmlentities($gpg_output);
  $ret .= "</pre>\n";
  $ret .= "<p>". _("Errors:")."</p>\n";
  $ret .= "<pre>\n";
  $ret .= htmlentities($gpg_errors);
  $ret .= "</pre>\n";
  $ret .= "<p>"._("Exit status:")." ";
  $ret .= $gpg_result . "</p>\n";
  return $gpg_result;
}

function test_gpg_import ($gpg_name, $key, $temp_dir, &$output)
{
  $output .= "<h2>"._("Importing key")."</h2>\n";
  $cmd = $gpg_name . " --home '".$temp_dir."' --batch --import";
  $d_spec = array (0 => array("pipe", "r"), 1 => array("pipe", "w"),
                   2 => array("pipe", "w"));
  $my_env = $_ENV;
  $my_env['LC_ALL'] = "C.UTF-8";
  $gpg_proc = proc_open ($cmd, $d_spec, $pipes, NULL, $my_env);
  fwrite ($pipes[0], $key);
  fclose ($pipes[0]);
  $gpg_errors = stream_get_contents ($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  $gpg_result = proc_close($gpg_proc);
  $output .= "<pre>\n";
  $output .= htmlentities($gpg_errors);
  $output .= "</pre>\n";
  $output .= "<p>"._("Exit status:")." ";
  $output .= $gpg_result . "</p>\n";
  return $gpg_result;
}

function test_gpg_encryption ($gpg_name, $temp_dir, &$output)
{
# The message is a slightly modified ASCII art
# from https://www.gnu.org/graphics/gnu-ascii2.html .
  $message = "
  ,' ,-_-. '.
 ((_/)o o(\\_))
  `-'(. .)`-'
      \\_/\n";
  $cmd = 'perl ../../../perl/encrypt-to-user/index.pl '
         .'--home="'.$temp_dir.'" --gpg=' . $gpg_name;

  $d_spec = array(
      0 => array("pipe", "r"), 1 => array("pipe", "w"),
      2 => array("pipe", "w"));

  $gpg_proc = proc_open($cmd, $d_spec, $pipes, NULL, $_ENV);

  fwrite($pipes[0], $message);
  fclose($pipes[0]);
  $encrypted_message = stream_get_contents($pipes[1]);
  $gpg_stderr = stream_get_contents($pipes[2]);
  fclose($pipes[1]); fclose($pipes[2]);
  $gpg_result = proc_close($gpg_proc);
  $gpg_error = "";
  if($gpg_result != 0 or $encrypted_message === FALSE or $encrypted_message === "")
    {
      $encrypted_message = "";
      if($gpg_result == 1)
        $gpg_error = _("Encryption failed.");
      else if($gpg_result == 2)
        $gpg_error = _("No key for encryption found.");
      else if($gpg_result == 3)
        $gpg_error = _("Can't extract user_id from database.");
      else if($gpg_result == 4)
        $gpg_error = _("Can't create temporary files.");
      else if($gpg_result == 5)
        $gpg_error = _("Extracted GPG key ID is invalid.");
      $encrypted_message = "";
    }
  $output .= "<h2>"._("Test Encryption")."</h2>\n";
  if ($gpg_result)
    $output .= "<p>"._("Errors:")." ".$gpg_error."</p>\n";
  else
    {
      $output .= "<p>"
._("Encryption succeeded; you should be able to decrypt this with
<em>gpg --decrypt</em>:")."</p>\n";
      $output .= "<pre>". $encrypted_message ."</pre>\n";
    }
  return $gpg_result;
}

function run_gpg_tests ($gpg_name, $key, $temp_dir, &$output)
{
  if (test_gpg_import ($gpg_name, $key, $temp_dir, $output))
    return;
  if (test_gpg_listing ($gpg_name, $temp_dir, $output))
    return;
  test_gpg_encryption ($gpg_name, $temp_dir, $output);
}
function run_gpg_checks ($key)
{
  $ret = "";
  $ret .= "<h2>"._("GnuPG version")."</h2>\n";
  $gpg_name = 'gpg';
  $cmd = $gpg_name . " --version";
  $d_spec = array (0 => array("pipe", "r"), 1 => array("pipe", "w"),
                   2 => array("file", "/dev/null", "a"));

  $gpg_proc = proc_open ($cmd, $d_spec, $pipes, NULL, $_ENV);
  fclose ($pipes[0]);
  $gpg_output = stream_get_contents ($pipes[1]);
  fclose ($pipes[1]);
  proc_close($gpg_proc);
  $ret .= "<pre>\n";
  $ret .= htmlentities($gpg_output);
  $ret .= "</pre>\n";

  $temp_dir = exec ("mktemp -d");
  if (is_dir ($temp_dir))
    {
      run_gpg_tests ($gpg_name, $key, $temp_dir, $ret);
      system ("rm -r '". $temp_dir ."'");
    }
  else
    $ret .= "<p>"._("Can't create temporary directory.")."</p>\n";
  $ret .= "\n<hr />\n";
  return $ret;
}

$success = FALSE;

# To delete the account, the user must have first quitted all groups.
# Yes, this form could do automatically this, but when a user quit his group
# it send mails to people that should be informed, so it is best to push
# the user to use the relevant form than to reimplement everything here
if ($item == 'delete')
  {
    $res_check = db_execute("SELECT group_id FROM user_group WHERE user_id=?",
                            array(user_getid()));
    $rows = db_numrows($res_check);
    $exists = false;
  # Check if the user is a member of any _active_ group.
    for ($i = 0; $i < $rows && !$exists; $i++)
      {
        $group_id = db_result ($res_check, $i, 'group_id');
        if (0 != db_numrows(db_execute("SELECT unix_group_name FROM groups "
                                       ."WHERE group_id=? and status='A'",
                                       array($group_id))))
          $exists = true;
      }
    if ($exists)
      exit_error(_("You must quit groups of which you are a member before
requesting account deletion. If you registered a project that was not approved
or discarded yet, you must ask admins to cancel that registration."));
  }

# Update the database.
if ($update)
  {
    if (!form_check($form_id))
      exit_error(_("Exiting"));

  # Update the database and redirect to account conf page.
    if ($item == "realname")
      {
        if (!$newvalue)
          fb(_("You must supply a new real name."), 1);
        else
          {
            $newvalue = strtr($newvalue, "\'\"\,", "     ");
            $success = db_autoexecute('user', array('realname' => $newvalue),
                                      DB_AUTOQUERY_UPDATE,
                                      "user_id=?", array(user_getid()));
            if ($success)
              fb(_("Real Name updated."));
            else
              fb(_("Failed to update the database."), 1);
          }
      }
    elseif ($item == "timezone")
      {
        if ($newvalue == 100)
          $newvalue = "GMT";
        $success = db_autoexecute('user', array('timezone' => $newvalue),
                                      DB_AUTOQUERY_UPDATE,
                                      "user_id=?", array(user_getid()));
        if ($success)
          fb(_("Timezone updated."));
        else
          fb(_("Failed to update the database."), 1);
      }
    elseif ($item == "password")
      {
        require_once('../../include/account.php');
        $success = 1;
        # Check against old pw.
        db_execute("SELECT user_pw, status FROM user WHERE user_id=?",
                   array(user_getid()));
        $row_pw = db_fetch_array();

      # CERN_SPECIFIC: sys_use_pamauth have to be included in the
      # configuration file and sv_update_conf.
        if ($GLOBALS['sys_use_pamauth']=='yes' && $row_pw[user_pw] == 'PAM')
          {
            # Use pam authentication.
            unset($pam_error);
            if (!pam_auth(user_getname(), $oldvalue, $pam_error))
              {
                ' '._("Old password is incorrect.").' '
                   . $pam_error;
                $success = 0;
              }
          }
        elseif (!account_validpw($row_pw['user_pw'], $oldvalue))
          {
            # Use basic authentication via user table.
            fb(_("Old password is incorrect."), 1);
            $success = 0;
          }

        if($GLOBALS['sys_use_pamauth'])
          {
            # Allow user to set authentication to be PAM based.
            $success = db_autoexecute('user', array('user_pw' => 'PAM'),
                                      DB_AUTOQUERY_UPDATE,
                                      "user_id=?", array(user_getid()));
          }
        else
          {
            # Do standard password sanity checks and update table.
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
              $success = 0;

            # Update only if everything was ok before.
            if ($success)
              {
                $success = db_autoexecute('user',
                                          array('user_pw' =>
                                                account_encryptpw($newvalue)),
                                          DB_AUTOQUERY_UPDATE,
                                          "user_id=?", array(user_getid()));
                if ($success)
                  fb(_("Password updated."));
                else
                  fb(_("Failed to update the database."), 1);
              }
          }
      }
    elseif ($item == "gpgkey")
      {
        $success = db_autoexecute('user', array('gpg_key' => $newvalue),
                                  DB_AUTOQUERY_UPDATE,
                                  "user_id=?", array(user_getid()));
        if ($success)
          fb(_("GPG Key updated."));
        else
          fb(_("Failed to update the database."), 1);
      }
    elseif ($item == "email")
      {
        # First step.
        if (!$step)
          {
            require_once('../../include/account.php');
            # Proceed only if it is a valid email address.
            if (account_emailvalid($newvalue))
              {
                # Build a new confirm hash.
                $confirm_hash = substr(md5($session_hash . time()),0,16);
                $res_user = db_execute("SELECT * FROM user WHERE user_id=?",
                                       array(user_getid()));
                if (db_numrows($res_user) < 1)
                  exit_error(_("Invalid User"),
                             _("That user does not exist."));

                $row_user = db_fetch_array($res_user);
                $success = db_autoexecute('user', array('confirm_hash' => $confirm_hash,
                                                        'email_new' => $newvalue),
                                          DB_AUTOQUERY_UPDATE,
                                          "user_id=?", array(user_getid()));
                if (!$success)
                  fb(_("Failed to update the database."), 1);
                else
                  {
                    fb(_("Database updated."));

                    if (!empty($GLOBALS['sys_https_host']))
                      $url = 'https://'.$GLOBALS['sys_https_host'];
                    else
                      $url = 'http://'.$GLOBALS['sys_default_domain'];
                    $url .= $GLOBALS['sys_home'].'my/admin/change.php?'
                            .'item=email&confirm_hash='.$confirm_hash;
                    $message = sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_('You have requested a change of email address on %s.
Please visit the following URL to complete the email change:'),
                                       $GLOBALS['sys_name']) . "\n\n"
                    . $url."&step=confirm\n\n";
                    $message .= sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_("-- the %s team."),
                                        $GLOBALS['sys_name']) . "\n";

                    $warning_message = sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_('Someone, presumably you, has requested a change of email address on %s.
If it wasn\'t you, maybe someone is trying to steal your account...')."\n\n"
._('Your current address is %1$s, the supposedly new address is %2$s.'),
$GLOBALS['sys_name'], $row_user['email'], $newvalue).'

' . _('If you did not request that change, please visit the following URL
to discard the email change and report the problem to us:')
."\n\n"
                    . $url."&step=discard\n\n";

                    $warning_message .=
                      sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_("-- the %s team."), $GLOBALS['sys_name'])."\n";

                    $success = sendmail_mail($GLOBALS['sys_mail_replyto']."@"
                                             .$GLOBALS['sys_mail_domain'],
                                             $newvalue,
                                             $GLOBALS['sys_name'] .' '
                                             ._("Verification"),
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
                    sendmail_mail($GLOBALS['sys_mail_replyto']."@"
                                  .$GLOBALS['sys_mail_domain'],
                                  $row_user['email'],
                                  $GLOBALS['sys_name'] .' '._("Verification"),
                                  $warning_message);
                    if ($success)
                      {
                        fb(sprintf(
# TRANSLATORS: the argument is email address.
_("Confirmation mailed to %s."), $newvalue) . ' '
._("Follow the instructions in the email to complete the email change."));
                      }
                    else
                      fb(_("The system reported a failure when trying to send
the confirmation mail. Please retry and report that problem to
administrators."), 1);
                  }
              }
          }
        elseif ($step == "confirm")
          {
          // Cf. form at the end
          }
        # Additional step with a direct POST request to avoid CSRF attacks.
        elseif ($step == "confirm2")
          {
            $success = false;

            if (preg_match ("/^[a-f0-9]{16}$/", $confirm_hash))
              {
                $res_user = db_execute("SELECT * FROM user WHERE confirm_hash=?",
                                       array($confirm_hash));
                if (db_numrows($res_user) > 1)
                  $ffeedback = ' '
                 ._("This confirmation hash is included in DB more than once.");
                elseif (db_numrows($res_user) < 1)
                  exit_error(' '._("Invalid confirmation hash."));
                else
                  $success = true;
              }
            else
              exit_error(' '._("Invalid confirmation hash."));
            if ($success)
              {
                $row_user = db_fetch_array($res_user);
                $success = db_autoexecute('user',
                  array(
                    'email' => $row_user['email_new'],
                    'confirm_hash' => null,
                    'email_new' => null
                  ), DB_AUTOQUERY_UPDATE,
                  "user_id=? AND confirm_hash=?", array(user_getid(),
                  $confirm_hash));

                if ($success)
                  fb(_("Email address updated."));
                else
                  fb(_("Failed to update the database."), 1);
              }
          }
        elseif ($step == "discard")
          {
            # Just remove stuff added.
            $success = db_autoexecute('user', array(
                'confirm_hash' => null,
                'email_new' => null
              ), DB_AUTOQUERY_UPDATE,
              "user_id=? AND confirm_hash=?", array(user_getid(), $confirm_hash));
             if ($success)
               fb(_("Address change process discarded."));
             else
               fb(
_("Failed to discard the address change process, please contact
administrators."), 1);
          }
        else
          fb(
_("Unable to understand what to do, parameters are probably missing"), 1);
      }
    elseif ($item == "delete")
      {
      # First step
        if (!$step && $newvalue == 'deletionconfirmed')
          {
            # Build a new confirm hash.
            $confirm_hash = substr(md5($session_hash . time()),0,16);
            $res_user = db_execute("SELECT * FROM user WHERE user_id=?",
                                   array(user_getid()));
            if (db_numrows($res_user) < 1)
              exit_error(_("Invalid User"), _("That user does not exist."));
            $row_user = db_fetch_array($res_user);
            $success = db_autoexecute('user', array('confirm_hash' => $confirm_hash),
                                      DB_AUTOQUERY_UPDATE,
                                      "user_id=?", array(user_getid()));
            if (!$success)
              fb(_("Failed to update the database."), 1);
            else
              {
                fb(_("Database updated."));
                if (!empty($GLOBALS['sys_https_host']))
                  $url = 'https://'.$GLOBALS['sys_https_host'];
                else
                  $url = 'http://'.$GLOBALS['sys_default_domain'];
                $url .= $GLOBALS['sys_home'].'my/admin/change.php?'
                        .'item=delete&confirm_hash='.$confirm_hash;
                $message = sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_('Someone, presumably you, has requested your %s account deletion.
If it wasn\'t you, it probably means that someone stole your account.'),
                                   $GLOBALS['sys_name']).'

';
                $message .= sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_('If you did request your %s account deletion, visit the following URL to finish
the deletion process:'), $GLOBALS['sys_name']) . "\n\n"
                . $url."&step=confirm\n\n"
._('If you did not request that change, please visit the following URL to discard
the process and report ASAP the problem to us:')."\n\n"
                . $url."&step=discard\n\n";
                $message .= sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_("-- the %s team."), $GLOBALS['sys_name'])
                            . "\n";
                $success = sendmail_mail($GLOBALS['sys_mail_replyto']."@"
                                         .$GLOBALS['sys_mail_domain'],
                                         $row_user['email'],
                                         $GLOBALS['sys_name'] .' '._("Verification"),
                                         $message);
                if ($success)
                  fb(
_("Follow the instructions in the email to complete the account deletion."));
                else
                  fb(_("The system reported a failure when trying to send
the confirmation mail. Please retry and report that problem to
administrators."),
                     1);
              }
          }
        elseif ($step == "confirm")
          {
          // Cf. form below
          }
        # Additional step with a direct POST request to avoid CSRF attacks
        elseif ($step == "confirm2")
          {
            $success = 1;
            $res_user = db_execute("SELECT * FROM user WHERE confirm_hash=?",
                                   array($confirm_hash));
            if (db_numrows($res_user) > 1)
              {
                $ffeedback =
                  _("This confirmation hash is included in DB more than once.");
                $success = 0;
              }
            if (db_numrows($res_user) < 1)
              {
                exit_error(_("Invalid confirmation hash."));
                $success = 0;
              }
            if ($success)
              user_delete(0, $confirm_hash);
          }
        elseif ($step == "discard")
          {
            # Just remove stuff added.
            $success = db_autoexecute('user', array('confirm_hash' => null),
                                      DB_AUTOQUERY_UPDATE,
                                      "confirm_hash=?", array($confirm_hash));
             if ($success)
               fb(_("Account deletion process discarded."));
             else
             fb(
_("Failed to discard account deletion process, please contact administrators."),
                1);
          }
        else
          fb(
_("Unable to understand what to do, parameters are probably missing"),
             1);
      }
  # Success is set, it means that we can safely go back to the main
  # configuration page.
    if ($success)
      session_redirect($GLOBALS['sys_home']."my/admin/?feedback="
                       .rawurlencode($feedback));
  } # if ($update).

########################################################################
# If we reach this point, it means that not sucessful update has been
# already made.

# Texts to be displayed.
$preamble = '';
$input_specific = '';

# Defines some information if not specific.
$form_item_name = "newvalue";
$input_title = '';
$input_type = "text";
$input2_type = NULL;
$input3_type = NULL;
$input4_type = NULL;

# Defines the page depending on the item given.
if ($item == "realname")
  {
    $title = _("Change Real Name");
    $input_title = _("New Real Name:");
  }
elseif ($item == "timezone")
  {
    require_once('../../include/timezones.php');
    $title = _("Change Timezone");
    $input_title =
_("No matter where you live, you can see all dates and times as if it were in
your neighborhood.");
    $input_specific = html_build_select_box_from_arrays ($TZs, $TZs, 'newvalue',
                                                         user_get_timezone(),
                                                         true, 'GMT', false,
                                                         'Any', false,
                                                         _('Timezone'));
  }
elseif ($item == "password")
  {
    $title = _("Change Password");
    $preamble = account_password_help();
    $input_title = _("Current password:");
    $input2_title = _("New password / passphrase:");
    $input3_title = _("Re-type new password:");

    $form_item_name = "oldvalue";
    $form_item2_name = "newvalue";
    $form_item3_name = "newvaluecheck";

    $input_type = "password";
    $input2_type = "password";
    $input3_type = "password";

  # AFS CERN Stuff
    if ($sys_use_pamauth == "yes")
      {
        $input4_title = "<br />Instead of providing a new Savannah password you
may choose to authenticate via an <strong>AFS</strong> account you own
at this site (this requires your Savannah login name to be the
same as the AFS account name). In this case, you don't need to fill the
two &ldquo;New Password&rdquo; fields. Instead, check the following box:";

        db_execute("SELECT user_pw FROM user WHERE user_id=?",
                   array(user_getid()));
        $row_pw = db_fetch_array();
        $uses_pam_auth = 0;
        if ($row_pw[user_pw] == 'PAM')
          $input4_type = 'checkbox" CHECKED';
        else
          $input4_type = 'checkbox"';
        $form_item4_name = "usepam";
      }
  }
elseif ($item == "gpgkey")
  {
    $res_user = db_execute("SELECT gpg_key FROM user WHERE user_id=?",
                           array(user_getid()));
    $row_user = db_fetch_array($res_user);
    $title = _("Change GPG Key");
    $input_title = "";
    $input_specific =
"<h2>"._("Sample GPG key")."</h2>\n"
.'<p>'._('The exported public GPG key should look like this:')
.'</p>
<pre>
-----BEGIN PGP PUBLIC KEY BLOCK-----

mQENBFr1PisBCAC9xQcWyOZRLa6K2g7NJbvQmm7p89/xifFYXPpMTQAnlSoCtUdZ
oznXNR4oFYIqTasaXCFpG5uFCTDObPOSg1JqRDZYckijkAvbYlieBY6/ItrQxjyS
b0VcBN/UNvn3BiGOIUZOuDkAM2dwR0AwCS0blKdIBZ5PMrAm0+NvuZAZzVYRXVas
7NHflqCKWLhctDR9lg2bf7mpnjiywrV0BuLTp4xFhCue30juqfn+udZpZ+uq7QyV
arWY3CBRTepXTLcgxpXtmRUU6KfTVMEaFZoJhemnD0PAUQ9ReWIOLVYTZbgdwp98
wv8QaZTK+aaRMTBlDrLkMZCWlem0370WgL0XABEBAAG0I9CS0LDRgdGPINCf0YPR
iNC60LjQvSA8dnBAdGVzdC5vcmc+iQFTBBMBCAA9FiEERHdxNm0nSYKP0OaDAjlB
ZosL8WAFAlr1PisCGwMFCQPCZwAFCwkIBwIGFQoJCAsCAxYCAQIeAQIXgAAKCRAC
OUFmiwvxYKMHB/9Zs2ptiMtUfh9tTpC/3k9Df8z3kIfxWI7n2Tw6YLKow9lbjgfL
UfcZIAXHBv9RteU748J/fscmDv2i1a83vJ+JBF9GQJacENAgkoB5ChWNAeI/ifCS
NpWl+NUv9DKkyG1GXU5icLmBJP+b5ASLtyuqnfgT2bCYlIo3PREkjLs4cfAUAtOY
c/1FYbth2tQRCXnujRqqzz+6+qYRkTz91WFWc7/GYJ2obwsTrRrPfYQM02Dx77Lm
9ir8GtWbC6xiOtRwDY2RyBlnYQj53XGaFljxOsqzTzUa0NLGmnBf3JXI6D+gWLK1
vZaKd2Kp9U6cr5kNP6fUxJPI2LEMBfX1tTMquQENBFr1PisBCACuqpIVoWT9yGzH
VRO12/hDAxROE1RzngTc7HrsuL2wfY2PDZ0hWOfJpU778TGrmtrTQT5DPBY1ECJM
Nw1j9WRUtnxPXCE8LLNd5zEdTQm7M1P/XVxrzVyItU3AyJUYjvypTNROrbrkoVhQ
0AMApawRG6chGLMkRxlhB7TCy/EJnlhNh+8xmsWXWQV3YLPIsfWnutvNSrIjE59q
4qDdwOWj8GhwtPd83lSBRCA1cGAikCaXvXYJRUUvbJt2PQni9/jQL0FC6fA6OACO
ab61aegk9JWoPbxUVUigzGTYQ1Xy00U4KLD9Eq0ivHTQTx7huNk7DjvN5PH54FdW
I01HJSNHABEBAAGJATwEGAEIACYWIQREd3E2bSdJgo/Q5oMCOUFmiwvxYAUCWvU+
KwIbDAUJA8JnAAAKCRACOUFmiwvxYO4yB/wKYJvIwGBclcX1OcNPmGcR2ckLZCYP
Ixuo21RtACp2kSV2CqUsi437caV0YrdHiv4IUgT2mKmy2NP6MUhrh6r9zaUpW9kx
Sj5RZdLZZNhiCRB8Lw/Nis9g/OHksDhKbgM3po8iIq6HC3cC1jSDjxuVQhCt5FAm
7oCNj7Bmi1bHEZXihOyjyz8wNEVp5xqYTtRrEXIQY6hbxR7VXZb2z++jWlsF0IS3
1rMbVMNua84/W98JMFHvu/RNNpmnHvIQoEw7yjVZYt2aTJN/uuGtugNCZ+wri+xh
yl1VWoHhHrHs1zAWDiJSmB4k0zV9Yyw/OMMlPrmMX3SfFEjMDqnC1SNi
=hZua
-----END PGP PUBLIC KEY BLOCK-----
</pre>
<p>'
._("Please don't remove begin and end markers when submitting your GPG key.")
."</p>\n"
.'<h2>'._("Update your key in the input area below")."</h2>\n"
.'<p>'
._("Insert your (ASCII) public key here (made with gpg --export --armor KEYID):")
."</p>\n";

    if (!$newvalue)
      $newvalue = $row_user['gpg_key'];

    $input_specific .= '<textarea title="'._("New GPG key")
                      .'" cols="70" rows="10" '
                      .'wrap="virtual" name="newvalue">'.$newvalue
                      ."</textarea>\n";
    $input_specific .= '<p><input type="submit" name="test_gpg_key" value="'
                       ._("Test GPG key").'" /></p>'."\n<hr />\n";
    if ($test_gpg_key)
      $input_specific .= run_gpg_checks ($newvalue);
  }
elseif ($item == "email")
  {
    # First step.
    if (!$step)
      {
        $title = _("Change Email Address");
        $input_title = _('New email address:');
        $preamble = _("Changing your email address will require confirmation from
your new email address, so that we can ensure we have a good email address on
file.").'</p>
<p>'
._("We need to maintain an accurate email address for each user due to the
level of access we grant via this account. If we need to reach a user for
issues related to this server, it is important that we be able to do so.")
.'</p>
<p>'
._("Submitting the form below will mail a confirmation URL to the new email
address; visiting this link will complete the email change. The old address
will also receive an email message, this one with a URL to discard the
request.").'</p>
';
      }
    elseif ($step == "confirm")
      {
        $title = _("Confirm Email Change");
        $preamble = _('Push &ldquo;Update&rdquo; to confirm your email change');
        $input_title = _('Confirmation hash:');
        $input_specific = "<input type='text' readonly='readonly' "
          ."name='confirm_hash' value='"
          . htmlentities($confirm_hash, ENT_QUOTES) . "' />";
        $input_specific .= "<input type='hidden' name='step' value='confirm2' />";
      }
    elseif ($step == "discard")
      {
      # Avoid php warning about title not defined,
      # <http://savannah.gnu.org/support/?108964>.
        $title = _("Discard Email Change");
      }
  }
elseif ($item == "delete")
  {
    # First step.
    if (!$step)
      {
        $title = _("Delete Account");
        $input_title = _('Do you really want to delete your user account?');
        $input_specific = form_input("checkbox", "newvalue",
                                     "deletionconfirmed",
                                     ' title="'.("Delete Account").'"')
                          .' '._("Yes, I really do");
        $preamble = _("This process will require email confirmation.");
      }
    elseif ($step == "confirm")
      {
        $title = _("Confirm account deletion");
        $preamble = _('Push &ldquo;Update&rdquo; to confirm your account deletion');
        $input_title = _('Confirmation hash:');
        $input_specific = "<input type='text' readonly='readonly' "
                          .'name="confirm_hash" value="'
                          .htmlentities($confirm_hash).'" />';
        $input_specific .= "<input type='hidden' name='step' value='confirm2' />";
      }
  }

# fallback
if (!$title)
  $title = sprintf (_("Unknown user settings item (%s)"), $item);

########################################################################
# Actually print the HTML page.
site_user_header(array('title'=>$title,
                       'context'=>'account'));
if (!$input_title)
  $input_title = $title;

if ($preamble)
  print '<p>'.$preamble.'</p>';

print form_header($_SERVER['PHP_SELF'], false, "post");
print '<span class="preinput">';
if (!$input_specific)
  print '<label for="'.$form_item_name.'">';
print $input_title;
if (!$input_specific)
  print '</label>';
print '</span>';

# Print the usual input unless we have something specific.
if (!$input_specific)
  print '<br />
&nbsp;&nbsp;&nbsp;<input name="'.$form_item_name
        .'" id="'.$form_item_name.'" type="'.$input_type.'" />';
else
  print '<br />&nbsp;&nbsp;&nbsp;'.$input_specific;

# Add one more input if required.
if ($input2_type)
  {
    print '<br />
<span class="preinput"><label for="'.$form_item2_name.'">'
          .$input2_title.'</label></span>';
    print '<br />
&nbsp;&nbsp;&nbsp;<input type="'.$input2_type
          .'" id="'.$form_item2_name.'" name="'.$form_item2_name.'" />';
  }

# Add one more input if required.
if ($input3_type)
  {
    print '<br />
<span class="preinput"><label for="'.$form_item3_name.'">'
          .$input3_title.'</label></span>';
    print '<br />
&nbsp;&nbsp;&nbsp;<input type="'.$input3_type
          .'" id="'.$form_item3_name.'" name="'.$form_item3_name.'" />';
  }

# Add one more input if required.
if ($input4_type)
  {
    print '<br />
<span class="preinput"><label for="'.$form_item4_name.'">'
          .$input4_title.'</label></span>';
    print '<br />
&nbsp;&nbsp;&nbsp;<input type="'.$input4_type
          .'" id="'.$form_item4_name.'" name="'.$form_item4_name.'" />';
  }

print '<p><input type="hidden" name="item" value="'.$item.'" /></p>';
print '<p><input type="submit" name="update" value="'._("Update").'" /></p>';
print '</form>';
site_user_footer(array());
?>
