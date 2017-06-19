<?php
# Savannah - User registration reminder to group admins.
#
# Copyright (C) 2003 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017 Ineiev <ineiev@gnu.org>
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

#    Here, you can configure the mail sent to group admins.
#    BEWARE, this file content must be PHP, with no parse-error.
#    Do not modify it until you really know what you're doing.

# we include this as function, it's easier to work with vars
# in this way

# This message is not localized because it's sent to admins.

function approval_user_gen_email ($group_name, $unix_group_name, $group_id,
    $user_name, $user_full_name, $user_email, $user_message) {
  $message = sprintf (('
A new user has registered on %s.

User Full Name:   %s
User Name:        %s
User Email:       %s

Project Full Name:   %s
Project System Name: %s
Project Page:        %s

Message from user:

%s

You receive this email because you are registered as an administrator
of this project and the system has been configured to send emails
to administrators when new users register.

Please login and go to the page
%s
and approve this new pending user, so they can obtain full
functionality on the web site.


 -- the %s team
'), $GLOBALS['sys_name'], $user_full_name, $user_name, $user_email,
  $group_name, $unix_group_name,
  $GLOBALS['sys_https_url'].'/projects/'.$unix_group_name,
  $user_message,
  $GLOBALS['sys_https_url'].'/project/admin/useradmin.php?group_id='.$group_id,
  $GLOBALS['sys_name']);

   return $message;
}

?>
