<?php
/*
 * Upgrade `ssh_accounts` table to have `meta_id` support
 */
sql_foreignkey_exists('ssh_accounts', 'fk_ssh_accounts_modifiedby', 'ALTER TABLE `ssh_accounts` DROP FOREIGN KEY `fk_ssh_accounts_modifiedby`');

sql_index_exists('ssh_accounts', 'modifiedby', 'ALTER TABLE `ssh_accounts` DROP INDEX `modifiedby`');
sql_index_exists('ssh_accounts', 'modifiedon', 'ALTER TABLE `ssh_accounts` DROP INDEX `modifiedon`');

sql_column_exists('ssh_accounts', 'modifiedby', 'ALTER TABLE `ssh_accounts` DROP COLUMN `modifiedby`');
sql_column_exists('ssh_accounts', 'modifiedon', 'ALTER TABLE `ssh_accounts` DROP COLUMN `modifiedon`');

sql_column_exists('ssh_accounts', 'meta_id', '!ALTER TABLE `ssh_accounts` ADD COLUMN `meta_id` INT(11) NULL AFTER `created_by`');
sql_index_exists('ssh_accounts' , 'meta_id', '!ALTER TABLE `ssh_accounts` ADD INDEX `meta_id` (`meta_id`)');
sql_foreignkey_exists('ssh_accounts', 'fk_ssh_accounts_meta_id', '!ALTER TABLE `ssh_accounts` ADD CONSTRAINT `fk_ssh_accounts_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE;');
?>
