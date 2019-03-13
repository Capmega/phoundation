<?php
/*
 * Add support for radius library
 * Add radius_devices table which is a front-end to the `devices` table for the freeradius system
 * Add radius_nas table which is a front-end to the `nas` table for the freeradius system
 *
 * @example INSERT INTO nas (nasname, shortname, type, secret) VALUES ('127.0.0.1', 'test-3', 'other', 'admin');
 */
sql_column_exists('devices', 'name'   , '!ALTER TABLE `devices` ADD COLUMN `name`    VARCHAR(64) NULL AFTER `type`');
sql_column_exists('devices', 'seoname', '!ALTER TABLE `devices` ADD COLUMN `seoname` VARCHAR(64) NULL AFTER `name`');
sql_index_exists ('devices', 'seoname', '!ALTER TABLE `devices` ADD KEY    `seoname` (`seoname`)');
?>
