<?php
# Temporary download area for project registration.
#
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2022  Ineiev
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

require_once ('../include/init.php');
extract (sane_import ('files', ['pass' => 'tarball']));
session_require (['isloggedin' => '1']);
$title = ['title' => _("Temporary upload")];

if (!isset ($tarball))
 {
   $HTML->header ($title);
   print "<form enctype='multipart/form-data' "
     . " action=\"" . htmlentities ($_SERVER['PHP_SELF']) . "\" method='post'>"
     . "<p>" . _("Select file to upload:") . "<br />\n"
     . "<input type='file' name='tarball'/>\n"
     . "<input type='submit' value='" . _('Upload file') . "' />"
     . "</p>\n</form>\n";
   $HTML->footer ([]);
   exit (0);
 }

if ($tarball['error'] != 0)
  exit_error (sprintf (_("Error during upload: %s"), $tarball['error']));

# Try to move $tmp_path to $path without overwriting if the latter exists;
# return $path when successful, $tmp_path otherwise.
function try_move ($tmp_path, $path)
{
  $link_error_handler = function ($errno, $errstr, $errfile, $errline)
  {
    # Ignore warning.
  };
  $old_handler = set_error_handler ($link_error_handler, E_WARNING);
  $res = link ($tmp_path, $path);
  set_error_handler ($old_handler, E_WARNING);
  if (!$res) # Already exists; fallback to temporary file name.
    return $tmp_path;
  unlink ($tmp_path);
  return $path;
}

$name = $tarball['name'];
$path = "$sys_upload_dir/$name";

# It might be easier to use tempnam (), but it has no --suffix feature.
$out = [];
$res = 0;
$name = strtr ($name, "'/", ".-");
exec ("mktemp -p \"$sys_upload_dir\" --suffix='-$name' XXXX", $out, $res);

if ($res)
  {
    # Can't create a temporary file; $path most probably will work,
    # but it would create a race condition, so just don't proceed.
    $path = null;
  }
else
  $path = try_move ($out[0], $path);

if (empty ($path) || !move_uploaded_file ($tarball['tmp_name'], $path))
  exit_error (_("Cannot move file to the download area."));

$HTML->header ($title);

$name = rawurlencode (basename ($path));
print "<p>" . _("Here's your temporary tar file URL:")
  . " https://$sys_default_domain/submissions_uploads/$name</p>\n";

$HTML->footer ([]);
?>
