<?php
# Form functions.
#
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
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

require_once(dirname(__FILE__).'/dnsbl.php');
require_once(dirname(__FILE__).'/spam.php');

# To use this form that disallow duplicates:
#    - form_header must be used on the form
#    - form_check must be used before any insert in the db after submission
#    - form_clean must be used after succesful item submission
#

# Start the form with unique ID, store it in the database.
function form_header ($action, $form_id=false, $method="post", $extra=false)
{
  if ($extra)
    $extra = " $extra";

  # Keep previous form id, in case of form that are recreated on failure.
  if (!$form_id)
    {
      mt_srand((double)microtime()*1000000);
      $form_id=md5(mt_rand(0,1000000));
    }
  $result = db_autoexecute('form',
    array('form_id' => $form_id,
          'timestamp' => time(),
          'user_id' => user_getid()),
    DB_AUTOQUERY_INSERT);
  if (db_affected_rows($result) != 1)
    fb(_("System error while creating the form, report it to admins"), 1);

  return '
  <form action="'.htmlentities($action).'" method="'.$method.'"'.$extra.'>'
       .form_input("hidden","form_id",$form_id);
}

# Usual input.
function form_input ($type, $name, $value="", $extra=false)
{
  if ($value != "")
    $value = 'value="'.htmlentities($value).'"';
  if ($extra)
    $extra = " $extra";
  $id_attr = ' id="'.$name.'"';
  if ($type == 'hidden' || $type == 'submit' || $type == 'radio')
    $id_attr = '';

  return "<input type=\"$type\"$id_attr name=\"$name\" $value$extra />";
}

function form_checkbox ($name, $is_checked = 0, $attr = [])
{
  $extra = '';
  if ($is_checked)
    $extra .= ' checked="checked"';
  if (!empty ($attr))
    foreach ($attr as $k => $v)
      $extra .= " $k=\"$v\"";
  $val = '1';
  if (isset ($attr['value']))
    $val = '';
  return form_input ('checkbox', $name, $val, $extra);
}

# Special input: textarea.
function form_textarea ($name, $value="", $extra=false)
{
  if ($extra)
    $extra = " $extra";

  return '
    <textarea id="'.$name.'" name="'.$name.'"'.$extra.'>'.$value.'</textarea>';
}

# Add submit button.
function form_submit (
  $text = false, $submit_name = "update", $extra = false, $skip_trap = false
)
{
  if (!$text)
    $text = _("Submit");

  # Add a trap for spammers: a text input that will have to be kept empty.
  # This won't prevent tailored bots to spam, but that should prevent
  # the rest of them, which is good enough (task #4151).
  # Sure, some bots will someday implement CSS support, but the ones that does
  # not will not disappear as soon as this happen.
  $trap = '';
  if (!$skip_trap && empty($GLOBALS['int_trapisset']) && !user_isloggedin())
    {
      $trap = " " . form_input ("text", "website", "http://");
      $GLOBALS['int_trapisset'] = true;
    }
  return form_input ("submit", $submit_name, $text, $extra) . $trap;
}

# Close the form, with submit button.
function form_footer ($text=false, $submit_name="update")
{
  return '
    <div class="center">
      '.form_submit($text, $submit_name).'
    </div>
  </form>';
}

# Check whether this is a duplicate or not: return true if the form
# is ok.
# Exit if we found sql wildcards: forged form, probably.
# We do need this extra check for anynomous users. Logged in users can forge
# their id and remove all the form id of their user, if they wish. Its their
# problem.
#
# form_check is also used as a cross-side request forgery (CSRF) protection.
function form_check ($form_id)
{
  if (!empty($GLOBALS['sys_debug_noformcheck']))
    return 1;
  # First, check for spambots (will kill the session if necessary).
  form_check_nobot();

  if (user_getid() == 0
      && (!preg_match('/^[a-f0-9]*$/', $form_id)))
    {
      fb(_("Unrecognized unique form_id"), 1);
      return 0;
    }

  # See bug #6983.
  # We must clean the form id right now. This is not how the form id mechanism
  # was designed.
  #
  # Originally, form id was supposed to be deleted only when we are sure
  # that the form was posted.
  # However, since apache & all are multithreaded, you can end up with the
  # case that the delay between the initial check and the end of the form
  # is long enough to make possible a duplicate.
  #
  # Now, the check will remove the id. If the remove fail, it means that
  # the form id no longer exists and then we exit. We will have only one
  # SQL request, reducing as much as possible delays.
  $success = db_affected_rows(db_execute("DELETE FROM form WHERE user_id=?
                                          AND form_id=?",
                                         array(user_getid(), $form_id)));
  if (!$success)
    {
      fb(_("Duplicate Post: this form was already submitted."),1);
      return 0;
    }

  # Always do a dnsbl check when such form is sent
  # (it will kill the submission if necessary).
  dnsbl_check();

  return 1;
}

# Remove form_id from database: the item was posted.
function form_clean ($form_id)
{
  # Form_id are now directly removed by form_check.
  return 1;
}

# Check whether the trap field has been filled. If so, refuse the post.
# This test should probably be made before remove form id, to be
# dumbuser-compliant.
function form_check_nobot ()
{
  extract(sane_import('request', ['pass' => 'website']));
  if ($website != "" && $website != "http://")
    {
      # Not much explanation on the reject, since we are hunting spammers.
      exit_log("filled the spam trap special field");
      exit_missing_param();
    }
  return 1;
}
?>
