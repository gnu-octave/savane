#!/usr/bin/perl
#
# Copyright (C) 2005  Mathieu Roy
#
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Savane; if not, write to the Free Software Foundation,
# Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
# Add a tailing '###' in table user, field authorized_keys

# Previous version of Savane did not add a trailing new line in
# authorized_keys, causing the .ssh/authorized_keys file to be
# recreated at each cron job - with no harm but additional CPU and HD
# usage

use strict;
use Savane;

my $higherid;

# Get the higher id in the database
foreach my $tracker ("bugs", "task", "support", "patch") {
    my $new_higherid = GetDBSettings($tracker.'_report_field',
				     '1 ORDER BY report_id DESC LIMIT 1',
				     'report_id');
    $higherid = $new_higherid if $new_higherid > $higherid;
}


# Increase of ten, to avoid race conditions
$higherid += 10;


open(SQL, "> voting-system.sql");
print SQL "
ALTER TABLE bugs ADD vote INT( 11 ) DEFAULT '0' NOT NULL AFTER privacy ;
ALTER TABLE task ADD vote INT( 11 ) DEFAULT '0' NOT NULL AFTER privacy ;
ALTER TABLE patch ADD vote INT( 11 ) DEFAULT '0' NOT NULL AFTER privacy ;
ALTER TABLE support ADD vote INT( 11 ) DEFAULT '0' NOT NULL AFTER privacy ;



INSERT INTO bugs_field \
  VALUES (110, 'vote', 'TF', '6/10', 'Votes', 'How many votes this item received.', 'S', '0', '1', '0', '1', '0');
INSERT INTO task_field \
  VALUES (110, 'vote', 'TF', '6/10', 'Votes', 'How many votes this item received.', 'S', '0', '1', '0', '1', '0');
INSERT INTO patch_field \
  VALUES (110, 'vote', 'TF', '6/10', 'Votes', 'How many votes this item received.', 'S', '0', '1', '0', '1', '0');
INSERT INTO support_field \
  VALUES (110, 'vote', 'TF', '6/10', 'Votes', 'How many votes this item received.', 'S', '0', '1', '0', '1', '0');


INSERT INTO `bugs_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` , `transition_default_auth` ) 
VALUES (
 '110', '100', '1', '1', '1', '405', NULL , NULL , NULL , NULL , NULL , 'A'
);
INSERT INTO `task_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` , `transition_default_auth` ) 
VALUES (
 '110', '100', '1', '1', '1', '405', NULL , NULL , NULL , NULL , NULL , 'A'
);
INSERT INTO `patch_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` , `transition_default_auth` ) 
VALUES (
 '110', '100', '1', '1', '1', '405', NULL , NULL , NULL , NULL , NULL , 'A'
);
INSERT INTO `support_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` , `transition_default_auth` ) 
VALUES (
 '110', '100', '1', '1', '1', '405', NULL , NULL , NULL , NULL , NULL , 'A'
);


# specific query form
INSERT INTO bugs_report VALUES ($higherid,100,100,'Votes','The default plus the votes','S');
INSERT INTO task_report VALUES ($higherid,100,100,'Votes','The default plus the votes','S');
INSERT INTO patch_report VALUES ($higherid,100,100,'Votes','The default plus the votes','S');
INSERT INTO support_report VALUES ($higherid,100,100,'Votes','The default plus the votes','S');


INSERT INTO bugs_report_field VALUES ($higherid,'bug_id',0,1,NULL,1,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'vote',0,1,NULL,2,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'category_id',1,0,50,NULL,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'assigned_to',1,1,20,40,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'status_id',1,0,10,NULL,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'resolution_id',1,1,15,22,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'summary',0,1,NULL,20,NULL);
INSERT INTO bugs_report_field VALUES ($higherid,'date',0,1,NULL,50,NULL);

INSERT INTO task_report_field VALUES ($higherid,'bug_id',0,1,NULL,1,NULL);
INSERT INTO task_report_field VALUES ($higherid,'vote',0,1,NULL,2,NULL);
INSERT INTO task_report_field VALUES ($higherid,'category_id',1,0,50,NULL,NULL);
INSERT INTO task_report_field VALUES ($higherid,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO task_report_field VALUES ($higherid,'assigned_to',1,1,20,40,NULL);
INSERT INTO task_report_field VALUES ($higherid,'status_id',1,0,10,NULL,NULL);
INSERT INTO task_report_field VALUES ($higherid,'resolution_id',1,1,15,22,NULL);
INSERT INTO task_report_field VALUES ($higherid,'summary',0,1,NULL,20,NULL);
INSERT INTO task_report_field VALUES ($higherid,'date',0,1,NULL,50,NULL);

INSERT INTO support_report_field VALUES ($higherid,'bug_id',0,1,NULL,1,NULL);
INSERT INTO support_report_field VALUES ($higherid,'vote',0,1,NULL,2,NULL);
INSERT INTO support_report_field VALUES ($higherid,'category_id',1,0,50,NULL,NULL);
INSERT INTO support_report_field VALUES ($higherid,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO support_report_field VALUES ($higherid,'assigned_to',1,1,20,40,NULL);
INSERT INTO support_report_field VALUES ($higherid,'status_id',1,0,10,NULL,NULL);
INSERT INTO support_report_field VALUES ($higherid,'resolution_id',1,1,15,22,NULL);
INSERT INTO support_report_field VALUES ($higherid,'summary',0,1,NULL,20,NULL);
INSERT INTO support_report_field VALUES ($higherid,'date',0,1,NULL,50,NULL);

INSERT INTO patch_report_field VALUES ($higherid,'bug_id',0,1,NULL,1,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'vote',0,1,NULL,2,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'category_id',1,0,50,NULL,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'assigned_to',1,1,20,40,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'status_id',1,0,10,NULL,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'resolution_id',1,1,15,22,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'summary',0,1,NULL,20,NULL);
INSERT INTO patch_report_field VALUES ($higherid,'date',0,1,NULL,50,NULL);



CREATE TABLE user_votes (
 vote_id INT( 11 ) NOT NULL AUTO_INCREMENT ,
 user_id INT( 11 ) NOT NULL ,
 tracker VARCHAR( 16 ) NOT NULL ,
 item_id INT( 11 ) NOT NULL ,
 howmuch INT( 3 ) DEFAULT '0' NOT NULL ,
 PRIMARY KEY ( vote_id ) 
) TYPE = MYISAM ;
";

close(SQL);

print "The SQL script voting-system.sql has been written, run it now, and only 
once!

(ex: mysql dbname < file.sql)\n";
