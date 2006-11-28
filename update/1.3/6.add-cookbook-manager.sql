-- This will make possible to run the cookbook manager:
--    these make the whole system aware of the cookbook manager

-- Update group type and group to enable group type configuration of it
ALTER TABLE group_type ADD cookbook_rflags INT( 1 ) DEFAULT '5' AFTER patch_rflags ;
ALTER TABLE group_type ADD cookbook_flags INT( 1 ) DEFAULT '2' AFTER patch_flags ;
ALTER TABLE groups_default_permissions ADD cookbook_rflags INT( 1 ) AFTER bugs_rflags ;
ALTER TABLE groups_default_permissions ADD cookbook_flags INT( 1 ) AFTER bugs_flags ;
ALTER TABLE user_group ADD cookbook_flags INT( 11 ) DEFAULT NULL AFTER support_flags ;
ALTER TABLE groups ADD cookbook_preamble TEXT DEFAULT NULL AFTER support_preamble ;
ALTER TABLE groups ADD new_cookbook_address TEXT NOT NULL AFTER new_news_address ;
ALTER TABLE groups ADD cookbook_glnotif INT( 11 ) DEFAULT '1' NOT NULL AFTER patch_glnotif ;
ALTER TABLE groups ADD send_all_cookbook INT( 11 ) DEFAULT '0' NOT NULL AFTER send_all_task ;
ALTER TABLE groups ADD cookbook_private_exclude_address TEXT DEFAULT NULL AFTER patch_private_exclude_address ;



-- This will make possible to run the cookbook manager:
--    these create the necessary tables for the cookbook manager itself
--

CREATE TABLE cookbook_context2recipe (
 context_id INT(11) NOT NULL AUTO_INCREMENT ,
 recipe_id INT(11) NOT NULL,
 group_id INT(11) NOT NULL,
--
 audience_anonymous INT(1) DEFAULT '0',
 audience_loggedin INT(1) DEFAULT '0',
 audience_members INT(1) DEFAULT '0',
 audience_technicians INT(1) DEFAULT '0',
 audience_managers INT(1) DEFAULT '0',
--
 context_project INT(1) DEFAULT '0',
 context_homepage INT(1) DEFAULT '0',
 context_cookbook INT(1) DEFAULT '0',
 context_download INT(1) DEFAULT '0',
 context_support INT(1) DEFAULT '0',
 context_bugs INT(1) DEFAULT '0',
 context_task INT(1) DEFAULT '0',
 context_patch INT(1) DEFAULT '0',
 context_news INT(1) DEFAULT '0',
 context_mail INT(1) DEFAULT '0',
 context_cvs INT(1) DEFAULT '0',
 context_arch INT(1) DEFAULT '0',
 context_svn INT(1) DEFAULT '0',
 context_my INT(1) DEFAULT '0',
 context_stats INT(1) DEFAULT '0',
 context_siteadmin INT(1) DEFAULT '0',
 context_people INT(1) DEFAULT '0',
--
 subcontext_browsing INT(1) DEFAULT '0',
 subcontext_postitem INT(1) DEFAULT '0',
 subcontext_edititem INT(1) DEFAULT '0',
 subcontext_search INT(1) DEFAULT '0',
 subcontext_configure INT(1) DEFAULT '0',
--
 PRIMARY KEY (context_id) 
) TYPE = MYISAM ;


CREATE TABLE cookbook (
  bug_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  status_id int(11) NOT NULL default '100',
  severity int(11) NOT NULL default '5',
  privacy int(2) NOT NULL default '1',
  vote int(11) default '0',
  category_id int(11) NOT NULL default '100',
  submitted_by int(11) NOT NULL default '100',
  assigned_to int(11) NOT NULL default '100',
  date int(11) NOT NULL default '0',
  summary text,
  details text,
  close_date int(11) default NULL,
  bug_group_id int(11) NOT NULL default '100',
  resolution_id int(11) NOT NULL default '9',
  category_version_id int(11) NOT NULL default '100',
  platform_version_id int(11) NOT NULL default '100',
  reproducibility_id int(11) NOT NULL default '100',
  size_id int(11) NOT NULL default '100',
  fix_release_id int(11) NOT NULL default '100',
  plan_release_id int(11) NOT NULL default '100',
  hours float(10,2) NOT NULL default '0.00',
  component_version varchar(255) NOT NULL default '',
  fix_release varchar(255) NOT NULL default '',
  plan_release varchar(255) NOT NULL default '',
  priority int(11) NOT NULL default '3',
  planned_starting_date int(11) default NULL,
  planned_close_date int(11) default NULL,
  percent_complete int(11) default '1',
  keywords varchar(255) NOT NULL default '',
  release_id int(11) NOT NULL default '100',
  release varchar(255) NOT NULL default '',
  originator_name varchar(255) NOT NULL default '',
  originator_email varchar(255) NOT NULL default '',
  originator_phone varchar(255) NOT NULL default '',
  custom_tf1 varchar(255) NOT NULL default '',
  custom_tf2 varchar(255) NOT NULL default '',
  custom_tf3 varchar(255) NOT NULL default '',
  custom_tf4 varchar(255) NOT NULL default '',
  custom_tf5 varchar(255) NOT NULL default '',
  custom_tf6 varchar(255) NOT NULL default '',
  custom_tf7 varchar(255) NOT NULL default '',
  custom_tf8 varchar(255) NOT NULL default '',
  custom_tf9 varchar(255) NOT NULL default '',
  custom_tf10 varchar(255) NOT NULL default '',
  custom_ta1 text NOT NULL,
  custom_ta2 text NOT NULL,
  custom_ta3 text NOT NULL,
  custom_ta4 text NOT NULL,
  custom_ta5 text NOT NULL,
  custom_ta6 text NOT NULL,
  custom_ta7 text NOT NULL,
  custom_ta8 text NOT NULL,
  custom_ta9 text NOT NULL,
  custom_ta10 text NOT NULL,
  custom_sb1 int(11) NOT NULL default '100',
  custom_sb2 int(11) NOT NULL default '100',
  custom_sb3 int(11) NOT NULL default '100',
  custom_sb4 int(11) NOT NULL default '100',
  custom_sb5 int(11) NOT NULL default '100',
  custom_sb6 int(11) NOT NULL default '100',
  custom_sb7 int(11) NOT NULL default '100',
  custom_sb8 int(11) NOT NULL default '100',
  custom_sb9 int(11) NOT NULL default '100',
  custom_sb10 int(11) NOT NULL default '100',
  custom_df1 int(11) NOT NULL default '0',
  custom_df2 int(11) NOT NULL default '0',
  custom_df3 int(11) NOT NULL default '0',
  custom_df4 int(11) NOT NULL default '0',
  custom_df5 int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_id),
  KEY idx_bug_group_id (group_id)
) TYPE=MyISAM;



CREATE TABLE cookbook_canned_responses (
  bug_canned_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  title text,
  body text,
  order_id int(11) NOT NULL default '50',
  PRIMARY KEY  (bug_canned_id),
  KEY idx_bug_canned_response_group_id (group_id)
) TYPE=MyISAM;

CREATE TABLE cookbook_cc (
  bug_cc_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  added_by int(11) NOT NULL default '0',
  comment text NOT NULL,
  date int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_cc_id),
  KEY bug_id_idx (bug_id)
) TYPE=MyISAM;

CREATE TABLE cookbook_dependencies (
  item_id int(11) NOT NULL default '0',
  is_dependent_on_item_id int(11) NOT NULL default '0',
  is_dependent_on_item_id_artifact varchar(255) NOT NULL default '0',
  KEY idx_item_dependencies_bug_id (item_id),
  KEY idx_item_is_dependent_on_item_id (is_dependent_on_item_id)
) TYPE=MyISAM;

CREATE TABLE cookbook_field (
  bug_field_id int(11) NOT NULL auto_increment,
  field_name varchar(255) NOT NULL default '',
  display_type varchar(255) NOT NULL default '',
  display_size varchar(255) NOT NULL default '',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default '',
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  custom int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_field_id),
  KEY idx_bug_field_name (field_name)
) TYPE=MyISAM;

CREATE TABLE cookbook_field_usage (
  bug_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  use_it int(11) NOT NULL default '0',
  show_on_add int(11) NOT NULL default '0',
  show_on_add_members int(11) NOT NULL default '0',
  place int(11) NOT NULL default '0',
  custom_label varchar(255) default NULL,
  custom_description varchar(255) default NULL,
  custom_display_size varchar(255) default NULL,
  custom_empty_ok int(11) default NULL,
  custom_keep_history int(11) default NULL,
  transition_default_auth char(1) NOT NULL default 'A',
  KEY idx_bug_fu_field_id (bug_field_id),
  KEY idx_bug_fu_group_id (group_id)
) TYPE=MyISAM;

CREATE TABLE cookbook_field_value (
  bug_fv_id int(11) NOT NULL auto_increment,
  bug_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  value text NOT NULL,
  description text NOT NULL,
  order_id int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  email_ad text,
  send_all_flag int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_fv_id),
  KEY idx_bug_fv_field_id (bug_fv_id),
  KEY idx_bug_fv_group_id (group_id),
  KEY idx_bug_fv_value_id (value_id),
  KEY idx_bug_fv_status (status)
) TYPE=MyISAM;

CREATE TABLE cookbook_filter (
  filter_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  sql_clause text NOT NULL,
  is_active int(11) NOT NULL default '0',
  PRIMARY KEY  (filter_id)
) TYPE=MyISAM;

CREATE TABLE cookbook_history (
  bug_history_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  new_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  date int(11) default NULL,
  type int(11) default NULL,
  PRIMARY KEY  (bug_history_id),
  KEY idx_bug_history_bug_id (bug_id)
) TYPE=MyISAM;

CREATE TABLE cookbook_report_field (
  report_id int(11) NOT NULL default '100',
  field_name varchar(255) default NULL,
  show_on_query int(11) default NULL,
  show_on_result int(11) default NULL,
  place_query int(11) default NULL,
  place_result int(11) default NULL,
  col_width int(11) default NULL,
  KEY report_id_idx (report_id)
) TYPE=MyISAM;

CREATE TABLE cookbook_report (
  report_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '100',
  user_id int(11) NOT NULL default '100',
  name varchar(80) default NULL,
  description varchar(255) default NULL,
  scope char(1) NOT NULL default 'I',
  PRIMARY KEY  (report_id),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY scope_idx (scope)
) TYPE=MyISAM;

INSERT INTO cookbook (bug_id, group_id, status_id, severity, privacy, vote, category_id, submitted_by, assigned_to, date, summary, details, close_date, bug_group_id, resolution_id, category_version_id, platform_version_id, reproducibility_id, size_id, fix_release_id, plan_release_id, hours, component_version, fix_release, plan_release, priority, planned_starting_date, planned_close_date, percent_complete, keywords, release_id, release, originator_name, originator_email, originator_phone, custom_tf1, custom_tf2, custom_tf3, custom_tf4, custom_tf5, custom_tf6, custom_tf7, custom_tf8, custom_tf9, custom_tf10, custom_ta1, custom_ta2, custom_ta3, custom_ta4, custom_ta5, custom_ta6, custom_ta7, custom_ta8, custom_ta9, custom_ta10, custom_sb1, custom_sb2, custom_sb3, custom_sb4, custom_sb5, custom_sb6, custom_sb7, custom_sb8, custom_sb9, custom_sb10, custom_df1, custom_df2, custom_df3, custom_df4, custom_df5) VALUES (100,100,100,5,1,0,100,100,100,0,NULL,NULL,NULL,100,100,100,100,100,100,100,100,0.00,'','','',5,NULL,NULL,1,'',100,'','','','','','','','','','','','','','','','','','','','','','','','',100,100,100,100,100,100,100,100,100,100,0,0,0,0,0);




-- Here come the special part, where we defines fields
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (90,'bug_id','TF','6/10','Item ID','Unique item identifier','S',1,0,0,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (91,'group_id','TF','','Group ID','Unique project identifier','S',1,0,0,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (92,'submitted_by','SB','','Submitted by','User who originally submitted the item','S',1,1,0,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (93,'date','DF','10/15','Submitted on','Date and time of the initial item submission','S',1,0,0,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (94,'close_date','DF','10/15','Closed on','Date and time when the item status was changed to \'Closed\'','S',1,1,0,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (101,'status_id','SB','','Open/Closed','Most basic status','S',1,0,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (102,'severity','SB','','Severity','Impact of the item on the system (Critical, Major,...)','S',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (103,'category_id','SB','','Category','Generally high level modules or functionalities of the software (e.g. User interface, Configuration Manager, etc)','P',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (104,'assigned_to','SB','','Assigned to','Who is in charge of handling the item','S',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (105,'summary','TF','65/120','Summary/Question','One line description of the item','S',1,0,1,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (106,'details','TA','65/45','Recipe','Answer to the question, recipe','S',1,0,1,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (107,'bug_group_id','SB','','Item Group','Characterizes the nature of the item (e.g. Crash Error, Documentation Typo, Installation Problem, etc','P',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (108,'resolution_id','SB','','Status','Current status of the item: only Approved Items will be shown to end-users','S',1,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (109,'privacy','SB','','Privacy','Determines whether the item can be seen by members of the project only or anybody.','S',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (110,'vote','TF','6/10','Votes','How many votes this item received.','S',0,1,0,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (200,'category_version_id','SB','','Component Version','Version of the System Component (aka Item Category) impacted by the item','P',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (201,'platform_version_id','SB','','Operating System','Operating System impacted by the problem','P',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (205,'comment_type_id','SB','','Comment Type','Nature of the comment','P',1,1,0,1,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (211,'priority','SB','','Importance','How important is this recipe','S',1,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (212,'keywords','TF','60/120','Keywords','List of comma separated keywords associated with an item','S',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (213,'release_id','SB','','Release','Release (global version number) impacted by the item','P',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (215,'originator_name','TF','20/40','Originator Name','Name of the person who submitted the item (if different from the submitter field)','S',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (216,'originator_email','TF','20/40','Originator Email','Email address of the person who submitted the item (if different from the submitter field, add address to CC list)','S',0,1,1,0,0);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (217,'originator_phone','TF','10/40','Originator Phone','Phone number of the person who submitted the item','S',0,1,1,0,0);


INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (300,'custom_tf1','TF','10/15','Custom Text Field #1','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (301,'custom_tf2','TF','10/15','Custom Text Field #2','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (302,'custom_tf3','TF','10/15','Custom Text Field #3','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (303,'custom_tf4','TF','10/15','Custom Text Field #4','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (304,'custom_tf5','TF','10/15','Custom Text Field #5','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (305,'custom_tf6','TF','10/15','Custom Text Field #6','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (306,'custom_tf7','TF','10/15','Custom Text Field #7','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (307,'custom_tf8','TF','10/15','Custom Text Field #8','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (308,'custom_tf9','TF','10/15','Custom Text Field #9','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (309,'custom_tf10','TF','10/15','Custom Text Field #10','Customizable Text Field (one line, up to 255 characters','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (400,'custom_ta1','TA','60/3','Custom Text Area #1','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (401,'custom_ta2','TA','60/3','Custom Text Area #2','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (402,'custom_ta3','TA','60/3','Custom Text Area #3','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (403,'custom_ta4','TA','60/3','Custom Text Area #4','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (404,'custom_ta5','TA','60/3','Custom Text Area #5','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (405,'custom_ta6','TA','60/3','Custom Text Area #6','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (406,'custom_ta7','TA','60/3','Custom Text Area #7','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (407,'custom_ta8','TA','60/3','Custom Text Area #8','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (408,'custom_ta9','TA','60/3','Custom Text Area #9','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (409,'custom_ta10','TA','60/3','Custom Text Area #10','Customizable Text Area (multi-line text)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (500,'custom_sb1','SB','','Custom Select Box #1','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (501,'custom_sb2','SB','','Custom Select Box #2','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (502,'custom_sb3','SB','','Custom Select Box #3','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (503,'custom_sb4','SB','','Custom Select Box #4','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (504,'custom_sb5','SB','','Custom Select Box #5','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (505,'custom_sb6','SB','','Custom Select Box #6','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (506,'custom_sb7','SB','','Custom Select Box #7','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (507,'custom_sb8','SB','','Custom Select Box #8','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (508,'custom_sb9','SB','','Custom Select Box #9','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);
INSERT INTO cookbook_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (509,'custom_sb10','SB','','Custom Select Box #10','Customizable Select Box (pull down menu with predefined values)','P',0,1,1,0,1);

INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (90,100,1,0,0,10,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (91,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (92,100,1,0,0,20,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (93,100,1,0,0,40,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (94,100,1,0,0,50,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (101,100,1,0,0,600,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (102,100,0,0,0,200,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (103,100,1,1,1,100,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (104,100,1,0,1,500,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (105,100,1,1,1,700000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (106,100,1,1,1,700001,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (107,100,0,1,1,300,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (108,100,1,0,1,400,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (109,100,1,0,1,402,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (110,100,0,0,0,405,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (200,100,0,0,0,1000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (201,100,0,0,0,1100,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (202,100,0,0,0,1200,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (203,100,0,0,0,1300,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (204,100,0,0,0,1400,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (205,100,1,0,0,1500,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (206,100,0,0,0,1700,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (207,100,0,0,0,1600,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (208,100,0,0,0,1800,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (209,100,0,0,0,1900,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (210,100,0,0,0,2000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (211,100,1,1,1,200,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (212,100,0,0,0,3000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (213,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (214,100,0,0,0,800,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (215,100,0,0,0,550,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (216,100,0,0,0,560,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (217,100,0,0,0,570,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (218,100,0,0,0,55,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (219,100,0,0,0,56,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (220,100,0,0,0,500,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (300,100,0,0,0,30000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (301,100,0,0,0,30100,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (302,100,0,0,0,30200,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (303,100,0,0,0,30300,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (304,100,0,0,0,30400,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (305,100,0,0,0,30500,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (306,100,0,0,0,30600,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (307,100,0,0,0,30700,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (308,100,0,0,0,30800,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (309,100,0,0,0,30900,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (400,100,0,0,0,40000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (401,100,0,0,0,40100,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (402,100,0,0,0,40200,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (403,100,0,0,0,40300,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (404,100,0,0,0,40400,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (405,100,0,0,0,40500,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (406,100,0,0,0,40600,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (407,100,0,0,0,40700,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (408,100,0,0,0,40800,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (409,100,0,0,0,40900,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (500,100,0,0,0,50000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (501,100,0,0,0,50100,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (502,100,0,0,0,50200,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (503,100,0,0,0,50300,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (504,100,0,0,0,50400,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (505,100,0,0,0,50500,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (506,100,0,0,0,50600,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (507,100,0,0,0,50700,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (508,100,0,0,0,50800,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (509,100,0,0,0,50900,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (600,100,0,0,0,60000,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (601,100,0,0,0,60100,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (602,100,0,0,0,60200,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (603,100,0,0,0,60300,NULL,NULL,NULL,NULL,NULL,'A');
INSERT INTO cookbook_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, transition_default_auth) VALUES (604,100,0,0,0,60400,NULL,NULL,NULL,NULL,NULL,'A');


INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (101,101,100,1,'Open','The item has been submitted',20,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (102,101,100,3,'Closed','The item is no longer active. See the Status field for details.',400,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (131,102,100,1,'1 - Wish','Issue which is mainly a matter of taste',10,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (132,102,100,2,'2','',20,'H',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (133,102,100,3,'3 - Minor','Issue which doesn\'t affect the object\'s usefulness, and is presumably trivial to fix',30,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (134,102,100,4,'4','',40,'H',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (135,102,100,5,'5 - Normal','',50,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (136,102,100,6,'6','',60,'H',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (137,102,100,7,'7 - Important','Issue which has a major effect on the usability of the object, without rendering it completely unusable to everyone',70,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (138,102,100,8,'8 - Blocker','Issue which makes the object in question unusable or mostly so, or causes data loss',80,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (139,102,100,9,'9 - Security','Issue which introduces a security breach',90,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (150,103,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (160,107,100,100,'None','',10,'P',NULL,0);
-- INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (170,108,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (171,108,100,1,'Approved','The recipe was approved and is currently active',30,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (172,108,100,2,'Refused/Outdated','The recipe was refused or is updated - it is not active',130,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (179,108,100,9,'Draft','This recipe is currently being worked on - it is not active',70,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (180,108,100,10,'Ready For Review','This recipe should be checked for approval - it is not active',65,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (200,200,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (210,201,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (211,201,100,110,'GNU/Linux','',20,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (212,201,100,120,'Microsoft Windows','',30,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (213,201,100,130,'*BSD','',40,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (214,201,100,140,'Mac OS','',50,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (220,202,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (221,202,100,110,'Every Time','',20,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (222,202,100,120,'Intermittent','',30,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (223,202,100,130,'Once','',40,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (240,203,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (241,203,100,110,'Low <30','',20,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (242,203,100,120,'Medium 30 - 200','',30,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (243,203,100,130,'High >200','',40,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (250,204,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (260,205,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (270,207,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (281,211,100,1,'1 - Low','',10,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (282,211,100,2,'2','',20,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (283,211,100,3,'3 - Normal','',30,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (284,211,100,4,'4','',40,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (285,211,100,5,'5 - High','',50,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (300,213,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (400,500,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (401,501,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (402,502,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (403,503,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (404,504,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (405,505,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (406,506,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (407,507,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (408,508,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (409,509,100,100,'None','',10,'P',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (719,220,100,1,'0%','',1,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (720,220,100,10,'10%','',10,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (721,220,100,20,'20%','',20,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (722,220,100,30,'30%','',30,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (723,220,100,40,'40%','',40,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (724,220,100,50,'50%','',50,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (725,220,100,60,'60%','',60,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (726,220,100,70,'70%','',70,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (727,220,100,80,'80%','',80,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (728,220,100,90,'90%','',90,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (729,220,100,100,'100%','',100,'A',NULL,0);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (111,109,100,1,'Public','This item can be seen by everybody.',10,'A','',1);
INSERT INTO cookbook_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status, email_ad, send_all_flag) VALUES (112,109,100,2,'Private','This item can be seen only by project members.',20,'A','',1);


INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'bug_id',0,1,NULL,1,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'category_id',1,0,50,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'assigned_to',1,1,20,40,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'status_id',1,0,10,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'resolution_id',1,1,15,22,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100,'planned_close_date',0,1,NULL,50,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'bug_id',0,1,NULL,1,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'category_id',1,1,50,21,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'status_id',1,0,10,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'resolution_id',1,1,15,23,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'summary',1,1,501,20,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'planned_starting_date',0,1,NULL,900,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'planned_close_date',0,1,NULL,880,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'close_date',0,1,NULL,879,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'submitted_by',1,1,30,41,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'assigned_to',1,1,31,40,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'priority',1,0,402,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'bug_id',0,1,NULL,1,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'vote',0,1,NULL,2,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'category_id',1,0,50,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'assigned_to',1,1,20,40,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'status_id',1,0,10,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'resolution_id',1,1,15,22,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'summary',0,1,NULL,20,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (102,'date',0,1,NULL,50,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'bug_id',0,1,NULL,1,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'vote',0,1,NULL,2,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'category_id',1,0,50,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'assigned_to',1,1,20,40,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'status_id',1,0,10,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'resolution_id',1,1,15,22,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'summary',0,1,NULL,20,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'planned_starting_date',1,1,1,900,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (112,'planned_close_date',1,1,2,880,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (101,'details',1,0,502,NULL,NULL);


INSERT INTO cookbook_report (report_id, group_id, user_id, name, description, scope) VALUES (100,100,100,'Default','The system default query form','S');
INSERT INTO cookbook_report (report_id, group_id, user_id, name, description, scope) VALUES (101,100,100,'Advanced','The second, more complex, default query form','S');
INSERT INTO cookbook_report (report_id, group_id, user_id, name, description, scope) VALUES (112,100,100,'By Date','Based on dates','S');


-- This will make possible to have query forms only for the sober mode.
-- We hack the query form SCOPE that can be Project (P) or System (S) by
-- adding SSB, meaning System SoBer. We may later add a PSB.

ALTER TABLE cookbook_report CHANGE scope scope CHAR( 3 ) NOT NULL DEFAULT 'I';

-- apply the changes on other trackers, just in case
ALTER TABLE task_report CHANGE scope scope CHAR( 3 ) NOT NULL DEFAULT 'I';
ALTER TABLE support_report CHANGE scope scope CHAR( 3 ) NOT NULL DEFAULT 'I';
ALTER TABLE patch_report CHANGE scope scope CHAR( 3 ) NOT NULL DEFAULT 'I';
ALTER TABLE bugs_report CHANGE scope scope CHAR( 3 ) NOT NULL DEFAULT 'I';

-- add the sober query form: minimalistic
-- (102 is used by : By Votes)
INSERT INTO cookbook_report (report_id, group_id, user_id, name, description, scope) VALUES (103,100,100,'Sober Basic','The system default sober query form','SSB');

INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (103,'category_id',1,1,50,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (103,'priority',1,1,402,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (103,'summary',0,1,501,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (103,'resolution_id',0,1,501,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (103,'bug_id',0,1,NULL,1,NULL);



INSERT INTO cookbook_report (report_id, group_id, user_id, name, description, scope) VALUES (104,100,100,'Sober Advanced','The second, more complexe, default sober query form','SSB');

INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (104,'category_id',1,1,50,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (104,'priority',1,1,402,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (104,'details',1,0,502,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (104,'summary',1,1,501,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (104,'resolution_id',0,1,501,NULL,NULL);
INSERT INTO cookbook_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (104,'bug_id',0,1,NULL,1,NULL);
