<?php
# Savannah - Forbidden group names
#
# Copyright (C) 2005 Sylvain Beucler
# Copyright (C) 2017 Ineiev <ineiev@gnu.org>
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
#
#    You can fed $specific_forbid_group_regexp by a perl regexp
#    with group names you want to forbid on your system.
#
#    It means that it will not possible to register a project with a name
#    that match that list.
#
#    This would constitue an additional list of group name to forbid.
#    If you want the system to only take account of that list, not to
#    take account of the Savannah hardcoded list, set the variable
#    	 $only_specific_forbid_group_regexp = 1;
#


// The perl regexp:
//    The two slashes (/ /) are mandatory, see the preg_match manual.
$GLOBALS['specific_forbid_group_regexp'] = 0;

// Disregard the Savannah default list (dangerous)
$GLOBALS['only_specific_forbid_group_regexp'] = 0;

?>
