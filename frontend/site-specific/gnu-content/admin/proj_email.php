<?php
# Savannah - Project registration STEP 6 Confirmation mail.
# Here, you can configure the mail sent to user and admins.

# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2013 Karl Berry
# Copyright (C) 2017, 2022 Ineiev <ineiev@gnu.org>
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

# We include this as function, it's easier to work with vars
# in this way.

# This message is not localized because it's sent to admins.

function approval_gen_email ($group_name, $unix_group_name)
{
  $fmt = ('
Your project registration for %s has been approved.
Project full name:   %s
Project system name: %s
Project page:        %s

Please note, that it will take up to half an hour for the system to
be updated (CVS repository creation for instance) before your project
will be fully functional.

Enjoy the system, and please tell others about %s.
Let us know if there is anything we can do to help you.

 -- the %s Volunteers





Post scriptum, important note:
   In order to release your project, you should write copyright notices
   and license notices at the beginning of every source code file, and
   include a copy of the plain text version of the license. If your
   software is published under the GNU GPL, please read
   https://www.gnu.org/licenses/gpl.html.
');
  $forge = $GLOBALS['sys_name'];
  $host = $GLOBALS['sys_https_host'];
  $message =
    sprintf (
      $fmt, $forge, $group_name, $unix_group_name,
      "https://$host/projects/$unix_group_name", $forge, $forge
    );
  return $message;
}
?>
