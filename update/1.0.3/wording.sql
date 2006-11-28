#
# Update wording
#

# Replace Later by Postpone
#
UPDATE `bugs_field_value` SET `value` = 'Postponed',
`description` = 'The item will be fixed later (no date given)',
`email_ad` = NULL WHERE `bug_fv_id` = '174' LIMIT 1 ;
UPDATE `task_field_value` SET `value` = 'Postponed',
`description` = 'The item will be fixed later (no date given)',
`email_ad` = NULL WHERE `bug_fv_id` = '174' LIMIT 1 ;
UPDATE `patch_field_value` SET `value` = 'Postponed',
`description` = 'The item will be fixed later (no date given)',
`email_ad` = NULL WHERE `bug_fv_id` = '174' LIMIT 1 ;
UPDATE `support_field_value` SET `value` = 'Postponed',
`description` = 'The item will be fixed later (no date given)',
`email_ad` = NULL WHERE `bug_fv_id` = '174' LIMIT 1 ;


# Replace Works For Me by Unreproducible
#
UPDATE `bugs_field_value` SET `value` = 'Unreproducible',
`description` = 'Project members are unable to reproduce the behavior mentioned',
`email_ad` = NULL WHERE `bug_fv_id` = '176' LIMIT 1 ;
UPDATE `support_field_value` SET `value` = 'Unreproducible',
`description` = 'Project members are unable to reproduce the behavior mentioned',
`email_ad` = NULL WHERE `bug_fv_id` = '176' LIMIT 1 ;

# Task and Patch cannot be unreproducible, this entry just dont fit
DELETE FROM `patch_field_value` WHERE `bug_fv_id` = '176' LIMIT 1 ;
DELETE FROM `task_field_value` WHERE `bug_fv_id` = '176' LIMIT 1 ;

# Patches are about applying or not.
UPDATE `patch_field_value` SET `value` = 'Applied',
`description` = 'The item was applied' WHERE `bug_fv_id` = '171' LIMIT 1 ;
UPDATE `patch_field_value` SET `value` = 'Wont Apply',
`description` = 'The patch won\'t be applied' WHERE `bug_fv_id` = '173' LIMIT 1 ;

# Task and support request are usually "done", not "fixed"
UPDATE `support_field_value` SET `value` = 'Done',
`description` = 'The item is done' WHERE `bug_fv_id` = '171' LIMIT 1 ;
UPDATE `task_field_value` SET `value` = 'Done',
`description` = 'The item is done' WHERE `bug_fv_id` = '171' LIMIT 1 ;
UPDATE `support_field_value` SET `value` = 'Wont Do',
`description` = 'The item won\'t be done' WHERE `bug_fv_id` = '173' LIMIT 1 ;
UPDATE `task_field_value` SET `value` = 'Cancelled',
`description` = 'The item won\'t be done' WHERE `bug_fv_id` = '173' LIMIT 1 ;

