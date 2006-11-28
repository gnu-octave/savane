<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require "../include/pre.php";    
require  $GLOBALS['sys_www_topdir']."/include/account.php";

# ###### function register_valid()
# ###### checks for valid register from form post

$res_lostuser = db_query("SELECT * FROM user WHERE confirm_hash='$confirm_hash'");
if (db_numrows($res_lostuser) > 1) {
	exit_error(_("Error"),_("This confirm hash exists more than once."));
}
if (db_numrows($res_lostuser) < 1) {
	exit_error(_("Error"),_("Invalid confirmation hash."));
}
$row_lostuser = db_fetch_array($res_lostuser);

if ($update && form_check($form_id) && $form_pw && !strcmp($form_pw,$form_pw2)) {
  db_query("UPDATE user SET "
	   . "user_pw='" . md5($form_pw) . "',"
	   . "confirm_hash='' WHERE "
	   . "confirm_hash='$confirm_hash'");
  
  form_clean($form_id);
  session_redirect($GLOBALS['sys_home']);
}

$HTML->header(array('title'=>_("Lost Password Login")));

print '<h3>'._("Lost Password Login").'</h3>';
print '<p>'._("Welcome").', '.$row_lostuser['user_name'].'.';
print ' '._("You may now change your password").'.</p>';

print form_header($_SERVER["PHP_SELF"]);

print '<div class="inputfield"><h5>'._("New Password:").'</h5>';
print form_input("password", "form_pw").'</div>';

print '<div class="inputfield"><h5>'._("New Password (repeat):").'</h5>';
print form_input("password", "form_pw2").'</div>';

print form_input("hidden", "confirm_hash", $confirm_hash);
print form_footer();

$HTML->footer(array());

?>
