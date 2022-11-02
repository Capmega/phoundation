<?php
/*
 * Add support for blog_media types
 */
sql_column_exists('blogs_media', 'type'    , '!ALTER TABLE `blogs_media` ADD COLUMN `type`     VARCHAR(64) NULL');
sql_column_exists('blogs_media', 'seo_type', '!ALTER TABLE `blogs_media` ADD COLUMN `seo_type` VARCHAR(64) NULL');
