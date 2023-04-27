<?php

declare(strict_types=1);

namespace Phoundation\Business;

/**
 * Updates class
 *
 * This is the Init class for the Business library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.9';
    }


    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('The Core library is the most basic library in the entire Phoundation framwork. It contains all the low level libraries used by all other libraries and is an essential component of your entire system. Do NOT modify!');
    }


    /**
     * The list of version updates available for this library
     *f
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.8', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('business_employees')->drop();
            sql()->schema()->table('business_departments')->drop();
            sql()->schema()->table('business_branches')->drop();
            sql()->schema()->table('business_companies')->drop();
            sql()->schema()->table('business_providers')->drop();
            sql()->schema()->table('business_customers')->drop();

            // Add table for customers
            sql()->schema()->table('business_customers')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `picture` varchar(512) DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `address` varchar(64) DEFAULT NULL,
                    `address2` varchar(64) DEFAULT NULL,
                    `address3` varchar(64) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `companies_id` bigint DEFAULT NULL,
                    `languages_id` bigint DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
                    `description` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `countries_id` (`countries_id`),
                    KEY `states_id` (`states_id`),
                    KEY `cities_id` (`cities_id`),
                    KEY `email` (`email`),
                    KEY `phones` (`phones`),
                    KEY `categories_id` (`categories_id`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_customers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_customers_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_customers_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_customers_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_customers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_business_customers_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT
                ')->create();

            // Add table for providers
            sql()->schema()->table('business_providers')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `picture` varchar(512) DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `address` varchar(64) DEFAULT NULL,
                    `address2` varchar(64) DEFAULT NULL,
                    `address3` varchar(64) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `companies_id` bigint DEFAULT NULL,
                    `languages_id` bigint DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
                    `description` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `categories_id` (`categories_id`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_providers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_providers_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_providers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE
                ')->create();

            // Add table for companies
            sql()->schema()->table('business_companies')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `customers_id` bigint DEFAULT NULL,
                    `providers_id` bigint DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `categories_name` (`categories_id`,`name`),
                    KEY `categories_id` (`categories_id`),
                    KEY `customers_id` (`customers_id`),
                    KEY `providers_id` (`providers_id`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_companies_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_companies_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_companies_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `business_customers` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_companies_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_business_companies_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `business_providers` (`id`) ON DELETE RESTRICT
                ')->create();

            // Add table for branches
            sql()->schema()->table('business_branches')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `companies_id` bigint NOT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `company_name` (`companies_id`,`name`),
                    KEY `companies_id` (`companies_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_branches_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `business_companies` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_branches_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_branches_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE               
                ')->create();

            // Add table for departments
            sql()->schema()->table('business_departments')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `companies_id` bigint NOT NULL,
                    `branches_id` bigint DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `company_branch_name` (`companies_id`,`branches_id`,`name`),
                    KEY `companies_id` (`companies_id`),
                    KEY `branches_id` (`branches_id`)               
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_departments_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `business_branches` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_departments_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `business_companies` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_departments_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_departments_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE               
                ')->create();

            // Add table for employees
            sql()->schema()->table('business_employees')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `companies_id` bigint NOT NULL,
                    `branches_id` bigint DEFAULT NULL,
                    `departments_id` bigint DEFAULT NULL,
                    `users_id` bigint DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `companies_id` (`companies_id`),
                    KEY `branches_id` (`branches_id`),
                    KEY `departments_id` (`departments_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_employees_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `business_branches` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_employees_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `business_companies` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_employees_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_employees_departments_id` FOREIGN KEY (`departments_id`) REFERENCES `business_departments` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_employees_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE                
                ')->create();
        })->addUpdate('0.0.9', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('business_invoices_items')->drop();
            sql()->schema()->table('business_invoices')->drop();

            // Add table for invoices
            sql()->schema()->table('business_invoices')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `accounts_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `customers_id` bigint DEFAULT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `invoice_number` varchar(16) DEFAULT NULL,
                    `available_date` datetime DEFAULT NULL,
                    `due_date` datetime DEFAULT NULL,
                    `amount_total` float DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `comments` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `invoice_number` (`invoice_number`),
                    KEY `due_date` (`due_date`),
                    KEY `available_date` (`available_date`),
                    KEY `customers_id` (`customers_id`),
                    KEY `accounts_id` (`accounts_id`),
                    KEY `parents_id` (`parents_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_invoices_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `business_invoices` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_invoices_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_invoices_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_invoices_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();
            // Add table for invoices
            sql()->schema()->table('business_invoices_items')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `invoices_id` bigint DEFAULT NULL,
                    `products_id` bigint DEFAULT NULL,
                    `quantity` int DEFAULT NULL,
                    `amount_per` float DEFAULT NULL,
                    `amount_total` float DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `invoices_id` (`invoices_id`),
                    KEY `products_id` (`products_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_business_invoices_items_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_business_invoices_items_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_business_invoices_items_invoices_id` FOREIGN KEY (`invoices_id`) REFERENCES `business_invoices` (`id`)
                ')->create();
        });
    }
}
