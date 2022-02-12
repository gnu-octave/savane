<?php
# Handle bookmarks.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2005 Mathieu Roy <yeupou--gnu.org>
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
require_once('../include/init.php');
require_once('../include/sane.php');
require_once('../include/html.php');
require_once('../include/my/bookmarks.php');

site_user_header(array('context'=>'bookmark'));
extract(sane_import('get', ['true' => 'add', 'digits' => 'delete']));
extract(sane_import('request',
  [
    'digits' => 'edit', 'pass' => ['url', 'title']
  ]));

if ($add && $url)
  bookmark_add($url, $title);
if ($delete)
  bookmark_delete($delete);
if ($edit)
  {
    if ($url && $title)
      # The url and title were in the request, we update the database.
      bookmark_edit ($edit, $url, $title);
    else
      {
        # No url and title? Print the form.
        $result = db_execute("SELECT * from user_bookmarks "
                             ."WHERE bookmark_id=? AND user_id=?",
                             array($edit, user_getid()));
        if ($result)
          {
            $title = htmlspecialchars (
              db_result ($result, 0, 'bookmark_title')
            );
            $url = htmlspecialchars (
               db_result ($result, 0, 'bookmark_url')
            );

            print "\n" . '<form action="'
              . htmlentities ($_SERVER['PHP_SELF']) . '" method="post">';
            print '<span class="preinput">' . _("Title:") . "</span><br />\n";
            print '&nbsp;&nbsp;&nbsp;<input type="text" '
              . 'name="title" value="' . $title . '" size="50" />';
            print "<br />\n" . '<span class="preinput">';
            print  _("Address:") . "</span><br />\n";
            print '&nbsp;&nbsp;&nbsp;<input type="text" name="url" value="'
              . $url .'" size="50" />' . "\n";
            print '<p><input type="hidden" name="edit" value="'
              . $edit . '" /></p>' . "\n";
            print '<p><input type="submit" name="update" value="'
              . _("Update") . '" /></p>' . "\n";
            print "</form>\n";
        }
        else
          # No result? Gives feedback and print the usual page.
          fb(_("Item not found"),1);
      }
  }
$result = db_execute("SELECT bookmark_url, bookmark_title,
                      bookmark_id from user_bookmarks
                      WHERE user_id=? ORDER BY bookmark_title",
                     array(user_getid()));
$rows=db_numrows($result);
if (!$result || $rows < 1)
  print _("There is no bookmark saved");
else
  {
    print "<br />\n";
    print $HTML->box_top(_("Saved Bookmarks"),'',1);
    print '
<ul>
';
    for ($i = 0; $i < $rows; $i++)
      {
        $url = htmlspecialchars (db_result ($result, $i, 'bookmark_url'));
        $title = htmlspecialchars (db_result ($result, $i,'bookmark_title'));
        $bm_id = db_result ($result, $i, 'bookmark_id');
        print '<li class="' . utils_get_alt_row_color($i) . '">';
        print '<span class="trash"><a href="?edit=' . $bm_id . '">'
          . '<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
          . '.theme/misc/edit.png" alt="' . _("Edit this bookmark")
          . '" /></a>' . "\n"
          . '<a href="?delete=' . $bm_id . '">'
          . '<img src="' . $GLOBALS['sys_home'] . 'images/' . SV_THEME
          . '.theme/misc/trash.png" alt="' . _("Delete this bookmark")
          . '" /></a></span>'."\n";
        print '<a href="' . $url . '">' . $title . "</a><br />\n";
        print '<span class="smaller">' . $url . "</span></li>\n";
      }
    print "</ul>\n";
    print $HTML->box_bottom(1);
  }
site_user_footer(array());
?>
