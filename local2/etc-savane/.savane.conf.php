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


## Savannah Local Dev:
## 'SERVER_HOST' is set by php's built-on webserver.
## If you use something else, update this setting.
$dev_server_host = $_SERVER['HTTP_HOST'];
if (empty($dev_server_host)) {
	die("savannah-dev-error: _SERVER['HTTP_HOST'] is empty.");
}
$sys_default_domain=$dev_server_host;


## Savannah Local Dev:
## Each developer will have his/her own dedicated database.
## User/pw will be given over email.
## Initially, the database configuration will not exist.
## Detect this, create a stub file and show the user how to edit it.

$db_file = dirname(__FILE__).'/savane-dev-db.php';

$first_run_message=<<<"EOD"
<pre>
Hello Savannah Developer!

It seems this is the first time you run the savane PHP code.

The code need to access a savane mock-up database.

To request access to such database, please write to
  savannah-hackers-public@gnu.org
and ask for username/password.

Once you receive them, please edit the following file
(locally on your computer):

   $db_file

Set the hostname, database, username and password,
then reload the website.

If you have any questions, contact savannah-hackers-public@gnu.org .
</pre>
EOD;

if (!file_exists($db_file)) {
	# Create the file with sub values:

	$text=<<<'EOD'
<?php
/*
   this file contains DB/USER/PASSWORD to access the GNU savannah
   development database.

   To get access to such database, please contant GNU savannah
   developers by writing to savannah-hackers-public@gnu.org .

   Once you have the DB/USER/PASSWORDS, please update
   the PHP variables below.

   This file must be a valid PHP script.

   If you have any questions, contact savannah-hackers-public@gnu.org .
*/
$sys_dbhost="";
$sys_dbname="";
$sys_dbuser="";
$sys_dbpasswd="";
?>
EOD;

	$r = file_put_contents($db_file,$text);
	if ($r === FALSE) {
		die("savannah-dev-error: failed to create PW file '$db_file'");
	}


	## Now that the stub file exists, tell the user what to do.
	print $first_run_message;
	exit(0);

}
else {
	## The DB/USER/PW file exists - read it, and ensure it contains
	## the needed variables.
	$sys_dbhost="";
	$sys_dbname="";
	$sys_dbuser="";
	$sys_dbpasswd="";

	require_once($db_file);

	## If ALL variables are empty, it's likely
	## this is still one of first times the user run the website,
	## the file was created but never updated. Show the message again.
	if (empty($sys_dbhost) && empty($sys_dbname) && empty($sys_dbuser) && empty($sys_dbpasswd)) {
		print $first_run_message;
		exit(0);
	}

	## Otherwise, perhaps just one variable is missing?
	if (empty($sys_dbhost))
		die("savannah-dev-error: '\$sys_dbhost' is not set in '$db_file'");
	if (empty($sys_dbname))
		die("savannah-dev-error: '\$sys_dbname' is not set in '$db_file'");
	if (empty($sys_dbuser))
		die("savannah-dev-error: '\$sys_dbuser' is not set in '$db_file'");
	if (empty($sys_dbpasswd))
		die("savannah-dev-error: '\$sys_dbpasswd' is not set in '$db_file'");
}

$use_pwqcheck = FALSE;



#
# Name of the configuration project.
# Must be a name of a valid (existing) project/group in the database.
# This name must match the "unix_group_name" value of the project.
#
# NOTE:
#  In GNU Savannah's production database, this project is called 'administration'.
#  In the sample database in './db/mysql/bootstrap.sql', the project is
#    called 'siteadmin'.
#  In this template file, use a variable - it will be replaced by running
#   'make' (see Makefile.am for details).
$sys_unix_group_name="siteadmin";


##
## The included text/php files, customized for each hosting company.
## On gnu savannah, the real files are on frontend0:/etc/savane/content .
##
## Here, we find take them from the git repository.
## This is an ugly hard-coded hack... to be improved...
$dev_content_dir = dirname(__FILE__).'/../../etc/site-specific-content';
if (!is_dir($dev_content_dir)) {
	print "$dev_content_dir";
	die("savannah-dev-error: failed to detect content directory in " .__FILE__);
}
$sys_incdir="$dev_content_dir";


##
## Another ugly hack, for savannah local development only:
## The $sys_www_topdir variable is determined in ./frontend/php/include/init.php.
## On savannah's production server, it is detected automatically.
##
## When running with local php's built-in server, we need to set it explicitly.
## The 'SAVANE_PHPROOT' envvar is set in 'run-local-dev.sh'.
##
$phpdir = getenv('SAVANE_PHPROOT');
if (empty($phpdir)) {
	die("savannah-dev-error: SAVANE_PHPROOT not empty in ".__FILE__);
}
if (!is_dir($phpdir)) {
	die("savannah-dev-error: SAVANE_PHPROOT points to a non-directory '$phpdir");
}
$sys_www_topdir="$phpdir";



# Name of the website - appears in many page titles.
$sys_name="GNU Savannah(Local)";

#$sys_debug_on=true;

# No redirect to brother website
$sys_debug_nobasehost=true;

#$$sys_debug_noformcheck=true;
?>
