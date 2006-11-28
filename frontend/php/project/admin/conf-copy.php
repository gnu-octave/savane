<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2004-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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

require "../../include/pre.php";
require_directory("trackers");

if ($group_id && user_ismember($group_id,'A'))
{
  
  # Initialize global bug structures
  if ($update && $from_group_id != 100)
    {
      trackers_conf_copy(addslashes($group_id), $artifact, addslashes($from_group_id));
    }
  
  site_project_header(array('context'=>'ahome','group'=>$group_id,'title'=>_("Copy Configuration")));

  
  print '<h3>'.("Support Trackers Configuration Copy").'</h3>';
  conf_form($group_id, "support");
  print '<h3>'.("Bugs Trackers Configuration Copy").'</h3>';
  conf_form($group_id, "bugs");
  print '<h3>'.("Task Trackers Configuration Copy").'</h3>';
  conf_form($group_id, "task");
  print '<h3>'.("Patch Trackers Configuration Copy").'</h3>';
  conf_form($group_id, "patch");
  
  site_project_footer(array());

}
else
{
  if (!$group_id)
    { exit_no_group(); }
  else
    { exit_permission_denied(); }
}


?>
