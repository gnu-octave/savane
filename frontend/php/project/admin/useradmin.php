<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2003-2005 (c) Frederik Orellana <frederik.orellana--cern.ch>
#                          Derek Feichtinger <derek.feichtinger--cern.ch>
#                          Mathieu Roy <yeupou--gnu.org>
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

require_once('../../include/init.php');

session_require(array('group'=>$group_id,'admin_flags'=>'A'));
if (!$group_id) 
{
  exit_no_group();
} 

function show_pending_users_list ($result, $group_id)
{
  print "<h3>"._("Users Pending for Group")."</h3>
	<p>
        "._("Users that have requested to be member of the group are listed here. To approve their requests, select their name and click on the button below. To discard requests, go to the next section called \"Removing users from group\".")."
	<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
	<input type=\"HIDDEN\" name=\"action\" VALUE=\"approve_for_group\" />
  <select name=\"user_id[]\" size=\"10\" multiple>\n";

  while ($usr = db_fetch_array($result)) {
    print "<option value=".$usr[user_id].">".$usr[realname].
      " &lt;".$usr[user_name]."&gt;</option>\n";
    $exists=1;
  }

  if (!$exists)
    {
      # Show none if the list is empty
      print '<option>'._("None found").'</option>';
    }

  print "</select>
	<input type=\"HIDDEN\" name=\"group_id\" VALUE=\"$group_id\" />
	<P>
	<input type=\"submit\" name=\"Submit\" value=\""._("Approve users for group")."\" />
	</form>";
}


function show_all_users_remove_list ($result, $result2, $group_id)
{
  print "
        <h3>"._("Removing users from group")."</h3>
	<p>
        "._("To remove users, select their name and click on the button below. The administrators of a project cannot be removed unless they quit. Pending users are at the bottom of the list.")."
	<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
	<input type=\"HIDDEN\" name=\"action\" VALUE=\"remove_from_group\" />
  <select name=\"user_id[]\" size=\"10\" multiple>\n";

  while ($usr = db_fetch_array($result)) {
    if (!member_check($usr[user_id], $group_id, "A"))
      {
	print "<option value=".$usr[user_id].">".$usr[realname].
	  " &lt;".$usr[user_name]."&gt;</option>\n";
	$exists=1;
      }
  }

  while ($usr = db_fetch_array($result2)) {
    if (!member_check($usr[user_id], $group_id, "A"))
      {
	print "<option value=".$usr[user_id].">"._("Pending:")." ".$usr[realname].
	  " &lt;".$usr[user_name]."&gt;</option>\n";
	$exists=1;
      }
  }

  if (!$exists) {
    # Show none if the list is empty
    print '<option>'._("None found").'</option>';
  }

  print "</select>
	<br />
	<input type=\"HIDDEN\" name=\"group_id\" VALUE=\"$group_id\" />
	<p>
	<input type=\"submit\" name=\"Submit\" value=\""._("Remove users from group")."\" />
	</form>";

}

function show_all_users_add_searchbox ($group_id, $previous_search)
{
  print '
        <h3><a name="searchuser"></a>'._("Adding users to group").'</h3>
	<p>
        '._("You can search one or several users to add in the whole users database with the following search tool. A list of users, depending on the names you'll type in this form, will be generated.").'

	<form action="'.$_SERVER['PHP_SELF'].'#searchuser" method="post">
	<input type="hidden" name="action" value="add_to_group_list" />
        <input type="text" size="35" name="words" value="'.$previous_search.'" /><br />
	<p>
	<input type="hidden" name="group_id" value="'.$group_id.'" />
	<input type="submit" name="Submit" value="'._("Search users").'" />
	</form>';
}



function show_all_users_add_list ($result, $group_id)
{
  print _("Below is the result of your search in the users database.")."

	<P></P>
	<form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
	<input type=\"HIDDEN\" name=\"action\" VALUE=\"add_to_group\" />
  <select name=\"user_id[]\" size=\"10\" multiple>\n";

  while ($usr = db_fetch_array($result)) {
      print "<option value=".$usr[user_id].">".$usr[realname].
	" &lt;".$usr[user_name]."&gt;</option>\n";
      $exists=1;
  }

  if (!$exists) {
    # Show none if the list is empty
    print '<option>'._("None found").'</option>';
  }

  print "</select>
	<br />
	<input type=\"HIDDEN\" name=\"group_id\" VALUE=\"$group_id\" />
	<p>
	<input type=\"submit\" name=\"Submit\" value=\""._("Add users to group")."\" />
	</form>";
}



# Administrative functions

# Add a user to this group
if ($action=='add_to_group' && $user_id) {
  $sys_approved_user_permissions = $GLOBALS['sys_approved_user_permissions'];
  foreach ($user_id as $user) {
    member_add($user, $group_id);
    fb(sprintf(_("User %s added to the project."), user_getname($user)));
  }
}

# Remove a user from this group
if ($action=='remove_from_group' && $user_id) {
  foreach ($user_id as $user) {
    # Check if the users about to be removed are not admins
    if (!member_check($user, $group_id, "A")) {
      member_remove($user, $group_id);
      fb(sprintf(_("User %s deleted from the project."), user_getname($user)));
    }
  }
}

# Approve a user for this group
if ($action=='approve_for_group' && $user_id) {
  foreach ($user_id as $user) {
    member_approve($user, $group_id);
    if($email=user_get_email($user)){
      # As mail content sent to a user different from the one browsing the 
      # page, this cannot be translated.
      $title = "Project membership approved";
      $message = sprintf("You've been approved as a member of the group %s on %s,\nwhere you are registered as %s.", group_getname($group_id), $GLOBALS['sys_name'], user_getname($user)) . "\n\n".
	 sprintf("-- the %s team.", $GLOBALS['sys_name'])."\n";
      $message = sprintf("You've been approved as a member of the group %s on %s,\nwhere you are registered as %s.", group_getname($group_id), $GLOBALS['sys_name'], user_getname($user)) . "\n\n".
	sprintf("-- the %s team.",$GLOBALS['sys_name'])."\n";
#      $title = _("Project membership approved");
#      $message = sprintf(_("You've been approved as a member of the group %s on %s,\nwhere you are registered as %s."), group_getname($group_id), $GLOBALS['sys_name'], user_getname($user)) . "\n\n".
#	 sprintf(_("-- the %s team."),$GLOBALS['sys_name'])."\n";
#      $message = sprintf(_("You've been approved as a member of the group %s on %s,\nwhere you are registered as %s."), group_getname($group_id), $GLOBALS['sys_name'], user_getname($user)) . "\n\n".
#	 sprintf(_("-- the %s team."),$GLOBALS['sys_name'])."\n";

      sendmail_mail($GLOBALS['sys_replyto'] . "@".$GLOBALS['sys_lists_domain'],
		    $email,
		    $title,
		    $message);
    }
  }
}

############
# Start the page
site_project_header(array('title'=>_("Manage Members"),'group'=>$group_id,'context'=>'ahome'));


# Show a form so a user can be approved for this group

$result =  db_query("SELECT user.user_id AS user_id, "
		    . "user.user_name AS user_name, "
		    . "user.realname AS realname "
		    . "FROM user,user_group "
		    . "WHERE user.user_id=user_group.user_id AND user_group.group_id='$group_id' AND admin_flags='P'"
		    . "ORDER BY user.user_name");

show_pending_users_list($result, $group_id);

print '<br />';

# Show a form so a user can be removed from this group
$result =  db_query("SELECT user.user_id AS user_id, "
		    . "user.user_name AS user_name, "
		    . "user.realname AS realname "
		    . "FROM user,user_group "
		    . "WHERE user.user_id=user_group.user_id AND user_group.group_id=$group_id AND admin_flags<>'A' AND admin_flags<>'P' AND admin_flags<>'SQD'"
		    . "ORDER BY user.user_name");

$result2 =  db_query("SELECT user.user_id AS user_id, "
		     . "user.user_name AS user_name, "
		     . "user.realname AS realname "
		     . "FROM user,user_group "
		     . "WHERE user.user_id=user_group.user_id AND user_group.group_id=$group_id AND admin_flags='P' AND admin_flags<>'SQD'"
		     . "ORDER BY user.user_name");

show_all_users_remove_list($result, $result2, $group_id);

print '<br />';

# Show a form so a user can be added to this group

# Query to find users
$array = explode(" ",$words);
$words1=implode($array,"%' OR user_name LIKE '%");
$words2=implode($array,"%' OR realname LIKE '%");
$result = db_query("SELECT user_id, user_name, realname "
		   . "FROM user "
		   . "WHERE ((user_name LIKE '%$words1%') OR (realname LIKE '%$words2%')) AND (status='A') ORDER BY user_name LIMIT 0,26");

show_all_users_add_searchbox($group_id, $words);

if ($words) {
  show_all_users_add_list($result, $group_id);
}

site_project_footer(array());
