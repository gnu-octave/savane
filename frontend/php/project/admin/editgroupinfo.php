<?php
# Edit project name/description/maturity
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2000-2003 Free Software Foundation
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
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

require_once('../../include/init.php');
require_once('../../include/vars.php');

extract(sane_import('post', array('update',
  'form_group_name', 'form_shortdesc', 'form_longdesc', 'form_devel_status',
  'upgrade_gpl')));

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

# Update info for page.
$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));
if (db_numrows($res_grp) < 1)
  exit_no_group();
$row_grp = db_fetch_array($res_grp);

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
      fb(_("Update failed."), 1);

    if ($row_grp['license'] == 'gpl' and $upgrade_gpl)
      db_execute("UPDATE groups SET license='gplv3orlater' WHERE group_id=?",
                 array($group_id));
  }

$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));
if (db_numrows($res_grp) < 1)
  exit_no_group();
$row_grp = db_fetch_array($res_grp);

site_project_header(array('title'=>_("Editing Public Information"),
                          'group'=>$group_id,'context'=>'ahome'));

# General Description.

print form_header($_SERVER['PHP_SELF'])
     .form_input("hidden", "group_id", $group_id);

print '
<p><span class="preinput"><label for="form_group_name">'._("Group Name:")
.'</label></span>
<br />&nbsp;&nbsp;&nbsp;'.form_input("text",
                                     "form_group_name",
                                     $row_grp['group_name'],
                                     'size="60" maxlen="254"').'</p>
';
print '
<p><span class="preinput"><label for="form_shortdesc">'
. _("Short Description (255 characters max)")
. '</label> ' . markup_info("none") . '</span>
<br />&nbsp;&nbsp;&nbsp;'.form_textarea("form_shortdesc",
                                        $row_grp['short_description'],
                                        'cols="70" rows="3" wrap="virtual"').'</p>
';
print '
<p><span class="preinput"><label for="form_longdesc">'._("Long Description")
.'</label> '.markup_info("full").'</span>
<br />&nbsp;&nbsp;&nbsp;'.form_textarea("form_longdesc",
                                        $row_grp['long_description'],
                                        'cols="70" rows="10" wrap="virtual"').'</p>
';

$type_id = $row_grp['type'];
$result1 = db_execute("SELECT * FROM group_type WHERE type_id=?", array($type_id));
$row_grp1 = db_fetch_array($result1);

if($DEVEL_STATUS1 = $row_grp1['devel_status_array'])
  $DEVEL_STATUS = preg_split("/\n/",$DEVEL_STATUS1);

if ($project->CanUse("devel_status"))
  {
    print '
<p><span class="preinput"><label for="form_devel_status">'
      ._("Development Status:")
      .'</label></span><br />&nbsp;&nbsp;&nbsp;<select '
      .'name="form_devel_status" id="form_devel_status">';
    while (list($k,$v) = each($DEVEL_STATUS))
      {
        print '<option value="'.$k.'"';
        if ($k == $row_grp['devel_status'])
          print ' selected';
        print '>'.$v;
        print '</option>';
      }
    print '</select></p>
';
  }

echo '<p><span class="preinput">'
    ._("License:").'</span><br />&nbsp;&nbsp;
'._('License changes are moderated by the site administrators. Please contact
them to change your project license.')
.'</p>'
;

if ($project->getLicense() == 'gpl')
  {
    print '<p><span class="preinput">'._("GNU GPL v3:").'</span>
<br />&nbsp;&nbsp;';
  html_build_checkbox("upgrade_gpl");
    print " <label for=\"upgrade_gpl\">"
          ._("Upgrade license to &quot;GNU GPLv3 or later&quot;");
    print "</label></p>\n";
  }

print form_footer();
site_project_footer(array());
?>
