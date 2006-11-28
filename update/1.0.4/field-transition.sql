ALTER TABLE `task_field_usage` ADD `transition_default_auth` CHAR( 1 ) DEFAULT 'A' NOT NULL ;
ALTER TABLE `patch_field_usage` ADD `transition_default_auth` CHAR( 1 ) DEFAULT 'A' NOT NULL ;
ALTER TABLE `support_field_usage` ADD `transition_default_auth` CHAR( 1 ) DEFAULT 'A' NOT NULL ;
ALTER TABLE `bugs_field_usage` ADD `transition_default_auth` CHAR( 1 ) DEFAULT 'A' NOT NULL ;


CREATE TABLE trackers_field_transition (
  transition_id int(11) NOT NULL auto_increment,
  artifact varchar(16) NOT NULL default '',
  group_id int(11) NOT NULL default '0',
  field_id int(11) NOT NULL default '0',
  from_value_id int(11) NOT NULL default '0',
  to_value_id int(11) NOT NULL default '0',
  is_allowed char(1) default 'Y',
  assign_to int(11) NOT NULL default '100',
  notification_list text,
  PRIMARY KEY  (transition_id)
) TYPE=MyISAM;

