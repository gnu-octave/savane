<?php
# Format tracker data.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017-2020, 2022 Ineiev
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

function format_item_details (
  $item_id, $group_id, $ascii = false, $item_assigned_to = false,
  $preview = [], $allow_quote = true
)
{
  # ASCII must not be translated.
  # Format the details rows from trackers_history.
  global $sys_datefmt;

  $data = [];
  $i = $max_entries = $hist_id = 0;

  $add_comment_item = function ($entry, $preview = false)
    use (&$i, &$max_entries, &$data, &$hist_id)
  {
    $i++;
    $max_entries++;
    $data[$i]['user_id'] = $entry['user_id'];
    $data[$i]['user_name'] = $entry['user_name'];
    $data[$i]['realname'] = $entry['realname'];
    $data[$i]['date'] = $entry['date'];
    $data[$i]['comment_type'] = $entry['comment_type'];
    $data[$i]['text'] = trackers_decode_value ($entry['old_value']);
    $data[$i]['comment_internal_id'] = $entry['bug_history_id'];
    if ($entry['bug_history_id'] < 0)
      $data[$i]['comment_internal_id'] = $hist_id;
    else
      $hist_id = $entry['bug_history_id'] + 1;

    $data[$i]['spamscore'] = $entry['spamscore'];
    $data[$i]['preview'] = $preview;
  };

  # Get original submission.
  $result = db_execute ("
    SELECT u.user_id, u.user_name, u.realname, a.date, a.details, a.spamscore
    FROM " . ARTIFACT . " a, user u
    WHERE a.submitted_by = u.user_id AND a.bug_id = ? AND a.group_id = ?
    LIMIT 1",
    [$item_id, $group_id]
  );
  $entry = db_fetch_array ($result);
  $data[$i]['user_id'] = $entry['user_id'];
  $data[$i]['user_name'] = $entry['user_name'];
  $data[$i]['realname'] = $entry['realname'];
  $data[$i]['date'] = $entry['date'];
  $data[$i]['text'] = $entry['details'];
  $data[$i]['comment_internal_id'] = '0';
  $data[$i]['spamscore'] = $entry['spamscore'];
  $data[$i]['preview'] = false;

  # Get comments (the spam is included to preserve comment No).
  $result = trackers_data_get_followups ($item_id);
  if (db_numrows ($result))
    {
      while ($entry = db_fetch_array ($result))
        $add_comment_item ($entry);
    }

  if (!empty ($preview))
    $add_comment_item ($preview, true);

  # Sort entries according to user config.
  $user_pref_fromoldertonewer = user_get_preference ("reverse_comments_order");
  if (!$ascii && $user_pref_fromoldertonewer)
    ksort($data);
  else
    krsort($data);

  # No followup comment -> return now.
  $out = '';
  if (!count ($data))
    {
      if ($ascii)
        return $out;
      return '<span class="warn">'
        . _("No followups have been posted") . '</span>';
    }

  # Only one comment: it is the original submission, skip it in ascii mode
  # because it will be already included elsewhere.
  if (count ($data) < 2 && $ascii)
    return;

  if ($ascii)
    $out .= "    _______________________________________________________\n\n"
      . "Follow-up Comments:\n\n";
  else
    $out .= html_build_list_table_top ([]);

  # Find how to which users the item was assigned to: if it is squad, several
  # users may be assignees.
  $assignee_id = user_getid ($item_assigned_to);
  $assignees_id = [$assignee_id => true];
  if (member_check_squad ($assignee_id, $group_id))
    {
      $result_assignee_squad = db_execute("
        SELECT user_id FROM user_squad WHERE squad_id = ? and group_id = ?",
        [$assignee_id, $group_id]
      );
      while ($row_assignee_squad = db_fetch_array ($result_assignee_squad))
        {
          $assignees_id[$row_assignee_squad['user_id']] = true;
        }
    }

  # Provide a shortcut to the original submission, if more than 5 comments
  # and not in reversed order.
  if (!$ascii && empty($_REQUEST['printer'])
      && $max_entries > 5 && !$user_pref_fromoldertonewer)
    {
      $jumpto_text = _("Jump to the original submission");
      if (ARTIFACT == "cookbook")
        $jumpto_text = _("Jump to the recipe preview");

      $out = '<p class="center"><span class="xsmall">'
        . "(<a href='#comment0'>"
        . html_image ("arrows/bottom.png", ['class' => 'icon'])
        . " $jumpto_text </a>)</span></p>\n" . $out;
    }

  # Loop throuh the follow-up comments and format them.
  reset ($data);
  $i = 0; # Comment counter.
  $j = 0; # Counter for background color.
  $previous = false;
  $is_admin = member_check (0, $group_id, 'A');
  foreach ($data as $entry)
    {
      # Ignore if found an entry without date (should not happen).
      if ($entry['date'] < 1)
        continue;

      # Determine if it is a spam.
      $is_spam = false;
      if ($entry['spamscore'] > 4)
        $is_spam = true;

      # In ascii output, always ignore spam.
      if ($ascii && $is_spam)
        continue;

      $score = sprintf (_("Current spam score: %s"), $entry['spamscore']);
      $score = "title=\"$score\"";
      $int_id = $entry['comment_internal_id'];
      $comment_ids = "&amp;item_id=$item_id&amp;comment_internal_id=$int_id";
      $url_start = htmlentities ($_SERVER['PHP_SELF']) . '?func=';
      $class = utils_altrow (++$j);

      # Find out what would be this comment number.
      if ($user_pref_fromoldertonewer)
        $comment_number = $i;
      else
        $comment_number = ($max_entries - $i);
      $i++;

      extract (sane_import ('get',
        [
          'strings' => [
            [
              'func',
              [
               'flagspam', 'unflagspam', 'viewspam', 'delete_file',
               'delete_cc'
              ]
            ]
          ],
          'digits' => 'comment_internal_id'
        ]
      ));
      if ($is_spam)
        {
          # If we are dealing with the original submission put a feedback
          # warning (not if the item was just flagged).
          if ($entry['comment_internal_id'] < 1 && $func != "flagspam")
            fb (_("This item has been reported to be a spam"), 1);

          if ($entry['user_id'] != 100)
            $spammer_user_name = $entry['user_name'];
          else
            $spammer_user_name = _("anonymous");

          # If we are in printer mode, simply skip if.
          if (!empty ($_REQUEST['printer']))
            continue;

          # The admin may actually want to see the incriminated item.
          # The submitter too.
          if (($func == "viewspam" && $comment_internal_id == $int_id)
              || ($entry['user_id'] != 100 && user_getid() == $entry['user_id']))
            {
              # Should be item content, without making links, with no markup.
              # It is only for checks purpose, nothing else.
              $out .= "\n<tr class=\"$class\">\n"
                . "<td valign='top'>\n<span class='warn'>("
                . _("Why is this post is considered to be spam? "
                    . "Users may have reported it to be\nspam or, if it has "
                    . "been recently posted, it may just be waiting for "
                    . "spamchecks\nto be run.")
                . ")</span><br />\n<span class='preinput'>"
                . _("Spam content:") . "</span><br />\n<br />"
                . nl2br ($entry['text']) . "</td>\n<td class=\"{$class}extra\" "
                . "id=\"spam{$int_id}\">\n";

              $out .=
                utils_user_link ($entry['user_name'], $entry['realname'], true);
              $out .= "<br />\n";

              if ($is_admin)
                {
                  $cn = $comment_number + 1;
                  $out .= "\n<br /><br />(<a $score href=\"$url_start"
                    . "unflagspam$comment_ids#comment$cn\">"
                    . html_image ("bool/ok.png", ['class' => 'icon'])
                    . _("Unflag as spam") . '</a>)';
                }
              $out .= "</td></tr>\n";
            }
          else
            {
              $out .= "\n<tr class=\"{$class}extra\">"
                . "<td class='xsmall'>&nbsp;</td>\n"
                . "<td class='xsmall'><a $score href=\"$url_start"
                . "viewspam$comment_ids#spam$int_id\">"
                . sprintf (_("Spam posted by %s"), $spammer_user_name)
                . "</a></td></tr>\n";
            }
          continue;
        } # if ($is_spam)

      $comment_type = null;
      if (isset ($entry['comment_type']))
        $comment_type = $entry['comment_type'];

      if ($comment_type == 'None' || $comment_type == '')
        $comment_type = '';
      else
        $comment_type = "[$comment_type]";

      if ($ascii)
        {
          $out .= "\n-------------------------------------------------------\n";

          $date = utils_format_date ($entry['date']);
          if ($entry['realname'])
            $name = "{$entry['realname']} <{$entry['user_name']}>";
          else
            $name = "Anonymous";
          $out .= sprintf ("Date: %-30sBy: %s\n", $date, $name);
          $out .= $comment_type;
          if ($comment_type)
            $out .= "\n";
          $out .= markup_ascii ($entry['text']) . "\n";
          continue;
        }
      if ($comment_type)
        $comment_type = "<b>$comment_type</b><br />\n";

      $icon = $icon_alt = '';
      $poster_id = $entry['user_id'];

      # Ignore user 100 (anonymous).
      if ($poster_id != 100)
        {
          # Cosmetics if the user is assignee.
          if (array_key_exists ($poster_id, $assignees_id))
            {
              # Highlight the latest comment of the assignee.
              if ($previous != 1)
                {
                  $class = "boxhighlight";
                  $previous = 1;
                }
            }

          # Cosmetics if the user is project member (we shan't go as far
          # as presenting a different icon for specific roles, like
          # manager).

          if (member_check ($poster_id, $group_id, 'A'))
            {
              # Project admin case: if the group is the admin group,
              # show the specific site admin icon.
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
          elseif (member_check ($poster_id, $group_id))
            {
              # Simple project member.
              $icon = "project-member";
              $icon_alt = _("Project Member");
            }
        } # if ($poster_id != 100)

      $text_to_markup = $entry['text'];

      $out .= "\n<tr class=\"$class\"><td valign='top'>\n";
      if ($entry['preview'])
        $out .= "<p><b>" . _("This is a preview") . "</b></p>\n";
      $out .= "<a id='comment$comment_number' href='#comment$comment_number' "
        . "class='preinput'>\n" . utils_format_date($entry['date']) . ', ';

      if ($comment_number < 1)
        {
          $msg = _("original submission:");
          if (ARTIFACT == "cookbook")
            $msg = _("recipe preview:");
          $out .= "<b>$msg</b>\n";
        }
      else
        $out .= sprintf (_("comment #%s:"), $comment_number);

      $out .= "</a>&nbsp;";
      if ($allow_quote)
        $out .=  "<button name='quote_no' value='$comment_number'>"
          . _('Quote') . "</button>";
      $out .= "<br />\n$comment_type";
      $out .= '<div class="tracker_comment">';
      # Full markup only for original submission.
      if ($comment_number < 1)
        $out .= markup_full ($text_to_markup);
      else
        $out .= markup_rich ($text_to_markup);
      $out .= "</div>\n</td>\n";

      $out .= "<td class=\"{$class}extra\">"
        . utils_user_link ($entry['user_name'], $entry['realname'], true);

      if ($icon)
        {
          $out .= "<br />\n<span class='help'>"
            . html_image ("roles/$icon.png", ['alt' => $icon_alt])
            . '</span>';
        }

      if ($poster_id != 100 && array_key_exists ($poster_id, $assignees_id))
        $out .= html_image (
          "roles/assignee.png", ['title' => _("In charge of this item.")]
        );

      # If not a member of the project, allow to mark as spam.
      # For performance reason, do not check here if the user already
      # flagged the comment as spam, it will be done only if he tries to
      # do it twice.
      if (user_isloggedin() && !$icon
          && $poster_id != user_getid () && empty ($_REQUEST['printer']))
        {
          # Surround by two line breaks, to keep that link clearly
          # separated from anything else, to avoid clicks by error.
          $out .= "<br /><br />\n";
          $cn = $comment_number - 1;
          $out .= "(<a $score\n  href=\"$url_start"
            . "flagspam$comment_ids#comment$cn\">"
            . html_image ("misc/trash.png", ['class' => 'icon'])
            . _("Flag as spam") . "</a>)<br /><br />\n";
        }
      $out .= "</td></tr>\n";
    } # foreach ($data as $entry)
  $out .= ($ascii ? "\n\n\n" : "</table>\n");

  return $out;
}

function format_item_changes ($changes, $item_id, $group_id)
{
  # ASCII must not be translated.
  global $sys_datefmt;

  # FIXME: strange, with %25s it does not behave exactly like
  # trackers_field_label_display.
  $fmt = "%24s: %23s => %-23s\n";

  $separator = "\n    _______________________________________________________\n\n";

  # Process most of the fields.
  $out = '';
  foreach ($changes as $field => $h)
    {
      # If both removed and added items are empty skip - Sanity check.
      if (empty($h['del']) && empty($h['add']))
        continue;

      if ($field == "details" || $field == "attach")
        continue;

      # Since details is used for followups (creepy!), we are forced to play
      # with "realdetails" non existant field.
      if ($field == "realdetails")
        $field = "details";

      $label = trackers_data_get_label($field);
      if (!$label)
        $label = $field;
      $out .= sprintf($fmt, $label,
                      isset($h['del']) ? $h['del'] : null,
                      isset($h['add']) ? $h['add'] : null);
    }

  if ($out)
    {
      $out = "Update of " . utils_get_tracker_prefix(ARTIFACT) . " #" . $item_id
             ." (project " . group_getunixname($group_id) . "):\n\n" . $out;
    }

  # Process special cases: follow-up comments.
  if (!empty($changes['details']))
    {
      if ($out)
        $out .= $separator;

      $out_com = "Follow-up Comment #"
                 . db_numrows(trackers_data_get_followups($item_id));
      if (!$out)
        {
          $out_com .= ", " . utils_get_tracker_prefix(ARTIFACT) . " #" . $item_id
                      . " (project " . group_getunixname($group_id) . ")";
        }

      $out_com .= ":\n\n";
      if ($changes['details']['type'] != 'None'
          && $changes['details']['type'] != '(Error - Not Found)')
        $out_com .= '[' . $changes['details']['type'] . "]\n";
      $out_com .= markup_ascii ($changes['details']['add']);
      unset ($changes['details']);

      $out .= $out_com;
    }

  # Process special cases: file attachment.
  if (!empty($changes['attach']))
    {
      if ($out)
        $out .= $separator;

      $out_att = "Additional Item Attachment";
      if (!$out)
        {
          $out_att .= ", " . utils_get_tracker_prefix(ARTIFACT) . " #" . $item_id
                      . " (project " . group_getunixname($group_id) . ")";
        }
      $out_att .= ":\n\n";

      foreach ($changes['attach'] as $file)
        {
          $out_att .= sprintf("File name: %-30s Size:%d KB\n    <%s>\n\n",
                              $file['name'], intval($file['size']/1024),
                              "https://" . $GLOBALS['sys_file_domain']
                              . '/file/' . $file['name'] . '?file_id='
                              . $file['id']);

        }
      unset($changes['attach']);
      $out .= $out_att;
    }

  return $out;
}

function format_item_attached_files ($item_id, $group_id, $ascii = false,
                                     $sober = false)
{
  # ASCII must not be translated.
  global $sys_datefmt, $HTML, $sys_home;
  $out = '';
  # In sober output, we assume that files are interesting in their
  # chronological order.
  # For instance, on the cookbook, if screenshots are provided, the author
  # of the item is likely to have posted them in the order of their use.
  # On the other hand, on non-sober output, what matters is the latest
  # submitted item.
  $order = $sober? 'ASC': 'DESC';

  $result = trackers_data_get_attached_files ($item_id, $order);
  $rows = db_numrows ($result);

  # No file attached -> return now.
  if ($rows <= 0)
    {
      if ($ascii)
        return "";
      return
        '<span class="warn">' . _("No files currently attached") . '</span>';
    }

  # Header first.
  if ($ascii)
    $out .= "    _______________________________________________________\n"
      . "File Attachments:\n\n";
  elseif (!$sober)
    $out .= $HTML->box_top(_("Attached Files"),'',1);

  # Determine what the print out format is based on output type (Ascii, HTML).
  if ($ascii)
    $fmt = "\n-------------------------------------------------------\n"
      . "Date: %s  Name: %s  Size: %s   By: %s\n%s\n%s";

  # Loop throuh the attached files and format them.
  for ($i = 0; $i < $rows; $i++)
    {
      $item_file_id = db_result ($result, $i, 'file_id');
      $href = $sys_home . ARTIFACT . "/download.php?file_id=$item_file_id";

      if ($ascii)
        $out .= sprintf (
          $fmt,
          utils_format_date (db_result ($result, $i, 'date')),
          db_result ($result, $i, 'filename'),
          utils_filesize (0, intval (db_result ($result, $i, 'filesize'))),
          db_result ($result, $i, 'user_name'),
          db_result ($result, $i, 'description'),
          '<http://' . $GLOBALS['sys_default_domain']
          . utils_unconvert_htmlspecialchars ($href) . '>'
        );
      else
        {
          $html_delete = '';
          $mem_ck = member_check (
            0, $group_id, member_create_tracker_flag (ARTIFACT) . '2'
          );
          if ($mem_ck && !$sober)
            {
              $html_delete = '<span class="trash"><a href="'
                . htmlentities ($_SERVER['PHP_SELF'])
                . "?func=delete_file&amp;item_id=$item_id"
                . "&amp;item_file_id=$item_file_id\">"
                . html_image_trash (['alt' => _("Delete"), 'class' => 'icon'])
                . '</a></span>';
            }

          if ($sober)
            $out .= '<div>&nbsp;&nbsp;&nbsp;- ';
          else
            $out .= '<div class="' . utils_altrow($i) . '">' . $html_delete;

          $out .= "<a href=\"$href\">file #$item_file_id: &nbsp;";

          if ($sober)
              $out .= "<a href=\"$href\">"
                . htmlspecialchars (db_result ($result, $i, 'filename'))
                . '</a>';
          else
            {
              # TRANSLATORS: the first argument is file name, the second
              # is user's name.
              $out .= sprintf (
                _('<!-- file -->%1$s added by %2$s'),
                htmlspecialchars (db_result ($result, $i, 'filename'))
                . '</a>',
                utils_user_link (db_result ($result, $i, 'user_name'))
              );
            }

          $out .= ' <span class="smaller">('
            . utils_filesize (0, db_result ($result, $i, 'filesize'));

          if (db_result ($result, $i, 'filetype'))
            $out .= ' - ' . db_result ($result, $i, 'filetype');

          if (db_result ($result, $i, 'description'))
            {
              $out .= ' - '
                . markup_basic (db_result ($result, $i, 'description'));
            }
          $out .= ")</span></div>\n";
        }
    } # for ($i = 0; $i < $rows; $i++)

  if ($ascii || $sober)
    $out .= "\n";
  else
    $out .= $HTML->box_bottom (1);

  return $out;
}

# Show the files attached to this bug.
function format_item_cc_list ($item_id, $group_id, $ascii = false)
{
  # ASCII must not be translated.
  global $sys_datefmt, $HTML;
  if ($ascii)
    $ascii = 1;
  else
    $ascii = 0;

  $result = trackers_data_get_cc_list ($item_id);
  $rows = db_numrows ($result);

  $out = '';

  # No file attached -> return now.
  if ($rows <= 0)
    {
      if (!$ascii)
        $out = '<span class="warn">' . _("CC list is empty") . '</span>';
      return $out;
    }

  # Header first an determine what the print out format is
  # based on output type (ASCII, HTML).
  if ($ascii)
    {
      $out .= "    _______________________________________________________\n\n"
        . "Carbon-Copy List:\n\n";
      $fmt = "%-35s | %s\n";
      $out .= sprintf ($fmt, 'CC Address', 'Comment');
      $out .=
        "------------------------------------+-----------------------------\n";
    }
  else
    $out .= $HTML->box_top (_("Carbon-Copy List"), '', 1);

  # Loop through the cc and format them.
  for ($i = 0; $i < $rows; $i++)
    {

      if ($ascii)
        {
          # We shan't provide the CC address in the mail, we keep that
          # information only on the web interface.
          $email = "Available only on the item webpage";
        }
      else
        {
          $email = db_result ($result, $i, 'email');

          # If email is numeric, it must be an user id. Try to convert it
          # to the username.
          if (ctype_digit ($email) && user_exists ($email))
            $email =  user_getname ($email);

          # HTML preformat the address.
          $email = utils_email ($email);
        }
      $item_cc_id = db_result ($result, $i, 'bug_cc_id');
      $href_cc = $email;

      # If the comment is -SUB-, -UPD- or -COM-, it means submitter
      # or commenter, etc.
      # It appears like this because the comment was automatically inserted.
      # It allows us to translated it only now, so the translation is the
      # one of the page viewer, not the one of that made the CC to be added.
      $comment = db_result ($result, $i, 'comment');
      $vot_arr = [
        'Voted in favor of this item', _('Voted in favor of this item')
      ];
      $com_arr = [
        '-SUB-' => ['Submitted the item', _('Submitted the item')],
        '-COM-' => ['Posted a comment', _('Posted a comment')],
        '-UPD-' => ['Updated the item', _('Updated the item')],
        '-VOT-' => $vot_arr, 'Voted in favor of this item' => $vot_arr
      ];
      if (isset ($com_arr[$comment]))
        $comment = $com_arr[$comment][$ascii];

      if ($ascii)
        {
          $out .= sprintf($fmt, $email, $comment);
          continue;
        }
      # Show CC delete icon if one of the condition is met:
      # a) current user is a tracker manager;
      # b) then CC name is the current user;
      # c) the CC email address matches the one of the current user;
      # d) the current user is the person who added the CC.
      $html_delete = '';
      $u_id = user_getid ();
      $u_name = user_getname ($u_id);
      $u_mail = user_getemail ($u_id);
      $res_name = db_result ($result, $i, 'user_name');
      $mem_ck = member_check (
        0, $group_id, member_create_tracker_flag (ARTIFACT) . '2'
      );
      if (
        $mem_ck || $u_name == $email || $u_mail == $email
        || $u_name == $res_name
      )
        $html_delete = '<span class="trash"><a href="'
          . htmlentities ($_SERVER['PHP_SELF'])
          . "?func=delete_cc&amp;item_id=$item_id"
          . "&amp;item_cc_id=$item_cc_id\">"
          . html_image_trash (['alt' => _("Delete"), 'class' => 'icon'])
          . '</a></span>';

      $out .= '<li class="' . utils_altrow ($i) . '">' . $html_delete;
      $u_link = utils_user_link (db_result ($result, $i, 'user_name'));
      # TRANSLATORS: the first argument is email, the second is user's name.
      $out .=
        sprintf (_('<!-- email --> %1$s added by %2$s'), $email, $u_link);
      if ($comment)
        $out .= ' <span class="smaller">(' . markup_basic ($comment)
          . ')</span>';
    } # for ($i = 0; $i < $rows; $i++)
  $out .= $ascii? "\n": $HTML->box_bottom (1);
  return $out;
}
?>
