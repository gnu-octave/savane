<?php
# Functions to deal with the HTTP protocol
# 
# Copyright (C) 2009  Sylvain Beucler
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

function http_exit_if_not_modified($mtime)
{
  if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
    {
      $modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'];
      
      // remove trailing garbage from IE
      $pos = strpos($modified_since, ';');
      if ($pos !== false)
	$modified_since = substr($modified_since, 0, $pos);
      
      $iftime = strtotime($modified_since);
      if ($iftime != -1 && $mtime <= $iftime)
	{
	  header('HTTP/1.0 304 Not Modified');
	  exit;
	}
    }
}