<?php
/*
 * Fix `devices` table
 *
 * `servers_id` always has to be set
 *
 * `servers_id` with `libusb` has to be unique
 * `servers_id` with `string` has to be unique
 */
load_libs('devices');
devices_clear();

sql_foreignkey_exists('devices', 'fk_devices_servers_id',  'ALTER TABLE `devices` DROP FOREIGN KEY `fk_devices_servers_id`');
sql_query('ALTER TABLE `devices` MODIFY COLUMN `servers_id` INT(11) NOT NULL');
sql_foreignkey_exists('devices', 'fk_devices_servers_id', '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');

sql_index_exists('devices', 'libusb', 'ALTER TABLE `devices` DROP KEY `libusb`');
sql_index_exists('devices', 'string', 'ALTER TABLE `devices` DROP KEY `string`');

sql_index_exists('devices', 'servers_id_libusb', '!ALTER TABLE `devices` ADD KEY `servers_id_libusb` (`servers_id`, `libusb`)');
sql_index_exists('devices', 'servers_id_string', '!ALTER TABLE `devices` ADD KEY `servers_id_string` (`servers_id`, `string`)');
?>