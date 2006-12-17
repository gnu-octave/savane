<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


require "../include/pre.php";
require  $GLOBALS['sys_www_topdir']."/include/vars.php";
require_directory("project");

session_require(array('group'=>'1','admin_flags'=>'A'));

function specific_showinput ($title, $form)
{
  return '
<span class="preinput">'.$title.'</span><br />
&nbsp;&nbsp; '.$form.'<br />';
}
function specific_showinput_inverted ($title, $form)
{
  return '
<br />&nbsp;&nbsp;'.$form.' <span class="preinput">'.$title.'</span><br />';
}



# group public choice
if ($delete)
{
  $result = db_query("DELETE FROM group_type WHERE type_id='$form_type_id'");

  if (!$result)
    { fb(_("Unable to delete group type"),0); }
  else
    { fb(_("group type deleted")); }

}

if ($update) {

  $sql = "UPDATE group_type SET " . "name='$form_name',"
     . "description='$form_description',"
     . "base_host='$form_base_host',"
     . "can_use_homepage='$form_can_use_homepage',"
     . "dir_type_cvs='$form_dir_type_cvs',"
     . "dir_type_svn='$form_dir_type_svn',"
     . "dir_type_arch='$form_dir_type_arch',"
     . "dir_type_download='$form_dir_type_download',"
     . "dir_type_homepage='$form_dir_type_homepage',"
     . "dir_cvs='$form_dir_cvs',"
     . "dir_arch='$form_dir_arch',"
     . "dir_svn='$form_dir_svn',"
     . "homepage_scm='$form_homepage_scm',"
     . "dir_homepage='$form_dir_homepage',"
     . "url_homepage='$form_url_homepage',"
     . "url_cvs_viewcvs_homepage='$form_url_cvs_viewcvs_homepage',"
     . "can_use_arch='$form_can_use_arch',"
     . "can_use_svn='$form_can_use_svn',"
     . "can_use_cvs='$form_can_use_cvs',"
     . "can_use_forum='$form_can_use_forum',"
     . "url_cvs_viewcvs='$form_url_cvs_viewcvs',"
     . "url_arch_viewcvs='$form_url_arch_viewcvs',"
     . "url_svn_viewcvs='$form_url_svn_viewcvs',"
     . "can_use_license='$form_can_use_license',"
#     . "license_array='$form_license_array',"
     . "can_use_devel_status='$form_can_use_devel_status',"
#     . "devel_status_array='$form_devel_status_array',"
     . "can_use_download='$form_can_use_download',"
     . "dir_download='$form_dir_download',"
     . "url_download='$form_url_download',"
     . "can_use_mailing_list='$form_can_use_mailing_list',"
     . "mailing_list_host='$form_mailing_list_host',"
     . "url_mailing_list_listinfo='$form_url_mailing_list_listinfo',"
     . "url_mailing_list_subscribe='$form_url_mailing_list_subscribe',"
     . "url_mailing_list_unsubscribe='$form_url_mailing_list_unsubscribe',"
     . "url_mailing_list_archives='$form_url_mailing_list_archives',"
     . "url_mailing_list_archives_private='$form_url_mailing_list_archives_private',"
     . "url_mailing_list_admin='$form_url_mailing_list_admin',"
     . "mailing_list_address='$form_mailing_list_address',"
     . "mailing_list_virtual_host='$form_mailing_list_virtual_host',"
     . "mailing_list_format='$form_mailing_list_format',"
     . "can_use_forum='$form_can_use_forum',"
     . "can_use_patch='$form_can_use_patch',"
     . "can_use_task='$form_can_use_task',"
     . "can_use_news='$form_can_use_news',"
     . "can_use_bug='$form_can_use_bug',"
     . "admin_email_adress='$form_admin_email_adress' WHERE "
     . "type_id='$form_type_id'";

  $result = db_query($sql);

  if (!$result)
    { fb(sprintf(_("Unable to update group type general settings: %s"), db_error()), 1); }
  else
    { fb(_("group type general settings updated")); }

  $result = db_query("UPDATE group_type SET "
		     . "is_menu_configurable_homepage='$form_is_menu_configurable_homepage',"
		     . "is_menu_configurable_extralink_documentation='$form_is_menu_configurable_extralink_documentation',"
		     . "is_menu_configurable_download='$form_is_menu_configurable_download',"
		     . "is_menu_configurable_support='$form_is_menu_configurable_support',"
		     . "is_menu_configurable_forum='$form_is_menu_configurable_forum',"
		     . "is_menu_configurable_mail='$form_is_menu_configurable_mail',"
		     . "is_menu_configurable_cvs='$form_is_menu_configurable_cvs',"
		     . "is_menu_configurable_cvs_viewcvs='$form_is_menu_configurable_cvs_viewcvs',"
		     . "is_menu_configurable_cvs_viewcvs_homepage='$form_is_menu_configurable_cvs_viewcvs_homepage',"
		     . "is_menu_configurable_arch='$form_is_menu_configurable_arch',"
		     . "is_menu_configurable_arch_viewcvs='$form_is_menu_configurable_arch_viewcvs',"
		     . "is_menu_configurable_svn='$form_is_menu_configurable_svn',"
		     . "is_menu_configurable_svn_viewcvs='$form_is_menu_configurable_svn_viewcvs',"
		     . "is_menu_configurable_bugs='$form_is_menu_configurable_bugs',"
		     . "is_menu_configurable_task='$form_is_menu_configurable_task',"
		     . "is_menu_configurable_patch='$form_is_menu_configurable_patch',"
		     . "is_configurable_download_dir='$form_is_menu_configurable_download_dir'"
		     . " WHERE "
		     . "type_id='$form_type_id'");

  if (!$result)
    { fb(sprintf(_("Unable to update group type menu settings: %s"), db_error()), 1); }
  else
    { fb(_("group type menu settings updated")); }

  $cookbook_flags="cookbook_user_";
  $bugs_flags="bugs_user_";
  $task_flags="task_user_";
  $patch_flags="patch_user_";
  $support_flags="support_user_";
  $news_flags="news_user_";

  $sql = 'UPDATE group_type SET '
     ."cookbook_flags=".$$cookbook_flags.","
     ."bugs_flags=".$$bugs_flags.","
     ."news_flags=".$$news_flags.","
     ."task_flags=".$$task_flags.", "
     ."patch_flags=".$$patch_flags.", "
     ."support_flags=".$$support_flags." "
     ."WHERE type_id='$form_type_id'";

  $result = db_query($sql);

  if (!$result)
    { fb(sprintf(_("Unable to update group type user permissions settings: %s"), db_error()), 1); }
  else
    { fb(_("group type user permissions settings updated")); }

  $cookbook_flags="cookbook_restrict_event1";
  $bugs_flags="bugs_restrict_event1";
  $task_flags="task_restrict_event1";
  $patch_flags="patch_restrict_event1";
  $support_flags="support_restrict_event1";
  $news_flags="news_restrict_event1";

  $sql = 'UPDATE group_type SET '
     ."bugs_rflags=".$$bugs_flags.","
     ."cookbook_rflags=".$$cookbook_flags.","
     ."news_rflags=".$$news_flags.","
     ."task_rflags=".$$task_flags.", "
     ."patch_rflags=".$$patch_flags.", "
     ."support_rflags=".$$support_flags." "
     ."WHERE type_id='$form_type_id'";

  $result = db_query($sql);

  if (!$result)
    { fb(sprintf(_("Unable to update group type posting restrictions settings: %s"), db_error()), 1); }
  else
    { fb(_("group type posting restrictions settings updated")); }

}



if (!isset($type_id))
{
  site_admin_header(array('title'=>'Group Type Management','context'=>'admgrptype'));

  $result = db_query("SELECT type_id,name FROM group_type ORDER BY type_id");

  print '<br />';
  while ($usr = db_fetch_array($result))
    {
      print '<a href="'.$_SERVER['PHP_SELF'].'?type_id='.$usr[type_id].'">Type #'.$usr[type_id].': '.$usr[name].'</a><br />';
      $last=$usr[type_id];
    }
  # Find an appropriate unused group type ID (skip value 100)
  $type=$last+1;
  if ($type == 100)
    { $type = 101; }

  print '<a href="'.$_SERVER['PHP_SELF'].'?type_id='.$type.'&amp;create=1">Create new group type</a>';

}
else
{

  if ($create == "1")
    {
      db_query("INSERT INTO group_type (type_id,name) VALUES ('$type_id','New type')");
      $update_button_text = _("Create");
    }
  else
    {
      $update_button_text = _("Update");
    }

  $result = db_query("SELECT * FROM group_type WHERE type_id='$type_id'");
  $row_grp = db_fetch_array($result);

  site_admin_header(array('title'=>"Edition/Creation of Group Type",'context'=>'admgrptype'));


  print '<h1>'.$row_grp['name'].' (#'.$row_grp['type_id'].')</h1>';


  print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="form_type_id" value="'.$type_id.'" />';

  # ####################################################################
  # GENERAL SETTINGS
  # ####################################################################

  print '<h3>'._("General Default Settings for Groups of this Type").'</h3>';

  $textfield_size='65';

  # ########### Help

  print '
<p>Basic Help: host means hostname (as savannah.gnu.org), dir means directory (as /var/www/savane), url means -well- url (as http://savannah.gnu.org/blah ).</p>

<p class="warn">Everytime a project\'s unix_group_name should appear, use the special string %PROJECT.</p>

<p>Fields marked with [BACKEND SPECIFIC] are only useful is you use the savannah backend.</p>

<p>Fill only the fields that have a specific setting, differing from the whole installation settings.</p>';

  print $HTML->box_top(_("General Settings"));

  print specific_showinput(_("Name:"), '<input type="text" name="form_name" value="'.$row_grp['name'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Base Host:"), '<input type="text" name="form_base_host" value="'.$row_grp['base_host'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Description (will be added on each project main page):"), '<textarea cols="'.$textfield_size.'" rows="6"wrap="virtual" name="form_description">'.$row_grp['description'].'</textarea>');
  print specific_showinput(_("Admin Email Address:"), '<input type="text" name="form_admin_email_adress" value="'.$row_grp['admin_email_adress'].'" size="'.$textfield_size.'" />');

  print $HTML->box_bottom();
  print '<br /><br />';

  # ########### Homepage

  # FIXME: the following more or less assuming that WWW homepage will be
  # managed using CVS.
  # For instance, there will be no viewcvs possibility for Arch managed
  # repository. But this is non-blocker so we let as it is.

  print $HTML->box_top(_("Project WWW Homepage"));
  print '<div>This is useful if you provide directly web homepages (created by the backend) or if you want to allow projects to configure the related menu entry (see below). The SCM selection will only affect the content shown by the frontend related to the homepage management.</div>';
  print specific_showinput_inverted(_("Can use homepage"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_homepage" VALUE="1"'.(($row_grp['can_use_homepage']==1) ? ' checked="checked"' : '').' />');

  print '<br>'.specific_showinput(_("Selected SCM:"), '<select name="form_homepage_scm">
  <option value="cvs"'.(($row_grp['homepage_scm'] == "cvs")?" selected=\"selected\"":"").'>'._("CVS").'</option>
  <option value="arch"'.(($row_grp['homepage_scm'] == "arch")?" selected=\"selected\"":"").'>'._("GNU Arch").'</option>
  <option value="svn"'.(($row_grp['homepage_scm'] == "svn")?" selected=\"selected\"":"").'>'._("Subversion").'</option></select>');

  html_select_typedir_box("form_dir_type_homepage",
			  $row_grp['dir_type_homepage']);
  print specific_showinput(_("Homepage Dir (path on the filesystem) [BACKEND SPECIFIC]:"), '<input type="text" name="form_dir_homepage" value="'.$row_grp['dir_homepage'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Homepage URL:"), '<input type="text" name="form_url_homepage" value="'.$row_grp['url_homepage'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Homepage CVS view URL (webcvs, viewcvs):"), '<input type="text" name="form_url_cvs_viewcvs_homepage" value="'.$row_grp['url_cvs_viewcvs_homepage'].'" size="'.$textfield_size.'" />');

  print $HTML->box_bottom();
  print '<br /><br />';

  # ########### Source code

  print $HTML->box_top(_("Primary Source Code Manager: CVS"));
  print '<div>This is useful if you provide directly CVS repositories (created by the backend) or if you want to allow projects to configure the related menu entry (see below).</div>';
  print specific_showinput_inverted(_("Can use CVS"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_cvs" VALUE="1"'.(($row_grp['can_use_cvs']==1) ? ' checked="checked"' : '').' />');
  html_select_typedir_box("form_dir_type_cvs",
			  $row_grp['dir_type_cvs']);
  print specific_showinput(_("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"), '<input type="text" name="form_dir_cvs" value="'.$row_grp['dir_cvs'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Repository view URL (cvsweb, viewcvs, archzoom...):"), '<input type="text" name="form_url_cvs_viewcvs" value="'.$row_grp['url_cvs_viewcvs'].'" size="'.$textfield_size.'" />');

  print $HTML->box_bottom();
  print '<br /><br />';

  # ########### Secondary Source code

  print $HTML->box_top(_("Secondary Source Code Manager: GNU Arch"));
  print '<div>This is useful if you provide directly GNU Arch repositories (created by the backend) or if you want to allow projects to configure the related menu entry (see below).</div>';
  print specific_showinput_inverted(_("Can use GNU Arch"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_arch" VALUE="1"'.(($row_grp['can_use_arch']==1) ? ' checked="checked"' : '').' />');
  html_select_typedir_box("form_dir_type_arch",
			  $row_grp['dir_type_arch']);
  print specific_showinput(_("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"), '<input type="text" name="form_dir_arch" value="'.$row_grp['dir_arch'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Repository view URL (cvsweb, viewcvs, archzoom...):"), '<input type="text" name="form_url_arch_viewcvs" value="'.$row_grp['url_arch_viewcvs'].'" size="'.$textfield_size.'" />');

  print $HTML->box_bottom();
  print '<br /><br />';

  # ########### Tertiary Source code

  print $HTML->box_top(_("Tertiary Source Code Manager: Subversion"));
  print '<div>This is useful if you provide directly Subversion repositories (created by the backend) or if you want to allow projects to configure the related menu entry (see below).</div>';
  print specific_showinput_inverted(_("Can use Subversion"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_svn" VALUE="1"'.(($row_grp['can_use_svn']==1) ? ' checked="checked"' : '').' />');
  html_select_typedir_box("form_dir_type_svn",
			  $row_grp['dir_type_svn']);
  print specific_showinput(_("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"), '<input type="text" name="form_dir_svn" value="'.$row_grp['dir_svn'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Repository view URL (cvsweb, viewcvs, archzoom...):"), '<input type="text" name="form_url_svn_viewcvs" value="'.$row_grp['url_svn_viewcvs'].'" size="'.$textfield_size.'" />');

  print $HTML->box_bottom();
  print '<br /><br />';

  # ########### Download

  print $HTML->box_top(_("Download Area"));
  print '<div>This is useful if you provide directly download areas (created by the backend) or if you want to allow projects to configure the related menu entry (see below).</div>';
  print specific_showinput_inverted(_("Can use Download Area"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_download" VALUE="1"'.(($row_grp['can_use_download']==1) ? ' checked="checked"' : '').' />');
  html_select_typedir_box("form_dir_type_download",
			  $row_grp['dir_type_download']);
  print specific_showinput(_("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"), '<input type="text" name="form_dir_download" value="'.$row_grp['dir_download'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Repository URL:"), '<input type="text" name="form_url_download" value="'.$row_grp['url_download'].'" size="'.$textfield_size.'" />');

  print $HTML->box_bottom();
  print '<br /><br />';

  # ########### License
  print $HTML->box_top(_("Licenses"));
  print '<div>This is useful if you want project to select a license on submission. Edit site-specific-content/hashes.txt to define the list of accepted licenses.</div>';
  print specific_showinput_inverted(_("Can use licenses"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_license" VALUE="1"'.(($row_grp['can_use_license']==1) ? ' checked="checked"' : '').' />');

  // Not activated: edition of hashes.txt do the job for now
  //<br /><br />License list (short name, update include/vars.php for name and url association):<br />
  //<TEXTAREA cols="'.$textfield_size.'" rows="6"wrap="virtual" name="form_license_array">'.$row_grp['license_array'].'</textarea>

  print $HTML->box_bottom();
  print '<br /><br />';


  # ########### Devel status

  print $HTML->box_top(_("Development Status"));
  print '<div>This is useful if you want project to be able to defines their development status that will be shown on their main page. This is purely a matter of cosmetics. This option is mainly here just to remove this content in case it is useless (it does not makes sense for organizational projects). Edit site-specific-content/hashes.txt to define the list of possible development status.</div>';
  print specific_showinput_inverted(_("Can use development status"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_devel_status" VALUE="1"'.(($row_grp['can_use_devel_status']==1) ? ' checked="checked"' : '').' />');


  // Not activated: edition of hashes.txt do the job for now
  //<br />Devel Status list:<br />
  //<TEXTAREA cols="'.$textfield_size.'" rows="6" wrap="virtual" name="form_devel_status_array">'.$row_grp['devel_status_array'].'</textarea>

  print $HTML->box_bottom();
  print '<br /><br />';

   # ###########  Mailing List

  print $HTML->box_top(_("Mailing List"));
  print '<div class="warn">Important: Everytime a mailing-list name should appear, use the special string %LIST.</div><div>Do not configure Mailing-list Host, this is a deprecated feature left for backward compatibility.</div><div>Mailing-list virtual host only need to be set if you use mailman list, set up via the backend, and have several mailman virtual hosts set.';

  print specific_showinput_inverted(_("Can use mailing-lists"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_mailing_list" VALUE="1"'.( ($row_grp['can_use_mailing_list']==1) ? ' checked="checked"' : '' ).' />');

  print '<br />';

  print specific_showinput(_("Mailing-list Host (DEPRECATED):"), '<input type="text" name="form_mailing_list_host" value="'.$row_grp['mailing_list_host'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Mailing-list address (would be %LIST@gnu.org for GNU projects at sv.gnu.org):"), '<input type="text" name="form_mailing_list_address" value="'.$row_grp['mailing_list_address'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Mailing-list virtual host (would be lists.gnu.org or lists.nongnu.org at sv.gnu.org) [BACKEND SPECIFIC]:"), '<input type="text" name="form_mailing_list_virtual_host" value="'.$row_grp['mailing_list_virtual_host'].'" size="'.$textfield_size.'">');

  print '<br /><br />';
  print '<div>With the following, you can force projects to follow a specific policy for the name of the %LIST. Here you should use the special wildcard %NAME, which is the part the of the mailing list name that the project admin can define (would be %PROJECT-%NAME for non-GNU projects at sv.gnu.org).<br /><span class="warn">Do no add any @hostname here!</span></div>';
  print specific_showinput(_("Mailing list name format:"), '<input type="text" name="form_mailing_list_format" value="'.$row_grp['mailing_list_format'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Listinfo URL:"), '<input type="text" name="form_url_mailing_list_listinfo" value="'.$row_grp['url_mailing_list_listinfo'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Subscribe URL (for majordomo at CERN, it is majordomo_interface.php?func=subscribe&amp;list=%LIST&amp;mailserver=listbox.server@cern.ch):"), '<input type="text" name="form_url_mailing_list_subscribe" value="'.$row_grp['url_mailing_list_subscribe'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Unsubscribe URL (for majordomo at CERN, it is majordomo_interface.php?func=unsubscribe&amp;list=%LIST&amp;mailserver=listbox.server@cern.ch):"), '<input type="text" name="form_url_mailing_list_unsubscribe" value="'.$row_grp['url_mailing_list_unsubscribe'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Archives URL:"), '<input type="text" name="form_url_mailing_list_archives" value="'.$row_grp['url_mailing_list_archives'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Private Archives URL:"), '<input type="text" name="form_url_mailing_list_archives_private" value="'.$row_grp['url_mailing_list_archives_private'].'" size="'.$textfield_size.'" />');
  print specific_showinput(_("Administrative Interface URL:"), '<input type="text" name="form_url_mailing_list_admin" value="'.$row_grp['url_mailing_list_admin'].'" size="'.$textfield_size.'" />');

  print $HTML->box_bottom();
  print '<br /><br />';


  # ########### Forum


  print $HTML->box_top(_("Forum"));
  print '<div>Forum is a deprecated feature of Savane. We do not recommend using it and we do not maintain this code any longer.</div>';
  print specific_showinput_inverted(_("Can use forum"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_forum" VALUE="1"'.(($row_grp['can_use_forum']==1) ? ' checked="checked"' : '').' />');

  print $HTML->box_bottom();
  print '<br /><br />';


  # ###########  Support

  print $HTML->box_top(_("Support Request Manager"));
  print '<div>This is one of the main issue tracker of Savane. Project are supposed to use it as primary interface with end user.</div>';
  print specific_showinput_inverted(_("Can use support request tracker"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_support" VALUE="1"'.(($row_grp['can_use_support']==1) ? ' checked="checked"' : '').' />');

  print $HTML->box_bottom();
  print '<br /><br />';


  # ###########  Bug

  print $HTML->box_top(_("Bug Tracker"));
  print '<div>This is one of the main issue tracker of Savane. Unlike the support tracker, it is supposed to be used mainly to organize the workflow amongs project members related to bugs. Projects with large audience should probably not accept item posting by people that are not member of the project on this tracker, and instead redirect end user to the support tracker (and only real bugs would be reassigned to this tracker). But that\'s only a suggestion.</div>';
  print specific_showinput_inverted(_("Can use bug tracker"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_bug" VALUE="1"'.(($row_grp['can_use_bug']==1) ? ' checked="checked"' : '').' />');

  print $HTML->box_bottom();
  print '<br /><br />';


  # ###########  Task

  print $HTML->box_top(_("Task Manager"));
  print '<div>This is one of the main issue tracker of Savane. Unlike the support tracker, it is supposed to be used mainly to organize the workflow amongs project members related to planned tasks. It\'s the counterpart of the bug tracker for regular and planned activities.</div>';
  print specific_showinput_inverted(_("Can use task manager"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_task" VALUE="1"'.(($row_grp['can_use_task']==1) ? ' checked="checked"' : '').' />');

  print $HTML->box_bottom();
  print '<br /><br />';


  # ########### Patch

  print $HTML->box_top(_("Patch Manager"));
  print '<div>This is a deprecated issue tracker. It was originally designed to get all the submitted patches; but it seems to us more sensible that patch get attached to the relevant item (task, bug...). We may deleted this tracker in the future or "anonymize it". The later option would mean that this tracker would no longer be name the patch tracker but the name would be up to you, as site administrator. This would be an additionnal tracker, with no purpose defined out of the box.</div>';
  print specific_showinput_inverted(_("Can use patch manager (deprecated)"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_patch" VALUE="1"'.(($row_grp['can_use_patch']==1) ? ' checked="checked"' : '').' />');

  print $HTML->box_bottom();
  print '<br /><br />';


  # ###########  News

  print $HTML->box_top(_("News Manager"));
  print specific_showinput_inverted(_("Can use news manager"), '<INPUT TYPE="CHECKBOX" NAME="form_can_use_news" VALUE="1"'.(($row_grp['can_use_news']==1) ? ' checked="checked"' : '').' />');

  print $HTML->box_bottom();
  print '<br /><br />';


print '
<p align="center">
<input type="submit" name="update" value="'.$update_button_text.'" /> &nbsp;
<input type="submit" name="delete" value="'._("Delete this Group Type").'" />';


  print '<br /><br />';

  # ####################################################################
  # PROJECTS MENU SETTINGS
  # ####################################################################

  print $HTML->box_top(_("Project Menu Settings"),'',1);
  $i = 1;
  print '<div class="'.utils_get_alt_row_color($i).'">This form allows you to choose which menu entries are configurable by the projects administrators.<br /><br /></div>';
  function specific_checkbox ($val, $explanation, $increment=1)
    {
      # just a little fonction to clean that part of the code, no
      # interest to generalize it
      global $i, $row_grp;
      if ($increment)
	{ $i++; }
      print '<li class="'.utils_get_alt_row_color($i).'">';
      html_build_checkbox("form_is_menu_configurable_".$val, $row_grp["is_menu_configurable_".$val]);
      print '<span class="preinput">'.$explanation.'</span></li>';
    }

  specific_checkbox("homepage",
		    _("the homepage link can be modified"));

  specific_checkbox("extralink_documentation",
		    _("the documentation \"extra\" link can be modified"));

  specific_checkbox("download",
		    _("the download area link can be modified"));
  $row_grp["is_menu_configurable_download_dir"] = $row_grp["is_configurable_download_dir"];
  specific_checkbox("download_dir",
		    _("the download _directory_ can be modified -- beware, if the backend is running and creating download dir, it can be used maliciously. don't activate this feature unless you truly know what you're doing"),0);

  specific_checkbox("support",
		    _("the support link can be modified"));

  specific_checkbox("bugs",
		    _("the bugs tracker link can be modified"));

  specific_checkbox("task",
		    _("the task tracker link can be modified"));

  specific_checkbox("patch",
		    _("the patch tracker link can be modified"));

  specific_checkbox("forum",
		    _("the forum link can be modified"));

  specific_checkbox("mail",
		    _("the mailing-list link can be modified"));

  specific_checkbox("cvs",
		    _("the cvs link can be modified"));
  specific_checkbox("cvs_viewcvs",
		    _("the viewcvs link can be modified"),0);
  specific_checkbox("cvs_viewcvs_homepage",
		    _("the viewcvs link for homepage code can be modified"),0);

  specific_checkbox("arch",
		    _("the arch link can be modified"));
  specific_checkbox("arch_viewcvs",
		    _("the arch viewcvs link can be modified"),0);

  specific_checkbox("svn",
		    _("the subversion link can be modified"));
  specific_checkbox("svn_viewcvs",
		    _("the subversion viewcvs link can be modified"),0);


  print $HTML->box_bottom(1);

  print '<p align="center"><input type="submit" name="update" value="'.$update_button_text.'" /> &nbsp;
<input type="submit" name="delete" value="'._("Delete this Group Type").'" />';

  print '<br /><br />';

  # ####################################################################
  # PROJECTS USERS SETTINGS
  # ####################################################################

  $HTML->box1_top(_("Project Default Member Permissions"));

  print '<p>'._("This form allows you to define the default permissions for users added to a group of this type, unless this group defined its own configuration.").'</p>';

  $title_arr=array();
  $title_arr[]=_("Cookbook Manager");
  $title_arr[]=_("Support Tracking");
  $title_arr[]=_("Bug Tracking");
  $title_arr[]=_("Task Tracking");
  $title_arr[]=_("Patch Tracking");
  $title_arr[]=_("News Manager");
  print html_build_list_table_top ($title_arr);

  html_select_permission_box("cookbook", $row_grp['cookbook_flags'], "type");
  html_select_permission_box("support", $row_grp['support_flags'], "type");
  html_select_permission_box("bugs", $row_grp['bugs_flags'], "type");
  html_select_permission_box("task", $row_grp['task_flags'], "type");
  html_select_permission_box("patch", $row_grp['patch_flags'], "type");
  html_select_permission_box("news", $row_grp['news_flags'], "type");

  print '  </tr>
</table>';

  $HTML->box1_bottom();

 print '<p align="center"><input type="submit" name="update" value="'.$update_button_text.'" /> &nbsp;
<input type="submit" name="delete" value="'._("Delete this Group Type").'" />';

  print '<br /><br />';

  $HTML->box1_top(_("Project Default Posting Restrictions"));

  print '<p>'._("This form allows you to define the default posting restriction on this group trackers.").'</p>';

  print html_build_list_table_top ($title_arr);

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
<input type="submit" name="delete" value="'._("Delete this Group Type").'" /></p>
</form>';


}

site_admin_footer(array());

?>