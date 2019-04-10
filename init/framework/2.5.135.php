<?php
/*
 * Update servers table to have the same replication status available as the databases table
 */
sql_column_exists('servers', 'ssh_port', '!ALTER TABLE `servers` ADD COLUMN `ssh_port` VARCHAR(7) NULL DEFAULT NULL AFTER `os_name`');

if(sql_column_exists('servers', 'replication_status')){
    sql_query('ALTER TABLE `servers` MODIFY COLUMN `replication_status` ENUM("enabled", "enabling", "pausing", "resuming", "preparing", "paused", "disabled", "error") NULL DEFAULT "disabled"');

}else{
    sql_query('ALTER TABLE `servers` ADD COLUMN `replication_status` ENUM("enabled", "enabling", "pausing", "resuming", "preparing", "paused", "disabled", "error") NULL DEFAULT "disabled" AFTER `ssh_port`');
}

sql_column_exists('servers', 'replication_lock', '!ALTER TABLE `servers` ADD COLUMN `replication_lock` TINYINT(4) NULL DEFAULT 0 AFTER `replication_status`');
