<?php
# <one line to give a brief idea of what this does.>
# 
# Copyright 1999-2000 (c) The SourceForge Crew
# Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
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
session_require(array('isloggedin' => 1));

extract(sane_import('post', array('update', 'keys', 'form_authorized_keys', 'form_id')));

if ($update)
{
  if (!form_check($form_id))
    { exit_error(_("Exiting")); }

  $keys = '';
  # Build the key string
  # Key limit is set to 25
  for ($i = 0; $i < 25; $i++)
    {
      if (!isset($form_authorized_keys[$i]))
	continue;
      $thiskey = $form_authorized_keys[$i];
      # Remove useless blank spaces
      $thiskey = trim($thiskey);
      # Remove line breaks
      $thiskey = str_replace("\n", " ", $thiskey);
      if ($thiskey != '')
	{
	  # test the key with ssh-vulnkey
	  $descriptorspec = array(
	    0 => array('pipe', 'r'),
	    1 => array('pipe', 'w'),
	    2 => array('file', '/dev/null', 'a'),
	  );
	  $process = proc_open('ssh-vulnkey -', $descriptorspec, $pipes);

	  $return_value = 1;
	  if (is_resource($process)) {
	    fwrite($pipes[0], $thiskey);
	    fclose($pipes[0]);
	    stream_get_contents($pipes[1]); // empty pipe
	    fclose($pipes[1]);
	    $return_value = proc_close($process);
	  }
	  if ($return_value != 0)
	    {
	      fb(sprintf(_("Key #%s seen"), $i+1));
	      $keys .= $thiskey."###";
	    }
	  else
	    {
	      fb(sprintf(_("Error: ssh-vulnkey detected key #%s as compromised."
			   . " Please upgrade your system and regenerate it"
			   . " (see <a href='http://wiki.debian.org/SSLkeys'>http://wiki.debian.org/SSLkeys</a>"
			   . " for more information)."), $i+1), 1);
	    }
	}
    }

  # Update the database
  $success = 0;
  if ($keys != '')
    $success = db_execute("UPDATE user SET authorized_keys = ? WHERE user_id = ?",
			  array($keys, user_getid()));
  
  if ($success)
    { fb(_("Keys registered")); }
  else
    { fb(_("Error while registering keys"), 1); }
}
else
{
  # Grab keys from the database
  $res_keys = db_execute("SELECT authorized_keys FROM user WHERE user_id = ?",
			 array(user_getid()));
  $row_keys = db_fetch_array($res_keys);

  $form_authorized_keys = split("###", $row_keys['authorized_keys'], 25);
}


# not valid registration, or first time to page
site_user_header(array('title' => _("Change Authorized Keys"),
		       'context' => 'account'));


# we get site-specific content
utils_get_content("account/editsshkeys");


print form_header($_SERVER['PHP_SELF'], false, "post");

print '<h3>'._("Authorized keys:").'</h3>';

print '<p>'._("Fill the text fields below with the public keys for each key you want to register. After submitting, verify that the number of keys registered is what you expected.").'</p>';

# Key limit is set to 25
# By default, show only 5 fields
$i = 0;
while($i < count($form_authorized_keys) or $i < 5)
{
  $thiskey = array_key_exists($i, $form_authorized_keys)
    ? $form_authorized_keys[$i] : '';
  print '<span class="preinput">' . sprintf(_("Key #%s:"), $i+1)
    . "</span> <input type='text' size='60' name='form_authorized_keys[$i]' value='$thiskey' /><br />";
  $i++;
}

print '<br />'.form_footer(_("Update"));


site_user_footer(array());
