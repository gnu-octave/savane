<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
# This file is part of Savane.
# 
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.


$no_redirection=1;

require_once('../include/init.php');
require_once('../include/vars.php');
require_once('../include/account.php');
require_once('../include/exit.php');
session_require(array('isloggedin'=>'1'));

extract(sane_import('post',
  array('insert_license', 'rand_hash', 'form_license', 'form_license_other')));

if ($group_id && $insert_license && $rand_hash && $form_license)
{
   # Hash prevents them from updating a live, existing group account
   $result=db_query_escape(
     "UPDATE groups SET license='%s', license_other='%s'
      WHERE group_id='%s' AND rand_hash='__%s'",
     $form_license, $form_license_other,
     $group_id, $rand_hash
   );
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
  Header("Location: ".$GLOBALS['sys_home']."register/confirmation.php?show_confirm=y&group_id=".$group_id."&rand_hash=".$rand_hash);
  
} else {

  # Create the page header just like if there was not yet any group_id
  $group_id_not_yet_valid = $group_id;
  unset($group_id);
  $HTML->header(array('title'=>_("Project Type")));
  $group_id = $group_id_not_yet_valid;

  
  # get site-specific content
  utils_get_content("register/projecttype_long");
  
  print '<form action="confirmation.php" method="post">';
  print '<input type="hidden" name="show_confirm" value="y" />';
  print '<input type="hidden" name="group_id" value="'.$group_id.'" />';
  print '<input type="hidden" name="rand_hash" value="'.$rand_hash.'" />';
    
  # get more site-specific content
  $default_group_type_value = null; // can be defined in register/projecttype_short.txt
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

