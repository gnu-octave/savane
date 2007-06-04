<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#  Copyright 2000-2003 (c) Free Software Foundation
#                          Mathieu Roy <yeupou--at--gnu.org>
#
#  Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Lorenzo Hernandez Garcia-Hierro
#                                      <lorenzohgh--tuxedo-es.org >
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
require_directory("trackers");

$detailed = sane_get("detailed");
$form_tgrp = sane_all("form_grp");

if ((!$group_id) && $form_grp)
{
  $group_id=$form_grp;
}
     
     site_project_header(array('title'=>_("Project Memberlist"),
			       'group'=>$group_id,
			       'context'=>'people'));

$checked = '';
if ($detailed)
{ $checked = " selected=\"selected\""; }
# I18N
# %s currently is "basic" or "detailed"
  $form_opening = '<form action="'.$_SERVER['PHP_SELF'].'#options" method="get">';
  $form_submit = '<input class="bold"  type="submit" value="'._("Apply").'" />';
print html_show_displayoptions(sprintf(_("Browse with the %s memberlist."), '<select name="detailed"><option value="0">'._("basic").'</option><option value="1"'.$checked.'>'._("detailed").'</option></select>').'<input type="hidden" name="group" value="'.$group.'" />',
			       $form_opening,
			       $form_submit);


if (!member_check(0,$group_id))
{
  print '<p>'.sprintf(_("If you would like to contribute to this project by becoming a member, %scontact one of the project admins%s, designated in bold text below."),
	 '<a href="'.$GLOBALS['sys_home'].'my/groups.php?words='.group_getname($group_id).'#searchgroup">', '</a>').'</p>';
}
else
{
  print '<p>'._("Note that you can 'watch' a member of your project. It allows you, for instance, to be the backup of someone when they are away from the office, or to review all their activities on this project: you will receive a copy of their mail notifications related to this project.").'</p>';

}

if ($detailed)
{
  member_explain_roles();
  # FIXME: yeupou--gnu.org 2003-11-07
  # The best would be to print non-specific roles but roles in any case.
  # It requires more, so we will see if there are people interested in that
  # or not.
  print '<p>'._("On this page are only presented specific roles, roles which are not attributed by default when joining this project.").'</p>';
}

# list members
if (!$detailed)
{
  $res_memb = db_execute("SELECT user.user_name AS user_name, "
     . "user.user_id AS user_id,"
     . "user.realname AS realname, "
     . "user.add_date AS add_date, "
     . "user.people_view_skills AS people_view_skills, "
     . "user_group.admin_flags AS admin_flags, "
     . "user.email AS email "
     . "FROM user,user_group "
     . "WHERE user.user_id=user_group.user_id AND user_group.group_id = ?  AND user_group.admin_flags <> 'P' "
     . "ORDER BY user.user_name ", array($group_id));
}
else
{
  $res_memb = db_execute("SELECT user.user_name AS user_name, "
     . "user.user_id AS user_id,"
     . "user.realname AS realname, "
     . "user.add_date AS add_date, "
     . "user.people_view_skills AS people_view_skills, "
     . "user_group.admin_flags AS admin_flags, "
     . "user_group.bugs_flags AS bugs_flags, "
     . "user_group.task_flags AS task_flags, "
     . "user_group.patch_flags AS patch_flags, "
     . "user_group.news_flags AS news_flags, "
     . "user_group.support_flags AS support_flags, "
     . "user.email AS email "
     . "FROM user,user_group "
     . "WHERE user.user_id=user_group.user_id AND user_group.group_id = ?  AND user_group.admin_flags <> 'P' "
     . "ORDER BY user.user_name", array($group_id));
}


$title_arr=array();
$title_arr[]="&nbsp;";
$title_arr[]=_("Member");
if ($detailed)
{ $title_arr[]=_("Specific Role"); }
# yeupou--gnu.org, 2004-11-04, remove email from this page; this data
# is accessible elsewhere, via links. It saves us extra tests on whether
# users want to hide their email or not.
#$title_arr[]=_("Email");
$title_arr[]=_("Resume and Skills");
if (user_ismember($group_id))
{
  $title_arr[]=_("Watch");
}

echo html_build_list_table_top ($title_arr);



function specific_print_role ($row, $title)
{
  print
    (($row == 1)?$title.' '._("technician").',<br />':"").
    (($row == 3)?$title.' '._("manager").',<br />':"").
    (($row == 2)?$title.' '._("techn. & manager").',<br />':"");
}

$i = 1;
while ($row_memb=db_fetch_array($res_memb))
{
  if ($row_memb['admin_flags'] != 'P')
    {
      $i++;
      $color = utils_get_alt_row_color($i);
      if ($row_memb['admin_flags'] == 'A')
	{ $color = "boxhighlight"; }

      print "\n\t<tr class=\"".$color."\">\n";
      print "\t\t";

      # Realname
      if ($row_memb['admin_flags'] == 'A')
	{
	  if ($group_id != $GLOBALS['sys_group_id'])
	    {
	      $icon = "project-admin";
	      $icon_alt = _("Project Administrator");
	    }
	  else
	    {
	      $icon = "site-admin";
	      $icon_alt = _("Site Administrator");
	    }
	}
      else if ($row_memb['admin_flags'] == 'SQD')
	{
	  $icon = "people";
	  $icon_alt = _("Squad");   
	}
      else
	{
	  $icon = "project-member";
	  $icon_alt = _("Project Member");      
	}
      
      print "\t\t".'<td><span class="help" title="'.$icon_alt.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/roles/'.$icon.'.png" alt="'.$icon_alt.'" class="icon" /></span></td><td>'.utils_user_link($row_memb['user_name'], $row_memb['realname'])."</td>\n";

      # Role
      if ($detailed)
	{
	  print "\t\t<td align=\"middle\">";
	  if ($row_memb['admin_flags'] == 'A')
	    {
	      # No details if it is an admin
	      print _("project admin");
	    }
	  else
	    {
	      # Print only not by default role.

	      specific_print_role($row_memb['support_flags'], _("support tracker"));
	      specific_print_role($row_memb['bugs_flags'], _("bug tracker"));
	      specific_print_role($row_memb['task_flags'], _("task tracker"));
	      specific_print_role($row_memb['patch_flags'], _("patch tracker"));
	      specific_print_role($row_memb['news_flags'], _("news tracker"));

	    }
	  print "</td>\n";
	}

      # Email
# yeupou--gnu.org, 2004-11-04, remove email from this page; this data
# is accessible elsewhere, via links. It saves us extra tests on whether
# users want to hide their email or not.

      # Skills
      if ($row_memb['people_view_skills'] == 1)
	{
	  print "\t\t<td align=\"middle\"><a href=\"".$GLOBALS['sys_home']."people/resume.php?user_id=".$row_memb['user_id']."\">"._("View Skills")."</a></td>\n";
	}
      else
	{
	  print "\t\t<td align=\"middle\">"._("Set to private")."</td>\n";
	}
      # Watch
      if (user_ismember($group_id))
	{
	  $thisuser = user_getid();
	  if ($row_memb['user_id'] != $thisuser && !trackers_data_is_watched($thisuser,$row_memb['user_id'],$group_id))
	    {
	      # permit to add a watchee only if not already in the watched list
	      print "\t\t<td align=\"middle\"><a href=\"".$GLOBALS['sys_home']."my/groups.php?func=addwatchee&amp;group_id=$group_id&amp;watchee_id=".$row_memb['user_id']."\">"._("Watch partner")."</a></td>\n";
	    }
	  else
	    {
	      print "\t\t<td align=\"middle\">---</td>\n";
	    }
	}
      print "\t<tr>\n";
    }
}
print "\t</table>";

if ($project->getGPGKeyring())
{
  print '<p>'.sprintf(_('You may also be interested in the %sGPG Keyring of this project%s'), '<a href="memberlist-gpgkeys.php?group='.$group.'">','</a>').'</p>';

}


site_project_footer(array());
