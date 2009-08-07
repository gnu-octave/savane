-- Add uidNumber and gidNumber fields so that the database can be used
-- directly by libnss-mysql-bg

ALTER TABLE `user`
  ADD `uidNumber` INT NOT NULL DEFAULT '0' AFTER `status`;
ALTER TABLE `groups`
  ADD `gidNumber` INT NOT NULL DEFAULT '0' AFTER `unix_group_name`;
INSERT INTO groups (unix_group_name, group_name, status)
  VALUES ('svusers', 'Default system group', 'A');

-- Cache membership system ids, needed for efficient getgrent +
-- libnss-mysql usage
ALTER TABLE `user_group`
  ADD `cache_uidNumber` INT NOT NULL DEFAULT '0' AFTER `admin_flags`,
  ADD `cache_gidNumber` INT NOT NULL DEFAULT '0' AFTER `cache_uidNumber`,
  ADD `cache_user_name` VARCHAR(33) AFTER `cache_gidNumber`,
  ADD INDEX `idx_cache_uidNumber` (`cache_uidNumber`),
  ADD INDEX `idx_cache_gidNumber` (`cache_gidNumber`);
UPDATE user_group, user, groups
  SET user_group.cache_uidNumber = user.uidNumber,
      user_group.cache_gidNumber = groups.gidNumber,
      user_group.cache_user_name = user.user_name
  WHERE user_group.user_id = user.user_id
    AND user_group.group_id = groups.group_id;
