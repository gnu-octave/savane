# no need to force percent_complete to be set, none is acceptable
UPDATE `task_field` SET `required` = '0',
 `empty_ok` = '1' WHERE `field_name` = 'percent_complete';
