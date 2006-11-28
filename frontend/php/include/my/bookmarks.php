<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
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


function bookmark_add ($bookmark_url, $bookmark_title="") 
{
  if (!$bookmark_title) 
    { $bookmark_title = $bookmark_url; }
  
  $result = db_query("INSERT into user_bookmarks (user_id, bookmark_url, ".
		     "bookmark_title) values ('".user_getid()."', '".addslashes($bookmark_url)."', ".
		     "'".addslashes($bookmark_title)."');");
  if (!$result) 
    { print db_error(); }
}

function bookmark_edit ($bookmark_id, $bookmark_url, $bookmark_title) 
{
  db_query("UPDATE user_bookmarks SET bookmark_url='".addslashes($bookmark_url)."', "
	   ."bookmark_title='".addslashes($bookmark_title)."' where bookmark_id='".addslashes($bookmark_id)."' AND user_id='". user_getid() ."'");
}

function bookmark_delete ($bookmark_id) 
{
  db_query("DELETE from user_bookmarks WHERE bookmark_id='".addslashes($bookmark_id)."'and user_id='".user_getid()."'");
}

?>
