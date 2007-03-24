<?php
require_once('../include/init.php');
session_redirect(preg_replace("/\/admin\//", "/siteadmin/", $_SERVER["REQUEST_URI"]));
