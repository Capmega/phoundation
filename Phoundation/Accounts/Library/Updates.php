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

namespace Phoundation\Accounts\Library;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\GuestUser;
use Phoundation\Core\Log\Log;
use Phoundation\Seo\Seo;


class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.5.2';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.4', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->getSchemaObject()->getTableObject('accounts_roles_rights')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_users_roles')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_users_rights')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_groups')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_roles')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_rights')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_users')->drop();

            // Create the users table.
            sql()->getSchemaObject()->getTableObject('accounts_users')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `last_sign_in` datetime DEFAULT NULL,
                    `authentication_failures` int NOT NULL,
                    `locked_until` datetime DEFAULT NULL,
                    `sign_in_count` int NOT NULL,
                    `username` varchar(64) NULL DEFAULT NULL,
                    `password` varchar(255) NULL DEFAULT NULL,
                    `fingerprint` datetime DEFAULT NULL,
                    `domain` varchar(128) NULL DEFAULT NULL,
                    `title` varchar(24) NULL DEFAULT NULL,
                    `first_names` varchar(128) NULL DEFAULT NULL,
                    `last_names` varchar(128) NULL DEFAULT NULL,
                    `nickname` varchar(128) NULL DEFAULT NULL,
                    `picture` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `email` varchar(128) NULL DEFAULT NULL,
                    `code` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `type` varchar(16) NULL DEFAULT NULL,
                    `keywords` varchar(255) NULL DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `address` varchar(255) NULL DEFAULT NULL,
                    `zipcode` varchar(8) NULL DEFAULT NULL,
                    `verified_on` datetime NULL DEFAULT NULL,
                    `verification_code` varchar(128) NULL DEFAULT NULL,
                    `priority` int NULL DEFAULT NULL,
                    `is_leader` int NULL DEFAULT NULL,
                    `leaders_id` bigint NULL DEFAULT NULL,
                    `latitude` decimal(18,15) NULL DEFAULT NULL,
                    `longitude` decimal(18,15) NULL DEFAULT NULL,
                    `accuracy` int NULL DEFAULT NULL,
                    `offset_latitude` decimal(18,15) NULL DEFAULT NULL,
                    `offset_longitude` decimal(18,15) NULL DEFAULT NULL,
                    `cities_id` bigint NULL DEFAULT NULL,
                    `states_id` bigint NULL DEFAULT NULL,
                    `countries_id` bigint NULL DEFAULT NULL,
                    `timezones_id` bigint NULL DEFAULT NULL,
                    `redirect` varchar(2048) NULL DEFAULT NULL,
                    `languages_id` bigint NULL DEFAULT NULL,
                    `gender` varchar(16) NULL DEFAULT NULL,
                    `birthdate` date DEFAULT NULL,
                    `url` varchar(2048) NULL DEFAULT NULL,
                    `description` text NULL DEFAULT NULL,
                    `comments` mediumtext NULL DEFAULT NULL
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `verification_code` (`verification_code`),
                    UNIQUE KEY `email` (`email`),
                    KEY `domain` (`domain`),
                    KEY `verified_on` (`verified_on`),
                    KEY `languages_id` (`languages_id`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `birthdate` (`birthdate`),
                    KEY `code` (`code`),
                    KEY `type` (`type`),
                    KEY `phones` (`phones`),
                    KEY `is_leader` (`is_leader`),
                    KEY `leaders_id` (`leaders_id`),
                    KEY `nickname` (`nickname`),
                    KEY `priority` (`priority`),
                    KEY `fingerprint` (`fingerprint`),
                    KEY `cities_id` (`cities_id`),
                    KEY `states_id` (`states_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `timezones_id` (`timezones_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_leaders_id` FOREIGN KEY (`leaders_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_users_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Create the users_rights table.
            sql()->getSchemaObject()->getTableObject('accounts_rights')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) NOT NULL,
                    `seo_name` varchar(128) NOT NULL,
                    `description` varchar(2047) NULL DEFAULT NULL
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_rights_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_rights_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

            // Create the users_roles table.
            sql()->getSchemaObject()->getTableObject('accounts_roles')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) NULL DEFAULT NULL,
                    `seo_name` varchar(128) NULL DEFAULT NULL,
                    `description` text NULL DEFAULT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_roles_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_roles_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

            // Create the users_groups table.
            sql()->getSchemaObject()->getTableObject('accounts_groups')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) NULL DEFAULT NULL,
                    `seo_name` varchar(128) NULL DEFAULT NULL,
                    `description` text NULL DEFAULT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_groups_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_groups_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

            // Create the users_rights_links table.
            sql()->getSchemaObject()->getTableObject('accounts_users_rights')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `users_id` bigint NOT NULL,
                    `rights_id` bigint NOT NULL,
                    `name` varchar(128) NOT NULL,
                    `seo_name` varchar(128) NULL DEFAULT NULL
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_rights` (`users_id`,`rights_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `users_id` (`users_id`),
                    KEY `rights_id` (`rights_id`),
                    KEY `name` (`name`),
                    KEY `seo_name` (`seo_name`)
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_users_rights_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_rights_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_users_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE CASCADE,
                ')->create();

            // Create the users_roles_links table.
            sql()->getSchemaObject()->getTableObject('accounts_users_roles')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `users_id` bigint NOT NULL,
                    `roles_id` bigint NOT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_roles` (`users_id`,`roles_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `roles_id` (`roles_id`),
                    KEY `users_id` (`users_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_users_roles_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_roles_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_users_roles_roles_id` FOREIGN KEY (`roles_id`) REFERENCES `accounts_roles` (`id`) ON DELETE CASCADE
                ')->create();

            // Create the users_roles_rights_links table.
            sql()->getSchemaObject()->getTableObject('accounts_roles_rights')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `roles_id` bigint NOT NULL,
                    `rights_id` bigint NOT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `roles_rights` (`roles_id`,`rights_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `roles_id` (`roles_id`),
                    KEY `rights_id` (`rights_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_roles_rights_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_roles_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_roles_rights_roles_id` FOREIGN KEY (`roles_id`) REFERENCES `accounts_roles` (`id`) ON DELETE CASCADE
                ')->create();

        })->addUpdate('0.0.5', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->getSchemaObject()->getTableObject('accounts_compromised_passwords')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_old_passwords')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_password_resets')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_authentications')->drop();

            // Create additional user tables.
            sql()->getSchemaObject()->getTableObject('accounts_authentications')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `captcha_required` tinyint(1) NOT NULL,
                    `failed_reason` varchar(127) NULL DEFAULT NULL,
                    `users_id` bigint NULL DEFAULT NULL,
                    `username` varchar(64) NOT NULL,
                    `ip` varchar(46) NULL DEFAULT NULL,
                    `action` enum("authentication", "signin") CHARACTER SET latin1 NOT NULL DEFAULT "authentication",
                    `method` enum("password", "google", "facebook") CHARACTER SET latin1 NOT NULL DEFAULT "password",
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    KEY `ip` (`ip`),
                    KEY `action` (`action`),
                    KEY `method` (`method`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_authentications_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_authentications_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_authentications_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE,
                ')->create();

            sql()->getSchemaObject()->getTableObject('accounts_password_resets')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `code` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `date_requested` int DEFAULT "0",
                    `date_used` int DEFAULT "0",
                    `ip` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_users_password_resets_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

            sql()->getSchemaObject()->getTableObject('accounts_old_passwords')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `password` varchar(255) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `password` (`password`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_old_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

            sql()->getSchemaObject()->getTableObject('accounts_compromised_passwords')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `password` varchar(255) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `password` (`password`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_banned_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

        })->addUpdate('0.0.6', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->getSchemaObject()->getTableObject('accounts_signins')->drop();

            sql()->getSchemaObject()->getTableObject('accounts_signins')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `method` varchar(32) NOT NULL,
                    `ip_address_human` varchar(48) NOT NULL,
                    `ip_address` binary(16) NOT NULL,
                    `net_len` int(11) NOT NULL,
                    `user_agent` varchar(2040) NULL,
                    `latitude` decimal(10,7) NULL,
                    `longitude` decimal(10,7) NULL,
                    `timezones_id` bigint NULL DEFAULT NULL,
                    `countries_id` bigint NULL DEFAULT NULL,
                    `states_id` bigint NULL DEFAULT NULL,
                    `cities_id` bigint NULL DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `method` (`method`),
                    KEY `ip_address` (`ip_address`),
                    KEY `ip_address_human` (`ip_address_human`),
                    KEY `user_agent` (`user_agent`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_signins_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_signins_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_signins_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_signins_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_signins_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_signins_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.7', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->getSchemaObject()->getTableObject('accounts_authentication_failures')->drop();

            sql()->getSchemaObject()->getTableObject('accounts_authentication_failures')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `method` varchar(32) NOT NULL,
                    `ip_address_human` varchar(48) NOT NULL,
                    `ip_address` binary(16) NOT NULL,
                    `net_len` int(11) NOT NULL,
                    `user_agent` varchar(2040) NULL,
                    `latitude` decimal(10,7) NOT NULL,
                    `longitude` decimal(10,7) NOT NULL,
                    `timezones_id` bigint NULL DEFAULT NULL,
                    `countries_id` bigint NULL DEFAULT NULL,
                    `states_id` bigint NULL DEFAULT NULL,
                    `cities_id` bigint NULL DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `method` (`method`),
                    KEY `ip_address` (`ip_address`),
                    KEY `ip_address_human` (`ip_address_human`),
                    KEY `user_agent` (`user_agent`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_authentication_failures_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_authentication_failures_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_authentication_failures_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_authentication_failures_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_authentication_failures_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_authentication_failures_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.12', function () {
            // Add "password_update" column
            sql()->getSchemaObject()->getTableObject('accounts_users')->alter()->addColumn('`update_password` datetime DEFAULT NULL', 'AFTER `password`');

        })->addUpdate('0.0.13', function () {
            // Fix minor db issues
            sql()->query('UPDATE `accounts_users` SET `locked_until` = NULL');
            sql()->query('UPDATE `accounts_users` SET `first_names` = "guest" WHERE `email` = "guest"');

        })->addUpdate('0.0.14', function () {
            // Fix minor db issues
            sql()->query('UPDATE `accounts_users` SET `verified_on` = NULL');

        })->addUpdate('0.0.17', function () {
            // Add support for notifications_hash
            sql()->getSchemaObject()->getTableObject('accounts_users')->alter()->addColumn('`notifications_hash` varchar(40) NULL DEFAULT NULL', 'AFTER `fingerprint`');

        })->addUpdate('0.0.18', function () {
            // Add support for multiple emails and phones per account
            sql()->getSchemaObject()->getTableObject('accounts_emails')->drop();
            sql()->getSchemaObject()->getTableObject('accounts_phones')->drop();

            sql()->getSchemaObject()->getTableObject('accounts_emails')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NULL DEFAULT NULL,
                    `account_type` enum("personal", "business", "other") NULL DEFAULT NULL,
                    `email` varchar(128) NULL DEFAULT NULL,
                    `verified_on` datetime NULL DEFAULT NULL,
                    `verification_code` varchar(128) NULL DEFAULT NULL,
                    `description` TEXT NULL DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    UNIQUE KEY `email` (`email`),
                    KEY `account_type` (`account_type`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_emails_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_emails_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_emails_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

            sql()->getSchemaObject()->getTableObject('accounts_phones')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NULL DEFAULT NULL,
                    `account_type` enum("personal", "business", "other") NULL DEFAULT NULL,
                    `phone` varchar(16) NULL DEFAULT NULL,
                    `verified_on` datetime NULL DEFAULT NULL,
                    `verification_code` varchar(128) NULL DEFAULT NULL,
                    `description` TEXT NULL DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    UNIQUE KEY `phone` (`phone`),
                    KEY `account_type` (`account_type`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_phones_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_phones_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_phones_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.0.19', function () {
            sql()->getSchemaObject()->getTableObject('accounts_users')->alter()
                 ->changeColumn('phones', '`phone` varchar(15) CHARACTER SET latin1 DEFAULT NULL')
                 ->dropIndex('phones')
                 ->addIndex('KEY `phone` (`phone`)');

        })->addUpdate('0.0.20', function () {
            sql()->getSchemaObject()->getTableObject('accounts_phones')->alter()
                 ->dropForeignKey('fk_accounts_phones_users_id')
                 ->changeColumn('phone', '`phone` varchar(24) CHARACTER SET latin1 DEFAULT NULL')
                 ->changeColumn('users_id', '`users_id` BIGINT NOT NULL')
                 ->addForeignKey('CONSTRAINT `fk_accounts_phones_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT')
                 ->dropIndex('phone')
                 ->addIndex('KEY `phone` (`phone`)');

            sql()->getSchemaObject()->getTableObject('accounts_emails')->alter()
                 ->dropForeignKey('fk_accounts_emails_users_id')
                 ->changeColumn('users_id', '`users_id` BIGINT NOT NULL')
                 ->addForeignKey('CONSTRAINT `fk_accounts_emails_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT');

        })->addUpdate('0.0.21', function () {
            // Ensure that all roles and rights are lowercase
            sql()->query('UPDATE `accounts_roles`
                                SET    `name` = LOWER(REPLACE(REPLACE(`name`, "_", "-"), " ", "-"))');

            sql()->query('UPDATE `accounts_rights`
                                SET    `name` = LOWER(REPLACE(REPLACE(`name`, "_", "-"), " ", "-"))');

        })->addUpdate('0.0.24', function () {
            sql()->getSchemaObject()->getTableObject('accounts_settings')->drop();

            sql()->getSchemaObject()->getTableObject('accounts_settings')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NULL DEFAULT NULL,
                    `path` varchar(255) NOT NULL,
                    `hash` varchar(40) NOT NULL,
                    `value` varchar(255),
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    KEY `path` (`path`),
                    UNIQUE KEY `users_id_hash` (`users_id`, `hash`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_settings_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_settings_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_settings_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

        })->addUpdate('0.1.0', function () {
            sql()->getSchemaObject()->getTableObject('accounts_signin_keys')->drop();

            sql()->getSchemaObject()->getTableObject('accounts_signin_keys')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NULL DEFAULT NULL,
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
            sql()->getSchemaObject()->getTableObject('accounts_signin_keys')->alter()
                 ->addColumn('`once` tinyint(1) NULL DEFAULT NULL', 'AFTER `allow_navigation`')
                 ->changeColumn('force_redirect', 'redirect VARCHAR(2048) NULL DEFAULT NULL');

        })->addUpdate('0.1.2', function () {
            // Since sign-in count and last_sign_in were all messed up, reset them to zero
            sql()->query('UPDATE `accounts_users` SET `sign_in_count` = 0, `last_sign_in` = NULL');

        })->addUpdate('0.1.3', function () {
            sql()->getSchemaObject()->getTableObject('accounts_rights')->alter()
                 ->dropIndex('name')
                 ->addIndex('UNIQUE KEY `name` (`name`)');

            sql()->getSchemaObject()->getTableObject('accounts_roles')->alter()
                 ->dropIndex('name')
                 ->addIndex('UNIQUE KEY `name` (`name`)');

            // Fix rights / roles names
            sql()->query('UPDATE `accounts_roles`  SET `name` = REPLACE(LCASE(`name`), " ", "-");');
            sql()->query('UPDATE `accounts_rights` SET `name` = REPLACE(LCASE(`name`), " ", "-");');

            // Fix all seo_name column entries
            $tables = [
                'accounts_roles',
                'accounts_rights',
            ];

            foreach ($tables as $table) {
                Log::action(tr('Fixing table ":table" seo_name', [
                    ':table' => $table,
                ]));

                $entries = sql()->query('SELECT `id`, `name` FROM ' . $table);

                foreach ($entries as $entry) {
                    sql()->update($table, ['seo_name' => Seo::unique($entry['name'], $table, $entry['id'], 'seo_name')], ['id' => $entry['id']]);
                }
            }

            Log::action(tr('Fixing table "accounts_users_rights" seo_name'));

            $entries = sql()->query('SELECT `id`, `rights_id`, `name` FROM `accounts_users_rights`');

            foreach ($entries as $entry) {
                $right = Right::load($entry['rights_id']);

                sql()->update('accounts_users_rights', [
                    'name'     => $right->getName(),
                    'seo_name' => $right->getSeoName(),
                ],            [
                                  'id' => $entry['id'],
                              ]);
            }

        })->addUpdate('0.2.0', function () {
            // Add parents_id to roles table
            sql()->getSchemaObject()->getTableObject('accounts_roles')->alter()
                 ->addColumn('`parents_id` bigint NULL DEFAULT NULL', 'AFTER `status`')
                 ->addIndex('KEY `parents_id` (`parents_id`)')
                 ->addForeignKey('CONSTRAINT `fk_accounts_roles_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `accounts_roles` (`id`) ON DELETE RESTRICT');

        })->addUpdate('0.2.1', function () {
            // Ensure Guest user is available
            GuestUser::new('guest');

            // Create default rights and roles
            $god = Right::new(['name' => 'God'])
                        ->setName('God')
                        ->setDescription('This right will give the user access to everything, everywhere')
                        ->save();

            $admin = Right::new(['name' => 'Admin'])
                          ->setName('Admin')
                          ->setDescription('This right will give the user access to the administrative area of the site, but no specific pages yet')
                          ->save();

            $developer = Right::new(['name' => 'Developer'])
                              ->setName('Developer')
                              ->setDescription('This right will give the user access to the developer area of the site')
                              ->save();

            $accounts = Right::new(['name' => 'Accounts'])
                             ->setName('Accounts')
                             ->setDescription('This right will give the user access to the administrative user accounts management section of the site')
                             ->save();

            $security = Right::new(['name' => 'Security'])
                             ->setName('Security')
                             ->setDescription('This right will give the user access to the administrative security pages of the site')
                             ->save();

            $phoundation = Right::new(['name' => 'Phoundation'])
                                ->setName('Phoundation')
                                ->setDescription('This right will give the user access to the administrative phoundation system management section of the site')
                                ->save();

            $audit = Right::new(['name' => 'Audit'])
                          ->setName('Audit')
                          ->setDescription('This right will give the user access to the audit information system of the site')
                          ->save();

            $test = Right::new(['name' => 'Test'])
                         ->setDescription('This right will make certain pages run in test mode. Information from this user may, for example, not show up in reports as it is a test user, generating test data')
                         ->save();

            $demo = Right::new(['name' => 'Demo'])
                         ->setDescription('This right will make certain pages run in demo mode. Information from this user may, for example, not show up in reports as it is a demonstration user, generating demo data')
                         ->save();

            Role::new(['name' => 'Test'])
                ->setDescription('This role gives the user the test right. See demo right for more information.')
                ->save()
                ->getRightsObject()
                ->add($test);

            Role::new(['name' => 'Demo'])
                ->setDescription('This role gives the user the demo right. See demo right for more information.')
                ->save()
                ->getRightsObject()
                ->add($demo);

            // Define basic roles
            Role::new(['name' => 'God'])
                ->setName('God')
                ->setDescription('This role will give the user the "God" right which will give it access to everything, everywhere')
                ->save()
                ->getRightsObject()
                ->add($god);

            Role::new(['name' => 'Audit'])
                ->setName('Audit')
                ->setDescription('This role will give the user access to the audit system')
                ->save()
                ->getRightsObject()
                ->add($audit);

            Role::new(['name' => 'Accounts'])
                ->setName('Accounts')
                ->setDescription('This role will give the user access to the accounts management system')
                ->save()
                ->getRightsObject()
                ->add($accounts);

            Role::new(['name' => 'Security'])
                ->setName('Security')
                ->setDescription('This role will give the user access to the security system')
                ->save()
                ->getRightsObject()
                ->add($security);

            Role::new(['name' => 'Administrator'])
                ->setName('Administrator')
                ->setDescription('This role gives access to all the administrative pages except user account management')
                ->save()
                ->getRightsObject()
                ->add($admin)
                ->add($audit)
                ->add($security)
                ->add($phoundation);

            Role::new(['name' => 'Accounts administrator'])
                ->setName('Accounts administrator')
                ->setDescription('This role gives access to only the administrative user account pages')
                ->save()
                ->getRightsObject()
                ->add($admin)
                ->add($accounts);

            Role::new(['name' => 'Developer'])
                ->setName('Developer')
                ->setDescription('This role will give the user access to the developer pages of the site')
                ->save()
                ->getRightsObject()
                ->add($developer);

            Role::new(['name' => 'Moderator'])
                ->setName('Moderator')
                ->setDescription('This role will give the user basic access to the administrative pages of the site')
                ->save()
                ->getRightsObject()
                ->add($admin);

            // Create some default roles and rights
            $rights = [
                'Accounts',
                'Admin',
                'Impersonate',
                'Logs',
                'My',
                'Notifications',
                'Profiles',
                'System',
            ];

            // Add default rights
            foreach ($rights as $right) {
                if (!Right::exists(['name' => $right])) {
                    Right::new(['name' => $right])
                         ->setName($right)
                         ->save();
                }
            }

            // Add default roles and assign the default rights to them
            foreach ($rights as $role) {
                if (!Role::exists(['name' => $role])) {
                    Role::new(['name' => $role])
                        ->setName($role)
                        ->save()
                        ->getRightsObject()
                        ->add($role);
                }
            }

            // Various rights go together...
            Role::load('Audit')->getRightsObject()->add('Admin');
            Role::load('Security')->getRightsObject()->add('Admin');
            Role::load('Impersonate')
                ->getRightsObject()
                    ->add('Admin')
                    ->add('Accounts');

        })->addUpdate('0.2.2', function () {
            // Data is a general storage of JSON data
            if (!sql()->getSchemaObject()->getTableObject('accounts_users')->getColumns()->keyExists('data')) {
                sql()->getSchemaObject()->getTableObject('accounts_users')->alter()
                     ->addColumn('`data` mediumtext NULL DEFAULT NULL', 'AFTER `description`');
            }

            // Remote id is the ID of the user in a different table and or database
            if (!sql()->getSchemaObject()->getTableObject('accounts_users')->getColumns()->keyExists('remote_id')) {
                sql()->getSchemaObject()->getTableObject('accounts_users')->alter()
                     ->addColumn('`remote_id` bigint NULL DEFAULT NULL', 'AFTER `meta_state`')
                     ->addIndex('UNIQUE KEY `remote_id` (`remote_id`)');
            }

        })->addUpdate('0.2.3', function () {
            // Codes can be UUID (36 characters) or much larger, so make it 64 characters
            sql()->getSchemaObject()->getTableObject('accounts_users')->alter()
                 ->modifyColumn('`code`', ' varchar(64) CHARACTER SET latin1 DEFAULT NULL');

            // The default page will send the user to that page right after signing in
            if (!sql()->getSchemaObject()->getTableObject('accounts_users')->getColumns()->keyExists('default_page')) {
                sql()->getSchemaObject()->getTableObject('accounts_users')->alter()
                     ->addColumn('`default_page` varchar(2048) NULL DEFAULT NULL', 'AFTER `url`');
            }

        })->addUpdate('0.2.4', function () {
            // Guest user will have status "guest" as well.
            sql()->query('UPDATE `accounts_users` SET `status` = "system" WHERE `email` = "guest"');

        })->addUpdate('0.3.0', function () {
            // Create the accounts_push_notifications table.
            sql()->getSchemaObject()->getTableObject('accounts_push_notifications')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NOT NULL,
                    `driver` varchar(32) NOT NULL,
                    `device` varchar(32) NOT NULL,
                    `token` varchar(255) NULL DEFAULT NULL
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    KEY `driver` (`driver`),
                    KEY `device` (`device`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_push_notifications_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_push_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_push_notifications_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

        })->addUpdate('0.3.1', function () {
            // Guest user will have status "guest" as well.
            sql()->query('UPDATE `accounts_users` SET `status` = "system" WHERE `email` = "guest"');

        })->addUpdate('0.4.0', function () {
            $table = sql()->getSchemaObject()->getTableObject('accounts_users');

            if ($table->foreignKeyExists('fk_accounts_users_profile_images_id')) {
                $table->alter()->dropForeignKey('fk_accounts_users_profile_images_id');
            }

            if ($table->indexExists('profile_images_id')) {
                $table->alter()->dropIndex('profile_images_id');
            }

            if ($table->columnExists('profile_images_id')) {
                $table->alter()->dropColumn('profile_images_id');
            }

            // Create the accounts_profile_images table.
            sql()->getSchemaObject()->getTableObject('accounts_profile_images')->drop()->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint NULL DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NOT NULL,
                    `uploads_id` bigint NULL DEFAULT NULL,
                    `file` varchar(2048) NOT NULL,
                    `description` TEXT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `users_id` (`users_id`),
                    KEY `uploads_id` (`uploads_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_profile_images_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_profile_images_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_profile_images_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_profile_images_uploads_id` FOREIGN KEY (`uploads_id`) REFERENCES `web_uploads` (`id`) ON DELETE RESTRICT
                ')->create();

            sql()->getSchemaObject()->getTableObject('accounts_users')->alter()
                ->addColumn('`profile_images_id` bigint NULL DEFAULT NULL', 'AFTER `status`')
                ->addIndex('KEY `profile_images_id` (`profile_images_id`)')
                ->addForeignKey('CONSTRAINT `fk_accounts_users_profile_images_id` FOREIGN KEY (`profile_images_id`) REFERENCES `accounts_profile_images` (`id`) ON DELETE RESTRICT');

        })->addUpdate('0.4.1', function () {
            $table = sql()->getSchemaObject()->getTableObject('accounts_users');

            // Fix the mess with picture column may or may not exist as picture, or profile_image, or not at all?
            if ($table->columnExists('picture')) {
                $table->alter()->changeColumn('`picture`', '`profile_image` varchar(2048) CHARACTER SET latin1 DEFAULT NULL');

            } elseif ($table->columnExists('profile_image')) {
                $table->alter()->changeColumn('`profile_image`', '`profile_image` varchar(2048) CHARACTER SET latin1 DEFAULT NULL');

            } else {
                $table->alter()->addColumn('`profile_image` varchar(2048) CHARACTER SET latin1 DEFAULT NULL', 'AFTER `nickname`');
            }

        })->addUpdate('0.4.11', function () {
            $table = sql()->getSchemaObject()->getTableObject('accounts_authentication_failures');
            $alter = $table->alter();

            if (!$table->columnExists('matched_users_id')) {
                $alter->addColumn('`matched_users_id` bigint NULL DEFAULT NULL', 'AFTER `status`');
            }

            if (!$table->columnExists('account')) {
                $alter->addColumn('`account` varchar(128) NULL DEFAULT NULL', 'AFTER `status`');
            }

            if (!$table->indexExists('matched_users_id')) {
                $alter->addIndex('KEY `matched_users_id` (`matched_users_id`)');
            }

            if (!$table->indexExists('account')) {
                $alter->addIndex('KEY `account` (`account`)');
            }

            if (!$table->foreignKeyExists('fk_accounts_authentication_failures_matched_users_id')) {
                $alter->addForeignKey('CONSTRAINT `fk_accounts_authentication_failures_matched_users_id` FOREIGN KEY (`matched_users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT');
            }

        })->addUpdate('0.5.0', function () {
            // Upgrade the authentication registrations. All authentications will now be stored in
            // the table accounts_authentications
            sql()->getSchemaObject()->getTableObject('accounts_authentication_failures')->drop();

            $table = sql()->getSchemaObject()->getTableObject('accounts_authentications');

            if ($table->foreignKeyExists('fk_accounts_authentications_users_id')) {
                $table->alter()->dropForeignKey('`fk_accounts_authentications_users_id`');
            }

            if ($table->indexExists('users_id')) {
                $table->alter()->dropIndex('`users_id`');
            }

            if ($table->columnExists('users_id')) {
                $table->alter()->dropColumn('`users_id`');
            }

            if ($table->columnExists('ip')) {
                $table->alter()->dropColumn('`ip`');
            }

            if ($table->columnExists('username')) {
                $table->alter()->dropColumn('`username`');
            }

            if (!$table->columnExists('account')) {
                $table->alter()->addColumn('`account` varchar(128) COLLATE utf8mb4_general_ci NULL DEFAULT NULL,', 'AFTER `method`');
            }

            if (!$table->columnExists('platform')) {
                $table->alter()->addColumn('`platform` ENUM("html", "ajax", "api", "cli", "other") NOT NULL,', 'AFTER `account`');
            }

            if (!$table->columnExists('method')) {
                $table->alter()->addColumn('`method` enum("password", "magic", "sso", "google", "facebook", "other") CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT "password",', 'AFTER `platform`');
            }

            if (!$table->columnExists('ip_address')) {
                $table->alter()->addColumn('`ip_address` varchar(48) COLLATE utf8mb4_general_ci NULL DEFAULT NULL,', 'AFTER `method`');
            }

            if (!$table->columnExists('ip_address_binary')) {
                $table->alter()->addColumn('`ip_address_binary` binary(16) NULL DEFAULT NULL,', 'AFTER `ip_address`');
            }

            if (!$table->columnExists('net_len')) {
                $table->alter()->addColumn('`net_len` int NOT NULL DEFAULT 0,', 'AFTER `ip_address_binary`');
            }

            if (!$table->columnExists('user_agent')) {
                $table->alter()->addColumn('`user_agent` varchar(2040) COLLATE utf8mb4_general_ci NULL DEFAULT NULL,', 'AFTER `net_len`');
            }

            if (!$table->columnExists('latitude')) {
                $table->alter()->addColumn('`latitude` decimal(10,7) NULL DEFAULT NULL,', 'AFTER `user_agent`');
            }

            if (!$table->columnExists('longitude')) {
                $table->alter()->addColumn('`longitude` decimal(10,7) NULL DEFAULT NULL,', 'AFTER `latitude`');
            }

            if (!$table->columnExists('timezones_id')) {
                $table->alter()->addColumn('`timezones_id` bigint NULL DEFAULT NULL,', 'AFTER `longitude`');
            }

            if (!$table->columnExists('countries_id')) {
                $table->alter()->addColumn('`countries_id` bigint NULL DEFAULT NULL,', 'AFTER `timezones_id`');
            }

            if (!$table->columnExists('states_id')) {
                $table->alter()->addColumn('`states_id` bigint NULL DEFAULT NULL,', 'AFTER `countries_id`');
            }

            if (!$table->columnExists('cities_id')) {
                $table->alter()->addColumn('`cities_id` bigint NULL DEFAULT NULL,', 'AFTER `states_id`');
            }

            $table->alter()->changeColumn('`action`'       , '`action` enum("authentication", "signin", "signout", "startimpersonation", "stopimpersonation", "other") CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,');
            $table->alter()->changeColumn('`method`'       , '`method` enum("password", "magic", "sso", "google", "facebook", "other") CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,');
            $table->alter()->changeColumn('`failed_reason`', '`failed_reason` varchar(4090) COLLATE utf8mb4_general_ci NULL DEFAULT NULL,');

            if (!$table->indexExists('account')) {
                $table->alter()->addIndex('KEY `account` (`account`)');
            }

            if (!$table->indexExists('platform')) {
                $table->alter()->addIndex('KEY `platform` (`platform`)');
            }

            if (!$table->indexExists('method')) {
                $table->alter()->addIndex('KEY `method` (`method`)');
            }

            if (!$table->indexExists('user_agent')) {
                $table->alter()->addIndex('KEY `user_agent` (`user_agent` (32))');
            }

            if (!$table->indexExists('latitude')) {
                $table->alter()->addIndex('KEY `latitude` (`latitude`)');
            }

            if (!$table->indexExists('longitude')) {
                $table->alter()->addIndex('KEY `longitude` (`longitude`)');
            }

            if (!$table->indexExists('timezones_id')) {
                $table->alter()->addIndex('KEY `timezones_id` (`timezones_id`)');
            }

            if (!$table->indexExists('countries_id')) {
                $table->alter()->addIndex('KEY `countries_id` (`countries_id`)');
            }

            if (!$table->indexExists('states_id')) {
                $table->alter()->addIndex('KEY `states_id` (`states_id`)');
            }

            if (!$table->indexExists('cities_id')) {
                $table->alter()->addIndex('KEY `cities_id` (`cities_id`)');
            }

            if (!$table->foreignKeyExists('fk_accounts_authentications_timezones_id')) {
                $table->alter()->addForeignKey('CONSTRAINT `fk_accounts_authentications_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT');
            }

            if (!$table->foreignKeyExists('fk_accounts_authentications_countries_id')) {
                $table->alter()->addForeignKey('CONSTRAINT `fk_accounts_authentications_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT');
            }

            if (!$table->foreignKeyExists('fk_accounts_authentications_states_id')) {
                $table->alter()->addForeignKey('CONSTRAINT `fk_accounts_authentications_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT');
            }

            if (!$table->foreignKeyExists('fk_accounts_authentications_cities_id')) {
                $table->alter()->addForeignKey('CONSTRAINT `fk_accounts_authentications_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT');
            }

            if ($table->indexExists('ip_address_human')) {
                $table->alter()->dropIndex('`ip_address`')
                               ->dropIndex('`ip_address_human`');
            }

            if ($table->columnExists('ip_address_human')) {
                $table->alter()->changeColumn('`ip_address`'      , '`ip_address_binary` binary(16) NULL DEFAULT NULL,')
                               ->changeColumn('`ip_address_human`', '`ip_address`        varchar(48) NULL DEFAULT NULL,');
            }

            if (!$table->indexExists('ip_address')) {
                $table->alter()->addIndex('KEY `ip_address` (`ip_address`)');
            }

            if (!$table->indexExists('ip_address_binary')) {
                $table->alter()->addIndex('KEY `ip_address_binary` (`ip_address_binary`)');
            }

            sql()->getSchemaObject()->getTableObject('accounts_signins')->drop();

        })->addUpdate('0.5.1', function () {
            sql()->getSchemaObject()->getTableObject('accounts_authentications')->alter()->modifyColumn('`status`', 'varchar(32) CHARACTER SET latin1 NULL DEFAULT NULL,');

        })->addUpdate('0.5.2', function () {
            // Fix nullable datetime column issues
            sql()->query('UPDATE `accounts_users` SET    `last_sign_in` = NULL WHERE `last_sign_in` = "0000-00-00 00:00:00"');
            sql()->query('UPDATE `accounts_users` SET    `locked_until` = NULL WHERE `locked_until` = "0000-00-00 00:00:00"');
            sql()->query('UPDATE `accounts_users` SET    `verified_on`  = NULL WHERE `verified_on`  = "0000-00-00 00:00:00"');
        });
    }
}
