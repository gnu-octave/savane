<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2004-2005 (c) Mathieu Roy <yeupou--gnu.org>
#                          Joxean Koret <joxeankoret--yahoo.es>
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

require_once('../include/init.php');
require_once('../include/sane.php');
require_once('../include/session.php');
require_once('../include/sendmail.php');

register_globals_off();
#input_is_safe();
#mysql_is_safe();


extract(sane_import('post', array('form_loginname')));


# Logged users have no business here
if (user_isloggedin())
{ session_redirect($GLOBALS['sys_home']."my/"); }

# Block here potential robots
dnsbl_check();
# Block banned IP
spam_bancheck();

# CERN_SPECIFIC: here we also have a speech about AFS which must not be
# hardcoded
if ($GLOBALS['sys_use_pamauth'] == "yes") {
  db_execute("SELECT user_pw FROM user WHERE user_name=?", array($form_loginname));
  $row_pw = db_fetch_array();
  if ($row_pw['user_pw'] == 'PAM') {
    $HTML->header(array('title'=>"Lost Password Confirmation"));
    print "<p>This account uses an AFS password. <strong>You cannot change your
           AFS password via Savane</strong>. Contact the AFS managers.";
    $HTML->footer(array());
    exit;
  }
}
# /CERN_SPECIFIC

$confirm_hash = md5(strval(time()) . strval(rand()));

########################
# Account check
$res_user = db_execute("SELECT * FROM user WHERE user_name=? AND status='A'", array($form_loginname));
if (db_numrows($res_user) < 1)
{
  exit_error(_("Invalid User"), _("This account does exist or has not been activated"));
}
$row_user = db_fetch_array($res_user);

########################
# Notification count check:
# This code would allow to define the number of request that can be made
# per hour.
# By default, we set it to one
$notifications_max = 1;
$email_notifications = 0;

$res_emails = db_execute("SELECT count FROM user_lostpw WHERE user_id=? and DAYOFYEAR(date) = DAYOFYEAR(CURRENT_DATE) AND HOUR(DATE) = HOUR(NOW())", array($row_user['user_id']));

if (db_numrows($res_emails) < 1)
{
  $email_notifications = 0;
}
else
{
  $row_emails = db_fetch_array($res_emails);
  $email_notifications = strval($row_emails[0]);
}

if ($email_notifications == 0)
{
  # This would be made empty by itself. We could have the login form
  # to remove old request.
  # But sv_cleaner will take care of it.
  db_execute("INSERT INTO user_lostpw VALUES (?, CURRENT_TIMESTAMP, 1)", array($row_user['user_id']));
}
else
{
  if ($email_notifications >= $notifications_max)
    {
      exit_error(_("An email for your lost password has already been sent. Please wait one hour and try again."));
    }
  else
    {
      db_execute("UPDATE user_lostpw SET count = count + 1
                  WHERE user_id = ? AND DAYOFYEAR(DATE) = DAYOFYEAR(CURRENT_DATE)
	                            AND HOUR(DATE) = HOUR(NOW())",
		 array($row_user['user_id']));
    }
}


# If we get here, it is OK to continue

db_execute("UPDATE user SET confirm_hash=? WHERE user_id=?",
	   array($confirm_hash, $row_user['user_id']));

$message = sprintf(_("Someone (presumably you) on the %s site requested a password change through email verification."),$GLOBALS['sys_default_domain']);
$message .= ' ';
$message .= _("If this was not you, this could pose a security risk for the system.")."\n\n";
$message .= sprintf(_("The request came from %s"),gethostbyaddr($_SERVER['REMOTE_ADDR']))."\n";
$message .= '(IP: '.$_SERVER['REMOTE_ADDR'].' port: '.$_SERVER['REMOTE_PORT'].")\n";
$message .= _("with").' '.$_SERVER['HTTP_USER_AGENT']."\n\n";
$message .= _("If you requested this verification, visit this URL\nto change your password:")."\n\n";
$message .= $GLOBALS['sys_https_url'].$GLOBALS['sys_home']."account/lostlogin.php?confirm_hash=".$confirm_hash."\n\n";
# FIXME: There should be a discard procedure
#$message .= _("If you did not request this verification, please visit this URL to cancel it.")."\n\n";
$message .= _("In any case make sure that you do not disclose this url to\n somebody else, e.g. do not mail this to a public mailinglist!\n\n");
$message .= sprintf(_("-- the %s team."),$GLOBALS['sys_name'])."\n";

# We should not add i18n to admin messages
$message_for_admin =
"Someone attempted to change a password via email verification\n"
. "on ".$GLOBALS['sys_default_domain']."\n\n"
. "Someone is maybe trying to steal a user account.\n\n"
. "The user affected is ".$form_loginname."\n\n"
. "The request comes from ".gethostbyaddr($_SERVER['REMOTE_ADDR'])." "
. "(IP: ".$_SERVER['REMOTE_ADDR']." port: ".$_SERVER['REMOTE_PORT'].") "
. "with ".$_SERVER['HTTP_USER_AGENT']."\n\n"
. "Date:"
. gmdate('D, d M Y H:i:s \G\M\T')
     . "\n";

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
print '<p>'._("Follow the instructions in the email to change your account password.").'</p>';
;

$HTML->footer(array());
