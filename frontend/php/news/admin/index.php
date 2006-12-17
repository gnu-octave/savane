<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
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

require '../../include/pre.php';

if ($group_id && user_ismember($group_id,'A'))
{
  $grp=project_get_object($group_id);

  if (!$project->Uses("news"))
    { exit_error(_("Error"),_("This Project Has Turned Off News Tracker")); }
  
  if ($update) 
    { 
      db_query("UPDATE groups SET "
	       ."new_news_address=".($form_news_address? "'$form_news_address' " : "''")
	       . " WHERE group_id=$group_id");
      fb("Updated");
    }
  
  site_project_header(array('group'=>$group_id,'context'=>'anews'));


  print '<p>'._("You can view/change all of this tracker configuration from here.").'</p>';
  
  $res_grp = db_query("SELECT new_news_address FROM groups WHERE group_id=$group_id");
  $row_grp = db_fetch_array($res_grp);
  
  
  print '<h3>'._("News Tracker Email Notification Settings").'</h3>';
  print '
<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="group_id" value="'.$group_id.'" />';
  print '<span class="preinput">'._("Carbon-Copy List:").'</span><br />&nbsp;&nbsp;<INPUT TYPE="TEXT" NAME="form_news_address" VALUE="'.$row_grp['new_news_address'].'" SIZE="40" MAXLENGTH="255" />';
  print '
<p align="center"><input type="submit" name="update" value="'._("Update").'" />
</form>
';

  site_project_footer(array());
}
else
{
  exit_permission_denied();
}


?>
