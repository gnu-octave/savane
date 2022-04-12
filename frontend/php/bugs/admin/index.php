<?php
# Configure trackers.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006  Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2022 Ineiev
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

require_once ('../../include/init.php');
require_once ('../../include/trackers/general.php');
$is_admin_page = 'y';

if (!$group_id)
  exit_no_group ();

if (!user_ismember ($group_id, 'A'))
  exit_permission_denied ();

trackers_init ($group_id);
trackers_header_admin ([]);

print '<p>'
  . _("You can change all of this tracker configuration from here.")
  . "</p>\n\n" . html_splitpage (1);
print $HTML->box_top (_("Miscellaneous"));

$next_box_item = function ($reset = false)
{
  global $HTML;
  static $i = 0;
  if ($reset)
    $i = 0;
  print $HTML->box_nextitem (utils_altrow ($i++));
};

print "<p><a href=\"userperms.php?group=$group\">" . _("Set Permissions")
  . "</a></p>\n";
print '<p class="smaller">'
  . _("Set permissions and posting restrictions for this tracker.")
  . "</p>\n";

$next_box_item ();
print "<p><a href=\"notification_settings.php?group=$group\">"
  . _("Configure Mail Notifications") . "</a></p>\n";
print '<p class="smaller">'
  . _("You can define email notification rules for this tracker.")
  . "</p>\n";

$next_box_item ();
print "<p><a href=\"other_settings.php?group=$group\">"
  . _("Edit Post Form Preambles") . "</a></p>\n";
print '<p class="smaller">'
  . _("Define preambles that will be shown to users when they submit "
      . "changes on this tracker.")
    ."</p>\n";

$next_box_item ();
print "<p><a href=\"conf-copy.php?group=$group\">" . _("Copy Configuration")
  . "</a></p>\n";
print '<p class="smaller">'
  . _("Copy the configuration of trackers of other projects you are member "
      . "of.")
  . "</p>\n";
print $HTML->box_bottom ();
print "<br />\n";
print html_splitpage (2);

print $HTML->box_top (_('Item Fields'));

print "<p><a href=\"field_usage.php?group=$group\">" . _("Select Fields")
  . "</a></p>\n";
print '<p class="smaller">'
  . _("Define which fields you want to use in this tracker, define how they "
      . "will\nbe used.")
  . "</p>\n";

$next_box_item (1);
print "<p><a href=\"field_values.php?group=$group\">" . _("Edit Field Values")
  .  "</a></p>\n";
print '<p class="smaller">'
  . _("Define the set of possible values for the fields you have decided "
      . "to use in\nthis tracker.")
  . "</p>\n";

$next_box_item ();
unset ($next_box_item);
print "<p><a href=\"editqueryforms.php?group=$group\">" . _("Edit Query Forms")
  .  "</a></p>\n";
print '<p class="smaller">'
  . _("Define project-wide query form: what display criteria to use "
      . "while browsing\nitems and which fields to show in the results table.")
  . "</p>\n";

print $HTML->box_bottom ();
print html_splitpage (3);
trackers_footer ([]);
?>
