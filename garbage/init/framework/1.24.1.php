<?php
/*
 * Correct "expires" column name
 */
sql_column_exists('coupons', 'expired', 'ALTER TABLE `coupons` CHANGE COLUMN `expired` `expires` DATETIME NULL');
?>