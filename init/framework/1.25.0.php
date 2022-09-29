<?php
/*
 * Add support for devices on multiple servers
 * Add support for devices in multiple categories
 * Add support for devices registered by company, branch and or department
 * Add support for fingerprint devices
 */
if (sql_table_exists('drivers_options')) {
    sql_foreignkey_exists('drivers_options', 'fk_drivers_options_id'        , 'ALTER TABLE `drivers_options` DROP FOREIGN KEY `fk_drivers_options_id`');
    sql_foreignkey_exists('drivers_options', 'fk_drivers_options_devices_id', 'ALTER TABLE `drivers_options` DROP FOREIGN KEY `fk_drivers_options_devices_id`');
}

if (sql_table_exists('drivers_devices')) {
    sql_foreignkey_exists('drivers_devices', 'fk_drivers_devices_createdby'     , 'ALTER TABLE `drivers_devices` DROP FOREIGN KEY `fk_drivers_devices_createdby`');
    sql_foreignkey_exists('drivers_devices', 'fk_drivers_devices_meta_id'       , 'ALTER TABLE `drivers_devices` DROP FOREIGN KEY `fk_drivers_devices_meta_id`');
    sql_foreignkey_exists('drivers_devices', 'fk_drivers_devices_servers_id'    , 'ALTER TABLE `drivers_devices` DROP FOREIGN KEY `fk_drivers_devices_servers_id`');
    sql_foreignkey_exists('drivers_devices', 'fk_drivers_devices_categories_id' , 'ALTER TABLE `drivers_devices` DROP FOREIGN KEY `fk_drivers_devices_categories_id`');
    sql_foreignkey_exists('drivers_devices', 'fk_drivers_devices_companies_id'  , 'ALTER TABLE `drivers_devices` DROP FOREIGN KEY `fk_drivers_devices_companies_id`');
    sql_foreignkey_exists('drivers_devices', 'fk_drivers_devices_departments_id', 'ALTER TABLE `drivers_devices` DROP FOREIGN KEY `fk_drivers_devices_departments_id`');
    sql_foreignkey_exists('drivers_devices', 'fk_drivers_devices_branches_id'   , 'ALTER TABLE `drivers_devices` DROP FOREIGN KEY `fk_drivers_devices_branches_id`');

    sql_query('RENAME TABLE `drivers_devices` TO `devices`');
}

/*
 * Clean up some garbage from a specific project
 */
if (sql_table_exists('push_devices')) {
    sql_foreignkey_exists('push_devices', 'fk_devices_createdby'     ,  'ALTER TABLE `push_devices` DROP FOREIGN KEY `fk_devices_createdby`');
    sql_foreignkey_exists('push_devices', 'fk_devices_meta_id'       ,  'ALTER TABLE `push_devices` DROP FOREIGN KEY `fk_devices_meta_id`');
    sql_foreignkey_exists('push_devices', 'fk_push_devices_createdby', '!ALTER TABLE `push_devices` ADD CONSTRAINT `fk_push_devices_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT');
    sql_foreignkey_exists('push_devices', 'fk_push_devices_meta_id'  , '!ALTER TABLE `push_devices` ADD CONSTRAINT `fk_push_devices_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT');
}

/*
 * Add links to tables categories, servers, companies, branches and departments
 */
sql_column_exists('devices', 'servers_id', '!ALTER TABLE `devices` ADD COLUMN `servers_id` INT(11) NULL DEFAULT NULL AFTER `status`');
sql_index_exists ('devices', 'servers_id', '!ALTER TABLE `devices` ADD KEY    `servers_id` (`servers_id`)');
sql_foreignkey_exists('devices', 'fk_devices_servers_id', '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT');

sql_column_exists('devices', 'categories_id', '!ALTER TABLE `devices` ADD COLUMN `categories_id` INT(11) NULL DEFAULT NULL AFTER `servers_id`');
sql_index_exists ('devices', 'categories_id', '!ALTER TABLE `devices` ADD KEY    `categories_id` (`categories_id`)');
sql_foreignkey_exists('devices', 'fk_devices_categories_id', '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT');

sql_column_exists('devices', 'companies_id', '!ALTER TABLE `devices` ADD COLUMN `companies_id` INT(11) NULL DEFAULT NULL AFTER `categories_id`');
sql_index_exists ('devices', 'companies_id', '!ALTER TABLE `devices` ADD KEY    `companies_id` (`companies_id`)');
sql_foreignkey_exists('devices', 'fk_devices_companies_id', '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT');

sql_column_exists('devices', 'branches_id', '!ALTER TABLE `devices` ADD COLUMN `branches_id` INT(11) NULL DEFAULT NULL AFTER `companies_id`');
sql_index_exists ('devices', 'branches_id', '!ALTER TABLE `devices` ADD KEY    `branches_id` (`branches_id`)');
sql_foreignkey_exists('devices', 'fk_devices_branches_id', '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT');

sql_column_exists('devices', 'departments_id', '!ALTER TABLE `devices` ADD COLUMN `departments_id` INT(11) NULL DEFAULT NULL AFTER `branches_id`');
sql_index_exists ('devices', 'departments_id', '!ALTER TABLE `devices` ADD KEY    `departments_id` (`departments_id`)');
sql_foreignkey_exists('devices', 'fk_devices_departments_id', '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_departments_id` FOREIGN KEY (`departments_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT');

sql_column_exists('devices', 'vendor_string'     , '!ALTER TABLE `devices` ADD  COLUMN `vendor_string`      VARCHAR(32) NULL DEFAULT NULL AFTER `vendor`');
sql_column_exists('devices', 'product_string'    , '!ALTER TABLE `devices` ADD  COLUMN `product_string`     VARCHAR(32) NULL DEFAULT NULL AFTER `vendor_string`');
sql_column_exists('devices', 'seo_product_string', '!ALTER TABLE `devices` ADD  COLUMN `seo_product_string` VARCHAR(32) NULL DEFAULT NULL AFTER `product_string`');

sql_column_exists('devices', 'type'              ,  'ALTER TABLE `devices` DROP COLUMN `type`');

/*
 * Add default foreign keys for devices table
 */
sql_foreignkey_exists('devices', 'fk_devices_createdby', '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT');
sql_foreignkey_exists('devices', 'fk_devices_meta_id'  , '!ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT');
?>