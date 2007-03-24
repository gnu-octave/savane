<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2004 (c) Mathieu Roy <yeupou---gnu.org>
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

require_once('../include/sendmail.php');

function send_new_project_email($group_id) 
{
  $res_grp = db_query("SELECT * FROM groups WHERE group_id='$group_id'");

  if (db_numrows($res_grp) < 1) {
    echo ("Group [ $group_id ] does not exist. Shame on you, sysadmin.");
  }

  $row_grp = db_fetch_array($res_grp);

  $res_admins = db_query("SELECT user.user_name,user.email FROM user,user_group WHERE "
			 . "user.user_id=user_group.user_id AND user_group.group_id='$group_id' AND "
			 . "user_group.admin_flags='A'");

  if (db_numrows($res_admins) < 1) {
    echo ("Group [ $group_id ] does not seem to have any administrators.");
  }

  # send one email per admin
  utils_get_content("admin/proj_email");
  while ($row_admins = db_fetch_array($res_admins)) {
    $message = approval_gen_email($row_grp['group_name'],$row_grp['unix_group_name']);


    sendmail_mail($GLOBALS['sys_email_adress'],
		  $row_admins['email'],
		  "Project Approved",
		  $message);
    
  }
}

?>
