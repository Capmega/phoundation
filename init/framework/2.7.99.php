<?php
/*
 * Improve api_sessions table
 */
sql_column_exists('api_sessions', 'last'       , '!ALTER TABLE `api_sessions` ADD COLUMN `last`        DATETIME    NOT NULL AFTER `createdon`');
sql_column_exists('api_sessions', 'sessions_id', '!ALTER TABLE `api_sessions` ADD COLUMN `sessions_id` VARCHAR(64) NOT NULL AFTER `created_by`');

sql_index_exists('api_sessions', 'sessions_id', '!ALTER TABLE `api_sessions` ADD INDEX `sessions_id` (`sessions_id`)');
sql_index_exists('api_sessions', 'last'       , '!ALTER TABLE `api_sessions` ADD INDEX `last`        (`last`)');
