<?php
# Markup reminder and test.
#
# Copyright (C) 2019 Ineiev
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
require_once('include/init.php');
require_once('include/markup.php');
require_once('include/trackers/general.php');

extract(sane_import('post', array('comment', 'basic', 'rich', 'full')));

$HTML->header(array('title' => _("Test Markup"), 'notopmenu' => 1));
html_feedback_top();

print markup_full ("= " . _('Markup Reminder and Test') . " =\n");
print "<p>"
. _('This page describes Savane markup language for formatting text you post
in items or item comments. You can <a href="#test">test it below</a>.')
. "</p>\n";

print markup_full (markup_get_reminder ());

print '<h3 id="test">' . _('Test Markup') . "</h3>\n";
print form_header($_SERVER['PHP_SELF'] . '#test', false, "post",
                  'enctype="multipart/form-data" name="test_form"');

print trackers_field_textarea('comment', htmlspecialchars($comment),
                              0, 0, _("Text to test"));
$GLOBALS['int_trapisset'] = true;
print '<div class="noprint">'
      . form_submit(_("Basic Markup"), "basic", 'class="bold"')
      . form_submit(_("Rich Markup"), "rich", 'class="bold"')
      . form_submit(_("Full Markup"), "full", 'class="bold"')
      . "</div>\n</form>\n";

if (!$comment)
  {
    $HTML->footer(array());
    exit;
  }
$comment_number = 0;
$text = $comment;
print '<table cellpadding="0" width="100%">' . "\n";
print '<tr class="boxlight"><td valign="top">';
print '<a id="comment' . $comment_number . '" href="#comment'
       . $comment_number . '" class="preinput">';
print utils_format_date(time ()) . ', '
      . sprintf(_("comment #%s:"), $comment_number)
      . "</a><br />\n";
print '<div class="tracker_comment">' . "\n";
if ($full)
  print markup_full ($text);
elseif ($rich)
  print markup_rich ($text);
else
  print markup_basic ($text);
print "</div>\n</td>\n";
print '<td class="boxlightextra">' . utils_user_link (_('Anonymous'));
print "</td>\n</tr>\n";
print "</table>\n";
$HTML->footer(array());
?>
