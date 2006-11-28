CREATE TABLE `user_squad` (
  `user_squad_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `squad_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`user_squad_id`)
);
