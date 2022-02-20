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

$reference = 'forum/forum.php';
{
  $names = ['digits' => 'forum_id'];
  $in = ['forum_id' => 'a;34.b'];
  $out = ['forum_id' => '34'];
  test_sane_import ($in, $names, $out);
  $names = [
    'digits' => ['offset', 'max_rows'],
    'strings' =>
      [
        ['style', ['default' => 'nested', 'flat', 'threaded', 'nocomments']],
        ['set', ['custom']]
      ]
  ];
  $in = [
   'offset' => '1',
   'style' => 'no',
   'max_rows' => '100',
   'set' => 'custom'
  ];
  $out = $in;
  $out['style'] = 'nested';
  test_sane_import ($in, $names, $out);
  $in['set'] = 1;
  $out['set'] = null;
  test_sane_import ($in, $names, $out);
  $names = [
    'true' => 'post_message',
    'specialchars' => ['subject', 'body'],
    'digits' => ['is_followup_to', 'thread_id']
  ];
  $in = [
    'subject' => '<subject>',
    'body' => '"test body"',
    'thread_id' => '4913'
  ];
  $out = $in;
  $out['post_message'] = null;
  $out['is_followup_to'] = null;
  $out['subject'] = htmlspec ($out['subject']);
  $out['body'] = htmlspec ($out['body']);
  test_sane_import ($in, $names, $out);
  $in['post_message'] = 'n';
  $out['post_message'] = true;
  $in['is_followup_to'] = '#76*98';
  $out['is_followup_to'] = '76';
  test_sane_import ($in, $names, $out);
}

$reference = 'forum/message.php';
{
  $names =['digits' => 'msg_id'];
  $in = ['msg_id' => '1'];
  $out = $in;
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

# The test is the same the next two files.
$reference = 'include/markup.php';
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

$reference = 'include/trackers/cookbook.php';
{
  $names = [
    'true' => [
      'recipe_audience_technicians',
      'recipe_audience_managers',
      'recipe_audience_anonymous',
      'recipe_audience_loggedin',
      'recipe_audience_members',
      'recipe_context_stats',
      'recipe_context_siteadmin',
      'recipe_context_my',
      'recipe_context_project',
      'recipe_context_homepage',
      'recipe_context_download',
      'recipe_context_mail',
      'recipe_context_cvs',
      'recipe_context_arch',
      'recipe_context_svn',
      'recipe_context_support',
      'recipe_context_bugs',
      'recipe_context_task',
      'recipe_context_patch',
      'recipe_context_cookbook',
      'recipe_context_news',
      'recipe_subcontext_browsing',
      'recipe_subcontext_search',
      'recipe_subcontext_postitem',
      'recipe_subcontext_edititem',
      'recipe_subcontext_configure',
    ]
  ];
  $in = [
    'recipe_audience_technicians' => 1,
    'recipe_audience_managers' => 0,
    'recipe_audience_anonymous' => 1,
    'recipe_audience_loggedin' => 20,
    'recipe_audience_members' => 1,
    'recipe_context_stats' => 'a',
    'recipe_context_siteadmin' => 1,
    'recipe_context_my' => 'b',
    'recipe_context_project' => 1,
    'recipe_context_homepage' => 1,
    'recipe_context_download' => '0x121'
  ];
  $out = [
    'recipe_audience_technicians' => true,
    'recipe_audience_managers' => true,
    'recipe_audience_anonymous' => true,
    'recipe_audience_loggedin' => true,
    'recipe_audience_members' => true,
    'recipe_context_stats' => true,
    'recipe_context_siteadmin' => true,
    'recipe_context_my' => true,
    'recipe_context_project' => true,
    'recipe_context_homepage' => true,
    'recipe_context_download' => true,
    'recipe_context_mail' => null,
    'recipe_context_cvs' => null,
    'recipe_context_arch' => null,
    'recipe_context_svn' => null,
    'recipe_context_support' => null,
    'recipe_context_bugs' => null,
    'recipe_context_task' => null,
    'recipe_context_patch' => null,
    'recipe_context_cookbook' => null,
    'recipe_context_news' => null,
    'recipe_subcontext_browsing' => null,
    'recipe_subcontext_search' => null,
    'recipe_subcontext_postitem' => null,
    'recipe_subcontext_edititem' => null,
    'recipe_subcontext_configure' => null
  ];
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

$reference = 'include/trackers/format.php';
{
  $names = [
    'strings' => [
      [
        'func',
        [
         'flagspam', 'unflagspam', 'viewspam', 'delete_file',
         'delete_cc'
        ]
      ]
    ],
    'digits' => 'comment_internal_id'
  ];
  $in = [
    'func' => 'viewspam',
    'comment_internal_id' => 23
  ];
  $out = $in;
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers/general.php';
{
  $in = [];
  for ($i = 1; $i < 5; $i++)
    {
      $n = "input_file$i";
      $filenames[] = $n;
      $in[$n] = "<a href $i\n" . '?*';
    }
  $names = ['pass' => $filenames];
  $out = $in;
  test_sane_import ($in, $names, $out);

  $names = ['specialchars' => 'file_description'];
  $in = [
    'file_description' => '&a<>@'
  ];
  $out = [
    'file_description' => htmlspec ($in['file_description'])
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/add.php';
{
  $names = [
    'hash' => 'form_id', 'array' => [['prefill', [null, 'specialchars']]]
  ];
  $in = [
    'form_id' => md5 ('a'),
    'prefill' => ['"prefill"', 'prefill', 'p<r>efill']
  ];
  $out = $in;
  foreach ($in['prefill'] as $key => $val)
    $out['prefill'][$key] = htmlspec ($val);
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/conf-copy.php';
{
  $names = ['true' => 'update', 'digits' => 'from_group_id'];
  $in = ['from_group_id' => 289];
  $out = $in;
  $out['update'] = null;
  test_sane_import ($in, $names, $out);
  $in['update'] = 0;
  $out['update'] = true;
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/editqueryforms.php';
{
  $names = [];
  $names['true'] = [
    'post_changes', 'set_default', 'create_report', 'update_report'
  ];
  $names['specialchars'] = ['rep_name', 'rep_desc'];
  $names['strings'] = [['rep_scope', 'P']];

  $prefices = ['TFSRCH', 'TFREP', 'TFCW', 'CBSRCH', 'CBREP'];
  $suffices = [
    'bug_id', 'submitted_by', 'date', 'close_date', 'planned_starting_date',
    'planned_close_date', 'category_id', 'priority', 'resolution_id',
    'privacy', 'vote', 'percent_complete', 'assigned_to', 'status_id',
    'discussion_lock', 'hours', 'summary', 'details', 'severity',
    'bug_group_id', 'originator_name', 'originator_email', 'originator_phone',
    'release', 'release_id', 'category_version_id', 'platform_version_id',
    'reproducibility_id', 'size_id', 'fix_release_id', 'comment_type_id',
    'plan_release_id', 'component_version', 'fix_release', 'plan_release',
    'keywords',
  ];

  $custom_suff = ['tf' => 10, 'ta' => 10, 'sb' => 10, 'df' => 5];

  foreach ($custom_suff as $suf => $num)
    for ($i = 1; $i <= $num; $i++)
      $suffices[] = 'custom_' . $suf . $i;

  $names['digits'] = [];
  foreach ($prefices as $pref)
    foreach ($suffices as $suf)
      $names['digits'][] = $pref . '_' . $suf;

  $in = [
    'post_changes' => 0,
    'rep_name' => '"name<">',
    'rep_desc' => "'description'",
    'rep_scope' => "W",
    'TFCW_date' => 40,
    'CBSRCH_custom_sb3' => 4,
    'CBREP_custom_tf8' => 3,
    'CBREP_custom_ta1' => 17,
    'CBREP_custom_df2' => 2,
    'CBREP_custom_df10' => 0,
  ];

  $out = $in;
  $out['post_changes'] = true;
  $out['set_default'] = null;
  $out['create_report'] = null;
  $out['update_report'] = null;
  $out['rep_scope'] = null;
  $out['rep_name'] = htmlspec ($out['rep_name']);
  $out['rep_desc'] = htmlspec ($out['rep_desc']);
  unset ($out['CBREP_custom_df10']);

  foreach ($names['digits'] as $n)
    if (!isset ($in[$n]))
      $out[$n] = null;

  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/field_usage.php';
{
  $names = ['name' => 'field', 'true' => 'update_field'];
  $in = ['field' => 'custom_tf9'];
  $out = $in;
  $out['update_field'] = null;
  test_sane_import ($in, $names, $out);

  $names = [
    'true' =>
      [
        'post_changes', 'submit', 'reset'
      ],
    'specialchars' => ['label', 'description'],
    'digits' =>
       [
         ['status', 'keep_history', [0, 1]],
         ['mandatory_flag', [0, 3]],
         'place', 'n1', 'n2'
       ],
     'strings' =>
       [
         ['form_transition_default_auth', ['A', 'F']],
         ['show_on_add', 'show_on_add_members', ['1']],
         ['show_on_add_logged', ['2']]
       ]
  ];
  $in = [
    'label' => "'<label>'",
    'description' => 'de&sc',
    'status' => 1,
    'keep_history' => 0,
    'mandatory_flag' => 2,
    'place' => 10,
    'n1' => '80', 'n2' => '25',
    'form_transition_default_auth' => 'A',
    'show_on_add' => '1',
    'show_on_add_logged' => '2',
  ];
  $out = $in;
  $out['label'] = htmlspec ($out['label']);
  $out['description'] = htmlspec ($out['description']);
  $out['post_changes'] = null;
  $out['submit'] = null;
  $out['reset'] = null;
  $out['show_on_add_members'] = null;
  $out['show_on_add'] = true;

  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/field_values.php';
{
  $names = [
    'strings' => [['func', ['deltransition']]],
    'true' => ['update_value', 'create_canned', 'update_canned'],
    'digits' => ['fv_id', 'item_canned_id'],
    'name' => 'field'
  ];
  $in = [
    'func' => 'deltransition',
    'field' => 'category_id',
    'update_canned' => 1,
    'fv_id' => 289,
    'item_canned_id' => 4913
  ];
  $out = $in;
  $out['update_canned'] = true;
  $out['update_value'] = null;
  $out['create_canned'] = null;
  test_sane_import ($in, $names, $out);

  $names = [
    'true' => ['list_value'],
    'strings' => [['delete_canned', [1]]],
    'digits' => 'transition_id'
  ];
  $in = [
    'delete_canned' => 1,
    'transition_id' => 'x54y'
  ];
  $out = [
    'list_value' => null,
    'delete_canned' => '1',
    'transition_id' => 54
  ];
  test_sane_import ($in, $names, $out);

  $names = [
    'true' => ['post_changes', 'create_value', 'by_field_id'],
    'specialchars' => ['title', 'description', 'body'],
    'digits' => ['order_id', 'from', 'to'],
    'strings' =>
      [
        ['allowed', ['A', 'F']],
        ['status', ['A', 'P', 'H']]
      ],
    'preg' => [['mail_list', '/^[-+_@.,\s\da-zA-Z]*$/']]
  ];
  $in = [
    'post_changes' => 'y',
    'title' => 'a&b',
    'description' => 'de<scription>',
    'body' => 'b"od"y',
    'order_id' => 0,
    'from' => 1,
    'to' => 2,
    'allowed' => 'F',
    'status' => 'P',
    'mail_list' => 'agn_ter, dis-cuss0@test.mil, A+B@C@.org,.,'
  ];
  $out = $in;
  $out['post_changes'] = true;
  $out['create_value'] = null;
  $out['by_field_id'] = null;
  $out['title'] = htmlspec ($in['title']);
  $out['description'] = htmlspec ($in['description']);
  $out['body'] = htmlspec ($in['body']);
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/field_values_reset.php';
{
  $names = [
    'name' => 'field',
    'true' => ['confirm', 'cancel']
  ];
  $in = ['field' => 'severity', 'cancel' => 'Cancel'];
  $out = [
    'field' => 'severity',
    'cancel' => true,
    'confirm' => null
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/field_values_transition-ofields-update.php';
{
  $names = ['digits' => 'transition_id'];
  $in = ['form_id' => 'abcdef01', 'transition_id' => 'i'];
  $out = ['transition_id' => null];
  test_sane_import ($in, $names, $out);
  $all_fields = ['category_id', 'resolution_id', 'privacy', 'status_id'];
  $name_digits = [];
  foreach ($all_fields as $f)
    $name_digits[] = "form_$f";
  $names = [
    'true' => 'update',
    'digits' => $name_digits
  ];
  $in = [];
  foreach ($name_digits as $n)
    $in[$n] = '102';
  $out = $in;
  $in['update'] = 'Update';
  $out['update'] = true;
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/notification_settings.php';
{
  $names = ['true' => 'submit'];
  $in = [];
  $out = ['submit' => null];
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/other_settings.php';
{
  $names = ['true' => 'submit', 'specialchars' => 'form_preamble'];
  $in = ['submit' => 'Submit', 'form_preamble' => 'a'];
  $out = $in;
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/admin/userperms.php';
{
  define ('ARTIFACT', 'bugs');
  $names = [
    'true' => 'update',
    'digits' =>
      [
        ARTIFACT . '_restrict_event2',
        [ARTIFACT . '_restrict_event1', [0, 99]]
      ]
  ];
  $in = [
    'update' => 'Update',
    ARTIFACT . '_restrict_event2' => 4913,
    ARTIFACT . '_restrict_event1' => 289,
  ];
  $out = [
    'update' => true,
    ARTIFACT . '_restrict_event2' => 4913,
    ARTIFACT . '_restrict_event1' => null,
  ];
  test_sane_import ($in, $names, $out);
  $in[ARTIFACT . '_restrict_event1'] = 68;
  $in['update'] = null;
  $out = $in;
  test_sane_import ($in, $names, $out);
  if (null * 100 + 51 != 51)
    {
      print_reference ();
      print '(null * 100 + 51 != 51)' . "\n";
    }
}

$reference = 'include/trackers_run/browse.php';
{
  $names = [
    'digits' =>
      [
        'chunksz', 'offset', 'report_id',
        ['msort', 'sumORdet', 'advsrch', 'history_search', [0, 1]],
        ['spamscore', [1, null]],
        ['history_date_yearfd', [1900, null]],
        ['history_date_monthfd', [1, 12]],
        ['history_date_dayfd', [1, 31]],
      ],
    'name' => 'history_field',
    'strings' =>
      [
        ['func', ['default' => 'browse', 'digest']],
        ['set', ['custom', 'my', 'open']],
        ['history_event', ['modified', 'not modified']]
      ],
    'preg' =>
      [
        ['history_date', '/^\d{4}-\d{1,2}-\d{1,2}$/'],
        ['order', '/^([_a-zA-Z-][_[:alnum:]-]*)?$/'],
        ['morder', '/^[,<>_[:alnum:]-]*$/']
      ],
    'true' => 'printer'
  ];
  $in = [
    'chunksz' => 40,
    'offset' => 83521,
    'report_id' => 5,
    'history_field' => 0,
    'msort' => 0,
    'sumORdet' => 0,
    'advsrch' => 1,
    'history_search' => 1,
    'spamscore' => 40,
    'history_date_yearfd' => 1983,
    'history_date_monthfd' => 9,
    'history_date_dayfd' => 27,
    'func' => 'digestselectfield',
    'order' => 'priority',
    'set' => 'my',
    'history_event' => 'modified',
    'history_date' => '1983-09-27',
    'morder' => 'bug_id<,status>',
    'printer' => 'printer'
  ];
  $out = $in;
  $out['func'] = 'browse';
  test_sane_import ($in, $names, $out);
  $in['order'] = '';
  $out['order'] = '';
  unset ($in['advsrch']);
  $out['advsrch'] = null;
  test_sane_import ($in, $names, $out);
  if (intval (null) != 0)
    {
      print_reference ();
      print 'intval(null) != 0' . "\n";
    }
  $co_field = 'status_op';
  $names = ['strings' => [[$co_field, ['>', '=', '<']]]];
  $in = [$co_field => '='];
  $out = $in;
  test_sane_import ($in, $names, $out);
  $co_field = 'category_end';
  $names = ['preg' => [[$co_field, '/^\d{4}-\d{1,2}-\d{1,2}$/']]];
  $in = [$co_field => '1983-09-27'];
  $out = $in;
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/detail-sober.php';
{
  $names = ['digits' => 'comingfrom'];
  $in = ['digits' => '123'];
  $out = ['comingfrom' => null];
  test_sane_import ($in, $names, $out);
  $in['comingfrom'] = 'x119y';
  $out['comingfrom'] = '119';
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/digest.php';
{
  $names = [
    'funcs' => 'func',
    'digits' =>  'dependencies_of_item',
    'artifact' => 'dependencies_of_tracker',
    'array' =>
      [
        ['items_for_digest', ['digits', 'digits']],
        ['field_used', ['name', ['digits', [0, 1]]]]
      ]
  ];
  $in = [
    'func' => 'digestselectfield',
    'dependencies_of_item' => 289,
    'dependencies_of_tracker' => 'bugs',
    'items_for_digest' => ['4913' => '83521', '1' => '2'],
    'field_used' => ['status_id' => '0', 'summary' => '1']
  ];
  $out = $in;
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/download.php';
{
  $names = ['digits' => 'file_id'];
  $in = ['file_id' => 1];
  $out = $in;
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/export.php';
{
  $names = ['true' => 'download'];
  $in = ['download' => 1];
  $out = ['download' => true];
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/index.php';
{
  $names = [
    'funcs' => 'func',
    'true' => 'printer',
    'digits' => ['item_file_id', 'item_cc_id']
  ];
  $in = [
    'func' => 'digest',
    'item_file_id' => 1,
    'item_cc_id' => 289
  ];
  $out = $in;
  $out['printer'] = null;
  test_sane_import ($in, $names, $out);
  $names = [
    'hash' => 'form_id',
    'pass' =>
      [
        'comment', 'additional_comment', 'depends_search',
        'reassign_change_project_search'
      ],
    'digits' => ['comment_type_id', 'new_vote', 'quote_no'],
    'specialchars' => 'cc_comment',
    'preg' =>
      [
        ['canned_response', '/^(\d+|!multiple!)$/'],
        [
          'originator_email',
          '/^[a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+$/'
        ],
        ['add_cc', '/^[-+_@.,;\s\da-zA-Z]*$/'],
        [
          'reassign_change_project', '/^[-_[:alnum:]]*$/'
        ]
      ],
    'strings' =>
      [
        [
          'depends_search_only_artifact',
          'reassign_change_artifact',
          ['all', 'support', 'bugs', 'task', 'patch']
        ],
        [
          'depends_search_only_project',
          ['any', 'notany']
        ]
      ],
    'true' =>
      [
        'submitreturn',
        'preview',
      ],
    'array' =>
      [
        [
          'dependent_on_task', 'dependent_on_bugs', 'dependent_on_support',
          'dependent_on_patch',
          [null, 'digits']
        ]
      ]
  ];
  $in = [
    'form_id' => md5 ('form_id'),
    'comment' => 'comment',
    'additional_comment' => 'a',
    'depends_search' => 'd',
    'reassign_change_project_search' => 'r',
    'comment_type_id' => 127,
    'new_vote' => 10,
    'quote_no' => 3,
    'cc_comment' => 'a',
    'canned_response' => '!multiple!',
    'originator_email' => 'agn@test.mil',
    'add_cc' => 'agn,a@b.ca;d@e.fgh',
    'reassign_change_project' => '0-grep_up',
    'depends_search_only_artifact' => 'bugs',
    'reassign_change_artifact' => 'all',
    'depends_search_only_project' => 'any',
    'submitreturn' => true,
    'preview' => true,
    'dependent_on_task' => [1, 2, 3],
    'dependent_on_bugs' => [2, 3, 4],
    'dependent_on_support' => [3, 4, 5],
    'dependent_on_patch' => [4, 5, 6],
  ];
  $out = $in;
  test_sane_import ($in, $names, $out);
  $in['canned_response'] = ['127', '128', '129'];
  $out['canned_response'] = null;
  $out = $in;
  $names = ['array' => [['canned_response', [null, 'digits']]]];
  $in = ['canned_response' => ['127', '128', '129']];
  $out = $in;
  test_sane_import ($in, $names, $out);
  $names = [
    'digits' => ['comment_internal_id', 'item_depends_on'],
    'artifact' => 'item_depends_on_artifact',
  ];
  $in = [
    'comment_internal_id' => 1,
    'item_depends_on_artifact' => 'bugs'
  ];
  $out = $in;
  $out['item_depends_on'] = null;
  test_sane_import ($in, $names, $out);
  $names = ['hash' => 'form_id', 'digits' => 'check', 'pass' => 'details'];
  $in = ['form_id' => md5(''), 'check' => 1];
  $out = $in;
  $out['details'] = null;
  test_sane_import ($in, $names, $out);
  $names = [
    'hash' => 'form_id', 'digits' => ['check', 'item_id'],
    'pass' => 'comment'
  ];
  $out['item_id'] = $in['item_id'] = 3;
  $out['comment'] = $in['comment'] = 'comment';
  unset ($out['details']);
  test_sane_import ($in, $names, $out);
}

$reference = 'include/trackers_run/reporting.php';
{
  $names = ['name' => 'field'];
  $in = $out = ['field' => 'aging'];
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
