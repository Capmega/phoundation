<?php
/*
 * Fix missing seonames column in database_accounts table
 */
sql_query('ALTER TABLE `databases` MODIFY COLUMN `name` VARCHAR(32) NOT NULL');

sql_column_exists('databases', 'seoname', '!ALTER TABLE `databases` ADD COLUMN     `seoname` VARCHAR(32) NOT NULL AFTER `name`');
sql_index_exists ('databases', 'seoname', '!ALTER TABLE `databases` ADD UNIQUE KEY `seoname` (`seoname`)');

sql_query('ALTER TABLE `databases` MODIFY COLUMN `replication_status` ENUM("enabled", "enabling", "pausing", "resuming", "preparing", "paused", "disabled", "error") NULL DEFAULT "disabled"');
?>
