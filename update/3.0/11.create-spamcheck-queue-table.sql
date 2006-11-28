CREATE TABLE `trackers_spamcheck_queue` (
  `queue_id` int(11) NOT NULL auto_increment,
  `artifact` varchar(16) NOT NULL,
  `item_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `priority` int(1) NOT NULL default '1',
  `date` int(11) NOT NULL,
-- should we put the summary in this database or extract it when checking?
-- * duplicating data means we do bigger inserts
-- * extracting when checking means doing more SQL requests
  PRIMARY KEY  (`queue_id`)
);


