<?php

namespace Phoundation\Business\Companies;



/**
 * Updates class
 *
 * This is the Init class for the Companies library
 *
 * @see \Phoundation\System\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
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
        return '0.0.2';
    }



    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('This library manages all company functionalities');
    }



    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.2', function () {
            // Create the providers table.
            sql()->schema()->table('providers')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NOT NULL,
                    `meta_id` bigint DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
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
                    CONSTRAINT `fk_providers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_providers_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_providers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)
                ')
                ->create();

            // Create the customers table.
            sql()->schema()->table('customers')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NOT NULL,
                    `meta_id` bigint DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `company` varchar(64) DEFAULT NULL,
                    `email` varchar(96) DEFAULT NULL,
                    `phones` varchar(36) DEFAULT NULL,
                    `documents_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `address1` varchar(64) DEFAULT NULL,
                    `address2` varchar(64) DEFAULT NULL,
                    `address3` varchar(64) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
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
                    KEY `countries_id` (`countries_id`),
                    KEY `states_id` (`states_id`),
                    KEY `cities_id` (`cities_id`),
                    KEY `meta_id` (`meta_id`),
                    KEY `email` (`email`),
                    KEY `phones` (`phones`),
                    KEY `documents_id` (`documents_id`),
                    KEY `categories_id` (`categories_id`)
                ')
                ->setForeignKeys(' 
                    CONSTRAINT `fk_customers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_customers_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`),
                    CONSTRAINT `fk_customers_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`),
                    CONSTRAINT `fk_customers_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_customers_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_customers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                    CONSTRAINT `fk_customers_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`)
                ')
                ->create();

            // Create the companies table.
            sql()->schema()->table('companies')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `customers_id` bigint DEFAULT NULL,
                    `providers_id` bigint DEFAULT NULL,
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
                    CONSTRAINT `fk_companies_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`),
                    CONSTRAINT `fk_companies_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_companies_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`),
                    CONSTRAINT `fk_companies_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                    CONSTRAINT `fk_companies_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`)
                ')
                ->create();

            // Create the branches table.
            sql()->schema()->table('branches')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `companies_id` bigint NOT NULL,
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
                    CONSTRAINT `fk_branches_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`),
                    CONSTRAINT `fk_branches_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_branches_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)
                ')
                ->create();

            // Create the departments table.
            sql()->schema()->table('departments')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `companies_id` bigint NOT NULL,
                    `branches_id` bigint DEFAULT NULL,
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
                    KEY `fk_departments_branches_id` (`branches_id`)
                ')
                ->setForeignKeys(' 
                    CONSTRAINT `fk_departments_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `branches` (`id`),
                    CONSTRAINT `fk_departments_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`),
                    CONSTRAINT `fk_departments_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_departments_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)
                ')
                ->create();

            // Create the employees table.
            sql()->schema()->table('employees')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `companies_id` bigint NOT NULL,
                    `branches_id` bigint DEFAULT NULL,
                    `departments_id` bigint DEFAULT NULL,
                    `users_id` bigint DEFAULT NULL,
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
                    CONSTRAINT `fk_employees_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `branches` (`id`),
                    CONSTRAINT `fk_employees_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`),
                    CONSTRAINT `fk_employees_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_employees_departments_id` FOREIGN KEY (`departments_id`) REFERENCES `departments` (`id`),
                    CONSTRAINT `fk_employees_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)
                ')
                ->create();
        });
    }
}
