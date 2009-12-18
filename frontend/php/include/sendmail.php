<?php
# Every mails sent should be using functions listed here.
# 
# <one line to give a brief idea of what this does.>
# 
# Copyright 2003-2006 (c) Mathieu Roy <yeupou--gnu.org>
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
# works like mail().
# Note: $to can be a coma-separated list.
#       $from and $to can contain user names
function sendmail_mail ($from, 
			$to, 
			$subject, 
			$message,  #4
			$savane_project=0, 
			$savane_tracker=0, 
			$savane_item_id=0, 
			$reply_to=0, # 8
			$additional_headers=0,
			$exclude_list=0) 
{
  
  global $int_delayspamcheck;

  # Check if $delayspamcheck makes sense
  if (!$savane_project || !$savane_tracker || !$savane_item_id)
    { unset($int_delayspamcheck); }

  # Clean the markup
  $message = markup_textoutput($message);

  # Make sure the message respect the 78chars max width
  # Make also sure we havent got excessive slashes escaping
  $message = wordwrap($message, 78);

  # Convert ; into
  $to = ereg_replace(";", ",", $to);

  # Transform $to in an ordered list, without duplicates
  # (remove blankspaces)
  # FIXME: this is questionable, as it disallow stuff like 
  #    "Dupont Lajoie" <dupont@devnull.net>
  # and we should allow that.
  # (check on $to necessary because explode returns a one element array in case
  # iet has to explode an empty string and this screws the code later on)
  if ($to != "") 
    { $to = array_unique(explode(",", ereg_replace(" ", "", $to))); } 
  else 
    { $to = array(); }

  # If $from is a login name, write nice From: field
  $fromuid = user_getid($from);
  if (user_exists($fromuid))
    {
      $from = user_getrealname($fromuid, 1)." <".user_getemail($fromuid).">";
    }

  # Write the add. headers 
  # Note: RFC-821 recommends to use \r\n as line break in headers but \n
  # works and there are report of failures with \r\n so we let \n for now.
  $more_headers = "From: ".sendmail_encode_header_content($from)."\n";
  if ($reply_to)
    { $more_headers .= "Reply-To: ".$reply_to."\n"; }

  # Add a signature for the server (not if delayed, because it will be added
  # we the mail will be actually sent)
  if (empty($int_delayspamcheck))
    {  
      $more_headers .= "X-Savane-Server: ".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT']." [".$_SERVER['SERVER_ADDR']."]\n";
    }

  # Necessary for proper utf-8 support
  $more_headers .= "MIME-Version: 1.0\n";
  $more_headers .= "Content-Type: text/plain;charset=UTF-8\n";

  # Savane details
  if ($savane_project) 
    { $more_headers .= "X-Savane-Project: ".$savane_project."\n"; }
  if ($savane_tracker) 
    { $more_headers .= "X-Savane-Tracker: ".$savane_tracker."\n"; }
  $savane_comment_id = 0;
  if ($savane_item_id) 
    {
      # Look if there is a (internal) comment id set 
      if (strpos($savane_item_id, ":"))
	{
	  list($savane_item_id, $savane_comment_id) = split(":", $savane_item_id);
	}
      $more_headers .= "X-Savane-Item-ID: ".$savane_item_id."\n"; 
    }
  if ($additional_headers) 
    { $more_headers .= $additional_headers."\n";  }

  # User details.
  # Tell what is the user agent, tell which authenticated user made
  # the mail to be sent
  if (empty($int_delayspamcheck))
    {  
      $more_headers .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\n";
    }

  if (user_isloggedin())
    {
      $more_headers .= "X-Apparently-From: ".$_SERVER['REMOTE_ADDR']." (Savane authenticated user ".user_getname(user_getid()).")\n";
    }
  else
    {
      $more_headers .= "X-Apparently-From: ".$_SERVER['REMOTE_ADDR']."\n";
    }

  # Message ID
  $msg_id = sendmail_create_msgid();
  $more_headers .= "Message-Id: <".$msg_id.">\n";
  # Add refs
  if ($savane_tracker && $savane_item_id)
    {      
      $more_headers .= "References: ".trackers_get_msgid($savane_tracker, $savane_item_id)."\n";
      $more_headers .= "In-Reply-To: ".trackers_get_msgid($savane_tracker, $savane_item_id, true)."\n";
    }

  # Add a signature for the server (not if delayed, because it will be added
  # we the mail will be actually sent)
  if (empty($int_delayspamcheck))
   {
     $message .= "\n\n_______________________________________________
  ".sprintf(_("Message sent via/by %s"), $GLOBALS['sys_name'])."
  http://".$GLOBALS['sys_default_domain'].$GLOBALS['sys_home']."\n";
   }

  # Register the message id for future references
  if ($savane_tracker && $savane_item_id)
    {
      trackers_register_msgid($msg_id, $savane_tracker, $savane_item_id);
    }

  # If there is an exclude list, create an array 
  # Convert ; into
  $exclude = array();
  if ($exclude_list)
    {
      $exclude_list = ereg_replace(";", ",", $exclude_list);
      $exclude = array_unique(explode(",", ereg_replace(" ", "", $exclude_list)));
    }

  while (list(,$v) = each($exclude)) 
    {
       if ($v)
         { $exclude[$v] = 1;  }
    }


  # Forge the real to list, by parsing every item of the $to list
  $recipients = array();

  # Do a first run to convert squads by users 
  $to2 = array();
  $squad_seen_before = array();
  while (list(,$v) = each($to)) 
    {     
      if (ctype_digit($v)) 
	{ $touid = $v; } 
      else 
	{ $touid = user_getid($v); }

      # Squad exists in the exclude array? Skip it
      if (!empty($exclude[$v]))
        { continue; }

      # Already handled?
      if (!empty($squad_seen_before[$v]))
	{ continue; }

      # Record that we handled this already
      $squad_seen_before[$v] = true;

      # If an address is a squad username, push in all the relevant users 
      # uid
      if (!strpos($v, "@")) 
	{
	  if (user_exists($touid, true)) 
            { 
              # Exists in the exclude array? Skip it
	      if (is_array($exclude) && array_key_exists(user_getname($touid), $exclude))
		{ continue; }
	      
              # If we get here, we have a squad and we will store all the
              # squad members uid
	      $result_squad = db_execute("SELECT user_id FROM user_squad WHERE squad_id=?",
					 array($touid));
	      if ($result_squad && db_numrows($result_squad) > 0) 
		{
		  while ($thisuser = db_fetch_array($result_squad))
		    {
		      $to2[] = $thisuser['user_id'];
		    }
		}
	      
	      # No need to go further, this squad was handled
	      continue;
	    }
	}

      # If we get here, it means that we have an address that is not squad
      # related have we keep it for the next run
      $to2[] = $v;      
    }


  # Second run, we should have only real users here, no squads
  $list = array();
  $user_subject = array();
  $user_name  = array();
  $seen_before = array();
  $i = 0;
  while (list(,$v) = each($to2)) 
    {
      if (is_numeric($v)) 
	{ $touid = $v; } 
      else 
	{ $touid = user_getid($v); }

      # User exists in the exclude array? Skip it
      if (!empty($exclude[$v]))
        { continue; }

      # Already handled?
      if (!empty($seen_before[$v]))
	{ continue; }
      
      # Record that we handled this already
      $seen_before[$v] = true;
   
      $i++;
      # If an address is a username, get the email address from
      # the database.
      # If nothing is found, just let the username - there is maybe a 
      # local alias.
      if (!strpos($v, "@")) 
	{
	  if (user_exists($touid)) 
            { 
              # Exists in the exclude array? Skip it
	      if (is_array($exclude) && array_key_exists(user_getname($touid), $exclude))
		{ continue; }

	      $thisuser_email = user_getemail($touid);

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
	      if (user_get_preference("subject_line", $touid) != "")
		{
		  $list[$i] = $v;
		  $user_subject[$v] = sendmail_format_subject_line(user_get_preference("subject_line", $touid), $savane_project, $savane_tracker, $savane_item_id)." ".$subject;
		  $user_name[$v] = user_getrealname($touid, 1)." <".$thisuser_email.">";
		  
		  $seen_before[$thisuser_email] = true;
		  continue;
		}

              # Already handled?
	      if (!empty($seen_before[$thisuser_email]))
		{ continue; }

              # Record that we handled this already
	      $seen_before[$thisuser_email] = true;

	         
	      # Finally, format nicely the entry
	      $v = user_getrealname($touid, 1)." <".$thisuser_email.">"; 
	      
            }
	  else
	    {
              # We have a string without @ that is not a user login?
	      # We assume it could be valid in the mail domain (like a mailing
	      # list)
	      # Usually, this is useless, as functions calling 
	      # sendmail_mail() should have already made checks
	      # (exception: global notifications of trackers)
	      $seen_before[$v] = true;
	      $v = utils_normalize_email($v);	  
	      
              # Already handled?
	      if ($seen_before[$v])
		{ continue; }
	      
	    }
	  
        }
      

      # FIXME: if at some point we will accept entries like
      #  "Dupont Lajoie" <dupont@devnull.net>
      #  we will have to extract "dupont@devnull.net" part and put it
      # in %seen_before

      # Add addresses arrived so far to the list, 
      $recipients[] = $v; 
      
      # Always record the full string. We may have already saved such info
      # before, but we maybe saved strictly the email address, while the 
      # full string may show up once more. If the full string reappears, we
      # wont have to parse it to find the correct email.
      $seen_before[$v] = true;
    } 

  # Add eventually info on the subject
  if ($savane_tracker && $savane_item_id) 
    { 
      $subject = "[".utils_get_tracker_prefix($savane_tracker)." #".$savane_item_id."] ".$subject; 
    }

   # Beuc - 20050316
   # That is what I intended to do:

   # All newlines should be \r\n; this is apparently more
   # RFC821-compliant.
   # $message = preg_replace("/(?<!\r)\n/", "\r\n", $message);

   # However the opposite is certainly more Mailman-compliant; a bug
   # report has been posted to the Mailman team - wait&see [bug #1980]
   $message = str_replace("\r\n", "\n", $message);

   # Send the mail in UTF-8.
   # Normally, nothing non-ASCII should be contained in To: field, apart the 
   # real names.

   # Send the final mail, 
   $ret = '';
   if (count($recipients) > 0)
        {
	  $real_to = sendmail_encode_recipients($recipients);

	  # Normally, $real_to should not contain duplicates
	  if (empty($int_delayspamcheck))
	    {
	      $ret .= mail($real_to, sendmail_encode_header_content($subject), $message, $more_headers);
	      // html_feedback_top() is currently escaping HTML
	      // already, to prevent XSS. So no need to do it again
	      // here:
	      //$r = array_map("htmlspecialchars", $recipients);
	      fb(sprintf(_("Mail sent to %s"), join(', ', $recipients)));
	    }
	  else
	    {
	      # Wait to be checked for spams
	      db_autoexecute('trackers_spamcheck_queue_notification',
                array(
                  'artifact' => $savane_tracker,
		  'item_id' => $savane_item_id,
		  'comment_id' => $savane_comment_id,
		  'to_header' => $real_to,
		  'other_headers' => $more_headers,
		  'subject_header' => sendmail_encode_header_content($subject),
		  'message' => $message
		),
              DB_AUTOQUERY_INSERT);
	    }
	} 

   # Send mails with specific subject line
   while (list(,$v) = each($list)) 
     {
       if (empty($int_delayspamcheck))
	 {
	   $ret .= mail(sendmail_encode_header_content($user_name[$v]), sendmail_encode_header_content($user_subject[$v]), $message, $more_headers);
	   fb(sprintf(_("Mail sent to %s"), utils_email($user_name[$v], 1)));
	 }
       else
	 {
           # Wait to be checked for spams
	   db_autoexecute('trackers_spamcheck_queue_notification',
            array(
              'artifact' => $savane_tracker,
	      'item_id' => $savane_item_id,
	      'comment_id' => $savane_comment_id,
	      'to_header' => sendmail_encode_header_content($user_name[$v]),
	      'other_headers' => $more_headers,
	      'subject_header' => sendmail_encode_header_content($user_subject[$v]),
	      'message' => $message
            ),
            DB_AUTOQUERY_INSERT);
	 }
     }     
      
  return $ret; 
}


# Encode each recipient separately and separate them using commas
function sendmail_encode_recipients ($recipients)
{
  $r = array_map("sendmail_encode_header_content", $recipients);
  return join(', ', $r);
}

# Needed to send utf-8 headers:
# Take a look at http://www.faqs.org/rfcs/rfc2047.html
# We should use mb_encode_mimeheader() but it just does not work.
#
# We must not encode starting and ending quotes.
# We assume there could be only 2 quotes. Otherwise it would be a malformed
# address.
# The easy way we use to do this is to simply consider as one string the 
# content of the quote, if any. If so, we are not working word per word but
# it saves us the time of searching for quotes in every words.
function sendmail_encode_header_content ($header, $charset="UTF-8")
{
  $withquotes = FALSE;
  if (ereg('"', $header)) 
   {
     # quotes found, we each quoted part will be a string to encode
     $words = split('"', $header);
     $withquotes = 1;
   }
  else 
   {
     # otherwise, the default behavior is to consider words as strings to 
     # encode
     $words = split(' ', $header);
   }

  while (list($key,$word) = each($words))
    {
      # Check word per word if they need encoding
      if (!utils_is_ascii($word)) {
         $words[$key] = "=?$charset?B?".base64_encode($word)."?=";
      }
    }
  
  if ($withquotes) 
   {
     return join('"', $words);
   }
  else
   {
     return join(' ', $words);
   }
}


# A form for logged in users to send mails to others users
function sendmail_form_message ($form_action, $user_id) 
{
  global $HTML;
  print $HTML->box_top(sprintf(_("Send a Message to %s"),user_getrealname($user_id)));
  print '<p class="warn">'.("If you are writing for help, did you read the project 
documentation first? Try to provide any potentially useful information you can think of.").'</p>';

  # We do not really bother finding out the realname + email, sendmail_mail()
  # will do it.
  print '
 <form action="'.$form_action.'" method="post">
   <input type="hidden" name="touser" value="'.$user_id.'" />
   <input type="hidden" name="fromuser" value="'.user_getname().'" />

   <span class="preinput">'._("From:").'</span><br />&nbsp;&nbsp;&nbsp;'.user_getrealname(user_getid(), 1).' &lt;'.user_getemail(user_getid()).'&gt;<br />
    <span class="preinput">'._("Mailer:").'</span><br />&nbsp;&nbsp;&nbsp;'.utils_cutstring($_SERVER['HTTP_USER_AGENT'], "50").'<br />
   <span class="preinput">'._("Subject:").'</span><br />&nbsp;&nbsp;&nbsp;<input type="text" name="subject" size="60" maxlength="45" value="" /><br />
   <span class="preinput">'._("Message:").'</span><br />
   &nbsp;&nbsp;&nbsp;<textarea name="body" rows="20" cols="60"></textarea>

   <p align="center"><input type="submit" name="send_mail" value="Send Message" /></p>
</form>';
  print $HTML->box_bottom();
}

function sendmail_format_subject_line ($subject_line, $savane_project="", $savane_tracker="", $savane_item_id="") 
{

  $subject_line = ereg_replace("%SERVER", $GLOBALS['sys_default_domain'], $subject_line);
  $subject_line = ereg_replace("%PROJECT", $savane_project, $subject_line);
  $subject_line = ereg_replace("%TRACKER", $savane_tracker, $subject_line);
  return ereg_replace("%ITEM", "#".$savane_item_id, $subject_line);
}

function sendmail_create_msgid ()
{
  mt_srand((double)microtime()*1000000);
  return date("Ymd-His", time()).".sv".user_getid().".".mt_rand(0,100000)."@".$_SERVER["HTTP_HOST"];
}
