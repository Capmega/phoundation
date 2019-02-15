<?php
/*
 * Remove old handler files
 */
sql_index_exists('devices', 'default_type',  'ALTER TABLE `devices` DROP INDEX `default_type`');
sql_index_exists('devices', 'default_type', '!ALTER TABLE `devices` ADD INDEX `default_type` (`type`, `default`)');

sql_query('ALTER TABLE `devices` MODIFY COLUMN `type` ENUM("fingerprint-reader", "document-scanner") NULL DEFAULT NULL');
?>