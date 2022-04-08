<?php
/*
Copyright (C) 2017 Assaf Gordon (assafgordon@gmail.com)
This file is part of Savane.

Savane is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

Savane is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/* A simple test script, to ensure the PHP configuration works.

It does one thing: extracts the PATH_INFO variable and print it.

That'll make it easy to autmate tests:

   a=$(curl http://savannah.gnu.org/test/foobar
   test "$a" = "foobar" && echo ok || echo fail

This should be used as a test for savannah's few scripts
that require a working PATH_INFO configuration (projects/users/file).
*/

if (isset ($_SERVER['PATH_INFO']))
  $foo = $_SERVER['PATH_INFO'];

if (!isset($foo) || empty($foo)) {
    http_response_code(400);
    print "error: PATH_INFO parameter not set (or empty)\n";
    exit(0);
}

header('Content-Type:text/plain');
print $foo;
print "\n";
?>
