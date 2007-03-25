<?php
// Copyright 1999-2000 (c) The SourceForge Crew
// Copyright (C) 2007  Sylvain Beucler
// 
// This file is part of Savane.
// 
// Savane is free software; you can redistribute it and/or modify it
// under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// Savane is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with the Savane project; if not, write to the Free Software
// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

require_once('../include/init.php');
register_globals_off();

$HTML->header(array('title'=>_("Deleted Account"), 'context'=>'account'));

echo '<h3>'._("Deleted Account").'</h3>';

echo '<p>'._("Your account has been deleted.");

$mail='<a href="mailto:'.$GLOBALS['sys_admin_list'].'@'.$GLOBALS['sys_lists_domain'].'">'.$GLOBALS['sys_admin_list'].'@'.$GLOBALS['sys_lists_domain'].'</a>';

printf (_("If you have questions regarding your deletion, please email %s."),$mail);
echo _("Inquiries through other channels will be directed to this address.").'</p>';

$HTML->footer(array());
