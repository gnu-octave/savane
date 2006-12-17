<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2000-2003 (c) Free Software Foundation
#                          Mathieu Roy <yeupou--gnu.org>
#
#  Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
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


require "../../include/pre.php";

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

# Internal function to determine if a squad permission must override user perm
# or not
#  * If user got lower rights, we override, because we assume that user has
# been added to obtain at least the rights given the squad
#  * If user got higher rights, we let as it is because the user may be
# member of a squad that provides somes rights to its members but being
# himself someone that have other duties requiring in some cases more
# rights.
function _compare_perms ($squad_perm, $user_perm)
{
  # We have to do some subtle comparisons because, unfortunately, perms
  # flags have a long history and are not completely consistant: higher does
  # not always mean better
  #   NULL = use default (group or group type)
  #   9 = none
  #   1 = technician
  #   3 = manager
  #   2 = technician & manager

  # if both perms are equal, dont bother checking further
  if ($squad_perm == $user_perm)
    { return $user_perm; }

  # if squad perm is 9 (none), keep user perm anyway
  if ($squad_perm == "9")
    { return $user_perm; }

  # if user perm is 9 (none) or NULL (group default), take it squad perm
  # (that cannot be 9, excluded already)
  if ($user_perm == "9")
    { 
      $GLOBALS['did_squad_override'] = true;
      return $squad_perm; 
    }

  # If user perm or squad perm is 2 (techn and manager), 
  # there is nothing higher, take it
  if ($user_perm == "2" || $squad_perm == "2")
    { 
      if ($squad_perm == "2")
	{ $GLOBALS['did_squad_override'] = true; }
      return "2"; 
    }

  # if user perm and squad perm are 1 (techician) and  3 (manager), assume
  # that the result is that the user should be 2 (both technician and manager)
  if (($user_perm == "1" && $squad_perm == "3") || 
      ($squad_perm == "1" && $user_perm == "3"))
    { 
      $GLOBALS['did_squad_override'] = true;
      return "2"; 
    }

  # If we end here, nothing conclusive, keep the user perm
  return $user_perm;
}

if ($update)
{
  # ##### Update members permissions
  unset($feedback_able, $feedback_unable, $feedback_squad_override);


  # Get the members list, taking first the squads
  $res_dev = db_query("select user_id,admin_flags FROM user_group WHERE group_id=$group_id AND admin_flags<>'P' ORDER BY admin_flags DESC");

  # Save the squads permissions to override users permissions if necessary
  $squad_permissions = array();

  while ($row_dev = db_fetch_array($res_dev))
    {
      $is_squad = false;
      $name = user_getname($row_dev['user_id']);

      # site admins are not allowed to changer their own user rights
      # on a project they are member of.
      # It creates issues (flags erroneously set).
      # They should use admin interface instead or end su session
      if (user_is_super_user() && $row_dev['user_id'] == user_getid())
	{
	  fb(sprintf(_("Configuration for user #%s (you!) ignored to avoid incoherent flags status. End the superuser session to change your settings in this group or use the admin user interface."), $row_dev['user_id']), 1);
	  continue;
	}


      # admin are not allowed to turn off their own admin flag
      # it is too dangerous -- set it back to 'A'
      $admin_flags="admin_user_$row_dev[user_id]";
      if (user_getid() == $row_dev['user_id'])
	{
	  $$admin_flags='A';
	}
      # squads flag cannot be changed, squads should not be turned into normal
      # users
      if ($row_dev['admin_flags'] == 'SQD')
	{ 
	  $$admin_flags='SQD'; 
	  $is_squad = true;
	}
 

      # If someone is made admin, he got automatically the right to read
      # private items
      $privacy_flags="privacy_user_$row_dev[user_id]";
      if ($$admin_flags == "A")
	{ $$privacy_flags='1'; }

      $bugs_flags="bugs_user_$row_dev[user_id]";
      $task_flags="task_user_$row_dev[user_id]";
      $patch_flags="patch_user_$row_dev[user_id]";
      $support_flags="support_user_$row_dev[user_id]";
      $cookbook_flags="cookbook_user_$row_dev[user_id]";
      $news_flags="news_user_$row_dev[user_id]";

      if ($is_squad)
	{
	  # If it is a squad, save every setting even if useless, it cost
	  # nothing
	  $squad_id = $row_dev['user_id'];
	  $squad_permissions[$squad_id.'bugs'] = $$bugs_flags;
	  $squad_permissions[$squad_id.'task'] = $$task_flags;
	  $squad_permissions[$squad_id.'patch'] = $$patch_flags;
	  $squad_permissions[$squad_id.'support'] = $$support_flags;
	  $squad_permissions[$squad_id.'cookbook'] = $$cookbook_flags;
	  $squad_permissions[$squad_id.'news'] = $$news_flags;
	  $squad_permissions[$squad_id.'privacy'] = $$privacy_flags;	  
	}
      else
	{
	  # If it is not a squad, we then have to check if the user is 
	  # member of any squad, and if he his, we have to check which
	  # setting must be kept (see _compare_perms comments) 
	  $result_user_squads = db_query("SELECT squad_id FROM user_squad WHERE user_id='".$row_dev['user_id']."' AND group_id='".safeinput($group_id)."'");
	  if (db_numrows($result_user_squads))
	    {
	      while ($thissquad = db_fetch_array($result_user_squads)) 
		{
		  $GLOBALS['did_squad_override'] = false;
		  $$bugs_flags = 
		    _compare_perms($squad_permissions[$thissquad['squad_id'].'bugs'], 
				   $$bugs_flags);
		  $$task_flags = 
		    _compare_perms($squad_permissions[$thissquad['squad_id'].'task'], 
				   $$task_flags);
		  
		  $$patch_flags = 
		    _compare_perms($squad_permissions[$thissquad['squad_id'].'patch'], 
				   $$patch_flags);
		  $$support_flags = 
		    _compare_perms($squad_permissions[$thissquad['squad_id'].'support'], 
				   $$support_flags);
		  $$cookbook_flags = 
		    _compare_perms($squad_permissions[$thissquad['squad_id'].'cookbook'], 
				   $$cookbook_flags);
		  $$news_flags = 
		    _compare_perms($squad_permissions[$thissquad['squad_id'].'news'], 
				   $$news_flags);

		  if ($squad_permissions[$thissquad['squad_id'].'privacy'] > $$privacy_flags)
		    { 
		      $GLOBALS['did_squad_override'] = true;
		      $$privacy_flags = 
			$squad_permissions[$thissquad['squad_id'].'privacy'];
		    }

		  # Record any squad override for later generated feedback
		  if ($GLOBALS['did_squad_override'])
		    { $feedback_squad_override = $name.", "; }
		  
		}
	    }
	  
	}

      $sql = 'UPDATE user_group SET '
	."admin_flags='".safeinput($$admin_flags)."',"
	."privacy_flags='".safeinput($$privacy_flags)."',"
	."cookbook_flags=".safeinput($$cookbook_flags).",";

      if ($project->Uses("bugs")) 
	{
	  $sql .= "bugs_flags=".safeinput($$bugs_flags).",";
	}
      if ($project->Uses("news")) 
	{
	  $sql .= "news_flags=".safeinput($$news_flags).",";
	}
      if ($project->Uses("task")) 
	{
	  $sql .= "task_flags=".safeinput($$task_flags).",";
	}
      if ($project->Uses("patch")) 
	{
	  $sql .= "patch_flags=".safeinput($$patch_flags).",";
	}
      if ($project->Uses("support")) 
	{
	  $sql .= "support_flags=".safeinput($$support_flags).",";
	}
      $sql = rtrim($sql, ",");
      $sql .= " WHERE user_id='$row_dev[user_id]' AND group_id='$group_id'";

      $result = db_query($sql);

      # Notice any change, yell on error, keep silent if no changes was 
      # necessary (if db_affected_rows works normally, which does not seems
      # to always be the case)
      if ($result && db_affected_rows($result))
	{
	  if ($is_squad)
	    { $string = 'Changed Squad Permissions'; }
	  else
	    { $string = 'Changed User Permissions'; }
	  group_add_history($string,
			    $name,
			    $group_id);
	  $feedback_able .= $name.", ";
	}
      elseif (!$result)
	{
	  $feedback_unable .= $name.", ";
	}
    }

  if ($feedback_able)
    {
      fb(sprintf(_("permissions of %s updated."), rtrim($feedback_able, ', ')));

    }
  if ($feedback_squad_override)
    {
      fb(sprintf(_("personal permissions of %s were overridden by squad permissions."), rtrim($feedback_squad_override, ', ')));
    }

  if ($feedback_unable)
    {
      fb(sprintf(_("failed to update %s permissions."), rtrim($feedback_unable, ', ')),1);
    }



  # ##### Update group default permissions

  $bugs_flags="bugs_user_";
  $task_flags="task_user_";
  $patch_flags="patch_user_";
  $support_flags="support_user_";
  $cookbook_flags="cookbook_user_";
  $news_flags="news_user_";

  # If the group entry do not exists, create it
  if (!db_result(db_query("SELECT groups_default_permissions_id FROM groups_default_permissions WHERE group_id='$group_id'"), 0, "groups_default_permissions_id"))
    {
      db_query("INSERT INTO groups_default_permissions (group_id) VALUES ($group_id)");
    }

  # Update the table
  $sql = 'UPDATE groups_default_permissions SET '
    ."cookbook_flags=".safeinput($$cookbook_flags).",";
  
  if ($project->Uses("bugs")) 
    {
      $sql .= "bugs_flags=".safeinput($$bugs_flags).",";
    }
  if ($project->Uses("news")) 
    {
      $sql .= "news_flags=".safeinput($$news_flags).",";
    }
  if ($project->Uses("task")) 
    {
      $sql .= "task_flags=".safeinput($$task_flags).",";
    }
  if ($project->Uses("patch")) 
    {
      $sql .= "patch_flags=".safeinput($$patch_flags).",";
    }
  if ($project->Uses("support")) 
    {
      $sql .= "support_flags=".safeinput($$support_flags).",";
    }
  $sql = rtrim($sql, ",");
  $sql .= " WHERE group_id='$group_id'";

  $result = db_query($sql);

  if ($result && db_affected_rows($result))
    {
      group_add_history('Changed Group Default Permissions','',$group_id);
      fb(_("Permissions for the group updated."));

    }
  else if (!$result)
    {
      fb(_("Unable to change group defaults permissions."), 1);
    }

  # ##### Update posting restrictions
  # (if equal to 0, manually set to NULL, since 0 have a different meaning)
  $newitem_restrict_event1 = "bugs_restrict_event1";
  $newitem_restrict_event2 = "bugs_restrict_event2";
  $bugs_flags = ($$newitem_restrict_event2)*100 + $$newitem_restrict_event1;
  if (!$bugs_flags)
    { $bugs_flags = 'NULL'; }

  $newitem_restrict_event1 = "task_restrict_event1";
  $newitem_restrict_event2 = "task_restrict_event2";
  $task_flags = ($$newitem_restrict_event2)*100 + $$newitem_restrict_event1;
  if (!$task_flags)
    { $task_flags = 'NULL'; }

  $newitem_restrict_event1 = "support_restrict_event1";
  $newitem_restrict_event2 = "support_restrict_event2";
  $support_flags = ($$newitem_restrict_event2)*100 + $$newitem_restrict_event1;
  if (!$support_flags)
    { $support_flags = 'NULL'; }

  $newitem_restrict_event1 = "patch_restrict_event1";
  $newitem_restrict_event2 = "patch_restrict_event2";
  $patch_flags = ($$newitem_restrict_event2)*100 + $$newitem_restrict_event1;
  if (!$patch_flags)
    { $patch_flags = 'NULL'; }

  $newitem_restrict_event1 = "cookbook_restrict_event1";
  $newitem_restrict_event2 = "cookbook_restrict_event2";
  $cookbook_flags = ($$newitem_restrict_event2)*100 + $$newitem_restrict_event1;
  if (!$cookbook_flags)
    { $cookbook_flags = 'NULL'; }

  $news_flags = $news_restrict_event1;
  if (!$news_flags)
    { $news_flags = 'NULL'; }

  # Update the table
  $sql = 'UPDATE groups_default_permissions SET '
    ."bugs_rflags=".safeinput($bugs_flags).","
    ."news_rflags=".safeinput($news_flags).","
    ."cookbook_rflags=".safeinput($cookbook_flags).","
    ."task_rflags=".safeinput($task_flags).", "
    ."patch_rflags=".safeinput($patch_flags).", "
    ."support_rflags=".safeinput($support_flags)." "
    ."WHERE group_id='$group_id'";

  
  $result = db_query($sql);
  
  if ($result && db_affected_rows($result))
    {
      group_add_history('Changed Posting Restrictions','',$group_id);
      fb(_("Posting restrictions updated."));
      
    }
  else if (!$result)
    {
      fb(_("Unable to change posting restrictions."), 1);
    }
}


# start HTML
site_project_header(array('title'=>_("Set Permissions"),'group'=>$group_id,'context'=>'ahome'));

print form_header($_SERVER['PHP_SELF']).
form_input("hidden", "group", $group_name);

########################### POSTING RESTRICTIONS
# Exists also in trackers config (missing for news).

$title_arr=array();
$title_arr[]=_("Applies when ...");
if ($project->Uses("support")) 
{
  $title_arr[]=_("Support Tracker");
}
if ($project->Uses("bugs")) 
{
  $title_arr[]=_("Bug Tracker");
}
if ($project->Uses("task")) 
{
  $title_arr[]=_("Task Tracker");
}
if ($project->Uses("patch")) 
{
  $title_arr[]=_("Patch Tracker");
} 
$title_arr[]=_("Cookbook Manager");
if ($project->Uses("news")) 
{
  $title_arr[]=_("News Manager");
}

print '<h3>'._("Group trackers posting restrictions").'</h3>';

print '<p>';
print _("Here you can set the minimal authentification level required in order to post on the trackers.");
print '</p>';

print html_build_list_table_top ($title_arr);

$i++;
print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td>'._("Posting new items").'</td>';
if ($project->Uses("support")) 
{
  html_select_restriction_box("support", group_getrestrictions($group_id, "support"));
}
if ($project->Uses("bugs")) 
{
  html_select_restriction_box("bugs", group_getrestrictions($group_id, "bugs"));
}
if ($project->Uses("task")) 
{
  html_select_restriction_box("task", group_getrestrictions($group_id, "task"));
}
if ($project->Uses("patch")) 
{
  html_select_restriction_box("patch", group_getrestrictions($group_id, "patch"));
} 
html_select_restriction_box("cookbook", group_getrestrictions($group_id, "cookbook"));
if ($project->Uses("news")) 
{
  html_select_restriction_box("news", group_getrestrictions($group_id, "news"));
}

print '  </tr>';

$i++;
print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td>'._("Posting comments").'</td>';
if ($project->Uses("support")) 
{
  html_select_restriction_box("support", group_getrestrictions($group_id, "support", 2),'', '', 2);
}
if ($project->Uses("bugs")) 
{
  html_select_restriction_box("bugs", group_getrestrictions($group_id, "bugs", 2),'', '', 2);
}
if ($project->Uses("task")) 
{
  html_select_restriction_box("task", group_getrestrictions($group_id, "task", 2),'', '', 2);
}
if ($project->Uses("patch")) 
{
  html_select_restriction_box("patch", group_getrestrictions($group_id, "patch", 2),'', '', 2);
}
html_select_restriction_box("cookbook", group_getrestrictions($group_id, "cookbook", 2),'', '', 2);
if ($project->Uses("news")) 
{
# not yet effective!
  print '<td align="center">---</td>';
#  html_select_restriction_box("news", group_getrestrictions($group_id, "news", 2),'', '', 2);
}
print '  </tr>';
 
print '
</table>
<p class="center">'.form_submit(_("Update Permissions")).'</p>';


########################### GROUP DEFAULTS

$title_arr=array();
if ($project->Uses("support")) 
{
  $title_arr[]=_("Support Tracker");
}
if ($project->Uses("bugs")) 
{
  $title_arr[]=_("Bug Tracker");
}
if ($project->Uses("task")) 
{
  $title_arr[]=_("Task Tracker");
}
if ($project->Uses("patch")) 
{
  $title_arr[]=_("Patch Tracker");
} 
$title_arr[]=_("Cookbook Manager");
if ($project->Uses("news")) 
{
  $title_arr[]=_("News Manager");
}

print '<p>&nbsp;</p>';
print '<h3>'._("Group Default Permissions").'</h3>';
member_explain_roles();
print html_build_list_table_top ($title_arr);

if ($project->Uses("support")) 
{
html_select_permission_box("support", group_getpermissions($group_id, "support"), "group");
}
if ($project->Uses("bugs")) 
{
html_select_permission_box("bugs", group_getpermissions($group_id, "bugs"), "group");
}
if ($project->Uses("task")) 
{
html_select_permission_box("task", group_getpermissions($group_id, "task"), "group");
}
if ($project->Uses("patch")) 
{
html_select_permission_box("patch", group_getpermissions($group_id, "patch"), "group");
}
html_select_permission_box("cookbook", group_getpermissions($group_id, "cookbook"), "group");
if ($project->Uses("news")) 
{
html_select_permission_box("news", group_getpermissions($group_id, "news"), "group");
}

print '  </tr>
</table>
<p class="center">'.form_submit(_("Update Permissions")).'</p>';


########################### PER SQUADS

# Get squads list
$sql = "select user.user_name AS user_name,"
. "user.realname AS realname, "
. "user.user_id AS user_id, "
. "user_group.admin_flags, "
. "user_group.privacy_flags, "
. "user_group.bugs_flags, "
. "user_group.cookbook_flags, "
. "user_group.forum_flags, "
. "user_group.task_flags, "
. "user_group.patch_flags, "
. "user_group.news_flags, "
. "user_group.support_flags "
. "FROM user,user_group WHERE "
. "user.user_id=user_group.user_id AND user_group.group_id=$group_id AND user_group.admin_flags='SQD'"
. "ORDER BY user.user_name";
$result = db_query($sql);

print '<p>&nbsp;</p>';
print '<h3>'._("Permissions per squad").'</h3>';

if (!$result || db_numrows($result) < 1)
{
  print '<p class="warn">'._("No Squads Found").'</p>';
}
else
{

  $title_arr=array();
  $title_arr[]=_("Squad");
  $title_arr[]=_("General Rights");
  if ($project->Uses("support")) 
    {
      $title_arr[]=_("Support Tracker");
    }
  if ($project->Uses("bugs")) 
    {
      $title_arr[]=_("Bug Tracker");
    }
  if ($project->Uses("task")) 
    {
      $title_arr[]=_("Task Tracker");
    }
  if ($project->Uses("patch")) 
    {
      $title_arr[]=_("Patch Tracker");
    } 
  $title_arr[]=_("Cookbook Manager");
  if ($project->Uses("news")) 
    {
      $title_arr[]=_("News Manager");
    }
  
  print '<p>';
  print _("Squad Members will automatically obtain, at least, the Squad permissions.");
  print '</p>';

  print html_build_list_table_top ($title_arr);

  # a function for this specific stuff that do not require generalization

  while ($row = db_fetch_array($result))
   {
     $i++;
     $reprinttitle++;
     if ($reprinttitle == 9)
       {
	 print html_build_list_table_top($title_arr, 0, 0);
	 $reprinttitle = 0;
       }
     print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td align="center"><a name="'.$row['user_name'].'"></a>'.utils_user_link($row['user_name'], $row['realname']).'</td>';
	 print '
    <td class="smaller">';

	 print '
      <input type="checkbox" name="privacy_user_'.$row['user_id'].'" value="1" '.(($row['privacy_flags']=='1')?'checked="checked"':'').' />&nbsp;'._("Private Items");
      
	 print '
    </td>';

     if ($project->Uses("support")) 
       {
	 html_select_permission_box("support", $row);
       }
     if ($project->Uses("bugs")) 
       {
	 html_select_permission_box("bugs", $row);
       }
     if ($project->Uses("task")) 
       {
	 html_select_permission_box("task", $row);
       }
     if ($project->Uses("patch")) 
       {
	 html_select_permission_box("patch", $row);
       }
     html_select_permission_box("cookbook", $row);
     if ($project->Uses("news")) 
       {
	 html_select_permission_box("news", $row);
       }

     print '  </tr>';

   }

  print '
</table>
<p class="center">'.form_submit(_("Update Permissions")).'</p>';

}

########################### PER MEMBERS

$sql = "select user.user_name AS user_name,"
. "user.realname AS realname, "
. "user.user_id AS user_id, "
. "user_group.admin_flags, "
. "user_group.privacy_flags, "
. "user_group.bugs_flags, "
. "user_group.cookbook_flags, "
. "user_group.forum_flags, "
. "user_group.task_flags, "
. "user_group.patch_flags, "
. "user_group.news_flags, "
. "user_group.support_flags "
. "FROM user,user_group WHERE "
. "user.user_id=user_group.user_id AND user_group.group_id=$group_id AND user_group.admin_flags<>'P' AND user_group.admin_flags<>'SQD'"
. "ORDER BY user.user_name";
$result = db_query($sql);

print '<p>&nbsp;</p>';
print '<h3>'._("Permissions per member").'</h3>';

if (!$result || db_numrows($result) < 1)
{
  # Unusual case! No point in changing permissions of an orphaned project
  print '<p class="warn">'._("No Members Found").'</p>';
}
else
{

  $title_arr=array();
  $title_arr[]=_("Member");
  $title_arr[]=_("General Rights");
  if ($project->Uses("support")) 
    {
      $title_arr[]=_("Support Tracker");
    }
  if ($project->Uses("bugs")) 
    {
      $title_arr[]=_("Bug Tracker");
    }
  if ($project->Uses("task")) 
    {
      $title_arr[]=_("Task Tracker");
    }
  if ($project->Uses("patch")) 
    {
      $title_arr[]=_("Patch Tracker");
    } 
  $title_arr[]=_("Cookbook Manager");
  if ($project->Uses("news")) 
    {
      $title_arr[]=_("News Manager");
    }
  print '<p class="warn">';
  print _("Projects Admins are always allowed to read private items.");
  print '</p>';

  print html_build_list_table_top ($title_arr);

  # a function for this specific stuff that do not require generalization

  while ($row = db_fetch_array($result))
   {
     $i++;
     $reprinttitle++;
     if ($reprinttitle == 9)
       {
	 print html_build_list_table_top($title_arr, 0, 0);
	 $reprinttitle = 0;
       }
     print '
  <tr class="'. utils_get_alt_row_color($i) .'">
    <td align="center"><a name="'.$row['user_name'].'"></a>'.utils_user_link($row['user_name'], $row['realname']).'</td>';
	 print '
    <td class="smaller">';
     if ($row['user_id'] == user_getid())
       {
	 print '<em>'._("You are Admin").'</em>';
       }
     else
       {
	 $extra = ($row['admin_flags'] == 'A' ) ?'checked="checked"':'';
	 print form_input("checkbox", "admin_user_".$row['user_id'], "A", $extra).'&nbsp;'._("Admin");
       }
     if ($row['admin_flags'] != 'A')
       {
	 $extra = ($row['privacy_flags'] == '1' ) ?'checked="checked"':'';
	 print '<br />'.form_input("checkbox", "privacy_user_".$row['user_id'], "1", $extra).'&nbsp;'._("Private Items");
       }
     else
       {
	 print form_input("hidden", 'privacy_user_'.$row['user_id'], 1);
       }
     print '
    </td>';

     if ($project->Uses("support")) 
       {
	 html_select_permission_box("support", $row);
       }
     if ($project->Uses("bugs")) 
       {
	 html_select_permission_box("bugs", $row);
       }
     if ($project->Uses("task")) 
       {
	 html_select_permission_box("task", $row);
       }
     if ($project->Uses("patch")) 
       {
	 html_select_permission_box("patch", $row);
       }
     html_select_permission_box("cookbook", $row);
     if ($project->Uses("news")) 
       {
	 html_select_permission_box("news", $row);
       }

     print '  </tr>';

   }

  print '
</table>'.form_footer(_("Update Permissions"));

}

site_project_footer(array());

?>