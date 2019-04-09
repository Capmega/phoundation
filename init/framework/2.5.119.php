<?php
/*
 * Fix missing seonames column in database_accounts table
 */
sql_column_exists('database_accounts', 'seoname', '!ALTER TABLE `database_accounts` ADD COLUMN     `seoname` VARCHAR(32) NULL AFTER `name`');
sql_index_exists ('database_accounts', 'seoname', '!ALTER TABLE `database_accounts` ADD UNIQUE KEY `seoname` (`seoname`)');
?>
