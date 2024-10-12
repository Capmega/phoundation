<?php

/**
 * Updates class
 *
 * This is the Init class for the Core library
 *
 * @see       Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Library;

use Phoundation\Core\Libraries;
use Phoundation\Core\Locale\Language\Import;
use Phoundation\Core\Log\Log;


class Updates extends Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.5.0';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.1', function () {
            sql()->getSchemaObject()->getTableObject('versions')->drop();

            // Add table for version control itself
            sql()->getSchemaObject()->getTableObject('core_versions')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `library` varchar(64) NOT NULL,
                    `version` bigint NOT NULL,
                    `comments` text DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `library` (`library`),
                    KEY `version` (`version`),
                    UNIQUE KEY `library_version` (`library`, `version`),
                ')->create();

        })->addUpdate('0.0.2', function () {
            sql()->getSchemaObject()->getTableObject('meta_history')->drop();
            sql()->getSchemaObject()->getTableObject('meta')->drop();

            // Add tables for the "meta" library
            sql()->getSchemaObject()->getTableObject('meta')->define()
                 ->setColumns('`id` bigint NOT NULL AUTO_INCREMENT')->setIndices('PRIMARY KEY (`id`)')->create();

            sql()->getSchemaObject()->getTableObject('meta_history')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `action` varchar(16) DEFAULT NULL,
                    `source` varchar(512) DEFAULT NULL,
                    `comments` varchar(255) DEFAULT NULL,
                    `data` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `meta_id` (`meta_id`),
                    KEY `action` (`action`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_meta_history_meta_id` 
                        FOREIGN KEY (`meta_id`) 
                        REFERENCES `meta` (`id`) 
                        ON DELETE CASCADE,
                ')->create();

        })->addUpdate('0.0.5', function () {
            sql()->getSchemaObject()->getTableObject('sessions_extended')->drop();

            // Modify the core_versions and meta_history tables to have a foreign key to the (now existing) accounts_users table
            sql()->getSchemaObject()->getTableObject('meta_history')->alter()
                 ->addForeignKey('
                    CONSTRAINT `fk_meta_history_created_by` 
                        FOREIGN KEY (`created_by`) 
                        REFERENCES `accounts_users` (`id`) 
                        ON DELETE RESTRICT
            ');

            sql()->getSchemaObject()->getTableObject('core_versions')->alter()
                 ->addForeignKey('
                CONSTRAINT `fk_core_versions_created_by` 
                    FOREIGN KEY (`created_by`) 
                    REFERENCES `accounts_users` (`id`) 
                    ON DELETE RESTRICT
            ');

            // Add tables for session management
            sql()->getSchemaObject()->getTableObject('sessions_extended')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `session_key` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                    `ip` int NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    UNIQUE KEY `session_key` (`session_key`),
                    KEY `ip` (`ip`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_sessions_extended_created_by` 
                        FOREIGN KEY (`created_by`) 
                        REFERENCES `accounts_users` (`id`) 
                        ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.6', function () {
            sql()->getSchemaObject()->getTableObject('url_cloaks')->drop();

            // Add tables for URL cloaking
            sql()->getSchemaObject()->getTableObject('url_cloaks')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `cloak` varchar(32) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    UNIQUE KEY `cloak` (`cloak`),
                    UNIQUE KEY `url_created_by` (`url` (32), `created_by`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_url_cloaks_created_by` 
                        FOREIGN KEY (`created_by`) 
                        REFERENCES `accounts_users` (`id`) 
                        ON DELETE RESTRICT
                ')->create();

        })->addUpdate('0.0.7', function () {
            sql()->getSchemaObject()->getTableObject('key_value_store')->drop();

            // Add tables for generic key-value store
            sql()->getSchemaObject()->getTableObject('key_value_store')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `key` varchar(64) NOT NULL,
                    `value` varchar(4096) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `key` (`key`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_key_value_store_created_by` 
                        FOREIGN KEY (`created_by`) 
                        REFERENCES `accounts_users` (`id`) 
                        ON DELETE RESTRICT,
                    CONSTRAINT `fk_key_value_store_meta_id` 
                        FOREIGN KEY (`meta_id`) 
                        REFERENCES `meta` (`id`) 
                        ON DELETE CASCADE,
                ')->create();

        })->addUpdate('0.0.8', function () {
            sql()->getSchemaObject()->getTableObject('core_languages')->drop();

            sql()->getSchemaObject()->getTableObject('core_languages')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `code_639_1` varchar(2) DEFAULT NULL,
                    `code_639_2_t` varchar(3) DEFAULT NULL,
                    `code_639_2_b` varchar(3) DEFAULT NULL,
                    `code_639_3` varchar(3) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `code_639_1` (`code_639_1`),
                    UNIQUE KEY `code_639_2_t` (`code_639_2_t`),
                    UNIQUE KEY `code_639_2_b` (`code_639_2_b`),
                    UNIQUE KEY `code_639_3` (`code_639_3`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_languages_created_by` 
                        FOREIGN KEY (`created_by`) 
                        REFERENCES `accounts_users` (`id`) 
                        ON DELETE RESTRICT,
                    CONSTRAINT `fk_languages_meta_id` 
                        FOREIGN KEY (`meta_id`) 
                        REFERENCES `meta` (`id`) 
                        ON DELETE CASCADE,
                ')->create();

            // Import all languages
            Import::new(false, 0, 0)->execute();

        })->addUpdate('0.0.10', function () {
            sql()->getSchemaObject()->getTableObject('core_templates')->drop();
            sql()->getSchemaObject()->getTableObject('core_plugins')->drop();

            // Add table for core plugin registration
            sql()->getSchemaObject()->getTableObject('core_plugins')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `enabled` tinyint NOT NULL,
                    `priority` int NOT NULL,
                    `name` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
                    `seo_name` varchar(128) CHARACTER SET latin1 DEFAULT NULL,
                    `path` varchar(128) NOT NULL,
                    `class` varchar(255) NOT NULL,
                    `description` text NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `class` (`class`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    INDEX `enabled` (`enabled`),
                    INDEX `enabled-status` (`enabled`, `status`),                    
                    INDEX `priority` (`priority`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_core_plugins_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_core_plugins_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

            // Add table for template registration
            sql()->getSchemaObject()->getTableObject('core_templates')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `file` varchar(64) NOT NULL,
                    `class` varchar(32) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `class` (`class`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_core_templates_created_by` 
                        FOREIGN KEY (`created_by`) 
                        REFERENCES `accounts_users` (`id`) 
                        ON DELETE RESTRICT,
                    CONSTRAINT `fk_core_templates_meta_id` 
                        FOREIGN KEY (`meta_id`) 
                        REFERENCES `meta` (`id`) 
                        ON DELETE CASCADE,
                ')->create();
        })->addUpdate('0.0.11', function () {
            sql()->getSchemaObject()->getTableObject('core_templates')->alter()
                 ->changeColumn('file', '`directory` varchar(128) NOT NULL');

        })->addUpdate('0.0.15', function () {
            // Fix meta_id columns
            Log::action(tr('Fixing meta_id column on all tables'), echo_newline: false);

            $tables = sql()->query('SELECT `TABLE_NAME`, `IS_NULLABLE`
                                          FROM   `information_schema`.`COLUMNS`
                                          WHERE  `TABLE_SCHEMA` = :table_schema
                                            AND  `COLUMN_NAME`  = "meta_id"', [
                ':table_schema' => sql()->getDatabase(),
            ]);

            foreach ($tables as $table) {
                if ($table['is_nullable'] === 'yes') {
                    continue;
                }

                // This table has a NOT NULL meta_id, fix it
                sql()->query('ALTER TABLE   `' . $table['table_name'] . '` 
                                    MODIFY COLUMN `meta_id` BIGINT NULL DEFAULT NULL');
                Log::dot(5);
            }

            Log::success('Finished', echo_prefix: false);

        })->addUpdate('0.1.0', function () {
            // Add table support in meta-system
            if (!sql()->getSchemaObject()->getTableObject('meta')->getColumns()->keyExists('table')) {
                sql()->getSchemaObject()->getTableObject('meta')->alter()
                     ->addColumn('`table` varchar(64) NULL DEFAULT NULL', 'AFTER `id`')
                     ->addIndex('KEY `table` (`table`)');
            }

            sql()->getSchemaObject()->getTableObject('meta_users')->drop();

            // Add users meta-tracking table
            sql()->getSchemaObject()->getTableObject('meta_users')->define()
                 ->setColumns('
                `users_id` bigint NOT NULL,
                `histories_id` bigint NOT NULL,
            ')->setIndices('
                KEY `users_id` (`users_id`),
                KEY `histories_id` (`histories_id`),
            ')->setForeignKeys('
                CONSTRAINT `fk_meta_users_histories_id` 
                    FOREIGN KEY (`histories_id`) 
                    REFERENCES `meta_history` (`id`) 
                    ON DELETE CASCADE,
                CONSTRAINT `fk_meta_users_users_id` 
                    FOREIGN KEY (`users_id`) 
                    REFERENCES `accounts_users` (`id`) 
                    ON DELETE CASCADE,
            ')->create();

        })->addUpdate('0.2.5', function () {
            sql()->getSchemaObject()->getTableObject('core_plugins')->alter()
                 ->addColumn('`menu_priority` int NOT NULL DEFAULT 50', 'AFTER `priority`')
                 ->addColumn('`menu_enabled` tinyint NOT NULL', 'AFTER `menu_priority`');

        })->addUpdate('0.2.6', function () {
            sql()->getSchemaObject()->getTableObject('core_plugins')->alter()
                 ->addColumn('`commands_enabled` tinyint NOT NULL', 'AFTER `menu_enabled`');

        })->addUpdate('0.2.7', function () {
            sql()->getSchemaObject()->getTableObject('core_plugins')->alter()
                 ->dropIndex('enabled-status')
                 ->addIndex('INDEX `enabled_status` (`enabled`, `status`)')
                 ->changeColumn('priority', '`priority` int NOT NULL DEFAULT 50');

        })->addUpdate('0.2.8', function () {
            if (!sql()->getSchemaObject()->getTableObject('core_plugins')->getColumns()->keyExists('vendor')) {
                sql()->getSchemaObject()->getTableObject('core_plugins')->alter()
                     ->addColumn('`vendor` varchar(128) NOT NULL', 'AFTER `commands_enabled`')
                     ->addIndex('KEY `vendor` (`vendor`)');
            }

        })->addUpdate('0.2.9', function () {
            if (!sql()->getSchemaObject()->getTableObject('core_plugins')->getColumns(false)->keyExists('web_enabled')) {
                sql()->getSchemaObject()->getTableObject('core_plugins')->alter()
                     ->changeColumn('enabled', 'web_enabled tinyint NOT NULL');

                $column = sql()->getSchemaObject()->getTableObject('core_plugins')->getColumns(false)->get('web_enabled');

                if ($column['key'] === 'UNI') {
                    // this key should NOT be unique
                    sql()->getSchemaObject()->getTableObject('core_plugins')->alter()
                         ->dropIndex('web_enabled')
                         ->addIndex('KEY `web_enabled` (`web_enabled`)');
                }
            }

        })->addUpdate('0.2.10', function () {
            // Fix in case this table still has a "path" column instead of a "directory" column
            if (sql()->getSchemaObject()->getTableObject('core_plugins')->columnExists('path')) {
                sql()->getSchemaObject()->getTableObject('core_plugins')->alter()
                     ->changeColumn('path', 'directory varchar(255) NOT NULL');
            }

        })->addUpdate('0.4.12', function () {
            // Improve meta_history table
            Log::action(tr('Fixing meta table structure, this may take a second...'));

            sql()->getSchemaObject()
                     ->getTableObject('meta_history')
                         ->alter()
                             ->changeColumn('comments', '`comments` varchar(2048) NULL DEFAULT NULL');

            Log::action(tr('Fixing meta table content, this may take a second...'));
            sql()->query('UPDATE `meta_history` SET `comments` = NULL WHERE `comments` = ""');

        })->addUpdate('0.5.0', function () {
            // Add indexed "can_startup" column to core_plugins table
            $table = sql()->getSchemaObject()->getTableObject('core_plugins');

            if (!$table->columnExists('can_startup')) {
                $table->alter()
                      ->addColumn('`can_startup` tinyint NOT NULL', 'AFTER `status`');

                if (!$table->indexExists('can_startup')) {
                    $table->alter()
                          ->addIndex('KEY `can_startup` (`can_startup`)');
                }
            }
        });
    }
}
