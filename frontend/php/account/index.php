<?php
require_once('../include/init.php');
require_once('../include/session.php');
register_globals_off();
session_redirect($GLOBALS['sys_home']."my/admin/");
