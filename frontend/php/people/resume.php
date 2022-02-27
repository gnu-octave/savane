<?php
# Display resume.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2019, 2022 Ineiev
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
require_once('../include/people/general.php');
register_globals_off();

extract (sane_import ('get', ['digits' => 'user_id']));

if (empty ($user_id))
  exit_missing_param();

$result = db_execute ("SELECT * FROM user WHERE user_id = ?", array($user_id));

if (!$result || (db_numrows($result) < 1))
  exit_error (_("User not found"));

if (db_result ($result, 0, 'people_view_skills') != 1)
  exit_error (_("This user deactivated his/her Resume & Skills page"));

$user_status = db_result ($result, 0, 'status');

if (($user_status == 'D' || $user_status == 'S') && !user_is_super_user())
  exit_error (_("This account was deleted."));

# TRANSLATORS: the argument is user's name.
$title = sprintf (_("%s Resume & Skills"), db_result ($result, 0, 'realname'));
site_header (['title' => $title, 'context' => 'people']);

$link = utils_user_link (
  db_result ($result, 0, 'user_name'), db_result($result, 0, 'realname')
);
print "<p>";
# TRANSLATORS: the argument is user's name.
printf (_("Follows Resume & Skills of %s."), $link);
print "</p>\n";

utils_get_content ("people/viewprofile");

$resume = db_result ($result, 0, 'people_resume');
if ($resume != '')
  {
    print '<h2>' . _("Resume") . "</h2>\n";
    print markup_full (htmlspecialchars ($resume));
  }
print '<h2>' . _("Skills") . "</h2>\n";
print people_show_skill_inventory ($user_id);

site_footer (array());
?>
