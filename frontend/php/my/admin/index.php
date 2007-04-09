<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: index.php 4977 2005-11-15 17:38:40Z yeupou $
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

# we need to define the theme before loading the pre.php init script,
# otherwise the page needs to be reloaded for the change to take effect.
# see bug #1987
$update = isset($_REQUEST['update'])? $_REQUEST['update'] : '';
$user_theme = isset($_REQUEST['user_theme']) ? $_REQUEST['user_theme'] : '';

if ($update and $user_theme != "random" and $user_theme != "rotate")
  {
    define('SV_THEME', addslashes($user_theme));
  }

require_once('../../include/init.php');
require_once('../../include/timezones.php');

register_globals_off();

$feedback = sane_all("feedback");

#######################
####################### UPDATE DATABASE

session_require(array('isloggedin'=>1));

if (sane_post("update"))
{
  # Update theme
  $user_theme = sane_post("user_theme");
  $theme_rotate_jump = sane_post("theme_rotate_jump");
  
  if ($user_theme == "Default")
    { $user_theme = ""; }

  if ($theme_rotate_jump == "1")
    {
      theme_rotate_jump($theme_rotate_numeric);
    }
  else
    {
      setcookie("SV_THEME", $user_theme, time() + 60*60*24*365, $GLOBALS['sys_url_topdir']);
    }


  # Update the rest
  $form_email_hide = sane_post("form_email_hide");
  $form_timezone = sane_post("form_timezone");
  if ($form_timezone == 100)
    { $form_timezone = "GMT"; }

  $success = db_query("UPDATE user SET "
		      . "email_hide='" . ($form_email_hide?"1":"0")."', "
		      . "theme='".$user_theme."', "
		      . "timezone='".$form_timezone."' WHERE "
		      . "user_id=" . user_getid());


  # Integrated bookmarks
  if (sane_post("form_use_bookmarks") == "1")
    { user_set_preference("use_bookmarks", 1); }
  else
    { user_unset_preference("use_bookmarks"); }

  # Relative position feedback
  if (sane_post("form_nonfixed_feedback") == "1")
    { user_set_preference("nonfixed_feedback", 1); }
  else
    { user_unset_preference("nonfixed_feedback"); }

  # Stone Age menu
  if (sane_post("form_stone_age_menu") == "1")
    { 
      user_set_preference("stone_age_menu", 1);
      # Too late for the stone age menu to be effective
      fb(_("Stone age menu activated, it will be effective the next time a page is loaded"));
    }
  else
    { user_unset_preference("stone_age_menu"); }


  # Reversed comment order
  if (sane_post("form_reverse_comments_order") == "1")
    { user_set_preference("reverse_comments_order", 1); }
  else
    { user_unset_preference("reverse_comments_order"); }

  # Keep only one session comment order
  if (sane_post("form_keep_only_one_session") == "1")
    { user_set_preference("keep_only_one_session", 1); }
  else
    { user_unset_preference("keep_only_one_session"); }


  if ($success)
    {
      fb(_("Database successfully updated"));
    }
  else
    {
      fb(_("Failed to update the database"),1);
    }
}


#######################
####################### PRINT FORM + LINKS


site_user_header(array('context'=>'account'));


# get global user vars
$res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
$row_user = db_fetch_array($res_user);

print '<p>'._("You can view/change all of your account features from here.").'</p>';

# we get site-specific content
utils_get_content("account/index_intro");


print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';

#####################################################################
#####################################################################

print '<h3>'._("Significant Arrangements").'</h3>';

print "\n".html_splitpage(1);

############## Passwd / SSH

print $HTML->box_top(_('Authentication Setup'));
print '<a href="change.php?item=password">'._("Change Password").'</a>';
print '<p class="smaller">'._("This password gives access to the web interface.").'<br />';
utils_get_content("account/index_passwd");
print '</p>';

# get shared key count from db
$expl_keys = explode("###",$row_user['authorized_keys']);

# If the last 'key' is empty, then it is because of a trailing separator;
# so do not count it
$keynum = (sizeof($expl_keys));
if ($expl_keys[$keynum-1] == "")
  $keynum--;

$i = 0;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="editsshkeys.php">';
if ($keynum > 0)
{ printf(ngettext("Edit the %d SSH Public Key registered", "Edit the %d SSH Public Keys registered", $keynum), $keynum); }
else
{ print gettext("Register an SSH Public Key"); }

print '</a><p class="smaller">';
utils_get_content("account/index_ssh");
print '</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="change.php?item=gpgkey">'._("Edit GPG Key").'</a>';
print '<p class="smaller">';
utils_get_content("account/index_gpg");
print '</p>';


$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="sessions.php">';
printf(ngettext("Manage the %d opened session", "Manage the %d opened sessions", session_count(user_getid())),session_count(user_getid()));
print '</a><br /><br />';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));

print '<input type="checkbox" name="form_keep_only_one_session" value="1" '.(user_get_preference("keep_only_one_session") ? 'checked="checked"':'').' /> '._("Keep only one session opened at a time");

print '<p class="smaller">'._("By default, you can open as many session concurrently as you want. But you may prefer to allow only one session to be opened at a time, killing previous sessions each time you log in.").'</p>';

print $HTML->box_bottom();
print "<br />\n";


################### Personal Record
print html_splitpage(2);


print $HTML->box_top(_('Identity Record'));

print sprintf(_("Account #%s"), $row_user['user_id']);
print '<p class="smaller">'.sprintf(_("Your login is %s."), '<strong>'.$row_user['user_name'].'</strong>').' '.sprintf(_("You registered your account on %s."), '<strong>'.utils_format_date($row_user['add_date']).'</strong>').'</p>';

$i = 0;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="change.php?item=realname">'._("Change Real Name").'</a>';
print '<p class="smaller">'.sprintf(_("You are %s."), '<strong>'.$row_user['realname'].'</strong>').'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="resume.php">'._("Edit Resume and Skills").'</a>';
print '<p class="smaller">'._("Details about your experience and skills may be of interest to other users or visitors.").'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="'.$GLOBALS['sys_home'].'users/'.$row_user['user_name'].'">'._("View your Public Profile").'</a>';
print '<p class="smaller">'._("Your profile can be viewed by everybody.").'</p>';

print $HTML->box_bottom();
print "<br />\n";

################### Email Related
print $HTML->box_top(_('Mail Setup'));

print '<a href="change.php?item=email">'._("Change Email Address").'</a>';
print '<p class="smaller">'.sprintf(_("Your current address is %s. It is essential to us that this address remains valid. Keep it up to date."), '<strong>'.$row_user['email'].'</strong>').'</p>';

$i = 0;
print $HTML->box_nextitem(utils_get_alt_row_color($i));

print '<a href="change_notifications.php">'._("Edit Personal Notification Settings").'</a>';
print '<p class="smaller">'._("Here is defined when the trackers should send email notifications. It permits also to configure the subject line prefix of sent mails.").'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));
print '<a href="cc.php">'._("Cancel Mail Notifications").'</a>';
print '<p class="smaller">'._("Here, you can cancel all mail notifications.").'</p>';


print $HTML->box_bottom();
print "<br />\n";

print html_splitpage(3);

#####################################################################
#####################################################################

print '<span class="clearr" /><p class="center"><input type="submit" name="update" value="'._("Update").'" /></p></span>';
print "<br />\n";

print '<h3>'._("Secondary Arrangements").'</h3>';

# warning about MSIE hacks
if (is_broken_msie() && !$_GET["printer"])
print '<p>'._("Caution: your current web browser identifies itself as Microsoft Internet Explorer. Unexpected behavior of this software in several regards may cause rendering problems, most notably break the interface layout.").' <span class="warn">'._("You are strongly advised to use a browser like Mozilla or Konqueror if you encounter such troubles.").'</span></p>';

print html_splitpage(1);

################### Account Deletion
print $HTML->box_top(_('Account Deletion'));
print '<a href="change.php?item=delete">'._("Delete Account").'</a>';
print '<p class="smaller">'.sprintf(_("If you are no longer member of any project and do not intend to use %s further, you may want to delete your account. This action cannot be undone and your current login will be forever lost."), $GLOBALS['sys_name']).'</strong></p>';

print $HTML->box_bottom();
print "<br />\n";

################### Optional features
print $HTML->box_top(_('Optional Features'));


print '<input type="checkbox" name="form_use_bookmarks" value="1" '.(user_get_preference("use_bookmarks") ? 'checked="checked"':'').' /> '._("Use integrated bookmarks");

print '<p class="smaller">'._("By default, integrated bookmarks are deactivated to avoid redundancy with the bookmark feature provided by most modern web browsers. However, you may prefer integrated bookmarks if you frequently use different workstations without web browsers bookmarks synchronization.").'</p>';

$i = 0;
print $HTML->box_nextitem(utils_get_alt_row_color($i));

print '<input type="checkbox" name="form_email_hide" value="1" '.($row_user['email_hide'] ? 'checked="checked"':'').' /> '._("Hide email address from your account information");

print '<p class="smaller">'._("When checked, the only way for users to get in touch with you would be to use the form available to logged-in users. It is generally a bad idea to choose this option, especially if you are a project administrator.").'</p>';


print $HTML->box_bottom();
print "<br />\n";


print html_splitpage(2);

################### Prefs

print $HTML->box_top(_('Cosmetics Setup'));

# The select box comes before the name of the category so all the clickable
# part of the form stays on a same line (better UI design)
print html_build_select_box_from_arrays($TZs,$TZs,'form_timezone',$row_user['timezone'], true, 'GMT');
print ' '._("Timezone");
print '<p class="smaller">'._("No matter where you live, you can see all dates and times as if it were in your neighborhood.").'</p>';

$i = 0;
print $HTML->box_nextitem(utils_get_alt_row_color($i));

html_select_theme_box("user_theme", $row_user['theme']);
print ' '._("Theme");

if ("rotate"==$row_user['theme'])
{
  print '<br /><input type="checkbox" name="theme_rotate_jump" value="1" /> '._("Jump to the next theme").'';
  print '<input type="hidden" name="theme_rotate_numeric" value="'. $_COOKIE['SV_THEME_ROTATE_NUMERIC'] .'" />';
}
print '<p class="smaller">'._("Not satisfied with the default color theme of the interface?").'</p>';

if (!theme_guidelines_check(SV_THEME))
{
  print '<p class="smaller"><span class="warn">'._("The theme you are currently using does not follow the latest Savane CSS guidelines. As a result, page layout may be more or less severely broken. It is not advised to use this theme.").' ';
  # If the non-valid theme is the default one, tell users they should fill
  # a support request
  if (SV_THEME == $GLOBALS['sys_themedefault'])
    {
      print utils_link($GLOBALS['sys_home'].'support/?group='.$GLOBALS['sys_unix_group_name'],
		       sprintf(_("%s administrators should be asked to take care of Savane CSS Guidelines, since it is the default theme"), $GLOBALS['sys_name']), "warn");
    }
  print '</span></p>';
}

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));

print '<input type="checkbox" name="form_reverse_comments_order" value="1" '.(user_get_preference("reverse_comments_order") ? 'checked="checked"':'').' /> '._("Print items comments from the oldest to the latest");

print '<p class="smaller">'._("By default, comments are listed in reverse chronological order. This means that for a given item, comments are printed from the latest to the oldest. If this behavior does not suit you, select this option.").'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));

print '<input type="checkbox" name="form_stone_age_menu" value="1" '.(user_get_preference("stone_age_menu") ? 'checked="checked"':'').' /> '._("Use the Stone Age menu");

print '<p class="smaller">'._("By default, the top menu includes links, via dropdown submenus, to all relevant pages in the current context (project area, personal area). However, the dropdown submenu mechanism may not work with few old browsers, for instance very old Konqueror versions (< 3.1, before 2003). Selecting this option enables an old fashioned submenu like the one shipped in older Savane releases (< 2.0).").'</p>';

$i++;
print $HTML->box_nextitem(utils_get_alt_row_color($i));

print '<input type="checkbox" name="form_nonfixed_feedback" value="1" '.(user_get_preference("nonfixed_feedback") ? 'checked="checked"':'').' /> '._("Show feedback in relative position");

print '<p class="smaller">'._("By default, the feedback box appear as a fixed box on top of the window and you can hide it by clicking on it. If you check this option, the feedback will be added in the page flow, after the top menu. Note: feedback is always in relative position with Microsoft Internet Explorer < 7, whatever this setting is set to.").'</p>';

print $HTML->box_bottom();
print "<br />\n";

print html_splitpage(3);

print '<span class="clearr" /><p class="center"><input type="submit" name="update" value="'._("Update").'" /></p></span>';

print '</form>';

$HTML->footer(array());
?>
