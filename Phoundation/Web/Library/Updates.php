<?php

/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Web\Library;


use Phoundation\Core\Meta\Meta;

class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.11.1';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.15', function () {
            sql()->getSchemaObject()->getTableObject('web_non200_urls')->drop();

            sql()->getSchemaObject()->getTableObject('web_non200_urls')->getDefineObject()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `ip_address_human` varchar(48) NOT NULL,
                    `ip_address` binary(16) NOT NULL,
                    `net_len` int(11) NOT NULL,
                    `method` varchar(12) DEFAULT NULL,
                    `http_code` int DEFAULT NULL,
                    `reason` varchar(255) DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `headers` mediumtext DEFAULT NULL,
                    `cookies` mediumtext DEFAULT NULL,
                    `post` mediumtext DEFAULT NULL,
                    `comments` mediumtext DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `ip_address` (`ip_address`),
                    KEY `ip_address_human` (`ip_address_human`),
                    KEY `method` (`method`),
                    KEY `http_code` (`http_code`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_web_non200_urls_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_web_non200_urls_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.20', function () {
            sql()->getSchemaObject()->getTableObject('web_routing_iplists')->drop();

            sql()->getSchemaObject()->getTableObject('web_routing_iplists')->getDefineObject()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint DEFAULT NULL,
                    `ip` varchar(64) DEFAULT NULL,
                    `white` tinyint(1) DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    KEY `ip` (`ip`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_web_routing_iplists_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_web_routing_iplists_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_web_routing_iplists_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.30', function () {
            sql()->getSchemaObject()->getTableObject('web_uploads')->drop()->getDefineObject()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(2048) NOT NULL,
                    `full_path` varchar(2048) NOT NULL,
                    `type` varchar(128) NOT NULL,
                    `tmp_name` varchar(255) NOT NULL,
                    `size` bigint NOT NULL,
                    `error` int NOT NULL,
                    `hash` varchar(128) NULL,
                    `comments` text NULL DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `size` (`size`),
                    KEY `error` (`error`),
                    KEY `type` (`type` (32)),
                    KEY `name` (`name` (64)),
                    KEY `full_path` (`full_path` (64)),
                    KEY `hash` (`hash` (64)),
                ')->setForeignKeys('
                    CONSTRAINT `fk_web_uploads_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_web_uploads_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.40', function () {
            $table = sql()->getSchemaObject()->getTableObject('web_non200_urls');

            if ($table->indexExists('ip_address_human')) {
                $table->getAlterObject()->dropIndex('`ip_address`')
                               ->dropIndex('`ip_address_human`');
            }

            if ($table->columnExists('ip_address_human')) {
                $table->getAlterObject()->changeColumn('`ip_address`'      , '`ip_address_binary` binary(16) NULL DEFAULT NULL,')
                               ->changeColumn('`ip_address_human`', '`ip_address`        varchar(48) NULL DEFAULT NULL,');
            }

            if (!$table->indexExists('ip_address')) {
                $table->getAlterObject()->addIndex('KEY `ip_address` (`ip_address`)');
            }

            if (!$table->indexExists('ip_address_binary')) {
                $table->getAlterObject()->addIndex('KEY `ip_address_binary` (`ip_address_binary`)');
            }

        })->addUpdate('0.0.41', function () {
            sql()->getSchemaObject()->getTableObject('web_uploads')->getAlterObject()->changeColumn('`error`', '`error` int(11) NULL DEFAULT NULL');

        })->addUpdate('0.8.0', function () {
            // Add support for modified_on and modified_by
            $this->ensureModifiedColumns([
                'web_non200_urls',
                'web_routing_iplists',
                'web_uploads',
            ]);

        })->addUpdate('0.8.1', function () {
            sql()->getSchemaObject()->getTableObject('web_uploads')->getAlterObject()->changeColumn('`tmp_name`', '`tmp_name` varchar(255) NULL DEFAULT NULL');
            sql()->getSchemaObject()->getTableObject('web_uploads')->getAlterObject()->changeColumn('`type`'    , '`type`     varchar(128) NULL DEFAULT NULL');
            sql()->getSchemaObject()->getTableObject('web_uploads')->getAlterObject()->changeColumn('`size`'    , '`size`     bigint       NULL DEFAULT NULL');
            sql()->getSchemaObject()->getTableObject('web_uploads')->getAlterObject()->changeColumn('`hash`'    , '`hash`     varchar(128) NULL DEFAULT NULL');

        })->addUpdate('0.9.0', function () {
            sql()->getSchemaObject()->getTableObject('web_non200_urls')->drop();

        })->addUpdate('0.10.0', function () {
            // Add the web_requests_logs
            sql()->getSchemaObject()
                 ->getTableObject('web_requests_logs')
                 ->drop()
                 ->getDefineObject()
                     ->setColumns('
                        `id` bigint NOT NULL AUTO_INCREMENT,
                        `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `created_by` bigint DEFAULT NULL,
                        `modified_on` timestamp NULL DEFAULT NULL,
                        `modified_by` bigint NULL DEFAULT NULL,
                        `meta_id` bigint NOT NULL,
                        `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                        `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                        `action` enum("sent_content", "redirected", "exception", "blocked", "other") NULL DEFAULT NULL,
                        `http_code` int NULL DEFAULT NULL,
                        `global_id` varchar(8) NOT NULL,
                        `local_id` varchar(8) NOT NULL,
                        `incidents_id` bigint NULL DEFAULT NULL,
                        `pid` int NOT NULL,
                        `platform` enum("web", "cli"),
                        `remote_ip` varchar(48) NULL DEFAULT NULL,
                        `remote_ip_real` varchar(48) NULL DEFAULT NULL,
                        `domain` varchar(255) NULL DEFAULT NULL,
                        `method` enum("get", "post", "put", "delete", "patch", "head", "options", "connect", "trace") NULL DEFAULT NULL,
                        `url` varchar(4090) NULL DEFAULT NULL,
                        `headers` mediumtext DEFAULT NULL,
                        `cookies` mediumtext NULL DEFAULT NULL,
                        `post` mediumtext NULL DEFAULT NULL,
                        `comments` mediumtext NULL DEFAULT NULL,
                        
                    ')->setIndices('
                        PRIMARY KEY (`id`),
                        KEY `created_on` (`created_on`),
                        KEY `created_by` (`created_by`),
                        KEY `modified_on` (`modified_on`),
                        KEY `modified_by` (`modified_by`),
                        KEY `status` (`status`),
                        KEY `meta_id` (`meta_id`),
                        KEY `remote_ip` (`remote_ip`),
                        KEY `remote_ip_real` (`remote_ip_real`),
                        KEY `domain` (`domain`),
                        KEY `method` (`method`),
                        KEY `http_code` (`http_code`),
                        KEY `global_id` (`global_id`),
                        KEY `local_id` (`local_id`),
                        
                    ')->setForeignKeys('
                        CONSTRAINT `fk_web_requests_logs_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_web_requests_logs_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    ')->create();

        })->addUpdate('0.11.0', function () {
            // Add the web_requests_logs
            sql()->getSchemaObject()
                 ->getTableObject('web_attack_rules')
                 ->drop()
                 ->getDefineObject()
                     ->setColumns('
                        `id` bigint NOT NULL AUTO_INCREMENT,
                        `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `created_by` bigint DEFAULT NULL,
                        `modified_on` timestamp NULL DEFAULT NULL,
                        `modified_by` bigint NULL DEFAULT NULL,
                        `meta_id` bigint NOT NULL,
                        `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                        `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                        `expression` varchar(255) NULL DEFAULT NULL,
                        `exempt` varchar(255) NULL DEFAULT NULL,
                        `action` enum("block", "ignore", "deny-access", "not-found") NULL DEFAULT NULL,
                        `seconds` int NULL DEFAULT NULL,
                        `comments` text NULL DEFAULT NULL,                       

                    ')->setIndices('
                        PRIMARY KEY (`id`),
                        KEY `created_on` (`created_on`),
                        KEY `created_by` (`created_by`),
                        KEY `modified_on` (`modified_on`),
                        KEY `modified_by` (`modified_by`),
                        KEY `status` (`status`),
                        KEY `meta_id` (`meta_id`),
                        KEY `action` (`action`),
                        
                    ')->setForeignKeys('
                        CONSTRAINT `fk_web_attack_rules_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                        CONSTRAINT `fk_web_attack_rules_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                        CONSTRAINT `fk_web_attack_rules_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    ')->create();

        })->addUpdate('0.11.1', function () {
            // Add more web attack rules
            sql()->query('INSERT INTO `web_attack_rules` (`meta_id`, `expression`, `exempt`, `action`) VALUES (:meta_id, :expression, :exempt, :action)', [
                ':expression' => '/\.well-known\//',
                ':exempt'     => '/^127.0.0.1$/',
                ':action'     => 'block',
                ':meta_id'    => Meta::init('Default rule')->getId()
            ]);

            sql()->query('INSERT INTO `web_attack_rules` (`meta_id`, `expression`, `exempt`, `action`) VALUES (:meta_id, :expression, :exempt, :action)', [
                ':expression' => '/acme-challenge\//',
                ':exempt'     => '/^127.0.0.1$/',
                ':action'     => 'block',
                ':meta_id'    => Meta::init('Default rule')->getId()
            ]);

        });
    }
}
