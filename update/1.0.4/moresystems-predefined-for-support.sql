## MORE OS

UPDATE support_field_usage SET use_it = '1', show_on_add = '1', show_on_add_members = '1' WHERE bug_field_id = '201' AND group_id = '100' LIMIT 1;

INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (211,201,100,110,'GNU/Linux','',20,'P');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (212,201,100,120,'*BSD','',40,'P');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (213,201,100,130,'Microsoft Windows','',50,'P');
INSERT INTO support_field_value (bug_fv_id,bug_field_id,group_id,value_id,value,description,order_id,status) VALUES (214,201,100,140,'Mac OS','',60,'P');
