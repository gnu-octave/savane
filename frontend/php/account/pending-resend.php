<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 1999-2000 (c) The SourceForge Crew
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
#
#
#
#

require "../include/pre.php";    

# Block here potential robots
dnsbl_check();
# Block banned IP
spam_bancheck();

$res_user = db_query("SELECT * FROM user WHERE user_name='$form_user'");
$row_user = db_fetch_array($res_user);

# only mail if pending
if ($row_user['status'] == 'P') {

  # send mail
  $message = sprintf(_("Thank you for registering on the %s web site."),$GLOBALS['sys_name'])."\n"
    . _("In order to complete your registration, visit the following url:")."\n\n"
    . $GLOBALS['sys_https_url'].$GLOBALS['sys_home']."account/verify.php?confirm_hash=$row_user[confirm_hash]\n\n"
    . _("Enjoy the site").".\n\n"
    . sprintf(_("-- the %s team."),$GLOBALS['sys_name'])."\n";
	
	
  sendmail_mail($GLOBALS['sys_replyto'] . "@".$GLOBALS['sys_lists_domain'],
		$row_user['email'],
		$GLOBALS['sys_name'] . " " . _("Account Registration"),
		$message);

  $HTML->header(array(title=>_("Account Pending Verification")));


  print '<h3>'._("Pending Account").'</h3>';
  print '<p>'._("Your email confirmation has been resent. Visit the link in this email to complete the registration process.").'</p>';
  print '<p><a href="'.$GLOBALS['sys_home'].'">['._("Return to Home Page").']</a></p>';
 
} else {
  exit_error(_("Error"),_("This account is not pending verification."));
}

$HTML->footer(array());

?>
