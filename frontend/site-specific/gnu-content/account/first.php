<?php
# Note for first valid login.
#
# Copyright (C) 2019 Ineiev <ineiev@gnu.org>
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

print '<p>' . sprintf (
# TRANSLATORS: the second argument is system name (like Savannah).
_('You should take some time to read the <a href="%1$s">Savane User
Guide</a> so that you may take full advantage of %2$s.'),
                       $GLOBALS['sys_home'] . 'userguide/',
                       $GLOBALS['sys_name']) . "</p>\n";
print '<p>' . sprintf (_('Note that <a href="%s">unused accounts</a>
may be removed without notice.'),
                       $GLOBALS['sys_home'] . 'maintenance/IdleAccounts/')
     . "</p>\n";
?>
