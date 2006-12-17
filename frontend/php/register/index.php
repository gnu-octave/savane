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


require_once('../include/pre.php');  # Initial db and session library, opens session

$HTML->header(array('title' => _("New Project Registration")));

if (db_numrows(db_query("SELECT type_id FROM group_type")) < 1) {
	# group_type is empty; it's not prossible to register projects
	print _("No group type has been set. Admins need to create at least one group type. They can make it so clicking on the link \"Group Type Admin\", on the Administration section of the left side menu, while logged in as admin");
} else {
	# get site-specific content
	utils_get_content("register/index");
}

print '<form action="requirements.php" method="post">';
print '<div align="center">';
print '<input type="submit" name="Submit" value="'._("Step 1: Services and Requirements").'" />';
print '</div>';
print '</form>';

$HTML->footer(array());
