<?php
/*
 * Update servers table to have the same replication status available as the databases table
 */
sql_query('ALTER TABLE `servers` MODIFY COLUMN `replication_status` ENUM("enabled", "enabling", "pausing", "resuming", "preparing", "paused", "disabled", "error") NULL DEFAULT "disabled"');
