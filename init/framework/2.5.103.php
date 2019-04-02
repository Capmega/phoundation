<?php
/*
 * Fix missing indices on dictionary table
 */
sql_index_exists('dictionary', 'language', 'ALTER TABLE `dictionary` ADD INDEX `language` (`language`)');
sql_index_exists('dictionary', 'status'  , 'ALTER TABLE `dictionary` ADD INDEX `status`   (`status`)');
?>
