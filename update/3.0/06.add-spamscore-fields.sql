ALTER TABLE `bugs` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `vote` ;
ALTER TABLE `bugs_history` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `date` ;
UPDATE bugs SET spamscore='3' WHERE submitted_by='100';
UPDATE bugs_history SET spamscore='3' WHERE mod_by='100';



ALTER TABLE `support` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `vote` ;
ALTER TABLE `support_history` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `date` ;
UPDATE support SET spamscore='3' WHERE submitted_by='100';
UPDATE support_history SET spamscore='3' WHERE mod_by='100';


ALTER TABLE `task` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `vote` ;
ALTER TABLE `task_history` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `date` ;

UPDATE task SET spamscore='3' WHERE submitted_by='100';
UPDATE task_history SET spamscore='3' WHERE mod_by='100';


ALTER TABLE `cookbook` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `vote` ;
ALTER TABLE `cookbook_history` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `date` ;

UPDATE cookbook SET spamscore='3' WHERE submitted_by='100';
UPDATE cookbook_history SET spamscore='3' WHERE mod_by='100';


ALTER TABLE `patch` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `vote` ;
ALTER TABLE `patch_history` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `date` ;

UPDATE patch SET spamscore='3' WHERE submitted_by='100';
UPDATE patch_history SET spamscore='3' WHERE mod_by='100';


ALTER TABLE `user` ADD `spamscore` INT( 2 ) DEFAULT '0' AFTER `status` ;
