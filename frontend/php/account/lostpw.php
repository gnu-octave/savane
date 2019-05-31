<?php
# Request password recovery.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2004 Mathieu Roy <yeupou--at--gnu.org>
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

require_once('../include/init.php');

# Logged users have no business here.
if (user_isloggedin())
  session_redirect($GLOBALS['sys_home']."my/");

# Block here potential robots.
dnsbl_check();

$HTML->header(array('title'=>_("Lost Account Password")));
print '<p><strong>'._("Lost your password?")."</strong></p>\n";

print '<p>'._("The form below will email a URL to the email address we have on
file for you. In this URL is a 128-bit confirmation hash for your account.
Visiting the URL will allow you to change your password online and
login.")."</p>\n";
print '<p class="warn">'._("This will work only if your account was already
successfully registered and activated. Note that accounts that are not
activated within the three days next to their registration are automatically
deleted.")."</p>\n";

print '<form action="lostpw-confirm.php" method="post">';
print '<p><span class="preinput"> &nbsp;&nbsp;';
print _("Login Name:");
print ' &nbsp;&nbsp;</span><input type="text" name="form_loginname" /> &nbsp;&nbsp;';
print '<input type="submit" name="send" value="'._("Send lost password hash")
       ."\" /></p>\n";
print "</form>\n";

$HTML->footer(array());
?>
