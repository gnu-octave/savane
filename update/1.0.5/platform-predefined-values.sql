# MAKE PREDEFINED VALUES INCLUDED IN 1.0.4 ONLY ACTIVE, NOT PERMANENT

UPDATE support_field_value SET status = 'A' WHERE bug_field_id = '201' AND status='P' AND value_id<>'100';
 

## INSERT NEW PREDEFINED VALUES

INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (211,201,100,110,'GNU/Linux','',20,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (212,201,100,120,'Microsoft Windows','',30,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (213,201,100,130,'*BSD','',40,'A');
INSERT INTO task_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (214,201,100,140,'Mac OS','',50,'A');


INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (211,201,100,110,'GNU/Linux','',20,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (212,201,100,120,'Microsoft Windows','',30,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (213,201,100,130,'*BSD','',40,'A');
INSERT INTO bugs_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (214,201,100,140,'Mac OS','',50,'A');