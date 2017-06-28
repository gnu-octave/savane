<?php
# News approval, with or without superadmin privs
# Copyright (C) 1999-2000  The SourceForge Crew
# Copyright (C) 2002-2006  Mathieu Roy <yeupou--gnu.org>
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
require_once('../include/sendmail.php');


extract(sane_import('all',
  array('id',
	'update', 'form_id',
	'post_changes', 'summary', 'details',
	'status', 'approve', 'for_group_id')));

// This page can be used to manage the whole news system for a server
// or news for a project.
// That's why, when required, we test if group_id = sys_group_id.

if ($group_id && member_check(0, $group_id, 'N3'))
{

  // Modifications are made to the database
  // 0 = locally approved
  // 1 = front page approved
  if ($post_changes && $approve)
    {
      if ($group_id != $GLOBALS['sys_group_id'] &&
	  $status != 0 && $status != 4)
	{
	  // Make sure that an item accepted for front page is not modified
	  $status=0;
	}

      $result = false;
      if (user_is_super_user() &&
	  $group_id == $GLOBALS['sys_group_id'])
	{
	  $fields = array('is_approved' => $status,
                          'date' => time(),
                          'date_last_edit' => time(),
                          'summary' => htmlspecialchars($summary),
                          'details' => htmlspecialchars($details));
	  $result = db_autoexecute('news_bytes', $fields, DB_AUTOQUERY_UPDATE,
				   "id=? AND group_id=?",
                                   array($id, $for_group_id));

	}
      elseif ($status == 0 || $status == 4)
	{
	  $fields = array('is_approved' => $status,
                          'date_last_edit' => time(),
			  'summary' => htmlspecialchars($summary),
			  'details' => htmlspecialchars($details));
	  $result = db_autoexecute('news_bytes', $fields, DB_AUTOQUERY_UPDATE,
				   "id=? AND group_id=?", array($id, $group_id));
	}

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
          ($status == 1 && user_is_super_user()
           && $group_id == $GLOBALS['sys_group_id']))

        {
           # get notification address and submitter id
           $to = db_result(db_execute("SELECT new_news_address "
                                      ."FROM groups WHERE group_id=?",
                                      array($group_id)),
			   0, 'new_news_address');

	   $res = db_execute("SELECT submitted_by FROM news_bytes "
                             ."WHERE id=? AND group_id=?",
			     array($id, $for_group_id));
	   if (db_numrows($res) > 0) {
	     $from = user_getrealname(db_result($res, 0, 'submitted_by'),1)
                     .' <'.$GLOBALS['sys_mail_replyto'].'@'
                     .$GLOBALS['sys_mail_domain'].'>';
	     
	     sendmail_mail($from, $to, $summary, $details, $group, 'news');
	   }
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
	  $result = db_execute("SELECT groups.unix_group_name,"
             ."news_bytes.*,news_bytes.submitted_by AS submitted_by
	     FROM news_bytes,groups WHERE id=?
               AND news_bytes.group_id=groups.group_id",
	     array($id));

	}
      else
	{
	  $result = db_execute("SELECT *,news_bytes.submitted_by "
            ."AS submitted_by FROM news_bytes
            WHERE id=? AND group_id=?",
	    array($id, $group_id));
	}

      if (db_numrows($result) < 1)
	{
	  print '<h2 class="error">'._("No pending news").'</h2>';
	  site_project_footer(array());
	  exit;
	}

      if ($group_id == $GLOBALS['sys_group_id'] && !user_is_super_user())
        {
          print '<p class="warn">'
._("If you want to approve/edit site news (shown on the front page), you must
be logged as superuser.").'</p>
';
        }
      elseif ($group_id == $GLOBALS['sys_group_id'] && user_is_super_user())
        {
          print '<p class="warn">'
._("If you want to approve/edit news for the local administration project (not
shown on the front page), you must end the superuser session.").'</p>
';
        } 

      # Found out who is the submitter:
      if (db_result($result,0,'submitted_by') == 0)
        { $submitted_by = "None"; }
      else
        { $submitted_by = user_getname(db_result($result,0,'submitted_by')); }


      print '<p>'._("Submitted by:").' '
            .utils_user_link($submitted_by,
                             user_getrealname(db_result($result,0,
                                              'submitted_by'))).'</p>
';
      print '
		<form action="'.$_SERVER['PHP_SELF'].'" method="post">
		<input type="hidden" name="id" value="'
                .db_result($result,0,'id').'" />';

      print '
		<input type="hidden" name="approve" value="y" />
		<input type="hidden" name="post_changes" value="y" />
';

      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  print '<input type="radio" name="status" value="1" />&nbsp;&nbsp;';
# TRANSLATORS: the argument is site name (like Savannah).
	  print '<span class="preinput">'
.sprintf(_("Approve For %s' Front Page"),$GLOBALS['sys_name'])
.'</span><br />
<input type="radio" name="status" value="0" '
.'checked="checked" />&nbsp;&nbsp;<span class="preinput">'
._("Do Nothing").'</span><br />
<input type="radio" name="status" value="2" />&nbsp;&nbsp;'
.'<span class="preinput">'._("Refuse").'</span><br />
<input type="hidden" name="for_group_id" value="'
.db_result($result,0,'group_id').'" />
<input type="hidden" name="group_id" value="'.$GLOBALS['sys_group_id'].'" />
';
	} 
      else 
        {
	  print '<input type="radio" name="status" '
.'value="0" checked="checked" /> &nbsp;&nbsp;<span class="preinput">'
._("Display").'</span><br />
<input type="radio" name="status" value="4" />&nbsp;&nbsp;<span class="preinput">'
._("Delete").'</span><br />
<input type="hidden" name="group_id" value="'.db_result($result,0,'group_id').'" />
';
	}

      print '<br /><span class="preinput">'
	._("Subject:").'</span><br />&nbsp;&nbsp;
<input type="text" name="summary" value="'
        .db_result($result,0,'summary').'" size="65" maxlength="80" /><br />
<span class="preinput">'
	._("Details").' '.markup_info("full").'</span><br />&nbsp;&nbsp;
<textarea name="details" rows="20" cols="65" wrap="soft">'
.db_result($result,0,'details').'</textarea><p>';
# TRANSLATORS: the argument is site name (like Savannah).
      print '<p>'.sprintf (
_("Note: If this item is on the %s home page and you edit it, it will be
removed from the home page."),$GLOBALS['sys_name']).'</p>
';
      print '<div class="center">
<input type="submit" name="submit" value="'._("Submit").'" /></div>
</form>
';
      print '<h3>'._("Preview:").'</h3>
'.markup_full(db_result($result,0,'details'));
    }
  else
    {
      # No item selected
      if ($group_id == $GLOBALS['sys_group_id'] && !user_is_super_user())
        {
          print '<p class="warn">'
._("If you want to approve/edit site news (shown on the front page), you must
be logged as superuser.").'</p>
';
        }
      elseif ($group_id == $GLOBALS['sys_group_id'] && user_is_super_user())
        {
          print '<p class="warn">'
._("If you want to approve/edit news for the local administration project (not
shown on the front page), you must end the superuser session.").'</p>
';
        } 

      $old_date=(time()-(86400*15));

      # Firstly, we show item that require approval
      #   - if site news: it has to be already approved projects (0)
      #     or project submitted on the system site project
      #   - if project news: it has to be proposed news (5)
      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  $result=db_execute("SELECT * FROM news_bytes
            WHERE (is_approved=0 OR (is_approved=5 AND group_id=?))
            AND date > ?",
            array($group_id, $old_date));
	}
      else
	{
	  $result=db_execute("SELECT * FROM news_bytes
            WHERE is_approved=5 AND date > ? AND group_id=?",
            array($old_date, $group_id));
	}

      $rows=db_numrows($result);

      if ($rows < 1)
	{
	  print '<h3>'._("No queued items found").'</h3>';
	}
      else
	{
	  print '<h3>'._("These news items were submitted and need approval")
                      .'</h3>
<ul>
';
	  for ($i=0; $i<$rows; $i++)
	    {
	      print '<li';
              if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id'])
                print ' class="boxhighlight"';
              print '><a href="'.$_SERVER['PHP_SELF'].'?approve=1&amp;id='
                    .db_result($result,$i,'id');

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
                print group_getname(db_result($result,$i,'group_id')).' - ';
              print db_result($result,$i,'summary').'</a></li>
';
	    }
	  print '</ul>
';
	}

      # Secondly, we show deleted items for this week

      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  $result = db_execute("SELECT * FROM news_bytes WHERE (is_approved=2 OR
            (is_approved=4 AND group_id=?)) AND date > ?",
            array($group_id, $old_date));
	}
      else
	{
	  $result = db_execute("SELECT * FROM news_bytes WHERE is_approved=4
            AND date > ? AND group_id=?",
            array($old_date, $group_id));
	}

      $rows=db_numrows($result);

      if ($rows < 1)
	{
	  print '<h3>'
	    ._("No deleted items during these past two weeks").'</h3>
';
	}
      else
	{
	  if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	    {
	      print '<h3>'
		._("These items were refused these past two weeks:").'</h3>
';
	    }
	  else
	    {
	      print '<h3>'
		._("These items were deleted these past two weeks:").'</h3>
';
	    }

	  print '<ul>
';
	  for ($i=0; $i<$rows; $i++)
	    {
	      print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id'])
              print ' class="boxhighlight"';
            print '><a href="'.$_SERVER['PHP_SELF'].'?approve=1&amp;group='
                  .$group.'&amp;id='.db_result($result,$i,'id').'">';

              if ($group_id == $GLOBALS['sys_group_id']) 
                { print group_getname(db_result($result,$i,'group_id')).' - '; }
              print db_result($result,$i,'summary').'</a></li>
';
	    }
	  print '</ul>
';
	}

      # We show all approved items.
      if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
	{
	  $result=db_execute("SELECT * FROM news_bytes
            WHERE (is_approved=1 OR (is_approved=0  AND group_id=?))", 
            array($group_id));
	}
      else
	{
	  $result=db_execute("SELECT * FROM news_bytes
            WHERE (is_approved=0 OR is_approved=1)
            AND date > ? AND group_id=?",
	    array($old_date, $group_id));
	}

      $rows=db_numrows($result);

      if ($rows < 1)
	{
	  print '<h3>'
	    ._("No news items approved").'</h3>
';
	}
      else
	{
	  print '<h3>'
	    ._("These items were approved:").'</h3>
<ul>
';
	  for ($i=0; $i<$rows; $i++) {
	    print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id'])
              print ' class="boxhighlight"';
            print '><a href="'.$_SERVER['PHP_SELF'].'?approve=1&amp;group='
                  .$group.'&amp;id='.db_result($result,$i,'id').'">';

              if ($group_id == $GLOBALS['sys_group_id']) 
                { print group_getname(db_result($result,$i,'group_id')).' - '; }
              print db_result($result,$i,'summary').'</a></li>
';
	  }
	  print '</ul>
';
	}

    }

  site_project_footer(array());
}
else
{
  exit_error(_("Action unavailable: only news managers can approve news."));
}
?>
