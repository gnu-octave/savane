<?php
# URL sent by mail to recover a password (not a login, despite the name)
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

require_once('../include/init.php');
require_once('../include/database.php');
require_once('../include/account.php');
require_once('../include/form.php');


extract(sane_import('request', array('confirm_hash')));
extract(sane_import('post', array('form_id', 'update', 'form_pw', 'form_pw2')));

# ###### function register_valid()
# ###### checks for valid register from form post

$res_lostuser = db_execute("SELECT * FROM user WHERE confirm_hash=?", array($confirm_hash));
if (db_numrows($res_lostuser) > 1) {
	exit_error(_("Error"),_("This confirm hash exists more than once."));
}
if (db_numrows($res_lostuser) < 1) {
	exit_error(_("Error"),_("Invalid confirmation hash."));
}
$row_lostuser = db_fetch_array($res_lostuser);

if ($update && form_check($form_id) && $form_pw && !strcmp($form_pw, $form_pw2) && account_pwvalid($form_pw)) {
  db_autoexecute('user',
    array('user_pw' => account_encryptpw($form_pw), 'confirm_hash' => ''),
    DB_AUTOQUERY_UPDATE,
    "confirm_hash=?", array($confirm_hash));
  
  form_clean($form_id);
  session_redirect($GLOBALS['sys_home']);
}

site_header(array('title'=>_("Lost Password Login")));

print '<h3>'._("Lost Password Login").'</h3>';
print '<p>'._("Welcome").', '.$row_lostuser['user_name'].'.';
print ' '._("You may now change your password").'.</p>';

print form_header($_SERVER['PHP_SELF']);

print '<div class="inputfield"><h5>'._("New password / passphrase:").'</h5>';
print '<div>'.account_password_help().'</div>';
print form_input("password", "form_pw").'</div>';

print '<div class="inputfield"><h5>'._("New Password (repeat):").'</h5>';
print form_input("password", "form_pw2").'</div>';

print form_input("hidden", "confirm_hash", $confirm_hash);
print form_footer();

$HTML->footer(array());
