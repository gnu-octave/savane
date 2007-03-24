<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
# Copyright 1999-2000 (c) The SourceForge Crew
#
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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

require_once('../include/init.php');
register_globals_off();
#input_is_safe();
#mysql_is_safe();

site_admin_header(array('title'=>_("Home"),'context'=>'admhome'));
extract(sane_import('request', array('func')));

$even = 0;
$odd = 1;

print '<p class="warn">';
print _("Administrators functions currently have minimal error checking, if any. They are fine to play with but may not act as expected if you leave fields blank, etc. Also, navigating the admin functions with the \"back\" button is highly unadvised.");
print '</p>';

###############################
if (!$func)
{
  print "\n\n".html_splitpage(1);
}

if (!$func || $func == "configure")
{
  print $HTML->box_top(_("Configuration"));

# Check savane.conf.pl
  print '<a href="retestconfig.php">'._("Test System Configuration").'</a>';
  print '<p class="smaller">'._("Check whether your configuration (PHP, MySQL, Savane) is in a good shape.").'</p>';
  
  print $HTML->box_nextitem(utils_get_alt_row_color($even));
  
  print '<a href="group_type.php">'._("Configure Group Types").'</a>';
  print '<p class="smaller">'._("The Group Types define which features are provided to groups that belongs to the related type, what are the default values for these. There must be at least one Group Type.").'</p>';
  
  print $HTML->box_nextitem(utils_get_alt_row_color($odd));
  print '<a href="../people/admin/">'._("Configure People Area").'</a>';
  print '<p class="smaller">'._("Here you can define skills for users to select in their Resume and type of jobs for Contribution Requests. ").'</p>';
  
  print $HTML->box_bottom();
  print "<br />\n";

}

if (!$func)
{
  print html_splitpage(2);
}

unset($i);
###############################
if (!$func || $func == "manage")
{
  if ($func == "manage")
    {
      print "\n\n".html_splitpage(1);
    }

  print $HTML->box_top(_("Management: Recent Events"));

# Public info
  print '<a href="'.$GLOBALS['sys_home'].'task/?group='.$GLOBALS['sys_unix_group_name'].'&amp;category_id=1&amp;status_id=1&amp;set=custom#results">'._("Browse Pending Project Registrations").'</a>';
  print '<p class="smaller">'._("This will show the list of open task related to pending registrations.");
  print '</p>';
  
  print $HTML->box_nextitem(utils_get_alt_row_color($even));
# Public info
  print '<a href="'.$GLOBALS['sys_home'].'news/approve.php?group='.$GLOBALS['sys_unix_group_name'].'">'._("Approve News").'</a>';
  print '<p class="smaller">'.sprintf(_("You can browse the list of recent news posted on the whole site. You can select some news and make them show up on the %s front page."), $GLOBALS['sys_name']).'</p>';
  
  print $HTML->box_bottom();
  

  print '<br />';


  if ($func == "manage")
    {
      print "\n\n".html_splitpage(2);
    }

  unset($i);
###############################
  print $HTML->box_top(_("Management"));
 
# Public info
  print '<a href="grouplist.php">'._("Browse Groups List").'</a>';
  print '<p class="smaller">'._("From there, you can see the complete list of groups and reset them (change status, etc).");
  print '</p>';
  
  print $HTML->box_nextitem(utils_get_alt_row_color($even));
# Public info
  print '<a href="userlist.php">'._("Browse Users List").'</a>';
  print '<p class="smaller">'._("From there, you can see the complete list of user and reset them (change status, email, etc).");
  print $HTML->box_bottom();
  

  print '<br />';

  if ($func)
    {
      print "\n\n".html_splitpage(3);
    }
}

unset($i);
###############################
if (!$func || $func == "monitor")
{
  print $HTML->box_top(_('Monitoring'));
  
  print '<a href="spamlist.php">'._("Monitor Spams").'</a>';
  print '<p class="smaller">'. _("Find out items flagged as spam, find out users suspected to be spammers.").'</p>';
  
  print $HTML->box_nextitem(utils_get_alt_row_color($even));
  
  print '<a href="lastlogins.php">'._("Check Last Logins").'</a>';
  print '<p class="smaller">'._("Get a list of recent logins.").'</p>';
  
  print $HTML->box_bottom();
  
}


if (!$func)
{
  print html_splitpage(3);
}

###############################



site_admin_footer(array());
