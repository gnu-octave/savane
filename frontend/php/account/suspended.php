<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
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


require "../include/pre.php";    
$HTML->header(array(title=>_("Suspended Account")));

echo '<h3>'._("Suspended Account").'</h3>';

$mail='<a href="mailto:'.$GLOBALS['sys_admin_list'].'@'.$GLOBALS['sys_lists_domain'].'">'.$GLOBALS['sys_admin_list'].'@'.$GLOBALS['sys_lists_domain'].'</a>';

echo '<p>'._("Your account has been suspended.").'</p>';
printf ('<p>'._("If you have questions regarding your suspension, please email %s."),$mail);
echo _("Inquiries through other channels will be directed to this address.").'</p>';

$HTML->footer(array());
