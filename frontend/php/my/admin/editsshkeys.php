<?php
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id: editsshkeys.php 4977 2005-11-15 17:38:40Z yeupou $
#
#  Copyright 1999-2000 (c) The SourceForge Crew
#
#  Copyright 2004-2006 (c) Mathieu Roy <yeupou--gnu.org>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

require_once('../../include/pre.php');
require_once('../../include/account.php');
session_require(array('isloggedin' => 1));

$update = isset($_REQUEST['update']) ? $_REQUEST['update'] : '';
if ($update)
{
  unset($keys);
  # Build the key string
  # Key limit is set to 25
  for ($i = 0; $i < 25; $i++)
    {
      $thiskey = stripslashesgpc($_POST['form_authorized_keys'][$i]);
      # Remove useless blank spaces
      $thiskey = trim($thiskey);
      # Remove line breaks
      $thiskey = str_replace("\n", " ", $thiskey);
      if ($thiskey != "")
	{
	  fb(sprintf(_("Key #%s seen"), $i+1));
	  $keys .= $thiskey."###";
	}
    }

  # Update the database
  $success = db_query("UPDATE user SET authorized_keys='" . mysql_real_escape_string($keys)
		      . "' WHERE user_id=" . user_getid());
  
  if ($success)
    { fb(_("Keys registered")); }
  else
    { fb(_("Error while registering keys"), 1); }
}
else
{
  # Grab keys from the database
  $res_keys = db_query("SELECT authorized_keys FROM user WHERE user_id=".user_getid());
  $row_keys = db_fetch_array($res_keys);

  $form_authorized_keys = split("###", $row_keys['authorized_keys'], 25);
}


# not valid registration, or first time to page
site_user_header(array('title' => _("Change Authorized Keys"),
		       'context' => 'account'));


# we get site-specific content
utils_get_content("account/editsshkeys");


print '<form action="editsshkeys.php" method="post">';

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
