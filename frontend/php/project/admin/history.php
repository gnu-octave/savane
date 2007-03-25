<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2000-2003 (c) Free Software Foundation
#
#  Copyright 2006      (c) Mathieu Roy <yeupou--gnu.org>
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


require_once('../../include/init.php');
require_once('../../include/project/admin.php');
register_globals_off();

$group_id = sane_all("group_id");
if (member_check(0, $group_id))
{
  site_project_header(array('title'=>_("Project History"),'group'=>$group_id,'context'=>'ahome'));
  
  show_grouphistory($group_id);

  site_project_footer(array());
}
else
{
  exit_permission_denied();
}
