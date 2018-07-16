<?php
# Display forum.
#
# Copyright (C) 1999-2000 The SourceForge Crew
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
    $ret_val = '';
    # If necessary, insert a new message into the forum.
    if ($post_message == 'y')
      post_message($thread_id, $is_followup_to, $subject, $body, $forum_id);

    # Set up some defaults if they aren't provided.
    if ((!$offset) || ($offset < 0))
      $offset=0;

    if (!$style)
      $style='nested';

    if (!$max_rows || $max_rows < 5)
      $max_rows=25;

/* Take care of setting up/saving prefs.

If they're logged in and a "custom set" was NOT just POSTed,
see if they have a pref set if so, use it
if it was a custom set just posted && logged in, set pref if it's changed.  */
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
                  user_set_preference ('forum_style',$_pref);
              }
            else
              user_set_preference ('forum_style',$_pref);
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
    # Set up navigation vars.
    $result=db_execute("SELECT group_id,forum_name,is_public "
                       ."FROM forum_group_list WHERE group_forum_id=?",
                       array($forum_id));
    if (db_numrows($result) == 0)
      exit_error(_("This forum ID doesn't exist."));
    $group_id=db_result($result,0,'group_id');
    $forum_name=db_result($result,0,'forum_name');

  # This forum_header writes the complete news item out
  # including the  comments \n Monitor Forum | Save Place | Admin bar
  # in case it is news
  # otherwise it pretends this item does not exist.
  # marcus marker
    forum_header(array('group'=>$group_id,'context'=>'forum','title'=>$forum_name));

    if (db_result($result,0,'is_public') != '1')
      {
        if (!user_isloggedin() || !user_ismember($group_id))
          {
            # If this is a private forum, kick them out.
            print '<h1>Forum is restricted</h1>
';
            forum_footer(array());
            exit;
          }
      }
    # Now setup the query.
    $threading_sql = '';
    if ($style == 'nested' || $style== 'threaded' )
      {
        # The flat and 'no comments' view just selects the most recent
        # messages out of the forum.
        # The other views just want the top message in a thread so they
        # can recurse.
        $threading_sql='AND forum.is_followup_to=0';
      }
    $result = db_execute("SELECT user.user_name,user.realname,"
                         ."forum.has_followups,user.user_id,forum.msg_id,"
                         ."forum.group_forum_id,forum.subject,forum.thread_id,"
                         ."forum.body,forum.date,forum.is_followup_to, "
                         ."forum_group_list.group_id  "
                         ."FROM forum,user,forum_group_list "
                         ."WHERE forum.group_forum_id = ? "
                         ."AND user.user_id=forum.posted_by $threading_sql "
                         ."AND forum_group_list.group_forum_id = forum.group_forum_id "
                         ."ORDER BY forum.date DESC LIMIT ?,?",
                         array($forum_id, $offset, $max_rows+1));
    $rows=db_numrows($result);
    if ($rows > $max_rows)
      $rows=$max_rows;
    $total_rows=0;

    if (!$result || $rows < 1)
      {
        $ret_val .= '<p>No messages in <em>'.$forum_name .'</em></p>
<p>'. db_error()."</p>\n";
      }
    else
      {
      # Build table header.

      # Create a pop-up select box listing the forums for this project.
      # Determine if this person can see private forums or not.
        if (user_isloggedin() && user_ismember($group_id))
          $public_flag='0,1';
        else
          $public_flag='1';
      # Create a pop-up select box showing options for viewing threads.

        $vals=array('nested','flat','threaded','nocomments');
        $texts=array(
# TRANSLATORS: this is forum style to select.
                     _('Nested'),
# TRANSLATORS: this is forum style to select.
                     _('Flat'),
# TRANSLATORS: this is forum style to select.
                     _('Threaded'),
# TRANSLATORS: this is forum style to select.
                     _('No Comments'));
        $options_popup=html_build_select_box_from_arrays ($vals, $texts, 'style',
                                                          $style, false, 'None',
                                                          false, 'Any', false,
                                                          _("forum style"));

      # Create a pop-up select box showing options for max_row count.
        $vals=array(25,50,75,100);
        $texts=array(_('Show 25'),_('Show 50'),_('Show 75'),_('Show 100'));
        $max_row_popup=html_build_select_box_from_arrays ($vals,$texts,
                                                          'max_rows',
                                                          $max_rows, false,
                                                          'None', false, 'Any',
                                                          false,
                                                          _("rows per page"));
      # Now show the popup boxes in a form.
        $ret_val .= '<table border="0" width="50%">
<form action="'. htmlentities ($_SERVER['PHP_SELF'] ).'" METHOD="get">
<input type="hidden" name="set" value="custom">
<input type="hidden" name="forum_id" value="'.htmlspecialchars($forum_id).'">
<tr>
<td><span class="smaller">'. $options_popup . '</span></td>
<td><span class="smaller">'. $max_row_popup . '</span></td>
<td><span class="smaller"><input type="submit" name="submit" value="'
._('Change View').'"></span></td>
</tr>
</form></table>
';
        if ($style == 'nested')
          {
          # No top table row for nested threads.
          }
        else
          {
           # Threaded, no comments, or flat; display
           # different header for default threading and flat now.
            $title_arr=array();
            $title_arr[]=_('Thread');
            $title_arr[]=_('Author');
            $title_arr[]=_('Date');
            $ret_val .= html_build_list_table_top ($title_arr);
          }

        $i=0;
        while (($total_rows < $max_rows) && ($i < $rows))
          {
            $total_rows++;
            if ($style == 'nested')
              {
              # New slashdot-inspired nested threads,
              # showing all submessages and bodies.

              # Show this one message.
                $ret_val .= forum_show_a_nested_message ( $result,$i ).'<br />
';
                if (db_result($result,$i,'has_followups') > 0)
                  {
                    # Show submessages for this message.
                    $ret_val .= forum_show_nested_messages (db_result($result,$i,
                                                                      'thread_id'),
                                                            db_result($result,$i,
                                                                      'msg_id'));
                  }
              }
            elseif ($style == 'flat')
              {
                # Just show the message boxes one after another.
                $ret_val .= forum_show_a_nested_message ( $result,$i ).'<BR>';
              }
            else
              {
              /* No comments or threaded use the "old" colored-row style
                phorum-esque threaded list of messages,
                not showing message bodies. */
                $ret_val .= '
<tr class="'. utils_get_alt_row_color($total_rows) .'">
<td><a href="'.$GLOBALS['sys_home'].'forum/message.php?msg_id='
              . db_result($result, $i, 'msg_id').'">'
              . '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
              .'.theme/contexts/mail.png" border=0 height=12 width=12> ';
              /* See if this message is new or not.
                 If so, highlite it in bold. */
                if (get_forum_saved_date($forum_id) < db_result($result,$i,'date'))
                  $ret_val .= '<strong>';
              # Show the subject and poster.
                $ret_val .= db_result($result, $i, 'subject').'</a></td>
<td>'.db_result($result, $i, 'user_name').'</td>
<td>'.utils_format_date(db_result($result,$i,'date')).'</td>
</tr>
';
        /* Show subjects for submessages in this thread.
           show_submessages() is recursive. */
                if ($style == 'threaded')
                  {
                    if (db_result($result,$i,'has_followups') > 0)
                      $ret_val .= show_submessages(db_result($result, $i,
                                                             'thread_id'),
                                                   db_result($result, $i,
                                                             'msg_id'),1,0);
                  }
              }
            $i++;
          }
      # This code puts the nice next/prev.
        if ($style=='nested' || $style=='flat')
          $ret_val .= '<table width="100%" border="0">';
        $ret_val .= '
<tr class="boxitemalt">
<td width="50%">';
        if ($offset != 0)
          {
            $ret_val .= '<a href="javascript:history.back()">
<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME
.'.theme/arrows/previous.png" height="24" width="24" border="0" align="middle" />'
                                .'<strong>'._('Previous Messages').'</strong></a>';
          }
        else
          $ret_val .= '&nbsp;';
        $ret_val .= '</td>
<td>&nbsp;</td><td align="right" width="50%">';
        if (db_numrows($result) > $i)
          {
            $ret_val .= '<a href="'.$GLOBALS['sys_home']
.'forum/forum.php?max_rows='.$max_rows.'&amp;style='
.$style.'&amp;offset='.($offset+$i).'&amp;forum_id='.$forum_id.'">
<strong>'._('Next Messages').'</strong><img src="'.$GLOBALS['sys_home']
.'images/'.SV_THEME.'.theme/arrows/next.png" height="24" width="24" '
.'border="0" align="middle" /></a>';
          }
        else
          $ret_val .= '&nbsp;';
        $ret_val .= '</table>
';
      }
    print $ret_val;
    print '<p>&nbsp;</p>
';
    if ($GLOBALS['sys_enable_forum_comments'])
      {
        print '<h2>'.html_anchor(_("Start a New Thread:"), "newthread").'</h2>
';
        show_post_form($forum_id);
      }
    forum_footer(array());
  }
else
  {
    forum_header(array('title'=>_('Error')));
    print '<p>'._('Error - choose a forum first').'</p>';
    forum_footer(array());
  }
?>
