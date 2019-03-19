<?php
/*
 * Fixed issues in init 1.21.4
 */

sql_column_exists('api_accounts', 'security_type', '!ALTER TABLE `api_accounts` ADD COLUMN `security_type` ENUM ("none", "api_key", "sessions") NOT NULL DEFAULT "sessions"');
sql_column_exists('api_accounts', 'version'      , '!ALTER TABLE `api_accounts` ADD COLUMN `version`       VARCHAR(8) NOT NULL');
?>
