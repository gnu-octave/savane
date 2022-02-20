<?php
# Edit news CC list.
#
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2018 Ineiev
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

require_once('../../include/init.php');
require_once('../../include/sane.php');

extract (sane_import ('post',
  [
    'true' => 'update',
    'preg' => [['form_news_address', '/^[-+_@.,;\s\da-zA-Z]*$/']],
  ]
));

if(!$group_id)
  exit_no_group();

if (!user_ismember($group_id,'A'))
  exit_permission_denied();

$grp=project_get_object($group_id);

if (!$grp->Uses("news"))
  exit_error(_("Error"),_("This Project Has Turned Off News Tracker"));

if ($update)
  {
    db_execute("UPDATE groups SET new_news_address=? WHERE group_id=?",
         array($form_news_address, $group_id));
    fb(_("Updated"));
  }
site_project_header (['group' => $group_id, 'context' => 'anews']);

print '<p>'
  . _("You can change all of this tracker configuration from this page.")
  . "</p>\n";

$res_grp = db_execute("SELECT new_news_address FROM groups WHERE group_id=?",
                      array($group_id));
$row_grp = db_fetch_array($res_grp);

print '<h2>' . _("News Tracker Email Notification Settings") . "</h2>\n"
  . '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
  . "\" method=\"post\">\n"
  .  "<input type=\"hidden\" name=\"group_id\" value=\"$group_id\" />\n"
  . '<span class="preinput"><label for="form_news_address">'
  . _("Carbon-Copy List:") . "</label></span>\n<br />\n"
  . "&nbsp;&nbsp;<input type=\"text\" name=\"form_news_address\" "
  . "id=\"form_news_address\" value=\"{$row_grp['new_news_address']}"
  . "\" size=\"40\" maxlength=\"255\" />\n"
  . '<p align="center"><input type="submit" name="update" value="'
  . _("Update") . "\" />\n</form>\n";

site_project_footer(array());
?>
