<?php

namespace Phoundation\Business;



/**
 * Updates class
 *
 * This is the Init class for the Business library
 *
 * @see \Phoundation\System\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Updates extends \Phoundation\System\Updates
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
     *
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
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int NOT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `email` varchar(96) DEFAULT NULL,
                    `phones` varchar(36) DEFAULT NULL,
                    `url` varchar(512) DEFAULT NULL,
                    `address` varchar(64) DEFAULT NULL,
                    `address2` varchar(64) DEFAULT NULL,
                    `address3` varchar(64) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `categories_id` int DEFAULT NULL,
                    `companies_id` int DEFAULT NULL,
                    `languages_id` int DEFAULT NULL,
                    `countries_id` int DEFAULT NULL,
                    `states_id` int DEFAULT NULL,
                    `cities_id` int DEFAULT NULL,
                    `description` TEXT DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `name` (`name`),
                    KEY `countries_id` (`countries_id`),
                    KEY `states_id` (`states_id`),
                    KEY `cities_id` (`cities_id`),
                    KEY `meta_id` (`meta_id`),
                    KEY `email` (`email`),
                    KEY `phones` (`phones`),
                    KEY `categories_id` (`categories_id`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_customers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_business_customers_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`),
                    CONSTRAINT `fk_business_customers_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`),
                    CONSTRAINT `fk_business_customers_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_customers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                    CONSTRAINT `fk_business_customers_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`)
                ')
                ->create();

            // Add table for providers
            sql()->schema()->table('business_providers')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int NOT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `categories_id` int DEFAULT NULL,
                    `name` varchar(32) NOT NULL,
                    `seo_name` varchar(32) NOT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `phones` varchar(36) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `url` varchar(255) DEFAULT NULL,
                    `description` varchar(2040) DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `name` (`name`),
                    KEY `meta_id` (`meta_id`),
                    KEY `categories_id` (`categories_id`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_providers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_business_providers_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_providers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)
                ')
                ->create();

            // Add table for companies
            sql()->schema()->table('business_companies')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `categories_id` int DEFAULT NULL,
                    `customers_id` int DEFAULT NULL,
                    `providers_id` int DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `categories_name` (`categories_id`,`name`),
                    KEY `meta_id` (`meta_id`),
                    KEY `categories_id` (`categories_id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `customers_id` (`customers_id`),
                    KEY `providers_id` (`providers_id`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_companies_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_business_companies_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_companies_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `business_customers` (`id`),
                    CONSTRAINT `fk_business_companies_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                    CONSTRAINT `fk_business_companies_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `business_providers` (`id`)
                ')
                ->create();

            // Add table for branches
            sql()->schema()->table('business_branches')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `companies_id` int NOT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `company_name` (`companies_id`,`name`),
                    KEY `meta_id` (`meta_id`),
                    KEY `companies_id` (`companies_id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_branches_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `business_companies` (`id`),
                    CONSTRAINT `fk_business_branches_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_branches_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)               
                ')
                ->create();

            // Add table for departments
            sql()->schema()->table('business_departments')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `companies_id` int NOT NULL,
                    `branches_id` int DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `company_branch_name` (`companies_id`,`branches_id`,`name`),
                    KEY `meta_id` (`meta_id`),
                    KEY `companies_id` (`companies_id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `branches_id` (`branches_id`)               
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_departments_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `business_branches` (`id`),
                    CONSTRAINT `fk_business_departments_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `business_companies` (`id`),
                    CONSTRAINT `fk_business_departments_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_departments_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)               
                ')
                ->create();

            // Add table for employees
            sql()->schema()->table('business_employees')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `companies_id` int NOT NULL,
                    `branches_id` int DEFAULT NULL,
                    `departments_id` int DEFAULT NULL,
                    `users_id` int DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `meta_id` (`meta_id`),
                    KEY `companies_id` (`companies_id`),
                    KEY `branches_id` (`branches_id`),
                    KEY `departments_id` (`departments_id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_employees_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `business_branches` (`id`),
                    CONSTRAINT `fk_business_employees_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `business_companies` (`id`),
                    CONSTRAINT `fk_business_employees_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_employees_departments_id` FOREIGN KEY (`departments_id`) REFERENCES `business_departments` (`id`),
                    CONSTRAINT `fk_business_employees_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)                
                ')
                ->create();
        })->addUpdate('0.0.9', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('business_invoices_items')->drop();
            sql()->schema()->table('business_invoices')->drop();


            // Add table for invoices
            sql()->schema()->table('business_invoices')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int NOT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `accounts_id` int DEFAULT NULL,
                    `categories_id` int DEFAULT NULL,
                    `customers_id` int DEFAULT NULL,
                    `parents_id` int DEFAULT NULL,
                    `invoice_number` varchar(16) DEFAULT NULL,
                    `available_date` datetime DEFAULT NULL,
                    `due_date` datetime DEFAULT NULL,
                    `amount_total` float DEFAULT NULL,
                    `description` TEXT DEFAULT NULL,
                    `comments` TEXT DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `invoice_number` (`invoice_number`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `due_date` (`due_date`),
                    KEY `available_date` (`available_date`),
                    KEY `customers_id` (`customers_id`),
                    KEY `accounts_id` (`accounts_id`),
                    KEY `parents_id` (`parents_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_invoices_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `business_invoices` (`id`),
                    CONSTRAINT `fk_business_invoices_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_business_invoices_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_invoices_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                ')
                ->create();
            // Add table for invoices
            sql()->schema()->table('business_invoices_items')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int NOT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `invoices_id` int DEFAULT NULL,
                    `products_id` int DEFAULT NULL,
                    `quantity` int DEFAULT NULL,
                    `amount_per` float DEFAULT NULL,
                    `amount_total` float DEFAULT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `invoices_id` (`invoices_id`),
                    KEY `products_id` (`products_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_business_invoices_items_createdby` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_business_invoices_items_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                    CONSTRAINT `fk_business_invoices_items_invoices_id` FOREIGN KEY (`invoices_id`) REFERENCES `business_invoices` (`id`)
                ')
                ->create();
        });
    }
}
