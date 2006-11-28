# Do it the very dirty way: delete all the default for status,
# reinsert new ones:

# Delete old ones
DELETE FROM task_field_value WHERE group_id='100' AND bug_field_id='108';
DELETE FROM patch_field_value WHERE group_id='100' AND bug_field_id='108';
DELETE FROM support_field_value WHERE group_id='100' AND bug_field_id='108';
DELETE FROM bugs_field_value WHERE group_id='100' AND bug_field_id='108';




# New ones (from db/mysql...)
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (170,108,100,100,'None','',10,'P');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (171,108,100,1,'Fixed','The bug was resolved',30,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (172,108,100,2,'Invalid','This item is not valid for some reason (see comments)',130,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (178,108,100,3,'Wont Fix','The issue won\'t be fixed (see comments)',40,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (173,108,100,8,'Need Info','More information is need to be able to handle this item',90,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (174,108,100,4,'Postponed','The issue will be handled later',80,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (175,108,100,5,'Remind','This field is a deprecated duplicate of Postponed, existing only for historical reason',60,'H');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (176,108,100,6,'Works For Me','No problem found for the project team',60,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (177,108,100,7,'Duplicate','This item is already covered by another item',120,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (179,108,100,9,'In Progress','This item is currently being worked on',70,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (180,108,100,10,'Ready For Test','This item should be tested now',65,'A');


INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (170,108,100,100,'None','',10,'P');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (171,108,100,1,'Done','The item was succesfuly done',30,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (172,108,100,2,'Invalid','This item is not valid for some reason (see comments)',130,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (178,108,100,3,'Wont Do','The item will not be carried out (see comments)',40,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (173,108,100,8,'Need Info','More information is need to be able to handle this item',90,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (174,108,100,4,'Postponed','The issue will be handled later',80,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (175,108,100,5,'Remind','This field is a deprecated duplicate of Postponed, existing only for historical reason',60,'H');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (176,108,100,6,'Works For Me','No problem found for the project team',60,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (177,108,100,7,'Duplicate','This item is already covered by another item',120,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (179,108,100,9,'In Progress','This item is currently being worked on',70,'A');
INSERT INTO patch_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (180,108,100,10,'Ready For Test','This item should be tested now',65,'A');


INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (170,108,100,100,'None','',10,'P');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (171,108,100,1,'Done','The item was succesfuly done',30,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (172,108,100,2,'Invalid','This item is not valid for some reason (see comments)',130,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (178,108,100,3,'Wont Do','The item will not be carried out (see comments)',40,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (173,108,100,8,'Need Info','More information is need to be able to handle this item',90,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (174,108,100,4,'Postponed','The issue will be handled later',80,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (175,108,100,5,'Remind','This field is a deprecated duplicate of Postponed, existing only for historical reason',60,'H');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (176,108,100,6,'Works For Me','No problem found for the project team',60,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (177,108,100,7,'Duplicate','This item is already covered by another item',120,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (179,108,100,9,'In Progress','This item is currently being worked on',70,'A');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (180,108,100,10,'Ready For Test','This item should be tested now',65,'A');


INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (170,108,100,100,'None','',10,'P');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (171,108,100,1,'Done','The item was succesfuly done',30,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (172,108,100,2,'Invalid','This item is not valid for some reason (see comments)',130,'H');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (178,108,100,3,'Cancelled','The item will not be carried out (see comments)',40,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (173,108,100,8,'Need Info','More information is need to be able to handle this item',90,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (174,108,100,4,'Postponed','The issue will be handled later',80,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (176,108,100,6,'Works For Me','No problem found for the project team',60,'H');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (177,108,100,7,'Duplicate','This item is already covered by another item',120,'H');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (179,108,100,9,'In Progress','This item is currently being worked on',70,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (180,108,100,10,'Ready For Test','This item should be tested now',65,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (175,108,100,5,'Remind','This field is a deprecated duplicate of Postponed, existing only for historical reason',60,'H');