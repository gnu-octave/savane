<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: index.php 5187 2005-12-01 16:22:29Z yeupou $
#
# Copyright 1999-2000 (c) The SourceForge Crew
#
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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
site_admin_header(array('title'=>_("Home"),'context'=>'admhome'));

# Include the /testconfig.php that can be run on 127.0.0.1
# when Savane is not yet running itself.
$inside_siteadmin = true;
include('../testconfig.php');

site_admin_footer(array());
