# This was not changed recently, but somehow some SQL files we not up to 
# date in this regard
UPDATE cookbook_report SET name='Basic' WHERE report_id=100 LIMIT 1;
UPDATE task_report SET name='Basic' WHERE report_id=100 LIMIT 1;
UPDATE patch_report SET name='Basic' WHERE report_id=100 LIMIT 1;
UPDATE bugs_report SET name='Basic' WHERE report_id=100 LIMIT 1;
UPDATE support_report SET name='Basic' WHERE report_id=100 LIMIT 1;
