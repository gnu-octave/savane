<?php
# Serve user guide.
#
# Copyright (C) 2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2022 Ineiev
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

site_header ([]);

# Guess the correct file name.
# Print the index if no specific file was asked.
# Make sure we dont have anything stupid/malicious in the file name.
# Get the HTML and include directly.

extract (sane_import (
  'get', ['preg' => [['file', '/^[[:alnum:]_-]+[.]html$/']]]
));
if (empty ($file))
  $file = "index.html";

print utils_read_file ("$sys_www_topdir/userguide/$file");

$HTML->footer ([]);
?>
