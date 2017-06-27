<?php
# Same as 404 error but for export pages
# 
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017  Ineiev
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

require_once('include/init.php');

site_header(array('title'=>_("Requested XML not Found (Error 404)")));


print '<p class="warn">'
       .sprintf(_("The XML file you are trying to access doesn't exist on %s."),
                $GLOBALS['sys_name'])
       .'</p>';
print '<p>'.
sprintf(_("It is surely because it was not yet generated, or too old. If you
think that there's a broken link on %s that must be repaired, <a href=\"%s\">
file a support request</a>, mentioning the URL you tried to access (%s)."),
  $GLOBALS['sys_name'],
  $GLOBALS['sys_home'].'support/?group='.$GLOBALS['sys_unix_group_name'],
  $_SERVER['REQUEST_URI']).'</p>';

print '<p>'
  .sprintf(_("Otherwise, you can return to the <a href=\"%s\">%s main page</a>."),
           $GLOBALS['sys_home'], $GLOBALS['sys_name']).'</p>';


$HTML->footer(array());
