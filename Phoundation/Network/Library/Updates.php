<?php

/**
 * Updates class
 *
 * This is the Init class for the Core library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Network
 */


declare(strict_types=1);

namespace Phoundation\Network\Library;


class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.2.0';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.8', function () {
            // Add network_curl_cache table
            sql()->getSchemaObject()->getTableObject('network_curl_cache')->drop()->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `headers` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                    `data` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,')
                 ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `url` (`url`),')
                 ->setForeignKeys('
                    CONSTRAINT `fk_network_curl_cache_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT')
                 ->create();

        })->addUpdate('0.1.0', function () {
            sql()->getSchemaObject()->getTableObject('network_meta')->drop()->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `global_id` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                    `local_id` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                    `version` int NULL DEFAULT NULL,
                    `data` mediumtext DEFAULT NULL,')
                 ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `global_id` (`global_id`),
                    KEY `local_id` (`local_id`),
                    KEY `version` (`version`),')
                 ->setForeignKeys('
                    CONSTRAINT `fk_network_meta_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_network_meta_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,')
                 ->create();

        })->addUpdate('0.2.0', function () {
            sql()->getSchemaObject()->getTableObject('network_test_meta')->drop()->define()
                 ->setColumns('
                        `id` bigint NOT NULL AUTO_INCREMENT,
                        `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `created_by` bigint NULL DEFAULT NULL,
                        `meta_id` bigint NULL DEFAULT NULL,
                        `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                        `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                        `network_meta_id` bigint NULL DEFAULT NULL,
                        `database_connector` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                        `database_selector` varchar(8) CHARACTER SET latin1 DEFAULT NULL,
                        `component` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                        `key` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                        `duration` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                        `success` varchar(8) CHARACTER SET latin1 DEFAULT NULL,')
                 ->setIndices('
                        PRIMARY KEY (`id`),
                        KEY `created_on` (`created_on`),
                        KEY `created_by` (`created_by`),
                        KEY `status` (`status`),
                        KEY `meta_id` (`meta_id`),
                        KEY `network_meta_id` (`network_meta_id`),
                        KEY `database_connector` (`database_connector`),
                        KEY `database_selector` (`database_selector`),
                        KEY `component` (`component`),
                        KEY `key` (`component`),
                        KEY `duration` (`component`),
                        KEY `success` (`success`),')
                 ->setForeignKeys('
                        CONSTRAINT `fk_network_test_meta_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                        CONSTRAINT `fk_network_test_meta_network_meta_id` FOREIGN KEY (`network_meta_id`) REFERENCES `network_meta` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_network_test_meta_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,')
                 ->create();
        });
    }
}
