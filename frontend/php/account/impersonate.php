<?php
# Become another user for testing his/her problems
# Copyright (C) 2007  Sylvain Beucler
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

require_once('../include/init.php');

if (!user_is_super_user())
  exit_error(_("You need to be site administrator to use this feature."));

extract(sane_import('post', array('user_name', 'uri')));


$new_uid = user_getid($user_name);
if ($new_uid == 0)
  exit_error(_("This user doesn't exist."));


// Modify session information to become the target user
extract(sane_import('cookie', array('session_hash')));
$result = db_execute("UPDATE session SET user_id=? WHERE session_hash=?",
		     array($new_uid, $session_hash));
session_cookie('session_uid', $new_uid);

header("Location: $uri");