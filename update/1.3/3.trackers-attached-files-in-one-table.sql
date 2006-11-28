CREATE TABLE trackers_file (
  file_id int(11) NOT NULL auto_increment,
  item_id int(11) NOT NULL default '0',
  artifact varchar(16) NOT NULL default '',
  submitted_by int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  description text NOT NULL,
  file longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  PRIMARY KEY  (file_id),
  KEY item_id_idx (item_id)
) TYPE=MyISAM;

