-- Old installs may have an inconsistent 'group_id' in
-- 'group_forum_id'.  If you use sv_cleaner --big-cleanup maybe these
-- entries were already mistaken deleted.
UPDATE forum_group_list JOIN news_bytes ON group_forum_id=forum_id
  SET forum_group_list.group_id = news_bytes.group_id;

-- Install key on 'user_name' so we can look for duplicates
-- max(username)=max(groupname)=16
-- 33 = max(username) + length('-') + max(groupname)  (i.e. a squad username)
ALTER TABLE `user` MODIFY `user_name` varchar(33) NOT NULL;
ALTER TABLE `user` ADD INDEX (`user_name`);

-- Install key on (user_id,group_id) so we can look for duplicates
ALTER TABLE user_group ADD INDEX `pk_idx` (`user_id`, `group_id`);
