# Arch and SVN support

## group_type

ALTER TABLE `group_type` ADD `can_use_arch` INT( 1 ) DEFAULT '0' NOT NULL AFTER `can_use_cvs` ,
 ADD `can_use_svn` INT( 1 ) DEFAULT '0' NOT NULL AFTER `can_use_arch` ;

ALTER TABLE `group_type` ADD `is_menu_configurable_arch` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_cvs_viewcvs_homepage` ,
 ADD `is_menu_configurable_arch_viewcvs` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_arch` ,
 ADD `is_menu_configurable_svn` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_arch_viewcvs` ,
 ADD `is_menu_configurable_svn_viewcvs` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_svn` ;

ALTER TABLE `group_type` ADD `dir_type_arch` VARCHAR( 255 ) DEFAULT 'basicdirectory' NOT NULL AFTER `dir_type_cvs` ,
 ADD `dir_type_svn` VARCHAR( 255 ) DEFAULT 'basicsvn' NOT NULL AFTER `dir_type_arch` ;

ALTER TABLE `group_type` ADD `dir_arch` VARCHAR( 255 ) DEFAULT '/' NOT NULL AFTER `dir_cvs` ,
 ADD `dir_svn` VARCHAR( 255 ) DEFAULT '/' NOT NULL AFTER `dir_arch` ;

ALTER TABLE `group_type` ADD `url_arch_viewcvs` VARCHAR( 255 ) DEFAULT 'http://' NOT NULL AFTER `url_cvs_viewcvs` ,
 ADD `url_svn_viewcvs` VARCHAR( 255 ) DEFAULT 'http://' NOT NULL AFTER `url_arch_viewcvs` ;


## groups

ALTER TABLE `groups` ADD `use_arch` CHAR( 1 ) DEFAULT '0' AFTER `use_cvs` ,
 ADD `use_svn` CHAR( 1 ) DEFAULT '0' AFTER `use_arch` ;

ALTER TABLE `groups` ADD `url_arch` VARCHAR( 255 ) DEFAULT NULL AFTER `url_cvs_viewcvs_homepage` ,
 ADD `url_arch_viewcvs` VARCHAR( 255 ) DEFAULT NULL AFTER `url_arch` ,
 ADD `url_svn` VARCHAR( 255 ) DEFAULT NULL  AFTER `url_arch_viewcvs` ,
 ADD `url_svn_viewcvs` VARCHAR( 255 ) DEFAULT NULL  AFTER `url_svn` ;

ALTER TABLE `groups` ADD `dir_arch` VARCHAR( 255 ) DEFAULT NULL AFTER `dir_cvs` ,
 ADD `dir_svn` VARCHAR( 255 ) DEFAULT NULL AFTER `dir_arch` ;

