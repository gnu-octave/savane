<?php
# Add and edit project mailing lists
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2006  BBN Technologies Corp
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

## Note about status of list:
##   - Status 0: list is deleted (ie, does not exist).
##   - Status 1: list is marked for creation.
##   - Status 2: list is marked for reconfiguration.
##   - Status 5: list has been created (ie, it exists).
##
##   This frontend php script sets status to:
##      0 if user deletes a list before the backend ever actually created it.
##      1 if user adds a list
##      2 if user reconfigures an _existing_ list (ie, status was 5)
##
##   The backend sv_mailman.pl script sets status to:
##      0 when a list is actually deleted
##      5 when a list is actually created
##
##   - when we create an alias, which mean someone was able, according to
##     group type restriction to add to his project a list that was already
##     inside the database, we add the list inside the database with a status
##     of 5, so sv_mailman does not try to recreate it.
##     In the worse case, if two persons creates the same list at the same

##   The field password will not contact real password, it will contain
##   '1' when the backend is supposed to reset it.

define('LIST_STATUS_DELETED', 0);
define('LIST_STATUS_NEED_CREATION', 1);
define('LIST_STATUS_NEED_RECONFIGURATION', 2);
define('LIST_STATUS_CREATED', 5);

require_once('../../include/init.php');
require_once('../../include/account.php');

extract(sane_import('post',
  array(
    'post_changes',
    'list_name', 'description', 'is_public', 'reset_password',
    'newlist_format_index',
)));

if (!$group_id)
  exit_no_group();

if (!member_check(0, $group_id))
  exit_permission_denied();

exit_test_usesmail($group_id);

$grp = project_get_object($group_id);

# Check first if the group type set up is acceptable. Otherwise, the form
# will probably be puzzling to the user (ex: no input text for the list
# name).

$ml_address =
  $grp->getTypeMailingListAddress($grp->getTypeMailingListFormat("testname"));

if (!$ml_address || $ml_address == "@")
  exit_error(_("Mailing lists are misconfigured. Post a support request to ask
your site administrator to review group type setup."));

if ($post_changes)
  {
    foreach ($list_name as $id => $ignored)
      {
        if ($id == 'new')
          {
            # Add a new list.
            # Need account-related functions.
            if (!isset($newlist_format_index) && !isset($list_name['new']))
              # User didn't fill the form.
              continue;
            if (!isset($newlist_format_index) && isset($list_name['new']))
              # When there's only a single choice, there's no format index.
              $newlist_format_index = 0;

            # Generates a password.
            $new_list_password = substr(md5(time() . rand(0,40000)),0,16);

            # Name shorter than two characters are not acceptable (only
            # check if the chosen format requires %NAME substitution).
            if (strpos($grp->getTypeMailingListFormat("%NAME", $newlist_format_index),
                       "%NAME") !== false
                && (!$list_name['new'] || strlen($list_name['new']) < 2))
              {
                if (strlen($list_name['new']) > 0)
                  fb(sprintf(
# TRANSLATORS: the argument is the new mailing list name entered by the user.
_("You must provide list name that is two or more characters long: %s"),
                             $list_name['new']), 1);
                continue;
              }
            # Site may have a strict policy on list names: checks now.
            $new_list_name =
              $grp->getTypeMailingListFormat(strtolower($list_name['new']),
                                                        $newlist_format_index);
            # Check if it is a valid name.
            if (!account_namevalid($new_list_name, 1, 1, 1, 80))
              {
                fb(sprintf(
# TRANSLATORS: the argument is the new mailing list name entered by the user.
                           _("Invalid list name: %s"), $new_list_name), 1);
                continue;
              }
            # Check on the list_name: must not be equal to a user account,
            # otherwise it can mess up the mail develivery for the list/user.
            if (db_numrows(db_execute("SELECT user_id FROM user WHERE "
                                      . "user_name LIKE ?",
                                      array($new_list_name))) > 0)
              {
                fb(sprintf(
_("List name %s is reserved to avoid conflicts with user accounts."),
                           $new_list_name), 1);
                continue;
              }
            # Check if the list does not exists already.
            $result = db_execute("SELECT group_id FROM mail_group_list "
                                 ."WHERE lower(list_name)=?",
                                 array($new_list_name));
            if (db_numrows($result) > 0)
              {
                $row = db_fetch_array($result);
                if ($row['group_id'] != $group_id)
                  {
                    # If the list exists already, we create an alias
                    # (same name but attached to a different project),
                    # assuming that group type configuration is well-done
                    # and disallow list name to persons not supposed to
                    # use some names.
                    fb(sprintf(
_("List %s is already in the database. We will create an alias."),
                               $new_list_name));
                    $status = LIST_STATUS_CREATED;
                  }
                else
                  {
                    fb(sprintf(_("The list %s already exists."), $new_list_name), 1);
                    continue;
                  }
              }
            else # !(db_numrows($result) > 0)
              $status = LIST_STATUS_NEED_CREATION;
            $result = db_autoexecute('mail_group_list',
                                     array(
                                           'group_id' => $group_id,
                                           'list_name' => $new_list_name,
                                           'is_public' => $is_public['new'],
                                           'password' => $new_list_password,
                                           'list_admin' => user_getid(),
                                           'status' => $status,
                                           'description'
                                       => htmlspecialchars($description['new']),
                                           ), DB_AUTOQUERY_INSERT);

            if (!$result)
              fb(_("Error Adding List"),1);
            else
              fb(_("List Added"));
            continue;
          } # if ($id == 'new')

        # Update.
        # Not a valid list id? Skip it, it was obviously not on the form.
        if (!is_numeric($id))
          continue;

        # Now get the current database data for this list
        # (yes, it means one SQL SELECT per list, but we dont expect to
        # have project with 200 lists so it should scale).
        $res_status = db_execute("SELECT * FROM mail_group_list " .
                                 "WHERE group_list_id=? AND group_id=?",
                                 array($id, $group_id));
        $num = db_numrows($res_status);
        if (!$num)
          {
            fb(sprintf(_("List %s not found in the database"), $list_name[$id]),
               1);
            continue;
          }
        $row_status = db_fetch_array($res_status);

        # Armando L. Caro, Jr. <acaro--at--bbn--dot--com> 2/23/06
        # Change the status based on what status is in mysql and what
        # is_public is being set to. We need to account for when
        # multiple changes are entered into mysql before the backend
        # has the opportunity to act on them.
        switch(intval($row_status['status']))
          {
            # Status of 0 or 1, means the mailing list doesnt
            # exist. So signal to backend to create as long as
            # is_public is not set to "deleted" (ie, 9).
          case LIST_STATUS_DELETED:
          case LIST_STATUS_NEED_CREATION:
            if ($is_public[$id] != 9)
              $status = LIST_STATUS_NEED_CREATION;
            else
              $status = LIST_STATUS_DELETED;
            break;

            # Status of 2 or 5, means the mailing list does exist,
            # and user is making a change. The change has to be
            # signaled to backend no matter what.
          case LIST_STATUS_NEED_RECONFIGURATION:
          case LIST_STATUS_CREATED:
            $status = LIST_STATUS_NEED_RECONFIGURATION;
            break;
          }

        if (empty($reset_password[$id]))
          $reset_password[$id] = '';
        # We need an update only if there is at least one change.
        util_debug("{$list_name[$id]}: $status == {$row_status['status']}");
        if ($description[$id] == $row_status['description']
            && $is_public[$id] == $row_status['is_public']
            && (($reset_password[$id] == $row_status['password'])
                || ($row_status['password'] != 1 && empty($reset_password[$id]))))
          continue;

        $result = db_autoexecute('mail_group_list',
          array(
            'status' => $status,
            'description' => $description[$id],
            'is_public' => $is_public[$id],
            'password' => $reset_password[$id],
            ), DB_AUTOQUERY_UPDATE,
          // list_id is enough, but group_id prevents users from
          // modifying other people's lists:
          "group_list_id=? AND group_id=?",
          array($id, $group_id));

        if (!$result)
          fb(sprintf(
# TRANSLATORS: the argument is list name.
                     _("Error updating list %s"), $list_name[$id]), 1);
        else
          fb(sprintf(
# TRANSLATORS: the argument is list name.
                     _("List %s updated"), $list_name[$id]));
      } # foreach ($list_name as $id => $ignored)
  }

$result = db_execute("SELECT list_name,group_list_id,is_public,"
                             ."description,password,status "
                     ."FROM mail_group_list "
                     ."WHERE group_id=? ORDER BY list_name ASC",
                     array($group_id));

# Show the form to modify lists status.
site_project_header(array('title'=>_("Update Mailing List"),
                          'group'=>$group_id,'context'=>'amail'));

print '<p>';
print _("You can administer list information from here. Please note that
private lists are only displayed for members of your project, but not for
visitors who are not logged in.")."<br />\n";
print "</p>\n";

# Start form
print form_header($_SERVER['PHP_SELF']);
print form_input("hidden", "post_changes", "y");
print form_input("hidden", "group_id", $group_id);

while ($row = db_fetch_array($result))
  {
    $id = $row['group_list_id'];
    print '<h4>'.$row['list_name']."</h4>\n";

    print '<span class="preinput">'._("Description:").'</span>';
    print '<br />&nbsp;&nbsp;&nbsp;'
          .form_input("text", "description[$id]", $row['description'],
                      'maxlenght="120" size="50"');

# Status: private or public list, or planned for deletion.
# It may be weird to have the last one here, but that is how things
# are in the database and it is simpler to follow the same idea.
    print '<br /><span class="preinput">'._("Status:").'</span>';
    $checked = '';
    if ($row['is_public'] == "1")
      $checked = ' checked="checked"';
    print '<br />&nbsp;&nbsp;&nbsp;'
          .form_input("radio", "is_public[$id]", '1', $checked).' '
          ._("Public List");

    $checked = '';
    if ($row['is_public'] == "0")
      $checked = ' checked="checked"';
    print '<br />&nbsp;&nbsp;&nbsp;'
          .form_input("radio", "is_public[$id]", '0', $checked).' '
          ._("Private List (not advertised, subscribing requires approval)");

    $checked = '';
    if ($row['is_public'] == "9")
      $checked = ' checked="checked"';
    print '<br />&nbsp;&nbsp;&nbsp;'
          .form_input("radio", "is_public[$id]", '9', $checked).' '
          ._("To be deleted (this cannot be undone!)");

# At this point we have no way to know if the backend brigde to
# mailman is used or not. We will propose the password change only
# if the list is marked as created.
# Do not heavily check this, just skip this in the form.
    if ($row['status'] == LIST_STATUS_CREATED
        || $row['status'] == LIST_STATUS_NEED_RECONFIGURATION)
      {
        print '<br /><span class="preinput">'
              ._("Reset List Admin Password:").'</span>';
        $checked = '';
        if ($row['password'] == "1")
          $checked = ' checked="checked"';
        print '<br />&nbsp;&nbsp;&nbsp;'
              .form_input("checkbox", "reset_password[$id]", "1", $checked).' '
# TRANSLATORS: this string relates to the previous, it means
# [checkbox] "request resetting admin password".
._("Requested - <em> this will have no effect if this list is not managed by
Mailman via Savane</em>");
      }
    else
      print form_input("hidden", "reset_password[$id]", $row['password']);
    print form_input("hidden", "list_name[$id]", $row['list_name']);
  } # while ($row = db_fetch_array($result))

# New list form.
print "<br /><br />\n";
utils_get_content("mail/about_list_creation");

print '
<p>
<input type="hidden" name="post_changes" value="y" />
<input type="hidden" name="group_id" value="'.$group_id.'" />
</p>
<h3>'._('Create a new mailing list:').'</h3> ';

$project_list_format  = $grp->getTypeMailingListFormat();
$project_list_formats = split(',', $project_list_format);

$i = 0;
foreach ($project_list_formats as $format)
  {
    if (count($project_list_formats) > 1)
      print "<input type='radio' name='newlist_format_index' value='$i'> ";
    $input = str_replace('%NAME',
                         '<input type="text" name="list_name[new]" '
                         .'value="" size="25" maxlenght="70" />',
                         $format);
    print $grp->getTypeMailingListAddress($input);
    print '<br />';
    $i++;
  }

print '<p>';
print                        _('Is Public? (visible to non-members)')
.'<br />
  <input type="radio" name="is_public[new]" value="1" checked> yes<br />
  <input type="radio" name="is_public[new]" value="0"> no<p></p>
  <strong>'._('Description:').'</strong><br />
  <input type="text" name="description[new]" value="" size="40" maxlength="80">
  <br />';

print '<br /><br /></p>
'.form_footer();
site_project_footer(array());
