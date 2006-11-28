CREATE TABLE `trackers_spamcheck_queue_notification` (
  `notification_id` int(11) NOT NULL auto_increment,
  `artifact` varchar(16) NOT NULL,
  `item_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `to_header` text NULL, 
  `subject_header` text NULL, 
  `other_headers` text NULL, 
  `message` text NULL,  
  PRIMARY KEY  (`notification_id`)
);


