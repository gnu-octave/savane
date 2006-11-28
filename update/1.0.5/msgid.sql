#  Copyright 2004 (c)  Mathieu Roy <yeupou--at--gnu.org>
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

# Table to keep a list of references to be included in mail headers.
# Abusing a field of trackers_history for details does not seem so 
# reasonable and we are unlikely to require the ability to link a comment
# with it's exact msg. If at some point it is required (threaded comments)
# we will probably change a bit this table structure.

CREATE TABLE trackers_msgid (
  id int(11) NOT NULL auto_increment,
  msg_id varchar(255) NOT NULL default '',
  artifact varchar(16) NOT NULL default '',
  item_id int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

