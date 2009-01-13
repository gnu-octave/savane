-- Some fields in the `bugs` table can be NULL. The
-- `bugs_history`.`old_value` field however is NOT NULL. In some
-- situations this causes an error, and with the stricter checks of
-- the new Savane code, this is blocker.  Since some fields can be
-- NULL, it sounds logical that their history can be set to NULL
-- too. Let's fix this.
ALTER TABLE bugs_history MODIFY `old_value` text;
ALTER TABLE cookbook_history MODIFY `old_value` text;
ALTER TABLE patch_history MODIFY `old_value` text;
ALTER TABLE task_history MODIFY `old_value` text;
ALTER TABLE support_history MODIFY `old_value` text;
