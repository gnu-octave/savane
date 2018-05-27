<?php
# Edit one group as superuser
# 
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2017, 2018 Ineiev
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

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
      $res_grp = db_execute("SELECT * FROM groups WHERE group_id=?",
                            array($group_id));
      $res_type = db_execute("SELECT * FROM group_type WHERE type_id=?",
                             array($group_type));
      
      if (db_result($res_grp,0,'status') != $form_status)
	{
	  group_add_history ('status',db_result($res_grp,0,'status'),$group_id);
	}
      if (db_result($res_grp,0,'is_public') != $form_public)
	{
	  group_add_history ('is_public',db_result($res_grp,0,'is_public'),
                             $group_id);
	}
      if (db_result($res_grp,0,'type') != $group_type)
	{
	  group_add_history ('type',db_result($res_grp,0,'type'),$group_id);
	}
      if (db_result($res_grp,0,'unix_group_name') != $form_name)
	{
	  group_add_history ('unix_group_name',
                             db_result($res_grp,0,'unix_group_name'),
                             $group_id);
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
      db_execute("UPDATE groups SET status=? WHERE group_id=?",
                 array($status, $group_id));
    }
  fb(no_i18n("Updating Project Info"));
}


# get current information
$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));

site_admin_header(array('title'=>no_i18n("Group List"),'context'=>'admgroup'));


if (db_numrows($res_grp) < 1) {
	fb(no_i18n("Invalid Group: Invalid group was passed in."));
	site_admin_footer(array());
	exit;
}

$row_grp = db_fetch_array($res_grp);

utils_get_content("admin/groupedit_intro");

print '<p>';
print "<a href='../projects/{$row_grp['unix_group_name']}'>"
.no_i18n("Project public page")."</a>";
print '</p>
';

# MODIFICATORS SHORTCUTS
print '<h2>'.no_i18n("Registration Management Shortcuts").'</h2>
';
print '<a href="'.htmlentities ($_SERVER['PHP_SELF'])
.'?status=A&amp;updatefast=1&amp;group_id='
.$group_id.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
.'.theme/bool/ok.orig.png" alt="'.no_i18n("Approve").'" /></a>&nbsp;&nbsp;&nbsp;';
print '<a href="'.htmlentities ($_SERVER['PHP_SELF'])
.'?status=D&amp;updatefast=1&amp;group_id='
.$group_id.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
.'.theme/bool/wrong.orig.png" alt="'.no_i18n("Discard").'" /></a>&nbsp;&nbsp;&nbsp;';
print '<a href="triggercreation.php?group_id='.$group_id.'"><img src="'
.$GLOBALS['sys_home'].'images/'.SV_THEME
.'.theme/contexts/preferences.orig.png" alt="'
.no_i18n("Send New Project Instruction Email and Trigger Project Creation (should be
done only once)").'" /></a>';

# MODIFICATORS
print '<form action="'.htmlentities ($_SERVER['PHP_SELF'])
.'" method="POST">';
print '<h2>'.no_i18n("Detailed Interface").'</h2>
';
$HTML->box1_top(no_i18n("General Settings"));

print '<p><span class="preinput">'.no_i18n("Group Type:").' </span><br />
';
print '<em>';
utils_get_content("admin/groupedit_grouptype");
print '</em><br />
';
print show_group_type_box('group_type',$row_grp['type']);

$i=0;
print '</td>
</tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput"><label for="form_name">'
.no_i18n("System Name:").'</label></span><br />
';
print '<input type="text" name="form_name" id="form_name" value="'
      .$row_grp['unix_group_name'].'" />';

$i++;
print '</td>
</tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput"><label for="form_status">'.no_i18n("Status:")
.'</label></span><br />
';

print '<select status" name="form_status" id="form_status">
<option '.(($row_grp['status'] == "A")?'selected ':'').'value="A">'
       .no_i18n("Active").'</option>
<option '.(($row_grp['status'] == "P")?'selected ':'').'value="P">'
       .no_i18n("Pending").'</option>
<option '.(($row_grp['status'] == "D")?'selected ':'').'value="D">'
       .no_i18n("Deleted").'</option>
<option '.(($row_grp['status'] == "M")?'selected ':'').'value="M">'
       .no_i18n("Maintenance (accessible only to superuser)").'</option>
<option '.(($row_grp['status'] == "I")?'selected ':'').'value="I">'
       .no_i18n("Incomplete (failure during registration)").'</option>
</select>
';
print '<p class="warn">'
.no_i18n("On project approval, do not forget to run the script &ldquo;Trigger Project
Creation&rdquo; at the end of this page, otherwise this project could end up
partly misconfigured.").'</p>
';
print '<p>'
.no_i18n("Project marked as deleted will be removed from the database by a
cronjob.").'</p>
';


$i++;
print '</td>
</tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput"><label for="form_public">'.no_i18n("Public?")
.'</label></span><br />
'.no_i18n("A private project will be completely invisible from the web interface.").'
'.no_i18n("You must clear the HTML repository field below when setting the private
flag otherwise unpredictable result will occur.").'<br />
<select name="form_public" id="form_public">
<option '.(($row_grp['is_public'] == 1)?'selected ':'').'value="1">'.no_i18n("Yes").'</option>
<option '.(($row_grp['is_public'] == 0)?'selected ':'').'value="0">'.no_i18n("No").'</option>
</select>
';

$i++;
print '</td>
</tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput"><label for="form_license">'
.no_i18n("License:").'</label></span><br />
';
print no_i18n("Note: this has influence only if the group type of which this group
belongs to accepts this information.").'<br />';
print '<select name="form_license" id="form_license">';
print '<option value="none">'.no_i18n("N/A").'</option>';
print '<option value="other">'.no_i18n("Other license").'</option>';
while (list($k,$v) = each($LICENSE_EN))
{
  print "<OPTION value=\"$k\"";
  if ($k == $row_grp['license']) print " selected";
  print ">$v</option>\n";
}
print '</select>
<br />
<label for="form_license_other">';
print no_i18n("If other:").'</label><br />
<input type="text" name="form_license_other" id="form_license_other" value="'
.$row_grp['license_other'].'" />';
print '</p>
';
print '<input type="hidden" name="group_id" value="'.$group_id.'" />';

$i++;
print '</td>
</tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

print '
<p><input type="submit" name="update" value="'.no_i18n("Update").'">';

$HTML->box1_bottom();

print '<p><a href="triggercreation.php?group_id='.$group_id.'">'
.no_i18n("Send New Project Instruction Email and Trigger Project Creation (should be
done only once)").'</a>';
print '</p>
';

# INFORMATION: redundant with the content of the approval task
$HTML->box1_top(no_i18n("Submitted Information"));

project_admin_registration_info($row_grp);

$HTML->box1_bottom();

# BACKEND SPECIFIC
print '<p>';
$HTML->box1_top(no_i18n("Specific Backend Settings"));
print no_i18n('[BACKEND SPECIFIC] If this group must have specific directories for
homepage, sourcecode, download, which are not the default of the group type it
belongs to, you can fill in the following fields. You may need to also edit the
urls in &ldquo;This Project Active Features.&rdquo; If possible, you should
avoid using these fields and consider creating new group types. Exceptions are
a pain to handle in the long run.');
$i=0;
print '</td>
</tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

function vcs_directory ($vcs, $label)
{
  print '<p><span class="preinput"><label for="form_dir_'.$vcs.'">'
  .$label.'</label></span><br />
';
print '<input type="text" name="form_dir_'.$vcs.'" id="form_dir_'.$vcs
      .'" value="'.$row_grp['dir_'.$vcs.''].'" size="50" />';
}

vcs_directory ('cvs', no_i18n("CVS directory:"));
$i++;
print '</td></tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

vcs_directory ('arch', no_i18n("GNU Arch directory:"));
$i++;
print '</td></tr>
<tr><td class="'.utils_get_alt_row_color($i).'">';

vcs_directory ('svn', no_i18n("Subversion directory:"));
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

vcs_directory ('git', no_i18n("Git directory:"));
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

vcs_directory ('hg', no_i18n("Mercurial directory:"));
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

vcs_directory ('bzr', no_i18n("Bazaar directory:"));
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput"><label for="form_dir_homepage">'
.no_i18n("Homepage directory:").'</label></span><br />
<input type="text" name="form_dir_homepage" id="form_dir_homepage" value="'
      .$row_grp['dir_homepage'].'" size="50" />';
$i++;
print '</td></tr><tr><td class="'.utils_get_alt_row_color($i).'">';

print '<p><span class="preinput"><label for="form_dir_download">'
.no_i18n("Download directory:").'</label></span><br />
<input type="text" name="form_dir_download" id="form_dir_download" value="'
      .$row_grp['dir_download'].'" size="50" />';
print '
<p><input type="submit" name="update" value="'.no_i18n("Update").'">';

$HTML->box1_bottom();

utils_get_content("admin/groupedit_outro");

site_admin_footer(array());
?>
