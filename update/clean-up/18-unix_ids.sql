-- Add uidNumber and gidNumber fields so that the database can be used
-- directly by libnss-mysql-bg

-- default=NULL, because default=0 would mean "root".  Note that if
-- you feed a NULL value to libnss-mysql-bg it will segfault - but you
-- should know better, and we can be sure you'll fix the security
-- issue :p

ALTER TABLE `user`
  ADD `uidNumber` int AFTER `status`,
  ADD INDEX `idx_uidNumber` (`uidNumber`);
ALTER TABLE `groups`
  ADD `gidNumber` int AFTER `unix_group_name`,
  ADD INDEX `idx_gidNumber` (`gidNumber`);
INSERT INTO groups (unix_group_name, group_name, status)
  VALUES ('svusers', 'Default system group', 'A');

-- Cache membership system ids, needed for efficient getgrent +
-- libnss-mysql usage
ALTER TABLE `user_group`
  ADD `cache_uidNumber` int AFTER `admin_flags`,
  ADD `cache_gidNumber` int AFTER `cache_uidNumber`,
  ADD `cache_user_name` varchar(33) AFTER `cache_gidNumber`,
  ADD INDEX `idx_cache_uidNumber` (`cache_uidNumber`),
  ADD INDEX `idx_cache_gidNumber` (`cache_gidNumber`);
