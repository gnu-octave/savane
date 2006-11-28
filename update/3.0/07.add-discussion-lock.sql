ALTER TABLE `bugs` ADD `discussion_lock` INT( 1 ) DEFAULT '0' AFTER `privacy` ;
ALTER TABLE `support` ADD `discussion_lock` INT( 1 ) DEFAULT '0' AFTER `privacy` ;
ALTER TABLE `task` ADD `discussion_lock` INT( 1 ) DEFAULT '0' AFTER `privacy` ;
ALTER TABLE `cookbook` ADD `discussion_lock` INT( 1 ) DEFAULT '0' AFTER `privacy` ;
ALTER TABLE `patch` ADD `discussion_lock` INT( 1 ) DEFAULT '0' AFTER `privacy` ;


INSERT INTO `bugs_field` (`bug_field_id`, `field_name`, `display_type`, `display_size`, `label`, `description`, `scope`, `required`, `empty_ok`, `keep_history`, `special`, `custom`) VALUES (112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0) ;
INSERT INTO `support_field` (`bug_field_id`, `field_name`, `display_type`, `display_size`, `label`, `description`, `scope`, `required`, `empty_ok`, `keep_history`, `special`, `custom`) VALUES (112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0) ;
INSERT INTO `task_field` (`bug_field_id`, `field_name`, `display_type`, `display_size`, `label`, `description`, `scope`, `required`, `empty_ok`, `keep_history`, `special`, `custom`) VALUES (112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0) ;
INSERT INTO `cookbook_field` (`bug_field_id`, `field_name`, `display_type`, `display_size`, `label`, `description`, `scope`, `required`, `empty_ok`, `keep_history`, `special`, `custom`) VALUES (112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0) ;
INSERT INTO `patch_field` (`bug_field_id`, `field_name`, `display_type`, `display_size`, `label`, `description`, `scope`, `required`, `empty_ok`, `keep_history`, `special`, `custom`) VALUES (112,'discussion_lock','SB','','Discussion Lock','Determines whether comments can still be added to the item','S',1,1,1,0,0) ;


INSERT INTO `bugs_field_usage` (`bug_field_id`, `group_id`, `use_it`, `show_on_add`, `show_on_add_members`, `place`, `custom_label`, `custom_description`, `custom_display_size`, `custom_empty_ok`, `custom_keep_history`, `transition_default_auth`) VALUES (112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A') ;
INSERT INTO `support_field_usage` (`bug_field_id`, `group_id`, `use_it`, `show_on_add`, `show_on_add_members`, `place`, `custom_label`, `custom_description`, `custom_display_size`, `custom_empty_ok`, `custom_keep_history`, `transition_default_auth`) VALUES (112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A') ;
INSERT INTO `task_field_usage` (`bug_field_id`, `group_id`, `use_it`, `show_on_add`, `show_on_add_members`, `place`, `custom_label`, `custom_description`, `custom_display_size`, `custom_empty_ok`, `custom_keep_history`, `transition_default_auth`) VALUES (112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A') ;
INSERT INTO `patch_field_usage` (`bug_field_id`, `group_id`, `use_it`, `show_on_add`, `show_on_add_members`, `place`, `custom_label`, `custom_description`, `custom_display_size`, `custom_empty_ok`, `custom_keep_history`, `transition_default_auth`) VALUES (112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A') ;
INSERT INTO `cookbook_field_usage` (`bug_field_id`, `group_id`, `use_it`, `show_on_add`, `show_on_add_members`, `place`, `custom_label`, `custom_description`, `custom_display_size`, `custom_empty_ok`, `custom_keep_history`, `transition_default_auth`) VALUES (112,100,1,0,0,800,NULL,NULL,NULL,NULL,NULL,'A') ;


INSERT INTO `bugs_field_value` (`bug_field_id`, `group_id`, `value_id`, `value`, `description`, `order_id`, `status`, `email_ad`, `send_all_flag`) VALUES (112,100,0,'Unlocked','Comment can be added freely',20,'P',NULL,1),(112,100,1,'Locked','Discussion about this item is over',30,'P',NULL,1) ;
INSERT INTO `support_field_value` (`bug_field_id`, `group_id`, `value_id`, `value`, `description`, `order_id`, `status`, `email_ad`, `send_all_flag`) VALUES (112,100,0,'Unlocked','Comment can be added freely',20,'P',NULL,1),(112,100,1,'Locked','Discussion about this item is over',30,'P',NULL,1) ;
INSERT INTO `task_field_value` (`bug_field_id`, `group_id`, `value_id`, `value`, `description`, `order_id`, `status`, `email_ad`, `send_all_flag`) VALUES (112,100,0,'Unlocked','Comment can be added freely',20,'P',NULL,1),(112,100,1,'Locked','Discussion about this item is over',30,'P',NULL,1) ;
INSERT INTO `cookbook_field_value` (`bug_field_id`, `group_id`, `value_id`, `value`, `description`, `order_id`, `status`, `email_ad`, `send_all_flag`) VALUES (112,100,0,'Unlocked','Comment can be added freely',20,'P',NULL,1),(112,100,1,'Locked','Discussion about this item is over.',30,'P',NULL,1) ;
INSERT INTO `patch_field_value` (`bug_field_id`, `group_id`, `value_id`, `value`, `description`, `order_id`, `status`, `email_ad`, `send_all_flag`) VALUES (112,100,0,'Unlocked','Comment can be added freely',20,'P',NULL,1),(112,100,1,'Locked','Discussion about this item is over',30,'P',NULL,1) ;

