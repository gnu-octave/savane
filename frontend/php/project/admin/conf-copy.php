<?php
# Copy configuration (for group admins).
# 
# Copyright (C) 2004 Mathieu Roy <yeupou--at--gnu.org>
# Copyright (C) 2017 Ineiev
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


require_once('../../include/init.php');
require_directory("trackers");

extract(sane_import('post', array('update', 'from_group_id', 'artifact')));
if ($group_id && user_ismember($group_id,'A'))
{
  
  # Initialize global bug structures
  if ($update && $from_group_id != 100)
    {
      trackers_conf_copy($group_id, $artifact, $from_group_id);
    }
  
  site_project_header(array('context'=>'ahome','group'=>$group_id,
                            'title'=>_("Copy Configuration")));
  
  print '<h3>'._("Support Trackers Configuration Copy").'</h3>
';
  conf_form($group_id, "support");
  print '<h3>'._("Bugs Trackers Configuration Copy").'</h3>
';
  conf_form($group_id, "bugs");
  print '<h3>'._("Task Trackers Configuration Copy").'</h3>
';
  conf_form($group_id, "task");
  print '<h3>'._("Patch Trackers Configuration Copy").'</h3>
';
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
