<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
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

# Initial db and session library, opens session
require_once('../include/init.php');

$HTML->header(array('title' => _("New Project Registration")));

if (db_numrows(db_execute("SELECT type_id FROM group_type")) < 1) {
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
