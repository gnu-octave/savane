<?php
# Test markup functions.
#
# Copyright (C) 2022 Ineiev
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
# Invocation:
#
#   php testing/markup.php
#
# In case of fail, diagnositc text is output to stdout.
require_once ('include/utils.php');
require_once ('include/markup.php');
$in = '0 item 1
00 item 1.1
00 item 1.2
000 item 1.2.1
000 item 1.2.2
000 item 1.2.3
 0 item 2
00 item 2.1
0 item 3

0 item 1
0 item 2
0 item 3';
$out = '1 item 1
	1 item 1.1
	2 item 1.2
		1 item 1.2.1
		2 item 1.2.2
		3 item 1.2.3
2 item 2
	1 item 2.1
3 item 3

1 item 1
2 item 2
3 item 3
';

$res = markup_ascii ($in);

if ($out !== $res)
  print "markup_ascii doesn't match\nexpected:\n$out\nresult:\n$res\n";
?>
