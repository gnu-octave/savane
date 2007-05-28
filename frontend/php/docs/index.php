<?php
#input_is_safe();
#mysql_is_safe();
require_once('../include/init.php');
header("Location: ".$GLOBALS['sys_url_topdir']."cookbook/?group=".$GLOBALS['sys_unix_group_name']);
