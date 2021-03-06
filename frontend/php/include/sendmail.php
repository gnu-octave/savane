<?php
# Every mails sent should be using functions listed here.
#
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2018, 2019, 2020, 2022 Ineiev
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


# The function that finally send the mail.
# Every mail sent by Savannah should be using that function which
# works like mail ().
# Note: $to can be a coma-separated list.
#       $from and $to can contain user names
function sendmail_mail (
  $from, $to, $subject, $message, $savane_project = 0, $savane_tracker = 0,
  $savane_item_id = 0, $reply_to = 0, $additional_headers = 0,
  $exclude_list = 0
)
{
  global $int_delayspamcheck;

  # Check if $delayspamcheck makes sense.
  if (!$savane_project || !$savane_tracker || !$savane_item_id)
    unset ($int_delayspamcheck);

  # Clean the markup.
  $message = markup_textoutput ($message);

  # Make sure the message respect the 78chars max width.
  # Make also sure we havent got excessive slashes escaping.
  $message = wordwrap ($message, 78);

  $to = str_replace (";", ",", $to);

  # Transform $to in an ordered list, without duplicates
  # (remove blankspaces).
  # FIXME: this is questionable, as it disallow stuff like
  #    "Dupont Lajoie" <dupont@devnull.net>
  # and we should allow that.
  # (Check on $to necessary because explode returns a one element array in case
  # iet has to explode an empty string and this screws the code later on).
  if ($to != "")
    $to = array_unique (explode (",", str_replace (" ", "", $to)));
  else
    $to = [];

  # If $from is a login name, write nice From: field.
  $fromuid = user_getid ($from);
  if (user_exists ($fromuid))
    $from =
      user_getrealname ($fromuid, 1) . " <" . user_getemail ($fromuid) . ">";

  # Write the add. headers
  # Note: RFC-821 recommends to use \r\n as line break in headers but \n
  # works and there are report of failures with \r\n so we let \n for now.
  $more_headers = "From: " . sendmail_encode_header_content ($from) . "\n";
  if ($reply_to)
    $more_headers .= "Reply-To: $reply_to\n";

  # Add a signature for the server (not if delayed, because it will be added
  # we the mail will be actually sent).
  if (empty ($int_delayspamcheck))
    $more_headers .= "X-Savane-Server: {$_SERVER['SERVER_NAME']}:"
       . "{$_SERVER['SERVER_PORT']} [{$_SERVER['SERVER_ADDR']}]\n";

  # Necessary for proper utf-8 support.
  $more_headers .= "MIME-Version: 1.0\n";
  $more_headers .= "Content-Type: text/plain;charset=UTF-8\n";

  # Savane details.
  if ($savane_project)
    $more_headers .= "X-Savane-Project: $savane_project\n";
  if ($savane_tracker)
    $more_headers .= "X-Savane-Tracker: $savane_tracker\n";
  $savane_comment_id = 0;
  if ($savane_item_id)
    {
      # Look if there is a (internal) comment id set.
      if (strpos ($savane_item_id, ":"))
        list ($savane_item_id, $savane_comment_id) =
          explode (":", $savane_item_id);
      $more_headers .= "X-Savane-Item-ID: $savane_item_id\n";
    }
  if ($additional_headers)
    $more_headers .= "$additional_headers\n";

  # User details: user agent and REMOTE_ADDR are not included
  # per Savannah sr #110592.

  if (user_isloggedin ())
    {
      $more_headers .= "X-Apparently-From: "
        . "Savane authenticated user " . user_getname (user_getid ()) . "\n";
    }

  $msg_id = sendmail_create_msgid ();
  $more_headers .= "Message-Id: <$msg_id>\n";
  if ($savane_tracker && $savane_item_id)
    {
      $more_headers .= "References: "
        . trackers_get_msgid ($savane_tracker, $savane_item_id) . "\n";
      $more_headers .= "In-Reply-To: "
        . trackers_get_msgid ($savane_tracker, $savane_item_id, true) . "\n";
    }

  # Add a signature for the server (not if delayed, because it will be added
  # we the mail will be actually sent).
  if (empty ($int_delayspamcheck))
    $message .= "\n\n_______________________________________________\n"
      # TRANSLATORS: the argument is site name (like Savannah).
      . sprintf (_("Message sent via %s"), $GLOBALS['sys_name'])
      . "\nhttps://{$GLOBALS['sys_default_domain']}{$GLOBALS['sys_home']}\n";

  # Register the message id for future references.
  if ($savane_tracker && $savane_item_id)
    trackers_register_msgid ($msg_id, $savane_tracker, $savane_item_id);

  # If there is an exclude list, create an array.
  $exclude = [];
  if ($exclude_list)
    {
      $exclude_list = str_replace (";", ",", $exclude_list);
      $exclude = array_unique (
        explode (",", str_replace (" ", "", $exclude_list))
      );
    }
  foreach ($exclude as $v)
    {
      if ($v)
        $exclude[$v] = 1;
    }

  # Forge the real to list, by parsing every item of the $to list.
  $recipients = [];

  # Do a first run to convert squads by users.
  $to2 = $squad_seen_before = [];
  foreach ($to as $v)
    {
      if (ctype_digit ($v))
        $touid = $v;
      else
        $touid = user_getid ($v);

      # Squad exists in the exclude array? Skip it.
      if (!empty ($exclude[$v]))
        continue;

      # Already handled?
      if (!empty ($squad_seen_before[$v]))
        continue;

      # Record that we handled this already.
      $squad_seen_before[$v] = true;

      # If an address is a squad username, push in all the relevant users
      # uid.
      if (!strpos ($v, "@"))
        {
          if (user_exists ($touid, true))
            {
              if (
                is_array ($exclude)
                && array_key_exists (user_getname ($touid), $exclude)
              )
                continue;

              # If we get here, we have a squad and we will store all the
              # squad members uid.
              $result_squad = db_execute ("
                SELECT user_id FROM user_squad WHERE squad_id = ?", [$touid]
              );
              if ($result_squad && db_numrows ($result_squad) > 0)
                {
                  while ($thisuser = db_fetch_array ($result_squad))
                    $to2[] = $thisuser['user_id'];
                }
              # No need to go further, this squad was handled.
              continue;
            }
        }
      # If we get here, it means that we have an address that is not squad
      # related have we keep it for the next run.
      $to2[] = $v;
    }

  # Second run, we should have only real users here, no squads.
  $list = $user_subject = $user_name  = $seen_before = [];
  $i = 0;
  foreach ($to2 as $v)
    {
      if (is_numeric ($v))
        $touid = $v;
      else
        $touid = user_getid ($v);

      # User exists in the exclude array? Skip it.
      if (!empty ($exclude[$v]))
        continue;

      # Already handled?
      if (!empty ($seen_before[$v]))
        continue;

      # Record that we handled this already.
      $seen_before[$v] = true;

      $i++;
      # If an address is a username, get the email address from
      # the database.
      # If nothing is found, just let the username - there is maybe a
      # local alias.
      if (!strpos ($v, "@"))
        {
          if (user_exists ($touid))
            {
              # Exists in the exclude array? Skip it
              if (
                is_array ($exclude)
                && array_key_exists (user_getname ($touid), $exclude)
              )
                continue;

              $thisuser_email = user_getemail ($touid);

              # Does the user have a specific subject line?
              # FIXME: in the rare case where the user got a specific subject
              # line and was added in CC manually, he may receive twice
              # the notification, if he is added in realto because his
              # email was plenty entered before the entry referring to his
              # login is handled.
              # If we do check %seen_before just before this, we would
              # avoid duplicates but we may loose the notification with
              # the user defined subject, which would be worse.
              # The only way to handle this would be to cross-check the
              # $realto (for instance by using only $seen_before and building
              # $realto at the last step) but that would probably be
              # overkill.
              if (user_get_preference ("subject_line", $touid) != "")
                {
                  $list[$i] = $v;
                  $subjl = sendmail_format_subject_line (
                     user_get_preference ("subject_line", $touid),
                     $savane_project, $savane_tracker, $savane_item_id
                  );
                  $user_subject[$v] = "$subjl $subject";
                  $user_name[$v] = user_getrealname ($touid, 1)
                    . " <$thisuser_email>";

                  $seen_before[$thisuser_email] = true;
                  continue;
                }

              # Already handled?
              if (!empty ($seen_before[$thisuser_email]))
                continue;

              # Record that we handled this already.
              $seen_before[$thisuser_email] = true;

              # Finally, format nicely the entry
              $v = user_getrealname ($touid, 1) . " <$thisuser_email>";
            }
          else
            {
              # We have a string without @ that is not a user login?
              # We assume it could be valid in the mail domain (like a mailing
              # list).
              # Usually, this is useless, as functions calling
              # sendmail_mail () should have already made checks
              # (exception: global notifications of trackers).
              $seen_before[$v] = true;
              $v = utils_normalize_email ($v);

              # Already handled?
              if (isset ($seen_before[$v]) && $seen_before[$v])
                continue;
            }
        }

      # FIXME: if at some point we will accept entries like
      #  "Dupont Lajoie" <dupont@devnull.net>
      #  we will have to extract "dupont@devnull.net" part and put it
      # in %seen_before.

      # Add addresses arrived so far to the list.
      $recipients[] = $v;

      # Always record the full string. We may have already saved such info
      # before, but we maybe saved strictly the email address, while the
      # full string may show up once more. If the full string reappears, we
      # shan't have to parse it to find the correct email.
      $seen_before[$v] = true;
    } # foreach ($to2 as $v)

  # Add eventually info on the subject.
  if ($savane_tracker && $savane_item_id)
    $subject = "[" . utils_get_tracker_prefix ($savane_tracker)
      . " #$savane_item_id] $subject";

  # agn,28-sep-2016
  # If email debugging is on - ignore the recipient list,
  # and send to the specified email address.
  # This variable should be set in `.savane.conf.php`.
  if (isset ($GLOBALS['sys_debug_email_override_address']))
    {
      $adr = $GLOBALS['sys_debug_email_override_address'];
      $message = "Savannah Debug: email override is turned on\n"
         . "Original recipient list:\n"
         . sendmail_encode_recipients ($recipients)
         . "\n------------\n\n$message";
      $recipients = [$adr];
      $list = []; # No recipients with custom subject lines.
    }

  # Beuc - 20050316
  # That is what I intended to do:

  # All newlines should be \r\n; this is apparently more
  # RFC821-compliant.
  # $message = preg_replace("/(?<!\r)\n/", "\r\n", $message);

  # However the opposite is certainly more Mailman-compliant; a bug
  # report has been posted to the Mailman team - wait&see [bug #1980].
  $message = str_replace ("\r\n", "\n", $message);

  # Send the mail in UTF-8.
  # Normally, nothing non-ASCII should be contained in To: field, apart the
  # real names.

  # Send the final mail.
  $ret = '';
  if (count ($recipients) > 0)
    {
      $real_to = sendmail_encode_recipients ($recipients);

      # Normally, $real_to should not contain duplicates
      if (empty ($int_delayspamcheck))
        {
          $ret .= mail (
            $real_to, sendmail_encode_header_content ($subject),
            $message, $more_headers
          );
          # TRANSLATORS: the argument is a comma-separated list of recipients.
          fb (sprintf ( _("Mail sent to %s"), join (', ', $recipients)));
        }
      else
        {
          # Wait to be checked for spams.
          db_autoexecute (
            'trackers_spamcheck_queue_notification',
            [
              'artifact' => $savane_tracker, 'item_id' => $savane_item_id,
              'comment_id' => $savane_comment_id, 'to_header' => $real_to,
              'other_headers' => $more_headers,
              'subject_header' => sendmail_encode_header_content ($subject),
              'message' => $message
            ],
            DB_AUTOQUERY_INSERT
          );
        }
    } # if (count ($recipients) > 0)

  # Send mails with specific subject line.
  foreach ($list as $v)
    {
      $u_name = sendmail_encode_header_content ($user_name[$v]);
      $u_subj = sendmail_encode_header_content ($user_subject[$v]);
      if (empty ($int_delayspamcheck))
        {
          $ret .= mail ($u_name, $u_subj, $message, $more_headers);
          # TRANSLATORS: the argument is a single email address.
          fb (sprintf (_("Mail sent to %s"), utils_email ($user_name[$v], 1)));
        }
      else
        # Wait to be checked for spams.
        db_autoexecute (
          'trackers_spamcheck_queue_notification',
          [
            'artifact' => $savane_tracker, 'item_id' => $savane_item_id,
            'comment_id' => $savane_comment_id, 'to_header' => $u_name,
            'other_headers' => $more_headers, 'subject_header' => $u_subj,
            'message' => $message
          ],
          DB_AUTOQUERY_INSERT
        );
    }
  return $ret;
}

# Encode each recipient separately and separate them using commas.
function sendmail_encode_recipients ($recipients)
{
  $r = array_map ("sendmail_encode_header_content", $recipients);
  return join (', ', $r);
}

# Needed to send utf-8 headers:
# Take a look at http://www.faqs.org/rfcs/rfc2047.html.
# We should use mb_encode_mimeheader () but it just does not work.
#
# We must not encode starting and ending quotes.
# We assume there could be only 2 quotes. Otherwise it would be a malformed
# address.
# The easy way we use to do this is to simply consider as one string the
# content of the quote, if any. If so, we are not working word per word but
# it saves us the time of searching for quotes in every words.
function sendmail_encode_header_content ($header, $charset = "UTF-8")
{
  if (strpos ($header, '"') !== FALSE)
    {
      # Quotes found, we each quoted part will be a string to encode.
      $words = explode ('"', $header);
      $separator = '"';
    }
  else
    {
      # Otherwise, the default behavior is to consider words as strings to
      # encode.
      $words = explode (' ', $header);
      $separator = ' ';
    }
  foreach ($words as $key => $word)
    $encode[$key] = !utils_is_ascii ($word);
  $last_key = count ($words) - 1;
  foreach ($words as $key => $word)
    {
      if (!$encode[$key])
        continue;
      # Embed the space in the encoded word (spaces between encoded
      # words are ignored when rendering).
      if ($separator === ' ' && $key != $last_key && $encode[$key + 1])
        $word .= $separator;
      $words[$key] = "=?$charset?B?" . base64_encode ($word) . "?=";
    }
  return join ($separator, $words);
}

# A form for logged in users to send mails to others users.
function sendmail_form_message ($form_action, $user_id, $cc_me = true)
{
  global $HTML;
  print $HTML->box_top (
    # TRANSLATORS: the argument is user's name.
    sprintf (_("Send a message to %s"), user_getrealname ($user_id))
  );
  print '<p class="warn">'
    . _("If you are writing for help, did you read the\nproject documentation "
        . "first? Try to provide any potentially useful information\n"
        . "you can think of.")
     . "</p>\n";

  $pre = '<span class="preinput">';
  $post = "</span><br />\n&nbsp;&nbsp;&nbsp;";
  # We do not really bother finding out the realname + email, sendmail_mail ()
  # will do it.
  print "<form action=\"$form_action\" method='post'>\n"
    . form_hidden ([
        'touser' => htmlspecialchars ($user_id),
        'fromuser' => user_getname ()])
    . "\n$pre" . _("From:") . "$post"
    . user_getrealname (user_getid (), 1) . ' &lt;'
    . user_getemail (user_getid ()) . "&gt;<br />\n"
    . $pre . _("Mailer:") . $post
    . utils_cutstring ($_SERVER['HTTP_USER_AGENT'], "50")
    . "<br />\n$pre<label for='subject'>" . _("Subject:") . "</label>$post"
    . '<input type="text" id="subject" name="subject" '
    . "size='60' maxlength='45' value='' /><br />\n$pre"
    . form_checkbox ("cc_me", $cc_me, ['value' => 'cc_me'])
    . " <label for='cc_me'>" . _("Send me a copy") . "</label>$post"
    . "$pre<label for='body'>" . _("Message:") . "</label>$post"
    . "<textarea id='body' name='body' rows='20' cols='60'></textarea>\n\n"
    . '<p align="center"><input type="submit" name="send_mail" value="'
    . _('Send Message') . "\" /></p>\n</form>\n";
  print $HTML->box_bottom ();
}

function sendmail_format_subject_line (
  $subject_line, $savane_project = "", $savane_tracker = "",
  $savane_item_id = ""
)
{
  $subject_line = str_replace ("%SERVER", $GLOBALS['sys_default_domain'],
                               $subject_line);
  $subject_line = str_replace ("%PROJECT", $savane_project, $subject_line);
  $subject_line = str_replace ("%TRACKER", $savane_tracker, $subject_line);
  return str_replace ("%ITEM", "#".$savane_item_id, $subject_line);
}

function sendmail_create_msgid ()
{
  mt_srand ((double)microtime () * 1000000);
  return
    date ("Ymd-His", time ()) . ".sv" . user_getid () . "."
    . mt_rand (0,100000) . "@" . $_SERVER["HTTP_HOST"];
}
?>
