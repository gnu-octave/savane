<?php

# Savannah - User registration reminder to group admins.
#    Here, you can configure the mail sent to group admins.
#    BEWARE, this file content must be PHP, with no parse-error.
#    Do not modify it until you really know what you're doing.

# Copyright (C) 2004, 2005 Sylvain Beucler
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

# we include this as function, it's easier to work with vars
# in this way

# This message is not localized because it's sent to admins.

function approval_user_gen_email ($group_name, $unix_group_name,
     $group_id, $user_name, $user_full_name, $user_email, $user_message) {
   $message = sprintf (('
%s requested membership to the group %s
at %s

User Details:
-------------
Name:    %s
Login:   %s
Email:   %s

Project Details:
----------------
Name:      %s
Unix Name: %s
Main Page: %s

Message from user:
------------------

%s


Note:
-----
You receive this email because you are registered as an administrator of this
project and the system has been configured to send emails to administrators
when new users register.




Please login and go to the page
%s and approve or discard this membership request.


 -- the %s team
'),
  $user_full_name, $group_name,  $GLOBALS['sys_name'], $user_full_name,
  $user_name, $user_email, $group_name, $unix_group_name,
  $GLOBALS['sys_https_url'].'/projects/'.$unix_group_name, $user_message,
  $GLOBALS['sys_https_url'].'/project/admin/useradmin.php?group='
    .$unix_group_name, $GLOBALS['sys_name']);

   return $message;
}

?>
