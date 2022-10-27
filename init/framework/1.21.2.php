<?php
/*
 * Fix servers table, created_by may be NULL
 */
sql_query('ALTER TABLE `servers` MODIFY COLUMN `created_by` INT(11) NULL DEFAULT NULL');
?>