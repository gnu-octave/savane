<?php
# siteadmin start page.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2019 Ineiev
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

require_once('../include/init.php');
register_globals_off();

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

site_admin_header(array('title'=>no_i18n("Home"),'context'=>'admhome'));
extract (sane_import ('get',
  ['strings' => [['func', ['configure', 'manage', 'monitor']]]]
));

$even = utils_altrow (0);
$odd = utils_altrow (1);

print '<p class="warn">';
print no_i18n("Administrators functions currently have minimal error checking, if
any. They are fine to play with but may not act as expected if you leave fields
blank, etc. Also, navigating the admin functions with the &ldquo;back&rdquo;
button is highly unadvised.") . "</p>\n";

if (!$func)
  print "\n\n" . html_splitpage(1);

if (!$func || $func == "configure")
  {
    print $HTML->box_top(no_i18n("Configuration"));

# Check savane.conf.pl
    print '<p><a href="retestconfig.php">'
          . no_i18n("Test System Configuration") . "</a></p>\n";
    print '<p class="smaller">' . no_i18n(
"Check whether your configuration (PHP, MySQL, Savane) is in a good shape.")
         . "</p>\n";
    print $HTML->box_nextitem ($even);

    print '<p><a href="group_type.php">' . no_i18n("Configure Group Types")
          . "</a></p>\n";
    print '<p class="smaller">' . no_i18n(
"The Group Types define which features are provided to groups that belongs
to the related type, what are the default values for these. There must be at
least one Group Type.") . "</p>\n";

    print $HTML->box_nextitem ($odd);
    print '<p><a href="../people/admin/">' . no_i18n("Configure People Area")
          . "</a></p>\n";
    print '<p class="smaller">' . no_i18n(
"Here you can define skills for users to select in their Resume and type of
jobs for Contribution Requests. ") . "</p>\n";

    print $HTML->box_bottom();
    print "<br />\n";
  }

if (!$func)
  print html_splitpage(2);

if (!$func || $func == "manage")
  {
    if ($func)
      print "\n\n" . html_splitpage(1);

    print $HTML->box_top(no_i18n("Management: Recent Events"));

    print '<a href="' . $GLOBALS['sys_home'] . 'task/?group='
    . $GLOBALS['sys_unix_group_name']
    . '&amp;category_id=1&amp;status_id=1&amp;set=custom#results">'
    . no_i18n("Browse Pending Project Registrations") . '</a>';
    print '<p class="smaller">' . no_i18n(
"This will show the list of open task related to pending registrations.");
    print "</p>\n";

    print $HTML->box_nextitem ($even);
    print '<a href="' . $GLOBALS['sys_home'] . 'news/approve.php?group='
    . $GLOBALS['sys_unix_group_name'] . '">' . no_i18n("Approve News") . '</a>';
    print '<p class="smaller">'
    . sprintf(no_i18n(
# TRANSLATORS: the argument is site name (like Savannah).
"You can browse the list of recent news posted on the whole site.
You can select some news and make them show up on the %s front page."),
              $GLOBALS['sys_name']) . "</p>\n";
    print $HTML->box_bottom();
    print "<br />\n";

    if ($func)
      print "\n\n" . html_splitpage(2);

    print $HTML->box_top(no_i18n("Management"));

    print '<a href="grouplist.php">' . no_i18n("Browse Group List") . '</a>';
    print '<p class="smaller">' . no_i18n(
"From there, you can see the complete list of groups and reset them (change
status, etc).") . "</p>\n";

    print $HTML->box_nextitem ($even);
    print '<a href="userlist.php">' . no_i18n("Browse User List") . '</a>';
    print '<p class="smaller">' . no_i18n(
"From there, you can see the complete list of user and reset them (change
status, email, etc).");
    print $HTML->box_bottom();
    print "<br />\n";

    if ($func)
      print "\n\n" . html_splitpage(3);
  }

if (!$func || $func == "monitor")
  {
    print $HTML->box_top(no_i18n('Monitoring'));

    print '<a href="spamlist.php">' . no_i18n("Monitor Spam") . '</a>';
    print '<p class="smaller">' . no_i18n(
"Find out items flagged as spam, find out users suspected to be
spammers.") . "</p>\n";

    print $HTML->box_nextitem ($even);

    print '<p><a href="lastlogins.php">'. no_i18n("Check Last Logins")
          . "</a></p>\n";
    print '<p class="smaller">'. no_i18n("Get a list of recent logins.");
    print $HTML->box_nextitem ($odd);
    print "</p>\n" . '<p><a href="/siteadmin/usergroup.php?user_id=100">'
          . no_i18n ('Check Anonymous Posts') . "</a></p>\n";
    print $HTML->box_bottom();
  }

if (!$func)
  print html_splitpage(3);

site_admin_footer(array());
?>
