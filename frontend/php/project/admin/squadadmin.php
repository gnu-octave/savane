<?php
# Manage squads.
# 
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
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


## NOTE: for now, squads are group specific. However, as squads reuse the
# users code, we could easily imagine to share squads among different projects

require_once('../../include/init.php');
require_once('../../include/account.php');

register_globals_off();
extract(sane_import('request', array('squad_id')));
extract(sane_import('post', array('update', 'update_general',
  'form_id', 'form_loginname', 'form_realname',
  'update_delete_step1',
  'update_delete_step2', 'deletionconfirmed', 'squad_id_to_delete',
  'add_to_squad', 'user_ids',
  'remove_from_squad')));
session_require(array('group'=>$group_id,'admin_flags'=>'A'));
if (!$group_id) 
{
  exit_no_group();
} 

if (!$squad_id) 
{
  ### No argument? List existing squads, allows to create one

  # Check if the user submitted something (if he wants to create a squad)
  if ($update)
    {
      if (form_check($form_id))
	{
	  if (!$form_loginname)
	    { fb(_("You must supply a username."),1); }
	  if (!$form_realname)
	    { fb(_("You must supply a non-empty real name."),1); }

	  if ($form_loginname && $form_realname)
	    {
              # Try to validate
	      $valid = true;
	      
	      if (!account_namevalid($form_loginname))
		{
                  # feedback included by the check function
		  $valid = false;
		}

	      if ($valid && db_numrows(db_execute("SELECT user_id FROM user WHERE "
						  . "user_name LIKE ?",
						  array($groupx.'-'
                                                        .$form_loginname))) > 0)
		{
		  fb(_("That username already exists."),1);
		  $valid = false;
		}
	      
	      if ($valid && db_numrows(db_execute("SELECT group_list_id "
                                                  . "FROM mail_group_list WHERE "
						  . "list_name LIKE ?",
						  array($group.'-'
                                                        .$form_loginname))) > 0)
		{
		  fb(
_("That username is blocked to avoid conflict with mailing-list addresses."),1);
		  $valid = false;
		}


	      if ($valid)
		{
                 # If at this point parameters are still valid, create the squad
		  $result = db_autoexecute('user',
                    array(
                      'user_name' => strtolower($group."-".$form_loginname),
		      'user_pw' => 'ignored',
		      'realname' => $form_realname,
		      'email' => $GLOBALS['sys_mail_replyto'].'@'
                                 .$GLOBALS['sys_mail_domain'],
		      'add_date' => time(),
		      'status' => 'SQD',
		      'email_hide' => 1,
		    ), DB_AUTOQUERY_INSERT);
		  if (db_affected_rows($result) > 0)
		    { 
		      fb(_("Squad created"));
		      $created_squad_id = db_insertid($result);
		      
                      # Now assign the squad to the group
		      member_add($created_squad_id, $group_id, 'SQD');

		      # Unset variables so the form below will be empty
		      $form_id = null;
		      $form_loginname = null;
		      $form_realname = null;
		      
		    }
		  else
		    { fb(_("Error during squad creation")); }
		  
		}
	    }
	}
    }

  # Requested squad deletion, step2
  if ($update_delete_step2 && $deletionconfirmed == "yes")
    {
      $squad_id_to_delete = $squad_id_to_delete;
      $delete_result = db_execute("SELECT user.user_name AS user_name,"
        . "user.realname AS realname, "
        . "user.user_id AS user_id "
	. "FROM user,user_group WHERE "
	. "user.user_id=? AND user_group.group_id=? "
        . "AND user_group.admin_flags='SQD' "
	. "ORDER BY user.user_name", array($squad_id_to_delete, $group_id));
      
      if (!db_numrows($delete_result))
	{ exit_error(_("Squad not found")); }

      fb(_("Squad deleted"));
      member_remove($squad_id_to_delete, $group_id);
    }


  # Print the page

  $result = db_execute("SELECT user.user_name AS user_name,"
    . "user.realname AS realname, "
    . "user.user_id AS user_id "
    . "FROM user,user_group WHERE "
    . "user.user_id=user_group.user_id AND user_group.group_id=? "
    . "AND user_group.admin_flags='SQD' "
    . "ORDER BY user.user_name", array($group_id));
  $rows = db_numrows($result);

  site_project_header(array('title'=>_("Manage Squads"),
                            'group'=>$group_id,'context'=>'ahome'));

  print '<p>'
._("Squads can be assigned items, share permissions. Creating squads is useful
if you want to assign some items to several members at once.").'</p>
';
  
  print '<h2 id="form">'._("Squads List").'</h2>
';

  if ($rows < 1)
    {
      print '<p class="warn">'._("None found").'</p>
';
    }
  else
    {
      print '<ul>
';
      while ($squad= db_fetch_array($result)) 
	{
	  print '<li><a href="?squad_id='.$squad['user_id'].'&amp;group_id='
                .$group_id.'">'.$squad['realname'].' &lt;'
                .$squad['user_name'].'&gt;</a></li>
';
	}
      print '</ul>
';
    }

  # Limit squad creation to the group size (yes, one can easily override this
  # restriction by creating fake users, but the point is only to incitate
  # to create squads only if necessary, not to really enforce something 
  # important)
  print '<h2>'._("Create a New Squad").'</h2>
';
  
  if ($rows < db_numrows(db_execute("SELECT user_id FROM user_group "
                                    ."WHERE group_id=? AND admin_flags<>'P' "
                                    ."AND admin_flags<>'SQD'", array($group_id))))
    {  
      print form_header($_SERVER["PHP_SELF"].'#form', $form_id);
      print form_input("hidden", "group_id", $group_id);
      print '<p><span class="preinput">'._("Squad Login Name:")
            .'</span><br />&nbsp;&nbsp;';
      print $group."-".form_input("text", "form_loginname", $form_loginname)
            .'</p>
';
      
      print '<p><span class="preinput">'._("Squad Full Name:").'</span>
<br />&nbsp;&nbsp;';
      print form_input("text", "form_realname", $form_realname).'</p>
'; 
      
      print form_footer();
    }
  else
    {
      print '<p class="warn">'
._("You cannot have more squads than members").'</p>
';
    }

}
else
{
### A squad passed as argument? Allow to add and remove member, to
# change the squad name or to delete it
  
  
  $sql = "SELECT user.user_name AS user_name,"
    . "user.realname AS realname, "
    . "user.user_id AS user_id "
    . "FROM user,user_group WHERE "
    . "user.user_id=? AND user_group.group_id=? AND user_group.admin_flags='SQD' "
    . "ORDER BY user.user_name";
  $result = db_execute($sql, array($squad_id, $group_id));

  if (!db_numrows($result))
    { exit_error(_("Squad not found")); }

  # Update of general info
  if ($update_general)
    {
      if (!$form_realname)
	{ fb(_("You must supply a non-empty real name."),1); }
      else
	{ 
	  $result_update = db_execute("UPDATE user SET realname=? WHERE user_id=?",
				      array($form_realname, $squad_id));
	  if (db_affected_rows($result_update) > 0)
	    { 
	      fb(_("Squad name updated")); 
	      group_add_history('Squad name update',
				db_result($result, 0, 'realname'),
				$group_id);
	      
	      # Update the result query with the new name
	      $result = db_execute($sql, array($squad_id, $group_id));
	    }
	}
    }
  
  # Request squad deletion
  if ($update_delete_step1)
    {
      site_project_header(array('title'=>_("Manage Squads"),
                          'group'=>$group_id,'context'=>'ahome'));
      print '<p>'
._('This action cannot be undone, the squad login name will no longer be
available.').'</p>
';

      print form_header($_SERVER["PHP_SELF"]);
      print form_input("hidden", "group_id", $group_id);
      # do not pass the squad id as $squad_id, because if $squad_id is defined
      # the software will try show the squad details, even if it has been
      # removed, while we want the list of existing squads
      print form_input("hidden", "squad_id_to_delete", $squad_id);
      print '<p><span class="preinput">'
._("Do you really want to delete this squad account?").'</span>
<br />&nbsp;&nbsp;';
      print form_input("checkbox", "deletionconfirmed", "yes")
.' '._("Yes, I really do").'</p>';
      print form_submit(_("Update"), "update_delete_step2");
      site_project_footer(array());
      exit;
    }

  # Add members to the squad
  if ($add_to_squad && $user_ids)
    {
      foreach ($user_ids as $user) {
	if (member_squad_add($user, $squad_id, $group_id)) 
# TRANSLATORS: the argument is user's name.
	  { fb(sprintf(_("User %s added to the squad."), user_getname($user))); }
	else
# TRANSLATORS: the argument is user's name.
	  { fb(sprintf(_("User %s is already part of the squad."),
                       user_getname($user)),1); }
      }
    }

  # Remove members from the squad
  if ($remove_from_squad && $user_ids)
    {
      foreach ($user_ids as $user) {
	if (member_squad_remove($user, $squad_id, $group_id)) 
# TRANSLATORS: the argument is user's name.
	  { fb(sprintf(_("User %s removed from the squad."), user_getname($user))); }
	else
# TRANSLATORS: the argument is user's name.
	  { fb(sprintf(_("User %s is not part of the squad."), user_getname($user)),1); }
      }
    }

  site_project_header(array('title'=>_("Manage Squads"),'group'=>$group_id,
                      'context'=>'ahome'));
  
  ## GENERAL
  print form_header($_SERVER["PHP_SELF"]);
  print form_input("hidden", "group_id", $group_id);
  print form_input("hidden", "squad_id", $squad_id);
  print '<p><span class="preinput">'._("Real Name:")
        .'</span><br />&nbsp;&nbsp;';
  print form_input("text", "form_realname", db_result($result, 0, 'realname'))
        .' &lt;'.db_result($result, 0, 'user_name').'&gt;</p>
';
  print form_submit(_("Update"), "update_general").' '
        .form_submit(_("Delete Squad"), "update_delete_step1").'</form>
';


  ## REMOVE USERS
  print '<h2>'._("Removing members").'</h2>
';

  $result_delusers = db_execute("SELECT user.user_id AS user_id, "
			       . "user.user_name AS user_name, "
			       . "user.realname AS realname "
			       . "FROM user,user_squad "
			       . "WHERE user.user_id=user_squad.user_id "
                               . "AND user_squad.squad_id=?"
			       . " ORDER BY user.user_name", array($squad_id));

  print "<p>"
._("To remove members from the squad, select their names and push the button
below.")."</p>\n";
  print form_header($_SERVER["PHP_SELF"]);
  print form_input("hidden", "group_id", $group_id);
  print form_input("hidden", "squad_id", $squad_id);
  print '&nbsp;&nbsp;<select name="user_ids[]" size="10" multiple="multiple">';
  $exists = false;
  $already_in_squad = array();
  while ($thisuser = db_fetch_array($result_delusers)) 
    {
      print '<option value="'.$thisuser['user_id'].'">'.$thisuser['realname']
	.' &lt;'.$thisuser['user_name'].'&gt;</option>';
      $already_in_squad[$thisuser['user_id']] = true;
      $exists=true;
    }
  
  if (!$exists) {
    # Show none if the list is empty
    print '<option>'._("None found").'</option>';
  }
  print '</select>';
  print '<br />
'.form_submit(_("Remove Members"), "remove_from_squad").'</form>
';
  
  ## ADD USERS
  print '<h2>'._("Adding members").'</h2>
';
  
  $result_addusers =  db_execute("SELECT user.user_id AS user_id, "
			       . "user.user_name AS user_name, "
			       . "user.realname AS realname "
			       . "FROM user,user_group "
			       . "WHERE user.user_id=user_group.user_id "
                               . "AND user_group.group_id=? "
			       . "  AND admin_flags<>'P' AND admin_flags<>'SQD' "
			       . "ORDER BY user.user_name", array($group_id));
  
  print "<p>"
._("To add members to the squad, select their name and push the button below.")
."</p>\n";
  print form_header($_SERVER["PHP_SELF"]);
  print form_input("hidden", "group_id", $group_id);
  print form_input("hidden", "squad_id", $squad_id);
  print '&nbsp;&nbsp;<select name="user_ids[]" size="10" multiple="multiple">';
  unset($exists);
  while ($thisuser = db_fetch_array($result_addusers)) 
    {
      # Ignore if previously found as member
      if (array_key_exists($thisuser['user_id'], $already_in_squad))
	{ continue; }
      
      print '<option value="'.$thisuser['user_id'].'">'
            .$thisuser['realname'].' &lt;'.$thisuser['user_name'].'&gt;</option>
';
      $exists=1;
    }
  
  if (!$exists) {
    # Show none if the list is empty
    print '<option>'._("None found").'</option>
';
  }
  print '</select>
';
  print '<br />
'.form_submit(_("Add Members"), "add_to_squad").'</form>
';

  print '<h2>'._("Setting permissions").'</h2>
';

  print '<p><a href="userperms.php?group='.$group.'#'
        .db_result($result, 0, 'user_name').'">'
        ._("Set Permissions").'</a></p>
';
}
site_project_footer(array());
?>
