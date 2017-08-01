<?php
# Offer project members keyring for download
#
# Copyright (C) 2002-2005 Mathieu Roy <yeupou--gnu.org>
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

require_once('../include/init.php');
register_globals_off();
extract(sane_import('get', array('user_id')));


# Check whether the user exists
if (!$user_id)
{
  exit_error(_("User not found."));
}
$result = db_execute("SELECT user_name,gpg_key FROM user WHERE user_id=?",
		     array($user_id));

if (!$result || db_numrows($result) < 1)
{
  exit_error(_("User not found."));
}

# Check whether a gpg key was registered
if (!db_result($result,0,'gpg_key'))
{
  exit_error(_("This user hasn't registered a GPG key."));
}

# If we get here, a key exists. Simply print it.
header('Content-Type: application/pgp-keys');
header('Content-Disposition: attachment; filename='
       .db_result($result, 0, 'user_name').'-key.gpg');
# TRANSLATORS: the argument is user's name.
header('Content-Description: '
       .sprintf(_('GPG Key of the user %s'),
       db_result($result, 0, 'user_name')));
print db_result($result,0,'gpg_key');
?>
