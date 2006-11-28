<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2005-2006 (c) Sylvain Beucler <beuc--beuc.net>
#                          Mathieu Roy <yeupou--gnu.org>
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


require "../include/pre.php";  # Initial db and session library, opens session
#session_require(array(isloggedin=>1));
$HTML->header(array(title=>_("Step 1: Services and Requirements")));


# get site-specific content
utils_get_content("register/requirements");

print '<form action="basicinfo.php" method="post">';
print '<div align="center">';
print '<input type=submit name="Submit" value="'._("Step 2: Project Description and Dependencies (Requires Login)").'" />';
print '</div>';
print '</form>';

$HTML->footer(array());
?>

