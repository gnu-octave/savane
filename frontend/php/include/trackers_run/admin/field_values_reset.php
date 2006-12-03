<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2005-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


if ($group_id && user_ismember($group_id,'A'))
{
  # Initialize global bug structures
  trackers_init($group_id);

  if (!$field)
    { exit_missing_param(); }

  if ($cancel)
    {
      session_redirect($GLOBALS['sys_home'].ARTIFACT."/admin/field_values.php?group_id=$group_id&field=$field&list_value=1");
    }
  
  if (!$confirm)
    {  
      $hdr = sprintf(_("Reset Values of '%s'"),trackers_data_get_label($field));
      trackers_header_admin(array ('title'=>$hdr));
      
      print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
      print '<input type="hidden" name="group_id" value="'.$group_id.'" />';
      print '<input type="hidden" name="field" value="'.$field.'" />';
      print '<span class="preinput">'.sprintf(_("You are about to reset values of the field %s. This action will not be undoable, please confirm:"),trackers_data_get_label($field)).'</span>';
      print '<div class="center"><input type="submit" name="confirm" value="'._("Confirm").'" /> <input type="submit" name="cancel" value="'._("Cancel").'" /></div>';
      print '</form>';
    }
  else
    {
      db_query("DELETE FROM ".ARTIFACT."_field_value WHERE group_id='".addslashes($group_id)."' AND bug_field_id = '".trackers_data_get_field_id($field)."'");
      session_redirect($GLOBALS['sys_home'].ARTIFACT."/admin/field_values.php?group_id=$group_id&field=$field&list_value=1");
    }

}
else
{
#browse for group first message
  if (!$group_id)
    {
      exit_no_group();
    }
  else
    {
      exit_permission_denied();
    }

}


?>
