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


# avoid pre.php looking for group_type info
$no_redirection=1;

require_once('../include/pre.php');
require_once('../include/vars.php');
require_once('../include/account.php');
session_require(array('isloggedin' => '1'));

extract(sane_import('post',
  array('insert_group_name', 'rand_hash', 'form_full_name', 'form_unix_name')));

if ($insert_group_name && $group_id && $rand_hash && $form_full_name && $form_unix_name)
{
   # check the validity of the project's name, only if we are not creating
   # the local admin group
   if (group_getid($GLOBALS['sys_unix_group_name']))
     { 
   if (!account_groupnamevalid($form_unix_name))
   {
      unset($group_id);
      exit_error(_("Invalid Group Name"));
   }
} 
   # make sure the name is not already taken, ignoring incomplete
   # registrations: risks of a name clash seems near 0, while not doing that
   # require maintainance, since some people interrupt registration and
   # try to redoit later with another name. 
   # And even if a name clash happens, admins will notice it during approval
   if (db_numrows(db_query_escape("SELECT group_id FROM groups WHERE unix_group_name LIKE '%s' AND status <> 'I'",
         $form_unix_name)) > 0)
   {
      unset($group_id);
      exit_error("Project Name Taken","A project with that name already exists. Use the back button.");
   }
   # hash to prevent modification of a existing project
   $result=db_query_escape(
     "UPDATE groups SET unix_group_name='%s', group_name='%s'
      WHERE group_id='%s' AND rand_hash='__%s'",
     strtolower($form_unix_name), $form_full_name, $group_id, $rand_hash);
} 
else 
{
   unset($group_id);
   exit_error(_("Some required fields were left empty. Use the back button."));
}

# Create the page header just like if there was not yet any group_id
$group_id_not_yet_valid = $group_id;
unset($group_id);
$HTML->header(array('title'=>_("Step 4: License")));
$group_id = $group_id_not_yet_valid;

# site-specific content
utils_get_content("register/license");

print '<h3>'._("Licenses compatible with our policies").' :</h3>';
print '<p><ul>';

while (list($l,$w) = each($LICENSE)) {
	print '<li>';
	if ($LICENSE_URL[$l] != "0") {
		print '<a href="'.$LICENSE_URL[$l].'" target="_blank">'.$w.'</a>';
	} else {
		print $w;
	}
	print "</li>\n";
}
print '</ul></p>';


print '<h3>'._("License for This Project").' :</h3>';

print '
<form action="projecttype.php" method="post">
<input type="hidden" name="insert_license" value="y" />
<input type="hidden" name="group_id" value="'.$group_id.'" />
<input type="hidden" name="rand_hash" value="'.$rand_hash.'" />';

print '<p>'._("Choose your project's license").'.</p>';

print '<select name="form_license">';
reset($LICENSE);
while (list($k,$v) = each($LICENSE)) {
	print "<option value=\"$k\">$v</option>\n";
}
print '</select>';
print '<p>'._("If you selected \"other\", please provide an explanation along with a description of your license").'. ';
print _("Remember that other licenses are subject to approval").'.</p>';
print '<p><textarea name="form_license_other" wrap="virtual" cols="60" rows="10">';
if (isset($re_license_other)) { echo $re_license_other; } 
print '</textarea><br /></p>';


print '<div align="center">';

if (db_numrows(db_query("SELECT type_id FROM group_type")) != 1) {
	print '<input type=submit name="Submit" value="'._("Step 5: Project Type").'" />';
} else {
	# if only one project_type available, skip step 5 
	print '<input type=submit name="Submit" value="'._("Next Step: Confirmation").'" />';
}
print '</div>';
print '</form>';

print '<div align="center"><span class="error">'._("Do not click back button (unless asked to).").'</span></div>';

$HTML->footer(array()); 

?>

