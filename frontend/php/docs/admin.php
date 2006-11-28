<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2002-2004 (c) Mathieu Roy <yeupou@gnu.org>
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

require "../include/pre.php";    

if (!user_can_be_super_user())
{
  exit_permission_denied();
}
else
{

  $project=project_get_object($sys_group_id);
  $title = $project->getPublicName().': '._("Admin documentation");
  
  site_header(array('title'=>$title,'group'=>$sys_group_id,'context'=>'special'));


# we get *.html content and we show only <body>content</body>

# Currently not advertised: with the local option in the link, it will print
# the content of a file in set by sys_localdoc_file, if exiting.
# If this file exists, it will show up in the menu links too.
  if ($local && $GLOBALS['sys_localdoc_file']) 
    {
      print utils_remove_htmlheader(utils_read_file($GLOBALS['sys_localdoc_file']));
    }
  
  $HTML->footer(array());
}
?>
