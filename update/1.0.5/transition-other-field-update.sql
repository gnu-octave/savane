#  Copyright 2004 (c) Yves Perrin <yves.perrin--at--cern.ch>
#                     Mathieu Roy <yeupou--at--gnu.org>
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

CREATE TABLE trackers_field_transition_other_field_update (
  other_field_update_id int(11) NOT NULL auto_increment,
  transition_id int(11) NOT NULL default '',
  update_field_name varchar(255) NOT NULL default '',
  update_value_id int(11) NOT NULL default '0',
  PRIMARY KEY (other_field_update_id)
) TYPE=MyISAM;

# This script does not migrate previously set assign_to. Sorry.

# This command will irremediably remove previous "assign_to" assign_to field
# update as shipped in 1.0.4.
# ALTER TABLE `trackers_field_transition` DROP `assign_to`

