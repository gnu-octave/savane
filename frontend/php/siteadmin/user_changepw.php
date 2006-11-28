<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
// 
//  Copyright 1999-2000 (c) The SourceForge Crew
//
// The Savane project is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// The Savane project is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with the Savane project; if not, write to the Free Software
// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


require "../include/pre.php";    
require  $GLOBALS['sys_www_topdir']."/include/account.php";
session_require(array('group'=>'1','admin_flags'=>'A'));

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	
{
	global $form_user;

	if (!$GLOBALS["Update"]) {
		return 0;
	}
	
	// check against old pw
	db_query("SELECT user_pw FROM user WHERE user_id=$form_user");

	if (!$GLOBALS['form_pw']) {
		$GLOBALS['register_error'] = "You must supply a password.";
		return 0;
	}
	if ($GLOBALS['form_pw'] != $GLOBALS['form_pw2']) {
		$GLOBALS['register_error'] = "Passwords do not match.";
		return 0;
	}
	if (!account_pwvalid($GLOBALS['form_pw'])) {
		return 0;
	}
	
	// if we got this far, it must be good
	db_query("UPDATE user SET user_pw='" . md5($GLOBALS['form_pw']) . "',"
		. "unix_pw='" . account_genunixpw($GLOBALS['form_pw']) . "' WHERE "
		. "user_id=" . $form_user);
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	$HTML->header(array(title=>"Change Password"));
?>
<p><strong>Savannah Change Confirmation</strong>
<p>Congratulations, genius. You have managed to change this user's password.
<p>You should now <a href="/admin/userlist.php">Return to UserList</a>.
<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>"Change Password"));

?>
<p><strong>Savannah Password Change</strong>
<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
// if ($register_error) print "<p>$register_error"; ?>
<form action="user_changepw.php" method="post">
<p>New Password:
<br /><input type="password" name="form_pw" />
<p>New Password (repeat):
<br /><input type="password" name="form_pw2" />
<INPUT type=hidden name="form_user" value="<?php print $form_user; ?>">
<p><input type="submit" name="Update" value="Update" />
</form>

<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
}
$HTML->footer(array());

?>
