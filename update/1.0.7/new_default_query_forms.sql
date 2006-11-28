# Do it the very dirty way: delete all the default for status,
# reinsert new ones:

# Delete old ones
DELETE FROM bugs_report_field WHERE report_id='100' OR report_id='101';
DELETE FROM task_report_field WHERE report_id='100' OR report_id='101';
DELETE FROM support_report_field WHERE report_id='100' OR report_id='101';
DELETE FROM patch_report_field WHERE report_id='100' OR report_id='101';



#  show_on_query int(11) default NULL,
#  show_on_result int(11) default NULL,
#  place_query int(11) default NULL,
#  place_result int(11) default NULL,
#  col_width int(11) default NULL,



## Bugs 

INSERT INTO bugs_report_field VALUES (100,'bug_id',0,1,NULL,1,NULL);
INSERT INTO bugs_report_field VALUES (100,'category_id',1,0,50,NULL,NULL);
INSERT INTO bugs_report_field VALUES (100,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO bugs_report_field VALUES (100,'assigned_to',1,1,20,40,NULL);
INSERT INTO bugs_report_field VALUES (100,'status_id',1,0,10,NULL,NULL);
INSERT INTO bugs_report_field VALUES (100,'resolution_id',1,1,15,22,NULL);
INSERT INTO bugs_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO bugs_report_field VALUES (100,'date',0,1,NULL,50,NULL);


INSERT INTO bugs_report_field VALUES (101,'bug_id',0,1,NULL,1,NULL);
INSERT INTO bugs_report_field VALUES (101,'category_id',1,1,50,21,NULL);
INSERT INTO bugs_report_field VALUES (101,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO bugs_report_field VALUES (101,'status_id',1,0,10,NULL,NULL);
INSERT INTO bugs_report_field VALUES (101,'resolution_id',1,1,15,23,NULL);
INSERT INTO bugs_report_field VALUES (101,'summary',0,1,NULL,20,NULL);
INSERT INTO bugs_report_field VALUES (101,'date',1,1,700,900,NULL);
INSERT INTO bugs_report_field VALUES (101,'submitted_by',1,1,30,41,NULL);
INSERT INTO bugs_report_field VALUES (101,'assigned_to',1,1,31,40,NULL);
INSERT INTO bugs_report_field VALUES (101,'priority',1,0,402,NULL,NULL);
INSERT INTO bugs_report_field VALUES (101,'severity',1,1,401,22,NULL);


## Support 

INSERT INTO support_report_field VALUES (100,'bug_id',0,1,NULL,1,NULL);
INSERT INTO support_report_field VALUES (100,'category_id',1,0,50,NULL,NULL);
INSERT INTO support_report_field VALUES (100,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO support_report_field VALUES (100,'assigned_to',1,1,20,40,NULL);
INSERT INTO support_report_field VALUES (100,'status_id',1,0,10,NULL,NULL);
INSERT INTO support_report_field VALUES (100,'resolution_id',1,1,15,22,NULL);
INSERT INTO support_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO support_report_field VALUES (100,'date',0,1,NULL,50,NULL);


INSERT INTO support_report_field VALUES (101,'bug_id',0,1,NULL,1,NULL);
INSERT INTO support_report_field VALUES (101,'category_id',1,1,50,21,NULL);
INSERT INTO support_report_field VALUES (101,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO support_report_field VALUES (101,'status_id',1,0,10,NULL,NULL);
INSERT INTO support_report_field VALUES (101,'resolution_id',1,1,15,23,NULL);
INSERT INTO support_report_field VALUES (101,'summary',0,1,NULL,20,NULL);
INSERT INTO support_report_field VALUES (101,'date',1,1,700,900,NULL);
INSERT INTO support_report_field VALUES (101,'submitted_by',1,1,30,41,NULL);
INSERT INTO support_report_field VALUES (101,'assigned_to',1,1,31,40,NULL);
INSERT INTO support_report_field VALUES (101,'priority',1,0,402,NULL,NULL);
INSERT INTO support_report_field VALUES (101,'severity',1,1,401,22,NULL);


## Task 

INSERT INTO task_report_field VALUES (100,'bug_id',0,1,NULL,1,NULL);
INSERT INTO task_report_field VALUES (100,'category_id',1,0,50,NULL,NULL);
INSERT INTO task_report_field VALUES (100,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO task_report_field VALUES (100,'assigned_to',1,1,20,40,NULL);
INSERT INTO task_report_field VALUES (100,'status_id',1,0,10,NULL,NULL);
INSERT INTO task_report_field VALUES (100,'resolution_id',1,1,15,22,NULL);
INSERT INTO task_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO task_report_field VALUES (100,'planned_close_date',0,1,NULL,50,NULL);


INSERT INTO task_report_field VALUES (101,'bug_id',0,1,NULL,1,NULL);
INSERT INTO task_report_field VALUES (101,'category_id',1,1,50,21,NULL);
INSERT INTO task_report_field VALUES (101,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO task_report_field VALUES (101,'status_id',1,0,10,NULL,NULL);
INSERT INTO task_report_field VALUES (101,'resolution_id',1,1,15,23,NULL);
INSERT INTO task_report_field VALUES (101,'summary',0,1,NULL,20,NULL);
INSERT INTO task_report_field VALUES (101,'planned_starting_date',1,1,700,900,NULL);
INSERT INTO task_report_field VALUES (101,'planned_close_date',1,1,680,880,NULL);
INSERT INTO task_report_field VALUES (101,'close_date',0,1,NULL,879,NULL);
INSERT INTO task_report_field VALUES (101,'submitted_by',1,1,30,41,NULL);
INSERT INTO task_report_field VALUES (101,'assigned_to',1,1,31,40,NULL);
INSERT INTO task_report_field VALUES (101,'priority',1,0,402,NULL,NULL);



## Patch 

INSERT INTO patch_report_field VALUES (100,'bug_id',0,1,NULL,1,NULL);
INSERT INTO patch_report_field VALUES (100,'category_id',1,0,50,NULL,NULL);
INSERT INTO patch_report_field VALUES (100,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO patch_report_field VALUES (100,'assigned_to',1,1,20,40,NULL);
INSERT INTO patch_report_field VALUES (100,'status_id',1,0,10,NULL,NULL);
INSERT INTO patch_report_field VALUES (100,'resolution_id',1,1,15,22,NULL);
INSERT INTO patch_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO patch_report_field VALUES (100,'date',0,1,NULL,50,NULL);


INSERT INTO patch_report_field VALUES (101,'bug_id',0,1,NULL,1,NULL);
INSERT INTO patch_report_field VALUES (101,'category_id',1,1,50,21,NULL);
INSERT INTO patch_report_field VALUES (101,'bug_group_id',1,0,55,NULL,NULL);
INSERT INTO patch_report_field VALUES (101,'status_id',1,0,10,NULL,NULL);
INSERT INTO patch_report_field VALUES (101,'resolution_id',1,1,15,23,NULL);
INSERT INTO patch_report_field VALUES (101,'summary',0,1,NULL,20,NULL);
INSERT INTO patch_report_field VALUES (101,'date',1,1,700,900,NULL);
INSERT INTO patch_report_field VALUES (101,'submitted_by',1,1,30,41,NULL);
INSERT INTO patch_report_field VALUES (101,'assigned_to',1,1,31,40,NULL);
INSERT INTO patch_report_field VALUES (101,'priority',1,0,402,NULL,NULL);
