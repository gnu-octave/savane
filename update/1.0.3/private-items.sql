# Add new tables related to private items
#
ALTER TABLE `bugs` ADD `privacy` VARCHAR( 2 ) DEFAULT '1' AFTER `severity` ;

INSERT INTO `bugs_field` ( `bug_field_id` , `field_name` , `display_type` , `display_size` , `label` , `description` , `scope` , `required` , `empty_ok` , `keep_history` , `special` , `custom` )
VALUES (
'109', 'privacy', 'SB', '', 'Privacy', 'Determines whether the item can be seen by members of the project only or anybody.', 'P', '0', '1', '1', '0', '0'
);
INSERT INTO `bugs_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` )
VALUES (
'109', '100', '1', '1', '1', '402', NULL , NULL , NULL , NULL , NULL
);
INSERT INTO `bugs_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'111', '109', '100', '1', 'Public', 'This item can be seen by everybody.', '10', 'A', NULL , '1'
);
INSERT INTO `bugs_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'112', '109', '100', '2', 'Private', 'This item can be seen only by project members.', '20', 'A', NULL , '1'
);


ALTER TABLE `task` ADD `privacy` VARCHAR( 2 ) DEFAULT '1' AFTER `severity` ;

INSERT INTO `task_field` ( `bug_field_id` , `field_name` , `display_type` , `display_size` , `label` , `description` , `scope` , `required` , `empty_ok` , `keep_history` , `special` , `custom` )
VALUES (
'109', 'privacy', 'SB', '', 'Privacy', 'Determines whether the item can be seen by members of the project only or anybody.', 'P', '0', '1', '1', '0', '0'
);
INSERT INTO `task_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` )
VALUES (
'109', '100', '1', '1', '1', '402', NULL , NULL , NULL , NULL , NULL
);
INSERT INTO `task_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'111', '109', '100', '1', 'Public', 'This item can be seen by everybody.', '10', 'A', '' , '1'
);
INSERT INTO `task_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'112', '109', '100', '2', 'Private', 'This item can be seen only by project members.', '20', 'A', '' , '1'
);

ALTER TABLE `support` ADD `privacy` VARCHAR( 2 ) DEFAULT '1' AFTER `severity` ;

INSERT INTO `support_field` ( `bug_field_id` , `field_name` , `display_type` , `display_size` , `label` , `description` , `scope` , `required` , `empty_ok` , `keep_history` , `special` , `custom` )
VALUES (
'109', 'privacy', 'SB', '', 'Privacy', 'Determines whether the item can be seen by members of the project only or anybody.', 'P', '0', '1', '1', '0', '0'
);
INSERT INTO `support_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` )
VALUES (
'109', '100', '1', '1', '1', '402', NULL , NULL , NULL , NULL , NULL
);
INSERT INTO `support_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'111', '109', '100', '1', 'Public', 'This item can be seen by everybody.', '10', 'A', '' , '1'
);
INSERT INTO `support_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'112', '109', '100', '2', 'Private', 'This item can be seen only by project members.', '20', 'A', '' , '1'
);


ALTER TABLE `patch` ADD `privacy` VARCHAR( 2 ) DEFAULT '1' AFTER `severity` ;


INSERT INTO `patch_field` ( `bug_field_id` , `field_name` , `display_type` , `display_size` , `label` , `description` , `scope` , `required` , `empty_ok` , `keep_history` , `special` , `custom` )
VALUES (
'109', 'privacy', 'SB', '', 'Privacy', 'Determines whether the item can be seen by members of the project only or anybody.', 'P', '0', '1', '1', '0', '0'
);
INSERT INTO `patch_field_usage` ( `bug_field_id` , `group_id` , `use_it` , `show_on_add` , `show_on_add_members` , `place` , `custom_label` , `custom_description` , `custom_display_size` , `custom_empty_ok` , `custom_keep_history` )
VALUES (
'109', '100', '1', '1', '1', '402', NULL , NULL , NULL , NULL , NULL
);
INSERT INTO `patch_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'111', '109', '100', '1', 'Public', 'This item can be seen by everybody.', '10', 'A', '' , '1'
);
INSERT INTO `patch_field_value` ( `bug_fv_id` , `bug_field_id` , `group_id` , `value_id` , `value` , `description` , `order_id` , `status` , `email_ad` , `send_all_flag` )
VALUES (
'112', '109', '100', '2', 'Private', 'This item can be seen only by project members.', '20', 'A', '' , '1'
);
