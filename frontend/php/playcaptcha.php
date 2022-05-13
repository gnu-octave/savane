<?php
# Captcha support.
#
# Copyright (C) 2012 Michael J. Flickinger
# Copyright (C) 2017 Ineiev
#
# This file is part of Savane.
#
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

require('include/init.php');
include_once $GLOBALS['sys_securimagedir'] . '/securimage.php';

$img = new Securimage();
$img->audio_format = 'mp3';
if (isset($_GET['format']))
  if (strtolower ($_GET['format']) == 'wav')
    $img->audio_format = 'wav';
$img->outputAudioFile();

$img->show();
