<?php
# Handle SSH keys.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
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

require_once('../../include/init.php');
require_once('../../include/account.php');
require_once('../../include/sendmail.php');
session_require(array('isloggedin' => 1));

extract (sane_import ('post',
  [
    'true' => 'update',
    'hash' => 'form_id',
    'array' =>
      [
        ['form_authorized_keys', ['digits', 'no_quotes']]
      ]
  ]
));

$key_limit = 25; # Maximum key number to register.
$min_keys = 5; # Minumum key fields to show.
$key_separator = "###";

if ($update)
  {
    if (!form_check($form_id))
      exit_error(_("Exiting"));

    $keys = '';
    # Build the key string.
    for ($i = 0; $i < $key_limit; $i++)
      {
        if (!isset($form_authorized_keys[$i]))
          continue;
        $thiskey = $form_authorized_keys[$i];
        # Remove useless blank spaces.
        $thiskey = trim($thiskey);
        # Remove line breaks.
        $thiskey = str_replace("\n", "", $thiskey);
        if ($thiskey != '')
          {
            # Test the key with ssh-vulnkey.
            $descriptorspec = array(
              0 => array('pipe', 'r'),
              1 => array('pipe', 'w'),
              2 => array('file', '/dev/null', 'a'),
            );
            $process = proc_open('ssh-vulnkey -', $descriptorspec, $pipes);

            $return_value = 1;
            if (is_resource($process))
              {
                fwrite($pipes[0], $thiskey);
                fclose($pipes[0]);
                stream_get_contents($pipes[1]); // empty pipe
                fclose($pipes[1]);
                $return_value = proc_close($process);
              }
            if ($return_value != 0)
              {
                fb(sprintf(_("Key #%s seen"), $i+1));
                $keys .= $thiskey . $key_separator;
              }
            else
              fb(sprintf(
# TRANSLATORS: the argument is a link to a page.
_('Error: ssh-vulnkey detected key #%s as compromised.
Please upgrade your system and regenerate it
(see %s for more information).'), $i + 1, '//wiki.debian.org/SSLkeys'),
                 1);
          }
      }
    # Grab original keys from the database for comparison.
    $res_orig_keys = db_execute("SELECT authorized_keys "
                                ."FROM user WHERE user_id = ?",
                                array(user_getid()));
    $row_orig_keys = db_fetch_array($res_orig_keys);
    $orig_keys = $row_orig_keys['authorized_keys'];
    $new_keys = array_diff (
      explode ($key_separator, $keys), explode ($key_separator, $orig_keys)
    );

    if (count ($new_keys))
      {
        $user_id = user_getid();
        $message = sprintf(
# TRANSLATORS: the argument is site name (like Savannah).
_('Someone, presumably you, has changed your SSH keys on %s.
If it wasn\'t you, maybe someone is trying to compromise your account...'),
                           $GLOBALS['sys_name'])."\n\n";

        $message .=
          sprintf(
            _('The request came from %s
(IP: %s, port: %s, user agent: %s)') . "\n\n",
            gethostbyaddr ($_SERVER['REMOTE_ADDR']),
            $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'],
            $_SERVER['HTTP_USER_AGENT']
          );

# TRANSLATORS: the argument is site name (like Savannah).
        $message .= sprintf(_("-- the %s team."),$GLOBALS['sys_name'])."\n";

        sendmail_mail($GLOBALS['sys_mail_replyto']."@".$GLOBALS['sys_mail_domain'],
                      user_get_email($user_id),
                      $GLOBALS['sys_name'] .' '
                      ._("SSH key changed on your account"),
                      $message);
      }
    $success =
      db_execute (
        "UPDATE user SET authorized_keys = ? WHERE user_id = ?",
        [$keys, user_getid()]
      );
    if ($success)
      {
        if ($keys == '')
          fb(_("No key is registered"));
        else
          fb(_("Keys registered"));
      }
    else
      fb(_("Error while registering keys"), 1);
  }
else # !$update
  {
    # Grab keys from the database.
    $res_keys = db_execute("SELECT authorized_keys FROM user WHERE user_id = ?",
                           array(user_getid()));
    $row_keys = db_fetch_array($res_keys);
    $keys = $row_keys['authorized_keys'];
  }

$form_authorized_keys =  explode ($key_separator, $keys);

# Not valid registration, or first time to page.
site_user_header(array('title' => _("Change Authorized Keys"),
                       'context' => 'account'));
print form_header($_SERVER['PHP_SELF'], false, "post");
print '<h2>' . _("Authorized keys") . "</h2>\n";
utils_get_content("account/editsshkeys");
print '<p>'
 . _("Fill the text fields below with the public keys for each key you want to
register. After submitting, verify that the number of keys registered is what
you expected.") . "</p>\n";

$n = count ($form_authorized_keys);
if ($n < $min_keys)
  $n = $min_keys;
if ($n > $key_limit)
  $n = $key_limit;

for ($i = 0; $i < $n; $i++)
  {
    $thiskey = '';
    if (isset ($form_authorized_keys[$i]))
      $thiskey = $form_authorized_keys[$i];
    print "<span class=\"preinput\"><label for=\"form_authorized_keys[$i]\">";
    printf (_("Key #%s:"), $i + 1);
    print "</label></span>\n<input type='text' size='60' "
      . "id='form_authorized_keys[$i]' name='form_authorized_keys[$i]'\n"
      . "      value='$thiskey' /><br />\n";
  }
print "<br />\n" . form_footer (_("Update"));
site_user_footer(array());
?>
