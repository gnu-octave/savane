<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2003-2006 (c) Frederik Orellana <frederik.orellana--cern.ch>
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

#input_is_safe();
#mysql_is_safe();

require_once('../include/init.php');
require_once('../include/database.php');
require_directory('search');
require_directory('trackers');

# Make this page register global off compliant
register_globals_off();

# Obtain general user info
$res_user = db_execute("SELECT * FROM user WHERE user_id=?", array(user_getid()));
$row_user = db_fetch_array($res_user);

# Obtain approval_user_gen_email() for site specific content
utils_get_content("my/request_for_inclusion");


###################################################################
## Updates

# Watchee add
extract(sane_import('request', array('func', 'watchee_id', 'group_id')));
if ($func)
{
  if ($func == "delwatchee")
    {
# Stop watching another user
      $result_upd = trackers_data_delete_watchees(user_getid(),$watchee_id,$group_id);
      if (!$result_upd)
	{
	  fb(_("Unable to remove user from the watched users list, probably a broken URL"));
	}

    }

  if ($func == "addwatchee")
    {
# Stop watching another user
      $result_upd = trackers_data_add_watchees(user_getid(),$watchee_id,$group_id);
      if (!$result_upd)
	{
	  fb(_("Unable to add user in the watched users list, probably a broken URL"));
	}

    }
}



# ###### function send_pending_user_email($group_id, $user_id, $user_message)
# ###### sends an email to group admins when a user joins group

function send_pending_user_email($group_id, $user_id, $user_message)
{

  $res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));

  if (db_numrows($res_grp) < 1)
    {
      return 0;
    }

  $row_grp = db_fetch_array($res_grp);

  $res_admins = db_execute("SELECT user.user_name FROM user,user_group WHERE "
			 . "user.user_id=user_group.user_id AND user_group.group_id=? AND "
			 . "user_group.admin_flags='A'", array($group_id));

  if (db_numrows($res_admins) < 1)
    {
      return 0;
    }

  # send one email per admin, in one command line coma separated
  $admin_list = '';
  while ($row_admins = db_fetch_array($res_admins))
    {
      $admin_list .= ($admin_list ? ',':'').$row_admins['user_name'];
    }

  $message = approval_user_gen_email($row_grp['group_name'],
				     $row_grp['unix_group_name'],
				     $group_id,
				     user_getname($user_id),
				     user_getrealname($user_id),
				     user_getemail($user_id),
				     $user_message);


  sendmail_mail(user_getname(),
		$admin_list,
		sprintf(_("Membership request for group %s"), $row_grp['group_name']),
		$message,
		$row_grp['unix_group_name'],
		"usermanagement");
}

# Request for inclusion
extract(sane_import('post', array('update', 'form_id', 'form_message', 'form_groups')));
# ($form_groups is an array)
if ($update)
{
  $result_upd = db_query("SELECT group_id FROM groups WHERE status='A' AND is_public='1' ORDER BY group_id");

  # Check for duplicates
  if (!form_check($form_id))
    { return 0; }
  $form_cleaned_already = false;

  while ($val = db_fetch_array($result_upd))
    {
      if (isset($form_groups[$val['group_id']]))
	{
          # If not in group, add user with admin_flag "P"
          # (not very sensible, but this way we avoid changing
          # the table layout)
	  if(!member_check_pending($row_user['user_id'], $val['group_id']))
	    {
	      if(!$form_message)
		{
		  fb(_("When joining you must provide a message for the administrator, a short explanation of why you want to join this/these project(s)."), 1);
		}
	      else
		{
		  if(member_add($row_user['user_id'], $val['group_id'], 'P'))
		    {
		      send_pending_user_email($val['group_id'], $row_user['user_id'], $form_message);
		      if (!$form_cleaned_already)
			{
			  form_clean($form_id);
			  $form_cleaned_already = 1;
			}
		    }
		}
	    }
	  else
	    {
	      fb(_("Request for inclusion already registered"),1);
	    }
	}
    }
}




# ###### get global user and group vars

$result = db_execute("SELECT groups.group_name,"
. "groups.group_id,"
. "groups.unix_group_name,"
. "groups.status,"
. "user_group.admin_flags, "
. "group_history.date "
. "FROM groups,user_group,group_history "
. "WHERE groups.group_id=user_group.group_id "
. "AND user_group.user_id=? "
. "AND groups.status='A' "
. "AND (group_history.field_name='Added User' OR group_history.field_name='Approved User' OR user_group.admin_flags='P')"
. "AND group_history.group_id=user_group.group_id "
. "AND group_history.old_value=? "
. "GROUP BY groups.unix_group_name "
. "ORDER BY groups.unix_group_name",
		     array(user_getid(), user_getname()));
$rows = db_numrows($result);

# Alternative sql that do not use group_history, just in case this history
# would be flawed (history usage has been inconsistent over Savane history)
$result_without_history = db_execute("SELECT groups.group_name,"
. "groups.group_id,"
. "groups.unix_group_name,"
. "groups.status,"
. "user_group.admin_flags "
. "FROM groups,user_group "
. "WHERE groups.group_id=user_group.group_id "
. "AND user_group.user_id=? "
. "AND groups.status='A' "
. "GROUP BY groups.unix_group_name "
. "ORDER BY groups.unix_group_name",
				   array(user_getid()));
$rows_without_history = db_numrows($result_without_history);

if ($rows_without_history != $rows)
{
  # If number of rows differ, assume that history is flawed. Print a
  # feedback incitating to fix the installation and override flawed result
  #
  # The following update script was maybe forgot:
  # update/1.0.6/update_group_history.pl
  fb(_("Groups history appears to be flawed. This is a site installation problem. Please report the incident to administrators, asking them to get in touch with their Savane supplier."), 1);
  $result = $result_without_history;
  $rows = $rows_without_history;
}


###################################################################
# Start HTML

# page header
site_user_header(array('context'=>'mygroups'));

print '<p>'._("Here is the list of groups you are member of, plus a form which allows you to ask for inclusion in a Group. Clicking on the trash permits you to quit a project.").'</p>';

# we get site-specific content
utils_get_content("my/groups");


################ RIGHT PART ###########################

print html_splitpage(1);  # Watching other users.

print $HTML->box_top(_("Watched Partners"));

$result_w = trackers_data_get_watchees(user_getid());
$rows_w=db_numrows($result_w);

if (!$result_w || $rows_w < 1)
    {
      print '<p>'._("You are not watching any partners.").'</p>';
      print '<p>'._("Watching a partner (receiving a copy of all notifications sent to them) permits you to be their backup when they are away from the office, or to review all their activities on a project.");
      print '</p><p>'._("To watch someone, click 'Watch partner' in the project memberlist page. You need to be member of that project yourself.");
      print '<br />';
      print db_error();
    }
else
    {
      print '<table>';
      for ($i=0; $i<$rows_w; $i++)
	{
	  print '<tr class="'.utils_get_alt_row_color($i).'"><td width="99%"><strong>'.
	    utils_user_link(user_getname(db_result($result_w, $i, 'watchee_id')), user_getrealname(db_result($result_w, $i, 'watchee_id'))).
	    '</strong> <span class="smaller">['.group_getname(db_result($result_w, $i, 'group_id')).']'.
	    '</span>';

	  print '</td>'.
	    '<td><a href="'.$_SERVER['PHP_SELF'].'?func=delwatchee&amp;group_id='.db_result($result_w,$i,'group_id').'&amp;watchee_id='.db_result($result_w, $i, 'watchee_id').
	    '" onClick="return confirm(\''._("Stop watching this user?").'\')">'.
	    '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" border="0" alt="'._("Stop watching this user?").'" /></a></td></tr>';
	}
      print '</table>';

    }

  $result_w = trackers_data_get_watchers(user_getid());
  $watchers = '';
  while ($row_watcher = db_fetch_array($result_w))
    {
      $watchers .= utils_user_link(user_getname($row_watcher['user_id']), user_getrealname($row_watcher['user_id'])).' <span class="smaller">['.group_getname($row_watcher['group_id']).']</span>, ';
    }

  if ($watchers)
    {
      $watchers = substr($watchers,0,-2); # remove extra comma at the end
      $watchers .= ".";

      print '<p>';
      printf (_("My own notifications are currently watched by: %s"),$watchers);
      print '</p>';
    }
  else
    {
      print '<p>'._("Nobody is currently watching my own notifications.").'</p>';
    }

print $HTML->box_bottom();

print "<br />\n";

print $HTML->box_top(_("Request for Inclusion"),'',1);

print '<div class="boxitem">'."\n";
  print '<p>';
print _("If there is a project - or several - you would like to be member of, to be able to fully contribute, it is possible to search for the names in the whole group database with the following search tool. A list of groups will be generated, depending on the word(s) typed in this form.")."\n";
print '</p>';

print '
	<form action="'.$_SERVER["PHP_SELF"].'#searchgroup" method="post">
	<input type="hidden" name="action" value="searchgroup" />
        <input type="text" size="35" name="words" value="'.$words.'" /><br />
	<br /><br />
	<input type="submit" name="Submit" value="'
	._("Search Group(s)").'" />
	</form>

</div><!-- end boxitem -->';

extract(sane_import('request', array('words')));
if ($words)
{
  # Avoid to big search by asking for more than 1 characters.
  # Restricting to more than 2 chars skips a great deal of project names (eg: gv, gdb)
  if (strlen($words) > 1)
    {
      $result_search = search_run($words, "soft", 0);
    }
  else
    { $result_search = 0; }

  print '<div class="boxitemalt"><a name="searchgroup"></a>';
  print '<p>';
  print _("Below is the result of the research in the groups database.");
  print '</p>';

  if (db_numrows($result_search) < 1)
    {
      print '<p class="warn">'._("None found. Please note that only search words of more than one character are valid.").'</p>';
    }
  else
    {
      # We do not put pointer to group page along with checkbox,
      # to avoid creating any confusion (for instance, should I check the
      # box or click on the link?).
      # This tool is to search groups for inclusion, not to look around
      # to get information about groups.
      print '<p>';
      print _("To request inclusion in one or several groups, check the correspondent boxes, write a meaningful message for the project administrator who will approve or disapprove the request, and submit the form.");

      print '</p>'.form_header($_SERVER['PHP_SELF']);

      while ($val = db_fetch_array($result_search))
	{
	  if (!user_is_group_member($row_user['user_id'], $val['group_id']))
	    {
	      print '<input type="checkbox" name="form_groups['.$val['group_id'].']" /> ';
	      print $val['group_name'];
	      print '<br />';
	    }
	  else
	    {
	      print '<input type="checkbox" disabled="yes" /> ';
	      print $val['group_name'];
	      print ' (already a member)<br />';
	    }
	}

      print '<br />'._("Comments (required):").'<br />
     <textarea name="form_message" cols="40" rows="7"></textarea><br /><br />
     <input type="submit" name="update" value="';
      print _("Request Inclusion").'" /></form>';
    }
  print '</div><!-- end boxitemalt -->';
}

print $HTML->box_bottom(1);


print html_splitpage(2);

################ LEFT PART ###########################


$exists = false;
if (!$result || $rows < 1)
{

  print $HTML->box_top(_("My Groups"),'',1);
  print _("You're not a member of any public projects");
  print $HTML->box_bottom(1);

}
else
{

  /*
     Projects administrated by the user
  */

  print $HTML->box_top(_("Groups I'm Administrator of"),'',1);

  $j = 1;
  $content = '';
  for ($i=0; $i<$rows; $i++)
    {
      if (db_result($result,$i,'admin_flags') == 'A')
	{
	  $content .= '<li class="'.utils_get_alt_row_color($j).'">';
	  $content .= '<span class="trash"><a href="../my/quitproject.php?quitting_group_id='. db_result($result,$i,'group_id').
	    '">'.
	   '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" alt="'._("Quit this project?").'" /></a><br /></span>';

	  $content .= '<a href="'.$GLOBALS['sys_home'].'projects/'. db_result($result,$i,'unix_group_name') .'/">'.db_result($result,$i,'group_name').'</a><br />';
	  $date_joined = db_result($result, $i, 'date');
	  if ($date_joined)
	    {
	      # If the group history is flawed (site install problem), the
	      # date may be unavailable
	      $content .= '<span class="smaller">'.
	        sprintf(_("Member since %s"),
	        utils_format_date($date_joined)).
	        '</span>';
	    }
	  $content .= '</li>';
	  $exists=1;
	  $j++;
	}
    }
  if (!$exists)
    {
      print _("I am not administrator of any projects");
    }
  else
    {
      print '<ul class="boxli">'.$content.'</ul>';
    }
  $exists = false;

  print $HTML->box_bottom(1);

  print "<br />\n";

  /*
     Projects the user is member of
  */

  print $HTML->box_top(_("Groups I'm Contributor of"),'',1);

  $j = 1;
  $content = '';
  for ($i=0; $i<$rows; $i++)
    {
      if (db_result($result,$i,'admin_flags') == '')
	{
	  $content .= '<li class="'.utils_get_alt_row_color($j).'">';
	  $content .= '<span class="trash"><a href="../my/quitproject.php?quitting_group_id='. db_result($result,$i,'group_id').
	    '">'.
	   '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" alt="'._("Quit this project?").'" /></a></span>';

	  $content .= '<a href="'.$GLOBALS['sys_home'].'projects/'. db_result($result,$i,'unix_group_name') .'/">'.db_result($result,$i,'group_name').'</a><br />';
	  $date_joined = db_result($result, $i, 'date');
	  if ($date_joined)
	    {
	      # If the group history is flawed (site install problem), the
	      # date may be unavailable
	      $content .= '<span class="smaller">'.
	        sprintf(_("Member since %s"),
	        utils_format_date($date_joined)).
	        '</span>';
	    }
	  $content .= '</li>';
	  $exists=1;
	  $j++;
	}
    }

  if (!$exists)
    {
      print _("I am not contributor member of any projects");
    }
  else
    {
      print '<ul class="boxli">'.$content.'</ul>';
    }
  $exists = false;

  print $HTML->box_bottom(1);


print "<br />\n";

/*
     Projects the user requested to be member of
*/

print $HTML->box_top(_("Request for Inclusion Waiting For Approval"),'',1);

$content = '';

for ($i=0; $i<$rows; $i++)
{
  if (db_result($result,$i,'admin_flags') == 'P')
    {
      $content .= '<li class="'.utils_get_alt_row_color($j).'">';
      $content .= '<span class="trash"><a href="../my/quitproject.php?quitting_group_id='. db_result($result,$i,'group_id').'">'.
	   '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/trash.png" alt="'._("Discard this request?").'" /></a></span>';

      $content .= '<a href="'.$GLOBALS['sys_home'].'projects/'. db_result($result,$i,'unix_group_name') .'/">'.db_result($result,$i,'group_name').'</a><br />&nbsp;</li>';
      $exists=1;

    }
}

if (!$exists)
{
  print _("None found");
}
else
{
  print '<ul class="boxli">'.$content.'</ul>';
}
unset($exists);

print $HTML->box_bottom(1);

}

print html_splitpage(3);



$HTML->footer(array());

?>