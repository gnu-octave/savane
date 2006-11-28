<?php
# This file is part of the Savane project
# <http:#gna.org/projects/savane/>
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

# Block here potential robots
dnsbl_check();
# Block banned IP
spam_bancheck();

# Logged users have no business here
if (user_isloggedin())
{ session_redirect($GLOBALS['sys_home']."my/"); }


####### first check for valid login, if so, redirect

if (sane_post("update"))
{
  $form_loginname = sane_post('form_loginname');
   
  # first check just confirmation hash
  $res = db_query('SELECT confirm_hash,status FROM user WHERE '
		  .'user_name="'.$form_loginname.'" and status<>"SQD"');

  if (db_numrows($res) < 1) 
    {
      exit_error(_("Invalid username."));
    }

  $usr = db_fetch_array($res);  
  $confirm_hash = sane_post('confirm_hash');  
  if ($confirm_hash != $usr['confirm_hash']) 
    {
      exit_error(_("Invalid confirmation hash"));
    }

  # then check valid login	
  if (session_login_valid($form_loginname, 
			  sane_post('form_pw'),
			  1, # accept not yet confirmed accounts
			  0, # not a cookie for a year
			  0, # not crypted
			  session_issecure())) 
    {
      $res = db_query("UPDATE user SET status='A' WHERE user_name='".sane_post('form_loginname')."'");
      session_redirect($GLOBALS['sys_home']."account/first.php");
    }
}

site_header(array('title'=>_("Login")));

print '<h3> '.sprintf(_("%s Account Verification"),$GLOBALS['sys_name']).'</h3>';
print '<p>'._("In order to complete your registration, login now. Your account will then be activated for normal logins").'.</p>';


print form_header($_SERVER["PHP_SELF"], $form_id);

print '<p><span class="preinput">'._("Login Name").':</span><br />&nbsp;&nbsp;';
print form_input("text", "form_loginname");
print '</p>';

print '<p><span class="preinput">'._("Password").':</span><br />&nbsp;&nbsp;';
print form_input("password", "form_pw");
print '</p>';

# must accept all ways of providing confirm_hash (POST & GET), because
# in the mail it is a POST but if the form fail (wrong password, etc), it will
# be a GET
print form_input("hidden", "confirm_hash", sane_all('confirm_hash'));
print form_footer(_("Login"));

site_footer(array());

?>
