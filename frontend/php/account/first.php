<?php
# First valid login, after account confirmation
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
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

site_user_header(array('title'=>sprintf(_("Welcome to %s"),$GLOBALS['sys_name']),'context'=>'account'));

print '<p>'.sprintf(_("You are now a registered user on %s."),$GLOBALS['sys_name']).'</p>';

print '<p>'._("As a registered user, you can participate fully in the activities on the site.");
print ' '.sprintf(_("You may now post items to issue trackers in %s, sign on as a project member, or even start your own project."),$GLOBALS['sys_name']).'</p>';

print '<p>'.sprintf(_("You should take some time to read %sthe Savane User Guide%s so that you may take full advantage of %s."),'<a href="'.$GLOBALS['sys_home'].'userguide/">','</a> ',$GLOBALS['sys_name']).'</p>';

print '<p>'._("Enjoy the site").'</p>';


site_user_footer(array());
