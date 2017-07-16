<?php
# News functions
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev
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


function news_new_subbox($row)
{
  return $row > 1 ? '</div><div class="'.utils_get_alt_row_color($row).'">' : '';
}

# Show a simple list of the latest news items with a link to the forum
function news_show_latest ($group_id,$limit=10,$show_summaries="true",
                           $start_from="no")
{
  global $sys_datefmt;
  if (!isset($group_id))
    {
      $group_id = $GLOBALS['sys_group_id'];
    }

  # We want the total number of news
  $news_total=news_total_number($group_id);


  $params = array();

  # We fetch news item for that group
  if ($group_id != $GLOBALS['sys_group_id'])
    {
      $wclause="news_bytes.group_id=? AND news_bytes.is_approved <> 4 "
               ."AND news_bytes.is_approved <> 5";
      $params[] = $group_id;
    }
  else
    {
      $wclause='news_bytes.is_approved=1';
    }

  $sql="SELECT groups.group_name,groups.unix_group_name,user.user_name,"
     ."news_bytes.forum_id,news_bytes.summary,news_bytes.date,news_bytes.details "
     ."FROM user,news_bytes,groups "
     ."WHERE $wclause "
     ."AND user.user_id=news_bytes.submitted_by "
     ."AND news_bytes.group_id=groups.group_id "
     ."ORDER BY date DESC";

  $sql .= " LIMIT ";
  if ($start_from != 0 && $start_from != "no" && $start_from != "nolinks")
    {
      $sql .= "?,?";
      $params[] = $start_from;
      $params[] = intval($news_total);
    }
  else
    {
      $sql .= "?";
      $params[] = $limit;
    }

  $result = db_execute($sql, $params);
  $rows = db_numrows($result);
  $return = '';

  if (!$result || $rows < 1)
    {
      $return .= news_new_subbox(0).'<h3>'._("No news items found").'</h3>';
    }
  else
    {
      for ($i=0; $i<$rows; $i++)
	{
	  # We want the number of message in this forum
	  $tres_count = db_execute("SELECT group_forum_id FROM forum "
                                   ."WHERE group_forum_id=?",
				   array(db_result($result,$i,'forum_id')));
	  $trow_count = db_numrows($tres_count);
	  if ($show_summaries != "false")
	    {
	      # Get the story
	      $story = rtrim(db_result($result,$i,'details'));

	      # if the news item is large (>500 characters),
	      # only show about 250 characters of the story
	      $strlen_story = strlen($story);
	      if ($strlen_story > 500)
	        {
	          # if there is a linebreak close to the 250 character
	          # mark, we use it to truncate the news item, so that
	          # the markup will not be confused.
	          # We accept the range from 240 to 350 characters, else
	          # the news item will be split on whitespace.
	          # See bug #7634
	          $linebreak = strpos($story, "\n", min($strlen_story, 240));
	          if ($linebreak !== false and $linebreak < 350)
	            {
	              $truncate = $linebreak;
	            }
	          else
	            {
	              $truncate = strrpos(substr($story, 0, 250), ' ');
	              if ($truncate === false)
	                {
	                  $truncate = 250;
	                }
	            }
	          $summ_txt = substr($story, 0, $truncate);
		  $summ_txt .= " ...";
		  $summ_txt = markup_full($summ_txt);
	          $summ_txt .= sprintf(_("%s[Read more]%s"), '<br /><a href="'
                            .$GLOBALS['sys_home'].'forum/forum.php?forum_id='
                            .db_result($result,$i,'forum_id').'">', '</a>');
		}
	      else
	        {
		  # this is a short news item. just display it.
		  $summ_txt = markup_full($story);
		}
	      $proj_name = db_result($result,$i,'group_name');
	    }
	  else
	    {
	      $proj_name='';
	      $summ_txt='';
	    }
      $reply = sprintf(ngettext("%s reply", "%s replies", $trow_count),
                       $trow_count);
      $return .=
        news_new_subbox($i+1)
	.'<a href="'.$GLOBALS['sys_home'].'forum/forum.php?forum_id='
        .db_result($result,$i,'forum_id').'"><strong>'
        .db_result($result,$i,'summary').'</strong></a>';
      if ($show_summaries != "false")
	{ $return .= '<br />&nbsp;&nbsp;&nbsp;&nbsp;'; }
      $return .= ' <span class="smaller"><em>'._("posted by").' <a href="'
	.$GLOBALS['sys_home'].'users/'. db_result($result,$i,'user_name') .'">'
        . db_result($result,$i,'user_name')
	.'</a>, '. utils_format_date(db_result($result,$i,'date')) .' - '
	.$reply.'</em></span>'.$summ_txt;
	}
    }

  if ($start_from != "nolinks")
    {

      # No link is a trick to skip archives + submit news links

      if ($group_id != $GLOBALS['sys_group_id'])
	{
	  # You can only submit news from a project now.
	  # You used to be able to submit general news.
	  $return .= news_new_subbox(0)
	     .'<br /> <a href="'.$GLOBALS['sys_home'].'news/submit.php?group_id='
	     .$group_id.'"><span class="smaller">['._("Submit News")
             .']</span></a>';
	}

      $return .= news_new_subbox(0)
	 .'<br /> <a href="'.$GLOBALS['sys_home'].'news/?group_id='
         .$group_id.'"><span class="smaller">['
	 .sprintf(ngettext("%d news in archive", "%d news in archive",
                           $news_total), $news_total)
         .']</span></a>';
    }
  return $return;
}


function news_total_number($group_id)
{
  # We want the total number of news for a group
  if ($group_id != $GLOBALS['sys_group_id'])
    {
      $wclause="news_bytes.group_id=? AND news_bytes.is_approved <> 4 "
               ."AND news_bytes.is_approved <> 5";
      $params = array($group_id);
    }
  else
    {
      $wclause='news_bytes.is_approved=1';
      $params = array();
    }
  $sql="SELECT count(*) FROM user,news_bytes,groups "
     ."WHERE $wclause "
     ."AND user.user_id=news_bytes.submitted_by "
     ."AND news_bytes.group_id=groups.group_id ";
  return db_result(db_execute($sql, $params),0,0);
}

# Take an ID and returns the corresponding forum name
function get_news_name($id)
{
  $result = db_execute("SELECT summary FROM news_bytes WHERE id=?", array($id));
  if (!$result || db_numrows($result) < 1)
    {
      return _("Not found");
    }
  else
    {
      return db_result($result, 0, 'summary');
    }
}
?>
