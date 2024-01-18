<?php

declare(strict_types=1);

namespace Phoundation\Web\Library;


/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
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
        return '0.0.15';
    }


    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('This library manages all web functionalities');
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.16', function () {
            sql()->schema()->table('web_routing_iplists')->drop();

            sql()->schema()->table('web_routing_iplists')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
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
                    KEY `ip` (`ip`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_settings_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_settings_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_settings_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.1.0', function () {
            sql()->schema()->table('accounts_signin_keys')->drop();

            sql()->schema()->table('accounts_signin_keys')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint DEFAULT NULL,
                    `uuid` varchar(36) NOT NULL,
                    `force_redirect` varchar(2048) NOT NULL,
                    `valid_until` datetime NULL DEFAULT NULL,
                    `allow_navigation` tinyint(1) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    UNIQUE KEY `uuid` (`uuid`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_signin_keys_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_signin_keys_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_signin_keys_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.1.1', function () {
            sql()->schema()->table('accounts_signin_keys')->alter()
                ->addColumn('`once` tinyint(1) NULL DEFAULT NULL', 'AFTER `allow_navigation`')
                ->changeColumn('force_redirect', 'redirect VARCHAR(2048) NULL DEFAULT NULL');

        })->addUpdate('0.1.2', function () {
            // Since sign-in count and last_sign_in were all messed up, reset them to zero
            sql()->query('UPDATE `accounts_users` SET `sign_in_count` = 0, `last_sign_in` = NULL');
        });
    }
}
