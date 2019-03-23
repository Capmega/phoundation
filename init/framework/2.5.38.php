<?php
/*
 * Add leaders support for projects library
 */
sql_column_exists('projects', 'leaders_id', '!ALTER TABLE `projects` ADD COLUMN `leaders_id` INT(11) NULL AFTER `customers_id`');
sql_index_exists ('projects', 'leaders_id', '!ALTER TABLE `projects` ADD KEY    `leaders_id` (`leaders_id`)');
?>
