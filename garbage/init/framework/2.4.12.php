<?php
/*
 * Upgrade the devices table, devices can be linked to inventory, clients, providers and customers
 */
sql_column_exists('devices', 'employees_id'  , '!ALTER TABLE `devices` ADD COLUMN `employees_id`   INT(11) NULL DEFAULT NULL AFTER `departments_id`');
sql_column_exists('devices', 'customers_id'  , '!ALTER TABLE `devices` ADD COLUMN `customers_id`   INT(11) NULL DEFAULT NULL AFTER `employees_id`');
sql_column_exists('devices', 'providers_id'  , '!ALTER TABLE `devices` ADD COLUMN `providers_id`   INT(11) NULL DEFAULT NULL AFTER `customers_id`');
sql_column_exists('devices', 'inventories_id', '!ALTER TABLE `devices` ADD COLUMN `inventories_id` INT(11) NULL DEFAULT NULL AFTER `providers_id`');

sql_index_exists('devices', 'employees_id'  , '!ALTER TABLE `devices` ADD KEY `employees_id`   (`employees_id`)');
sql_index_exists('devices', 'customers_id'  , '!ALTER TABLE `devices` ADD KEY `customers_id`   (`customers_id`)');
sql_index_exists('devices', 'providers_id'  , '!ALTER TABLE `devices` ADD KEY `providers_id`   (`providers_id`)');
sql_index_exists('devices', 'inventories_id', '!ALTER TABLE `devices` ADD KEY `inventories_id` (`inventories_id`)');

sql_foreignkey_exists('devices', 'fk_devices_employees_id'  , 'ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_employees_id`   FOREIGN KEY (`employees_id`)   REFERENCES `employees`   (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('devices', 'fk_devices_customers_id'  , 'ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_customers_id`   FOREIGN KEY (`customers_id`)   REFERENCES `customers`   (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('devices', 'fk_devices_providers_id'  , 'ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_providers_id`   FOREIGN KEY (`providers_id`)   REFERENCES `providers`   (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('devices', 'fk_devices_inventories_id', 'ALTER TABLE `devices` ADD CONSTRAINT `fk_devices_inventories_id` FOREIGN KEY (`inventories_id`) REFERENCES `inventories` (`id`) ON DELETE RESTRICT;');
?>