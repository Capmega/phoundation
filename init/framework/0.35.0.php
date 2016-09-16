<?php
/*
 * Add extra category, bringing the number of possible categories to 3.
 * Rename all to categoryN, so category is now category1, group is now category2
 */
sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_category', 'ALTER TABLE `blogs_posts` DROP FOREIGN KEY `fk_blogs_posts_category`');
sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_group'   , 'ALTER TABLE `blogs_posts` DROP FOREIGN KEY `fk_blogs_posts_group`');

sql_index_exists('blogs_posts', 'seocategory', 'ALTER TABLE `blogs_posts` DROP INDEX `seocategory`');
sql_index_exists('blogs_posts', 'seogroup'   , 'ALTER TABLE `blogs_posts` DROP INDEX `seogroup`');

sql_column_exists('blogs_posts', 'seocategory', 'ALTER TABLE `blogs_posts` CHANGE COLUMN `seocategory` `seocategory1` VARCHAR(64)');
sql_column_exists('blogs_posts', 'category'   , 'ALTER TABLE `blogs_posts` CHANGE COLUMN `category`    `category1`    VARCHAR(64)');

sql_column_exists('blogs_posts', 'seogroup', 'ALTER TABLE `blogs_posts` CHANGE COLUMN `seogroup` `seocategory2` VARCHAR(64) AFTER `category1`');
sql_column_exists('blogs_posts', 'group'   , 'ALTER TABLE `blogs_posts` CHANGE COLUMN `group`    `category2`    VARCHAR(64) AFTER `seocategory2`');

sql_column_exists('blogs_posts', 'seocategory3', '!ALTER TABLE `blogs_posts` ADD COLUMN `seocategory3` VARCHAR(64) AFTER `category2`');
sql_column_exists('blogs_posts', 'category3'   , '!ALTER TABLE `blogs_posts` ADD COLUMN `category3`    VARCHAR(64) AFTER `seocategory3`');

sql_index_exists('blogs_posts', 'seocategory1', '!ALTER TABLE `blogs_posts` ADD INDEX (`seocategory1`)');
sql_index_exists('blogs_posts', 'seocategory2', '!ALTER TABLE `blogs_posts` ADD INDEX (`seocategory2`)');
sql_index_exists('blogs_posts', 'seocategory3', '!ALTER TABLE `blogs_posts` ADD INDEX (`seocategory3`)');

sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_seocategory1', '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_seocategory1` FOREIGN KEY (`seocategory1`) REFERENCES `blogs_categories` (`seoname`) ON DELETE RESTRICT;');
sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_seocategory2', '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_seocategory2` FOREIGN KEY (`seocategory2`) REFERENCES `blogs_categories` (`seoname`) ON DELETE RESTRICT;');
sql_foreignkey_exists('blogs_posts', 'fk_blogs_posts_seocategory3', '!ALTER TABLE `blogs_posts` ADD CONSTRAINT `fk_blogs_posts_seocategory3` FOREIGN KEY (`seocategory3`) REFERENCES `blogs_categories` (`seoname`) ON DELETE RESTRICT;');

?>
