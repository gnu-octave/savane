<?php

require('include/init.php');
include_once $GLOBALS['sys_securimagedir'] . '/securimage.php';

$img = new securimage();
$img->show();
