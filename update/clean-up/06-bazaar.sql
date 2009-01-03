# Bazaar support

## group_type

ALTER TABLE `group_type` ADD `can_use_bzr` INT( 1 ) DEFAULT '0' NOT NULL AFTER `can_use_hg`;

ALTER TABLE `group_type` ADD `is_menu_configurable_bzr` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_hg_viewcvs` ,
 ADD `is_menu_configurable_bzr_viewcvs` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_bzr`;

ALTER TABLE `group_type` ADD `dir_type_bzr` VARCHAR( 255 ) DEFAULT 'basicbzr' NOT NULL AFTER `dir_type_hg`;

ALTER TABLE `group_type` ADD `dir_bzr` VARCHAR( 255 ) DEFAULT '/' NOT NULL AFTER `dir_hg`;

ALTER TABLE `group_type` ADD `url_bzr_viewcvs` VARCHAR( 255 ) DEFAULT 'http://' NOT NULL AFTER `url_hg_viewcvs`;


## groups

ALTER TABLE `groups` ADD `use_bzr` CHAR( 1 ) DEFAULT '0' AFTER `use_hg`;

ALTER TABLE `groups` ADD `url_bzr` VARCHAR( 255 ) DEFAULT NULL AFTER `url_hg_viewcvs` ,
 ADD `url_bzr_viewcvs` VARCHAR( 255 ) DEFAULT NULL AFTER `url_bzr`;

ALTER TABLE `groups` ADD `dir_bzr` VARCHAR( 255 ) DEFAULT NULL AFTER `dir_hg`;
