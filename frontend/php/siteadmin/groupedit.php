<?php
# Edit one group as superuser
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
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
# needed for group history :
require_directory("project");

session_require(array('group'=>$sys_group_id,'admin_flags'=>'A'));

extract(sane_import('post',
  array('update', 'form_name', 'form_status', 'form_public',
	'form_license', 'form_license_other', 'group_type',
	'form_dir_cvs', 'form_dir_arch', 'form_dir_svn', 'form_dir_git',
	'form_dir_hg', 'form_dir_bzr', 'form_dir_homepage', 'form_dir_download')));
extract(sane_import('get',
  array('updatefast', 'status')));

# group public choice
if ($update || $updatefast)
{
  # Full details update
  if ($update) 
    {
      $res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));
      $res_type = db_execute("SELECT * FROM group_type WHERE type_id=?", array($group_type));
      
      
      if (db_result($res_grp,0,'status') != $form_status)
	{
	  group_add_history ('status',db_result($res_grp,0,'status'),$group_id);
	}
      if (db_result($res_grp,0,'is_public') != $form_public)
	{
	  group_add_history ('is_public',db_result($res_grp,0,'is_public'),$group_id);
	}
      if (db_result($res_grp,0,'type') != $group_type)
	{
	  group_add_history ('type',db_result($res_grp,0,'type'),$group_id);
	}
      if (db_result($res_grp,0,'unix_group_name') != $form_name)
	{
	  group_add_history ('unix_group_name',db_result($res_grp,0,'unix_group_name'),$group_id);
	}
      
      db_autoexecute('groups',
        array(
	  'is_public' => $form_public,
	  'status' => $form_status,
	  'license' => $form_license,
	  'license_other' => $form_license_other,
	  'type' => $group_type,
	  'unix_group_name' => $form_name,
	  'dir_cvs' => $form_dir_cvs,
	  'dir_arch' => $form_dir_arch,
	  'dir_svn' => $form_dir_svn,
	  'dir_git' => $form_dir_git,
	  'dir_hg' => $form_dir_hg,
	  'dir_bzr' => $form_dir_bzr,
	  'dir_homepage' => $form_dir_homepage,
	  'dir_download' => $form_dir_download,
	), DB_AUTOQUERY_UPDATE,
        "group_id=?", array($group_id));
    }
  if ($updatefast) 
    {
      db_execute("UPDATE groups SET status=? WHERE group_id=?", array($status, $group_id));
    }
      
  fb(_("Updating Project Info"));
}


# get current information
$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));

site_admin_header(array('title'=>_("Group List"),'context'=>'admgroup'));


if (db_numrows($res_grp) < 1) {
	fb(_("Invalid Group: Invalid group was passed in."));
	site_admin_footer(array());
	exit;
}

$row_grp = db_fetch_array($res_grp);

# we get site-specific content
utils_get_content("admin/groupedit_intro");

print '<p>';
print "<a href='../projects/{$row_grp['unix_group_name']}'>Go to project public page</a>";
print '</p>';

# MODIFICATORS SHORTCUTS
print '<h3>'._("Registration Management Shortcuts").'</h3>';
print '<a href="'.$_SERVER['PHP_SELF'].'?status=A&amp;updatefast=1&amp;group_id='.$group_id.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/bool/ok.orig.png" alt="'._("Approve").'" /></a>&nbsp;&nbsp;&nbsp;';
print '<a href="'.$_SERVER['PHP_SELF'].'?status=D&amp;updatefast=1&amp;group_id='.$group_id.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/bool/wrong.orig.png" alt="'._("Discard").'" /></a>&nbsp;&nbsp;&nbsp;';
print '<a href="triggercreation.php?group_id='.$group_id.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/contexts/preferences.orig.png" alt="'._("Send New Project Instruction Email and Trigger Project Creation (should be done only once)").'" /></a>';

# MODIFICATORS
print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
print '<h3>'._("Detailed Interface").'</h3>';
$HTML->box1_top(_("General Settings"));

print '<p><span class="preinput">'._("Group Type:").' </span><br />';
print '<em>';
utils_get_content("admin/groupedit_grouptype");
print '</em><br />';
print show_group_type_box('group_type',$row_grp['type']);


$i=0;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("System Name").':</span><br /> ';
print '<input type="text" name="form_name" value="'.$row_grp['unix_group_name'].'" />';

$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Status").':</span><br />';

print '<select name="form_status">';

print '<option '.(($row_grp['status'] == "A")?'selected ':'').'value="A">'._("Active").'</option>';
print '<option '.(($row_grp['status'] == "P")?'selected ':'').'value="P">'._("Pending").'</option>';
print '<option '.(($row_grp['status'] == "D")?'selected ':'').'value="D">'._("Deleted").'</option>';
print '<option '.(($row_grp['status'] == "M")?'selected ':'').'value="M">'._("Maintenance (accessible only to superuser)").'</option>';
print '<option '.(($row_grp['status'] == "I")?'selected ':'').'value="I">'._("Incomplete (failure during registration)").'</option>';


print '</select>';
print '<p class="warn">'._("On project approval, do not forget to run the script \"Trigger Project Creation\" at the end of this page, otherwise this project could end up partly misconfigured.").'</p>';
print '<p>'._("Project marked as deleted will be removed from the database by a cronjob.").'</p>';


$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Public?").'</span><br />
'._("A private project will be completely invisible from the web interface.").'
'._("You must clear the HTML repository field below when setting the private flag otherwise unpredictable result will occur.").'</em><br />';
print '<select name="form_public">';
print '<option '.(($row_grp['is_public'] == 1)?'selected ':'').'value="1">'._("Yes").'</option>';
print '<option '.(($row_grp['is_public'] == 0)?'selected ':'').'value="0">'._("No").'</option>';
print '</select>';

$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("License:").' </span><br />';
print _("Note: this has influence only if the group type of which this group belongs to accepts this information.").'<br /></em>';
print '<select name="form_license">';
print '<option value="none">'._("N/A").'</option>';
print '<option value="other">'._("Other").'</option>';
while (list($k,$v) = each($LICENSE))
{
  print "<OPTION value=\"$k\"";
  if ($k == $row_grp['license']) print " selected";
  print ">$v</option>\n";
}
print '</select>';
print '<br />';
print _("If other:").'<br />';
print '<input type="text" name="form_license_other" value="'.$row_grp['license_other'].'" />';
print '</p>';

print '<input type="hidden" name="group_id" value="'.$group_id.'" />';


$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '
<p><INPUT type="submit" name="update" value="'._("Update").'">';

$HTML->box1_bottom();

print '<p><a href="triggercreation.php?group_id='.$group_id.'">'._("Send New Project Instruction Email and Trigger Project Creation (should be done only once)").'</a>';

print '</p>';

# INFORMATION: redundant with the content of the approval task
$HTML->box1_top(_("Submitted Information"));

project_admin_registration_info($row_grp);

$HTML->box1_bottom();

# BACKEND SPECIFIC
print '<p>';
$HTML->box1_top(_("Specific Backend Settings"));
print _('[BACKEND SPECIFIC] If this group must have specific directories for homepage, sourcecode, download, which are not the default of the group type it belongs to, you can fill in the following fields. You may need to also edit the urls in \'This Project Active Features\'. If possible, you should avoid using these fields and consider creating new group types. Exceptions are a pain to handle in the long run.');
$i=0;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("CVS directory:").'</span><br /> ';
print '<input type="text" name="form_dir_cvs" value="'.$row_grp['dir_cvs'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("GNU Arch directory:").'</span><br /> ';
print '<input type="text" name="form_dir_arch" value="'.$row_grp['dir_arch'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Subversion directory:").'</span><br /> ';
print '<input type="text" name="form_dir_svn" value="'.$row_grp['dir_svn'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Git directory:").'</span><br /> ';
print '<input type="text" name="form_dir_git" value="'.$row_grp['dir_git'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Mercurial directory:").'</span><br /> ';
print '<input type="text" name="form_dir_hg" value="'.$row_grp['dir_hg'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Bazaar directory:").'</span><br /> ';
print '<input type="text" name="form_dir_bzr" value="'.$row_grp['dir_bzr'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Homepage directory:").'</span><br /> ';
print '<input type="text" name="form_dir_homepage" value="'.$row_grp['dir_homepage'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput">'._("Download directory:").'</span><br /> ';
print '<input type="text" name="form_dir_download" value="'.$row_grp['dir_download'].'" size="50" />';
print '
<p><INPUT type="submit" name="update" value="'._("Update").'">';

$HTML->box1_bottom();

# we get site-specific content
utils_get_content("admin/groupedit_outro");


site_admin_footer(array());
