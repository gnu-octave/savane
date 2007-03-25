<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
# 
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

require_once('include/init.php');
require_once('include/sendmail.php');
register_globals_off();

$touser = sane_all("touser");
$fromuser = sane_all("fromuser");
$send_mail = sane_all("send_mail");
$subject = sane_all("subject");
$body = sane_all("body");
$freedback = sane_all("feedback");

if (user_isloggedin()) 
{


  if ($touser) 
    {
      # Search infos in the database about the user
      $result=db_query("SELECT email,user_name FROM user WHERE user_id='$touser' AND (status='A' OR status='SQD')");
      if (!$result || db_numrows($result) < 1) 
	{
	  exit_error(_('That user does not exist'));
	}
    } 
  else
    {
      exit_missing_param();
    } 

  
  if ($send_mail) 
    {
    
      if (!$subject || !$body || !$fromuser) 
	{
	  # Force them to enter all vars
	  exit_missing_param();
	}
      else 
	{
	  # Let sendmail_mail() figuring out real email addresses
	  sendmail_mail($fromuser, $touser, $subject, stripslashes($body));
	  $HTML->header(array('title'=>_('Message Sent')));
	  print html_feedback_top($GLOBALS['feedback']);
	  $HTML->footer(array());
	  exit;
	}
    
    } 
  else 
    {
    
      $HTML->header(array('title'=>_('Send a message')));
      sendmail_form_message($_SERVER["PHP_SELF"], $touser);
      $HTML->footer(array());
    
    }

} else {
  
  # Not logged-in, no mail to be sent.
  exit_not_logged_in();  
  
}
