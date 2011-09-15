<?php

require('include/init.php');
include_once $GLOBALS['sys_secureimagedir'] . '/securimage.php';

$img = new securimage();
$img->show();
