<?php
# Format tracker data.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
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



function format_item_details ($item_id, $group_id, $ascii=false,
                              $item_assigned_to=false,$quoted=false,
                              $new_comment=false)
{
  ## ASCII must not be translated
  #  Format the details rows from trackers_history
  global $sys_datefmt;


  ## Obtain data.
  $data = array();

  $i = 0;
  
  # Get original submission
  $result = db_execute("SELECT user.user_id,user.user_name,user.realname,"
                       .ARTIFACT.".date,".ARTIFACT.".details,".ARTIFACT
                       .".spamscore FROM ".ARTIFACT.",user WHERE  ".ARTIFACT
                       .".submitted_by=user.user_id AND ".ARTIFACT
                       .".bug_id=? AND ".ARTIFACT.".group_id=? LIMIT 1",
                       array($item_id, $group_id));
  $entry = db_fetch_array($result);
  $data[$i]['user_id'] = $entry['user_id'];
  $data[$i]['user_name'] = $entry['user_name'];
  $data[$i]['realname'] = $entry['realname'];
  $data[$i]['date'] = $entry['date'];
  $data[$i]['content'] = $entry['details'];  
  $data[$i]['comment_internal_id'] = '0';  
  $data[$i]['spamscore'] = $entry['spamscore'];  

  # Get comments
  # (the spams are included to avoid the comment #nnn refs to change, that 
  # could be puzzling to users)
  $result = trackers_data_get_followups($item_id);
  $svn_entries_exist = false;
  $max_entries = 0;
  $hist_id = 0;
  if (db_numrows($result))
    {
      while ($entry = db_fetch_array($result))
	{
	  $i++;
	  $data[$i]['user_id'] = $entry['user_id'];
	  $data[$i]['user_name'] = $entry['user_name'];
	  $data[$i]['realname'] = $entry['realname'];
	  $data[$i]['date'] = $entry['date'];
	  $data[$i]['comment_type'] = $entry['comment_type'];
	  $data[$i]['content'] = $entry['old_value'];
	  $data[$i]['comment_internal_id'] = $entry['bug_history_id']; 
	  $hist_id = $entry['bug_history_id'] + 1;

	  $data[$i]['spamscore'] = $entry['spamscore'];   
	  if ($entry['spamscore'] < 5)
	    {
	      # count the entry only if not a spam
	      $max_entries++;
	    }

	  # Special case: if the field_name is svncommit, set the comment_type
	  # to remind that is not an usual comment
	  if ($entry['field_name'] == 'svncommit')
	    {
	      $data[$i]['comment_type'] = "SVN";
	      $data[$i]['revision'] = $entry['new_value'];
	      $svn_entries_exist = true;
	    }
	}
    }

  if ($new_comment)
    {
      $i++;
      $max_entries++;
      $data[$i]['user_id'] = user_getid();
      $data[$i]['user_name'] = user_getname(user_getid(), 0);
      $data[$i]['realname'] = user_getname(user_getid(), 1);
      $data[$i]['date'] = time();
      $data[$i]['comment_type'] = '';
      $data[$i]['content'] = "*"._("This is a preview")."*\n\n". $new_comment;
      $data[$i]['comment_internal_id'] = $hist_id;
      $data[$i]['spamscore'] = '0';
    }

  # Not in text output (mail notif) and if there are svn commits, 
  # find out if there is a relevant link to add
  unset($svn_link);
  if (!$ascii && $svn_entries_exist)
    {
      global $project;
      if ($project->getUrl("svn_viewcvs") != 'http://' && 
	  $project->getUrl("svn_viewcvs") != '')
	{
	  
	  $svn_link .= $project->getUrl("svn_viewcvs");
	}
    }  


  # Sort entries according to user config
  $user_pref_fromoldertonewer = 
    user_get_preference("reverse_comments_order");
  if (!$ascii && $user_pref_fromoldertonewer)
    { ksort($data); }
  else
    { krsort($data); }

  # No followup comment -> return now
  $out = '';
  if (!count($data))
    {
      if (!$ascii)
	$out = '<span class="warn">'._("No Followups Have Been Posted").'</span>';
      return $out;
    }

  # Only one comment: it is the original submission, skip it in ascii mode
  # because it will be already included elsewhere
  if (count($data) < 2 && $ascii)
    {
      return;
    }

  # Header first
  if ($ascii)
    {
      $out .= "    _______________________________________________________\n\nFollow-up Comments:\n\n";
    }
  else
    {
      $title_arr=array();
  #    $title_arr[]=_("Comment");
  #    $title_arr[]=_("Posted By");

      $out .= html_build_list_table_top ($title_arr);
    }

  # Find how to which users the item was assigned to: if it is squad, several
  # users may be assignees
  $assignee_id = user_getid($item_assigned_to);
  $assignees_id = array();
  $assignees_id[$assignee_id] = true;  
  if (member_check_squad($assignee_id, $group_id))
    {
      $result_assignee_squad = db_execute("SELECT user_id FROM user_squad WHERE squad_id=? and group_id=?",
					  array($assignee_id, $group_id));
      while ($row_assignee_squad = db_fetch_array($result_assignee_squad))
	{
	  $assignees_id[$row_assignee_squad['user_id']] = true;
	}
    }

  # Provide a shortcut to the original submission, if more than 5 comments
  # and not in reversed order
  if (!$ascii && empty($_REQUEST['printer'])
      && $max_entries > 5 && !$user_pref_fromoldertonewer)
    {
      $jumpto_text = _("Jump to the original submission");
      if (ARTIFACT == "cookbook")
	{ $jumpto_text = _("Jump to the recipe preview"); }
      print '<p class="center"><span class="xsmall">(<a href="#comment0"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/arrows/bottom.png" class="icon" alt="'.$jumpto_text.'" /> '.$jumpto_text.'</a>)</span></p>';
    }

  # Loop throuh the follow-up comments and format them
  reset($data);
  $i = 0;
  $j = 0;
  $previous = false;
  $is_admin = member_check(0, $group_id, 'A');
  foreach ($data as $entry) 
    {
      # Ignore if found an entry without date (should not happen)
      if ($entry['date'] < 1)
	{ continue; }

      # Determine if it is a spam
      $is_spam = false;
      if ($entry['spamscore'] > 4)
	{ $is_spam = true; }
      
      # In ascii output, always ignore spams
      if ($ascii && $is_spam)
	{ continue; }
  
      # In html output, show spams only to project admin
      # (note that comment #nnn may loose accurracy if a previous comment
      # is set to spam. The only alternative option would be to count spams
      # but that could look very awkward too, as there were seemingly holes
      # in the comment list)
      # FIXME: no sure about this, it may be convenient to help handling
      # false positives to allow anyone to check -> for now, restrict to 
      # logged in users
      # yeupou, 2006-11-17: as we use the spamscore to delay post from 
      # anonymous, it matters that they can still see that their stuff was
      # posted
      #if ($is_spam && !user_isloggedin())
      # { continue; }

      # Counter for background color
      $j++;

      # Find out what would be this comment number
      if (!$user_pref_fromoldertonewer)
	{ $comment_number = ($max_entries-$i); }
      else
	{ $comment_number = $i; }

      extract(sane_import('get', array('func', 'comment_internal_id')));
      # Handle spam special cases here
      if ($is_spam)
	{
	  # If we are dealing with the original submission put a feedback
	  # warning
	  # (not if the item was just flagged)
	  if ($entry['comment_internal_id'] < 1 &&
	      $func != "flagspam")
	    {
	      fb(_("This item as been reported to be a spam"), 1);
	    }

	  if ($entry['user_id'] != 100)
	    { $spammer_user_name = $entry['user_name'];  }
	  else
	    { $spammer_user_name = _("an anonymous"); }

	  # If we are in printer mode, simply skip if
	  if (!empty($_REQUEST['printer']))
	    { continue; }

	  $class = utils_get_alt_row_color($j);

	  # The admin may actually want to see the incriminated item
	  # The submitter too
	  if (($func == "viewspam" &&
	      $comment_internal_id == $entry['comment_internal_id']) ||
	      ($entry['user_id'] != 100 && user_getid() == $entry['user_id']))
	    {
	      # Should the item content, without making links, with no markup
	      # It is only for checks purpose, nothing else
	      $out .= "\n".'<tr class="'.$class.'">'.
		'<td valign="top"><span class="warn">('._("Why is this post is considered to be spam? Users may have reported it to be spam or, if it has been recently posted, it may just be waiting for spamchecks to be run.").')</span><br /><span class="preinput">'._("Spam content:").'</span><br /><br />'.nl2br($entry['content']).'</td>'.
		'<td class="'.$class.'extra">';


	      
	      $out .= '<a name="spam'.$entry['comment_internal_id'].'"></a>'.
		utils_user_link($entry['user_name'], $entry['realname'], true).'<br />';

	      if ($is_admin)
		{
		  $out .= '<br /><br />(<a name="spam'.$entry['comment_internal_id'].'" title="'.sprintf(_("Current spam score: %s"), $entry['spamscore']).'" href="'.$_SERVER['PHP_SELF'].'?func=unflagspam&amp;item_id='.$item_id.'&amp;comment_internal_id='.$entry['comment_internal_id'].'#comment'.($comment_number+1).'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/bool/ok.png" class="icon" alt="'._("Unflag as spam").'" />'._("Unflag as spam").'</a>)';
		}

	      $out .= '</td></tr>';
		  
	    }
	  else
	    {	   

	      $out .= "\n".'<tr class="'.$class.'extra">'.
		'<td class="xsmall">&nbsp;</td>'.
		'<td class="xsmall"><a name="spam'.$entry['comment_internal_id'].'" href="'.$_SERVER['PHP_SELF'].'?func=viewspam&amp;item_id='.$item_id.'&amp;comment_internal_id='.$entry['comment_internal_id'].'#spam'.$entry['comment_internal_id'].'" title="'.sprintf(_("Current spam score: %s"), $entry['spamscore']).'">'.sprintf(_("Spam posted by %s"), $spammer_user_name).'</a>'.
		'</td></tr>';
	    }
          # No go to the next comment
	  continue;
	}

      # Now print a normal comment
      
      # Increment the comment count
      $i++;
      
      $comment_type = isset($entry['comment_type']) ? $entry['comment_type'] : null;
      $is_svn = false;
      if ($comment_type == 'SVN')
	{ $is_svn = true; }
      if ($comment_type == 'None' || $comment_type == '')
	{ $comment_type = ''; }
      else
	{ $comment_type = '['.$comment_type.']'; }
      
      if ($ascii)
	{
	  $fmt = "\n-------------------------------------------------------\n".
	    "Date: %-30sBy: %s\n";
	  if ($comment_type)
	    { $fmt .= "%s\n%s"; }
	  else
	    { $fmt .= "%s%s"; }
	  $fmt .= "\n";
	}
      
      # I wish we had sprintf argument swapping in PHP3 but
      # we dont so do it the ugly way...
      if ($ascii)
	{
          if ($entry['realname'])
            {
              $name = $entry['realname']." <".$entry['user_name'].">";
            }
          else
            {
              $name = "Anonymous"; 
              # must not be translated, part of mails notifs
	    }
	  
	  $out .= sprintf($fmt,
			  utils_format_date($entry['date']),
			  $name,
			  $comment_type,
			  utils_unconvert_htmlspecialchars($entry['content'])
			  );
	}
      else
	{
	  # If comment type is special commit thing, unset it so it does
	  # not appear in text
	  if ($is_svn)
	    { unset($comment_type); }
	  if ($comment_type)
	    {
	      # put the comment type in strong
	      $comment_type = '<strong>'.$comment_type.'</strong><br />';
	    }
	  
	  $icon = '';
	  $icon_alt = '';
	  $class = utils_get_alt_row_color($j);

	  # Find out the user id of the comment author
	  $poster_id = $entry['user_id'];

	  # Ignore user 100 (anonymous)
	  if ($poster_id != 100)
	    {
	      # Cosmetics if the user is assignee
	      if (array_key_exists($poster_id, $assignees_id))
		{
		  # Highlight the latest comment of the assignee
		  if ($previous != 1)
		    {
		      $class = "boxhighlight";
		      $previous = 1;
		    }
		}

	      # Cosmetics if the user is project member (we wont go as far
              # as presenting a different icon for specific roles, like
	      # manager etc..)

	      if (member_check($poster_id, $group_id, 'A'))
		{
		  # Project admin case: if the group is the admin group,
		  # show the specific site admin icon
		  if ($group_id == $GLOBALS['sys_group_id'])
		    {
		      $icon = "site-admin";
		      $icon_alt = _("Site Administrator");
		    }
		  else
		    {
		      $icon = "project-admin";
		      $icon_alt = _("Project Administrator");
		    }
		}
	      elseif (member_check($poster_id, $group_id))
		{
		  # Simple project member
		  $icon = "project-member";
		  $icon_alt = _("Project Member");
		}	      
	    }		 
	  
	  
          # FIXME: we should provide a javascript to enable to a simple
	  # quote, as this will easily get broken with formatting 
	  # functions
	  if (!$quoted)
	    {
	      $text_to_markup = $entry['content'];
	    }
	  else
	    {
	      $text_to_markup = str_replace("\n", "&gt; ",
					    wordwrap("\n".$entry['content'], 78, "\r\n"));
	    }
	  
	  $out .= "\n".'<tr class="'.$class.'"><td valign="top">';
	  $out .= '<a name="comment'.$comment_number.'" href="#comment'.$comment_number.'" class="preinput">';
	  $out .= utils_format_date($entry['date']);
	  $out .= ', ';
	  
	  if (!$is_svn)
	    {
	      if ($comment_number < 1)
		{
		  if (ARTIFACT != "cookbook")
		    {
		      $out .= '<strong>'._("original submission:").'</strong>';
		    }
		  else
		    {
		      $out .= '<strong>'._("recipe preview:").'</strong>';
   		    }
		}
	      else	   
		{
		  $out .= sprintf(_("comment #%s:"), $comment_number);
		}
	    }
	  else
	    {	      
	      # No # in front of the revision number so users dont get
	      # confused and do use it as a ref that will be made link
	      # (in the future, we could imagine doing such links)
	      $out .= sprintf(_("SVN revision %s:"), $entry['revision']);
	    }
	  
	  $out .= '</a><br />'.$comment_type;
	  
	  # Full markup only for original submission
	  if ($comment_number < 1)
	    {
	      $out .= markup_full($text_to_markup);
	    }
	  else
	    {
	      $out .= markup_rich($text_to_markup);
	    }

	  # Add an svn link if relevant (it supports viewcvs syntax)
	  if ($is_svn && $svn_link)
	    {
	      $out .= '<p>(<a href="'.$svn_link.'?rev='.$entry['revision'].'&amp;view=rev">'.sprintf(_("Browse SVN revision %s"), $entry['revision']).'</a>)</p>';

	    }

	  $out .='</td>';
	  $out .= '<td class="'.$class.'extra">'.utils_user_link($entry['user_name'], $entry['realname'], true);
	  
	  if ($icon)
	    {
	      $out .= '<br /><span class="help" title="'.$icon_alt.'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/roles/'.$icon.'.png" alt="'.$icon_alt.'" /></span>';
	    }
	  
	  if ($poster_id != 100 && array_key_exists($poster_id, $assignees_id))
	    {
	      $out .= '<span class="help" title="'._("In charge of this item.").'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/roles/assignee.png" alt="'._("In charge of this item.").'" /></span>';
	    }

	  # If not a member of the project, allow to mark as spam
	  # For performance reason, do not check here if the user already
	  # flagged the comment as spam, it will be done only if he tries to
	  # to it twice
	  if (!$is_svn && 
	      user_isloggedin() && 
	      !$icon && 
	      $poster_id != user_getid() &&
	      empty($_REQUEST['printer']))
	    {
              # Surround by two line breaks, to keep that link clearly 
	      # separated from 
	      # anything else, to avoid clicks by error
	      $out .= '<br /><br />(<a title="'.sprintf(_("Current spam score: %s"), $entry['spamscore']).'" href="'.$_SERVER['PHP_SELF'].'?func=flagspam&amp;item_id='.$item_id.'&amp;comment_internal_id='.$entry['comment_internal_id'].'#comment'.($comment_number-1).'"><img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" class="icon" alt="'._("Flag as spam").'" />'._("Flag as spam").'</a>)<br /><br />';
	    }
	  
	  $out .= '</td></tr>';
	  
	}
    }

  # final touch...
  $out .= ($ascii ? "\n\n\n" : "</table>");

  return $out;
}



function format_item_changes ($changes,$item_id,$group_id)
{


  # ASCII must not be translated

  global $sys_datefmt;

  # FIXME: strange, with %25s it does not behave exactly like
  # trackers_field_label_display
  $fmt = "%24s: %23s => %-23s\n";

  $separator = "\n    _______________________________________________________\n\n";

  # Process most of the fields
  reset($changes);
  $out = '';
  while (list($field,$h) = each($changes))
    {

      # If both removed and added items are empty skip - Sanity check
      if (empty($h['del']) && empty($h['add']))
	{ continue; }

      if ($field == "details" || $field == "attach")
        { continue; }

      # Since details is used for followups (creepy!), we are forced to play
      # with "realdetails" non existant field.
      if ($field == "realdetails")
        { $field = "details"; }

      $label = trackers_data_get_label($field);
      if (!$label)
	{ $label = $field; }
      $out .= sprintf($fmt, $label,
		      isset($h['del']) ? $h['del'] : null,
		      isset($h['add']) ? $h['add'] : null);
    }

  if ($out)
    {
      $out = "Update of ".utils_get_tracker_prefix(ARTIFACT)." #".$item_id." (project ".group_getunixname($group_id)."):\n\n".$out;
    }


  # Process special cases: follow-up comments
  if (!empty($changes['details']))
    {
      if ($out)
        { $out .= $separator; }

      $out_com = "Follow-up Comment #".db_numrows(trackers_data_get_followups($item_id));
      if (!$out)
        {
          $out_com .= ", ".utils_get_tracker_prefix(ARTIFACT)." #".$item_id." (project ".group_getunixname($group_id).")";
        }

      $out_com .= ":\n\n";
      if ($changes['details']['type'] != 'None' && $changes['details']['type'] != '(Error - Not Found)')
	{
	  $out_com .= '['.$changes['details']['type']."]\n";
	}
      $out_com .= utils_unconvert_htmlspecialchars($changes['details']['add']);
      unset($changes['details']);

      $out .= $out_com;
    }


  # Process special cases: file attachment
  if (!empty($changes['attach']))
    {
      if ($out)
        { $out .= $separator; }

      $out_att = "Additional Item Attachment";
      if (!$out)
        {
          $out_att .= ", ".utils_get_tracker_prefix(ARTIFACT)." #".$item_id." (project ".group_getunixname($group_id).")";
        }
      $out_att .= ":\n\n";

      
      foreach ($changes['attach'] as $file)
	{ 
	  $out_att .= sprintf("File name: %-30s Size:%d KB\n",
			      $file['name'],
			      intval($file['size']/1024));

	}
      unset($changes['attach']);
      $out .= $out_att;
    }

  return $out;

}


function format_item_attached_files ($item_id,$group_id,$ascii=false,$sober=false)
{

  global $sys_datefmt, $HTML;
  $out = '';

  # ASCII must not be translated

  if (!$sober)
    {
      $result=trackers_data_get_attached_files($item_id);
    }
  else
    {
      # In sober output, we assume that files are interesting in their
      # chronological order.
      # For instance, on the cookbook, if screenshots are provided, the author
      # of the item is likely to have posted them in the order of their use.
      # On the other hand, on non-sober output, what matters is the latest
      # submitted item.
      $result=trackers_data_get_attached_files($item_id, 'ASC');
    }
  $rows=db_numrows($result);

  # No file attached -> return now
  if ($rows <= 0)
    {
      if ($ascii)
	$out = "";
      else
	$out = '<span class="warn">'._("No files currently attached").'</span>';
      return $out;
    }

  # Header first
  if ($ascii)
    {
      $out .= "    _______________________________________________________\n\nFile Attachments:\n\n";
    }
  else
    {
      if (!$sober)
	{
	  $out .= $HTML->box_top(_("Attached Files"),'',1);
	}
    }

  # Determine what the print out format is based on output type (Ascii, HTML
  if ($ascii)
    {
      $fmt = "\n-------------------------------------------------------\n".
	 "Date: %s  Name: %s  Size: %s   By: %s\n%s\n%s";
    }

  # Loop throuh the attached files and format them
  for ($i=0; $i < $rows; $i++)
    {

      $item_file_id = db_result($result, $i, 'file_id');
      if ($ascii)
	{
	  $href = $GLOBALS['sys_home'].ARTIFACT."/download.php?file_id=$item_file_id";
	}
      else
	{
	  $href = $GLOBALS['sys_home'].ARTIFACT."/download.php?file_id=$item_file_id";
	}

      if ($ascii)
	{
	  $out .= sprintf($fmt,
			  utils_format_date(db_result($result, $i, 'date')),
			  db_result($result, $i, 'filename'),
			  utils_filesize(0, intval(db_result($result, $i, 'filesize'))),
			  db_result($result, $i, 'user_name'),
			  db_result($result, $i, 'description'),
			  '<http://'.$GLOBALS['sys_default_domain'].utils_unconvert_htmlspecialchars($href).'>');
	}
      else
	{
	  $html_delete = '';
	  if (member_check(0,$group_id,member_create_tracker_flag(ARTIFACT).'2') && !$sober)
	    {
	      $html_delete = '<span class="trash"><a href="'.$_SERVER['PHP_SELF'].'?func=delete_file&amp;item_id='.$item_id.'&amp;item_file_id='.$item_file_id.'">'.
		'<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" class="icon" alt="'._("Delete").'" /></a></span>';
	    }


	  if (!$sober)
	    {
	      $out .= '<div class="'.utils_get_alt_row_color($i).'">'.$html_delete;
	    }
	  else
	    {
	      $out .= '<div>&nbsp;&nbsp;&nbsp;- ';
	    }

	  $out .= '<a href="'.$href.'">file #'.$item_file_id._(": ").'&nbsp;';

	  if (!$sober)
	    {
	      $out .= sprintf(_('%s added by %s'), db_result($result, $i, 'filename').'</a>', utils_user_link(db_result($result, $i, 'user_name')));
	    }
	  else
	    {
	      $out .= '<a href="'.$href.'">'.db_result($result, $i, 'filename').'</a>';
	    }

	  $out .= ' <span class="smaller">('.utils_filesize(0, db_result($result, $i, 'filesize'));

	  if (db_result($result, $i, 'filetype'))
	    {
	      $out .= ' - '.db_result($result, $i, 'filetype');
	    }

	  if (db_result($result, $i, 'description'))
	    {
	      $out .= ' - '
                .markup_basic(db_result($result, $i, 'description'));
	    }
	  $out .= ')</span></div>';
	}
    }

  # final touch...

  if ($ascii || $sober)
  {
    $out .= "\n";
  }
  else
  {
    $out .= $HTML->box_bottom(1);
  }

  return($out);

}



function format_item_cc_list ($item_id,$group_id, $ascii=false)
{
# ASCII must not be translated
  global $sys_datefmt, $HTML;

  /*
          show the files attached to this bug
  */

  $result=trackers_data_get_cc_list($item_id);
  $rows=db_numrows($result);

  $out = '';

  # No file attached -> return now
  if ($rows <= 0)
    {
      if ($ascii)
	{
	  $out .= "";
	}
      else
	{
	  $out = '<span class="warn">'._("CC list is empty").'</span>';
	}
      return $out;
    }

  # Header first an determine what the print out format is
  # based on output type (Ascii, HTML)
  if ($ascii)
    {
      $out .= "    _______________________________________________________\n\n"."Carbon-Copy List:\n\n";
      $fmt = "%-35s | %s\n";
      $out .= sprintf($fmt, 'CC Address', 'Comment');
      $out .= "------------------------------------+-----------------------------\n";
    }
  else
    {
      $out .= $HTML->box_top(_("Carbon-Copy List"),'',1);
    }

  # Loop through the cc and format them
  for ($i=0; $i < $rows; $i++)
    {

      if ($ascii)
	{
	  # We wont provide the CC address in the mail, we keep that
	  # information only on the web interface
	  $email = "Available only the item webpage";
	}
      else
	{
	  $email = db_result($result, $i, 'email');

	  # If email is numeric, it must be an user id. Try to convert it
	  # to the username
	  if (ctype_digit($email) && user_exists($email))
	    { $email =  user_getname($email); }

	  # HTML preformat the address
	  $email = utils_email($email);
	}
      $item_cc_id = db_result($result, $i, 'bug_cc_id');
      $href_cc = $email;

      # If the comment is -SUB-, -UPD- or -COM-, it means submitter
      # or commenter, etc
      # It appears like this because the comment was automatically inserted
      # It allows us to translated it only now, so the translation is the
      # one of the page viewer, not the one of that made the CC to be added
      $comment = db_result($result, $i, 'comment');
      if ($comment == '-SUB-')
	{
	  if ($ascii)
	    { $comment = 'Submitted the item'; }
	  else
	    { $comment = _('Submitted the item'); }
	}

      if ($comment == '-COM-')
	{
	  if ($ascii)
	    { $comment = 'Posted a comment'; }
	  else
	    { $comment = _('Posted a comment'); }
	}

      if ($comment == '-UPD-')
	{
	  if ($ascii)
	    { $comment = 'Updated the item'; }
	  else
	    { $comment = _('Updated the item'); }
	}
      

      if ($ascii)
	{
	  $out .= sprintf($fmt, $email, $comment);
	}
      else
	{

	  # show CC delete icon if one of the condition is met:
	  # a) current user is a tracker manager
	  # b) then CC name is the current user
	  # c) the CC email address matches the one of the current user
	  # d) the current user is the person who added a given name in CC list
	  if (member_check(0,$group_id,member_create_tracker_flag(ARTIFACT).'2') ||
	      (user_getname(user_getid()) == $email) ||
	      (user_getemail(user_getid()) == $email) ||
	      (user_getname(user_getid()) == db_result($result, $i, 'user_name') ))
            {
$html_delete = '<span class="trash"><a href="'.$_SERVER['PHP_SELF'].'?func=delete_cc&amp;item_id='.$item_id.'&amp;item_cc_id='.$item_cc_id.'">'.
		 '<img src="'.$GLOBALS['sys_home'].'images/'.SV_THEME.'.theme/misc/trash.png" class="icon" alt="'._("Delete").'" /></a></span>';
	    }
	  else
            {
	      $html_delete = '';
	    }

          $out .= '<li class="'.utils_get_alt_row_color($i).'">'.$html_delete.
	  sprintf(_('%s added by %s'), $email, utils_user_link(db_result($result, $i, 'user_name')));
          if ($comment)
           {
	     $out .= ' <span class="smaller">('.markup_basic($comment).')</span>';
           }

#$href_cc,
#			  utils_format_date(db_result($result, $i, 'date')),
#			  $html_delete);
        }
    }

  # final touch...
  $out .= ($ascii ? "\n" : $HTML->box_bottom(1));

  return($out);

}
?>
