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

require "../include/pre.php";
require "../include/vars.php";
session_require(array('isloggedin'=>'1'));
require "../include/account.php";

if ($group_id && $insert_license && $rand_hash && $form_license)
{
   # Hash prevents them from updating a live, existing group account
   $sql="UPDATE groups SET license='$form_license', license_other='$form_license_other' "
      . "WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
   $result=db_query($sql);
   if (db_affected_rows($result) < 1)
   {
     unset($group_id);
     exit_error(_("Error"),_("This is an invalid state.").' '._("Update query failed.").' '.sprintf (_("Please report to %s"),$GLOBALS['sys_email_address']));
   }

}
else
{
  unset($group_id);
  exit_error(_("Error"),_("This is an invalid state.").' '._("Some form variables were missing.").' '
	.sprintf (_("If you are certain you entered everything, %sPLEASE%s report to %s including info on your browser and platform configuration."),'<strong>','</strong>',$GLOBALS['sys_email_address']));
}

# If we have only one project type available, the next page is not a real step

if (db_numrows(db_query("SELECT type_id FROM group_type")) == 1) {
  Header("Location: ".$GLOBALS['sys_home']."register/confirmation.php?no_redirection=1&show_confirm=y&group_id=".$group_id."&rand_hash=".$rand_hash);
  
} else {

  # Create the page header just like if there was not yet any group_id
  $group_id_not_yet_valid = $group_id;
  unset($group_id);
  $HTML->header(array('title'=>_("Project Type")));
  $group_id = $group_id_not_yet_valid;

  
  # get site-specific content
  utils_get_content("register/projecttype_long");
  
  print '<form action="confirmation.php" method="post">';
  print '<input type="hidden" name="no_redirection" value="1" />';
  print '<input type="hidden" name="show_confirm" value="y" />';
  print '<input type="hidden" name="group_id" value="'.$group_id.'" />';
  print '<input type="hidden" name="rand_hash" value="'.$rand_hash.'" />';
    
  # get more site-specific content
  utils_get_content("register/projecttype_short");
  

  print show_group_type_box('group_type', $default_group_type_value, true);
  
  print '<br /><br />';
  
  print '<div align="center">';
  
  print '<input type=submit name="Submit" value="'._("Next Step: Confirmation").'" />';
  print '</div>';
  print '</form>';
  
  print '<div align="center"><span class="error">'._("Do not click back button (unless asked to).").'</span></div>';
     
  $HTML->footer(array());
}

?>

