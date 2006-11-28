
## 1:
# Add summary and original submission the advanced query form

#  show_on_query int(11) default NULL,
#  show_on_result int(11) default NULL,
#  place_query int(11) default NULL,
#  place_result int(11) default NULL,
#  col_width int(11) default NULL,

UPDATE bugs_report_field SET show_on_query='1',place_query='501' WHERE report_id='101' and field_name='summary';  
INSERT INTO bugs_report_field VALUES (101,'details',1,0,502,NULL,NULL);

UPDATE task_report_field SET show_on_query='1',place_query='501' WHERE report_id='101' and field_name='summary';  
INSERT INTO task_report_field VALUES (101,'details',1,0,502,NULL,NULL);

UPDATE patch_report_field SET show_on_query='1',place_query='501' WHERE report_id='101' and field_name='summary';  
INSERT INTO patch_report_field VALUES (101,'details',1,0,502,NULL,NULL);

UPDATE support_report_field SET show_on_query='1',place_query='501' WHERE report_id='101' and field_name='summary';  
INSERT INTO support_report_field VALUES (101,'details',1,0,502,NULL,NULL);

## 2:
# Remove the date from the Advanced form: in many cases, it is inconvenient
# (having submitted on set is in many cases hiding everything)

UPDATE support_report_field SET show_on_query='0',place_query=NULL WHERE report_id='101' and field_name='date';  
UPDATE bugs_report_field SET show_on_query='0',place_query=NULL WHERE report_id='101' and field_name='date';  
UPDATE patch_report_field SET show_on_query='0',place_query=NULL WHERE report_id='101' and field_name='date';
UPDATE task_report_field SET show_on_query='0',place_query=NULL WHERE report_id='101' and field_name='planned_starting_date';  
UPDATE task_report_field SET show_on_query='0',place_query=NULL WHERE report_id='101' and field_name='planned_close_date';   
