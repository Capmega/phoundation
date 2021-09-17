<?php
/*
 * Add support for blog_media categories
 */
sql_column_exists('blogs_media', 'type', '!ALTER TABLE `blogs_media` ADD COLUMN `type` VARCHAR(64) NULL');
