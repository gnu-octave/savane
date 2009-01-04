-- Now that files are external we can remove the file contents from
-- the database
ALTER TABLE `trackers_file` DROP `file`;
