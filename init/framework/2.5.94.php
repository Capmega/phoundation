<?php
/*
 * Fix customers and providers tables
 */
sql_query('ALTER TABLE `customers` MODIFY COLUMN `url`         VARCHAR(255)  NULL');
sql_query('ALTER TABLE `customers` MODIFY COLUMN `description` VARCHAR(2040) NULL');

sql_query('ALTER TABLE `providers` MODIFY COLUMN `url`         VARCHAR(255)  NULL');
sql_query('ALTER TABLE `providers` MODIFY COLUMN `description` VARCHAR(2040) NULL');
?>
