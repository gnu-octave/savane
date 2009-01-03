-- Remove date fields from the cookbook query forms - they don't exist in cookbook_report_field
DELETE FROM cookbook_report_field WHERE field_name IN ('planned_close_date', 'planned_starting_date');
