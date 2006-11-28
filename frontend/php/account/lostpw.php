<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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

# Logged users have no business here
if (user_isloggedin())
{ session_redirect($GLOBALS['sys_home']."my/"); }

# Block here potential robots
dnsbl_check();
# Block banned IP
spam_bancheck();

$HTML->header(array('title'=>_("Lost Account Password")));


print '<p><strong>'._("Lost your password?").'</strong></p>';

print '<p>'._("Hey... losing your password is serious business. It compromises the security of your account, your projects, and this site.").'</p>';

print '<p>'._("The form below will email a URL to the email address we have on file for you. In this URL is a 128-bit confirmation hash for your account. Visiting the URL will allow you to change your password online and login.").'</p>';
print '<p class="warn">'._("This will work only if your account was already successfully registered and activated. Note that accounts that are not activated within the three days next to their registration are automatically deleted.").'</p>';

print '<form action="lostpw-confirm.php" method="post">';
print '<p><input type="hidden" name="form_user" value="'.$form_user.'" /><span class="preinput"> &nbsp;&nbsp;';
print _("Login Name:");
print ' &nbsp;&nbsp;</span><input type="text" name="form_loginname" /> &nbsp;&nbsp;';
print '<input type="submit" name="send" value="'._("Send lost password hash").'" /></p>';
print '</form>';

$HTML->footer(array());

?>
