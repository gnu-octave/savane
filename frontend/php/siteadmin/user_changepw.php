<?php
# Change user's password.
#
# This file is part of the Savane project
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2017, 2018, 2022 Ineiev
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n($string)
{
  return $string;
}

require_once('../include/init.php');
require_once('../include/account.php');
session_require(array('group'=>'1','admin_flags'=>'A'));

extract (sane_import ('request', ['digits' => 'user_id']));
extract (sane_import ('post',
  ['true' => 'update', 'pass' => ['form_pw', 'form_pw2']]
));

$error = '';

# Check for valid register from form post.
function register_valid ()
{
  global $update, $user_id, $error;

  if (!$update)
    return 0;
# Check against old password.
  db_execute("SELECT user_pw FROM user WHERE user_id=?", array($user_id));

  if (!$GLOBALS['form_pw'])
    {
      $error = no_i18n ('no password provided');
      return 0;
    }
  if ($GLOBALS['form_pw'] != $GLOBALS['form_pw2'])
    {
      $error = no_i18n ('passwords don\'t match');
      return 0;
    }
  if (!account_pwvalid($GLOBALS['form_pw']))
    {
      $error = no_i18n ('provided password is considered weak');
      return 0;
    }

  # If we got this far, it must be good.
  db_autoexecute('user', array('user_pw' =>
                 account_encryptpw($GLOBALS['form_pw'])),
                 DB_AUTOQUERY_UPDATE, "user_id=?", array($user_id));
  return 1;
}

$title = sprintf (no_i18n ('Change Password for %s'), user_getname ($user_id));

$HTML->header(['title' => $title]);
# Check for valid login, if so, congratulate.
if (register_valid())
  {
    print '
<p><strong>'.no_i18n('Savannah Change Confirmation').'</strong></p>
<p>'.no_i18n('Congratulations. You have managed to change this user\'s
password.').'
</p>
<p>'.sprintf(no_i18n('You should now <a href="%s">Return to UserList</a>.'),
'/admin/userlist.php')."</p>\n";

  }
else
  {
    if ($error)
      $error = '<p><b>' . no_i18n ('Password change failed')
        . ": $error</b></p>\n";

    print "<h2>" . no_i18n ('Savannah Password Change')
      . "</h2>\n$error<form action='user_changepw.php' method='post'>\n"
      . '<p>' . no_i18n ('New Password:')
      . "<br />\n<input type='password' name='form_pw' />\n</p>\n<p>"
      . no_i18n ('New Password (repeat):')
      . "<br />\n<input type='password' name='form_pw2' />\n"
      . "<input type='hidden' name='user_id' value='$user_id'>\n</p>\n"
      . '<p><input type="submit" name="update" value=\''
      . no_i18n ('Update') . "' />\n</p>\n</form>\n";
  }
$HTML->footer(array());
?>
