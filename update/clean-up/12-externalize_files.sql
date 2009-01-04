-- Now that files are external we can remove the file contents from
-- the database. We do this in a separate update file because this
-- requires SQL privileges.
ALTER TABLE `trackers_file` DROP `file`;
