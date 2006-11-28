ALTER TABLE `task_canned_responses` ADD `order_id` INT( 11 ) DEFAULT '50' NOT NULL AFTER `body` ;

ALTER TABLE `bugs_canned_responses` ADD `order_id` INT( 11 ) DEFAULT '50' NOT NULL AFTER `body` ;

ALTER TABLE `support_canned_responses` ADD `order_id` INT( 11 ) DEFAULT '50' NOT NULL AFTER `body` ;

ALTER TABLE `patch_canned_responses` ADD `order_id` INT( 11 ) DEFAULT '50' NOT NULL AFTER `body` ;

 