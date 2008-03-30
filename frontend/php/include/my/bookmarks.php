<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright (C) 2007  Sylvain Beucler
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
