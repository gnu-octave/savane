<?php
# Become another user for testing his/her problems
# Copyright (C) 2007  Sylvain Beucler
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

if (!user_is_super_user())
  exit_error(_("You need to be site administrator to use this feature."));

extract(sane_import('post', array('user_name', 'uri')));

$new_uid = user_getid($user_name);
if ($new_uid == 0)
  exit_error(_("This user doesn't exist."));

# Modify session information to become the target user.
extract(sane_import('cookie', array('session_hash')));
$result = db_execute("UPDATE session SET user_id=? WHERE session_hash=?",
                     array($new_uid, $session_hash));
session_cookie('session_uid', $new_uid);

# Only allow redirections to the same website.
if (strlen ($uri) < 2
    || substr ($uri, 0, 1) !== '/' || substr ($uri, 1, 1) === '/')
  $uri = "/";

header("Location: $uri");
?>
