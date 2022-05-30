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
    'true' => ['brotherhood', 'cookie_for_a_year', 'login', 'cookie_test'],
    'name' => 'form_loginname',
    'pass' => 'form_pw',
    'internal_uri' => 'uri'
  ];
  $in = [
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

$reference = 'file';
{
  $names = ['preg' => [['file_id', '/^(\d+|test[.]png)$/']]];
  $in = $out = ['file_id' => 'test.png'];
  test_sane_import ($in, $names, $out);
  $in = $out = ['file_id' => '12345'];
  test_sane_import ($in, $names, $out);
  $in['file_id'] = '124a';
  $out['file_id'] = null;
  test_sane_import ($in, $names, $out);
  $in = [0 => 'task'];
  $out = [];
  if ($sane_sanitizers['artifact'] ($in, $out, 0, null) || $out[0] != 'task')
    print "\$sane_sanitizers['artifact'] () doesn't work\n";
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
    'preg' => [['language', '/^(([a-z]{2}((-[a-z]{2})?))|100)$/']],
    'internal_uri' => 'lang_uri',
    'true' => ['cookie_test', 'cookie_for_a_year']
  ];

  $in = [
    'language' => 'pt-br',
    'lang_uri' => '/account/logout.php',
    'cookie_for_a_year' => 1
  ];

  $out = $in;
  $out['cookie_for_a_year'] = true;
  $out['cookie_test'] = null;

  test_sane_import ($in, $names, $out);
  $out['language'] = $in['language'] = 'he';
  test_sane_import ($in, $names, $out);
  $out['language'] = $in['language'] = 100;
  test_sane_import ($in, $names, $out);
  $out['language'] = null;
  $in['language'] = 'EN';
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
  $names = [
    'hash' => 'form_id', 'strings' => [['check', '1984']],
    'pass' => 'details'
  ];
  $in = ['form_id' => md5(''), 'check' => '1984'];
  $out = $in;
  $out['details'] = null;
  test_sane_import ($in, $names, $out);
  $in['check'] = '1985'; $out['check'] = null;
  test_sane_import ($in, $names, $out);
  $in['check'] = 1984; $out['check'] = '1984';
  test_sane_import ($in, $names, $out);
  $names = [
    'hash' => 'form_id', 'digits' => ['item_id'],
    'strings' => [['check', '1984']], 'pass' => 'comment'
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
    'digits' => 'deploy',
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
  $out['deploy'] = 1;
  test_sane_import ($in, $names, $out);
}

$reference = 'mail/admin/index.php';
{
  $key_func = ['preg', '/^(\d+|new)$/'];
  $names = [
    'true' => 'post_changes',
    'array' =>
      [
        [
          'list_name',
          [
            $key_func,
            ['name', ['max_len' => 80, 'allow_dots' => true]]
          ]
        ],
        ['description', [$key_func, 'specialchars']],
        ['reset_password', [$key_func, 'true']],
        ['is_public', 'newlist_format_index', [$key_func, 'digits']],
      ],
  ];
  $in = $out = [
    'post_changes' => true,
    'list_name' =>
      [
        'new' => 'list.test',
        '123' => 'tes9-t.list',
      ],
    'description' =>
      [
        'new' => 'new description',
        '12' => '3',
      ],
    'is_public' => ['1' => 289],
    'reset_password' => ['new' => true],
    'newlist_format_index' => ['1' => '34'],
  ];
  $in['description']['wen'] = '1';
  $in['description']['45'] = '<b id="';
  $out['description']['45'] = htmlspec ($in['description']['45']);
  $in['is_public']['abc'] = 'e';
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

$reference = 'my/admin/cc.php';
{
  $names = ['preg' => [['cancel', '/^(\d+|any)$/']]];
  $in = $out = ['cancel' => '1'];
  test_sane_import ($in, $names, $out);
  $in = $out = ['cancel' => 'any'];
  test_sane_import ($in, $names, $out);
  $in['cancel'] = 'a';
  $out['cancel'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'my/admin/change_notifications.php';
{

  $notif_arr = [
    'notify_unless_im_author', 'notify_item_closed',
    'notify_item_statuschanged', 'skipcc_postcomment',
    'skipcc_updateitem', 'removecc_notassignee',
  ];

  $names = [
     'true' => ['update'],
     'digits' => [['form_frequency', [0, 3]]],
     'pass' => 'form_subject_line', # Validated later.
  ];

  foreach ($notif_arr as $n)
    $names['true'][] = "form_$n";

  $in = $out = [
    'form_frequency' => 2,
    'form_subject_line' => 'subject'
  ];
  foreach ($names['true'] as $n)
    $in[$n] = $out[$n] = true;
  unset ($in['form_removecc_notassignee']);
  $out['form_removecc_notassignee'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'my/admin/change.php';
{
  $names = [
    'strings' =>
      [
        [
          'item',
          ['delete', 'realname', 'timezone', 'password', 'gpgkey', 'email']
        ],
        ['step', ['confirm', 'confirm2', 'discard']],
      ],
    'true' => ['update', 'test_gpg_key'],
    'hash' => ['session_hash', 'confirm_hash', 'form_id'],
  ];
  $in = $out = [
   'item' => 'realname',
   'update' => true,
   'step' => 'discard',
   'test_gpg_key' => true,
   'session_hash' => md5 (5),
   'confirm_hash' => md5 (6),
   'form_id' => md5 (7),
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'my/admin/editsshkeys.php';
{
  $names = [
    'true' => 'update',
    'hash' => 'form_id',
    'array' =>
      [
        ['form_authorized_keys', ['digits', 'no_quotes']]
      ]
  ];
  $in = $out = [
    'form_id' => md5 (8),
    'form_authorized_keys' => ['a', 'b', 'c', 3 => '"']
  ];
  $out['update'] = null;
  unset ($out['form_authorized_keys'][3]);
  test_sane_import ($in, $names, $out);
}

$reference = 'my/admin/index.php';
{
  $names = [
    'true' =>
      [
        'update', 'form_keep_only_one_session', 'theme_rotate_jump',
        'form_reverse_comments_order', 'form_stone_age_menu',
        'form_nonfixed_feedback', 'form_use_bookmarks', 'form_email_hide',
        'form_email_encrypted'
      ],
    'no_quotes' => ['form_timezone', 'user_theme']
  ];
  $in = $out = [
    'update' => true,
    'form_keep_only_one_session' => true,
    'form_timezone' => 'Africa/Ouagadougou',
    'user_theme' => 'www.gnu.org'
  ];
  $out['theme_rotate_jump'] = $out['form_reverse_comments_order']
    = $out['form_stone_age_menu'] = $out['form_nonfixed_feedback']
    = $out['form_use_bookmarks'] = $out['form_email_hide']
    = $out['form_email_encrypted'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'my/admin/resume.php';
{
  $names = [
    'true' =>
      [
        'update_profile', 'add_to_skill_inventory', 'update_skill_inventory',
        'delete_from_skill_inventory'
      ],
    'digits' =>
      [
        'skill_id', 'skill_level_id', 'skill_year_id', 'skill_inventory_id',
        ['people_view_skills', [0, 1]],
      ],
    'pass' => 'people_resume'
  ];
  $in = $out = [
    'update_profile' => true,
    'add_to_skill_inventory' => true,
    'update_skill_inventory' => true,
    'delete_from_skill_inventory' => true,
    'skill_id' => 4,
    'people_view_skills' => 0,
    'people_resume' => 'a\'&b"',
  ];
  $out['skill_level_id'] = $out['skill_year_id'] = $out['skill_inventory_id']
    = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'my/admin/sessions.php';
{
  $names = [
    'strings' => [['func', 'del']],
    'true' => 'dkeep_one',
    'digits' => 'dtime',
    'preg' =>
      [
        ['dip_addr', ',^[\d./:]+$,'],
        ['dsession_hash', '/^[a-f\d]+[.]{3}$/']
      ],
  ];
  $in = $out = [
    'dip_addr' => '127.0.0.1/24:5',
    'func' => 'del',
    'dkeep_one' => true,
    'dtime' => 1,
    'dsession_hash' => substr (md5 (0), 0, 6) . "..."
  ];
  test_sane_import ($in, $names, $out);
  $in['func'] = 'add'; $out['func'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'my/groups.php';
{
  $names = [
    'true' => 'update',
    'hash' => 'form_id',
    'pass' => 'form_message',
    'array' => [['form_groups', ['digits', 'true']]],
  ];
  $in = $out = [
    'update' => true,
    'form_id' => 'x',
    'form_message' => '<b id=\'',
    'form_groups' => ['on', 'on', 'on', 'on'],
  ];
  $out['form_id'] = null;
  foreach ($out['form_groups'] as $k => $v)
    $out['form_groups'][$k] = true;
  test_sane_import ($in, $names, $out);
  $names = [
    'strings' => [['func', ['addwatchee', 'delwatchee']]],
    'digits' => ['watchee_id', 'group_id'],
  ];
  $in = $out = [
    'func' => 'delwatchee',
    'watchee_id' => 1,
    'group_id' => 2,
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

$reference = 'my/quitproject.php';
{
  $names = ['digits' => 'quitting_group_id'];
  $in = [
    'confirm' => 'y',
    'quitting_group_id' => '#19',
  ];
  $out = ['quitting_group_id' => 19];
  test_sane_import ($in, $names, $out);
  $names = ['true' => ['confirm', 'cancel']];
  $out = ['confirm' => true, 'cancel' => null];
  test_sane_import ($in, $names, $out);
}

$reference = 'my/votes.php';
{
  $names = [
    'true' => 'submit',
    'array' => [['new_votes', ['digits', 'digits']]],
  ];
  $in = $out = [
    'new_votes' => [1, 2, 3]
  ];
  $out['submit'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'news/admin/index.php';
{
  $names = [
    'true' => 'update',
    'preg' => [['form_news_address', '/^[-+_@.,;\s\da-zA-Z]*$/']],
  ];
  $in = $out = [
    'update' => true,
    'form_news_address' => 'news@test.org',
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'news/index.php';
{
  $names = ['pass' => 'feedback', 'digits' => 'limit'];
  $in = $out = [
    'feedback' => 'a',
    'limit' => 10
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'news/submit.php';
{
  $names = [
    'hash' => 'form_id',
    'true' => 'update',
    'specialchars' => ['summary', 'details'],
  ];
  $in = $out = [
    'form_id' => md5 ('form_id'),
    'update' => true,
    'summary' => 'symmary',
    'details' => 'details',
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'news/approve.php';
{
  $names = [
    'digits' => ['id', 'status', 'for_group_id'],
    'hash' => 'form_id',
    'true' => ['update', 'post_changes', 'approve'],
    'specialchars' => ['summary', 'details'],
  ];
  $in = $out = [
    'id' => 1,
    'status' => 4,
    'for_group_id' => 83521,
    'form_id' => md5 ('md5'),
    'update' => true,
    'summary' => 'sum',
    'details' => 'det'
  ];
  $out['post_changes'] = $out['approve'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'people/admin/index.php';
{
  $names = ['true' => ['people_cat', 'people_skills']];
  $in = $out = ['people_cat' => true, 'people_skills' => true];
  test_sane_import ($in, $names, $out);
  $names = [
    'true' => 'post_changes',
    'specialchars' => ['skill_name', 'cat_name'],
  ];
  $in = [
    'skill_name' => 'inv<a"lidate',
    'cat_name' => 'v\'ery <w id="ell',
  ];
  $out = ['post_changes' => null];
  foreach ($in as $k => $v)
    $out[$k] = htmlspec ($v);
  test_sane_import ($in, $names, $out);
}

$reference = 'people/editjob.php';
{
  $names = [
    'true' =>
      [
       'add_job', 'update_job', 'add_to_job_inventory', 'update_job_inventory',
       'delete_from_job_inventory',
      ],
    'digits' =>
      [
        'status_id', 'category_id', 'job_inventory_id', 'skill_id',
        'skill_level_id', 'skill_year_id',
      ],
    'specialchars' => 'title',
    'pass' => 'description',
  ];
  $in = $out = [
    'add_job' => 'y', 'status_id' => 1, 'category_id' => 2,
    'job_inventory_id' => 3, 'skill_id' => 4, 'skill_level_id' => 5,
    'skill_year_id' => 6,
    'title' => 'a',
    'description' => 'a<"b\''
  ];
  $out['update_job'] = $out['add_to_job_inventory']
    = $out['update_job_inventory'] = $out['delete_from_job_inventory'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'people/index.php';
{
  $names = [
    'true' => 'submit',
    'array' => [['categories', 'types', [null, 'digits']]],
  ];
  $in = $out = [
    'submit' => true,
    'categories' => [1, 1, 1, 1],
    'types' => [1, 2, 3, 5],
  ];
  test_sane_import ($in, $names, $out);
}

# The next two files share the test.
$reference = 'people/resume.php';
$reference = 'people/viewgpg.php';
{
  $names = ['digits' => 'user_id'];
  $in = $out = ['user_id' => 1];
  test_sane_import ($in, $names, $out);
}

$reference = 'people/viewjob.php';
{
  $names = ['digits' => ['group_id', 'job_id']];
  $in = $out = ['job_id' => 1, 'group_id' => '4913'];
  test_sane_import ($in, $names, $out);
}

$reference = 'project/admin/conf-copy.php';
{
  $names = [
    'true' => 'update',
    'digits' => 'from_group_id',
    'artifact' => 'artifact',
  ];
  $in = $out = [
    'from_group_id' => 119,
    'artifact' => 'patch',
  ];
  $out['update'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'project/admin/editgroupfeatures.php';
{
  $post_names = function ()
  {
    $vcs = ['cvs', 'arch', 'svn', 'git', 'hg', 'bzr'];
    $use_url = [
      'bugs', 'support', 'patch', 'task', 'mail', 'download', 'homepage',
      'forum', 'extralink_documentation',
    ];
    $use_ = array_merge ($vcs, $use_url, ['news']);
    $names = ['true' => ['update'], 'specialchars' => ['dir_download']];
    foreach ($use_ as $u)
      $names['true'][] = 'use_' . $u;
    $viewvcs = [];
    foreach ($vcs as $v)
      $viewvcs[] = $v . '_viewcvs';
    $urls = array_merge ($vcs, $viewvcs, $use_url);
    foreach ($urls as $u)
      $names['specialchars'][] = 'url_' . $u;
    $names['specialchars'][] = 'url_cvs_viewcvs_homepage';
    return $names;
  };
  $names = $post_names ();
  $in = $out = [];
  foreach ($names['true'] as $n)
    $in[$n] = $out[$n] = true;
  foreach ($names['specialchars'] as $n)
    $in[$n] = $out[$n] = $n;
  test_sane_import ($in, $names, $out);
}

$reference = 'project/admin/editgroupinfo.php';
{
  $names = [
    'true' =>
      [
        'update', 'update_keyring', 'reset_keyring', 'test_keyring',
        'upgrade_gpl',
      ],
    'pass' => ['new_keyring', 'form_longdesc'],
    'specialchars' => ['form_group_name', 'form_shortdesc'],
    'digits' => 'form_devel_status',
  ];
  $in = $out = [
    'update' => true, 'update_keyring' => true, 'upgrade_gpl' => true,
    'new_keyring' => 'a',
    'form_group_name' => 'grep', 'form_shortdesc' => 'shortdesc',
    'form_longdesc' => 'longdesc',
    'form_devel_status' => 0
  ];
  $out['reset_keyring'] = $out['test_keyring'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'project/admin/editgroupnotifications.php';
{
  $names = [
    'true' => 'update',
    'pass' => 'form_news_address',
    'digits' => [['form_frequency', [0, 3]]],
  ];
  $in = $out = [
    'update' => true,
    'form_news_address' => 'agn@test.org',
    'form_frequency' => 2,
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'project/admin/squadadmin.php';
{
  $names = [
    'true' =>
      [
        'update', 'update_general', 'update_delete_step1',
        'update_delete_step2', 'deletionconfirmed', 'add_to_squad',
        'remove_from_squad',
      ],
    'hash' => 'form_id',
    'array' => [['user_ids', ['digits', 'digits']]],
    'digits' => ['squad_id_to_delete'],
     # form_realname is sanitized further.
    'pass' => 'form_realname',
    'name' => 'form_loginname',
  ];
  $in = $out = [
    'update' => true, 'update_general' => true, 'add_to_squad' => true,
    'form_id' => md5 ('update'), 'squad_id_to_delete' => 289,
    'user_ids' => [1, 2], 'form_realname' => 'agn', 'form_loginname' => 'agn',
  ];
  $out['update_delete_step1'] = $out['update_delete_step2']
    = $out['deletionconfirmed'] = $out['remove_from_squad'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'project/admin/useradmin.php';
{
  $names = [
    'array' => [['user_ids', [null, 'digits']]],
    'pass' => 'words',
    'strings' =>
      [
        [
          'action',
          [
            'approve_for_group', 'remove_from_group', 'add_to_group_list',
            'add_to_group',
          ]
        ]
      ],
  ];
  $in = $out = [
    'user_ids' => [1, 2, 3], 'words' => 'user admin',
    'action' => 'add_to_group',
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'project/admin/userperms.php';
{
  $perm_regexp = '/^(\d+|NULL)$/';
  $fields = ['privacy_289', 'admin_289'];
  $fields[] = $perm_regexp;
  $names = [
    'true' => ['update', 'onduty_user_289'],
    'preg' => [$fields],
  ];
  $in = $out = [
    'update' => true,
    'onduty_user_289' => true,
    'privacy_289' => 'NULL',
    'admin_289' => 1
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'project/memberlist-gpgkeys.php';
{
  $names = ['true' => 'download'];
  $in = $names;
  $out = ['download' => null];
  test_sane_import ($in, $names, $out);
}

$reference = 'register/upload.php';
# (no test: the only import is a file)

$reference = 'search/index.php';
{
  $names = [
    'digits' =>
      ['type', 'offset', 'max_rows', 'only_group_id', ['exact', [0, 1]]],
    'strings' => [
      [
        'type_of_search',
        ['soft', 'people', 'bugs', 'support', 'patch', 'cookbook', 'task'],
      ],
    ],
    'pass' => 'words',
  ];
  $in = $out = [
    'type' => 1, 'exact' => 0, 'offset' => 2, 'max_rows' => 3,
    'only_group_id' => 4, 'type_of_search' => 'soft', 'words' => 'w',
  ];
  test_sane_import ($in, $names, $out);
}

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

$reference = 'siteadmin/groupedit.php';
{
  $names = ['true' => 'updatefast', 'preg' => [['status', '/^[A-Z]$/']]];
  $in = $out = ['updatefast' => 'true', 'status' => 'P'];
  test_sane_import ($in, $names, $out);
  $names = [
    'true' => 'update',
    'name' => 'form_name',
    'digits' => ['group_type', 'form_public'],
    'specialchars' => ['form_license', 'form_license_other'],
    'preg' => [['form_status', '/^[A-Z]$/']]
  ];
  $dirs = ['cvs', 'arch', 'svn', 'git', 'hg', 'bzr', 'homepage', 'download'];
  foreach ($dirs as $d)
    $names['specialchars'][] = "form_dir_$d";
  $in = $out = [
    'update' => true, 'form_public' => 0, 'form_status' => 'A',
    'form_name' => 'grep', 'group_type' => 1,
  ];
  foreach ($names['specialchars'] as $n)
    $in[$n] = $out[$n] = $n;  
  test_sane_import ($in, $names, $out);
}

$reference = 'siteadmin/grouplist.php';
{
  $names = ['pass' => 'search', 'true' => 'groupsearch'];
  $in = $out = ['search' => 'search', 'groupsearch' => true];
  test_sane_import ($in, $names, $out);
  $names = [
    'digits' => ['offset', 'max_rows'],
    'name' => 'group_name_search',
    'preg' => [['status', '/^[A-Z]$/']],
  ];
  $in = $out = [
    'offset' => 0, 'max_rows' => 25, 'status' => 'P',
    'group_name_search' => 'gre'
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'siteadmin/group_type.php';
{
  function no_i18n ($x) { return $x; }

  $trackers = ['cookbook', 'bugs', 'news', 'task', 'support', 'patch'];

  $vcs_list = [
    no_i18n ("CVS") => 'cvs', no_i18n ("GNU Arch") => 'arch',
    no_i18n ("Subversion") => 'svn', no_i18n ("Git") => 'git',
    no_i18n ("Mercurial") => 'hg', no_i18n ("Bazaar") => 'bzr',
  ];

  $names = [
    'name' => 'name',
    'specialchars' => [
      'description',  'base_host', 'homepage_scm',
      'admin_email_adress', # Sic! adress not address
    ],
    'true' => []
  ];
  $hm_dw = ['download', 'homepage'];
  $vcs_extra = array_merge ($vcs_list, $hm_dw);
  foreach ($vcs_extra as $vcs)
    {
      $names['specialchars'][] = "dir_type_$vcs";
      $names['specialchars'][] = "dir_$vcs";
    }
  foreach ($hm_dw as $hd)
    $names['specialchars'][] = "url_$hd";
  foreach ($vcs_list as $vcs)
    $names['specialchars'][] = "url_${vcs}_viewcvs";
  $names['specialchars'][] = "url_cvs_virecvs_homepage";
  foreach (
    [
      'listinfo', 'subscribe', 'unsubscribe', 'archives', 'archives_private',
      'admin'
    ] as $f
  )
    $names['specialchars'][] = "url_mailing_list_$f";
  foreach (['address', 'virtual_host', 'format'] as $f)
    $names['specialchars'][] = "mailing_list_$f";
  $can_use_ = array_merge (
    $vcs_extra, $trackers,
    ['forum', 'license', 'devel_status', 'mailing_list', 'bug']
  );
  foreach ($can_use_ as $art)
    if ($art != 'bugs')
      $names['true'][] = "can_use_$art";
  $conf = array_merge (
    ['forum', 'extralink_documentation', 'mail'], $trackers, $vcs_extra
  );
  foreach ($conf as $art)
    $names['true'][] = "is_menu_configurable_$art";
  foreach ($vcs_list as $vcs)
    $names['true'][] = "is_menu_configurable_${vcs}_viewvcs";
  $names['true'][] = "is_configurable_download_dir";
  $in = $out = [];
  foreach ($names['true'] as $n)
    $in[$n] = $out[$n] = true;
  foreach ($names['specialchars'] as $n)
    $in[$n] = $out[$n] = $n;
  $in['name'] = $out['name'] = 'name';
  test_sane_import ($in, $names, $out);
}

$reference = 'siteadmin/index.php';
{
  $names = ['strings' => [['func', ['configure', 'manage', 'monitor']]]];
  $in = $out = ['func' => 'manage'];
  test_sane_import ($in, $names, $out);
}

$reference = 'siteadmin/spamlist.php';
{
  $names = [
    'digits' => ['ban_user_id', 'wash_user_id', 'max_rows', 'offset']
  ];
  $in = $out = [
    'ban_user_id' => 1, 'wash_user_id' => 2, 'max_rows' => 3, 'offset' => 4
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'siteadmin/user_changepw.php';
{
  $names = ['true' => 'update', 'pass' => ['form_pw', 'form_pw2']];
  $in = $out = ['form_pw' => 'p>"w', 'form_pw2' => '2<"wp'];
  $out['update'] = null;
  test_sane_import ($in, $names, $out);
}

$reference = 'siteadmin/usergroup.php';
{
  $names = [
    'digits' => ['user_id', 'comment_max_rows', 'comment_offset'],
    'strings' => [
      [
        'action',
        [
          'remove_user_from_group', 'update_user_group', 'update_user',
          'add_user_to_group', 'rename', 'delete'
        ],
      ],
    ],
  ];
  $in = $out = [
    'user_id' => 83521, 'comment_max_rows' => 51, 'comment_offset' => 119,
    'action' => 'delete'
  ];
  test_sane_import ($in, $names, $out);
  $names = [
    'name' => 'new_name',
    'preg' => [
      ['email', '/^[a-zA-Z\d_.+-]+@(([a-zA-Z\d-])+\.)+[a-zA-Z\d]+$/'],
      ['admin_flags', '/^[A-Z\d]+$/'],
    ],
  ];
  $in = $out = [
    'new_name' => 'new_agn', 'email' => 'test@test.org', 'admin_flags' => 'A'
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'siteadmin/userlist.php';
{
  $names = [
    'digits' => ['offset', 'user_id'],
    'specialchars' => 'text_search',
    'strings' => [
      ['action', ['delete', 'suspend', 'activate']],
    ],
    'name' => 'user_name_search',
  ];
  $in = $out = [
    'offset' => 1, 'user_id' => 289, 'text_search' => 'text',
    'action' => 'delete', 'user_name_search' => 'agn'
  ];
  test_sane_import ($in, $names, $out);
}

$reference = 'stats/index.php';
{
  $digit_names = [];
  foreach (['day', 'month', 'year'] as $term)
    foreach (['since', 'until'] as $prep)
      $digit_names[] = "${prep}_$term";
  $names = ['true' => 'update', 'digits' => $digit_names];
  $in = $out = ['update' => true];
  foreach ($digit_names as $n)
    $in[$n] = $out[$n] = 83521;
  test_sane_import ($in, $names, $out);
}
?>
