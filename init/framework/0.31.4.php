<?php
/*
 * Fix blogs "created_by" columns
 */
sql_query('ALTER TABLE `blogs`                        CHANGE COLUMN `created_by` `created_by` INT(11) NULL');
sql_query('ALTER TABLE `blogs_categories`             CHANGE COLUMN `created_by` `created_by` INT(11) NULL');
sql_query('ALTER TABLE `blogs_comments`               CHANGE COLUMN `created_by` `created_by` INT(11) NULL');
sql_query('ALTER TABLE `blogs_key_value_descriptions` CHANGE COLUMN `created_by` `created_by` INT(11) NULL');
sql_query('ALTER TABLE `blogs_media`                  CHANGE COLUMN `created_by` `created_by` INT(11) NULL');
sql_query('ALTER TABLE `blogs_posts`                  CHANGE COLUMN `created_by` `created_by` INT(11) NULL');
sql_query('ALTER TABLE `blogs_updates`                CHANGE COLUMN `created_by` `created_by` INT(11) NULL');

sql_index_exists('email_messages', 'messages_id', 'ALTER TABLE `email_messages` ADD INDEX (`messages_id` (16))');
?>
