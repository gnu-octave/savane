# Original email should not be visible to anyone but anonymous users
# (see bug #4428)
UPDATE bugs_field_usage SET show_on_add='2' WHERE bug_field_id='216' AND show_on_add='3';
UPDATE support_field_usage SET show_on_add='2' WHERE bug_field_id='216' AND show_on_add='3';
UPDATE task_field_usage SET show_on_add='2' WHERE bug_field_id='216' AND show_on_add='3';
UPDATE patch_field_usage SET show_on_add='2' WHERE bug_field_id='216' AND show_on_add='3';