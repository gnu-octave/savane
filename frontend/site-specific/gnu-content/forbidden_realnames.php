<?php
# Savannah - Forbidden real names
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

# Preg_match regexp for forbidden real names.
$GLOBALS['forbid_realname_regexp'] = 
# Forbid names with strings looking like links.
  ','
  . '([<>])'
  . '|(href\s*=)'
  . '|(\s*((http)|(ftp)|(mailto))://)'
  . '|(www\.)'
  . '|(\.((com)))'
  . ',i';
?>
