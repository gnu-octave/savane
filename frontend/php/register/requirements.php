<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2005-2006 (c) Sylvain Beucler <beuc--beuc.net>
#                          Mathieu Roy <yeupou--gnu.org>
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


require_once('../include/init.php');  # Initial db and session library, opens session
#session_require(array(isloggedin=>1));
$HTML->header(array('title' => _("Step 1: Services and Requirements")));


# get site-specific content
utils_get_content("register/requirements");

print '<form action="basicinfo.php" method="post">';
print '<div align="center">';
print '<input type=submit name="Submit" value="'._("Step 2: Project Description and Dependencies (Requires Login)").'" />';
print '</div>';
print '</form>';

$HTML->footer(array());
