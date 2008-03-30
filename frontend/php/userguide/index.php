<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 2005 (c) Mathieu Roy <yeupou--gnu.org>
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

require_once('../include/init.php'); 
register_globals_off();

site_header(array());

# Guess the correct file name
# Print the index if no specific file was asked.
# Make sure we dont have anything stupid/malicious in the file name.
# To be sure, we simply remove slashes. Without slashes, we are quite sure
# to stay in the userguide directory
$file = sane_get("file");
$file = preg_replace("@/@", "", $file); 
if (!$file)
{ $file = "index.html"; }

# We get the html content and include directly.
# Normally, we have xhtml valid content, stripped of everything outside of the
# body.
print utils_read_file($GLOBALS['sys_www_topdir'].'/userguide/'.$file);

$HTML->footer(array());
