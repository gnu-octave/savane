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

/*
This is a PHP rounter file,
used only for savannah local development when running
under php's built-in web server.

It solves two issues:

1.
On savannah's production server,
EVERY file under ./frontend/php is treated as a PHP file,
regardless of '.php' extensions.
This allows URLs like:
  https://sv.gnu.org/projects/coreutils

to execute ./frontend/php/projects (which is a PHP file without extension).

The PHP built-in server doesn't like this at all.

So we detect two specific cases ('projects' and 'users')
and load the PHP files explicitly.

All other cases are passed as-is to the PHP webserver (with 'return false')
which will then load the corresponding PHP file and work as expected.


2.
Savane uses gettext for internationalization,
and it's annoying to set it up (requires fiddling
with php's configuration file).
If 'php-gettext' is not available, fake the require
functions.




See run-local-dev.sh' file to see how this file is used
(as the last parameter to 'php -S').
*/



/* Create stub internationalization functions, if needed. */
if (!function_exists("bindtextdomain")) {
	function bindtextdomain ( )
	{
		return "";
	}

	function textdomain ()
	{
		return "";
	}
	function _($a)
	{
		return $a;
	}
}



$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

# This is set in run-local-dev.sh script.
$phpdir = getenv('SAVANE_PHPROOT');
if (empty($phpdir)) {
	die("savannah-dev-error: SAVANE_PHPROOT not empty in ".__FILE__);
}
if (!is_dir($phpdir)) {
	die("savannah-dev-error: SAVANE_PHPROOT points to a non-directory '$phpdir");
}

if (substr($path,0, 10) === "/projects/") {
	include "$phpdir/projects";
	return true;
}
if (substr($path,0, 10) === "/users/") {
	include "$phpdir/users";
	return true;
}

return false;
