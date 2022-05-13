<?php
# Captcha support.
#
# Copyright (C) 2011, 2012 Michael J. Flickinger
# Copyright (C) 2017, 2022 Ineiev
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

# We don't include init.php because we test this feature in testconfig.php,
# so we should only rely on ac_config.php.
include_once ('include/ac_config.php');
if (!empty ($sys_conf_file) && is_readable ($sys_conf_file))
  include_once $sys_conf_file;

if (empty ($sys_securimagedir))
  $sys_securimagedir = '/usr/src/securimage';

include_once "$sys_securimagedir/securimage.php";

function run_image ($img)
{
  if (isset ($_GET['play']) && $_GET['play'])
    {
      $img->audio_format = 'mp3';
      if (isset ($_GET['format']))
        if (strtolower ($_GET['format']) == 'wav')
          $img->audio_format = 'wav';
      $img->outputAudioFile ();
    }
  $img->show ();
}

$img = new securimage ();

if (isset ($antispam_is_valid))
  {
    if ($img->check ($_POST['captcha_code']) == false)
      fb (_("Please correctly answer the antispam captcha!"), 1);
    else
      $antispam_is_valid = true;
  }
else
  run_image ($img);
?>
