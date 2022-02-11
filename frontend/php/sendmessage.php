<?php
# Send message to given user via Savane
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
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

require_once('include/init.php');
require_once('include/sendmail.php');

extract(sane_import('request',
  array('touser', 'fromuser', 'send_mail', 'subject', 'body', 'feedback')));

if (!user_isloggedin())
  exit_not_logged_in();

if ($touser)
  {
    # Search infos in the database about the user
    $result=db_execute("SELECT email,user_name FROM user
                        WHERE user_id=? AND (status='A' OR status='SQD')",
                       array($touser));
    if (!$result || db_numrows($result) < 1)
      # TRANSLATORS: the argument is user id (a number).
      exit_error(sprintf(_('User %s does not exist'), $touser));
  }
else
  {
    exit_missing_param();
  }

if ($send_mail)
  {
    if (!$subject || !$body || !$fromuser)
      {
        # Force them to enter all vars
        exit_missing_param();
      }
    else
      {
        # Let sendmail_mail() figure out real email addresses.
        sendmail_mail($fromuser, $touser, $subject, $body);
        $HTML->header(array('title'=>_('Message Sent')));
        print html_feedback_top();
        $HTML->footer(array());
        exit;
      }
  }
else
  {
    $HTML->header(array('title'=>_('Send a message')));
    sendmail_form_message(htmlentities ($_SERVER["PHP_SELF"]), $touser);
    $HTML->footer(array());
  }
?>
