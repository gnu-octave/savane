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

$reference = 'account/impersonate.php';
{
  $names = [
    'name' => 'user_name',
    'internal_uri' => 'uri',
    'hash' => 'session_hash'
  ];
  $in =  [
    'user_name' => 'agn',
    'uri' => '/account/login.php',
    'session_hash' => '/account/login.php'
  ];
  $out = $in;
  $out['session_hash'] = null;

  test_sane_import ($in, $names, $out);

  $in['session_hash'] = '9ad59d2d0703e7f015d54e725ce099fc1fd1433c';
  $out['session_hash'] = $in['session_hash'];

  test_sane_import ($in, $names, $out);
}

$reference = 'account/login.php';
{
  $names = [
    'true' => [
      'stay_in_ssl', 'brotherhood', 'cookie_for_a_year', 'login',
      'cookie_test'
    ],
    'name' => 'form_loginname',
    'pass' => 'form_pw',
    'internal_uri' => 'uri'
  ];
  $in = [
    'stay_in_ssl' => true,
    'brotherhood' => true,
    'login' => 'Login',
    'form_loginname' => 'agn',
    'form_pw' => '12345',
    'uri' => '/account/login.php'
  ];
  $out = $in;
  $out['login'] = true;
  $out['cookie_for_a_year'] = null;
  $out['cookie_test'] = null;

  test_sane_import ($in, $names, $out);
}

$reference = 'account/lostlogin.php';
{
  $names = [
    'hash' => 'form_id',
    'true' => 'update',
    'pass' => ['form_pw', 'form_pw2']
  ];
  $in = [
    'form_id' => md5 (83521),
    'form_pw' => '123;"45',
    'form_pw2' => '123 45',
  ];
  $out = $in;
  $out['update'] = null;
  test_sane_import ($in, $names, $out);

  $in['form_id'] = $in['form_id'] . 'A';
  $out['form_id'] = null;
  $in['update'] = 'x';
  $out['update'] = true;
  test_sane_import ($in, $names, $out);
}

$reference = 'account/lostpw-confirm.php';
{
  $names = ['name' => 'form_loginname'];
  $in = ['form_loginname' => 'agn', 'user_id' => 83521];
  $out = ['form_loginname' => 'agn'];
  test_sane_import ($in, $names, $out);
}

$reference = 'account/pending-resend.php';
{
  $names = ['name' => 'form_user'];
  $in = ['form_user' => 'agn', 'user_id' => 83521];
  $out = ['form_user' => 'agn'];
  test_sane_import ($in, $names, $out);
  $in['form_user'] = 'a;gn';
  $out['form_user'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'account/register.php';
{
  $names = [
    'hash' => 'form_id',
    'name' => 'form_loginname',
    'pass' => ['form_pw', 'form_pw2', 'form_realname', 'form_email'],
    'digits' => 'form_year',
    'true' => ['update', 'form_usepam']
  ];
  $in = [
    'form_id' => md5 (289),
    'form_loginname' => 'agn',
    'update' => 'Update',
    'form_pw' => '%',
    'form_pw2' => '^',
    'form_year' => '1983',
    'form_realname' => 'A. B. C.',
    'form_email' => 'agn@test.org'
  ];
  $out = $in;
  $out['update'] = true;
  $out['form_usepam'] = null;
  test_sane_import ($in, $names, $out);

  if (empty ($out['update']))
    print "empty (true)\n";
  $in['form_usepam'] = 1;
  $out['form_usepam'] = true;
  test_sane_import ($in, $names, $out);
}

$reference = 'account/su.php';
{
  $names = [
    'true' => 'from_brother',
    'internal_uri' => 'uri',
    'strings' => [['action', ['login', 'logout']]]
  ];
  $in = [
    'uri' => 'https://www.gnu.org',
    'action' => 'login'
  ];
  $out = $in;
  $out['uri'] = '/';
  $out['from_brother'] = null;
  test_sane_import ($in, $names, $out);

  $in['action'] = 'add';
  $out['action'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'account/verify.php';
{
  $names = [
    'true' => 'update',
    'hash' => ['form_id', 'confirm_hash'],
    'name' => 'form_loginname',
    'pass' => 'form_pw'
  ];
  $in = [
    'form_id' => md5 (4913),
    'confirm_hash' => md5 ('agn'),
    'form_loginname' => 'agn',
    'form_pw' => 0
  ];
  $out = $in;
  $out['update'] = null;
  test_sane_import ($in, $names, $out);

  $in['update'] = 1;
  $out['update'] = true;
  test_sane_import ($in, $names, $out);
}

$reference = 'cookbook/index.php';
{
  $names = [
    'strings' => [['func', ['default' => 'default', 'search', 'detailitem']]],
    'digits' => 'item_id'
  ];
  $in = [
    'func' => 'search',
    'item_id' => 'a234b'
  ];
  $out = $in;
  $out['item_id'] = 234;
  test_sane_import ($in, $names, $out);
  $in['func'] = 'browse';
  $out['func'] = 'default';
  test_sane_import ($in, $names, $out);
}

$reference = 'css/graph-widths.php';
{
  $names = ['preg' => [['widths', '/^[.,\d]+$/']]];
  $in['widths'] = '1,2,3,a';
  $out = ['widths' => null];
  test_sane_import ($in, $names, $out);
  $w = explode (',', $out['widths']);
  $count = count ($w);
  if ($count > 1)
    {
      print_reference ();
      print "count $count > 1\n";
      print '$' . "w\n";
      print_r ($w);
    }
  $in['widths'] = '1,2,3,';
  $out['widths'] = $in['widths'];
  test_sane_import ($in, $names, $out);
}

$reference = 'cvs/admin/index.php';
{
  $key_func = ['preg', '/^(([\d]+)|(new))$/'];
  $names = [
    'true' => 'log_accum',
    'array' => [
      [
        'arr_branches',
        [$key_func, ['preg', '/^[-~!@#$%^&*()+=:.,_\da-zA-Z]+$/']]
      ],
      ['arr_id', [$key_func, 'true']],
      ['arr_remove', [['preg', '/^[\d]+$/'], 'true']],
      ['arr_repo_name', [$key_func, ['strings', ['sources', 'web']]]],
      [
        'arr_match_type',
        [$key_func, ['strings', ['ALL', 'dir_list', 'DEFAULT']]]
      ],
      [
        'arr_dir_list',
        [
          $key_func,
          ['preg', '/^(([a-zA-Z0-9_.+\/-]+,)*([a-zA-Z0-9_.+\/-]+))$/']
        ]
      ],
      ['arr_emails_notif', 'arr_emails_diff',
        [
          $key_func,
          [
            'preg',
            '/^([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+,)*'
            . '([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+)$/'
          ]
        ]
      ],
      ['arr_enable_diff', [$key_func, ['digits', [1, 1]]]]
    ]
  ];
  $in = [
    'log_accum' => 1,
    'arr_branches' => ['11' => 'br11', '22' => '_br12', '33' => 'br-33'],
    'arr_id' => ['11' => '1', '22' => '1', '33' => '1'],
    'arr_remove' => ['44' => '0', '99' => 'sources'],
    'arr_repo_name' => ['11' => 'sources', '22' => 'web', '33' => 'sources'],
    'arr_match_type' => ['11' => 'ALL', '22' => 'dir_list', '33' => 'DEFAULT'],
    'arr_dir_list' => ['11' => 'abc/def,ghi', '22' => 'def', '33' => 'ghi'],
    'arr_emails_notif' =>
      ['11' => 'a@b.c', '22' => 'c@d.e,f@g.h', '33' => 'x@y.zyx'],
    'arr_emails_diff' =>
      ['11' => 'A@B.C', '22' => 'C@D.Eacd,F@G.iHi', '33' => 'X@Y.Z'],
    'arr_enable_diff' => ['11' => 1, '33' => 1]
  ];
  $out = $in;
  $out['arr_remove'][44] = true;
  $out['arr_remove'][99] = true;
  $in['arr_branches']['44'] = 'b"r';
  $in['arr_branches']['55'] = '<>';
  $in['arr_enable_diff']['22'] = 'a';
  $in['arr_enable_diff']['44'] = "0";
  $in['arr_emails_notif']['44'] = 'x@y.z,a';
  $in['arr_emails_notif']['55'] = 'a@b';
  $in['arr_emails_notif']['66'] = 'a@b.c@d.e';
  $in['arr_dir_list']['44'] = 'a@b,c/d';
  test_sane_import ($in, $names, $out);
  $in['arr_remove']['aa'] = 0;
  $in['arr_remove']['a4a'] = 5;
  $in['arr_emails_notif'][88] = 'agn';
  $in['arr_enable_diff'][77] = 2;
  test_sane_import ($in, $names, $out);
}

$reference = 'i18n.php';
{
  $names = [
    'digits' => 'language',
    'internal_uri' => 'lang_uri',
    'true' => ['cookie_test', 'cookie_for_a_year']
  ];

  $in = [
    'language' => 3,
    'lang_uri' => '/account/logout.php',
    'cookie_for_a_year' => 1
  ];

  $out = $in;
  $out['cookie_for_a_year'] = true;
  $out['cookie_test'] = null;

  test_sane_import ($in, $names, $out);
}

$reference = 'include/context.php';
{
  $names = ['funcs' => 'func'];

  $in = ['func' => 'rbowse'];
  $out = ['func' => null];

  test_sane_import ($in, $names, $out);

  $in = ['func' => 'additem'];
  $out = $in;

  test_sane_import ($in, $names, $out);
}

$reference = 'include/form.php';
{
  $names = ['pass' => 'website'];
  $in = ['website' => ''];
  $out = $in;

  $in['group_id'] = '1234';

  test_sane_import ($in, $names, $out);
}

$reference = 'include/html.php';
{
  $names = ['true' => 'boxoptionwanted'];

  $in = ['boxoptionwanted' => '289'];
  $out = ['boxoptionwanted' => true];

  test_sane_import ($in, $names, $out);

  if ($out['boxoptionwanted'] != 1)
    print "false\n";

  $in = ['comingfrom' => '289'];
  $out = ['boxoptionwanted' => null];

  test_sane_import ($in, $names, $out);
}

$reference = 'include/init.php';
{
  $names = ['digits' => 'comingfrom'];

  $in = ['group' => 'savane'];
  $out = ['comingfrom' => null];

  test_sane_import ($in, $names, $out);

  extract($out);

  if (isset ($comingfrom))
    print "comingfrom: " . $comingfrom . "\n";

  $in = ['comingfrom' => '1234'];
  $out = ['comingfrom' => '1234'];

  test_sane_import ($in, $names, $out);

  $names = [
    'name' => 'group',
    'digits' => ['group_id', 'item_id', 'forum_id']
  ];

  $in = [
    'group' => 'savane',
    'group_id' => '1234',
    'item_id' => '123456',
    'forim_id' => '12'
  ];

  $out = $in;
  unset($out['forim_id']);
  $out['forum_id'] = null;

  $in['printer'] = 1;

  test_sane_import ($in, $names, $out);

  $in['forum_id'] = 12;
  $out['forum_id'] = '12';

  $in['group'] = '1savane';
  $out['group'] = null;

  test_sane_import ($in, $names, $out);
}

$reference = 'include/markup.php'; # The test is the same as for the next file.
$reference = 'include/theme.php';
{
  $names = ['true' => 'printer'];
  $in = [
    'printer' => 1,
    'group' => 'administration'
  ];

  $out = ['printer' => true];

  test_sane_import ($in, $names, $out);

  extract($out);

  if (empty ($printer))
    print "empty\n";

  if ($printer == 1)
    ; # OK.
  else
    print "printer != 1\n";

  $in = ['group' => 'administration'];
  $out = ['printer' => null];

  test_sane_import ($in, $names, $out);

  extract($out);
  if ($printer)
    print '$printer' . "\n";

  unset($printer);
}

$reference = 'include/my/general.php';
{
  $role = 'role';
  $names = [
    "digits" => ["hide_group_id", ["hide_$role", [0, 1]]]
  ];

  $in = [
    'hide_group_id' => '1234a5',
    'hide_role' => '0'
  ];

  $out = $in;
  $out['hide_group_id'] = 1234;

  test_sane_import ($in, $names, $out);

  $in['hide_role'] = 1;
  $out['hide_role'] = 1;

  test_sane_import ($in, $names, $out);

  $in['hide_role'] = 'a';
  $out['hide_role'] = null;

  test_sane_import ($in, $names, $out);
}

$reference = 'include/session.php';
{
  $names = [
    'hash' =>'session_hash', 'digits' => 'session_uid'
  ];

  $in = [
    'session_hash' => '0cc175b9c0f1b6a831c399e269772661',
    'session_uid' => 83521
  ];

  $out = $in;

  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers/data.php';
{
  $tracker_name = 'support';

  $notif_scope_name = $tracker_name . "_notif_scope";
  $new_item_address_name = $tracker_name . "_new_item_address";
  $send_all_changes_name = $tracker_name . "_send_all_changes";
  $nb_categories_name = $tracker_name . "_nb_categories";
  $private_exclude_address_name = $tracker_name . "_private_exclude_address";

  $names = [
   "strings" =>
      [[$notif_scope_name, ['default' => 'global', 'category', 'both']]],
    'pass' => [$new_item_address_name, $private_exclude_address_name],
    'true' => $send_all_changes_name,
    'digits' => $nb_categories_name
  ];

  $in = [
    $notif_scope_name => 'category',
    $new_item_address_name => 'reports@example.net,agn',
    $private_exclude_address_name => 'reports@example.net'
  ];

  $out = $in;
  $out[$send_all_changes_name] = null;
  $out[$nb_categories_name] = null;

  test_sane_import ($in, $names, $out);

  $in[$new_item_address_name] = 'agn, "a.g.n." <reports@example.net>';
  $in[$send_all_changes_name] = 1;
  $in[$notif_scope_name] = 'local';
  $in[$nb_categories_name] = 3;
  $out = $in;
  $out[$notif_scope_name] = 'global';

  test_sane_import ($in, $names, $out);

  $fv_name = $tracker_name . "_cat_1_bug_fv_id";
  $email_name = $tracker_name . "_cat_1_email";
  $send_all_name = $tracker_name . "_cat_1_send_all_flag";
  $names = [
    'digits' => $fv_name,
    'pass' => $email_name,
    'true' => $send_all_name
  ];

  $in = [
    $fv_name => 'b4913c55',
    $email_name => '"a.g.n." <agn@example.net>',
  ];

  $out = $in;
  $out[$fv_name] = 4913;
  $out[$send_all_name] = null;

  test_sane_import ($in, $names, $out);
}

# The test for the following files is the same.
$reference = 'js/hide-feedback.php';
$reference = 'js/show-feedback.php';
{
  $names = [
    'preg' => [['suffix', '/^\w*$/']]
  ];
  $in = ['suffix' => 'a-b'];
  $out = ['suffix' => null];
  test_sane_import ($in, $names, $out);
  $in['suffix'] = 'abcd';
  $out = $in;
  test_sane_import ($in, $names, $out);
}

# The test for the following files is the same.
$reference = 'js/hide-span.php';
{
  $names = [
    'preg' => [['box_id', '/^\w*$/']]
  ];
  $in = ['box_id' => 'a-b'];
  $out = ['box_id' => null];
  test_sane_import ($in, $names, $out);
  $in['box_id'] = 'abcd';
  $out = $in;
  test_sane_import ($in, $names, $out);
}

$reference = 'js/show-hide.php';
{
  $names = [
    'true' => 'deploy',
    'preg' => [['box_id', 'suffix', '/^\w*$/']],
    'specialchars' => 'legend'
  ];
  $in = [
    'box_id' => 'a-b',
    'suffix' => 'ext',
    'legend' => '<email>',
  ];
  $out = $in;
  $out['box_id'] = null;
  $out['legend'] = '&lt;email&gt;';
  $out['deploy'] = null;
  test_sane_import ($in, $names, $out);
  $in['box_id'] = 'abcd';
  $out['box_id'] = 'abcd';
  $in['deploy'] = 1;
  $out['deploy'] = true;
  test_sane_import ($in, $names, $out);
}

$reference = 'markup-test.php';
{
  $names = [
    'specialchars' => 'comment',
    'true' => ['basic', 'rich', 'full']
  ];

  $in = [
    'comment' => '<a href="b">c</d>',
    'basic' => 'Basic Markup'
  ];

  $out = [
    'comment' => '&lt;a href=&quot;b&quot;&gt;c&lt;/d&gt;',
    'basic' => true,
    'rich' => null,
    'full' => null
  ];

  test_sane_import ($in, $names, $out);
}

$reference = 'my/bookmarks.php';
{
  $names = [
    'true' => 'add',
    'digits' => 'delete'
  ];
  $in = [
    'delete' => '1234'
  ];
  $out = $in;
  $out['add'] = null;

  test_sane_import ($in, $names, $out);

  $in['add'] = 1;
  $out['add'] = true;

  test_sane_import ($in, $names, $out);

  $names = ['digits' => 'edit', 'pass' => ['url', 'title']];
  $in = [
    'edit' => 123456,
    'url' => '/my/bookmarks.php',
    'title' => 'proba - bookmark'
  ];
  $out = $in;

  test_sane_import ($in, $names, $out);
}

$reference = 'my/items.php';
{
  $names = [
    'digits' => [['form_threshold', [1, 9]]],
    'strings' => [['form_open', ['open', 'closed']]],
    'true' => 'boxoptionwanted'
  ];

  $in = ['form_threshold' => '10'];

  $out = [
    'form_threshold' => null,
    'form_open' => null,
    'boxoptionwanted' => null
  ];

  test_sane_import ($in, $names, $out);

  $in['form_threshold'] = 1;
  $out['form_threshold'] = 1;
  $in['form_open'] = 'closed';
  $out['form_open'] = 'closed';

  test_sane_import ($in, $names, $out);

  if (!$in['form_open'])
    print "!form_open\n";
}

$reference = 'register/upload.php';
# (no test: the only import is a file)

$reference = 'sendmessage.php';
{
  unset ($out);

  $out['send_mail'] = 'Send Mail';
  $out['touser'] = '54321';
  $out['fromuser'] = 'agn';
  $out['subject'] = 'test subject';
  $out['body'] = 'test body';
  $out['feedback'] = 'no error';

  $in = $out;
  $in['cancel'] = 'cancel';
  $in['group_id'] = '1234';

  $out['send_mail'] = true;

  $names =  [
    'true' => 'send_mail',
    'digits' => 'touser',
    'name' => 'fromuser',
    'pass' => ['subject', 'body', 'feedback']
  ];

  test_sane_import ($in, $names, $out);
}

?>
