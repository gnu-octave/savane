# git support

## group_type

ALTER TABLE `group_type` ADD `can_use_git` INT( 1 ) DEFAULT '0' NOT NULL AFTER `can_use_svn`;

ALTER TABLE `group_type` ADD `is_menu_configurable_git` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_svn_viewcvs` ,
 ADD `is_menu_configurable_git_viewcvs` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_git`;

ALTER TABLE `group_type` ADD `dir_type_git` VARCHAR( 255 ) DEFAULT 'basicgit' NOT NULL AFTER `dir_type_svn`;

ALTER TABLE `group_type` ADD `dir_git` VARCHAR( 255 ) DEFAULT '/' NOT NULL AFTER `dir_svn`;

ALTER TABLE `group_type` ADD `url_git_viewcvs` VARCHAR( 255 ) DEFAULT 'http://' NOT NULL AFTER `url_svn_viewcvs`;


## groups

ALTER TABLE `groups` ADD `use_git` CHAR( 1 ) DEFAULT '0' AFTER `use_svn`;

ALTER TABLE `groups` ADD `url_git` VARCHAR( 255 ) DEFAULT NULL AFTER `url_svn_viewcvs` ,
 ADD `url_git_viewcvs` VARCHAR( 255 ) DEFAULT NULL AFTER `url_git`;

ALTER TABLE `groups` ADD `dir_git` VARCHAR( 255 ) DEFAULT NULL AFTER `dir_svn`;
