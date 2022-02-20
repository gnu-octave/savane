<?php
# Set user permissions.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2000-2003 Free Software Foundation
# Copyright (C) 2000-2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004-2005 Yves Perrin <yves.perrin--cern.ch>
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

require_directory("project");
$is_admin_page='y';

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

extract (sane_import ('post',
  [
    'true' => 'update',
    'digits' =>
      [
        ARTIFACT . '_restrict_event2',
        [ARTIFACT . '_restrict_event1', [0, 99]]
      ]
  ]
));

if ($update)
  {
    # If the group entry does not exist, create it.
    if (!db_result(db_execute("SELECT groups_default_permissions_id
                             FROM groups_default_permissions WHERE group_id=?",
                            array($group_id)), 0,
                            "groups_default_permissions_id"))
      db_execute("INSERT INTO groups_default_permissions (group_id)
                  VALUES (?)", array($group_id));

  # Update posting restrictions.
    $newitem_restrict_event1 = ARTIFACT."_restrict_event1";
    $newitem_restrict_event2 = ARTIFACT."_restrict_event2";
    $flags = ($$newitem_restrict_event2)*100 + $$newitem_restrict_event1;
    if (!$flags)
      # If equal to 0, manually set to NULL, since 0 have a different meaning.
      $flags = NULL;

  # Update the table.
    $result = db_execute('UPDATE groups_default_permissions SET '
                         .ARTIFACT."_rflags=? "
                         ."WHERE group_id=?", array($flags, $group_id));
    if ($result)
      {
        group_add_history('Changed Posting Restrictions','',$group_id);
        fb(_("Posting restrictions updated."));
      }
    else
      {
        print db_error();
        fb(_("Unable to change posting restrictions."), 0);
      }
  }

trackers_header_admin(array ('title'=>_("Set Permissions")));

print '<h2>'._("Posting Restrictions")."</h2>\n";
print '<form action="'.htmlentities ($_SERVER['PHP_SELF']).'" method="post">
<input type="hidden" name="group" value="' . $group . '" />';

print '<span class="preinput">'
._("Authentication level required to be able to post new items on this tracker:")
." </span><br />\n";
print '&nbsp;&nbsp;&nbsp;';
print html_select_restriction_box(ARTIFACT,
                                  group_getrestrictions($group_id, ARTIFACT),
                                  $group,'', 1);
print '<br /><br />
<span class="preinput">'
._("Authentication level required to be able to post comments (and to attach
files) on this tracker:")
." </span><br />\n";
print '&nbsp;&nbsp;&nbsp;';
print html_select_restriction_box(ARTIFACT,
                                  group_getrestrictions($group_id, ARTIFACT, 2),
                                  $group,'', 2);
print '
<p align="center"><input type="submit" name="update" value="'
._("Update Permissions").'" /></p>
</form>
';

trackers_footer(array());
?>
