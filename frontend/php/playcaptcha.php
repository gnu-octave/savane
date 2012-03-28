<?php

require('include/init.php');
include_once $GLOBALS['sys_securimagedir'] . '/securimage.php';

$img = new Securimage();
$img->audio_format = (isset($_GET['format']) && in_array(strtolower($_GET['format']), array('mp3', 'wav')) ? strtolower($_GET['format']) : 'mp3');
$img->setAudioPath($GLOBALS['sys_securimagedir'] .  '/audio/');

$img->outputAudioFile();

$img->show();
