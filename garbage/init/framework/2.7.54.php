<?php
/*
 * Add code support for blog posts
 */
sql_column_exists('blogs_posts', 'code', '!ALTER TABLE `blogs_posts` ADD COLUMN `code` VARCHAR(32) NULL AFTER `name`');
sql_index_exists ('blogs_posts', 'code', '!ALTER TABLE `blogs_posts` ADD INDEX `code` (`code`)');
?>
