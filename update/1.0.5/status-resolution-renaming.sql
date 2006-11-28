## status -> open

UPDATE support_field SET label='Open/Closed',description='Most basic status of the item: is the item considered as dealth with or not.' WHERE bug_field_id='101' LIMIT 1 ;

UPDATE bugs_field SET label='Open/Closed',description='Most basic status of the item: is the item considered as dealth with or not.' WHERE bug_field_id='101' LIMIT 1 ;

UPDATE task_field SET label='Open/Closed',description='Most basic status of the item: is the item considered as dealth with or not.' WHERE bug_field_id='101' LIMIT 1 ;

UPDATE patch_field SET label='Open/Closed',description='Most basic status of the item: is the item considered as dealth with or not.' WHERE bug_field_id='101' LIMIT 1 ;

## resolution -> status 

UPDATE support_field SET label='Status',description='Current status of the item' WHERE bug_field_id='108' LIMIT 1 ;

UPDATE bugs_field SET label='Status',description='Current status of the item' WHERE bug_field_id='108' LIMIT 1 ;

UPDATE task_field SET label='Status',description='Current status of the item' WHERE bug_field_id='108' LIMIT 1 ; 

UPDATE patch_field SET label='Status',description='Current status of the item' WHERE bug_field_id='108' LIMIT 1 ;