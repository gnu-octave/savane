<?php
# Generate show/hide JavaScript code.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Pogonyshev <pogonyshev--gmx.net>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2018 Ineiev <ineiev--gnu.org>
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

require_once('../include/init.php');
require_once('../include/http.php');
require_once('../include/sane.php');
header('Content-Type: text/javascript');

extract(sane_import('request', array('deploy','legend', 'box_id', 'suffix')));

if (preg_match('/\W/', $box_id))
  $box_id = "";
if (preg_match('/\W/', $suffix))
  $suffix = "";

if ($deploy != 1)
  {
    print '
document.write(\'<span id="'.$box_id.'linkhide'.$suffix.'">'
      .'<span class="minusorplus">(-)</span>'
      .htmlspecialchars($legend, ENT_QUOTES).'</span>\');
document.write(\'<span id="'.$box_id.'linkshow'.$suffix.'">'
      .'<span class="minusorplus">(+)</span>'
      .htmlspecialchars($legend, ENT_QUOTES)."</span>');\n";
    print "document.getElementById('".$box_id.'linkhide'.$suffix
          ."').style.display = 'none';\n";
    print "document.getElementById('".$box_id.'linkshow'.$suffix
          ."').style.display = 'inline';\n";
  }
else
  {
    print '
document.write(\'<span id="'.$box_id.'linkhide'.$suffix.'">'
      .'<span class="minusorplus">(-)</span>'
      .htmlspecialchars($legend, ENT_QUOTES).'</span>\');
document.write(\'<span id="'.$box_id.'linkshow'.$suffix.'">'
      .'<span class="minusorplus">(+)</span>'
      .htmlspecialchars($legend, ENT_QUOTES)."</span>');\n";
    print "document.getElementById('".$box_id.'linkhide'.$suffix
          ."').style.display = 'inline';\n";
    print "document.getElementById('".$box_id.'linkshow'.$suffix
          ."').style.display = 'none';\n";
  }
print "document.getElementById('".$box_id.'linkhide'.$suffix."').onclick = ";
print "
    function ()
    {
      document.getElementById('".$box_id."content".$suffix."').style.display='none';
      document.getElementById('".$box_id."linkhide".$suffix."').style.display='none';
      document.getElementById('".$box_id."linkshow".$suffix."').style.display='inline';
    }\n";

print "document.getElementById('".$box_id.'linkshow'.$suffix."').onclick = ";
print "
    function ()
    {
      document.getElementById('".$box_id."content".$suffix."').style.display='inline';
      document.getElementById('".$box_id."linkhide".$suffix."').style.display='inline';
      document.getElementById('".$box_id."linkshow".$suffix."').style.display='none';
    }\n";
?>
