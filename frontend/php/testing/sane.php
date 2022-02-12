<?php
# Test sanitizing functions.
#
# Copyright (C) 2022 Ineiev
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
#
# Invocation:
#
#   php testing/sane.php
#
# In case of fail, diagnositc text is output to stdout.
#
# Commands to list files to update to the new version of sane_import:
#
# f=`sed -e '1s,.*,(,;:egin;' \
#      -e 's,\(^\|\n\)[$]reference = .\([^\n ]*\)\.php.;$,\2),;' \
#      -e 't next;s,\n.*,,;:next;s,)\(.\),\|\1,;N;begin;' \
#      testing/sane.php`
# grep -rlI '\<sane_import\>' | egrep -v "^$f"'\.php'

require_once('include/sane.php');

# Exclude these files from grepping:
$reference = 'testing/sane.php';
$reference = 'include/sane.php';

$reference = null;

function htmlspec ($x)
{
  return htmlspecialchars ($x, ENT_QUOTES);
}

function print_reference ()
{
  global $reference;
  if ($reference !== null)
    print "reference: $reference\n";
}

# Basic test routine.
function test_sane_import ($in, $names, $out)
{
  global $sane_test_input;

  $sane_test_input = $in;
  $res = sane_import('test', $names);
  if ($res == $out)
    return;
  print_reference ();
  print "in:\n";
  print_r ($in);
  print "names:\n";
  print_r ($names);
  print "expected:\n";
  print_r ($out);
  print "result:\n";
  print_r ($res);
}

# Preliminary tests.
{
  $names = [
    'name' => 'user',
    'digits' => ['group_id', 'user_id'],
    'true' =>  ['cancel', 'post'],
    'preg' => [['cc_list', '/^[-_,\s[:alnum:]]*$/']],
    'array' => [['arr', [['digits', [0, 289]], 'name']]],
  ];
  $in = [
    'group_id' => '1234',
    'user_id' => '54321',
    'user' => 'agn',
    'cancel' => 'cancel',
    'cc_list' => 10,
    'arr' => ['user', 'group', 'task', 3 => '00x', '4913' => 'name'],
  ];

  $out = $in;
  $out['cancel'] = true;
  $out['post'] = null;
  unset ($out['arr'][3]);
  unset ($out['arr']['4913']);

  test_sane_import ($in, $names, $out);

  $names = ['user', 'user_id'];
  unset ($out['cancel']);
  unset ($out['post']);
  unset ($out['group_id']);
  unset ($out['cc_list']);
  unset ($out['arr']);

  test_sane_import ($in, $names, $out);
  $tmp = strlen (123);
  if ($tmp != 3)
    print "strlen (123) != 3 ($tmp)\n";
}

?>
