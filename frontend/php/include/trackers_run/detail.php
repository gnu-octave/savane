<?php
# View a tracker item - alternate view.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006  Perrin <yves.perrin--cern.ch>
# Copyright (C) 2007  Sylvain Beucler
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

require_once(dirname(__FILE__) . '/../trackers/show.php');

$fields_per_line=2;
$max_size=40;

$result = db_execute($sql = "SELECT * FROM ".ARTIFACT
		     ." WHERE bug_id=? AND group_id=?",
		     array($item_id, $group_id));

if (db_numrows($result) <= 0)
  exit_error(_("No item found with that id."));

  # ################################ Start the form

  # Defines the item name, converting bugs to bug.
  # (Ideally, the artifact bugs should be named bug)
  $item_name = utils_get_tracker_prefix(ARTIFACT)." #".$item_id;
  # Defines the item link
  $item_link = utils_link("?".$item_id, $item_name);

  # Check whether this item is private or not. If it is private, show only to
  # the submitter
  $private_intro = '';
  if (db_result($result,0,'privacy') == "2")
    {
      if (member_check_private(0, $group_id))
	{
	  # Nothing worth being mentioned
	}
      elseif (db_result($result,0,'submitted_by') == user_getid())
	{
	  $private_intro = _(
"This item is private. However, you are allowed to read it as you submitted it.");
	}
      else
	{
	  exit_error(_("This item is private."));
	}
    }

  # Check if it is possible for the current user to post a comment. If not
  # add a message
  if (!group_restrictions_check($group_id, ARTIFACT, 2))
    {
      $private_intro .= ' '
._("You are not allowed to post comments on this tracker with your current
authentication level.");
    }

  trackers_header(array ('title'=>$item_name.", "
                           .utils_cutstring(db_result($result,0,'summary'))));

  print '<p>'.$private_intro.'</p>';

  print '<h2 class="'.utils_get_priority_color(db_result($result,0,'priority'),
                                               db_result($result,0,'status_id'))
        .'">'.sprintf("<em>%s</em>:", $item_link).' '
        .db_result($result,0,'summary').'</h2>';

  print form_header($_SERVER['PHP_SELF'], $form_id, "post",
                    'enctype="multipart/form-data" name="item_form"');
  print form_input("hidden", "func", "postaddcomment");
  print form_input("hidden", "group_id", $group_id);
  print form_input("hidden", "item_id", $item_id);

  # Colspan explanation:
  #
  #  We want the following, twice much space for the value than for the label
  #
  #  | Label:  Value________| Label:  Value______ |
  #  | Label:  Value_____________________________ |
  #
  #  So we have 4 column large via colspan.

  print '

  <table cellpadding="0" width="100%">
      <tr>
          <td class="preinput" width="15%">'._("Submitted by:").'&nbsp;</td>
          <td width="35%">'
          .utils_user_link(user_getname(db_result($result,0,'submitted_by')),
                           user_getrealname(db_result($result,0,'submitted_by')))
          .'</td>
          <td colspan="'.($fields_per_line)
          .'" align="center"  width="50%" valign="top"><span class="noprint">'
          .form_submit(_("Submit Changes"), "submit", ' class="bold"').'</span></td>
      </tr>
      <tr>
          <td class="preinput" width="15%">'._("Submitted on:").'&nbsp;</td>
          <td width="35%">'.utils_format_date(db_result($result,0,'date')).'</td>
          <td colspan="'.($fields_per_line)
          .'" align="center"  width="50%" valign="top">&nbsp;</td>
      </tr>';
  $votes = db_result($result,0,'vote');

  if ($votes)
    {
      # display vote here if any, anything else is handled below
      print '
      <tr>
          <td class="preinput" width="15%">'._("Votes:").'&nbsp;</td>
          <td width="35%"><a href="#votes">'.$votes.'</a></td>
          <td colspan="'.($fields_per_line).'" width="50%">&nbsp;</td>
      </tr>
';
    }

  print '<tr><td colspan="'.($fields_per_line*2).'">&nbsp;</td></tr>';

  # Variables that will be used afterwards
  unset($item_assigned_to, $item_discussion_lock);

  # Print special fields
  $i=0;
  $j=0;
  while ($field_name = trackers_list_all_fields())
    {
      # if the field is a special field (not even summary, as the user
      # wont have the right to modify it)
      # or if not used by this project  then skip it.
      if (trackers_data_is_special($field_name)
          || !trackers_data_is_used($field_name))
	{ continue; }

      # print the originator email field only if it was posted by
      # an anonymous user
      if ($field_name == 'originator_email'
	  && db_result($result,0,'submitted_by') != '100')
	{ continue; }

      # Never show the special discussion lock field
      if ($field_name == 'discussion_lock')
	{
	  $item_discussion_lock = db_result($result,0,$field_name);
	  continue;
	}

      # display the bug field
      # if field size is greatest than max_size chars then force it to
      # appear alone on a new line or it wont fit in the page
      $field_value = db_result($result,0,$field_name);
      list($sz,) = trackers_data_get_display_size($field_name);

      $field_display = trackers_field_display($field_name,$group_id,
                                              $field_value,false,true,true);

      $label = trackers_field_label_display($field_name,$group_id,false,false);
      $value = trackers_field_display($field_name,$group_id,$field_value,false,
                                      false,true);

      # Save the assigned to value for later.
      # Also make the user link for this same field
      if ($field_name == 'assigned_to')
	{
	  $item_assigned_to = trackers_field_display($field_name,$group_id,
                                                     $field_value,false,false,
                                                     true);

	  $value = utils_user_link(user_getname($field_value),
                                   user_getrealname($field_value));
	}

      # originator email
      if ($field_name == 'originator_email')
	{
	  $value = utils_email_basic($value);
	}

      # Fields colors
      $field_class = '';
      $row_class = '';
      if (!empty($previous_form_bad_fields)
          && array_key_exists($field_name, $previous_form_bad_fields))
	{
          # We highlight fields that were not properly/completely
	  # filled.
	  $field_class = ' class="highlight"';
	}
      if ($j % 2 && $field_name != 'details')
	{
	  # We keep the original submission with the default
	  # background color, for lisibility sake
	  #
	  # We also use the boxitem background color only one time
	  # out of two, to keep the page light
	  $row_class = ' class="'.utils_altrow($j+1).'"';
	}

      if ($sz > $max_size)
	{
          # Field getting one line for itself

          # Each time change the background color
	  $j++;

	  print "\n<tr".$row_class.">"
	    .'<td valign="middle" '.$field_class.' width="15%">'.$label.'</td>'
	    .'<td valign="middle" '.$field_class.' colspan="'
            .(2*$fields_per_line-1).'" width="75%">'
	    .$value.'</td>'
	    ."\n</tr>";
	  $i=0;
	}
      else
	{
          # Field getting half of a line for itself

	  if (!($i % $fields_per_line))
	    {
              # Every one out of two, prepare the background color change.
   	      # We do that at this moment because we cannot be sure
 	      # there will be another field on this line.
	      $j++;
	    }

	  print ($i % $fields_per_line ? '':"\n<tr".$row_class.">");
	  print '<td valign="middle"'.$field_class.' width="15%">'
            .$label.'</td>'
	    .'<td valign="middle"'.$field_class.' width="35%">'
            .$value.'</td>';
	  $i++;
	  print ($i % $fields_per_line ? '':"\n</tr>");
	}
    }
  print '</table>';

# ############################### Determine which subpart must be deployed
  $is_deployed = array();

  # Default picks
  $is_deployed["postcomment"] = false;
  if ($preview)
    $is_deployed["postcomment"] = true;
  $is_deployed["discussion"] = true;
  $is_deployed["attached"] = true;
  $is_deployed["dependencies"] = true;
  $is_deployed["cc"] = false;
  $is_deployed["votes"] = false;
  $is_deployed["reassign"] = false;

  # In printer mode, deploy everything by default: assume that people default
  # printout should contain all necessary info (note that history is excluded)
  if ($printer)
    {
      reset($is_deployed);
      while (list($entry,) = each($is_deployed))
	{ $is_deployed[$entry] = true; }
    }

# ################################ Post a comment

  # For now hidden by default, assuming that people first read comments,
  # then post comment.
  # The bad side is the fact that they are forced to click at least one.
  # The good thing is they do not have to scroll when starting.
  # There is one more click but people feel more in control (well, at least
  # the one that were vocal about Savane UI design)
  if (group_restrictions_check($group_id, ARTIFACT, 2))
    {
      print html_hidsubpart_header("postcomment", _("Post a Comment"));

      if (!$item_discussion_lock)
	{
	  print '<p class="noprint"><span class="preinput"> '
                ._("Add a New Comment").markup_info("rich");
          print form_submit (_('Preview'), 'preview')
                ."</span><br />&nbsp;&nbsp;&nbsp;\n";
	  print trackers_field_textarea('comment', htmlspecialchars($comment));
	  print '</p>';

	  if (!user_isloggedin())
	    {
	      print '<h2 class="warn">'._("You are not logged in").'</h2><p>';
	      printf (
_("Please <a href=\"%s\">log in</a>, so followups can be emailed to you."),
                      $GLOBALS['sys_home'].'account/login.php?uri='
                      .urlencode($_SERVER['REQUEST_URI']));
	      print '</p>';
	    }
	}
      else
	{
	  print '<p class="warn">'._("Discussion locked!").'</p>';
	}

      print '<p>&nbsp;</p>';
      print html_hidsubpart_footer();
    }

# ################################ Read Comments

  print html_hidsubpart_header("discussion", _("Discussion"), 1);
  $new_comment = $preview? $comment: false;

  print show_item_details($item_id,$group_id,0,$item_assigned_to, false,
                          $new_comment);

  print '<p>&nbsp;</p>';
  print html_hidsubpart_footer();

  # ################################ Attached Files

  # deployed by default, important item info
  print html_hidsubpart_header("attached", _("Attached Files"), 1);

  if (group_restrictions_check($group_id, ARTIFACT, 2)
      && !$item_discussion_lock)
    {
      print '<p class="noprint">';
      print sprintf(_(
"(Note: upload size limit is set to %s kB, after insertion of the required
escape characters.)"), $GLOBALS['sys_upload_max']);

      print '</p><p class="noprint"><span class="preinput"> '
            ._("Attach Files:").'</span><br />
      &nbsp;&nbsp;&nbsp;<input type="file" name="input_file1" size="10" />
      <input type="file" name="input_file2" size="10" />
      <br />
      &nbsp;&nbsp;&nbsp;<input type="file" name="input_file3" size="10" />
      <input type="file" name="input_file4" size="10" />
      <br />
      <span class="preinput">'._("Comment:").'</span><br />
      &nbsp;&nbsp;&nbsp;<input type="text" name="file_description" '
      .'size="60" maxlength="255" />
      </p><p>';
    }
  else
    {
      print '<p>';
    }

  show_item_attached_files($item_id,$group_id);

  print '</p><p>&nbsp;</p>';
  print html_hidsubpart_footer();

  # ################################ Dependencies

  # deployed by default, important item info
  print html_hidsubpart_header("dependencies", _("Dependencies"), 1);

  print show_item_dependency($item_id);

  print '<p></p>';
  print show_dependent_item($item_id);

  print '</p><p>&nbsp;</p>';
  print html_hidsubpart_footer();

  # ################################ Mail notification
  print html_hidsubpart_header("cc", _("Mail Notification Carbon-Copy List"));

  if (user_isloggedin() && !$item_discussion_lock)
    {
      print '<p class="noprint">';
      printf (
# TRANSLATORS: the argument is site name (like Savannah).
_(
"(Note: for %s users, you can use their login name
rather than their email addresses.)"), $GLOBALS['sys_name']);
      print '</p><p class="noprint">
	   <span class="preinput">'
            ._("Add Email Addresses (comma as separator):")
            .'</span><br />&nbsp;&nbsp;&nbsp;'
            .'<input type="text" name="add_cc" size="30" /><br />
	   <span class="preinput">'._("Comment:")
            .'</span><br />&nbsp;&nbsp;&nbsp;'
            .'<input type="text" name="cc_comment" size="40" maxlength="255" />'
            .'<p>';
    }

  show_item_cc_list($item_id, $group_id);

  print '<p>&nbsp;</p>';
  print html_hidsubpart_footer();

  # ################################ Votes

  if (trackers_data_is_used("vote"))
    {
      print html_hidsubpart_header("votes", _("Votes"));
      print '<p>'
	._("Do you think this task is very important?")
	.'<br />'
	._("If so, you can add your encouragement to it.")
	.'<br />'
	.sprintf(ngettext("This task has %s encouragement so far.",
			  "This task has %s encouragements so far.", $votes),
		 $votes)
	.'</p><p class="noprint">';

      if (trackers_data_is_showed_on_add("vote")
          || member_check(user_getid(), $group_id))
        {
          if (user_isloggedin())
            {
              $votes_given = trackers_votes_user_giventoitem_count(user_getid(),
                                 ARTIFACT, $item_id);
              $votes_remaining =
                trackers_votes_user_remains_count(user_getid()) + $votes_given;
              if (!$new_vote)
                { $new_vote = $votes_given; }

              # Show how many vote he already gave and allows to remove
              # or give more
              # votes.
              # The number of remaining points must be 100 - others votes

              print '<span class="preinput">'._("Your vote:")
                    .'</span><br />&nbsp;&nbsp;&nbsp;'
                    .'<input type="text" name="new_vote" size="3" '
                    .'maxlength="3" value="'
                    .htmlspecialchars($new_vote).'" /> '
                    .sprintf(ngettext("/ %s remaining vote",
                                      "/ %s remaining votes", $votes_remaining),
                             $votes_remaining);
              print '</p>';
            }
          else
            {
              print '<span class="warn">'._("Only logged-in users can vote.")
                    .'</span></p>';
            }
         }
       else
         {
            print '<span class="warn">'._("Only project members can vote.")
                  .'</span></p>';
         }

      print '</p>';
      print '<p>&nbsp;</p>';
      print html_hidsubpart_footer();
    }

  # Minimal anti-spam
  if (!user_isloggedin()) {
    print '<p class="noprint">'._('Please enter the title of <a
href="https://en.wikipedia.org/wiki/George_Orwell">George Orwell</a>\'s famous
dystopian book (it\'s a date):').' <input type="text" name="check" /></p>';
  }

  #  ################################  Submit
  print '<div align="center" class="noprint">'.
  form_submit(_("Submit Changes"), "submit", ' class="bold"').'</form></div>
';

# ################################ History

  print '<p>&nbsp;</p><p>&nbsp;</p>';
  print html_hidsubpart_header("history", _("History"));
  show_item_history($item_id,$group_id);
  print html_hidsubpart_footer();

  trackers_footer(array());
?>
