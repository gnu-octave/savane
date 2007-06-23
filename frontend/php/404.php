<?php
# 'Not found' error page with a Savane look&feel
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

require_once('include/init.php');

site_header(array('title'=>_("Requested Page not Found (Error 404)")));


print '<p class="warn">'.sprintf(_("The web page you are trying to access doesn't exist on %s."), $GLOBALS['sys_name']).'</p>';
print '<p>'.sprintf(_("If you think that there's a broken link on %s that must be repaired, %sfile a support request%s, mentioning the URL you tried to access (%s)."),
  $GLOBALS['sys_name'],
  '<a href="'.$GLOBALS['sys_home'].'support/?group='.$GLOBALS['sys_unix_group_name'].'">',
  '</a>',
  $_SERVER['REQUEST_URI']).'</p>';

print '<p>'.sprintf(_("Otherwise, you can return to the %s%s main page%s."),
		    '<a href="'.$GLOBALS['sys_home'].'">',
		    $GLOBALS['sys_name'],
		    '</a>').'</p>';


$HTML->footer(array());
