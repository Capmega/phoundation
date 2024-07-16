<?php

/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

declare(strict_types=1);

namespace Phoundation\Databases\Library;



class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.25';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.20', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->getSchemaObject()->getTableObject('databases_connectors')->drop();

            // Create the database_mounts table.
            sql()->getSchemaObject()->getTableObject('databases_connectors')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `type` ENUM("sql", "memcached", "redis", "mongodb", "other"),
                    `driver` ENUM("mysql", "postgre", "oracle", "mssql"),
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `hostname` varchar(255) DEFAULT NULL,
                    `port` int(11) DEFAULT NULL,
                    `username` varchar(255) DEFAULT NULL,
                    `password` varchar(255) DEFAULT NULL,
                    `database` varchar(255) DEFAULT NULL,
                    `auto_increment` tinyint(1) NOT NULL DEFAULT 0,
                    `persist` tinyint(1) NOT NULL DEFAULT 0,
                    `init` tinyint(1) NOT NULL DEFAULT 0,
                    `buffered` tinyint(1) NOT NULL DEFAULT 0,
                    `log` tinyint(1) NOT NULL DEFAULT 0,
                    `statistics` tinyint(1) NOT NULL DEFAULT 0,
                    `limit_max` bigint NOT NULL,
                    `ssh_tunnels_id` bigint NULL DEFAULT NULL,
                    `timezones_name` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `character_set` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `collate` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `mode` varchar(1020) CHARACTER SET latin1 DEFAULT NULL,
                    `pdo_attributes` varchar(1020) CHARACTER SET latin1 DEFAULT NULL,
                    `description` mediumtext DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `type_hostname_username_database` (`type`, `hostname`, `username`, `database`),
                    KEY `type` (`type`),
                    KEY `driver` (`driver`),
                    KEY `hostname` (`hostname`),
                    KEY `port` (`port`),
                    KEY `username` (`username`),
                    KEY `database` (`database`),
                    KEY `ssh_tunnels_id` (`ssh_tunnels_id`),
                    KEY `persist` (`persist`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_databases_connectors_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_databases_connectors_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

        })->addUpdate('0.0.24', function () {
            sql()->getSchemaObject()->getTableObject('databases_connectors')->alter()
                 ->addColumn('`sync` tinyint DEFAULT 0 NOT NULL', 'AFTER `statistics`');

        })->addUpdate('0.0.25', function () {
            sql()->getSchemaObject()->getTableObject('databases_connectors')->alter()
                 ->addColumn('`environment` varchar(32) NULL DEFAULT NULL', 'AFTER `seo_name`')
                 ->addIndex('KEY `environment` (`environment`)');
        });
    }
}
