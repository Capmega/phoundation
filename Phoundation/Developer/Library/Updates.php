<?php

/**
 * Updates class
 *
 * This is the Init class for the Developer library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Library;


class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.9.0';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.1.0', function () {
            // Ensure the table developer_incidents is gone, it was an old table from an older version that is no longer
            // supported
            sql()->getSchemaObject()->getTableObject('developer_incidents')->drop();

            // Drop and create the developer_unittests table
            sql()->getSchemaObject()->getTableObject('developer_unittests')->drop()->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seoname` varchar(128) DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `type` varchar(16) DEFAULT NULL,
                    `description` mediumtext DEFAULT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seoname` (`seoname`),
                    KEY `type` (`type`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_developer_unittests_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_developer_unittests_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

        })->addUpdate('0.8.0', function () {
            // Add support for modified_on and modified_by
            $this->ensureModifiedColumns([
                'developer_unittests',
            ]);

        })->addUpdate('0.9.0', function () {
            // Drop and create the developer_unittests table
            sql()->getSchemaObject()->getTableObject('developer_repositories')->drop()->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `modified_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
                    `seoname` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
                    `path` varchar(2048) CHARACTER SET latin1 DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `platform` ENUM ("git"),
                    `type` ENUM ("system", "plugins", "templates", "data", "cdn", "project"),
                    `description` mediumtext DEFAULT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seoname` (`seoname`),
                    UNIQUE KEY `path` (`path`),
                    KEY `url` (`url` (32)),
                    KEY `type` (`type`),
                    KEY `platform` (`platform`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_developer_repositories_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_developer_repositories_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_developer_repositories_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();
        });
    }
}
