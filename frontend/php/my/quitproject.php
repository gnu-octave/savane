<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#                          Wallace Lee
#
#  Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

session_require(array('isloggedin'=>'1'));

# Make sure the user is actually member of the project
if (!$pending && !member_check(0,$quitting_group_id))
{ 
  exit_error("You are not member of this group.");
}
if ($pending && !member_check_pending(0,$quitting_group_id))
{ 
  exit_error("You have not requested inclusion in this group.");
}

if ($cancel)
{
  session_redirect($GLOBALS['sys_home']."my/groups.php");
}

# If we get here, the user is actually member of the group
# Ask him to confirm he understood what he is doing.
# If it is just a removal from a pending for inclusion request, no need
# to confirm, the loss is not worth it.
if (!$confirm && !$pending)
{  
  site_user_header(array('title'=>_("Quit a group"),
			 'context'=>'mygroups'));

  print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
  print '<input type="hidden" name="quitting_group_id" value="'.$quitting_group_id.'" />';  
  print '<span class="preinput">'.sprintf(_("You are about to leave the group %s, please confirm:"), group_getname($quitting_group_id)).'</span>';
print '<div class="center"><input type="submit" name="confirm" value="'._("Confirm").'" /> <input type="submit" name="cancel" value="'._("Cancel").'" /></div>';
  print '</form>';
}
else
{
  member_remove(user_getid(), sane_all("quitting_group_id"));
  
  # Mail the changes so the admins know what happened
  # unless it is a request for inclusion cancelled
  if ($pending)
    {
      $res_admin = db_query("SELECT user.user_id AS user_id, user.email AS email, user.user_name AS user_name FROM user,user_group "
			    . "WHERE user_group.user_id=user.user_id AND user_group.group_id=$quitting_group_id AND "
			    . "user_group.admin_flags = 'A'");
      
      while ($row_admin = db_fetch_array($res_admin)) 
	{
	  $to .= "$row_admin[email],";
	}
      if ($to) 
	{
	  $to = substr($to,0,-1);
	  $message = "This message is being sent to notify the administrator(s) of".	"\nproject ".group_getname($quitting_group_id)." that ".user_getname(0,1)." <".user_getname().">\n".
	    "has chosen to remove him/herself from the project.\n";
	}
      else
	{
          # No admin? The project is admin orphan, it will require the assistance
          # of site admins
	  $to = $GLOBALS[sys_mail_admin]."@".$GLOBALS[sys_mail_domain];
	  $message = "This message is being sent to notify the site administrator(s)\n".
	    "that the last administrator of the project ".group_getname($quitting_group_id)." (".user_getname(0,1)." <".user_getname().">)\n".
	"has chosen to remove him/herself from the project.\n\n".
	"As result, the project is administrator-orphan.\n";
	}
      
      $from = $GLOBALS[sys_mail_replyto]."@".$GLOBALS[sys_mail_domain];
      $subject = user_getname(0,1)." quitted the project ".group_getname($quitting_group_id);
      sendmail_mail($from,$to,$subject,$message);
    }

  session_redirect($GLOBALS['sys_home']."my/groups.php");
}

?>
