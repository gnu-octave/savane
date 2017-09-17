<?php
# Temporary download area for project registration
# 
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

require_once('../include/init.php');
extract(sane_import('files', array('tarball')));

session_require(array('isloggedin'=>'1'));

if (!isset($tarball))
{
  $HTML->header(array('title' => _("Temporary upload")));
  echo "<form enctype='multipart/form-data' "
    . " action=\"";
  print htmlentities($_SERVER['PHP_SELF'])
    . "\" method='post'>";
  echo "<p>"._("Select file to upload:")."<br />\n";
  echo "<input type='file' name='tarball'/>";
  echo "<input type='submit' value='" . _('Upload file') . "' />";
  echo "</p>\n</form>\n";
}
else
{
  if ($tarball['error'] != 0)
    exit_error(sprintf(_("Error during upload: %s"), $tarball['error']));

  if (!move_uploaded_file($tarball['tmp_name'], $GLOBALS['sys_upload_dir']
                                                . '/' . $tarball['name']))
    exit_error(_("Cannot move file to the download area."));

  $HTML->header(array('title' => _("Temporary upload")));
  echo "<p>" . _("Here's your temporary tarball URL:")
    . " "
    . "https://" . $GLOBALS['sys_default_domain'] . "/submissions_uploads/"
    .rawurlencode($tarball['name'])
    . "</p>\n";
}

$HTML->footer(array());
?>
