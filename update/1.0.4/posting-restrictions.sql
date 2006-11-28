ALTER TABLE group_type ADD forum_rflags INT( 1 ) DEFAULT '2' AFTER forum_flags;
ALTER TABLE group_type ADD bugs_rflags INT( 1 ) DEFAULT '2' AFTER bugs_flags;
ALTER TABLE group_type ADD task_rflags INT( 1 ) DEFAULT '5' AFTER task_flags;
ALTER TABLE group_type ADD support_rflags INT( 1 ) DEFAULT '2' AFTER support_flags;
ALTER TABLE group_type ADD patch_rflags INT( 1 ) DEFAULT '2' AFTER patch_flags;
ALTER TABLE group_type ADD news_rflags INT( 1 ) DEFAULT '2' AFTER news_flags;

ALTER TABLE groups_default_permissions ADD forum_rflags INT( 1 ) DEFAULT NULL AFTER forum_flags;
ALTER TABLE groups_default_permissions ADD bugs_rflags INT( 1 ) DEFAULT NULL AFTER bugs_flags;
ALTER TABLE groups_default_permissions ADD task_rflags INT( 1 ) DEFAULT NULL AFTER task_flags;
ALTER TABLE groups_default_permissions ADD support_rflags INT( 1 ) DEFAULT NULL AFTER support_flags;
ALTER TABLE groups_default_permissions ADD patch_rflags INT( 1 ) DEFAULT NULL AFTER patch_flags;
ALTER TABLE groups_default_permissions ADD news_rflags INT( 1 ) DEFAULT NULL AFTER news_flags;