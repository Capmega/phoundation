<?php
/*
 * Add support for blog_media categories
 */
sql_column_exists('blogs_media', 'type'   , '!ALTER TABLE `blogs_media` ADD COLUMN `type`    VARCHAR(64) NULL AFTER `mime2`');
sql_column_exists('blogs_media', 'seotype', '!ALTER TABLE `blogs_media` ADD COLUMN `seotype` VARCHAR(64) NULL  AFTER `type`');
sql_index_exists('blogs_media', 'seotype', '!ALTER TABLE `blogs_media` ADD INDEX `seotype` (`seotype`)');
