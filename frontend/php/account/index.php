<?php
require_once('../include/init.php');
require_once('../include/session.php');
register_globals_off();
#input_is_safe();
#mysql_is_safe();
session_redirect($GLOBALS['sys_home']."my/admin/");
