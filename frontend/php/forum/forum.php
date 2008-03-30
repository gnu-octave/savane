<?php
// This file is part of the Savane project
// <http://gna.org/projects/savane/>
//
// $Id$
//
//  Copyright 1999-2000 (c) The SourceForge Crew
//
// The Savane project is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// The Savane project is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with the Savane project; if not, write to the Free Software
// Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


require_once('../include/init.php');
require_once('../include/sane.php');
require_once('../include/database.php');
require_once('../include/news/forum.php');
require_once('../include/news/general.php');
require_directory("trackers");

extract(sane_import('request', array('forum_id')));
extract(sane_import('get', array('offset', 'style', 'max_rows', 'set')));
extract(sane_import('post',
  array(
    'post_message', // flag
    'subject', 'body', // content
    'is_followup_to', // reply to which msg?
    'thread_id', // new or existing thread (ie call from message.php)?
    )));

if ($forum_id)
{
  // Final output:
  $ret_val = '';

  /*
		if necessary, insert a new message into the forum
  */
  if ($post_message == 'y')
    {
      post_message($thread_id, $is_followup_to, $subject, $body, $forum_id);
    }

  /*
		set up some defaults if they aren't provided
  */
  if ((!$offset) || ($offset < 0))
    {
      $offset=0;
    }

  if (!$style)
    {
      $style='nested';
    }

  if (!$max_rows || $max_rows < 5)
    {
      $max_rows=25;
    }

  /*
		take care of setting up/saving prefs

		If they're logged in and a "custom set" was NOT just POSTed,
			see if they have a pref set
				if so, use it
			if it was a custom set just posted && logged in, set pref if it's changed
  */
  if (user_isloggedin())
    {
      $_pref=$style.'|'.$max_rows;
      if ($set=='custom')
	{
	  if (user_get_preference('forum_style'))
	    {
	      $_pref=$style.'|'.$max_rows;
	      if ($_pref == user_get_preference('forum_style'))
		{
		  //do nothing - pref already stored
		}
	      else
		{
		  //set the pref
		  user_set_preference ('forum_style',$_pref);
		}
	    }
	  else
	    {
	      //set the pref
	      user_set_preference ('forum_style',$_pref);
	    }
	}
      else
	{
	  if (user_get_preference('forum_style'))
	    {
	      $_pref_arr=explode ('|',user_get_preference('forum_style'));
	      $style=$_pref_arr[0];
	      $max_rows=$_pref_arr[1];
	    }
	  else
	    {
	      //no saved pref and we're not setting
	      //one because this is all default settings
	    }
	}
    }



  /*
		Set up navigation vars
  */
  $result=db_execute("SELECT group_id,forum_name,is_public FROM forum_group_list WHERE group_forum_id=?",
		     array($forum_id));
  if (db_numrows($result) == 0)
    exit_error(_("This forum ID doesn't exist."));
  $group_id=db_result($result,0,'group_id');
  $forum_name=db_result($result,0,'forum_name');

  // this forum_header writes the complete news item out
  // including the  comments \n Monitor Forum | Save Place | Admin bar
  // in case it is news
  // otherwise it pretends this item does not exist.
  // marcus marker
  forum_header(array('group'=>$group_id,'context'=>'forum','title'=>$forum_name));


  //private forum check
  if (db_result($result,0,'is_public') != '1')
    {
      if (!user_isloggedin() || !user_ismember($group_id))
	{
	  /*
				If this is a private forum, kick 'em out
	  */
	  print '<h1>Forum is restricted</H1>';
	  forum_footer(array());
	  exit;
	}
    }

  //now set up the query
  $threading_sql = '';
  if ($style == 'nested' || $style== 'threaded' )
    {
      //the flat and 'no comments' view just selects the most recent messages out of the forum
      //the other views just want the top message in a thread so they can recurse.
      $threading_sql='AND forum.is_followup_to=0';
    }

  $result = db_execute("SELECT user.user_name,user.realname,forum.has_followups,user.user_id,forum.msg_id,forum.group_forum_id,forum.subject,forum.thread_id,forum.body,forum.date,forum.is_followup_to, forum_group_list.group_id  ".
     "FROM forum,user,forum_group_list WHERE forum.group_forum_id = ? AND user.user_id=forum.posted_by $threading_sql AND forum_group_list.group_forum_id = forum.group_forum_id ".
     "ORDER BY forum.date DESC LIMIT ?,?", array($forum_id, $offset, $max_rows+1));
  $rows=db_numrows($result);


  if ($rows > $max_rows)
    {
      $rows=$max_rows;
    }
  $total_rows=0;

  if (!$result || $rows < 1)
    {
      //empty forum
      $ret_val .= 'No messages in <em>'.$forum_name .'</em><P>'. db_error();
    }
  else
    {

      /*

      build table header

      */

      //create a pop-up select box listing the forums for this project
      //determine if this person can see private forums or not
      if (user_isloggedin() && user_ismember($group_id))
	{
	  $public_flag='0,1';
	}
      else
	{
	  $public_flag='1';
	}
      /*
          yeupou@gnu.org, 2003-11-24:
          This is broken, it prints every forum existing in the database,
          deactivate it for now.

          Forum are not supported in their current shape. Improvements
          should first be made in the direction of code cleaning and respect
          of the methods used in others parts of the code.

       if ($group_id==$GLOBALS['sys_group_id'])
	{ // NEWS ADMIN
	  print '<INPUT TYPE="HIDDEN" NAME="forum_id" VALUE="'.$forum_id.'">';
	}
      else
	{
	  $res=db_query("SELECT group_forum_id,forum_name ".
			"FROM forum_group_list ".
			"WHERE group_id='$group_id' AND is_public IN ($public_flag)");
	  $vals=utils_result_column_to_array($res,0);
	  $texts=utils_result_column_to_array($res,1);

	  $forum_popup = html_build_select_box_from_arrays ($vals,$texts,'forum_id',$forum_id,false);
	} */

      //create a pop-up select box showing options for viewing threads

      $vals=array('nested','flat','threaded','nocomments');
      $texts=array('Nested','Flat','Threaded','No Comments');

      $options_popup=html_build_select_box_from_arrays ($vals,$texts,'style',$style,false);

      //create a pop-up select box showing options for max_row count
      $vals=array(25,50,75,100);
      $texts=array('Show 25','Show 50','Show 75','Show 100');

      $max_row_popup=html_build_select_box_from_arrays ($vals,$texts,'max_rows',$max_rows,false);

      //now show the popup boxes in a form
      $ret_val .= '<TABLE BORDER="0" WIDTH="50%">
				<FORM ACTION="'. $_SERVER['PHP_SELF'] .'" METHOD="get">
				<INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
				<INPUT TYPE="HIDDEN" NAME="forum_id" VALUE="'.$forum_id.'">
				<TR>'.
         // '<TD><span class="smaller">'. $forum_popup . '</span></TD>'.
         '<TD><span class="smaller">'. $options_popup . '</span></TD>'.
	 '<TD><span class="smaller">'. $max_row_popup . '</span></TD>'.
         '<TD><span class="smaller"><INPUT TYPE="SUBMIT" NAME="SUBMIT" VALUE="Change View"></span></TD>'.
	 '</TR></TABLE></FORM>';

      if ($style == 'nested')
	{
	  /*
				no top table row for nested threads
	  */
	}
      else
	{
	  /*
				threaded, no comments, or flat display

				different header for default threading and flat now
	  */

	  $title_arr=array();
	  $title_arr[]='Thread';
	  $title_arr[]='Author';
	  $title_arr[]='Date';

	  $ret_val .= html_build_list_table_top ($title_arr);

	}

      $i=0;
      while (($total_rows < $max_rows) && ($i < $rows))
	{
	  $total_rows++;
	  if ($style == 'nested')
	    {
	      /*
					New slashdot-inspired nested threads,
					showing all submessages and bodies
	      */
	      //show this one message
	      $ret_val .= forum_show_a_nested_message ( $result,$i ).'<BR>';

	      if (db_result($result,$i,'has_followups') > 0)
		{
		  //show submessages for this message
		  $ret_val .= forum_show_nested_messages ( db_result($result,$i,'thread_id'), db_result($result,$i,'msg_id') );
		}
	    } else if ($style == 'flat')
	      {

		//just show the message boxes one after another

		$ret_val .= forum_show_a_nested_message ( $result,$i ).'<BR>';
	      }
	  else
	    {
	      /*
					no-comments or threaded use the "old" colored-row style

					phorum-esque threaded list of messages,
					not showing message bodies
	      */

	      $ret_val .= '
					<TR class="'. utils_get_alt_row_color($total_rows) .'"><TD><A HREF="'.$GLOBALS['sys_home'].'forum/message.php?msg_id='.
		 db_result($result, $i, 'msg_id').'">'.
		 '<IMG SRC="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/contexts/mail.png" BORDER=0 HEIGHT=12 WIDTH=12> ';
	      /*

	      See if this message is new or not
					If so, highlite it in bold

	      */
	      if (get_forum_saved_date($forum_id) < db_result($result,$i,'date'))
		{
		  $ret_val .= '<strong>';
		}
	      /*
					show the subject and poster
	      */
	      $ret_val .= db_result($result, $i, 'subject').'</A></TD>'.
		 '<TD>'.db_result($result, $i, 'user_name').'</TD>'.
		 '<TD>'.utils_format_date(db_result($result,$i,'date')).'</TD></TR>';

	      /*

	      Show subjects for submessages in this thread

					show_submessages() is recursive

	      */
	      if ($style == 'threaded')
		{
		  if (db_result($result,$i,'has_followups') > 0)
		    {
		      $ret_val .= show_submessages(db_result($result, $i, 'thread_id'),
						   db_result($result, $i, 'msg_id'),1,0);
		    }
		}
	    }
	  $i++;
	}

      /*
			This code puts the nice next/prev.
      */
      if ($style=='nested' || $style=='flat')
	{
	  $ret_val .= '<TABLE WIDTH="100%" BORDER="0">';
	}
      $ret_val .= '
				<TR class="boxitemalt"><TD WIDTH="50%">';
      if ($offset != 0)
	{
	  $ret_val .= '<a href="javascript:history.back()">
				<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/previous.png" height="24" width="24" border="0" align="middle" />'
                                .'<strong>'._('Previous Messages').'</strong></a>';
	}
      else
	{
	  $ret_val .= '&nbsp;';
	}

      $ret_val .= '</TD><TD>&nbsp;</TD><TD ALIGN="RIGHT" WIDTH="50%">';
      if (db_numrows($result) > $i)
	{
	  $ret_val .= '<a href="'.$GLOBALS['sys_home'].'forum/forum.php?max_rows='.$max_rows.'&amp;style='.$style.'&amp;offset='.($offset+$i).'&amp;forum_id='.$forum_id.'">
				<strong>'
                                ._('Next Messages').'</strong><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/next.png" height="24" width="24" border="0" align="middle" /></a>';
	}
      else
	{
	  $ret_val .= '&nbsp;';
	}
      $ret_val .= '</TABLE>';
    }

  print $ret_val;

  print '<p>&nbsp;</p>';

  print '<h3>'.html_anchor(_("Start a New Thread:"), "newthread").'</h3>';
  show_post_form($forum_id);

  forum_footer(array());
}
else
{
  forum_header(array('title'=>'Error'));
  print '<H1>Error - choose a forum first</H1>';
  forum_footer(array());
}
