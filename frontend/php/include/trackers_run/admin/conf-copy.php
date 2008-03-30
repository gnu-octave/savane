<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2004-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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


require_directory("project");
require_once('../../include/trackers/conf.php');

extract(sane_import('post', array('update', 'from_group_id')));

if ($group_id && user_ismember($group_id,'A'))
{
  
  # Initialize global bug structures
  trackers_init($group_id);

  if ($update && $from_group_id != 100)
    {
      trackers_conf_copy(addslashes($group_id), ARTIFACT, addslashes($from_group_id));
    }
  


  trackers_header_admin(array ('title'=>_("Copy Configuration")));

  conf_form($group_id, ARTIFACT);
  
  trackers_footer(array());

}
else
{
  if (!$group_id)
    { exit_no_group(); }
  else
    { exit_permission_denied(); }
}
