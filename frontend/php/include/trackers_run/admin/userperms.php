<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2000-2003 (c) Free Software Foundation
#                          Mathieu Roy <yeupou--gnu.org>
#
#  Copyright 2004-2005 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
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

###
### WARNING: whenever you modify this page, you should modify 
###   project/admin/userperms.php as well

require_directory("project");
$is_admin_page='y';

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($update)  
{

  # If the group entry does not exist, create it
  if (!db_result(db_query("SELECT groups_default_permissions_id FROM groups_default_permissions WHERE group_id='$group_id'"), 0, "groups_default_permissions_id"))
    {
      db_query("INSERT INTO groups_default_permissions (group_id) VALUES ($group_id)"); 
    }
  
  # ##### Update posting restrictions
  $newitem_restrict_event1 = ARTIFACT."_restrict_event1";
  $newitem_restrict_event2 = ARTIFACT."_restrict_event2";
  $flags = ($$newitem_restrict_event2)*100 + $$newitem_restrict_event1;
  if (!$flags)
    { 
      # if equal to 0, manually set to NULL, since 0 have a different meaning
      $flags = 'NULL';
    }

  # Update the table
  $sql = 'UPDATE groups_default_permissions SET ' 
     .ARTIFACT."_rflags=".$flags." "
     ."WHERE group_id='$group_id'";

  $result = db_query($sql);
  
  if ($result) 
    {
      group_add_history('Changed Posting Restrictions','',$group_id);
      fb(_("Posting restrictions updated."));
    }
  else
    {
      print db_error();
      fb(_("Unable to change posting restrictions."), 0);
    }
}


# start HTML
trackers_header_admin(array ('title'=>_("Set Permissions")));

########################### POSTING RESTRICTIONS
print '<h3>'._("Posting Restrictions").'</h3>';
print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="group" value="'.$group_name.'" />';

## post restriction
print '<span class="preinput">'._("Authentication level required to be able to post new items on this tracker:").' </span><br />';
print '&nbsp;&nbsp;&nbsp;';
print html_select_restriction_box(ARTIFACT, group_getrestrictions($group_id, ARTIFACT), $group,'', 1);

## comment restriction
print '<br /><br /><span class="preinput">'._("Authentication level required to be able to post comments (and to attach files) on this tracker:").' </span><br />';
print '&nbsp;&nbsp;&nbsp;';
print html_select_restriction_box(ARTIFACT, group_getrestrictions($group_id, ARTIFACT, 2), $group,'', 2);

print '
<p align="center"><input type="submit" name="update" value="'._("Update Permissions").'" /></p></form>';

trackers_footer(array())

?>
