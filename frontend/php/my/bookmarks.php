<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2004-2005 (c) Mathieu Roy <yeupou--gnu.org>
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

extract(sane_import('get', array('add', 'delete')));
extract(sane_import('request', array('edit', 'url', 'title')));

if ($add && $url)
{
  # New bookmark
  bookmark_add($url, $title);
}
if ($delete)
{
  # Delete bookmark
  bookmark_delete($delete);
}
if ($edit)
{
  if ($url && $title)
    {
      # The url and title were in the request, we update the database
      bookmark_edit($edit, $url, $title); 
    }
  else
    {
      # No url and title? Print the form
      $result = db_execute("SELECT * from user_bookmarks WHERE bookmark_id=? AND user_id=?",
			   array($edit, user_getid()));
      if ($result) 
	{
	  # Result found? Really print (only) the form 
	  $title = db_result($result,0,'bookmark_title');
	  $url = db_result($result,0,'bookmark_url');
	  
	  print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
	  print '<span class="preinput">'._("Title:").'</span>';
	  print '<br />&nbsp;&nbsp;&nbsp;<input type="text" name="title" value="'.$title.'" size="50" />';
	  print '<br />';
	  print '<span class="preinput">'._("Address:").'</span>';
	  print '<br />&nbsp;&nbsp;&nbsp;<input type="text" name="url" value="'.$url.'" size="50" />';

	  print '<input type="hidden" name="edit" value="'.$edit.'" /></p>';
	  print '<p><input type="submit" name="update" value="'._("Update").'" /></p>';
print '</form>';

	}
      else 
	{
	  # No result? Gives feedback and print the usual page
	  fb(_("Item not found"),1);
	}
    }
}

$result = db_execute("SELECT bookmark_url, bookmark_title, bookmark_id from user_bookmarks
                      WHERE user_id=? ORDER BY bookmark_title",
		     array(user_getid()));
$rows=db_numrows($result);
if (!$result || $rows < 1)
{
  print _("There is no bookmark saved");
}
else
{
  
  print '<br />';
  print $HTML->box_top(_("Saved Bookmarks"),'',1);
  for ($i=0; $i<$rows; $i++)
        {
          print '<li class="'.utils_get_alt_row_color($i).'">';
	  print '<span class="trash"><a href="?edit='.db_result($result,$i,'bookmark_id').'">'.
	    '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/edit.png" alt="'._("Edit this bookmark").'" /></a>'.
	    '<a href="?delete='.db_result($result,$i,'bookmark_id').'">'.
	    '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" alt="'._("Delete this bookmark").'" /></a></span>';
	  print '<a href="'.db_result($result,$i,'bookmark_url').'">'.
            stripslashes(db_result($result,$i,'bookmark_title')).'</a> ';
	  print '<br /><span class="smaller">'.stripslashes(db_result($result,$i,'bookmark_url'));
	  print '</span></li>';
        }
  print $HTML->box_bottom(1);
}

site_user_footer(array());
