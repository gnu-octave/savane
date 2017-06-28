<?php
# /project/index.php
# 
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2000-2003 Free Software Foundation
# Copyright (C) 2000-2003 Mathieu Roy <yeupou--at--gnu.org>
# Copyright (C) 2006-2008 Sylvain Beucler
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

header ("Location: ".$GLOBALS['sys_home']."projects/".$group);
?>
