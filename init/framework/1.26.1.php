<?php
/*
 * Correct "account_id" column
 */
sql_column_exists('twilio_accounts', 'accounts_id'   , 'ALTER TABLE `twilio_accounts` CHANGE COLUMN `accounts_id`    `account_id`    VARCHAR(40) NULL');
sql_column_exists('twilio_accounts', 'accounts_token', 'ALTER TABLE `twilio_accounts` CHANGE COLUMN `accounts_token` `account_token` VARCHAR(40) NULL');
?>