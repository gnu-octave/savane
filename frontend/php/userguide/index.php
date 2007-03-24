<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: admin.php 4977 2005-11-15 17:38:40Z yeupou $
#
#  Copyright 2005 (c) Mathieu Roy <yeupou--gnu.org>
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
register_globals_off();
#input_is_safe();
#mysql_is_safe();

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
