<?php
# Change user's password
#
# This file is part of the Savane project
# 
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2017 Ineiev
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

require_once('../include/init.php');    
require_once('../include/account.php');
session_require(array('group'=>'1','admin_flags'=>'A'));

extract(sane_import('request', array('user_id')));
extract(sane_import('post', array('update', 'form_pw', 'form_pw2')));

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	
{
  global $update, $user_id;

	if (!$update) {
		return 0;
	}
	
	// check against old pw
	db_execute("SELECT user_pw FROM user WHERE user_id=?", array($user_id));

	if (!$GLOBALS['form_pw']) {
		return 0;
	}
	if ($GLOBALS['form_pw'] != $GLOBALS['form_pw2']) {
		return 0;
	}
	if (!account_pwvalid($GLOBALS['form_pw'])) {
		return 0;
	}
	
	// if we got this far, it must be good
	db_autoexecute('user', array('user_pw' =>
                       account_encryptpw($GLOBALS['form_pw'])),
		       DB_AUTOQUERY_UPDATE, "user_id=?", array($user_id));
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	$HTML->header(array('title' => no_i18n("Change Password")));
  print '
<p><strong>'.no_i18n('Savannah Change Confirmation').'</strong></p>
<p>'.no_i18n('Congratulations, genius. You have managed to change this user\'s
password.').'
</p>
<p>'.sprintf(no_i18n('You should now <a href="%s">Return to UserList</a>.'),
'/admin/userlist.php')."</p>\n";

} else { // not valid registration, or first time to page
	$HTML->header(array('title' => "Change Password"));

  print '
<p><strong>'.no_i18n('Savannah Password Change').'</strong></p>
<form action="user_changepw.php" method="post">
<p>'.no_i18n('New Password:').'
<br /><input type="password" name="form_pw" />
</p>
<p>'.no_i18n('New Password (repeat):').'
<br /><input type="password" name="form_pw2" />
<input type=hidden name="user_id" value="'. $user_id .'">
</p>
<p><input type="submit" name="update" value="'.no_i18n('Update').'" />
</p>
</form>
';

}
$HTML->footer(array());
?>
