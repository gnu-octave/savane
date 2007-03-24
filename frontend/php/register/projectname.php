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

require_once('../include/init.php');

session_require(array('isloggedin' => '1'));

require('../include/account.php');
require('../include/exit.php');
require('../include/Group.class');

extract(sane_import('post',
  array('insert_purpose', 'form_purpose', 'form_required_sw',
	'form_comments', 'form_full_name', 'form_unix_name')));

# push received vars
if ($insert_purpose && $form_purpose) { 

	mt_srand((double)microtime()*1000000);
	$random_num=mt_rand(0,1000000);

	# make group entry
	$result = db_query_escape(
          "INSERT INTO groups (group_name,is_public,unix_group_name,status,license,
                               register_purpose,required_software,
                               other_comments,register_time,
                               license_other,rand_hash)
           VALUES ('__%s',1,'__%s','I','__%s',
                   '%s','%s',
                   '%s','%s',
                   '__%s','__%s')",
	  $random_num, $random_num, $random_num,
	  htmlspecialchars($form_purpose), htmlspecialchars($form_required_sw),
	  htmlspecialchars($form_comments), time(),
	  $random_num, md5($random_num)
        );

	if (!$result) 
	  {
	    unset($group_id);
	    exit_error('ERROR','INSERT QUERY FAILED. Please notify '
		       . $GLOBALS['sys_mail_admin'].'@'.$GLOBALS['sys_mail_domain']);
	  }
	else 
	  {
	    $group_id=db_insertid($result);
	  }

} elseif (!$form_full_name && !$form_unix_name) {
  unset($group_id);
  exit_error('Error','Missing Information. PLEASE fill in all required information.');
}

# Create the page header just like if there was not yet any group_id
$group_id_not_yet_valid = $group_id;
unset($group_id);
$HTML->header(array('title'=>_("Step 3: Project Name")));
$group_id = $group_id_not_yet_valid;

# get site-specific content
utils_get_content("register/projectname");

# FIXME: begin
#if ($need_to_print_warning) {
#  print '<p><span class="error">'.projectname_test_error_message().'</span>';
#}
# FIXME: end


print '<p>'._("Please complete both fields").'.</p>';

print '<form action="license.php" method="post">';


#FIXME
#if (isset($test_on_name)) { echo '<input type="hidden" name="test_on_name" value="'.$test_on_name.'" />'; }

if (isset($re_license_other)) { echo '<input type="hidden" name="re_license_other" value="'.$re_license_other.'" />'; }

print '<input type="hidden" name="insert_group_name" value="y" />';
print '<input type="hidden" name="group_id" value="'.$group_id.'" />';
print '<input type="hidden" name="rand_hash" value="'.(isset($rand_hash) ? $rand_hash : md5($random_num)).'" />';

# If we are creating the local admin project, system unix group name cannot be
# changed and we make a proposal for the full name
if (!group_getid($GLOBALS['sys_unix_group_name']))
{
  print '<h3>'._("Full Name").' :</h3>';
  print '<input size="60" maxlength="254" type="text" name="form_full_name" value="Site Administration" />';

  print '<h3>'._("System Name").' :</h3>';
  print '<input type="hidden" name="form_unix_name" value="'.$GLOBALS['sys_unix_group_name'].'" /><strong>'.$GLOBALS['sys_unix_group_name'].'</strong>';
}
else
{
  print '<h3>'._("Full Name").' :</h3>';
  print '<input size="60" maxlength="254" type="text" name="form_full_name"'.(isset($re_full_name) ? " value=\"$re_full_name\"":"").' />';
  
  print '<h3>'._("System Name").' :</h3>';
  print '<input type="text" maxlength="16" size="15" name="form_unix_name"'.(isset($re_unix_name) ? " value=\"$re_unix_name\"":"").' /><br /><br />';
}
  
print '<div align="center">';
print '<input type=submit name="Submit" value="'._("Step 4: License").'" />';

print '</div>';
print '</form>';
print '<div align="center"><span class="error">'._("Do not click back button after this point (unless asked to).").'</span></div>';

$HTML->footer(array());
