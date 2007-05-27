<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007  Sylvain Beucler
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

#input_is_safe();
#mysql_is_safe();

require_once('../include/init.php');
# not yet compliant, kind of messy: register_globals_off();

extract(sane_import('all',
  array('only_group_id', 'type', 'words', 'type_of_search', 'func', 'exact')));

# No words? Ask for them
if (!$words)
{
  search_send_header();
  print '<p>'._("Enter your search words above.").'</p>';
  $HTML->footer(array());
  exit;
}

$result = search_run($words, $type_of_search);

# Print out the results

if ($type_of_search == "soft")
{
  $rows = $rows_returned = db_numrows($result);

  if (!$result || $rows < 1)
    {
      # No result? Stop here.
      search_failed();
    }
  elseif (($rows == 1) && ($GLOBALS['offset'] == 0))
    {
      # Only one result? Redirect, but only if this is the first
      # page to be displayed. Otherwise, if the last page contains
      # just one row, the user will be redirected.
      $res_type = db_execute("SELECT base_host FROM group_type WHERE type_id=?",
			     array(db_result($result, 0, 'type')));
      $project = db_result($result, 0, 'unix_group_name');
      if (db_result($res_type, 0, 'base_host'))
	{ $host = db_result($res_type, 0, 'base_host'); }
      else
	{ $host = $_SERVER['HTTP_HOST']; }

      Header("Location: http".(session_issecure()?'s':'')."://".$host.$GLOBALS['sys_home']."projects/$project");
    }
  else
    {
      # More results? Print them in the respect of max_rows setting.
      if ($rows_returned > $GLOBALS['max_rows'])
	{ $rows = $GLOBALS['max_rows']; }
      search_send_header();

      $title_arr = array();
      $title_arr[] = _("Project");
      $title_arr[] = _("Description");
      $title_arr[] = _("Type");

      print html_build_list_table_top($title_arr);

      print "\n";

      for ( $i = 0; $i < $rows; $i++ )
	{
	  $res_type = db_execute("SELECT base_host,name FROM group_type WHERE type_id=?",
				 array(db_result($result, $i, 'type')));

      if (db_result($res_type, 0, 'base_host'))
	{ $host = db_result($res_type, 0, 'base_host'); }
      else
	{ $host = $_SERVER['HTTP_HOST']; }

      print	'<tr class="'. html_get_alt_row_color($i).'"><td><a href="http'.(session_issecure()?'s':'')."://".$host.$GLOBALS['sys_home']."projects/".db_result($result, $i, 'unix_group_name')."/\">"
	. db_result($result, $i, 'group_name').'</a></td>'
	. '<td>'.db_result($result,$i,'short_description').'</td>'
	. '<td>'.db_result($res_type, 0, 'name')."</td></tr>\n";
	}
      print "</table>\n";

      print '<p> Note that <strong>private</strong> projects are not shown on this page </p>';
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

      $title_arr = array();
      $title_arr[] = _("Login");
      $title_arr[] = _("Name");

      print html_build_list_table_top ($title_arr);

      print "\n";

      for ( $i = 0; $i < $rows; $i++ )
	{
	  $namequery = eregi_replace('[^a-z]+', '+', db_result($result,$i,'realname'));
	  print	"<tr class=\"". html_get_alt_row_color($i) ."\"><td>".
	    utils_user_link(db_result($result, $i, 'user_name'))

	    . "<td>".db_result($result,$i,'realname')."</td></tr>\n";
	}
      print "</table>\n";
    }

}
/*else if ($type_of_search == 'forums')
{

 FIXME: DEACTIVATED RIGHT NOW: no forums.

  $array=explode(" ",$words);
  $words1=implode($array,"%' $crit forum.body LIKE '%");
  $words2=implode($array,"%' $crit forum.subject LIKE '%");

  $sql =	"SELECT forum.msg_id,forum.subject,forum.date,user.user_name "
     . "FROM forum,user "
     . "WHERE user.user_id=forum.posted_by AND ((forum.body LIKE '%$words1%') "
     . "OR (forum.subject LIKE '%$words2%')) AND forum.group_forum_id='$forum_id' "
     . "GROUP BY msg_id,subject,date,user_name LIMIT $offset,26";
  $result = db_query($sql);
  $rows = $rows_returned = db_numrows($result);

  if (!$result || $rows < 1)
    {
      #		$no_rows = 1;
      search_failed();

    }
  elseif (($rows == 1) && ($GLOBALS['offset'] == 0))
    {
      $msg = db_result($result, 0, 'msg_id');
      Header("Location: ".$GLOBALS['sys_home']."forum/message.php?msg_id=$msg");
    }
  else
    {

      if ( $rows_returned > $MAX_ROW)
	{
	  $rows = $MAX_ROW;
	}

      search_send_header();

      $title_arr = array();
      $title_arr[] = _("Thread");
      $title_arr[] = _("Author");
      $title_arr[] = _("Date");

      print html_build_list_table_top ($title_arr);

      print "\n";

      for ( $i = 0; $i < $rows; $i++ )
	{
	  print	"<tr class=\"". html_get_alt_row_color($i) ."\"><td><a href=\"".$GLOBALS['sys_home']."forum/message.php?msg_id="
	    . db_result($result, $i, "msg_id")."\"><img src=\"".$GLOBALS['sys_home']."images/contexts/mail.png\" border=0 height=12 width=10 /> "
	    . db_result($result, $i, "subject")."</a></td>"
	    . "<td>".db_result($result, $i, "user_name")."</td>"
	    . "<td>".utils_format_date(db_result($result,$i,"date"))."</td></tr>\n";
	}
      print "</table>\n";
    }

} */
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
  elseif (($rows == 1) && ($GLOBALS['offset'] == 0 && (db_result($result, $i, 'privacy') != "2")))
    {
      # no automatic redirection for private item, use the usual listing
      $bug = db_result($result, 0, 'bug_id');
      Header("Location: ".$GLOBALS['sys_home'].$type_of_search."/?func=detailitem&item_id=$bug");
    }
  else
    {

      if ( $rows_returned > $GLOBALS['max_rows'])
	{ $rows = $GLOBALS['max_rows']; }

      search_send_header();

      $title_arr = array();
      $title_arr[] = _("Item Id");
      $title_arr[] = _("Item Summary");
      $title_arr[] = _("Group");
      $title_arr[] = _("Submitted By");
      $title_arr[] = _("Date");

      print html_build_list_table_top ($title_arr);

      print "\n";

      $j;
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

	      $url = $GLOBALS['sys_home'].$type_of_search."/?func=detailitem&amp;item_id=".db_result($result, $i, "bug_id");

	      print	'<tr class="'.html_get_alt_row_color($j).'">'
		. '<td><a href="'.$url.'">#'.db_result($result, $i, "bug_id").'</a></td>'
		. '<td><a href="'.$url.'">'.db_result($result, $i, "summary").'</a></td>'
		. '<td><a href="'.$url.'">'.group_getname(db_result($result, $i, "group_id")).'</a></td>'
		. "<td>".utils_user_link(db_result($result, $i, "user_name"))."</td>"
		. "<td>".utils_format_date(db_result($result,$i,"date"))."</td></tr>";
	      $j++;
	    }
	}
      print "</table>\n";
    }
}
else
{
  search_send_header();
  print '<h2 class="error">'._("Error").' - '._("Invalid Search!!").'</h1>';

}

# Print prev/next links
$nextprev_url = $GLOBALS['sys_home']."search/?type_of_search=$type_of_search&amp;words=".urlencode($words);
if (isset($type))
{  $nextprev_url .= "&amp;type=$type"; }
if ($group_id)
{  $nextprev_url .= "&amp;only_group_id=$group_id"; }

html_nextprev($nextprev_url,$rows,$rows_returned);

site_footer(Array());
