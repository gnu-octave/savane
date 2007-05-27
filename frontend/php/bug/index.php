<?php
#input_is_safe();
#mysql_is_safe();
require_once('../include/init.php');
header('Location: '
       . preg_replace(":^$sys_url_topdir/bug:", "$sys_url_topdir/bugs", $_SERVER['REQUEST_URI']));
