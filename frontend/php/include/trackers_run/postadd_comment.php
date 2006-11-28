<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2004 (c) Mathieu Roy <yeupou--at--gnu.org>
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


$changes = array();
$changed = false;

# Add a new comment if there is one
if ($details != '') 
{
  
    # For none project members force the comment type to None (100)
  trackers_data_add_history ('details',htmlspecialchars($details),'',$item_id,100);  
  $changes['details']['add'] = stripslashes($details);
  $changes['details']['type'] = 'None';
  $changed = true;
  
  ' Comment added to item ';
  
}

# Add a new cc if any
if ($add_cc) {
    $changed |= trackers_add_cc($item_id,$group_id,$add_cc,$cc_comment,$changes);
}

# Attach new file if there is one
if ($add_file && $input_file) 
{
  $changed |= trackers_attach_file($item_id,$group_id,$input_file,
				   $input_file_name,$input_file_type,
				   $input_file_size,$file_description,
				   $changes);
}

if (!$changed) 
{
  fb(_('Nothing Done'));
}

?>
