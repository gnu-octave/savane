-- Old installs may have an inconsistent 'group_id' in
-- 'group_forum_id'.  If you use sv_cleaner --big-cleanup maybe these
-- entries were already mistaken deleted.
UPDATE forum_group_list JOIN news_bytes ON group_forum_id=forum_id
  SET forum_group_list.group_id = news_bytes.group_id;
