<?php
/*
 * Rename "time_limit" column to "timeout"
 */
sql_column_exists('tasks', 'time_limit', 'ALTER TABLE `tasks` CHANGE COLUMN `time_limit` `timeout` INT(11) NULL DEFAULT NULL');
?>
