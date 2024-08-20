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
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Web\Library;

class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.30';
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

            sql()->getSchemaObject()->getTableObject('web_non200_urls')->define()
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

            sql()->getSchemaObject()->getTableObject('web_routing_iplists')->define()
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
            sql()->getSchemaObject()->getTableObject('web_uploads')->drop()->define()
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
        });
    }
}
