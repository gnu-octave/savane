<?php
# Modify items.
#
# Copyright 1999-2000 The SourceForge Crew
# Copyright 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright 2017, 2018 Ineiev
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


require_once(dirname(__FILE__).'/../trackers/show.php');
require_once(dirname(__FILE__).'/../trackers/format.php');
require_once(dirname(__FILE__).'/../trackers/votes.php');
# Need search functions.
require_directory("search");


$fields_per_line = 2;
$max_size = 40;

$result = db_execute("SELECT * FROM " . ARTIFACT
                     . " WHERE bug_id=? AND group_id=?",
                     array($item_id, $group_id));

if (db_numrows($result) > 0)
  {
# Defines the item name, converting bugs to bug.
# (Ideally, the artifact bugs should be named bug).
    $item_name = utils_get_tracker_prefix(ARTIFACT) . " #" . $item_id;
# Defines the item link.
    $item_link = utils_link("?" . $item_id, $item_name);

# Check whether this item is private or not. If it is private, show only to
# the submitter or to people that got the right to see private items
    $private_intro = '';
    if (db_result($result, 0, 'privacy') == "2")
      {
        if (member_check_private(0, $group_id))
          {
            # Nothing worth being mentioned.
          }
      elseif (db_result($result, 0, 'submitted_by') == user_getid())
        $private_intro =
_("This item is private. However, you are allowed to read it as you submitted it.");
      else
        exit_error(_("This item is private. You are not listed as member
allowed to read private items."));
      }

    trackers_header(array ('title' => $item_name . ", "
                           . utils_cutstring(db_result($result, 0, 'summary'))));

# Check if the user have a specific role.
    if (member_check(0, $group_id,
                     member_create_tracker_flag(ARTIFACT) . '2', 1))
      {
        print '<p>'
          . help(_("You are both technician and manager for this tracker."),
                 array(_("technician") =>
                       _("can be assigned items, cannot change status or priority"),
                       _("manager") => _("fully manage the items")))
          . "</p>\n";
      }
    elseif (member_check(0, $group_id,
                         member_create_tracker_flag(ARTIFACT) . '1', 1))
      {
        print '<p>' . help(_("You are technician for this tracker."),
                           array(_("technician") =>
_("you can be assigned tracker's items, but you cannot reassign
items, change priority nor open/close"))) . "</p>\n";
      }
    elseif (member_check(0, $group_id,
                         member_create_tracker_flag(ARTIFACT) . '3', 1))
      {
        print '<p>' . help(_("You are manager for this tracker."),
                           array(_("manager") =>
_("you can fully manage the trackers items, including assigning
items to technicians, reassign items over trackers and projects, changing
priority and open/close items"))) . "</p>\n";
      }

    print '<h1 class="'
          . utils_get_priority_color(db_result($result, 0, 'priority'),
                                     db_result($result, 0, 'status_id'))
          . '">' . sprintf(("<em>%s</em>:"), $item_link) . ' '
          . db_result($result,0,'summary') . "</h1>\n";

    print form_header($_SERVER['PHP_SELF'], $form_id, "post",
                      'enctype="multipart/form-data" name="item_form"');
    print form_input("hidden", "func", "postmoditem");
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
    . utils_user_link(user_getname(db_result($result, 0, 'submitted_by')),
                      user_getrealname(db_result($result, 0, 'submitted_by')))
    . '</td>
          <td colspan="'
    . ($fields_per_line)
    . '" width="50%" align="center" valign="top"><span class="noprint">'
    . form_submit(_("Submit Changes and Browse Items"), "submit", 'class="bold"')
    . '</span></td>
      </tr>
      <tr>
          <td class="preinput" width="15%">' . _("Submitted on:") . '&nbsp;</td>
          <td width="35%">' . utils_format_date(db_result($result, 0, 'date'))
    . '</td>
          <td colspan="'
    . ($fields_per_line)
    . '" width="50%" align="center" valign="top"><span class="noprint">'
    . form_submit(_("Submit Changes and Return to this Item"), "submitreturn")
    . "</span></td>\n</tr>\n";

    $votes = db_result($result, 0, 'vote');
    if ($votes)
      {
        # Display vote here if any, anything else is handled below.
        print '
      <tr>
          <td class="preinput" width="15%">' . _("Votes:") . '&nbsp;</td>
          <td width="35%"><a href="#votes">' . $votes . '</a></td>
          <td colspan="' . ($fields_per_line) . '" width="50%">&nbsp;</td>
      </tr>
';
      }

    print '<tr><td colspan="' . ($fields_per_line * 2) . '"'
          . ">&nbsp;</td></tr>\n";

    # Now display the variable part of the field list (depend on the project).
    # Some fields must be displayed differently according to the user role.
    $is_manager = member_check (0, $group_id,
                                member_create_tracker_flag(ARTIFACT) . '3');

    # Variables that will be used afterwards.
    $item_assigned_to = null;
    $item_discussion_lock = null;

    # $i is used to count the number of field.
    # $j is used to select the appropriate background color.
    $i=0;
    $j=0;

    while ($field_name = trackers_list_all_fields())
      {
        # If the field is not used by the project, skip it.
        if (!trackers_data_is_used($field_name))
          continue ;

        # If the field is a special field (not summary) then skip it.
        if (trackers_data_is_special($field_name)
            && $field_name != 'summary')
          {
            # If we are on the cookbook, details (a special field) must be
            # allowed too.
            if (ARTIFACT == 'cookbook' && $field_name == 'details')
              {
                # ok
              }
            else
              continue;
          }

        #  Print the originator email field only if the submitted was anonymous.
        if ($field_name == 'originator_email'
            && db_result($result, 0, 'submitted_by') != '100')
          continue;

        # Display the bug field.
        # If field size is greatest than max_size chars then force it to
        # appear alone on a new line or it wont fit in the page.

        # Look for the field value in the database only if we missing
        # its values. If we already have a value, we are probably in
        # step 2 of a search on item/group (dependency, reassignation).

        # If nocache is set, we were explicetely asked to rely only
        # on database content.
        if (!isset ($nocache))
          $nocache = false;
        if ((empty($$field_name) || $nocache) && !$preview)
          $field_value = db_result($result, 0, $field_name);
        else
          {
            if ($preview)
              extract(sane_import('post', array ($field_name)));
            $field_value = htmlspecialchars($$field_name);
          }
        list($sz,) = trackers_data_get_display_size($field_name);
        $label = trackers_field_label_display ($field_name, $group_id,
                                               false, false);

        # Save the assigned to value for later.
        if ($field_name == 'assigned_to')
          $item_assigned_to = trackers_field_display($field_name, $group_id,
                                                     $field_value, false, false,
                                                     true);

        # Discussion lock is shown only to at least managers.
        if ($field_name == 'discussion_lock')
          {
            # Save for later.
            $item_discussion_lock = db_result($result, 0, $field_name);
            if (!$is_manager)
              continue;
          }

        # Some fields must be displayed read-only,
        # assigned_to, status_id and priority too, for technicians
        # (if super_user, do nothing).
        if (!$is_manager
            && ($field_name == 'status_id'
                || $field_name == 'assigned_to'
                || $field_name == 'priority'
                || $field_name == 'originator_email'))
          {
            $value = trackers_field_display($field_name, $group_id, $field_value,
                                            false, false, true);
            if ($field_name == 'originator_email')
              $value = utils_email_basic($value);
          }
        else
          $value = trackers_field_display($field_name, $group_id, $field_value,
                                          false, false, $printer, false, false,
                                          _("None"), false,
                                          _("Any"), true);

        # Check if the field is mandatory.
        $star = '';
        $mandatory_flag = trackers_data_mandatory_flag($field_name);
        if ($mandatory_flag == 3
            || ($mandatory_flag == 0
                && trackers_check_is_shown_to_submitter($field_name, $group_id,
                                                        db_result($result, 0,
                                                                  'submitted_by'))))
          {
            $star = '<span class="warn"> *</span>';
          }

        # Fields colors.
        $field_class = '';
        $row_class = '';
        if ($j % 2 && $field_name != 'details')
          {
            # We keep the original submission with the default
            # background color, for lisibility sake.
            #
            # We also use the boxitem background color only one time
            # out of two, to keep the page light.
            $row_class = ' class="' . utils_altrow($j+1) . '"';
          }

        # If we are working on the cookbook, present checkboxes to
        # defines context before the summary line;
        if (CONTEXT == 'cookbook' && $field_name == 'summary')
          {
            cookbook_print_form();
          }

        # We highlight fields that were not properly/completely
        # filled.
        if ($previous_form_bad_fields
            && array_key_exists($field_name, $previous_form_bad_fields))
          {
            $field_class = ' class="highlight"';
          }

        if ($sz > $max_size)
          {
            # Field getting one line for itself.

            # Each time prepare the change of the background color.
            $j++;

            print "\n<tr" . $row_class . ">"
              . '<td valign="middle" ' . $field_class . ' width="15%">'
              . $label . $star . "</td>\n"
              . '<td valign="middle" ' . $field_class . ' colspan="'
              . (2*$fields_per_line-1) . '" width="75%">'
              . $value . "</td>\n"
              . "</tr>\n";
              $i=0;
          }
        else
          {
            # Field getting half of a line for itself.
            if (!($i % $fields_per_line))
              {
                # Every one out of two, prepare the background color change.
                # We do that at this moment because we cannot be sure
                # there will be another field on this line.
                $j++;
              }

            print ($i % $fields_per_line ? '': "\n<tr" . $row_class . ">");
            print '<td valign="middle"' . $field_class.' width="15%">'
              . $label . $star
              . '</td><td valign="middle"' . $field_class . ' width="35%">'
              . $value . " </td>\n";
            $i++;
            print ($i % $fields_per_line ? '': "</tr>\n");
          }
      } # while ($field_name = trackers_list_all_fields())

    print "</table>\n";
    print '<div class="warn"><span class="smaller">* '
          . _("Mandatory Fields") . '</span></div>';

# Determine which subpart must be deployed.
    $is_deployed = array();

    # Default picks.
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
    # printout should contain all necessary info (note that history is excluded).
    if (sane_all("printer"))
      {
        reset($is_deployed);
        while (list($entry,) = each($is_deployed))
          { $is_deployed[$entry] = true; }
      }

    # If at the second step of any two-step activity (add deps, reassign,
    # multiple canned answer), deploy only the relevant:
    # first set them all to false without question and then set to true only
    # the relevant.
    if ($depends_search || $canned_response == "!multiple!"
        || $reassign_change_project_search)
      {
        reset($is_deployed);
        while (list($entry,) = each($is_deployed))
          $is_deployed[$entry] = false;

        if ($depends_search)
          $is_deployed["dependencies"] = true;
        if ($canned_response == "!multiple!")
          $is_deployed["postcomment"] = true;
        if ($reassign_change_project_search)
          $is_deployed["reassign"] = true;
      }

# Post a comment.

    # For now hidden by default, assuming that people first read comments,
    # then post comment.
    # The bad side is the fact that they are forced to click at least one.
    # The good thing is they do not have to scroll when starting.
    # There is one more click but people feel more in control (well, at least
    # the one that were vocal about Savane UI design).
    print html_hidsubpart_header("postcomment", _("Post a Comment"),
                                 $is_deployed['postcomment']);

    # The discussion lock will not prevent technicians and manager to comment.
    # The point is too filter spams and to allow to stop flamewars, but
    # managers and technicians are expected to be serious enough.

    print '<p class="noprint"><span class="preinput"> ' . _("Add a New Comment")
          . ' ' . markup_info("rich");
    print form_submit (_('Preview'), 'preview')
          . "</span><br />&nbsp;&nbsp;&nbsp;\n";
    print trackers_field_textarea('comment', htmlspecialchars($comment),
                                  0, 0, _("New comment"));
    print "</p>\n";

    print '<p class="noprint"><span class="preinput">';
    print _("Comment Type & Canned Response:") . '</span><br />&nbsp;&nbsp;&nbsp;';
    print trackers_field_box('comment_type_id', '', $group_id, '', true, 'None');

    print '&nbsp;&nbsp;&nbsp;';

    if ($canned_response == "!multiple!")
      {
        $result_canned = trackers_data_get_canned_responses($group_id);
        if (db_numrows($result_canned) > 0)
          {
            print '<div>';

            while ($canned = db_fetch_array($result_canned))
              {
                print '&nbsp;&nbsp;&nbsp;';
                print form_input("checkbox", "canned_response[]",
                                 $canned['bug_canned_id']) . ' '
                                 . $canned['title'];
                print "<br />\n";
              }
            print "</div>\n";
          }
        else
          {
            print '<span class="warn">'
                  . _("Strangely enough, there is no canned response available.")
                  . '</span>';
          }
      }
    else
      {
        print trackers_canned_response_box ($group_id,'canned_response');
        print '&nbsp;&nbsp;&nbsp;<a class="smaller" href="' . $GLOBALS['sys_home']
              . ARTIFACT . '/admin/field_values.php?group_id=' . $group_id
              . '&amp;create_canned=1">(' . _("Or define a new Canned Response")
              . ')</a>';
      }
    print "</p>\n";

    if ($item_discussion_lock)
      {
        print '<p class="warn">' . _("Discussion locked!") . ' '
              . _("Your privileges however allow to override the lock.");
        print "</p>\n";
      }

    print "<p>&nbsp;</p>\n";
    print html_hidsubpart_footer();
    print '</span>';

    print html_hidsubpart_header("discussion", _("Discussion"));

    $new_comment = $preview? $comment: false;
    print show_item_details($item_id, $group_id, 0, $item_assigned_to,
                            $new_comment);
    print "<p>&nbsp;</p>\n";
    print html_hidsubpart_footer();

    print html_hidsubpart_header("attached", _("Attached Files"));

    print '<p class="noprint">';
    print sprintf(_("(Note: upload size limit is set to %s kB, after insertion of
the required escape characters.)"), $GLOBALS['sys_upload_max']);

    print '</p><p class="noprint"><span class="preinput"> ' . _("Attach Files:")
      . '</span><br />
      &nbsp;&nbsp;&nbsp;<input type="file" title="' . _("File to attach")
      . '" name="input_file1" size="10" /> '
      . '<input type="file" name="input_file2" title="' . _("File to attach")
      . '" size="10" />
      <br />
      &nbsp;&nbsp;&nbsp;<input type="file" title="' . _("File to attach")
      . '" name="input_file3" size="10" /> '
      . '<input type="file" name="input_file4" title="' . _("File to attach")
      . '" size="10" />
      <br />
      <span class="preinput">' . _("Comment:") . '</span><br />
      &nbsp;&nbsp;&nbsp;'
      . '<input type="text" name="file_description" title="'
      . _("File description") . '" size="60" maxlength="255" />
</p>
<p>';

    show_item_attached_files($item_id, $group_id);

    print "</p>\n<p>&nbsp;</p>\n";
    print html_hidsubpart_footer();

# Dependencies.

    # Deployed by default, important item info.
    print html_hidsubpart_header("dependencies", _("Dependencies"));

    print '<p class="noprint"><span class="preinput">';
    if (!$depends_search)
      print _("Search an item (to fill a dependency against):");
    else
      {
# Print a specific message if we are already at step 2 of filling
# a dependency.
        print _("New search, in case the previous one was not satisfactory (to
fill a dependency against):");
      }

    print '</span><br />
      &nbsp;&nbsp;&nbsp;'
    . '<input type="text" title="' . _("Terms to look for")
    . '" name="depends_search" size="40" maxlength="255" /><br />
';

    $tracker_select =
    '&nbsp;&nbsp;&nbsp;<select title="' . _("Tracker to search in")
      . '" name="depends_search_only_artifact">';

    # Generate the list of searchable trackers.
    $tracker_list = array('all'     => _("Any Tracker"),
                          'support' => _("The Support Tracker Only"),
                          'bugs'    => _("The Bug Tracker Only"),
                          'task'    => _("The Task Manager Only"),
                          'patch'   => _("The Patch Manager Only"),);

    foreach ($tracker_list as $option_value => $text)
      {
        $selected = '';
        if ($option_value == $depends_search_only_artifact)
          $selected = ' selected="selected"';
        $tracker_select .=
          "<option value=\"$option_value\"$selected>$text</option>\n";
      }
    $tracker_select .= "</select>\n";

    $group_select = '<select title="' . _("Wether to search in any project")
                    . '" name="depends_search_only_project">';

    # By default, search restricted to the project (lighter for the CPU, probably
    # also more accurate).
    $selected = '';
    if ($depends_search_only_project == "any")
      $selected = ' selected="selected"';
    $group_select .= '<option value="any"' . $selected . '>'
# TRANSLATORS: this string is used in the context like 
# "search an item of [The Bug Tracker Only] of [Any Project]".
                     . _("Any Project")
                     . "</option>\n";

    # Not yet a select? It means we are in the default case.
    if (!$selected)
      $selected = ' selected="selected"';
    else
      $selected = '';

    $group_select .= '<option value="notany"' . $selected . '>'
# TRANSLATORS: this string is used in the context like 
# "search an item of [The Bug Tracker Only] of [This Project Only]".
                     . _("This Project Only") . '</option>
</select>&nbsp;';

    printf (
# TRANSLATORS: the first argument is tracker type (like The Bug Tracker),
# the second argument is either 'This Project Only' or 'Any Project'.
            _('Of %1$s of %2$s'), $tracker_select, $group_select);

    if (!$depends_search)
      {
        print form_submit(_("Search"), "submit");
      }
    else
      {
# Print a specific message if we are already at step 2 of filling
# a dependency.
        print form_submit(_("New search"), "submit");
      }

# Search results, if we are already at step 2 of filling.
    if ($depends_search)
      {
        print '</p>
<p><span class="preinput">';
      printf (_("Please select a dependency to add in the result of your search
of '%s' in the database:"), $depends_search);
        print '</span>';

        $success = false;

# If we have less than 4 characters, to avoid giving lot of feedback
# and put an exit to the report, just consider the search as a failure.
        if (strlen($depends_search) > 3)
          {
# Build the list of trackers to take account of.
            if ($depends_search_only_artifact == "all")
              $artifacts = array("support", "bugs", "task", "patch");
            else
              $artifacts = array($depends_search_only_artifact);

# Actually search on each asked trackers.
            while (list($num, $tracker) = each($artifacts))
              {
                if ($depends_search_only_project == "notany")
                  $GLOBALS['only_group_id'] = $group_id;

# Do not ask for all words,
                $GLOBALS['exact'] = 0;

                $result_search = search_run($depends_search, $tracker, 0);
                $success = db_numrows($result_search) + $success;

# Print the result, if existing.
                if (db_numrows($result_search) == 0)
                  continue;
                while (list($res_id, $res_summary, $res_date, $res_privacy,
                            $res_submitter_id, $res_submitter_name,
                            $res_group) = db_fetch_array($result_search))
                  {
# Avoid item depending on itself.
# Hide private items. For now, they are excluded for dependencies.
# We ll implement that later if necessary.
                    if ($res_privacy == 2)
                      continue;
                    if ($res_id != $item_id || $tracker != ARTIFACT)
                      {
# Right now only print id, summary and group.
# We may change that depending on users feedback.
                         print '<br />';
                         print '&nbsp;&nbsp;&nbsp;'
                           . form_input("checkbox", "dependent_on_" . $tracker
                                        . "[]", $res_id)
                           . ' ' . $tracker . ' #' . $res_id . ': '
                           . $res_summary;
                         print ', ' . _("group") . ' '
                           . group_getname($res_group);
                      }
                    }
              } # while (list($num, $tracker) = each($artifacts))
          } # if (strlen($depends_search) > 3)
        if (!$success)
          {
            print '<br /><span class="warn">';
            print _("None found. Please note that only search words of more than
three characters are valid.");
            print '</span>';
          }
      } # if ($depends_search)

    print "</p>\n";
    print show_item_dependency($item_id);
    print show_dependent_item($item_id);
    print "\n<p>&nbsp;</p>\n";
    print html_hidsubpart_footer();

# Mail notification.

    print html_hidsubpart_header("cc", _("Mail Notification Carbon-Copy List"));
    print '<p class="noprint">';
    printf (
# TRANSLATORS: the argument is site name (like Savannah).
          _("(Note: for %s users, you can use their login name
rather than their email addresses.)"), $GLOBALS['sys_name']);

    print '</p>
<p class="noprint"><span class="preinput"><label for="add_cc">'
      . _("Add Email Addresses (comma as separator):")
      . '</label></span><br />
&nbsp;&nbsp;&nbsp;<input type="text" id="add_cc" name="add_cc" '
      . 'size="40" value="'
      . htmlspecialchars($add_cc) . '" />&nbsp;&nbsp;&nbsp;
        <br />
        <span class="preinput"><label for="cc_comment">'
      . _("Comment:")
      . '</label></span><br />
&nbsp;&nbsp;&nbsp;<input type="text" id="cc_comment" name="cc_comment" '
      . 'size="40" maxlength="255" value="'
      . htmlspecialchars($cc_comment) . '" />';
    print "<p></p>\n";
    show_item_cc_list($item_id, $group_id);
    print "<p>&nbsp;</p>\n";
    print html_hidsubpart_footer();

# Votes.
    if (trackers_data_is_used("vote"))
      {
        $votes_given = trackers_votes_user_giventoitem_count(user_getid(),
                                                             ARTIFACT, $item_id);
        $votes_remaining = trackers_votes_user_remains_count(user_getid())
                                                             + $votes_given;
        if (!$new_vote)
          $new_vote = $votes_given;

# ATM, the mod page is only for technicians/manager. They always have
# the right to vote, as they are project member anyway.
        print html_hidsubpart_header("votes", _("Votes"));
        print '<p>' . sprintf(ngettext("There is %s vote so far.",
                                     "There are %s votes so far.",
                                     $votes), $votes) . ' '
        . _("Votes easily highlight which items people would like to see resolved
in priority, independently of the priority of the item set by tracker
managers.") . '</p>
<p class="noprint">';

# Show how many vote he already gave and allows to remove or give more
# votes.
# The number of remaining points must be 100 - others votes
        print '<span class="preinput"><label for="new_vote">' . _("Your vote:")
          . '</label></span><br />
&nbsp;&nbsp;&nbsp;<input type="text" name="new_vote" id="new_vote" '
          . 'size="3" maxlength="3" value="'
          . htmlspecialchars($new_vote) . '" /> '
          . sprintf(ngettext("/ %s remaining vote",
                            "/ %s remaining votes",
                            $votes_remaining), $votes_remaining);
        print "</p>\n";
        print "<p>&nbsp;</p>\n";
        print html_hidsubpart_footer();
      }

# Reassign an item, if manager of the tracker.
# Not possible on the cookbook manager, cookbook entries are too specific.
    if (member_check(0, $group_id, member_create_tracker_flag(ARTIFACT) . '3')
        && ARTIFACT != "cookbook")
      {
        # No point in having this part printable.
        print '<span class="noprint">';
        print html_hidsubpart_header("reassign", _("Reassign this item"));

        function specific_reassign_artifact ($art, $content)
          {
            $checked = '';
            if (!$GLOBALS['reassign_change_artifact'] && ARTIFACT == $art
                || $GLOBALS['reassign_change_artifact'] == $art)
                $checked = ' selected="selected"';
            $ret = '<option value="' . $art . '"' . $checked . '>' . $content
                   . "</option>\n";
            $checked = '';
            return $ret;
          }
        $tracker_select = '<select title="' . _("Tracker to reassign to")
                          . '" name="reassign_change_artifact">';
        $tracker_select .= specific_reassign_artifact("support",
# TRANSLATORS: this string is used in the context of "Move to the %s".
          _("<!-- Move to the -->Support Tracker"));
        $tracker_select .= specific_reassign_artifact("bugs",
# TRANSLATORS: this string is used in the context of "Move to the %s".
          _("<!-- Move to the -->Bug Tracker"));
        $tracker_select .= specific_reassign_artifact("task",
# TRANSLATORS: this string is used in the context of "Move to the %s".
          _("<!-- Move to the -->Task Tracker"));
        $tracker_select .= specific_reassign_artifact("patch",
# TRANSLATORS: this string is used in the context of "Move to the %s".
          _("<!-- Move to the -->Patch Tracker"));
        $tracker_select .= "</select>\n";

        printf (_("Move to the %s"), $tracker_select);

        print '<br /><br />
        <span class="preinput">';

        if (!$reassign_change_project_search)
          print _("Move to the project:");
        else
          {
# Print a specific message if we are already at step 2 of
# reassignation to another project.
            print _("New search, in case the previous one was not satisfactory
(to reassign the item to another project):");
          }

        print '</span><br />
&nbsp;&nbsp;&nbsp;<input type="text" title="' . _("Project to reassign item to")
. '" name="reassign_change_project_search" '
. 'size="40" maxlength="255" />';
       if (!$reassign_change_project_search)
          print form_submit(_("Search"), "submit");
        else
          {
# Print a specific message if we are already at step 2 of filling
# a ressign.
            print form_submit(_("New search"), "submit");
          }

# Search results, if we are already at step 2 of filling.
        if ($reassign_change_project_search)
          {
            print '</p>
<p><span class="preinput">';
            printf (_("To which project this bug should be reassigned to? This is
the result of your search of '%s' in the database:"),
                    $reassign_change_project_search);
            print '</span>';

# Print a null-option, someone may change his mine without having
# to use the back button of his browser.
            print '<br />
&nbsp;&nbsp;&nbsp;<input type="radio" name="reassign_change_project" '
. 'value="0" checked="checked" /> ' . _("Do not reassign to another project.");

            $success = false;
            $result_search = search_run($reassign_change_project_search, "soft",
                                        0);
            $success = db_numrows($result_search);

# Print the result, if existing.
            if (db_numrows($result_search) != 0)
              {
                while (list($res_group_name, $res_unix_group_name, $res_group_id)
                       = db_fetch_array($result_search))
                  {
# Not reassigning to itself.
                    if ($res_unix_group_name == $group)
                      continue;
                    print "<br />\n";
                    print '&nbsp;&nbsp;&nbsp;'
                          . form_input("radio", "reassign_change_project",
                                       $res_unix_group_name)
                          . ' [' . $res_unix_group_name . ', #'
                          . $res_group_id . '] ' . $res_group_name;
                  }
              }

            if (!$success)
              {
                print '<br /><span class="warn">';
                print _("None found. Please note that only search words of more
than three characters are valid.");
                print '</span>';
              }
          } # if ($reassign_change_project_search)
        print html_hidsubpart_footer();
        print '</span>';
      } # if (member_check(0, $group_id, ...

    print "<p>&nbsp;</p>\n";
    print '<div align="center" class="noprint">'
      . form_submit(_("Submit Changes and Browse Items"), "submit", 'class="bold"')
      . ' '
      . form_submit(_("Submit Changes and Return to this Item"), "submitreturn")
      . "</form></div>\n";

    print "<p>&nbsp;</p><p>&nbsp;</p>\n";
    print html_hidsubpart_header("history", _("History"));
    show_item_history($item_id, $group_id);
    print html_hidsubpart_footer();
  } # if (db_numrows($result) > 0)

trackers_footer(array());
?>
