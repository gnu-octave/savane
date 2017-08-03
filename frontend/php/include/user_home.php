<?php
# User home page.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
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


register_globals_off();
require_directory("my");

# Assumes $res_user result handle is present

if (!$res_user || db_numrows($res_user) < 1)
{
  exit_error(_('No Such User'),_('No Such User'));
}

$realname = db_result($res_user,0,'realname');

if (!user_is_super_user() && db_result($res_user,0,'status') == 'D')
  {
    $realname = _('-deleted account-');
    $email_address = _('-deleted account-');
  }

# TRANSLATORS: the argument is user's name (like Assaf Gordon).
site_header(array('title'=>sprintf(_("%s Profile"),$realname),
		  'context'=>'people'));

# For suspended account, we will print only very basic info:
# accound id, login + description as deleted account
$is_suspended = false;
if (db_result($res_user,0,'status') == 'S')
{ $is_suspended = true; }

# The same for deleted accounts.
if (db_result($res_user,0,'status') == 'D')
{ $is_suspended = true; }

$is_squad = false;
if (db_result($res_user,0,'status') == 'SQD')
{ $is_squad = true; }

# For squad account, we will print some specific info
# TRANSLATORS: the argument is user's name (like Assaf Gordon).
print '<p>'.sprintf(_("Follows the Profile of %s."),
                    utils_user_link(db_result($res_user, 0, 'user_name'),
                    $realname));

if ($is_squad)
  print ' '._("It is not a normal user account but a squad: it unites several
users as if they were one (notifications, privileges, etc).");

print "</p>\n";
print html_splitpage("start");

if (!$is_suspended)
{

  # List items:
  #  - ignore recipes, it is less personal
  #  - ignore closed items, it would make a page that dont scale for
  #    very active developers
  #  - ignore private items

  $result = db_execute("SELECT groups.group_name,"
		       . "groups.group_id,"
		       . "groups.unix_group_name,"
		       . "groups.status "
		       . "FROM groups,user_group "
		       . "WHERE groups.group_id=user_group.group_id "
		       . "AND user_group.user_id=? "
		       . "AND groups.status='A' "
		       . "GROUP BY groups.unix_group_name "
		       . "ORDER BY groups.unix_group_name",
		       array(user_getid()));
  $rows = db_numrows($result);
  $usergroups = array();
  $usergroups_groupid = array();
  $usersquads = array();
  $group_data = array();
  if ($result && $rows > 0)
    {
      for ($j=0; $j<$rows; $j++)
	{
	  unset($nogroups);
	  $unixname = db_result($result,$j,'unix_group_name');
	  $usergroups[$unixname] = db_result($result,$j,'group_name');
	  $usergroups_groupid[$unixname] = db_result($result,$j,'group_id');
	}
    }
  else
    { $nogroups = 1; }


  if (!$is_squad)
    {
      # meaningless for squads
# TRANSLATORS: the argument is user's name (like Assaf Gordon).
      print $HTML->box_top(sprintf(_("Open Items submitted by %s"),$realname),
                           '',1);
      # FIXME: News item are missing

      my_item_list("submitter", "0", "open", $user_id, true);
      print $HTML->box_bottom(1);

      print "<br />\n";
    }

# TRANSLATORS: the argument is user's name (like Assaf Gordon).
  print $HTML->box_top(sprintf(_("Open Items assigned to %s"),$realname),'',1);
  # FIXME: News item are missing
  my_item_list("assignee", "0", "open", $user_id, true);
  print $HTML->box_bottom(1);
}

print html_splitpage(2);
print $HTML->box_top(_("General Information"));

print '
<br />
<table width="100%" cellpadding="0" cellspacing="0" border="0">';
if (db_result($res_user,0,'status') == "D" && user_is_super_user())
{
  print '
<tr valign="top">
	<td>'
	._("Note:").' </td>
	<td><strong>'._("The account was deleted").'</strong></td>
</tr>';
}
print '
<tr valign="top">
	<td>'
	._("Real Name:").' </td>
	<td><strong>'.$realname.'</strong></td>
</tr>
<tr valign="top">
	<td>'._("Login Name:").' </td>
	<td><strong>'.db_result($res_user,0,'user_name').'</strong></td>
</tr>';
if (db_result($res_user,0,'status') != "D" || user_is_super_user())
{
  print '
<tr valign="top">
	<td>';
# TRANSLATORS: user's id (a number) shall follow this message.
print _("Id:").' </td>
	<td><strong>#'.db_result($res_user,0,'user_id').'</strong></td>
</tr>

<tr valign="top">
	<td>'._("Email Address:").' </td>
	<td>
	<strong><a href="'.$GLOBALS['sys_home'].'sendmessage.php?touser='
       .db_result($res_user,0,'user_id').'">';

  # Do not print email address to anonymous user
  if (db_result($res_user,0,'email_hide') == "1" && !user_is_super_user())
    print _("Send this user a mail");
  else
    print utils_email_basic(db_result($res_user,0,'email'), 1);
  print '</a></strong>
	</td>
</tr>';
}

# meaningless for squads
if (!$is_squad && (!$is_suspended || user_is_super_user()))
{
  print '
<tr valign="top">
	<td>'
    ._("Site Member Since:").'
	</td>
	<td>
		<strong>'
        .utils_format_date(db_result($res_user,0,'add_date')).'</strong>
	</td>
</tr>
<tr valign="top">
	<td></td>
	<td>';

  if (db_result($res_user,0,'people_view_skills') != 1)
      print _("This user did not enable Resume & Skills.");
  else
      print '<a href="'.$GLOBALS['sys_home'].'people/resume.php?user_id='
            .db_result($res_user,0,'user_id').'"><strong>'
            ._("View Resume & Skills").'</strong></a>';
  print '
	</td>
</tr>
';

  if (db_result($res_user,0,'gpg_key') != "") {
    print '<tr valign="top"><td></td><td>';
    print '<a href="'.$GLOBALS['sys_home'].'people/viewgpg.php?user_id='
          .db_result($res_user,0,'user_id').'"><strong>'
          ._("Download GPG Key").'</strong></a>';
    print "</td>\n</tr>\n";
  }
}
print "</table>\n";

print $HTML->box_bottom();

if (!$is_suspended)
{

  print "<br />\n";
# FIXME: it could reuse the arrays built before to generate
# the items lists.

  print $HTML->box_top(_("Project/Group Information"),'',1);
# now get listing of groups for that user

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
                       . "AND groups.is_public='1' "
                       . "AND (group_history.field_name='Added User' "
                       . "OR group_history.field_name='Approved User' "
                       . "OR user_group.admin_flags='P')"
                       . "AND group_history.group_id=user_group.group_id "
                       . "AND group_history.old_value=? "
                       . "GROUP BY groups.unix_group_name "
                       . "ORDER BY groups.unix_group_name",
                       array($user_id, db_result($res_user,0,'user_name')));
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
				     . "AND groups.is_public='1' "
				     . "GROUP BY groups.unix_group_name "
				     . "ORDER BY groups.unix_group_name",
				     array($user_id));
$rows_without_history = db_numrows($result_without_history);

// The history is often broken, as soon as you have an old install or
// rename a user. This has nothing to do with the 1.0.6 upgrade - we
// need to convert the project history to machine-readable data.

$history_is_flawed = false;
if ($rows_without_history != $rows)
{
  # If number of rows differ, assume that history is flawed.
  $result = $result_without_history;
  $rows = $rows_without_history;
  $history_is_flawed = true;
}


  $j = 1;
  $content = '';
  $exists = FALSE;
  for ($i=0; $i<$rows; $i++)
    {
      # Ignore if requesting for inclusion
      if (db_result($result, $i, 'admin_flags') == 'P')
	{ continue; }

      $content .= '<li class="'.utils_get_alt_row_color($j).'">';
      $content .= '<a href="'.$GLOBALS['sys_home'].'projects/'
         . db_result($result,$i,'unix_group_name') .'/">'
         .db_result($result,$i,'group_name')."</a><br />\n";
      if ($history_is_flawed)
	$date_joined = null;
      else
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
      $content .= "</li>\n";
      $exists=1;
      $j++;
    }

  if (!$exists)
    print _("This user is not a member of any Group");
  else
    print '<ul class="boxli">'.$content."</ul>\n";
  unset($exists);

  print $HTML->box_bottom(1);

  # List of squad members, if appliable
  if ($is_squad)
    {

      print "<br />\n";
      print $HTML->box_top(_("Members"),'',1);

      $result = db_execute("SELECT user.user_name, user.realname, user.user_id "
                           . "FROM user,user_squad "
                           . "WHERE user.user_id=user_squad.user_id "
                           . "AND user_squad.squad_id=? "
                           . "GROUP BY user.user_name ",
                           array($user_id));
      $rows = db_numrows($result);

      $j = 1;
      $content = '';
      $exists = false;
      for ($i=0; $i<$rows; $i++)
	{
	  $content .= '<li class="'.utils_get_alt_row_color($j).'">';
	  $content .= utils_user_link(db_result($result,$i,'user_name'),
				      db_result($result,$i,'realname'));
	  $content .= "</li>\n";
	  $exists=1;
	  $j++;
	}

      if (!$exists)
	{
	  print _("No member found");
	}
      else
	{
	  print '<ul class="boxli">'.$content."</ul>\n";
	}

      unset($exists);

      print $HTML->box_bottom(1);
    }

  print html_splitpage(3)."<p class=\"clearr\">&nbsp;</p>\n";

  if (user_isloggedin())
    {
      sendmail_form_message($GLOBALS['sys_home'].'sendmessage.php', $user_id);
    }
  else
    {
      print '<p class="warn">'
        ._("You Could Send a Message if you were logged in.")."</p>\n";
    }
}

$HTML->footer(array());
?>
