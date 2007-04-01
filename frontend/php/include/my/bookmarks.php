<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
# Copyright (C) 2007  Sylvain Beucler
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

#input_is_safe();
#mysql_is_safe();

function bookmark_add ($bookmark_url, $bookmark_title='')
{
  if (!$bookmark_title) 
    { $bookmark_title = $bookmark_url; }
  
  $result = db_autoexecute('user_bookmarks',
    array('user_id' => user_getid(),
	  'bookmark_url' => $bookmark_url,
	  'bookmark_title' => $bookmark_title),
    DB_AUTOQUERY_INSERT);
  if (!$result) 
    { print db_error(); }
}

function bookmark_edit ($bookmark_id, $bookmark_url, $bookmark_title)
{
  db_autoexecute('user_bookmarks',
    array('bookmark_url' => $bookmark_url,
	  'bookmark_title' => $bookmark_title),
    DB_AUTOQUERY_UPDATE,
    "bookmark_id=? AND user_id=?",
    array($bookmark_id, user_getid()));
}

function bookmark_delete ($bookmark_id)
{
  db_execute("DELETE from user_bookmarks WHERE bookmark_id=? AND user_id=?",
	     array($bookmark_id, user_getid()));
}
