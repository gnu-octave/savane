#!/usr/bin/perl
#
# $Id$
#
#  Copyright 2004 (c) Mathieu Roy <yeupou@gnu.org> 
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

# For the current database, remove all the fields that could implies 
# notification.
# It's purpose is to be able to run test against a copy of a production
# database without the risk of seing notifications being sent by mistake

use strict;
use Savane;
use Savane::DB;

my $user_address = "null-user\@localhost";
my $group_address = "null-user\@localhost";

open(OUT, "> clean-notif.sql");

# user table
print OUT "UPDATE user SET email='$user_address' WHERE 1;
UPDATE user SET user_pw='23132' WHERE user_pw='PAM';
";

# groups table
print OUT "UPDATE groups SET new_support_address='$group_address',new_bugs_address='$group_address',new_task_address='$group_address',new_patch_address='$group_address',new_news_address='$group_address' WHERE 1;
";

# remove cc
print OUT "DELETE FROM patch_cc;
DELETE FROM task_cc;
DELETE FROM bugs_cc;
DELETE FROM support_cc;
";

# remove categories notif
print OUT "UPDATE bugs_field_value SET email_ad='' WHERE 1;
UPDATE task_field_value SET email_ad='' WHERE 1;
UPDATE patch_field_value SET email_ad='' WHERE 1;
UPDATE support_field_value SET email_ad='' WHERE 1;
";

close(OUT);
print "\nNow run clean-notif.sql (mysql database < clean-notif.sql)\n";

