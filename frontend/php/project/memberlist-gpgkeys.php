<?php
# Get group keyring.
# 
# Copyright (C) 2005 Mathieu Roy <yeupou--at--gnu.org>
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

extract(sane_import('get', array('download')));

$project=project_get_object($group_id);
$keyring = $project->getGPGKeyring();

if (!$keyring) {
  exit_error(_("They GPG Keyring of the project is empty, no keys were registered"));
}

if (!$download) {
  site_project_header(array('title'=>_("Project Members GPG Keyring"),
			    'group'=>$group_id,
			    'context'=>'keys'));

  print '<p>'
.sprintf(_("You can <a href=\"%s\">download the keyring</a> and import it with
the command %s."), htmlentities ($_SERVER['PHP_SELF']).'?group='.$group
                   .'&amp;download=1',
'<em>gpg --import &lt;file&gt;</em>').'</p>';

  site_project_footer(array());

} else {

# Download the keyring
  $result = db_execute("SELECT keyring FROM groups_gpg_keyrings "
                       ."WHERE unix_group_name=? LIMIT 1", array($group));

  header('Content-Type: application/pgp-keys');
  header('Content-Disposition: attachment; filename='.$group.'-keyring.gpg');
  header('Content-Description: GPG Keyring of the project '.$group);
  passthru('/usr/local/bin/sv_tmpgpg ' . $group_id);
}
?>
