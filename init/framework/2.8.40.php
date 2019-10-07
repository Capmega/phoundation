<?php
/*
 * Add support for multiple code fields to blogs system
 */
sql_column_exists('blogs_posts', 'code1', '!ALTER TABLE `blogs_posts` ADD COLUMN `code1` VARCHAR(64) NULL AFTER `code`');
sql_column_exists('blogs_posts', 'code2', '!ALTER TABLE `blogs_posts` ADD COLUMN `code2` VARCHAR(64) NULL AFTER `code1`');
sql_column_exists('blogs_posts', 'code3', '!ALTER TABLE `blogs_posts` ADD COLUMN `code3` VARCHAR(64) NULL AFTER `code2`');
sql_column_exists('blogs_posts', 'code4', '!ALTER TABLE `blogs_posts` ADD COLUMN `code4` VARCHAR(64) NULL AFTER `code3`');
sql_column_exists('blogs_posts', 'code5', '!ALTER TABLE `blogs_posts` ADD COLUMN `code5` VARCHAR(64) NULL AFTER `code4`');



