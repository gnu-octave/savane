<?php
# Edit user's groups.
# 
# This file is part of the Savane project
# 
# Copyright (C) 1999-2000 The SourceForge Crew
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

require_once('../include/init.php');
require_once('../include/account.php');
session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>no_i18n('Admin: User Info')));

extract(sane_import('request', array('user_id', 'action')));
extract(sane_import('post', array('admin_flags', 'email')));

// user remove from group
if ($action=='remove_user_from_group') {
        # Remove this user from this group

	$result = db_execute("DELETE FROM user_group "
                             ."WHERE user_id=? AND group_id=?",
			     array($user_id, $group_id));
	if (!$result || db_affected_rows($result) < 1) {
		fb(no_i18n('Error Removing User:').' '.db_error(), 1);
	} else {
		fb (no_i18n('Successfully removed user'));
	}

} else if ($action=='update_user_group') {
        # Update the user/group entry

	$result = db_execute("UPDATE user_group SET admin_flags=? "
		. "WHERE user_id=? AND "
		. "group_id=?", array($admin_flags, $user_id, $group_id));
	if (!$result || db_affected_rows($result) < 1) {
		fb(no_i18n('Error Updating User Group:').' '.db_error(), 1);
	} else {
		fb(no_i18n('Successfully updated user group'));
	}


} else if ($action=='update_user') {
        # Update the user

	$result=db_execute("UPDATE user SET email=? WHERE user_id=?",
			   array($email, $user_id));
	if (!$result || db_affected_rows($result) < 1) {
		fb(no_i18n('Error Updating User:').$result.' '.db_error(), 1);
	} else {
		fb(no_i18n('Successfully updated user'));
	}

} else if ($action=='add_user_to_group') {
        # Add this user to a group
	$result=db_execute("INSERT INTO user_group (user_id, group_id) "
                           ."VALUES (?, ?)",
			   array($user_id, $group_id));
	if (!$result || db_affected_rows($result) < 1) {
		fb(no_i18n('Error Adding User to Group:').' '.db_error(), 1);
	} else {
		fb(no_i18n('Successfully added user to group'));
	}
}

// get user info
$res_user = db_execute("SELECT * FROM user WHERE user_id=?", array($user_id));
$row_user = db_fetch_array($res_user);

print '
<p>'.no_i18n('Savannah User Group Edit for user:').' <strong>'
.$user_id. ' ' .user_getname($user_id).'</strong></p>
<p>
'.no_i18n('Account Info:').'
<form method="post" action="'.$_SERVER['PHP_SELF'].'">
<input type="hidden" name="action" value="update_user">
<input type="hidden" name="user_id" value="'.$user_id.'">
</p>
<p>
<input type="text" name="email" value="'
.htmlspecialchars($row_user['email']).'" size="25" maxlength="55">

<p>
<input type="submit" name="Update_Unix" value="'.no_i18n('Update').'">
</p>
</form>
<hr />

<p>
<h2>'.no_i18n('Current Groups:').'</h2>
<br />
';

# Iterate and show groups this user is in
$res_cat = db_execute("SELECT groups.group_name AS group_name, "
	. "groups.group_id AS group_id, "
	. "user_group.admin_flags AS admin_flags FROM "
	. "groups,user_group WHERE user_group.user_id=? AND "
	. "groups.group_id=user_group.group_id", array($user_id));

	while ($row_cat = db_fetch_array($res_cat)) {
		print ("<br /><hr /><strong>"
                        . group_getname($row_cat['group_id']) . "</strong> "
			. "<a href=\"usergroup.php?user_id="
                        . "$user_id&action=remove_user_from_group&group_id="
                        . "$row_cat[group_id]\">"
			. "[".no_i18n('Remove User from Group')."]</a>");
		# editing for flags
print '
<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="action" value="update_user_group">
<input name="user_id" type="hidden" value="'.$user_id.'">
<input name="group_id" type="hidden" value="'.$row_cat['group_id'].'">
<br />
'.no_i18n('Admin Flags:').'
<br />
<input type="text" name="admin_flags" value="'
.htmlspecialchars($row_cat['admin_flags'], ENT_QUOTES).'">
<br />
<input type="submit" name="Update_Group" value="'.no_i18n('Update').'" />
</form>
';
	}

# Show a form so a user can be added to a group
print '
<hr />
<p>
<form action="'.$_SERVER['PHP_SELF'].'" method="post">
<input type="hidden" name="action" value="add_user_to_group">
<input name="user_id" type="hidden" value="'.$user_id.'">
<p>
'.no_i18n('Add User to Group (group_id):').'
<br />
<input type="text" name="group_id" length="4" maxlength="5" />
</p>
<p>
<input type="submit" name="Submit" value="'.no_i18n('Submit').'" />
</form>

<p><a href="user_changepw.php?user_id='
.$user_id.'">['.no_i18n('Change User\'s Password').']</a>
</p>
';

html_feedback_bottom($feedback);
$HTML->footer(array());
?>
