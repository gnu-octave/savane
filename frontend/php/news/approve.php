<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
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


require '../include/pre.php';

# FIXME: should use register_globals_off() instead
if ($_POST['group_id'])
   { 
   $group_id= $_POST['group_id']; 
   }
elseif ($_GET['group_id'])
   { 
   $group_id = $_GET['group_id']; 
   }

if ($_POST['post_changes'])
   { 
   $post_changes = $_POST['post_changes']; 
   }
elseif ($_GET['post_changes'])
   { 
   $post_changes = $_GET['post_changes']; 
   }
   
if ($_POST['summary'])
   { 
   $summary = $_POST['summary']; 
   }
elseif ($_GET['summary'])
   { 
   $summary = $_GET['summary']; 
   }
   
if ($_POST['details'])
   { 
   $details = $_POST['details']; 
   }
elseif ($_GET['details'])
   { 
   $details = $_GET['details']; 
   }

if ($_POST['status'])
   { 
   $status = $_POST['status']; 
   }
elseif ($_GET['status'])
   { 
   $status = $_GET['status']; 
   }
   
if ($_POST['approve'])
   { 
   $approve = $_POST['approve']; 
   }
elseif ($_GET['approve'])
   { 
   $approve = $_GET['approve']; 
   }   
if ($_POST['for_group_id'])
   { 
   $for_group_id = $_POST['for_group_id']; 
   }
elseif ($_GET['for_group_id'])
   { 
   $for_group_id = $_GET['for_group_id']; 
   }   
if ($_POST['group'])
   { 
   $group = $_POST['group']; 
   }
elseif ($_GET['group'])
   { 
   $group = $_GET['group']; 
   }      
if ($_POST['id'])
   { 
   $id = $_POST['id']; 
   }
elseif ($_GET['id'])
   { 
   $id = $_GET['id']; 
   }      

# This page can be used to manage the whole news system for a server
# or news for a project.
# That's why, when required, we test if group_id = sys_group_id.

if ($group_id && member_check(0, $group_id,'N3'))
{

  # Modifications are made to the database
  # 0 = locally approved
  # 1 = front page approved
  if ($post_changes && $approve)
    {
      if ($group_id != $GLOBALS['sys_group_id'] &&
	  $status != 0 && $status != 4)
	{
	  # Make sure that an item accepted for front page is not modified
	  $status=0;
	}

      if (user_is_super_user() &&
	  $group_id == $GLOBALS['sys_group_id'])
	{
	  $sql="UPDATE news_bytes SET is_approved='".$status."', date='".time()."', ".
	     "summary='".htmlspecialchars($summary)."',details='".htmlspecialchars($details)."'  WHERE id='$id' AND group_id='$for_group_id'";
	}
      else
	{
	  if ($status == 0)
	    {
	      $sql="UPDATE news_bytes SET is_approved='0', summary='".htmlspecialchars($summary)."', ".
		 "details='".htmlspecialchars($details)."' WHERE id='$id' AND group_id='$group_id'";
	    }
	  elseif ($status == 4)
	    {
	      $sql="UPDATE news_bytes SET is_approved='4', summary='".htmlspecialchars($summary)."', ".
		 "details='".htmlspecialchars($details)."' WHERE id='$id' AND group_id='$group_id'";
	    }
	}

      $result=db_query($sql);

      if (!$result || db_affected_rows($result) < 1)
	{
	  fb(_("Failed to update"),1);
	}
      else
	{
	  fb(_("Project News Item Updated."));
	}

      dbg("STATUS $status, group $group_id");

      # send mails: does not care if it was already approved
      if (($status == 0 && $group_id != $GLOBALS['sys_group_id']) ||
          ($status == 1 && user_is_super_user() && $group_id == $GLOBALS['sys_group_id']))

        {
           # get notification address and submitter id
           $to = db_result(db_query("SELECT new_news_address FROM groups WHERE group_id=$group_id"), 0, 'new_news_address');
           $from = user_getrealname(db_result(db_query("SELECT submitted_by FROM news_bytes WHERE id='$id' AND group_id='$for_group_id'"), 0, 'submitted_by'),1).' <'.$GLOBALS['sys_mail_replyto'].'@'.$GLOBALS['sys_mail_domain'].'>';


           # Run stripslashes to avoid slashes added by magic quotes and 
           sendmail_mail($from, $to, $summary, stripslashes($details), $group_name, 'news');
        }

      # Show the list_queue
      $approve='';
      $list_queue='y';

    }

  # Begin HTML
  site_project_header(array('title'=>_("Manage"),
			    'group'=>$group_id,
			    'context'=>'news'));


  # Form to make modifications to an existing item, to submit one
  if ($approve)
    {

      if (user_is_super_user()  &&
	  $group_id == $GLOBALS['sys_group_id'])
	{
	  $sql="SELECT groups.unix_group_name,news_bytes.*,news_bytes.submitted_by AS submitted_by ".
	     "FROM news_bytes,groups WHERE id='$id' ".
	     "AND news_bytes.group_id=groups.group_id ";

	}
      else
	{
	  $sql="SELECT *,news_bytes.submitted_by AS submitted_by FROM news_bytes WHERE id='$id' AND group_id='$group_id'";
	}

      $result=db_query($sql);
      if (db_numrows($result) < 1)
	{
	  print '<h2 class="error">'._("No pending news").'</h2>';
	  site_project_footer(array());
	  exit;
	}

      if ($group_id == $GLOBALS['sys_group_id'] && !user_is_super_user())
        {
          print '<p class="warn">'._("If you want to approve/edit site news (shown on the front page), you must be logged as superuser.").'</p>';
        }
      elseif ($group_id == $GLOBALS['sys_group_id'] && user_is_super_user())
        {
          print '<p class="warn">'._("If you want to approve/edit news for the local administration project (not shown on the front page), you must end the superuser session.").'</p>';
        } 

      # Found out who is the submitter:
      if (db_result($result,0,'submitted_by') == 0)
        { $submitted_by = "None"; }
      else
        { $submitted_by = user_getname(db_result($result,0,'submitted_by')); }


      print '<p>'._("Submitted by:").' '.utils_user_link($submitted_by, user_getrealname(db_result($result,0,'submitted_by'))).'</p>';
      print '
		<form action="'.$_SERVER['PHP_SELF'].'" method="post">
		<input type="hidden" name="id" value="'.db_result($result,0,'id').'" />';

     # Useless title
     # print '<h3>'.sprintf(_("Approve a news item for %s submitted by %s"),'<a href="'.$GLOBALS['sys_home'].'projects/'.group_getunixname(db_result($result,0,'group_id')).'/">'.group_getname(db_result($result,0,'group_id')).'</a>',utils_user_link($submitted_by)).'</h3>';
      print '
		<input type="hidden" name="approve" value="y" />
		<input type="hidden" name="post_changes" value="y" />';

      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  print '<input type="radio" name="status" value="1" />&nbsp;&nbsp;';
	  print '<span class="preinput">'.sprintf(_("Approve For %s' Front Page"),$GLOBALS['sys_name']).'</span><br />';
	  print '<input type="radio" name="status" value="0" checked="checked" />&nbsp;&nbsp;<span class="preinput">'._("Do Nothing").'</span><br />';
	  print '<input type="radio" name="status" value="2" />&nbsp;&nbsp;<span class="preinput">'._("Refuse").'</span><br />';
	  print '<input type="hidden" name="for_group_id" value="'.db_result($result,0,'group_id').'" />';
	  print '<input type="hidden" name="group_id" value="'.$GLOBALS['sys_group_id'].'" />';
	} 
      else 
        {
	  print '<input type="radio" name="status" value="0" checked="checked" /> &nbsp;&nbsp;<span class="preinput">'._("Display").'</span><br />';
	  print '<input type="radio" name="status" value="4" />&nbsp;&nbsp;<span class="preinput">'._("Delete").'</span><br />';
	  print '<input type="hidden" name="group_id" value="'.db_result($result,0,'group_id').'" />';
	}

      print '<br /><span class="preinput">'
	._("Subject:").'</span><br />&nbsp;&nbsp;
		<input type="text" name="summary" value="'.db_result($result,0,'summary').'" size="65" MAXLENGTH="80" /><br />
		<span class="preinput">'
	.sprintf(_("Details %s:"), markup_info("full")).'</span><br />&nbsp;&nbsp;
		<textarea name="details" ROWS="20" COLS="65" WRAP="SOFT">'.db_result($result,0,'details').'</textarea><p>';
      print '<p>'.sprintf (_("Note: If this item is on the %s home page and you edit it, it will be removed from the home page."),$GLOBALS['sys_name']).'</p>';
      print '<div class="center">
		<input type="submit" name="submit" value="'._("submit").'" /></div>
		</form>';

      print '<h3>'._("Preview:").'</h3>'.markup_full(db_result($result,0,'details'));


    }
  else
    {
      # No item selected
      if ($group_id == $GLOBALS['sys_group_id'] && !user_is_super_user())
        {
          print '<p class="warn">'._("If you want to approve/edit site news (shown on the front page), you must be logged as superuser.").'</p>';
        }
      elseif ($group_id == $GLOBALS['sys_group_id'] && user_is_super_user())
        {
          print '<p class="warn">'._("If you want to approve/edit news for the local administration project (not shown on the front page), you must end the superuser session.").'</p>';
        } 

      $old_date=(time()-(86400*15));

      # Firstly, we show item that require approval
      #   - if site news: it has to be already approved projects (0)
      #     or project submitted on the system site project
      #   - if project news: it has to be proposed news (5)
      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  $sql="SELECT * FROM news_bytes WHERE (is_approved=0 OR (is_approved=5 AND group_id='$group_id')) AND date > '$old_date'";
	}
      else
	{
	  $sql="SELECT * FROM news_bytes WHERE is_approved=5 AND date > '$old_date' AND group_id='$group_id'";
	}

      $result=db_query($sql);
      $rows=db_numrows($result);

      if ($rows < 1)
	{
	  print '<h3>'._("No queued items found").'</h3>';
	}
      else
	{
	  print '<h3>'._("These news items were submitted and need approval").'</h3>
			<ul>';
	  for ($i=0; $i<$rows; $i++)
	    {
	      print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id']){ print ' class="boxhighlight"'; }
            print '><a href="'.$_SERVER['PHP_SELF'].'?approve=1&amp;id='.db_result($result,$i,'id');

	      if ($group_id == $GLOBALS['sys_group_id']) 
                {
		  print '&amp;group='.$GLOBALS['sys_unix_group_name'];
	        }
	      else
		{
		  print '&amp;group_id='.db_result($result,$i,'group_id');
		}

	      print '">';
              if ($group_id == $GLOBALS['sys_group_id']) 
                { print group_getname(db_result($result,$i,'group_id')).' - '; }
              print db_result($result,$i,'summary').'</a></li>';
	    }
	  print '</ul>';
	}

      # Secondly, we show deleted items for this week

      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  $sql="SELECT * FROM news_bytes WHERE (is_approved=2 OR (is_approved=4 AND group_id='$group_id')) AND date > '$old_date'";
	}
      else
	{
	  $sql="SELECT * FROM news_bytes WHERE is_approved=4 AND date > '$old_date' AND group_id='$group_id'";
	}

      $result=db_query($sql);
      $rows=db_numrows($result);

      if ($rows < 1)
	{
	  print '<h3>'
	    ._("No deleted items during these past two weeks").'</h3>';
	}
      else
	{
	  if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	    {
	      print '<h3>'
		._("These items were refused these past two weeks:").'</h3>';
	    }
	  else
	    {
	      print '<h3>'
		._("These items were deleted these past two weeks:").'</h3>';
	    }

	  print '<ul>';
	  for ($i=0; $i<$rows; $i++)
	    {
	      print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id']){ print ' class="boxhighlight"'; }
            print '><a href="'.$_SERVER['PHP_SELF'].'?approve=1&amp;group='.$group_name.'&amp;id='.db_result($result,$i,'id').'">';

              if ($group_id == $GLOBALS['sys_group_id']) 
                { print group_getname(db_result($result,$i,'group_id')).' - '; }
              print db_result($result,$i,'summary').'</a></li>';
	    }
	  print '</ul>';
	}

      # We show all approved items.
      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  $sql="SELECT * FROM news_bytes WHERE (is_approved=1 OR (is_approved=0  AND group_id='$group_id'))";

	}
      else
	{
	  $sql="SELECT * FROM news_bytes WHERE (is_approved=0 OR is_approved=1) AND date > '$old_date' AND group_id='$group_id'";
	}

      $result=db_query($sql);
      $rows=db_numrows($result);

      if ($rows < 1)
	{
	  print '<h3>'
	    ._("No news items approved").'</h3>';
	}
      else
	{
	  print '<h3>'
	    ._("These items were approved:").'</h3><ul>';
	  for ($i=0; $i<$rows; $i++) {
	    print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id']){ print ' class="boxhighlight"'; }
            print '><a href="'.$_SERVER['PHP_SELF'].'?approve=1&amp;group='.$group_name.'&amp;id='.db_result($result,$i,'id').'">';

              if ($group_id == $GLOBALS['sys_group_id']) 
                { print group_getname(db_result($result,$i,'group_id')).' - '; }
              print db_result($result,$i,'summary').'</a></li>';
	  }
	  print '</ul>';
	}

    }

  site_project_footer(array());

}
else
{

  exit_error(_("Action unavailable: only news managers can approve news."));

}

?>
