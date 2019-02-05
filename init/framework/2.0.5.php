<?php
/*
 * Implement meta data support for rights and roles tables
 * Fix missing meta links for servers table
 */
sql_column_exists('devices', 'type', '!ALTER TABLE `devices` ADD COLUMN `type` ENUM("fingerprint-reader", "scanner")');
sql_index_exists ('devices', 'type', '!ALTER TABLE `devices` ADD KEY    `type` (`type`)');
?>