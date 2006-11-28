<?php
require "../include/pre.php";
session_redirect(preg_replace("/\/admin\//", "/siteadmin/", $_SERVER["REQUEST_URI"]));
?>