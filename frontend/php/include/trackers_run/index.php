<?php
# Operate trackers.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2001-2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017, 2019 Ineiev
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

require_once (dirname (__FILE__) . '/../trackers/votes.php');

# This page does not give access to sober mode.
$sober = false;

if (!$group_id)
  exit_no_group();

$is_trackeradmin =
  member_check (0, $group_id, member_create_tracker_flag (ARTIFACT) . '2');

# Mention if there was an attached file: we cannot pre-fill an HTML input file.
function warn_about_uploads ()
{
  $filenames = [];
  for ($i = 1; $i < 5; $i++)
    $filenames[] = "input_file$i";
  $files = sane_import ('files',  ['pass' => $filenames]);
  foreach ($files as $file)
    {
      if ($file['error'] != UPLOAD_ERR_OK)
        continue;
      $msg = sprintf (
        _("Warning: do not forget to re-attach your file '%s'"),
        $file['name']
      );
      fb ($msg, 1);
    }
}

function fb_anon_check_failed ($check)
{
  if (!$check)
    return;
  fb (
    _("You're not logged in and you didn't enter the magic\n"
      . "anti-spam number, please go back!"),
    1
  );
}

extract (sane_import ('request',
  [
    'funcs' => 'func',
    'true' => 'printer',
    'digits' => ['item_file_id', 'item_cc_id']
  ]
));
extract (sane_import ('post',
  [
    'hash' => 'form_id', 'true' => ['submitreturn', 'preview'],
    'digits' => ['comment_type_id', 'quote_no'], 'pass' => 'comment',
    'preg' =>
      [
        ['canned_response', '/^(\d+|!multiple!)$/'],
        [
          'originator_email',
          '/^[a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+$/'
        ],
      ]
  ]
));

# Assign null to the fields that only tracker admins may modify.
foreach (
  [
    'depends_search', 'reassign_change_project_search', 'new_vote',
    'cc_comment', 'add_cc', 'reassign_change_project',
    'depends_search_only_artifact', 'reassign_change_artifact',
    'depends_search_only_project', 'dependent_on_task', 'dependent_on_bugs',
    'dependent_on_support', 'dependent_on_patch',
  ] as $var
)
  $$var = null;

if ($is_trackeradmin)
  extract (sane_import ('post',
    [
      'pass' => ['depends_search', 'reassign_change_project_search'],
      'digits' => 'new_vote', 'specialchars' => 'cc_comment',
      'preg' =>
        [
          ['add_cc', '/^[-+_@.,;\s\da-zA-Z]*$/'],
          [
            'reassign_change_project', '/^[-_[:alnum:]]*$/'
          ]
        ],
      'strings' =>
        [
          [
            'depends_search_only_artifact',
            'reassign_change_artifact',
            ['all', 'support', 'bugs', 'task', 'patch']
          ],
          [
            'depends_search_only_project',
            ['any', 'notany']
          ]
        ],
      'array' =>
        [
          [
            'dependent_on_task', 'dependent_on_bugs', 'dependent_on_support',
            'dependent_on_patch',
            [null, 'digits']
          ]
        ]
    ]
  ));

if ($canned_response === null)
  extract (sane_import ('post',
    ['array' => [['canned_response', [null, 'digits']]]]
  ));

extract (sane_import ('get',
  [
    'digits' => ['comment_internal_id', 'item_depends_on'],
    'artifact' => 'item_depends_on_artifact',
  ]
));

# If we are on an artifact index page and we have only one argument which is
# a numeric number, we suppose it is an item_id.
# Maybe it was a link shortcut like
# blabla.org/task/?nnnn (blabla.org/task/?#nnnn cannot work because # is
# not sent by the browser as it's a tag for html anchors).
if (!empty ($_SERVER['QUERY_STRING'])
    && ctype_digit ($_SERVER['QUERY_STRING']))
  $func = 'detailitem';

# Initialize the global data structure before anything else.
trackers_init($group_id);

$project = project_get_object ($group_id);
$changed = false;
$changes = array();

$browse_preamble = '';
$previous_form_bad_fields = false;
$sober = false;

$address = '';

if (!$func)
  $func = 'browse';

$process_comment = false;
if ($preview || isset($quote_no))
  $process_comment = true;
if ($process_comment)
  $submitreturn = 1;
switch ($func)
{
  case 'search':
    # Search in the item database.
    include '../include/trackers_run/search.php';
    break;

  case 'digest':
    # Item digest: search item stage.
    include '../include/trackers_run/digest.php';
    include '../include/trackers_run/browse.php';
    break;

  case 'digestselectfield':
    # Item digest: select field stage.
    include '../include/trackers_run/digest.php';
    break;

  case 'digestget':
    # Item digest: output.
    include '../include/trackers_run/digest.php';
    break;

  case 'browse':
    # Browse the bug database (it also the default).
    include '../include/trackers_run/browse.php';
    break;

  case 'additem':
    include '../include/trackers_run/add.php';
    break;

  case 'detailitem':
    include '../include/trackers_run/mod.php';
    break;

  case 'postadditem':
    # Actually add in the database what was filled in the form.
    $fields = sane_import ('post',
      [
        'hash' => 'form_id', 'strings' => [['check', '1984']],
        # As of 2022-02, frontend never reads from the spam_stats table,
        # so we may safely 'pass' 'details'.
        'pass' => 'details', 'true' => 'preview'
      ]
    );
    db_autoexecute (
      'spam_stats',
      [
        'tracker' => ARTIFACT, 'bug_id' => 0, 'type' => 'new',
        'user_id' => user_getid (), 'form_id' => $fields['form_id'],
        'ip' => '127.0.0.1', 'check_value' => $fields['check'],
        'details' => $fields['details']
      ]
    );
    $stat_id = db_insertid (NULL);

    $anon_check_failed = false;
    if (!user_isloggedin ())
      $anon_check_failed = empty ($fields['check']);

    # Check for duplicates.
    if (!form_check ($form_id))
      exit_error(_("Exiting"));

    # Get the list of bug fields used in the form.
    $vfl = trackers_extract_field_list ();

    $item_id = null;
    if (empty ($preview))
      {
        # Data control layer.
        $item_id = trackers_data_create_item ($group_id, $vfl, $address);
        db_execute (
          'UPDATE spam_stats SET bug_id = ? WHERE id = ?',
         [$item_id, $stat_id]
        );
      }
    if ($previous_form_bad_fields || !empty ($preview) || $anon_check_failed)
       warn_about_uploads ();
    if ($item_id && empty ($preview) && !$anon_check_failed)
      {
        # Attach new file if there is one.
        # As we need to create the item first to have an item id so this
        # function can work properly, we shan't be able to update the
        # comment on-the-fly to mention in the comment the attached files.
        # However, this is unlikely to be a problem because the attached
        # files is the section next to comments, and the original
        # submission in the latest comment. So the proximity is always
        # optimal.
        list ($changed,) =
          trackers_attach_several_files ($item_id, $group_id, $changes);
        # Add new cc if any.
        if ($add_cc && user_isloggedin())
          trackers_add_cc (
            $item_id, $group_id, $add_cc, $cc_comment, $changes
          );

        # Originator Email:
        # "Email address of the person who submitted the item
        # (if different from the submitter field, add address to CC list)".
        # Only apply this behavior if the field is present and used.
        $oe_field_name = "originator_email";

        if (trackers_data_is_used ($oe_field_name))
          {
            # Originator email is only available to anonymous.
            if (!user_isloggedin ()
                && trackers_data_is_showed_on_add_nologin ($oe_field_name))
              {
                # Cannot be a registered user.
                if (validate_email ($originator_email))
                  {
                    # Must be different from the submitter field.
                    $res = db_execute (
                      "SELECT email FROM user WHERE user_id = ?", [user_getid ()]
                    );
                    $submitter_email = db_result ($res, 0, 'email');
                    if ($originator_email != $submitter_email)
                      {
                        trackers_add_cc (
                          $item_id, $group_id, $originator_email, "-SUB-",
                          $changes
                        );
                      }
                  }
                else
                  fb (_("Originator E-mail is not valid, thus was not added\n"
                        . "to the Carbon-Copy list."), 1);
              }
          }
        # Send an email to notify the user of the item update
        # (third arg of get_item_notification must be 0 for a first
        # submission).
        list ($additional_address, $sendall) =
          trackers_data_get_item_notification_info ($item_id, ARTIFACT, 0);

        if ((trim ($address) != "") && (trim ($additional_address) != ""))
          $address .= ", ";
        $address .= $additional_address;
        trackers_mail_followup ($item_id, $address);
      }
    else # !($item_id && empty ($preview) && !$anon_check_failed)
      {
        # Some error occurred.

        # Missing mandatory field?
        # The relevant error message was supposedly properly produced by
        # trackers_data_create_item.
        # Reshow the same page.
        if ($previous_form_bad_fields || $preview || $anon_check_failed)
          {
            # Copy the previous form values (taking into account dates)
            # to redisplay them and initialize nocache to 0.
            foreach ($vfl as $fieldname => $value)
              {
                if (trackers_data_is_date_field ($fieldname))
                  list ($value, $ok) = utils_date_to_unixtime ($value);
                $$fieldname = $value;
              }
            fb_anon_check_failed ($anon_check_failed);
            $nocache = 0;
            include '../include/trackers_run/add.php';
            break;
          }
        # Otherwise, that's odd and there's not much to do.
        fb (_("Missing parameters, nothing added."), 1);
      } # !($item_id && empty ($preview) && !$anon_check_failed)

    # Show browse item page.
    include '../include/trackers_run/browse.php';
    break;

  case 'postmoditem':
    # Actually add in the database what was filled in the form
    # for a bug already in the database, reserved to item techn.
    # or manager.
    $fields = sane_import ('post',
      [
        'hash' => 'form_id', 'digits' => ['item_id'],
        'strings' => [['check', '1984']], 'pass' => 'comment'
      ]
    );
    db_autoexecute (
      'spam_stats',
      [
        'tracker' => ARTIFACT, 'bug_id' => $fields['item_id'],
        'type' => 'comment', 'user_id' => user_getid (),
        'form_id' => $fields['form_id'], 'ip' => '127.0.0.1',
        'check_value' => $fields['check'], 'details' => $fields['comment']
      ]
    );

    if (!form_check ($form_id))
      exit_error (_("Exiting"));

    $anon_check_failed = false;
    if (!user_isloggedin ())
      $anon_check_failed = empty ($fields['check']) && !$process_comment;

    # Filter out people that would submit data while they are not allowed
    # too (obviously by using an old form, or something else).
    $result = db_execute ("
      SELECT privacy, discussion_lock, submitted_by
      FROM " . ARTIFACT . " WHERE bug_id = ? AND group_id = ?",
      [$item_id, $group_id]
    );

    if (db_numrows ($result) > 0)
      {
        # Check if the item is private, refuse post if it is and the
        # users has no appropriate rights (not member, not submitter).
        if (db_result ($result, 0, 'privacy') == '2')
          {
            if (
              !member_check (user_getid (), $group_id)
              && db_result ($result, 0, 'submitted_by') != user_getid ()
            )
              {
                # As the user here is expected to behave maliciously,
                # return an error message that does not give too much info.
                exit_permission_denied ();
              }
          }
        if (db_result ($result, 0, 'discussion_lock'))
          exit_permission_denied ();
      }
    elseif (!$is_trackeradmin)
      exit_permission_denied ();

    # To keep track of changes.
    $changes = [];

    # Special case: we may be searching for an item, in that case
    # reprint the same page, plus search results.
    if ($depends_search || $reassign_change_project_search
        || $canned_response == "!multiple!")
      {
        if ($depends_search)
          {
            $msg = sprintf (
               _("You provided search words to get a list of items\nthis one "
                 . "may depend on. Below, in the section [%s Dependencies], "
                 . "you can now\nselect the appropriate ones and submit "
                 . "the form."),
               $sys_https_url . $_SERVER['SCRIPT_NAME'] . '#dependencies'
            );
            fb ($msg);
          }
        if ($reassign_change_project_search)
          {
            $msg = sprintf (
              _("You provided search words to get a list of projects\nthis "
                . "item should maybe reassigned to. Below, in the section\n"
                . "[%s Reassign this item], you can now select the "
                . "appropriate\nproject and submit the form."),
              $sys_https_url . $_SERVER['SCRIPT_NAME'] .'#reassign'
            );
            fb ($msg);
          }
        if ($canned_response == "!multiple!")
          {
            fb (_("You selected Multiple Canned Responses: you are free now\n"
                  . "to select the one you want to use to compose your answer.")
            );
          }
        include '../include/trackers_run/mod.php';
        exit (0);
      }

    # Get the list of bug fields used in the form.
    $vfl = trackers_extract_field_list();

    $changed = 0;
    if (!$process_comment)
      {
        fb_anon_check_failed ($anon_check_failed);
        if (!$anon_check_failed)
          {
            list ($changed, $additional_comment) =
              trackers_attach_several_files ($item_id, $group_id, $changes);

            # If there is an item for this comment, add the additional
            # comment providing refs to the item.
            if (array_key_exists('comment', $vfl) && $vfl['comment'] != '')
              $vfl['comment'] .= $additional_comment;

            $changed |= trackers_data_handle_update (
              $group_id, $item_id, $dependent_on_task, $dependent_on_bugs,
              $dependent_on_support, $dependent_on_patch, $canned_response,
              $vfl, $changes, $address
            );
          }
        # The update failed due to a missing field? Reprint it and squish
        # the rest of the action normally done.
        if (!$changed && ($previous_form_bad_fields || $anon_check_failed))
          {
            warn_about_uploads ();
            # Copy the previous form values (taking into account dates) to
            # redisplay them and initialize nocache to 0.
            foreach ($vfl as $fieldname => $value)
              {
                if (trackers_data_is_date_field ($fieldname))
                  list ($value, $ok) = utils_date_to_unixtime ($value);
                $$fieldname = $value;
              }
            $nocache = 0;
            include '../include/trackers_run/mod.php';
            exit (0);
          }

        # Add new cc if any.
        if ($add_cc)
          {
            # No notification needs to be sent when a cc is added,
            # it is irrelevant to the item itself.
            trackers_add_cc (
              $item_id, $group_id, $add_cc, $cc_comment, $changes
            );
          }

        # Update vote (will do the necessary checks itself).
        # Currently votes does not influence notifications
        # (that could harass developers).
        if (trackers_data_is_used ("vote"))
          trackers_votes_update ($item_id, $group_id, $new_vote);
      } # !$process_comment
    else
      warn_about_uploads ();

    # Now handle notification, after all necessary actions has been.
    if ($changed)
      {
        # Check if we re supposed to send all modifications to an address.
        list($additional_address, $sendall) =
          trackers_data_get_item_notification_info ($item_id, ARTIFACT, 1);

        if (($sendall == 1) && (trim ($address) != "")
            && (trim ($additional_address) != ""))
          $address .= ", ";
        $address .= $additional_address;
        trackers_mail_followup ($item_id, $address, $changes);

        # If the assigned_to was changed and the previously assigned guy
        # wants to be removed from CC when he is no longer assigned, do it now.
        # We do this after the item update so the previously assignee
        # got the notification of the this change.
        if (!empty ($changes['assigned_to']['del']))
          {
            $prev_uid = user_getid ($changes['assigned_to']['del']);
            if (user_get_preference ("removecc_notassignee", $prev_uid))
              {
                # No feedback for this.
                trackers_delete_cc_by_user ($item_id, $prev_uid);
              }
          }
      }
    # Handle reassignation of an entry. Why so late?
    # Because all the information entered by someone reassigning
    # the bug must be in the original report, and will be duplicated
    # in the new one.
    if (
      $reassign_change_project
      || ($reassign_change_artifact && ($reassign_change_artifact != ARTIFACT))
    )
      {
        dbg("reassign item: reassign_change_project:$reassign_change_project, "
            . "reassign_change_artifact:$reassign_change_artifact, ARTIFACT:"
            . ARTIFACT);
        trackers_data_reassign_item (
          $item_id, $reassign_change_project, $reassign_change_artifact
        );
      }

    # Show browse item page, unless the user want to get back
    # to the same report, to make something else.
    if (!$submitreturn)
      {
        include '../include/trackers_run/browse.php';
        exit (0);
      }
    if (!$process_comment)
      {
        if (isset ($item_id))
          {
            # Include tracker item number in URL, if present.
            header ("Location: {$_SERVER['PHP_SELF']}?$item_id");
            exit (0);
          }
        $_POST = $_FILES = [];
        $form_id = $depends_search = $reassign_change_project_search =
        $add_cc = $input_file = $changed = $vfl = $details = $comment = null;
        $nocache = 1;
      }
    include '../include/trackers_run/mod.php';
    exit (0);

  case 'delete_file':
    # Remove an attached file.
    if ($is_trackeradmin)
      {
        trackers_data_delete_file($group_id, $item_id, $item_file_id);

         # Unset previous settings and return to the item.
         $depends_search = $reassign_change_project_search = $add_cc
           = $input_file = $changed = $vfl = $details = null;
         include '../include/trackers_run/mod.php';
      }
    else
      exit_permission_denied ();
    break;

  case 'delete_cc':
    # Remove a person from the Cc.
    $changed = trackers_delete_cc ($group_id, $item_id, $item_cc_id, $changes);

    # Unset previous settings and return to the item.
    $depends_search = $reassign_change_project_search = $add_cc = $input_file
      = $changed = $vfl = $details = null;

    include '../include/trackers_run/mod.php';
    break;

  case 'delete_dependency':
    $changed |= trackers_delete_dependency (
      $group_id, $item_id, $item_depends_on, $item_depends_on_artifact,
      $changes
    );
    if ($changed)
      {
        # See if we are supposed to send all modifications to an address.
        list($additional_address, $sendall) =
          trackers_data_get_item_notification_info ($item_id, ARTIFACT, 1);
        if (($sendall == 1) && (trim($address) != "")
            && (trim($additional_address) != ""))
          $address .= ", ";
        $address .= $additional_address;
        trackers_mail_followup($item_id, $address, $changes);
      }

    # Unset previous settings and return to the item.
    $depends_search = $reassign_change_project_search = $add_cc = $input_file
      = $changed = $vfl = $details = $changes = $address = null;
    include '../include/trackers_run/mod.php';
    break;

  case 'flagspam':
    # Only allowed to logged in user.
    if (!user_isloggedin ())
      {
        # Do not use exit_not_logged_in(), because the user has no
        # valid reason to get here if he was not logged in in first place
        # (the link was not provided).
        exit_permission_denied ();
      }

    # Determine the additional spamscore according to user credentials.
    # +1 = logged in user
    # +3 = project member
    # +5 = project admin
    $spamscore = 1;
    if (member_check (0, $group_id))
      {
        if (member_check (0, $group_id, 'A'))
          $spamscore = 5;
        else
          $spamscore = 3;
      }
    spam_flag ($item_id, $comment_internal_id, $spamscore, $group_id);

    # Return to the item page if it was not the item itself that was
    # marked as spam.
    include '../include/trackers_run/mod.php';
    break;

  case 'unflagspam':
    # Unflag an alledged spam: for group admins only.
    if (!member_check (0, $group_id, 'A'))
      {
        # Do not use exit_not_logged_in(), because the user has no
        # valid reason to get here if he was not logged in in first place
        # (the link was not provided).
        exit_permission_denied ();
      }

    spam_unflag ($item_id, $comment_internal_id, ARTIFACT, $group_id);
    include '../include/trackers_run/mod.php';
    break;
  case 'viewspam':
    include '../include/trackers_run/mod.php';
    break;

  case 'browse' :
  default :
    include '../include/trackers_run/browse.php';
    break;
} # switch ($func)
?>
