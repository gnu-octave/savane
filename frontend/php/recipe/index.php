<?php
require_once('../include/init.php');
header('Location: '
       . preg_replace(":^$sys_url_topdir/recipe:", "$sys_url_topdir/cookbook", $_SERVER['REQUEST_URI']));
