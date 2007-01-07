<?php
require_once('../include/pre.php');
header('Location: '
       . preg_replace(":^$sys_url_topdir/bug:", "$sys_url_topdir/bugs", $_SERVER['REQUEST_URI']));
