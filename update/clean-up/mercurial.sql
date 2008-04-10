# Mercurial support

## group_type

ALTER TABLE `group_type` ADD `can_use_hg` INT( 1 ) DEFAULT '0' NOT NULL AFTER `can_use_git`;

ALTER TABLE `group_type` ADD `is_menu_configurable_hg` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_git_viewcvs` ,
 ADD `is_menu_configurable_hg_viewcvs` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_hg`;

ALTER TABLE `group_type` ADD `dir_type_hg` VARCHAR( 255 ) DEFAULT 'basicdirectory' NOT NULL AFTER `dir_type_git`;

ALTER TABLE `group_type` ADD `dir_hg` VARCHAR( 255 ) DEFAULT '/' NOT NULL AFTER `dir_git`;

ALTER TABLE `group_type` ADD `url_hg_viewcvs` VARCHAR( 255 ) DEFAULT 'http://' NOT NULL AFTER `url_git_viewcvs`;


## groups

ALTER TABLE `groups` ADD `use_hg` CHAR( 1 ) DEFAULT '0' AFTER `use_git`;

ALTER TABLE `groups` ADD `url_hg` VARCHAR( 255 ) DEFAULT NULL AFTER `url_git_viewcvs` ,
 ADD `url_hg_viewcvs` VARCHAR( 255 ) DEFAULT NULL AFTER `url_hg`;

ALTER TABLE `groups` ADD `dir_hg` VARCHAR( 255 ) DEFAULT NULL AFTER `dir_git`;
