<?php
# Edit group types configuration
#
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
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


require_once('../include/init.php');
require_once('../include/vars.php');
require_directory("project");

session_require(array('group'=>'1','admin_flags'=>'A'));

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

function specific_showinput ($title, $form, $id = false)
{
  if ($id === false)
    $head = $tail = '';
  else
    {
      $head = "<label for=\"$id\">";
      $tail = '</label>';
    }
   print "\n<span class='preinput'>$head$title$tail</span><br />\n"
     . "&nbsp;&nbsp; $form<br />\n";
}
function show_checkbox ($title, $field, $row)
{
  $checkbox = form_checkbox ($field, $row[$field] == 1);
  $id = $field;
  print "\n<br />\n&nbsp;&nbsp;$checkbox\n"
    . "<span class=\"preinput\"><label for=\"$id\">$title</label></span>"
    . "<br />\n";
}

extract (sane_import ('request', ['digits' => 'type_id']));
extract (sane_import ('get', ['true' => 'create']));
extract (sane_import ('post', ['true' => ['delete', 'update']]));

$tracker_labels = [
  no_i18n("Cookbook Manager"), no_i18n("Bug Tracking"),
  no_i18n("News Manager"), no_i18n("Task Tracking"),
  no_i18n("Support Tracking"), no_i18n("Patch Tracking"),
];

$trackers = ['cookbook', 'bugs', 'news', 'task', 'support', 'patch'];

$vcs_list = [
  no_i18n ("CVS") => 'cvs', no_i18n ("GNU Arch") => 'arch',
  no_i18n ("Subversion") => 'svn', no_i18n ("Git") => 'git',
  no_i18n ("Mercurial") => 'hg', no_i18n ("Bazaar") => 'bzr',
];

# group public choice
if ($delete)
{
  $result = db_execute("DELETE FROM group_type WHERE type_id=?", array($type_id));

  if (!$result)
    { fb(no_i18n("Unable to delete group type"),0); }
  else
    { fb(no_i18n("group type deleted")); }

  site_admin_header(array('title'=>no_i18n('Group Type Management'),
                          'context'=>'admgrptype'));
  site_admin_footer(array());
  exit;
}

$name_matching = function ($trackers, $vcs_list)
{
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
  $names['specialchars'][] = "url_cvs_viewcvs_homepage";
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
    if ($art != 'bugs' && $art != 'cookbook')
      $names['true'][] = "can_use_$art";
  $conf = array_merge (
    ['forum', 'extralink_documentation', 'mail'], $trackers, $vcs_extra
  );
  foreach ($conf as $art)
    if ($art != 'cookbook' && $art != 'news')
      $names['true'][] = "is_menu_configurable_$art";
  foreach ($vcs_list as $vcs)
    $names['true'][] = "is_menu_configurable_${vcs}_viewcvs";
  $names['true'][] = "is_configurable_download_dir";
  return $names;
};

if ($update)
  {
    $names = $name_matching ($trackers, $vcs_list);
    $values = sane_import ('post', $names);
    foreach ($names['true'] as $k)
      if ($values[$k] === null)
        $values[$k] = 0;
    $result = db_autoexecute (
      'group_type', $values, DB_AUTOQUERY_UPDATE, "type_id = ?", [$type_id]
    );

    if ($result)
      fb (no_i18n ("group type general settings updated"));
    else
      fb (
        sprintf (
          # TRANSLATORS: the argument is error message.
          no_i18n("Unable to update group type settings: %s"),
          db_error()),
        1
      );

    $names = [];
    foreach ($trackers as $art)
      {
        $names[] = "${art}_user_";
        $names[] = "${art}_restrict_event1";
      }
    $names[] = '/^(\d+|NULL)$/';
    extract (sane_import ('post', ['preg' => [$names]]));
    $arg_arr = [];
    foreach ($trackers as $art)
      {
        $var = "${art}_user_";
        $arg_arr["${art}_flags"] = $$var;
        $var = "${art}_restrict_event1";
        $arg_arr["${art}_rflags"] = $$var;
      }

    $result = db_autoexecute (
      'group_type', $arg_arr , DB_AUTOQUERY_UPDATE, "type_id = ?", [$type_id]
    );
  }


if (empty ($type_id))
{
  site_admin_header(array('title'=>no_i18n('Group Type Management'),
                    'context'=>'admgrptype'));

  $result = db_query("SELECT type_id,name FROM group_type ORDER BY type_id");

  print "<br />\n";
  while ($usr = db_fetch_array($result))
    {
      $last = $usr['type_id'];
# TRANSLATORS: the first argument is type No, the second is group name.
      print '<a href="' . htmlentities ($_SERVER['PHP_SELF'])
        . "?type_id=$last\">";
      printf ('Type #%1$s: %2$s', $last, gettext($usr['name']));
      print "</a><br />\n";
    }
  # Find an appropriate unused group type ID (skip value 100).
  $type = $last + 1;
  if ($type == 100)
    $type = 101;

  print '<a href="'.htmlentities ($_SERVER['PHP_SELF']).'?type_id='.$type
        .'&amp;create=1">'.no_i18n('Create new group type').'</a>';
  site_admin_footer(array());
  exit (0);
}

$update_button_text = no_i18n("Update");
if ($create == "1")
  {
    db_execute("INSERT INTO group_type (type_id,name) VALUES (?,'New type')",
               array($type_id));
    $update_button_text = no_i18n("Create");
  }

$result = db_execute("SELECT * FROM group_type WHERE type_id=?", array($type_id));
$row_grp = db_fetch_array($result);

site_admin_header(array('title'=>no_i18n("Edition/Creation of Group Type"),
                  'context'=>'admgrptype'));


print "<h1>{$row_grp['name']} (#{$row_grp['type_id']})</h1>\n";

print '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
  . "\" method='post'>\n"
  . "<input type='hidden' name='type_id' value=\"$type_id\" />\n";

print '<h2>' . no_i18n("General Default Settings for Groups of this Type")
  . "</h2>\n";
$textfield_size = '65';
print '<p>'
  . no_i18n (
      'Basic Help: host means hostname (like savannah.gnu.org), '
      . 'dir means directory (like /var/www/savane).'
    )
  . "</p>\n<p class='warn'>"
  . no_i18n (
      'Everytime a project\'s unix_group_name should appear, use the '
      . 'special string %PROJECT.')
  . "</p>\n<p>"
  . no_i18n(
      'Fields marked with [BACKEND SPECIFIC] are only useful is you use '
      . 'the savannah backend.')
  ."</p>\n<p>"
  . no_i18n (
      'Fill only the fields that have a specific setting, differing '
      . 'from the whole installation settings.')
  . "</p>\n";

print $HTML->box_top(no_i18n("General Settings"));

specific_showinput (
  no_i18n ("Name:"),
  "<input type='text' name='name' id='name' value=\"{$row_grp['name']}\" "
  . "size='$textfield_size\" />",
  "name"
);
specific_showinput (
  no_i18n ("Base Host:"),
  '<input type="text" id="base_host" name="base_host" '
  . "value=\"{$row_grp['base_host']}\" size=\"$textfield_size\" />",
  'base_host'
);
specific_showinput (
  no_i18n ("Description (will be added on each project main page):"),
  "<textarea cols=\"$textfield_size\" rows='6' wrap='virtual' "
  . "name='description' id='description'>{$row_grp['description']}</textarea>",
  'description'
);
specific_showinput (
  no_i18n ("Admin Email Address:"),
  '<input type="text" name="admin_email_adress"'
  . "id='admin_email_adress' value=\"{$row_grp['admin_email_adress']}\""
  . " size=\"$textfield_size\" />",
  'admin_email_adress'
);

print $HTML->box_bottom();
print "<br /><br />\n";

# FIXME: the following more or less assuming that WWW homepage will be
# managed using CVS.
# For instance, there will be no viewcvs possibility for Arch managed
# repository. But this is non-blocker so we let as it is.

print $HTML->box_top(no_i18n("Project WWW Homepage"));
print '<p>'
  . no_i18n('This is useful if you provide directly web homepages (created by
the backend) or if you want to allow projects to configure the related menu
entry (see below). The SCM selection will only affect the content shown by the
frontend related to the homepage management.') . "</p>\n";

show_checkbox (no_i18n ("Can use homepage"), 'can_use_homepage', $row_grp);

$sel_val = null;
$selection = $row_grp['homepage_scm'];
foreach ($vcs_list as $title => $name)
  if ($name === $selection)
    {
      $sel_val = $title;
      break;
    }
$vals = array_keys ($vcs_list);
$select_box =
   html_build_select_box_from_array ($vals, no_i18n ("VCS"), $sel_val);
print "<br />\n";
specific_showinput (no_i18n("Selected SCM:"), $select_box);

html_select_typedir_box("dir_type_homepage", $row_grp['dir_type_homepage']);
specific_showinput (
  no_i18n ("Homepage Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
 '<input type="text" name="dir_homepage" id="dir_homepage" '
 . "value=\"{$row_grp['dir_homepage']}\" size=\"$textfield_size\" />",
 'dir_homepage'
);
specific_showinput (
  no_i18n("Homepage URL:"),
  '<input type="text" name="url_homepage" id="url_homepage" '
  . "value=\"{$row_grp['url_homepage']}\" size=\"{$textfield_size}\" />",
  'url_homepage'
);
$field = 'url_cvs_viewcvs_homepage';
specific_showinput (
  no_i18n("Homepage CVS view URL (webcvs, viewcvs):"),
  "<input type='text' name='$field' id='$field' value=\""
  . "{$row_grp[$field]}\" size='$textfield_size' />",
  $field
);

print $HTML->box_bottom();
print "<br /><br />\n";

function source_code_manager ($HTML, $row_grp, $textfield_size,
                              $vcs_name, $vcs_offix)
{
# TRANSLATORS: the argument is VCS name (like Subversion).
  print $HTML->box_top(sprintf(no_i18n("Source Code Manager: %s"), $vcs_name));
  print '<p>';
# TRANSLATORS: the argument is VCS name (like Subversion).
printf (no_i18n('This is useful if you provide directly %s repositories (created by the
backend) or if you want to allow projects to configure the related menu entry
(see below).'), $vcs_name);
  print '</p>
';
  show_checkbox (
    # TRANSLATORS: the argument is VCS name (like Subversion).
    sprintf (no_i18n("Can use %s"), $vcs_name),
    "can_use_$vcs_offix", $row_grp
  );
  html_select_typedir_box("dir_type_".$vcs_offix,
			  $row_grp['dir_type_'.$vcs_offix]);
  specific_showinput(
no_i18n("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
 '<input type="text" name="dir_'.$vcs_offix.'" id="dir_'.$vcs_offix.'" value="'
 .$row_grp['dir_'.$vcs_offix].'" size="' .$textfield_size.'" />',
  "dir_".$vcs_offix);
  specific_showinput(
no_i18n("Repository view URL (cvsweb, viewcvs, archzoom...):"),
 '<input type="text" name="url_'.$vcs_offix.'_viewcvs"
   id="url_'.$vcs_offix.'_viewcvs" value="'
 .$row_grp['url_'.$vcs_offix.'_viewcvs']
 .'" size="'.$textfield_size.'" />', "url_".$vcs_offix."_viewcvs");

  print $HTML->box_bottom();
  print "<br /><br />\n";
}

foreach ($vcs_list as $title => $name)
  source_code_manager ($HTML, $row_grp, $textfield_size, $title, $name);

print $HTML->box_top(no_i18n("Download Area"));
print '<p>'.no_i18n('This is useful if you provide directly download areas
(created by the backend) or if you want to allow projects to configure the
related menu entry (see below).').'</p>
';
  show_checkbox (
    no_i18n("Can use Download Area"), "can_use_download", $row_grp
  );
  html_select_typedir_box("dir_type_download",
			  $row_grp['dir_type_download']);
  specific_showinput(
no_i18n("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
  '<input type="text" name="dir_download" id="dir_download"
   value="'.$row_grp['dir_download']
  .'" size="'.$textfield_size.'" />', "dir_download");
  specific_showinput(no_i18n("Repository URL:"),
 '<input type="text" name="url_download" id="url_download"
       value="'.$row_grp['url_download']
 .'" size="'.$textfield_size.'" />', 'url_download');

print $HTML->box_bottom();
print "<br /><br />\n";

# License
print $HTML->box_top(no_i18n("Licenses"));
print '<p>'.no_i18n('This is useful if you want project to select a license on
submission. Edit site-specific-content/hashes.txt to define the list of
accepted licenses.').'</p>';
show_checkbox (no_i18n("Can use licenses"), 'can_use_license', $row_grp);

print $HTML->box_bottom();
print "<br /><br />\n";

print $HTML->box_top(no_i18n("Development Status"));
print '<p>'.no_i18n('This is useful if you want project to be able to defines
their development status that will be shown on their main page. This is purely
a matter of cosmetics. This option is mainly here just to remove this content
in case it is useless (it does not makes sense for organizational projects).
Edit site-specific-content/hashes.txt to define the list of possible
development status.').'</p>';
  show_checkbox (
    no_i18n("Can use development status"), "can_use_devel_status", $row_grp
  );

print $HTML->box_bottom();
print "<br /><br />\n";

print $HTML->box_top(no_i18n("Mailing List"));
print '<p class="warn">'
  . no_i18n ('Important: Everytime a mailing list name should appear,
use the special string %LIST.') . "</p>\n<p>"
  . no_i18n ('Do not configure Mailing list Host, this is a deprecated
feature left for backward compatibility.') . "</p>\n<p>"
  . no_i18n ('Mailing list virtual host only need to be set if you use mailman
list, set up via the backend, and have several mailman virtual hosts
set.') . "</p>\n";

show_checkbox (
  no_i18n("Can use mailing lists"), "can_use_mailing_list", $row_grp
);

print "<br /><br />\n";

specific_showinput(no_i18n("Mailing list Host (DEPRECATED):"),
 '<input type="text" name="mailing_list_host" id="mailing_list_host" value="'
 .$row_grp['mailing_list_host'].'" size="'.$textfield_size.'" />',
 'mailing_list_host');
specific_showinput (
  no_i18n ("Mailing list address (would be %LIST@gnu.org for GNU projects
at sv.gnu.org):"),
 '<input type="text" name="mailing_list_address" id="mailing_list_address" value="'
 .$row_grp['mailing_list_address'].'" size="'.$textfield_size.'" />',
  'mailing_list_address');
  specific_showinput(
no_i18n("Mailing list virtual host (would be lists.gnu.org or lists.nongnu.org at
sv.gnu.org) [BACKEND SPECIFIC]:"),
 '<input type="text" name="mailing_list_virtual_host"
       id="mailing_list_virtual_host" value="'
 .$row_grp['mailing_list_virtual_host'].'" size="'.$textfield_size.'">',
   "mailing_list_virtual_host");

print "<br /><br />\n";
print '<p>'.no_i18n('With the following, you can force projects to follow a specific
policy for the name of the %LIST. Here you should use the special wildcard
%NAME, which is the part the of the mailing list name that the project admin
can define (would be %PROJECT-%NAME for non-GNU projects at sv.gnu.org).').'</p>
<p class="warn">'.no_i18n('Do no add any @hostname here!').'</p>';
specific_showinput(no_i18n("Mailing list name format:"),
  '<input type="text" name="mailing_list_format" id="mailing_list_format" value="'
  .$row_grp['mailing_list_format'].'" size="'.$textfield_size.'" />',
  "mailing_list_format");
specific_showinput(no_i18n("Listinfo URL:"),
  '<input type="text" name="url_mailing_list_listinfo"
  id="url_mailing_list_listinfo" value="'
  .$row_grp['url_mailing_list_listinfo'].'" size="'.$textfield_size.'" />',
  "url_mailing_list_listinfo");
$cern_fmt =
  "majordomo_interface.php?func=%s&amp;list=%%LIST&amp;"
  . "mailserver=listbox.server@cern.ch):";
$cern_url = sprintf ($cern_fmt, 'subscribe');
specific_showinput (
  sprintf (
    no_i18n ("Subscribe URL (for majordomo at CERN, it is %s"),
    $cern_url
  ),
  '<input type="text" name="url_mailing_list_subscribe"
        id="url_mailing_list_subscribe" value="'
 .$row_grp['url_mailing_list_subscribe'].'" size="'
 .$textfield_size.'" />', "url_mailing_list_subscribe"
);
$cern_url = sprintf ($cern_fmt, 'unsubscribe');
specific_showinput (
  sprintf (
    no_i18n ("Unsubscribe URL (for majordomo at CERN, it is %s"),
    $cern_url
  ),
  '<input type="text" name="url_mailing_list_unsubscribe"
     id="url_mailing_list_unsubscribe" value="'
  .$row_grp['url_mailing_list_unsubscribe'].'" size="'.$textfield_size.'" />',
  "url_mailing_list_unsubscribe"
);
$field = "url_mailing_list_archives";
specific_showinput (
  no_i18n("Archives URL:"),
  "<input type='text' name='$field' id='$field' value=\""
  . "{$row_grp[$field]}\" size=\"$textfield_size\" />",
  $field
);
$field = "url_mailing_list_archives_private";
specific_showinput(no_i18n("Private Archives URL:"),
  "<input type='text' name='$field' id='$field' value=\""
 . "{$row_grp[$field]}\" size=\"$textfield_size\" />",
 $field
);
$field = "url_mailing_list_admin";
specific_showinput(no_i18n("Administrative Interface URL:"),
  "<input type='text' name='$field' id='$field' value=\""
  . "{$row_grp[$field]}\" size=\"$textfield_size\" />",
  $field
);

print $HTML->box_bottom();
print "<br /><br />\n";

function artifact_checkbox($HTML, $title, $description, $label, $artifact)
{
  global $row_grp;

  print $HTML->box_top($title);
  if ($description != '')
    print '<p>'.$description."</p>\n";
  show_checkbox ($label, "can_use_$artifact", $row_grp);

  print $HTML->box_bottom();
  print "<br /><br />\n";
}

artifact_checkbox($HTML, no_i18n("Forum"),
no_i18n('Forum is a deprecated feature of Savane. We do not recommend using
it and we do not maintain this code any longer.'),
                    no_i18n("Can use forum"), 'forum');

artifact_checkbox($HTML, no_i18n("Support Request Manager"),
no_i18n('This is one of the main issue tracker of Savane. Project are
supposed to use it as primary interface with end user.'),
                    no_i18n("Can use support request tracker"), 'support');

artifact_checkbox($HTML, no_i18n("Bug Tracker"),
no_i18n('This is one of the main issue tracker of Savane. Unlike the
support tracker, it is supposed to be used mainly to organize the workflow
amongs project members related to bugs. Projects with large audience should
probably not accept item posting by people that are not member of the project
on this tracker, and instead redirect end user to the support tracker (and only
real bugs would be reassigned to this tracker). But that\'s only a
suggestion.'), no_i18n("Can use bug tracker"), 'bug');

artifact_checkbox($HTML, no_i18n("Task Manager"),
no_i18n('This is one of the main issue tracker of Savane. Unlike the
support tracker, it is supposed to be used mainly to organize the workflow
amongs project members related to planned tasks. It\'s the counterpart of the
bug tracker for regular and planned activities.'),
                    no_i18n("Can use task manager"), 'task');

artifact_checkbox($HTML, no_i18n("Patch Manager"),
no_i18n('This is a deprecated issue tracker. It was originally designed
to get all the submitted patches; but it seems to us more sensible that patch
get attached to the relevant item (task, bug...). We may deleted this tracker
in the future or "anonymize it". The later option would mean that this tracker
would no longer be name the patch tracker but the name would be up to you, as
site administrator. This would be an additionnal tracker, with no purpose
defined out of the box.'), no_i18n("Can use patch manager (deprecated)"),
                   'patch');

artifact_checkbox ($HTML, no_i18n("News Manager"), '',
                     no_i18n("Can use news manager"), 'news');

$update_delete_buttons =
  "\n<p align='center'>\n"
  . form_submit ($update_button_text) . "&nbsp;\n"
  . form_submit (no_i18n("Delete this Group Type"), 'delete') . "\n</p>\n";
print $update_delete_buttons;

print $HTML->box_top(no_i18n("Project Menu Settings"),'',1);
$i = 1;
print '<p class="' . utils_get_alt_row_color ($i) . '">'
.no_i18n('This form allows you to choose which menu entries are configurable by the
projects administrators.').'</p>';
function specific_checkbox ($val, $explanation, $row_grp, $class)
  {
    # Just a little function to clean that part of the code, no
    # interest to generalize it.
    $field = "is_menu_configurable_$val";
    print "<li class=\"$class\">" . form_checkbox ($field, $row_grp[$field]);
    print "<span class='preinput'><label for=\"$field\">"
      . "$explanation</label></span></li>\n";
  }

$row_grp["is_menu_configurable_download_dir"] =
  $row_grp["is_configurable_download_dir"];
$checkboxes = [
  "homepage" => no_i18n("the homepage link can be modified"),
  "extralink_documentation" =>
     no_i18n("the documentation &ldquo;extra&rdquo; link can be modified"),

  "download" => no_i18n("the download area link can be modified"),
  "download_dir" =>
     [no_i18n("the download _directory_ can be modified -- beware, if
the backend is running and creating download dir, it can be used maliciously.
don't activate this feature unless you truly know what you're doing")],

  "support" => no_i18n("the support link can be modified"),
  "bugs" => no_i18n("the bug tracker link can be modified"),
  "task" => no_i18n("the task tracker link can be modified"),
  "patch" => no_i18n("the patch tracker link can be modified"),
  "forum" => no_i18n("the forum link can be modified"),
  "mail" => no_i18n("the mailing list link can be modified"),

  "cvs" => no_i18n("the cvs link can be modified"),
  "cvs_viewcvs" => [no_i18n("the viewcvs link can be modified")],
  "cvs_viewcvs_homepage" =>
     [no_i18n("the viewcvs link for homepage code can be modified")],

  "arch" => no_i18n("the GNU Arch link can be modified"),
  "arch_viewcvs" => [no_i18n("the GNU Arch viewcvs link can be modified")],

  "svn" => no_i18n("the Subversion link can be modified"),
  "svn_viewcvs" => [no_i18n("the Subversion viewcvs link can be modified")],

  "git" => no_i18n("the Git link can be modified"),
  "git_viewcvs" => [no_i18n("the Git viewcvs link can be modified")],

  "hg" => no_i18n("the Mercurial link can be modified"),
  "hg_viewcvs" => [no_i18n("the Mercurial viewcvs link can be modified")],

  "bzr" => no_i18n("the Bazaar link can be modified"),
  "bzr_viewcvs" => [no_i18n("the Bazaar viewcvs link can be modified")],
];
foreach ($checkboxes as $k => $v)
  {
    if (is_array ($v))
      $v = $v[0];
    else
      $i++;
    specific_checkbox ($k, $v, $row_grp, utils_get_alt_row_color ($i));
  }

print $HTML->box_bottom(1);
print $update_delete_buttons . "<br /><br />\n";

$HTML->box1_top(no_i18n("Project Default Member Permissions"));

print '<p>'.no_i18n("This form allows you to define the default permissions for
users added to a group of this type, unless this group defined its own
configuration.").'</p>';

$list_head = html_build_list_table_top ($tracker_labels) . "<tr>\n";
print $list_head;
foreach ($trackers as $art)
  html_select_permission_box ($art, $row_grp["${art}_flags"], "type");

print "</tr>\n</table>\n";

$HTML->box1_bottom();
print $update_delete_buttons . "<br /><br />\n";

$HTML->box1_top(no_i18n("Project Default Posting Restrictions"));

print '<p>'.no_i18n("This form allows you to define the default posting restriction
on this group trackers.").'</p>';

print $list_head;
foreach ($trackers as $art)
  html_select_restriction_box ($art, $row_grp["${art}_rflags"], "type");
print "</tr>\n</table>\n";

$HTML->box1_bottom();

print $update_delete_buttons;
print "</form>\n";
site_admin_footer(array());
?>
