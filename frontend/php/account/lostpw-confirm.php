<?php
# Send confirmation message for password recovery.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004-2005 Joxean Koret <joxeankoret--yahoo.es>
# Copyright (C) 2017, 2018, 2019 Ineiev <ineiev--gnu.org>
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
require_once('../include/session.php');
require_once('../include/sendmail.php');
require_once('../include/database.php');

register_globals_off();

extract(sane_import('post', array('form_loginname')));

# Logged users have no business here.
if (user_isloggedin())
  session_redirect($GLOBALS['sys_home']."my/");

# Block here potential robots.
dnsbl_check();

# CERN_SPECIFIC: here we also have a speech about AFS which must not be
# hardcoded.
if ($GLOBALS['sys_use_pamauth'] == "yes")
  {
    db_execute("SELECT user_pw FROM user WHERE user_name=?", array($form_loginname));
    $row_pw = db_fetch_array();
    if ($row_pw['user_pw'] == 'PAM')
      {
        $HTML->header(array('title'=>"Lost Password Confirmation"));
        print "<p>"
._('This account uses an AFS password. <strong>You cannot change your
AFS password via Savane</strong>. Contact the AFS managers.');
        $HTML->footer(array());
        exit;
      }
  }
# /CERN_SPECIFIC

$confirm_hash = md5(strval(time()) . strval(rand()));
# Account check.
$res_user = db_execute("SELECT * FROM user WHERE user_name=? AND status='A'",
                       array($form_loginname));
if (db_numrows($res_user) < 1)
  {
    $res_user = db_execute("SELECT status FROM user WHERE user_name=? AND status='P'",
                           array($form_loginname));
    if (db_numrows($res_user) > 0)
      $msg =
 _("This account hasn't been activated, please contact website administration");
    else
      $msg = _("This account does not exist");

    exit_error(_("Invalid User"), $msg);
  }
$row_user = db_fetch_array($res_user);

# Notification count check:
# This code would allow to define the number of request that can be made
# per hour.
# By default, we set it to one.
$notifications_max = 1;
$email_notifications = 0;

$res_emails = db_execute("SELECT count FROM user_lostpw WHERE user_id=? and "
                         ."DAYOFYEAR(date) = DAYOFYEAR(CURRENT_DATE) AND "
                         ."HOUR(DATE) = HOUR(NOW())",
                         array($row_user['user_id']));
if (db_numrows($res_emails) < 1)
  $email_notifications = 0;
else
  {
    $row_emails = db_fetch_array($res_emails);
    $email_notifications = strval($row_emails[0]);
  }

if ($email_notifications == 0)
  # This would be made empty by itself. We could have the login form
  # to remove old request.
  # But sv_cleaner will take care of it.
  db_execute("INSERT INTO user_lostpw VALUES (?, CURRENT_TIMESTAMP, 1)", array($row_user['user_id']));
else
  {
    if ($email_notifications >= $notifications_max)
      exit_error(_("An email for your lost password has already been sent.
Please wait one hour and try again."));
    else
      db_execute("UPDATE user_lostpw SET count = count + 1
                  WHERE user_id = ? AND DAYOFYEAR(DATE) = DAYOFYEAR(CURRENT_DATE)
                                    AND HOUR(DATE) = HOUR(NOW())",
                   array($row_user['user_id']));
  }

db_execute("UPDATE user SET confirm_hash=? WHERE user_id=?",
           array($confirm_hash, $row_user['user_id']));

# TRANSLATORS: the argument is a domain (like "savannah.gnu.org"
# vs. "savannah.nongnu.org").
$message = sprintf(_("Someone (presumably you) on the %s site requested
a password change through email verification."), $GLOBALS['sys_default_domain']);
$message .= ' ';
$message .= _("If this was not you, this could pose a security risk for the system.")
            ."\n\n";
$message .= sprintf(
_('The request came from %s
(IP: %s; port: %s; user agent: %s)'), gethostbyaddr($_SERVER['REMOTE_ADDR']),
    $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
    $_SERVER['HTTP_USER_AGENT'])."\n\n";
$message .=
_("If you requested this verification, visit this URL to change your password:")
."\n\n";
$message .= $GLOBALS['sys_https_url'].$GLOBALS['sys_home']
            ."account/lostlogin.php?confirm_hash=".$confirm_hash."\n\n";
# FIXME: There should be a discard procedure.
$message .= _("In any case make sure that you do not disclose this URL to
somebody else, e.g. do not mail this to a public mailinglist!\n\n");
$message .= sprintf(_("-- the %s team."),$GLOBALS['sys_name'])."\n";

# We should not add i18n to admin messages.
$message_for_admin =
sprintf(("Someone attempted to change a password via email verification\n"
. "on %s\n\n"
. "Someone is maybe trying to steal a user account.\n\n"
. "The user affected is %s\n\n"
. "The request comes from %s "
. "(IP: %s port: %s), user agent: %s\n\n"
. "Date: %s"),
   $GLOBALS['sys_default_domain'],
   $form_loginname,
   gethostbyaddr($_SERVER['REMOTE_ADDR']),
   $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
   $_SERVER['HTTP_USER_AGENT'],
   gmdate('D, d M Y H:i:s \G\M\T'))
. "\n";

$encrypted_message = "";
$gpg_error = "";
if(user_get_preference("email_encrypted", $row_user['user_id']))
  {
    $cmd = 'perl ../../perl/encrypt-to-user/index.pl '
    . '--gpg="' . $GLOBALS['sys_gpg_name'] . '" '
    . '--user="' . $row_user['user_id'] . '" '
    . '--dbname="' . $sys_dbname . '" '
    . '--dbhost="' . $sys_dbhost . '"';

    $d_spec = array(
        0 => array("pipe", "r"), 1 => array("pipe", "w"),
        2 => array("file", "/dev/null", "a"));

    $gpg_proc = proc_open($cmd, $d_spec, $pipes, NULL, $_ENV);
    fwrite($pipes[0], $sys_dbuser."\n");
    fwrite($pipes[0], $sys_dbpasswd."\n");
    fwrite($pipes[0], $message);
    fclose($pipes[0]);
    $encrypted_message = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $gpg_result = proc_close($gpg_proc);

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
      }
  }

if($encrypted_message != "")
  $message = $encrypted_message;

sendmail_mail($GLOBALS['sys_mail_replyto']."@".$GLOBALS['sys_mail_domain'],
              $row_user['email'],
              $GLOBALS['sys_default_domain']." Verification",
              $message);

sendmail_mail($GLOBALS['sys_mail_replyto']."@".$GLOBALS['sys_mail_domain'],
              $GLOBALS['sys_mail_admin']."@".$GLOBALS['sys_mail_domain'],
              "password change - ".$GLOBALS['sys_default_domain'],
              $message_for_admin,
              0,
              "lostpw");

fb(_("Confirmation mailed"));

$HTML->header(array('title'=>_("Lost Password Confirmation")));
print '<p>'._("An email has been sent to the address you have on file.").'</p>';
print '<p>'
._("Follow the instructions in the email to change your account password.")
.'</p>';

if($encrypted_message === "")
  {
    if(user_get_preference("email_encrypted", $row_user['user_id']))
      print '<p><strong>'.$gpg_error.'<strong></p>';
    print '<blockquote><p>'._("Note that the message was sent unencrypted.
In order to use encryption, register an encryption-capable GPG key
and set the <b>Encrypt emails when resetting password</b> checkbox
in your account settings.").'</p></blockquote>';
  }
else
  print '<p>'._("Note that it was encrypted with your registered GPG key.").'</p>';

$HTML->footer(array());
?>
