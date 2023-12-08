<?php

declare(strict_types=1);

namespace Phoundation\Data;


/**
 * Updates class
 *
 * This is the Init class for the Data library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
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
        return '0.0.5';
    }


    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('This library manages all general data management functionalities');
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.3', function () {
            // Create the categories table.
            sql()->schema()->table('categories')->drop();

            sql()->schema()->table('categories')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `description` text DEFAULT NULL
                ')->setIndices(' 
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `parent_name` (`parents_id`,`name`),
                    KEY `parents_id` (`parents_id`),
                ')->setForeignKeys(' 
                    CONSTRAINT `fk_categories_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_categories_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
                ')->create();
        })->addUpdate('0.0.5', function () {
            // Modify the categories table.
            sql()->schema()->table('categories')->alter()
                ->addColumn('
                    `created_by` bigint DEFAULT NULL', 'AFTER `created_on`
                ')
                ->addIndices('
                    KEY `created_by` (`created_by`)
                ')
                ->addForeignKeys('
                    CONSTRAINT `fk_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ');
        })->addUpdate('0.0.12', function () {
            // Create the entities table.
            sql()->schema()->table('entities')->drop();

            sql()->schema()->table('entities')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `first_names` varchar(128) DEFAULT NULL,
                    `last_names` varchar(128) DEFAULT NULL,
                    `nickname` varchar(128) DEFAULT NULL,
                    `picture` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `code` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `keywords` varchar(255) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `address` varchar(255) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `verification_code` varchar(128) DEFAULT NULL,
                    `verified_on` datetime NULL DEFAULT NULL,
                    `priority` int DEFAULT NULL,
                    `latitude` decimal(18,15) DEFAULT NULL,
                    `longitude` decimal(18,15) DEFAULT NULL,
                    `accuracy` int DEFAULT NULL,
                    `offset_latitude` decimal(18,15) DEFAULT NULL,
                    `offset_longitude` decimal(18,15) DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `languages_id` bigint DEFAULT NULL,
                    `gender` varchar(16) DEFAULT NULL,
                    `birthdate` date DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `verified_on` datetime NULL DEFAULT NULL,
                    `verification_code` varchar(128) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `comments` mediumtext DEFAULT NULL
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `verification_code` (`verification_code`),
                    KEY `verified_on` (`verified_on`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `email` (`email`),
                    KEY `verified_on` (`verified_on`),
                    KEY `languages_id` (`languages_id`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `birthdate` (`birthdate`),
                    KEY `code` (`code`),
                    KEY `type` (`type`),
                    KEY `phones` (`phones`),
                    KEY `is_leader` (`is_leader`),
                    KEY `leaders_id` (`leaders_id`),
                    KEY `name` (`name`),
                    KEY `cities_id` (`cities_id`),
                    KEY `states_id` (`states_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `timezones_id` (`timezones_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_entities_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_entities_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_entities_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_entities_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_entities_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_entities_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_entities_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT,
                ')->create();
        });
    }
}
