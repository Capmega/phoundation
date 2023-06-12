<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;


/**
 * Updates class
 *
 * This is the Init class for the Companies library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `name` varchar(64) NOT NULL,
                    `seo_name` varchar(64) NOT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `description` varchar(2040) DEFAULT NULL
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
                    CONSTRAINT `fk_providers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_providers_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_providers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE
                ')->create();

            // Create the customers table.
            sql()->schema()->table('customers')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `company` varchar(64) DEFAULT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `documents_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `address` varchar(64) DEFAULT NULL,
                    `address2` varchar(64) DEFAULT NULL,
                    `address3` varchar(64) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `description` varchar(2040) DEFAULT NULL
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
                    KEY `documents_id` (`documents_id`),
                    KEY `categories_id` (`categories_id`)
                ')->setForeignKeys(' 
                    CONSTRAINT `fk_customers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_customers_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT,,
                    CONSTRAINT `fk_customers_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,,
                    CONSTRAINT `fk_customers_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_customers_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_customers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_customers_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Create the companies table.
            sql()->schema()->table('companies')->define()
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
                    CONSTRAINT `fk_companies_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_companies_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_companies_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_companies_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_companies_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT
                ')->create();

            // Create the branches table.
            sql()->schema()->table('branches')->define()
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
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `company_name` (`companies_id`,`name`),
                    KEY `companies_id` (`companies_id`),
                ')->setForeignKeys(' 
                    CONSTRAINT `fk_branches_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_branches_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_branches_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE
                ')->create();

            // Create the departments table.
            sql()->schema()->table('departments')->define()
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
                    KEY `fk_departments_branches_id` (`branches_id`)
                ')->setForeignKeys(' 
                    CONSTRAINT `fk_departments_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_departments_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_departments_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_departments_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE
                ')->create();

            // Create the employees table.
            sql()->schema()->table('employees')->define()
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
                    CONSTRAINT `fk_employees_branches_id` FOREIGN KEY (`branches_id`) REFERENCES `branches` (`id`),
                    CONSTRAINT `fk_employees_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`),
                    CONSTRAINT `fk_employees_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_employees_departments_id` FOREIGN KEY (`departments_id`) REFERENCES `departments` (`id`),
                    CONSTRAINT `fk_employees_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE
                ')->create();
        });
    }
}
