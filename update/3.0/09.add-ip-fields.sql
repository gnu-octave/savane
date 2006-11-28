ALTER TABLE `bugs` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;
ALTER TABLE `bugs_history` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;

ALTER TABLE `support` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;
ALTER TABLE `support_history` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;
ALTER TABLE `patch` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;
ALTER TABLE `patch_history` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;

ALTER TABLE `cookbook` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;
ALTER TABLE `cookbook_history` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;

ALTER TABLE `task` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;
ALTER TABLE `task_history` ADD `ip` char(15) DEFAULT NULL AFTER `spamscore` ;
