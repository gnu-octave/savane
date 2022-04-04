<?php
# Generate show/hide JavaScript code.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Pogonyshev <pogonyshev--gmx.net>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2018, 2022 Ineiev <ineiev--gnu.org>
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

require_once('../include/sane.php');
header('Content-Type: text/javascript');

extract(sane_import('request',
  [
    'digits' => 'deploy',
    'preg' => [['box_id', 'suffix', '/^\w*$/']],
    'specialchars' => 'legend'
  ]
));

if ($box_id === null)
  $box_id = "";
if ($suffix === null)
  $suffix = "";

foreach (
  ['hide' => 'linkhide' , 'show' => 'linkshow', 'cont' => 'content']
  as $k => $v
)
  {
    $$k = "${box_id}$v$suffix";
    ${"${k}_el"} = "document.getElementById('{$$k}')";
  }

$sign_func = function ($sign, $id, $legend)
{
  return "<span id=\"$id\"><span class=\"minusorplus\">($sign)</span>"
    . htmlspecialchars ($legend, ENT_QUOTES) . "</span>";
};
print "document.write('"
  .  $sign_func ('-', $hide, $legend) .  $sign_func ('+', $show, $legend)
  . "');\n";

$inline_el = $show_el;
$none_el = $hide_el;
if ($deploy)
  {
    $inline_el = $hide_el;
    $none_el = $show_el;
  }
print "$inline_el.style.display = 'inline';\n";
print "$none_el.style.display = 'none';\n";

$on_click_func = function ($this_el, $that_el, $cont_el, $disp)
{
  print "$this_el.onclick = function ()\n"
    . "{\n"
    . "  $cont_el.style.display='$disp';\n"
    . "  $this_el.style.display='none';\n"
    . "  $that_el.style.display='inline';\n"
    . "}\n";
};
$on_click_func ($hide_el, $show_el, $cont_el, 'none');
$on_click_func ($show_el, $hide_el, $cont_el, 'inline');
unset ($on_click_func, $sign_func);
?>
