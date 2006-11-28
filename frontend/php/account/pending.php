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
register_globals_off();

$form_user = sane_all("form_user");

# Logged users have no business here
if (user_isloggedin())
{ session_redirect($GLOBALS['sys_home']."my/"); }


$HTML->header(array(title=>_("Pending Account")));


echo '<h3>'._("Pending Account").'</h3>';

echo '<p>'._("Your account is currently pending your email confirmation. Visiting the link sent to you in this email will activate your account.").'</p>';

echo '<p>'._("If you need this email resent, please click below and a confirmation email will be sent to the email address you provided in registration.").'</p>';

echo '<p><a href="pending-resend.php?form_user='.$form_user.'">['._("Resend Confirmation Email").']</a>';
echo '<br /><a href="/">';
printf (_("[Return to %s]"),$GLOBALS['sys_name']);
echo '</a></p>';

$HTML->footer(array());

?>
