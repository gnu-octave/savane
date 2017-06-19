<?php
# Savannah - Forbidden themes
#
# Copyright (C) 2003 Mathieu Roy <yeupou--gnu.org>
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
#    You can fed $forbid_theme_regexp by a perl regexp
#    with theme names you want to forbid on your system.
#
#    The theme name given in this regexp must not have the extension
#    .css.
#
#    This forbid_theme_regexp site specific variable be useful if you
#    do not want to provide to users every  themes available for Savannah,
#    for instance because some of them creates troubles with a browser
#    widely used in your company/organisation.
#
#    By default, Savannah forbid only themes which have been made for
#    a specific installation and are very very close to a more generic
#    theme.


// The perl regexp:
//    The two slashes (/ /) are mandatory, see the preg_match manual.

$GLOBALS['forbid_theme_regexp'] = "/^(cern|savanedu)$/";

?>
