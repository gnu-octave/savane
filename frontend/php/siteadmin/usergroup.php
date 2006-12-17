<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
// 
//   Copyright 1999-2000 (c) The SourceForge Crew
// 
// The Savane project is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// The Savane project is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with the Savane project; if not, write to the Free Software
// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


require "../include/pre.php";    
require  $GLOBALS['sys_www_topdir']."/include/account.php";
session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>'Admin: User Info'));

// user remove from group
if ($action=='remove_user_from_group') {
	/*
		Remove this user from this group
	*/

	$result = db_query("DELETE FROM user_group WHERE user_id='$user_id' AND group_id='$group_id'");
	if (!$result || db_affected_rows($result) < 1) {
		' Error Removing User ';
		echo db_error();
	} else {
		' Successfully removed user ';
	}

} else if ($action=='update_user_group') {
	/*
		Update the user/group entry
	*/

	$result = db_query("UPDATE user_group SET admin_flags='$admin_flags' "
		. "WHERE user_id=$user_id AND "
		. "group_id=$group_id");
	if (!$result || db_affected_rows($result) < 1) {
		' Error Updating User_group ';
		echo db_error();
	} else {
		' Successfully updated user_group ';
	}


} else if ($action=='update_user') {
	/*
		Update the user
	*/

	$result=db_query("UPDATE user SET email='$email' WHERE user_id=$user_id");
	if (!$result || db_affected_rows($result) < 1) {
		' Error Updating User ';
		echo db_error();
	} else {
		' Successfully updated user ';
	}

} else if ($action=='add_user_to_group') {
	/*
		Add this user to a group
	*/
	$result=db_query("INSERT INTO user_group (user_id,group_id) VALUES ($user_id,$group_id)");
	if (!$result || db_affected_rows($result) < 1) {
		' Error adding User to group ';
		echo db_error();
	} else {
		' Successfully added user to group ';
	}
}

// get user info
$res_user = db_query("SELECT * FROM user WHERE user_id=$user_id");
$row_user = db_fetch_array($res_user);

?>
<p>
Savannah User Group Edit for user: <strong><?php print $user_id . ": " . user_getname($user_id); ?></strong>
<p>
Account Info:
<FORM method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<INPUT type="hidden" name="action" value="update_user">
<INPUT type="hidden" name="user_id" value="<?php print $user_id; ?>">

<P>
<INPUT TYPE="TEXT" NAME="email" VALUE="<?php echo $row_user[email]; ?>" SIZE="25" MAXLENGTH="55">

<P>
<INPUT type="submit" name="Update_Unix" value="Update">
</FORM>

<HR>

<p>
<H2>Current Groups:</H2>
<br />
&nbsp;

<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
/*
	Iterate and show groups this user is in
*/
$res_cat = db_query("SELECT groups.group_name AS group_name, "
	. "groups.group_id AS group_id, "
	. "user_group.admin_flags AS admin_flags FROM "
	. "groups,user_group WHERE user_group.user_id=$user_id AND "
	. "groups.group_id=user_group.group_id");

	while ($row_cat = db_fetch_array($res_cat)) {
		print ("<br /><hr><strong>" . group_getname($row_cat[group_id]) . "</strong> "
			. "<a href=\"usergroup.php?user_id=$user_id&action=remove_user_from_group&group_id=$row_cat[group_id]\">"
			. "[Remove User from Group]</a>");
		// editing for flags
		?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<INPUT type="hidden" name="action" value="update_user_group">
		<input name="user_id" type="hidden" value="<?php print $user_id; ?>">
		<input name="group_id" type="hidden" value="<?php print $row_cat[group_id]; ?>">
		<br />
		Admin Flags: 
		<BR>
		<input type="text" name="admin_flags" value="<?php print $row_cat[admin_flags]; ?>">
		<BR>
		<input type="submit" name="Update_Group" value="Update" />
		</form>
		<?php
	}

/*
	Show a form so a user can be added to a group
*/
?>
<hr>
<P>
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
<INPUT type="hidden" name="action" value="add_user_to_group">
<input name="user_id" type="hidden" value="<?php print $user_id; ?>">
<p>
Add User to Group (group_id):
<br />
<input type="text" name="group_id" LENGTH="4" MAXLENGTH="5" />
<p>
<input type="submit" name="Submit" value="Submit" />
</form>

<P><A href="user_changepw.php?user_id=<?php print $user_id; ?>">[Change User PW]</A>

<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
html_feedback_bottom($feedback);
$HTML->footer(array());

?>
