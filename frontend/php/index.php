<?php
# Front page - news, latests projects, etc.
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2006, 2007  Sylvain Beucler
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

require_once('include/init.php');
require_directory("people");
require_directory("news");
require_directory("stats");
require_once('include/features_boxes.php');

# Check if the PHP Frontend is acceptably configured.
# Do progressive little checks, to avoid creating to much extra load.
# Not gettextized for now, already lot of more important strings to 
# translate.
if (empty($GLOBALS['sys_unix_group_name']))
{
  fb(_("Serious configuration problem: sys_unix_group_name is empty."), 1);
} 

# Check whether the local admin group exists. This is useful only during
# installation process.
if ($conn && empty($sys_group_id))
{
  if (!user_isloggedin()) 
    {
      # If there is no database, we will first found that no user is logged in
      # Check if there is a database.
      $result = db_query("SHOW TABLES LIKE 'groups'");
      if (!db_numrows($result))
	{
	  # No valid database
	  fb(sprintf(_("Installation incomplete: while the connection to the SQL server is ok, the database '%s' was not found. Please, create it according to the documentation shipped with your Savane package"), $GLOBALS['sys_dbname']), 1);
	}
      else if (db_result(db_query("SELECT count(*) AS count FROM user"), 0, 'count') < 2)
	{ // 2 = 1 default "None" user + 1 normal user
	  fb(_("Installation incomplete: you must now create for yourself a user account. Once it is done, you will have to login and register the local administration project"), 1);
	}
      else
	{
	  # Not logged-in, probably no user account
	  fb(sprintf(_("Installation incomplete: you have to login and register the local administration project (or maybe <em>%s</em>, from the <em>sys_unix_group_name</em> configuration parameter, is not the right projet name?)"), $sys_unix_group_name), 1);
	}
    }
  else
    {    
      # No admin groups
      fb(_("Installation incomplete: you must now register the local administration project, select \"Register New Project\" in the left menu"), 1);
    }
  # I18N
  # The string is a URL on localhost, e.g. http://127.0.0.1/testconfig.php
  fb(sprintf(_("By the way, have you checked the setup of your web server at %s?"), 'http://127.0.0.1'.$GLOBALS['sys_home'].'testconfig.php'), 1);
}

$HTML->header(array('title'=>_("Welcome"), 'notopmenu'=>1));
html_feedback_top();

print '
   <div class="indexright">
';
print show_features_boxes();
print '
   </div><!-- end indexright --> 
';

print '   <div class="indexcenter">';

utils_get_content("homepage");

print "\n<p>&nbsp;</p>\n";

print $HTML->box_top('<a href="'.$GLOBALS['sys_home'].'news/" class="sortbutton">'._("Latest News").'</a>');
print news_show_latest($GLOBALS['sys_group_id'],9, "true"); 
print $HTML->box_bottom();

print '
   </div><!-- end indexcenter -->
';

$HTML->footer(array());
