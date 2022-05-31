<?php
# News approval, with or without superadmin privs
# Copyright (C) 1999-2000  The SourceForge Crew
# Copyright (C) 2002-2006  Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2018  Ineiev
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

extract (sane_import ('all',
  [
    'digits' => ['id', 'status', 'for_group_id'],
    'hash' => 'form_id',
    'true' => ['update', 'post_changes', 'approve'],
    'specialchars' => ['summary', 'details'],
  ]
));

// This page can be used to manage the whole news system for a server
// or news for a project.
// That's why, when required, we test if group_id = sys_group_id.

if (!($group_id && member_check(0, $group_id, 'N3')))
  exit_error(_("Action unavailable: only news managers can approve news."));

// Modifications are made to the database
// 0 = locally approved
// 1 = front page approved
if ($post_changes && $approve)
  {
    if ($group_id != $GLOBALS['sys_group_id'] && $status != 0 && $status != 4)
      {
        # Make sure that an item accepted for front page is not modified.
        $status=0;
      }

    $result = false;
    if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
      {
        $fields = array('is_approved' => $status,
                        'date' => time(),
                        'date_last_edit' => time(),
                        'summary' => $summary,
                        'details' => $details);
        $result = db_autoexecute('news_bytes', $fields, DB_AUTOQUERY_UPDATE,
                                 "id=? AND group_id=?",
                                 array($id, $for_group_id));

      }
    elseif ($status == 0 || $status == 4)
      {
        $fields = array('is_approved' => $status,
                        'date_last_edit' => time(),
                        'summary' => $summary,
                        'details' => $details);
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
    # Send mails: does not care if it was already approved.
    if (($status == 0 && $group_id != $GLOBALS['sys_group_id'])
        || ($status == 1 && user_is_super_user()
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
         if (db_numrows($res) > 0)
           {
             $from = user_getrealname(db_result($res, 0, 'submitted_by'),1)
                       .' <'.$GLOBALS['sys_mail_replyto'].'@'
                       .$GLOBALS['sys_mail_domain'].'>';
             sendmail_mail($from, $to, $summary, $details, $group, 'news');
           }
      }
    # Show the list_queue.
    $approve='';
    $list_queue='y';
  }

site_project_header (
  ['title' => _("Manage"), 'group' => $group_id, 'context' => 'news']
);

# Form to make modifications to an existing item, to submit one.
if ($approve)
  {
    if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
      {
        $result = db_execute ("
          SELECT
            groups.unix_group_name, news_bytes.*,
            news_bytes.submitted_by AS submitted_by
          FROM news_bytes,groups
          WHERE id=?  AND news_bytes.group_id=groups.group_id",
          [$id]
        );
      }
    else
      {
        $result = db_execute ("
          SELECT *,news_bytes.submitted_by AS submitted_by
          FROM news_bytes
          WHERE id=? AND group_id=?",
          [$id, $group_id]
        );
      }

    if (db_numrows($result) < 1)
      {
        print '<h1 class="error">' . _("No pending news") . "</h1>\n";
        site_project_footer(array());
        exit;
      }

    if ($group_id == $GLOBALS['sys_group_id'] && !user_is_super_user())
      {
        print '<p class="warn">'
. _("If you want to approve/edit site news (shown on the front page), you must
be logged as superuser.") . "</p>\n";
      }
    elseif ($group_id == $GLOBALS['sys_group_id'] && user_is_super_user())
      {
        print '<p class="warn">'
._("If you want to approve/edit news for the local administration project (not
shown on the front page), you must end the superuser session.").'</p>
';
      }

    $s_by_res = db_result ($result, 0, 'submitted_by');
    $submitted_by = "None";
    if (db_result ($result, 0, 'submitted_by'))
      $submitted_by = user_getname ($s_by_res);


    print '<p>' . _("Submitter:") . ' '
     . utils_user_link ($submitted_by, user_getrealname ($s_by_res))
     . "</p>\n";
    print '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
      . "\" method=\"post\">\n"
      . '<input type="hidden" name="id" value="'
      . db_result ($result, 0, 'id') . "\" />\n";

    print "<input type='hidden' name='approve' value='y' />\n";
    print "<input type='hidden' name='post_changes' value='y' />\n";

    if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
      {
        print "<input type='radio' name='status' id='status_admin' "
          . "value='1'\n/>&nbsp;&nbsp;";
        print '<span class="preinput"><label for="status_admin">';
        printf (
# TRANSLATORS: the argument is site name (like Savannah).
          _("Approve For %s' Front Page"), $GLOBALS['sys_name']
        );
        print "</label></span><br />\n";
        print "<input type='radio' id='status_do_nothing' name='status' "
          . "value='0' checked='checked'\n"
          . "/>&nbsp;&nbsp;<span class='preinput'><label "
          . "for='status_do_nothing'>" . _("Do Nothing") . "</label></span>"
          . "<br />\n"
          . "<input type='radio' name='status' id='status_refuse' value='2'\n"
          . "/>&nbsp;&nbsp;<span class='preinput'><label for='status_refuse'>"
          . _("Refuse") . "</label></span><br />\n"
          . "<input type='hidden' name='for_group_id' value='"
          . db_result ($result, 0, 'group_id') . "' />\n"
          . "<input type='hidden' name='group_id' "
          . "value='{$GLOBALS['sys_group_id']}' />\n";
      }
    else
      {
        print '<input type="radio" name="status" id="status_display" '
          . "value='0' checked='checked' />\n"
          . '&nbsp;&nbsp;<span class="preinput"><label for="status_display">'
          . _("Display") . "</label></span><br />\n"
          . "<input type='radio' name='status' id='status_delete' "
          . "value='4' />\n"
          . '&nbsp;&nbsp;<span class="preinput"><label for="status_delete">'
          . _("Delete") . "</label></span><br />\n"
          . '<input type="hidden" name="group_id" value="'
          . db_result ($result, 0, 'group_id') . "\" />\n";
      }

    print "<br />\n<span class='preinput'><label for='summary'>"
      . _("Subject:") . "</label></span><br />\n&nbsp;&nbsp;\n"
      . '<input type="text" name="summary" id="summary" value="'
      . db_result ($result, 0, 'summary') . '" size="65" maxlength="80" />'
      . "<br />\n"
      . '<span class="preinput"><label for="details">'
      . _("Details"). '</label> '. markup_info ("full")
      . "</span><br />\n&nbsp;&nbsp;\n"
      . '<textarea name="details" id="details" rows="20" cols="65" wrap="soft">'
      . db_result($result, 0, 'details') . "</textarea>\n";
    print '<p>';
    printf (
# TRANSLATORS: the argument is site name (like Savannah).
      _("Note: If this item is on the %s home page and you edit it, it will be
removed from the home page."),
      $GLOBALS['sys_name']
    );
    print "</p>\n<div class='center'>"
      . '<input type="submit" name="submit" value="'
      . _("Submit") . "\" /></div>\n</form>\n";
    print '<h2>' . _("Preview:") . "</h2>\n"
      . markup_full (db_result ($result, 0, 'details'));
  }
else # ! $approve
  {
    # No item selected.
    if ($group_id == $GLOBALS['sys_group_id'] && !user_is_super_user())
      {
        print '<p class="warn">'
. _("If you want to approve/edit site news (shown on the front page), you must
be logged as superuser.") . "</p>\n";
      }
    elseif ($group_id == $GLOBALS['sys_group_id'] && user_is_super_user())
      {
        print '<p class="warn">'
. _("If you want to approve/edit news for the local administration project (not
shown on the front page), you must end the superuser session.") . "</p>\n";
      }

    $old_date=(time()-(86400*15));

    # Firstly, we show item that requires approval.
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
        print '<h2>' . _("No queued items found") . "</h2>\n";
      }
    else
      {
        print '<h2>' . _("These news items were submitted and need approval")
          . "</h2>\n<ul>\n";

        for ($i=0; $i<$rows; $i++)
          {
            print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id'])
              print ' class="boxhighlight"';
            print '><a href="'.htmlentities ($_SERVER['PHP_SELF'])
                  .'?approve=1&amp;id='.db_result($result,$i,'id');

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
            print db_result ($result, $i, 'summary') . "</a></li>\n";
          }
          print "</ul>\n";
      }
    # Secondly, we show deleted items for this week.
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
        print '<h2>'
          . _("No deleted items during these past two weeks") . "</h2>\n";
      }
    else
      {
        if (user_is_super_user() && $group_id == $GLOBALS['sys_group_id'])
          {
            print '<h2>'
              ._("These items were refused these past two weeks:") . "</h2\n";
          }
        else
          {
            print '<h2>'
              ._("These items were deleted these past two weeks:") . "</h2>\n";
          }
        print "<ul>\n";
        for ($i = 0; $i < $rows; $i++)
          {
            print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id'])
              print ' class="boxhighlight"';
            print '><a href="' . htmlentities ($_SERVER['PHP_SELF'])
              . "?approve=1&amp;group=$group&amp;id="
              . db_result($result,$i,'id') . '">';

            if ($group_id == $GLOBALS['sys_group_id'])
              print group_getname(db_result($result,$i,'group_id')).' - ';
            print db_result ($result, $i, 'summary') . "</a></li>\n";
          }
        print "</ul>\n";
      } # $rows >= 1

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
        print '<h2>' . _("No news items approved"). "</h2>\n";
      }
    else
      {
        print '<h2>' . _("These items were approved:") . "</h2>\n<ul>\n";

        for ($i = 0; $i < $rows; $i++)
          {
            print '<li';
            if (db_result($result,$i,'group_id') == $GLOBALS['sys_group_id'])
              print ' class="boxhighlight"';
            print '><a href="' . htmlentities ($_SERVER['PHP_SELF'])
              . "?approve=1&amp;group=$group&amp;id="
              . db_result ($result, $i, 'id') . '">';

              if ($group_id == $GLOBALS['sys_group_id'])
                print group_getname(db_result($result,$i,'group_id')).' - ';
              print db_result ($result, $i, 'summary') . "</a></li>\n";
          }
        print "</ul>\n";
      } # $rows >= 1
  }
site_project_footer(array());
?>
