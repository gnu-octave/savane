<?php
# Display search form and search results
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017  Ineiev
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

extract(sane_import('request',
  array('only_group_id', 'type', 'words', 'type_of_search',
	'func', 'exact',
	'offset', 'max_rows',
)));

# No words? Ask for them
if (!$words)
{
  search_send_header();
  print '<p>'._("Enter your search words above.").'</p>
';
  $HTML->footer(array());
  exit;
}

$result = search_run($words, $type_of_search);

# Print out the results

if ($type_of_search == 'soft')
{
  $rows = $rows_returned = db_numrows($result);

  if (!$result || $rows < 1)
    {
      # No result? Stop here.
      search_failed();
    }
  else
    {
      # More results? Print them in the respect of max_rows setting.
      if ($rows_returned > $GLOBALS['max_rows'])
	{ $rows = $GLOBALS['max_rows']; }
      search_send_header();

      search_exact($words);

      print_search_heading();
      $title_arr = array();
      $title_arr[] = _("Project");
      $title_arr[] = _("Description");
      $title_arr[] = _("Type");

      print html_build_list_table_top($title_arr);

      print "\n";

      for ( $i = 0; $i < $rows; $i++ )
	{
	  $res_type = db_execute("SELECT name FROM group_type WHERE type_id=?",
				 array(db_result($result, $i, 'type')));

	  print	'<tr class="'. html_get_alt_row_color($i)
                .'"><td><a href="../projects/'.db_result($result, $i,
                                                         'unix_group_name').'">'
	    . db_result($result, $i, 'group_name').'</a></td>
<td>'.db_result($result,$i,'short_description').'</td>
<td>'.db_result($res_type, 0, 'name')."</td>\n</tr>\n";
	}
      print "</table>\n";

      print '<p>'
._('Note that <strong>private</strong> projects are not shown on this page.')
.'</p>
';
    }

}
else if ($type_of_search == "people")
{
  $rows = $rows_returned = db_numrows($result);

  if (!$result || $rows < 1)
    {
      search_failed();
    }
  elseif (($rows == 1) && ($GLOBALS['offset'] == 0))
    {
      $user = db_result($result, 0, 'user_name');
      Header("Location: ".$GLOBALS['sys_home']."users/$user");
    }
  else
    {
      if ( $rows_returned > $GLOBALS['max_rows'])
	{ $rows = $GLOBALS['max_rows']; }

      search_send_header();

      print_search_heading();

      $title_arr = array();
      $title_arr[] = _("Login");
      $title_arr[] = _("Name");

      print html_build_list_table_top ($title_arr);

      print "\n";

      for ( $i = 0; $i < $rows; $i++ )
	{
	  $namequery = eregi_replace('[^a-z]+', '+', db_result($result,$i,
                                                               'realname'));
	  print	"<tr class=\"". html_get_alt_row_color($i) ."\"><td>".
	    utils_user_link(db_result($result, $i, 'user_name'))

	    . "</td>\n<td>".db_result($result,$i,'realname')."</td>\n</tr>\n";
	}
      print "</table>\n";
    }

}
else if ($type_of_search == 'bugs' ||
	 $type_of_search == 'support' ||
	 $type_of_search == 'patch' ||
	 $type_of_search == 'cookbook' ||
	 $type_of_search == 'task')
{
  $rows = $rows_returned = db_numrows($result);

  if ( !$result || $rows < 1)
    {
      search_failed();
    }
  elseif (($rows == 1) && ($GLOBALS['offset'] == 0
          && (db_result($result, 0, 'privacy') != "2")))
    {
      # no automatic redirection for private item, use the usual listing
      $bug = db_result($result, 0, 'bug_id');
      Header("Location: ".$GLOBALS['sys_home'].$type_of_search
             ."/?func=detailitem&item_id=$bug");
    }
  else
    {

      if ( $rows_returned > $GLOBALS['max_rows'])
	{ $rows = $GLOBALS['max_rows']; }

      search_send_header();
      print_search_heading();

      $title_arr = array();
      $title_arr[] = _("Item Id");
      $title_arr[] = _("Item Summary");
      $title_arr[] = _("Group");
      $title_arr[] = _("Submitted By");
      $title_arr[] = _("Date");

      print html_build_list_table_top ($title_arr);

      print "\n";

      $j = 0;
      for ( $i = 0; $i < $rows; $i++ )
	{
          # Do even show private item
	  if (db_result($result, $i, 'privacy') == "2" &&
	      !member_check_private(0, $group_id)  &&
	      db_result($result,$i,'user_name') != user_getname())
	    {
	      dbg("Private item.");
	    }
	  else
	    {

	      $url = $GLOBALS['sys_home'].$type_of_search
                     ."/?func=detailitem&amp;item_id=".db_result($result, $i,
                                                                 "bug_id");

	      print	'<tr class="'.html_get_alt_row_color($j).'">'
		. '<td><a href="'.$url.'">#'.db_result($result, $i, "bug_id")
                .'</a></td>
<td><a href="'.$url.'">'.db_result($result, $i, "summary").'</a></td>
<td><a href="'.$url.'">'.group_getname(db_result($result, $i, "group_id"))
.'</a></td>
<td>'.utils_user_link(db_result($result, $i, "user_name"))."</td>
<td>".utils_format_date(db_result($result,$i,"date"))."</td>\n</tr>\n";
	      $j++;
	    }
	}
      print "</table>\n";
    }
}
else
{
  search_send_header();
  print '<p class="error">'._("Error").' - '._("Invalid Search!!").'</p>
';
}

# Print prev/next links
$nextprev_url = $GLOBALS['sys_home']
                ."search/?type_of_search=$type_of_search&amp;words="
                .urlencode($words);
if (isset($type))
{  $nextprev_url .= "&amp;type=$type"; }
if ($group_id)
{  $nextprev_url .= "&amp;only_group_id=$group_id"; }

html_nextprev($nextprev_url,$rows,$rows_returned);

site_footer(Array());
?>
