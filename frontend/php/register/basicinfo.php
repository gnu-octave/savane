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

require "../include/pre.php"; 
session_require(array('isloggedin'=>'1'));
$HTML->header(array('title'=>_("Step 2: Project Description and Dependencies")));

# get site-specific content
utils_get_content("register/basicinfo");

print '<h3>'._("Project Purpose and Summarization").' :</h3>';
print '<p><span class="error"><strong>'._("REQUIRED").':</strong> '._("Provide detailed, accurate description, with URLs").'.</span></p>';

print '<form action="projectname.php" method="post">';
print '<input type="hidden" name="insert_purpose" value="y" />';


if (isset($re_full_name))
{
   echo '<input type="hidden" name="re_full_name" value="'.$re_full_name.'" />';
}
if (isset($re_unix_name))
{
   echo '<input type="hidden" name="re_unix_name" value="'.$re_unix_name.'" />';
}
if (isset($re_license_other))
{
   echo '<input type="hidden" name="re_license_other" value="'.$re_license_other.'" />';
}

print '<p><textarea name="form_purpose" wrap="virtual" cols="70" rows="20">';

if (isset($re_purpose)) {
	print $re_purpose;
} else {
	utils_get_content("register/basicinfo_description");
/*
	print '
Replace this paragraph by the technical description of your
project (approximately 20 lines should be OK). Do not
forget to include a URL to the source code, even if it
is not a functional version (it will be easier to sort out
licensing problems now, rather than having your project
rejected later on). If you do not have any code
to show yet, then say that explicitly.
';
*/
}
print '</textarea><br /></p>';


print '<h3>'._("Software Dependencies").' :</h3>';
# get site-specific content
utils_get_content("register/basicinfo_software");
print '<p><textarea name="form_required_sw" wrap="virtual" cols="70" rows="6">';
if (isset($re_required_sw)) { 
	print $re_required_sw; 
} 
print '</textarea><br /></p>';


print '<h3>'._("Other Comments").' :</h3>';
# get site-specific content
utils_get_content("register/basicinfo_comments");

print '<p><textarea name="form_comments" wrap="virtual" cols="70" rows="4">';
if (isset($re_comments)) { 
	print $re_comments; 
}
print '</textarea><br /></p>';


print '<div align="center">';
print '<input type=submit name="Submit" value="'._("Step 3: Project Name").'" />';
print '</div>';
print '</form>';

$HTML->footer(array());
?>
