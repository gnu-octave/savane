<?php
# Copyright (C) 2017 Assaf Gordon (assafgordon@gmail.com)
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

# A simple test script, to ensure the PHP configuration works.
#
# It does one thing: gets a CGI GET parameter name 'foo',
# and prints its value.
#
# That'll make it easy to autmate tests:
#
#    a=$(curl http://savannah.gnu.org/testing/index.php?foo=bar)
#    test "$a" = "bar" && echo ok || echo fail

if (!empty ($_GET['foo']))
  $foo = htmlspecialchars ($_GET['foo']);

if (empty ($foo))
  {
    http_response_code (400);
    print "error: cgi parameter 'foo' not set (or empty)\n";
    exit (0);
  }

header ('Content-Type:text/plain');
print $foo . "\n";
?>
