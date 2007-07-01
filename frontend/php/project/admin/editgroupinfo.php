<?php
# Edit project name/description/maturity
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2000-2003 (c) Free Software Foundation
#                         Mathieu Roy <yeupou--gnu.org>
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

require_once('../../include/init.php');
require_once('../../include/vars.php');

extract(sane_import('post', array('update',
  'form_group_name', 'form_shortdesc', 'form_longdesc', 'form_devel_status')));

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

if ($update)
{
  group_add_history ('Changed Public Info','',$group_id);

  $result = db_autoexecute('groups',
    array(
      'group_name' => htmlspecialchars($form_group_name),
      'short_description' => htmlspecialchars($form_shortdesc),
      'long_description' => $form_longdesc,
      'devel_status' => $form_devel_status,
    ), DB_AUTOQUERY_UPDATE,
    "group_id=?", array($group_id));
  if (!$result)
    { fb(_("Update failed."), 1); }
}

# update info for page
$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));
if (db_numrows($res_grp) < 1)
{
  exit_no_group();
}
$row_grp = db_fetch_array($res_grp);


site_project_header(array('title'=>_("Editing Public Info"),'group'=>$group_id,'context'=>'ahome'));



# ####################################### General Description

print form_header($_SERVER['PHP_SELF'])
     .form_input("hidden", "group_id", $group_id);

print '
<p><span class="preinput">'._("Group Name:").'</span>
<br />&nbsp;&nbsp;&nbsp;'.form_input("text", 
				     "form_group_name", 
				     $row_grp['group_name'],
				     'size="60" maxlen="254"').'</p>';

print '
<p><span class="preinput">'.sprintf(_("Short Description %s:"), markup_info("none", ", 255 Characters Max")).'</span>
<br />&nbsp;&nbsp;&nbsp;'.form_textarea("form_shortdesc",
					$row_grp['short_description'],
					'cols="70" rows="3" wrap="virtual"').'</p>';

print '
<p><span class="preinput">'.sprintf(_("Long Description %s:"), markup_info("full")).'</span>
<br />&nbsp;&nbsp;&nbsp;'.form_textarea("form_longdesc",
					$row_grp['long_description'],
					'cols="70" rows="10" wrap="virtual"').'</p>';

$type_id = $row_grp['type'];
$result1 = db_execute("SELECT * FROM group_type WHERE type_id=?", array($type_id));
$row_grp1 = db_fetch_array($result1);

if($DEVEL_STATUS1 = $row_grp1['devel_status_array']){
  $DEVEL_STATUS=preg_split("/\n/",$DEVEL_STATUS1);
}

if ($project->CanUse("devel_status"))
{
  print '
<p><span class="preinput">'
    ._("Development Status:").'</span><br />&nbsp;&nbsp;&nbsp;<select name="form_devel_status">';
  while (list($k,$v) = each($DEVEL_STATUS))
    {
      print '<option value="'.$k.'"';
      if ($k == $row_grp['devel_status'])
	{ print ' SELECTED'; }
      print '>'.$v;
      print '</option>';
    }
  print '</select></p>';
}

print form_footer();

site_project_footer(array());
