<?php

namespace Phoundation\Core;


use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;

/**
 * Updates class
 *
 * This is the Init class for the Core library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Updates extends Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.11';
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
            sql()->schema()->table('versions')->drop();

            // Add table for version control itself
            sql()->schema()->table('core_versions')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `library` VARCHAR(64) NOT NULL,
                    `version` bigint NOT NULL,
                    `comments` VARCHAR(2048) NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    INDEX `library` (`library`),
                    INDEX `version` (`version`),
                    UNIQUE `library_version` (`library`, `version`),
                ')
                ->create();

        })->addUpdate('0.0.2', function () {
            sql()->schema()->table('meta_history')->drop();
            sql()->schema()->table('meta')->drop();

            // Add tables for the meta library
            sql()->schema()->table('meta')->define()
                ->setColumns('`id` bigint NOT NULL AUTO_INCREMENT')->setIndices('PRIMARY KEY (`id`)')
                ->create();

            sql()->schema()->table('meta_history')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
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
                    CONSTRAINT `fk_meta_history_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();

        })->addUpdate('0.0.5', function () {
            sql()->schema()->table('sessions_extended')->drop();

            // Modify the core_versions and meta_history tables to have a foreign key to the (now existing) accounts_users table
            sql()->schema()->table('meta_history')->alter()->addForeignKey('
                CONSTRAINT `fk_meta_history_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
            ');

            sql()->schema()->table('core_versions')->alter()->addForeignKey('
                CONSTRAINT `fk_core_versions_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
            ');

            // Add tables for the sessions management
            sql()->schema()->table('sessions_extended')->define()
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
                    CONSTRAINT `fk_sessions_extended_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')
                ->create();

        })->addUpdate('0.0.6', function () {
            sql()->schema()->table('url_cloaks')->drop();

            // Add tables for the sessions management
            sql()->schema()->table('url_cloaks')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `status` VARCHAR(16) DEFAULT NULL,
                    `url` varchar(140) NOT NULL,
                    `cloak` varchar(32) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    UNIQUE KEY `cloak` (`cloak`),
                    UNIQUE KEY `url_created_by` (`url`,`created_by`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_url_cloaks_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')
                ->create();

        })->addUpdate('0.0.7', function () {
            sql()->schema()->table('key_value_store')->drop();

            // Add tables for the sessions management
            sql()->schema()->table('key_value_store')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
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
                    CONSTRAINT `fk_key_value_store_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_key_value_store_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();

        })->addUpdate('0.0.8', function () {
            sql()->schema()->table('languages')->drop();

            sql()->schema()->table('languages')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) DEFAULT NULL,
                    `seo_name` varchar(32) DEFAULT NULL,
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
                    CONSTRAINT `fk_languages_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_languages_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();

        })->addUpdate('0.0.10', function () {
            sql()->schema()->table('core_templates')->drop();
            sql()->schema()->table('core_plugins')->drop();

            // Add table for plugins registration
            sql()->schema()->table('core_plugins')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `priority` bigint DEFAULT NULL,
                    `name` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                    `seo_name` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                    `file` varchar(64) NOT NULL,
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
                    INDEX `priority` (`priority`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_core_plugins_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_core_plugins_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();

            // Add table for teplates registration
            sql()->schema()->table('core_templates')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) DEFAULT NULL,
                    `seo_name` varchar(32) DEFAULT NULL,
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
                    CONSTRAINT `fk_core_templates_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_core_templates_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();

        })->addUpdate('0.0.11', function () {
            // Create some default roles and rights
            $rights = [
                'god',
                'logs',
                'admin',
                'audit',
                'accounts',
                'security',
                'impersonate',
            ];

            // Add default rights
            foreach ($rights as $right) {
                if (!Right::exists($right)) {
                    Right::new()
                        ->setName($right)
                        ->save();
                }

            }

            // Add default roles and assign the default rights to them
            foreach ($rights as $role) {
                if (!Role::exists($role)) {
                    Role::new()
                        ->setName($role)
                        ->save();
                }

                Role::get($role)->rights()->add($role);
            }

            // Various rights go together...
            Role::get('audit')->rights()->add('admin');

            Role::get('security')->rights()->add('admin');

            Role::get('impersonate')->rights()->add('admin');
            Role::get('impersonate')->rights()->add('accounts');
        });
    }
}
