# Drop useless field
ALTER TABLE groups DROP http_domain;

# Add needed new fields:
ALTER TABLE `group_type` ADD `is_configurable_download_dir` INT( 1 ) DEFAULT '0' NOT NULL AFTER `is_menu_configurable_download` ;