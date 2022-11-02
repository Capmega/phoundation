<?php
/*
 * Link companies to customers and providers
 */
sql_column_exists('companies', 'customers_id', '!ALTER TABLE `companies` ADD COLUMN `customers_id` INT(11) NULL AFTER `categories_id`');
sql_index_exists ('companies', 'customers_id', '!ALTER TABLE `companies` ADD KEY    `customers_id` (`customers_id`)');
sql_foreignkey_exists ('companies', 'fk_companies_customers_id', '!ALTER TABLE `companies` ADD CONSTRAINT `fk_companies_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT;');

sql_column_exists('companies', 'providers_id', '!ALTER TABLE `companies` ADD COLUMN `providers_id` INT(11) NULL AFTER `customers_id`');
sql_index_exists ('companies', 'providers_id', '!ALTER TABLE `companies` ADD KEY    `providers_id` (`providers_id`)');
sql_foreignkey_exists ('companies', 'fk_companies_providers_id', '!ALTER TABLE `companies` ADD CONSTRAINT `fk_companies_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT;');
