<?php

/**
 * Updates class
 *
 * This is the Init class for the Security library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Security\Library;



class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.20';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.5', function () {
            // Add security incidents table
            sql()->getSchemaObject()->getTableObject('security_incidents')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `type` varchar(64) NOT NULL,
                    `severity` ENUM("notice", "low", "medium", "high", "severe") NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `details` text NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    KEY `type` (`type`),
                    KEY `severity` (`severity`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_security_incidents_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.6', function () {
            sql()->getSchemaObject()->getTableObject('security_puks_keys')->drop();

            // Add PUKS keys table
            sql()->getSchemaObject()->getTableObject('security_puks_keys')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `key` text NOT NULL,
                    `comments` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_security_puks_keys_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_security_puks_keys_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.15', function () {
            sql()->getSchemaObject()
                 ->getTableObject('security_incidents')->alter()
                 ->changeColumn('`severity`', '`severity` ENUM("notice", "low", "medium", "high", "severe") NULL');

        })->addUpdate('0.0.20', function () {
            sql()->getSchemaObject()
                 ->getTableObject('security_incidents')->alter()
                 ->addColumn('`body` text NOT NULL', 'AFTER `title`');
        });
    }
}
