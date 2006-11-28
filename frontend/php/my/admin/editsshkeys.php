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

require '../../include/pre.php';
require '../../include/account.php';
session_require(array(isloggedin=>1));


if ($update)
{
  unset($keys);
  # Build the key string
  # Key limit is set to 25
  for ($i = 1; $i < 26; $i++)
    {
      $thiskey = "form_authorized_keys_$i";
      # Remove useless blank spaces
      $$thiskey = trim($$thiskey);
      # Remove line breaks
      $$thiskey = str_replace("\n", "", $$thiskey);
      if ($$thiskey != "")
	{
	  fb(sprintf(_("Key #%s seen"), $i));
	  $keys .= $$thiskey."###";
	}
    }

  # Update the database
  $success = db_query("UPDATE user SET authorized_keys='".addslashes($keys)."' WHERE user_id=" . user_getid());

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

  list($form_authorized_keys_1,
       $form_authorized_keys_2,
       $form_authorized_keys_3,
       $form_authorized_keys_4,
       $form_authorized_keys_5,
       $form_authorized_keys_6,
       $form_authorized_keys_7,
       $form_authorized_keys_8,
       $form_authorized_keys_9,
       $form_authorized_keys_10,
       $form_authorized_keys_11,
       $form_authorized_keys_12,
       $form_authorized_keys_13,
       $form_authorized_keys_14,
       $form_authorized_keys_15,
       $form_authorized_keys_16,
       $form_authorized_keys_17,
       $form_authorized_keys_18,
       $form_authorized_keys_19,
       $form_authorized_keys_20,
       $form_authorized_keys_21,
       $form_authorized_keys_22,
       $form_authorized_keys_23,
       $form_authorized_keys_24,
       $form_authorized_keys_25) = split("###", $row_keys['authorized_keys'], 25);

}


# not valid registration, or first time to page
site_user_header(array('title'=>_("Change Authorized Keys"),'context'=>'account'));


# we get site-specific content
utils_get_content("account/editsshkeys");


print '<form action="editsshkeys.php" method="post">';

print '<h3>'._("Authorized keys:").'</h3>';

print '<p>'._("Fill the text fields below with the public keys for each key you want to register. After submitting, verify that the number of keys registered is what you expected.").'</p>';

# Key limit is set to 25
# By default, show only 6 fields
for ($i = 1; $i < 26; $i++)
{
  $thiskey="form_authorized_keys_$i";
  $$thiskey=trim($$thiskey);
  print '<span class="preinput">'.sprintf(_("Key #%s:"), $i).'</span> <input type="text" size="60" name="form_authorized_keys_'.$i.'" value="'.$$thiskey.'" /><br />';
  if ($i > 6 && $$thiskey == "")
    { break; }
}

print '<br />'.form_footer(_("Update"));


site_user_footer(array());

?>
