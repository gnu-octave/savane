<?php
# Edit group types configuration
#
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
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
   return '
<span class="preinput">'.$title.'</span><br />
&nbsp;&nbsp; '.$form.'<br />
';
  return '
<span class="preinput"><label for="'.$id.'">'.$title.'</label></span><br />
&nbsp;&nbsp; '.$form.'<br />
';
}
function specific_showinput_inverted ($title, $form, $id = false)
{
  if ($id === false)
    return '
<br />
&nbsp;&nbsp;'.$form.'
<span class="preinput">'.$title.'</span><br />
';
    return '
<br />
&nbsp;&nbsp;'.$form.'
<span class="preinput"><label for="'.$id.'">'
.$title.'</label></span><br />
';
}


extract(sane_import('request', array('type_id')));
extract(sane_import('get', array('create')));
extract(sane_import('post', array('delete', 'update')));

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

if ($update) {
  // Name-matching params
  $fields = array(
    // General
    'name', 'description', 'base_host', 'can_use_homepage',
    'dir_type_cvs', 'dir_type_svn', 'dir_type_arch', 'dir_type_git',
    'dir_type_hg', 'dir_type_bzr',
    'dir_type_download', 'dir_type_homepage',
    'dir_cvs', 'dir_arch', 'dir_svn', 'dir_git', 'dir_hg','dir_bzr',
    'homepage_scm', 'dir_homepage', 'url_homepage',
    'url_cvs_viewcvs_homepage', 'can_use_arch', 'can_use_svn',
    'can_use_cvs', 'can_use_git', 'can_use_hg', 'can_use_bzr',
    'can_use_forum',
    'url_cvs_viewcvs', 'url_arch_viewcvs', 'url_svn_viewcvs',
    'url_git_viewcvs', 'url_hg_viewcvs', 'url_bzr_viewcvs',
    'can_use_license', 'can_use_devel_status', 'can_use_download',
    'dir_download', 'url_download',
    'can_use_mailing_list', 'mailing_list_host',
    'url_mailing_list_listinfo', 'url_mailing_list_subscribe',
    'url_mailing_list_unsubscribe', 'url_mailing_list_archives',
    'url_mailing_list_archives_private', 'url_mailing_list_admin',
    'mailing_list_address', 'mailing_list_virtual_host',
    'mailing_list_format', 'can_use_patch', 'can_use_task',
    'can_use_news', 'can_use_bug', 'admin_email_adress',

    // Menu
    'is_menu_configurable_homepage',
    'is_menu_configurable_extralink_documentation',
    'is_menu_configurable_download',
    'is_menu_configurable_support',
    'is_menu_configurable_forum',
    'is_menu_configurable_mail',
    'is_menu_configurable_cvs',
    'is_menu_configurable_cvs_viewcvs',
    'is_menu_configurable_cvs_viewcvs_homepage',
    'is_menu_configurable_arch',
    'is_menu_configurable_arch_viewcvs',
    'is_menu_configurable_svn',
    'is_menu_configurable_svn_viewcvs',
    'is_menu_configurable_git',
    'is_menu_configurable_git_viewcvs',
    'is_menu_configurable_hg',
    'is_menu_configurable_hg_viewcvs',
    'is_menu_configurable_bzr',
    'is_menu_configurable_bzr_viewcvs',
    'is_menu_configurable_bugs',
    'is_menu_configurable_task',
    'is_menu_configurable_patch',
    'is_configurable_download_dir',
  );
  $values = sane_import('post', $fields);

  $result = db_autoexecute('group_type',
    $values, DB_AUTOQUERY_UPDATE,
    "type_id=?", array($type_id));

# TRANSLATORS: the argument is error message.
  if (!$result)
    { fb(sprintf(no_i18n("Unable to update group type settings: %s"),
                   db_error()), 1); }
  else
    { fb(no_i18n("group type general settings updated")); }


  // Not-name-maching params
  extract(sane_import('post',
    array('cookbook_user_','bugs_user_',
	  'news_user_','task_user_',
	  'patch_user_','support_user_',

          'bugs_restrict_event1','cookbook_restrict_event1',
	  'news_restrict_event1','task_restrict_event1',
	  'patch_restrict_event1','support_restrict_event1')));

  $result = db_autoexecute('group_type',
    array(
    # User permissions
    'cookbook_flags' => $cookbook_user_,
    'bugs_flags' => $bugs_user_,
    'news_flags' => $news_user_,
    'task_flags' => $task_user_,
    'patch_flags' => $patch_user_,
    'support_flags' => $support_user_,

    # Posting restrictions
    'bugs_rflags' => $bugs_restrict_event1,
    'cookbook_rflags' => $cookbook_restrict_event1,
    'news_rflags' => $news_restrict_event1,
    'task_rflags' => $task_restrict_event1,
    'patch_rflags' => $patch_restrict_event1,
    'support_rflags' => $support_restrict_event1,
    ), DB_AUTOQUERY_UPDATE,
    "type_id=?", array($type_id));
}


if (!isset($type_id))
{
  site_admin_header(array('title'=>no_i18n('Group Type Management'),
                    'context'=>'admgrptype'));

  $result = db_query("SELECT type_id,name FROM group_type ORDER BY type_id");

  print '<br />
';
  while ($usr = db_fetch_array($result))
    {
# TRANSLATORS: the first argument is type No, the second is group name.
      print '<a href="'.htmlentities ($_SERVER['PHP_SELF'])
            .'?type_id='.$usr['type_id'].'">'
            .sprintf('Type #%1$s: %2$s', $usr['type_id'], gettext($usr['name']))
            .'</a><br />
';
      $last=$usr['type_id'];
    }
  # Find an appropriate unused group type ID (skip value 100)
  $type=$last+1;
  if ($type == 100)
    { $type = 101; }

  print '<a href="'.htmlentities ($_SERVER['PHP_SELF']).'?type_id='.$type
        .'&amp;create=1">'.no_i18n('Create new group type').'</a>';

}
else
{

  if ($create == "1")
    {
      db_execute("INSERT INTO group_type (type_id,name) VALUES (?,'New type')",
                 array($type_id));
      $update_button_text = no_i18n("Create");
    }
  else
    {
      $update_button_text = no_i18n("Update");
    }

  $result = db_execute("SELECT * FROM group_type WHERE type_id=?", array($type_id));
  $row_grp = db_fetch_array($result);

  site_admin_header(array('title'=>no_i18n("Edition/Creation of Group Type"),
                    'context'=>'admgrptype'));


  print '<h1>'.$row_grp['name'].' (#'.$row_grp['type_id'].')</h1>';


  print '<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
<input type="hidden" name="type_id" value="'.$type_id.'" />';

  # General settings

  print '<h2>'.no_i18n("General Default Settings for Groups of this Type").'</h2>
';

  $textfield_size='65';

  # Help

  print '
<p>'.no_i18n('Basic Help: host means hostname (as savannah.gnu.org), dir means directory
(as /var/www/savane).').'</p>

<p class="warn">'
.no_i18n('Everytime a project\'s unix_group_name should appear, use the special
string %PROJECT.').'</p>

<p>'
.no_i18n('Fields marked with [BACKEND SPECIFIC] are only useful is you use the
savannah backend.').'</p>

<p>'.no_i18n('Fill only the fields that have a specific setting, differing from the
whole installation settings.').'</p>
';

  print $HTML->box_top(no_i18n("General Settings"));

  print specific_showinput(no_i18n("Name:"),
'<input type="text" name="name" id="name" value="'
.$row_grp['name'].'" size="'.$textfield_size.'" />', "name");
  print specific_showinput(no_i18n("Base Host:"),
'<input type="text" id="base_host" name="base_host" value="'
                           .$row_grp['base_host'].'" size="'.$textfield_size.'" />',
                           'base_host');
  print specific_showinput(
no_i18n("Description (will be added on each project main page):"),
                           '<textarea cols="'.$textfield_size
                           .'" rows="6" wrap="virtual" name="description" id="description">'
                           .$row_grp['description'].'</textarea>', 'description');
  print specific_showinput(no_i18n("Admin Email Address:"),
                           '<input type="text" name="admin_email_adress"
id="admin_email_adress" value="'
                           .$row_grp['admin_email_adress'].'" size="'
                           .$textfield_size.'" />', 'admin_email_adress');

  print $HTML->box_bottom();
  print '<br /><br />
';

  # Homepage

  # FIXME: the following more or less assuming that WWW homepage will be
  # managed using CVS.
  # For instance, there will be no viewcvs possibility for Arch managed
  # repository. But this is non-blocker so we let as it is.

  print $HTML->box_top(no_i18n("Project WWW Homepage"));
  print '<p>'.no_i18n('This is useful if you provide directly web homepages (created by
the backend) or if you want to allow projects to configure the related menu
entry (see below). The SCM selection will only affect the content shown by the
frontend related to the homepage management.').'</p>
';
  print specific_showinput_inverted(no_i18n("Can use homepage"),
                                    '<input type="checkbox" name="can_use_homepage" '
                                    .'id="can_use_homepage" '
                                    .'value="1"'.(($row_grp['can_use_homepage']==1) ?
                                                    ' checked="checked"' : '').' />',
                                    'can_use_homepage');

  print '<br />
'.specific_showinput(no_i18n("Selected SCM:"), '<select title="VCS" name="homepage_scm">
  <option value="cvs"'.(($row_grp['homepage_scm'] == "cvs")?"
                         selected=\"selected\"":"").'>'.no_i18n("CVS").'</option>
  <option value="arch"'.(($row_grp['homepage_scm'] == "arch")?"
                         selected=\"selected\"":"").'>'.no_i18n("GNU Arch").'</option>
  <option value="svn"'.(($row_grp['homepage_scm'] == "svn")?"
                         selected=\"selected\"":"").'>'.no_i18n("Subversion").'</option>
  <option value="git"'.(($row_grp['homepage_scm'] == "git")?"
                         selected=\"selected\"":"").'>'.no_i18n("Git").'</option>
  <option value="hg"'.(($row_grp['homepage_scm'] == "hg")?"
                         selected=\"selected\"":"").'>'.no_i18n("Mercurial").'</option>
  <option value="bzr"'.(($row_grp['homepage_scm'] == "bzr")?"
                         selected=\"selected\"":"").'>'.no_i18n("Bazaar").'</option>
</select>
');

  html_select_typedir_box("dir_type_homepage",
			  $row_grp['dir_type_homepage']);
  print specific_showinput(
no_i18n("Homepage Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
 '<input type="text" name="dir_homepage" id="dir_homepage" value="'.$row_grp['dir_homepage']
 .'" size="'.$textfield_size.'" />', 'dir_homepage');
  print specific_showinput(no_i18n("Homepage URL:"),
 '<input type="text" name="url_homepage" id="url_homepage" value="'.$row_grp['url_homepage']
 .'" size="'.$textfield_size.'" />', 'url_homepage');
  print specific_showinput(no_i18n("Homepage CVS view URL (webcvs, viewcvs):"),
 '<input type="text" name="url_cvs_viewcvs_homepage" id="url_cvs_viewcvs_homepage" value="'
 .$row_grp['url_cvs_viewcvs_homepage'].'" size="'.$textfield_size.'" />',
         'url_cvs_viewcvs_homepage');

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
# TRANSLATORS: the argument is VCS name (like Subversion).
  print specific_showinput_inverted(sprintf (no_i18n("Can use %s"), $vcs_name),
 '<input type="checkbox" name="can_use_'.$vcs_offix.'" id="can_use_'.$vcs_offix
 .'" value="1"'
 .(($row_grp['can_use_'.$vcs_offix]==1) ? ' checked="checked"' : '').' />',
  "can_use_".$vcs_offix);
  html_select_typedir_box("dir_type_".$vcs_offix,
			  $row_grp['dir_type_'.$vcs_offix]);
  print specific_showinput(
no_i18n("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
 '<input type="text" name="dir_'.$vcs_offix.'" id="dir_'.$vcs_offix.'" value="'
 .$row_grp['dir_'.$vcs_offix].'" size="' .$textfield_size.'" />',
  "dir_".$vcs_offix);
  print specific_showinput(
no_i18n("Repository view URL (cvsweb, viewcvs, archzoom...):"),
 '<input type="text" name="url_'.$vcs_offix.'_viewcvs"
   id="url_'.$vcs_offix.'_viewcvs" value="'
 .$row_grp['url_'.$vcs_offix.'_viewcvs']
 .'" size="'.$textfield_size.'" />', "url_".$vcs_offix."_viewcvs");

  print $HTML->box_bottom();
  print "<br /><br />\n";
}

source_code_manager ($HTML, $row_grp, $textfield_size, no_i18n("CVS"), 'cvs');
source_code_manager ($HTML, $row_grp, $textfield_size, no_i18n("GNU Arch"), 'arch');
source_code_manager ($HTML, $row_grp, $textfield_size, no_i18n("Subversion"), 'svn');
source_code_manager ($HTML, $row_grp, $textfield_size, no_i18n("Git"), 'git');
source_code_manager ($HTML, $row_grp, $textfield_size, no_i18n("Mercurial"), 'hg');
source_code_manager ($HTML, $row_grp, $textfield_size, no_i18n("Bazaar"), 'bzr');

  # Download

  print $HTML->box_top(no_i18n("Download Area"));
  print '<p>'.no_i18n('This is useful if you provide directly download areas
(created by the backend) or if you want to allow projects to configure the
related menu entry (see below).').'</p>
';
  print specific_showinput_inverted(no_i18n("Can use Download Area"),
 '<input type="checkbox" name="can_use_download" id="can_use_download" value="1"'
 .(($row_grp['can_use_download']==1) ? ' checked="checked"' : '').' />',
  "can_use_download");
  html_select_typedir_box("dir_type_download",
			  $row_grp['dir_type_download']);
  print specific_showinput(
no_i18n("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
  '<input type="text" name="dir_download" id="dir_download"
   value="'.$row_grp['dir_download']
  .'" size="'.$textfield_size.'" />', "dir_download");
  print specific_showinput(no_i18n("Repository URL:"),
 '<input type="text" name="url_download" id="url_download"
       value="'.$row_grp['url_download']
 .'" size="'.$textfield_size.'" />', 'url_download');

  print $HTML->box_bottom();
  print '<br /><br />
';

  # License
  print $HTML->box_top(no_i18n("Licenses"));
  print '<p>'.no_i18n('This is useful if you want project to select a license on
submission. Edit site-specific-content/hashes.txt to define the list of
accepted licenses.').'</p>';
  print specific_showinput_inverted(no_i18n("Can use licenses"),
 '<input type="checkbox" name="can_use_license" id="can_use_license" value="1"'
 .(($row_grp['can_use_license']==1) ? ' checked="checked"' : '').' />',
  'can_use_license');

  print $HTML->box_bottom();
  print '<br /><br />
';

  # Devel status

  print $HTML->box_top(no_i18n("Development Status"));
  print '<p>'.no_i18n('This is useful if you want project to be able to defines
their development status that will be shown on their main page. This is purely
a matter of cosmetics. This option is mainly here just to remove this content
in case it is useless (it does not makes sense for organizational projects).
Edit site-specific-content/hashes.txt to define the list of possible
development status.').'</p>';
  print specific_showinput_inverted(no_i18n("Can use development status"),
  '<input type="checkbox" name="can_use_devel_status" id="can_use_devel_status"
          value="1"'
  .(($row_grp['can_use_devel_status']==1) ? ' checked="checked"' : '').' />',
  "can_use_devel_status");

  print $HTML->box_bottom();
  print '<br /><br />
';

   # Mailing lists

  print $HTML->box_top(no_i18n("Mailing List"));
  print '<p class="warn">'
.no_i18n('Important: Everytime a mailing list name should appear, use the special
string %LIST.').'</p>
<p>'.no_i18n('Do not configure Mailing list Host, this is a deprecated feature left
for backward compatibility.').'</p>
<p>'.no_i18n('Mailing list virtual host only need to be set if you use mailman
list, set up via the backend, and have several mailman virtual hosts
set.').'</p>
';

  print specific_showinput_inverted(no_i18n("Can use mailing lists"),
 '<input type="checkbox" name="can_use_mailing_list" id="can_use_mailing_list"
         value="1"'
 .( ($row_grp['can_use_mailing_list']==1) ? ' checked="checked"' : '' ).' />',
 "can_use_mailing_list");

  print '<br /><br />
';

  print specific_showinput(no_i18n("Mailing list Host (DEPRECATED):"),
 '<input type="text" name="mailing_list_host" id="mailing_list_host" value="'
 .$row_grp['mailing_list_host'].'" size="'.$textfield_size.'" />',
 'mailing_list_host');
  print specific_showinput(
no_i18n("Mailing list address (would be %LIST@gnu.org for GNU projects at sv.gnu.org):"),
 '<input type="text" name="mailing_list_address" id="mailing_list_address" value="'
 .$row_grp['mailing_list_address'].'" size="'.$textfield_size.'" />',
  'mailing_list_address');
  print specific_showinput(
no_i18n("Mailing list virtual host (would be lists.gnu.org or lists.nongnu.org at
sv.gnu.org) [BACKEND SPECIFIC]:"),
 '<input type="text" name="mailing_list_virtual_host"
       id="mailing_list_virtual_host" value="'
 .$row_grp['mailing_list_virtual_host'].'" size="'.$textfield_size.'">',
   "mailing_list_virtual_host");

  print '<br /><br />';
  print '<p>'.no_i18n('With the following, you can force projects to follow a specific
policy for the name of the %LIST. Here you should use the special wildcard
%NAME, which is the part the of the mailing list name that the project admin
can define (would be %PROJECT-%NAME for non-GNU projects at sv.gnu.org).').'</p>
<p class="warn">'.no_i18n('Do no add any @hostname here!').'</p>';
  print specific_showinput(no_i18n("Mailing list name format:"),
   '<input type="text" name="mailing_list_format" id="mailing_list_format" value="'
   .$row_grp['mailing_list_format'].'" size="'.$textfield_size.'" />',
       "mailing_list_format");
  print specific_showinput(no_i18n("Listinfo URL:"),
   '<input type="text" name="url_mailing_list_listinfo"
           id="url_mailing_list_listinfo" value="'
   .$row_grp['url_mailing_list_listinfo'].'" size="'.$textfield_size.'" />',
         "url_mailing_list_listinfo");
  print specific_showinput(
sprintf (no_i18n("Subscribe URL (for majordomo at CERN, it is %s"),
"majordomo_interface.php?func=subscribe&amp;list=%LIST&amp;mailserver=listbox.server@cern.ch):"),
'<input type="text" name="url_mailing_list_subscribe"
        id="url_mailing_list_subscribe" value="'
 .$row_grp['url_mailing_list_subscribe'].'" size="'
 .$textfield_size.'" />', "url_mailing_list_subscribe");
  print specific_showinput(
sprintf(no_i18n("Unsubscribe URL (for majordomo at CERN, it is %s"),
"majordomo_interface.php?func=unsubscribe&amp;list=%LIST&amp;mailserver=listbox.server@cern.ch):"),
 '<input type="text" name="url_mailing_list_unsubscribe"
     id="url_mailing_list_unsubscribe" value="'
 .$row_grp['url_mailing_list_unsubscribe'].'" size="'.$textfield_size.'" />',
     "url_mailing_list_unsubscribe");
  print specific_showinput(no_i18n("Archives URL:"),
  '<input type="text" name="url_mailing_list_archives"
         id="url_mailing_list_archives" value="'
  .$row_grp['url_mailing_list_archives'].'" size="'.$textfield_size.'" />',
         "url_mailing_list_archives");
  print specific_showinput(no_i18n("Private Archives URL:"),
  '<input type="text" name="url_mailing_list_archives_private"
       id="url_mailing_list_archives_private" value="'
 .$row_grp['url_mailing_list_archives_private'].'" size="'.$textfield_size.'" />',
         "url_mailing_list_archives_private");
  print specific_showinput(no_i18n("Administrative Interface URL:"),
  '<input type="text" name="url_mailing_list_admin"
         id="url_mailing_list_admin" value="'
  .$row_grp['url_mailing_list_admin'].'" size="'.$textfield_size.'" />',
         "url_mailing_list_admin");

  print $HTML->box_bottom();
  print '<br /><br />';

function artifact_checkbox($HTML, $title, $description, $label, $artifact)
{
  print $HTML->box_top($title);
  if ($description != '')
    print '<p>'.$description."</p>\n";
  print specific_showinput_inverted($label,
  '<input type="checkbox" name="can_use_'.$artifact.'" id="can_use_'
  .$artifact.'" value="1"'
  .(($row_grp['can_use_'.$artifact]==1) ? ' checked="checked"' : '').' />',
  "can_use_".$artifact);

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

print '
<p align="center">
<input type="submit" name="update" value="'.$update_button_text.'" /> &nbsp;
<input type="submit" name="delete" value="'.no_i18n("Delete this Group Type").'" />
</p>
';

  #  Menu settings

  print $HTML->box_top(no_i18n("Project Menu Settings"),'',1);
  $i = 1;
  print '<p class="'.utils_get_alt_row_color($i).'">'
.no_i18n('This form allows you to choose which menu entries are configurable by the
projects administrators.').'</p>';
  function specific_checkbox ($val, $explanation, $increment=1)
    {
      # just a little fonction to clean that part of the code, no
      # interest to generalize it
      global $i, $row_grp;
      if ($increment)
	$i++;
      print '<li class="'.utils_get_alt_row_color($i).'">';
      html_build_checkbox("is_menu_configurable_".$val,
                          $row_grp["is_menu_configurable_".$val]);
      print '
  <span class="preinput"><label for="is_menu_configurable_'.$val.'">'
             .$explanation.'</label></span></li>
';
    }

  specific_checkbox("homepage",
		    no_i18n("the homepage link can be modified"));

  specific_checkbox("extralink_documentation",
		    no_i18n("the documentation &ldquo;extra&rdquo; link can be modified"));

  specific_checkbox("download",
		    no_i18n("the download area link can be modified"));
  $row_grp["is_menu_configurable_download_dir"] =
        $row_grp["is_configurable_download_dir"];
  specific_checkbox("download_dir",
		    no_i18n("the download _directory_ can be modified -- beware, if
the backend is running and creating download dir, it can be used maliciously.
don't activate this feature unless you truly know what you're doing"),0);

  specific_checkbox("support",
		    no_i18n("the support link can be modified"));

  specific_checkbox("bugs",
		    no_i18n("the bug tracker link can be modified"));

  specific_checkbox("task",
		    no_i18n("the task tracker link can be modified"));

  specific_checkbox("patch",
		    no_i18n("the patch tracker link can be modified"));

  specific_checkbox("forum",
		    no_i18n("the forum link can be modified"));

  specific_checkbox("mail",
		    no_i18n("the mailing list link can be modified"));

  specific_checkbox("cvs",
		    no_i18n("the cvs link can be modified"));
  specific_checkbox("cvs_viewcvs",
		    no_i18n("the viewcvs link can be modified"),0);
  specific_checkbox("cvs_viewcvs_homepage",
		    no_i18n("the viewcvs link for homepage code can be modified"),0);

  specific_checkbox("arch",
		    no_i18n("the GNU Arch link can be modified"));
  specific_checkbox("arch_viewcvs",
		    no_i18n("the GNU Arch viewcvs link can be modified"),0);

  specific_checkbox("svn",
		    no_i18n("the Subversion link can be modified"));
  specific_checkbox("svn_viewcvs",
		    no_i18n("the Subversion viewcvs link can be modified"),0);

  specific_checkbox("git",
		    no_i18n("the Git link can be modified"));
  specific_checkbox("git_viewcvs",
		    no_i18n("the Git viewcvs link can be modified"),0);

  specific_checkbox("hg",
		    no_i18n("the Mercurial link can be modified"));
  specific_checkbox("hg_viewcvs",
		    no_i18n("the Mercurial viewcvs link can be modified"),0);

  specific_checkbox("bzr",
		    no_i18n("the Bazaar link can be modified"));
  specific_checkbox("bzr_viewcvs",
		    no_i18n("the Bazaar viewcvs link can be modified"),0);

  print $HTML->box_bottom(1);

  print '<p align="center"><input type="submit" name="update" value="'
        .$update_button_text.'" /> &nbsp;
<input type="submit" name="delete" value="'.no_i18n("Delete this Group Type").'" />';

  print '<br /><br />';

  # Project users' settings.

  $HTML->box1_top(no_i18n("Project Default Member Permissions"));

  print '<p>'.no_i18n("This form allows you to define the default permissions for
users added to a group of this type, unless this group defined its own
configuration.").'</p>';

  $title_arr=array();
  $title_arr[]=no_i18n("Cookbook Manager");
  $title_arr[]=no_i18n("Support Tracking");
  $title_arr[]=no_i18n("Bug Tracking");
  $title_arr[]=no_i18n("Task Tracking");
  $title_arr[]=no_i18n("Patch Tracking");
  $title_arr[]=no_i18n("News Manager");
  print html_build_list_table_top ($title_arr);
  print "<tr>\n";
  html_select_permission_box("cookbook", $row_grp['cookbook_flags'], "type");
  html_select_permission_box("support", $row_grp['support_flags'], "type");
  html_select_permission_box("bugs", $row_grp['bugs_flags'], "type");
  html_select_permission_box("task", $row_grp['task_flags'], "type");
  html_select_permission_box("patch", $row_grp['patch_flags'], "type");
  html_select_permission_box("news", $row_grp['news_flags'], "type");

  print '  </tr>
</table>';

  $HTML->box1_bottom();

 print '<p align="center"><input type="submit" name="update" value="'
       .$update_button_text.'" /> &nbsp;
<input type="submit" name="delete" value="'.no_i18n("Delete this Group Type").'" />';

  print "<br /><br />\n";

  $HTML->box1_top(no_i18n("Project Default Posting Restrictions"));

  print '<p>'.no_i18n("This form allows you to define the default posting restriction
on this group trackers.").'</p>';

  print html_build_list_table_top ($title_arr);
  print "<tr>\n";
  html_select_restriction_box("cookbook", $row_grp['cookbook_rflags'], "type");
  html_select_restriction_box("support", $row_grp['support_rflags'], "type");
  html_select_restriction_box("bugs", $row_grp['bugs_rflags'], "type");
  html_select_restriction_box("task", $row_grp['task_rflags'], "type");
  html_select_restriction_box("patch", $row_grp['patch_rflags'], "type");
  html_select_restriction_box("news", $row_grp['news_rflags'], "type");
  print '  </tr>
</table>';

  $HTML->box1_bottom();

  print '<p align="center">
<input type="submit" name="update" value="'.$update_button_text.'" /> &nbsp;
<input type="submit" name="delete" value="'.no_i18n("Delete this Group Type")
.'" /></p>
</form>
';

}

site_admin_footer(array());
?>
