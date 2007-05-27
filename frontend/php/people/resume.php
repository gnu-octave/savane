<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: resume.php 5435 2006-02-19 11:54:36Z yeupou $
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require_once('../include/init.php');
require_once('../include/people/general.php');
register_globals_off();

#input_is_safe();
#mysql_is_safe();

extract(sane_import('get', array('user_id')));

if ($user_id == null)
{ exit_missing_param(); }

$result=db_execute("SELECT * FROM user WHERE user_id=?", array($user_id));
  
if (!$result || (db_numrows($result) < 1))
{
  exit_error(_("User not found"));
  
}
else if (db_result($result,0,'people_view_skills') != 1)
{
  exit_error(_("This user deactivated his/her Resume & Skills page"));
}


site_header(array('title'=>sprintf(_("%s Resume & Skills"),db_result($result, 0, 'realname')),
		  'context'=>'people'));


print '<p>'.sprintf(_("Follows Resume & Skills of %s."), utils_user_link(db_result($result, 0, 'user_name'),db_result($result, 0, 'realname'))).'</p>';
# we get site-specific content
utils_get_content("people/viewprofile");

print '<h3>'._("Resume").'</h3>';
print markup_full(htmlspecialchars(db_result($result,0,'people_resume')));

print '<h3>'._("Skills").'</h3>';
print people_show_skill_inventory($user_id);


site_footer(array());



?>
