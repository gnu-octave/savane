<?php
# Get group keyrings.
#
# Copyright (C) 2005 Mathieu Roy <yeupou--at--gnu.org>
# Copyright (C) 2017, 2021, 2022 Ineiev
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

extract (sane_import ('get', ['true' => 'download']));
$project = project_get_object ($group_id);

if (basename($_SERVER['PHP_SELF']) === 'memberlist-gpgkeys.php')
  {
    $keyring = $project->getGPGKeyring();
    $error_no_keyring = _("Member Keyring is empty, no keys were registered");
    $title = _("Group Member GPG Keyring");
    $filename = $group . '-members.gpg';
    $description = sprintf(_('Member GPG keyring of %s group.'), $group);
    $note = "\n\n"
. _('Note that this keyring is not intended for checking releases of that group.
Use Group Release Keyring instead.');
  }
else
  {
    $keyring = group_get_preference ($group_id, 'gpg_keyring');
    $error_no_keyring = _("Group Keyring is empty, no keys were registered");
    $title = _("Group Release GPG Keyring");
    $filename = $group . '-keyring.gpg';
    $description = sprintf(_('Release GPG keyring of %s group.'), $group);
    $note = '';
  }

if (!$keyring)
  exit_error ($error_no_keyring);

if ($download)
  {
    header ('Content-Type: application/pgp-keys');
    header ("Content-Disposition: attachment; filename=$filename");
    header ("Content-Description: $description");
    print "$description$note\n\n$keyring";
    exit (0);
  }

site_project_header (
  ['title' => $title, 'group' => $group_id, 'context' => 'keys']
);

print '<p>';
printf (
  _("You can <a href=\"%s\">download the keyring</a> and import it with
the command %s."),
  htmlentities ($_SERVER['PHP_SELF']) . "?group=$group&amp;download=1",
  '<em>gpg --import &lt;file&gt;</em>'
);
print "</p>\n";

site_project_footer (array ());
?>
