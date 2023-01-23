<?php

namespace Phoundation\Core;

use Phoundation\Core\Locale\Language\Languages;



/**
 * Updates class
 *
 * This is the Init class for the Core library
 *
 * @see \Phoundation\System\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
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
        $this->addUpdate('0.0.1', function () {
            // Add table for version control itself
            sql()->schema()->table('versions')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `library` VARCHAR(64) NOT NULL,
                    `version` VARCHAR(64) NOT NULL,
                    `comments` VARCHAR(2048) NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    INDEX `created_on` (`created_on`),
                    INDEX `library` (`library`),
                    INDEX `version` (`version`),
                    UNIQUE `library_version` (`library`, `version`),
                ')
                ->create();
        })->addUpdate('0.0.2', function () {
            // Add tables for the meta library
            sql()->schema()->table('meta')->define()
                ->setColumns('`id` int NOT NULL AUTO_INCREMENT')
                ->setIndices('PRIMARY KEY (`id`)')
                ->create();

            sql()->schema()->table('meta_history')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `action` varchar(16) DEFAULT NULL,
                    `comments` varchar(255) DEFAULT NULL,
                    `data` varchar(2555) DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `action` (`action`),
                    KEY `fk_meta_history_id` (`meta_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_meta_history_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();
        })->addUpdate('0.0.5', function () {
            // Modify the meta_history table to have a foreign key to the (now existing) accounts_users table
            sql()->schema()->table('meta_history')->alter()->addForeignKey('CONSTRAINT `fk_meta_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT');

            // Add tables for the sessions management
            sql()->schema()->table('sessions_extended')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `session_key` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                    `ip` int NOT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `session_key` (`session_key`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `ip` (`ip`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_sessions_extended_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE,
                ')
                ->create();
        })->addUpdate('0.0.6', function () {
            // Add tables for the sessions management
            sql()->schema()->table('url_cloaks')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `url` varchar(140) NOT NULL,
                    `cloak` varchar(32) NOT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `cloak` (`cloak`),
                    UNIQUE KEY `url_created_by` (`url`,`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_url_cloaks_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`)
                ')
                ->create();
        })->addUpdate('0.0.7', function () {
            // Add tables for the sessions management
            sql()->schema()->table('key_value_store')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `key` varchar(64) NOT NULL,
                    `value` varchar(4096) NOT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `key` (`key`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_key_value_store_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_key_value_store_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();
        })->addUpdate('0.0.8', function () {
            sql()->schema()->table('languages')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) DEFAULT NULL,
                    `seo_name` varchar(32) DEFAULT NULL,
                    `code_639_1` varchar(2) DEFAULT NULL,
                    `code_639_2_t` varchar(3) DEFAULT NULL,
                    `code_639_2_b` varchar(3) DEFAULT NULL,
                    `code_639_3` varchar(3) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `code_639_1` (`code_639_1`),
                    UNIQUE KEY `code_639_2_t` (`code_639_2_t`),
                    UNIQUE KEY `code_639_2_b` (`code_639_2_b`),
                    UNIQUE KEY `code_639_3` (`code_639_3`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_languages_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_languages_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();
        });
    }
}
