-- Add a 'date_last_edit' field to return a correct 'Last-Modified'
-- header in the Atom feed.
ALTER TABLE `news_bytes` ADD `date_last_edit` int AFTER `date`;
UPDATE news_bytes SET `date_last_edit` = `date`;
ALTER TABLE `news_bytes` MODIFY `date_last_edit` int NOT NULL,
      ADD KEY idx_news_bytes_date (`date`),
      ADD KEY idx_news_bytes_date_last_edit (`date_last_edit`);
