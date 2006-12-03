<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2004-2005 (c) Mathieu Roy <yeupou--gnu.org>
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

function news_new_subbox($row)
{
  return $row > 1 ? '</div><div class="'.utils_get_alt_row_color($row).'">' : '';
}

function news_show_latest ($group_id,$limit=10,$show_summaries="true",$start_from="no")
{
  global $sys_datefmt;
  /*
		Show a simple list of the latest news items with a link to the forum
  */
  if (!isset($group_id))
    {
      $group_id = $GLOBALS['sys_group_id'];
    }

  # We want the total number of news
  $news_total=news_total_number($group_id);

  # We fetch news item for that group
  if ($group_id != $GLOBALS['sys_group_id'])
    {
      $wclause="news_bytes.group_id='$group_id' AND news_bytes.is_approved <> 4 AND news_bytes.is_approved <> 5";
    }
  else
    {
      $wclause='news_bytes.is_approved=1';
    }

  $sql="SELECT groups.group_name,groups.unix_group_name,user.user_name,news_bytes.forum_id,news_bytes.summary,news_bytes.date,news_bytes.details ".
     "FROM user,news_bytes,groups ".
     "WHERE $wclause ".
     "AND user.user_id=news_bytes.submitted_by ".
     "AND news_bytes.group_id=groups.group_id ".
     "ORDER BY date DESC";

  $sql .= " LIMIT ";

  if ($start_from != 0 && $start_from != "no" && $start_from != "nolinks")
    {
      $sql .= "$start_from,$news_total";
    }
  else
    {
      $sql .= "$limit";
    }

  $result=db_query($sql);
  $rows=db_numrows($result);
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
	  $tres_count = db_query("SELECT group_forum_id FROM forum WHERE group_forum_id='". db_result($result,$i,'forum_id') ."'");
	  $trow_count = db_numrows($tres_count);
	  if ($show_summaries != "false")
	    {
	      # Get the story
	      $story = rtrim(db_result($result,$i,'details'));

	      # if the news item is large (>500 characters or >30 words),
	      # only show about 250 characters of the story
	      $strlen_story = strlen($story);
	      if ($strlen_story > 500 || str_word_count($story) > 30)
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
	          $summ_txt .= sprintf(_("%s[Read more]%s"), '<br /><a href="'.$GLOBALS['sys_home'].'forum/forum.php?forum_id='.db_result($result,$i,'forum_id').'">', '</a>');
		}
	      else
	        {
		  # this is a short news item. just display it.
		  # FIXME: actually counting the number of words may not be
		  # enough, as it may have list in here.
		  $summ_txt = markup_full($story);
		}
	      $proj_name = db_result($result,$i,'group_name');
	    }
	  else
	    {
	      $proj_name='';
	      $summ_txt='';
	    }
      $reply = sprintf(ngettext("%s reply", "%s replies", $trow_count), $trow_count);

      $return .=
        news_new_subbox($i+1)
	.'<a href="'.$GLOBALS['sys_home'].'forum/forum.php?forum_id='
        .db_result($result,$i,'forum_id').'"><strong>'
        .db_result($result,$i,'summary').'</strong></a>';
      if ($show_summaries != "false")
	{ $return .= '<br />&nbsp;&nbsp;&nbsp;&nbsp;'; }
      $return .= ' <span class="smaller"><em>'._("posted by").' <a href="'
	.$GLOBALS['sys_home'].'users/'. db_result($result,$i,'user_name') .'">'. db_result($result,$i,'user_name')
	.'</a>, '. format_date($sys_datefmt,db_result($result,$i,'date')) .' - '
	.$reply.'</em></span>'
        .$summ_txt;

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
	     .$group_id.'"><span class="smaller">['._("Submit News").']</span></a>';
	}

      $return .= news_new_subbox(0)
	 .'<br /> <a href="'.$GLOBALS['sys_home'].'news/?group_id='.$group_id.'"><span class="smaller">['
	 .sprintf(ngettext("%d news in archive", "%d news in archive", $news_total), $news_total)
         .']</span></a>';
    }

  return $return;
}


function news_total_number($group_id)
{
  # We want the total number of news for a group
  if ($group_id != $GLOBALS['sys_group_id'])
    {
      $wclause="news_bytes.group_id='$group_id' AND news_bytes.is_approved <> 4 AND news_bytes.is_approved <> 5";
    }
  else
    {
      $wclause='news_bytes.is_approved=1';
    }
  $sql="SELECT count(*) FROM user,news_bytes,groups ".
     "WHERE $wclause ".
     "AND user.user_id=news_bytes.submitted_by ".
     "AND news_bytes.group_id=groups.group_id ";
  return db_result(db_query($sql),0,0);
}


function get_news_name($id)
{
  /*
		Takes an ID and returns the corresponding forum name
  */
  $sql="SELECT summary FROM news_bytes WHERE id='$id'";
  $result=db_query($sql);
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
