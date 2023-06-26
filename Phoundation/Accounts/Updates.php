<?php

declare(strict_types=1);

namespace Phoundation\Accounts;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\GuestUser;
use Phoundation\Core\Session;


/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        return '0.0.12';
    }


    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('This library manages all user functionalities');
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
            sql()->schema()->table('accounts_roles_rights')->drop();
            sql()->schema()->table('accounts_users_roles')->drop();
            sql()->schema()->table('accounts_users_rights')->drop();
            sql()->schema()->table('accounts_groups')->drop();
            sql()->schema()->table('accounts_roles')->drop();
            sql()->schema()->table('accounts_rights')->drop();
            sql()->schema()->table('accounts_users')->drop();

            // Create the users table.
            sql()->schema()->table('accounts_users')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `last_sign_in` datetime DEFAULT NULL,
                    `authentication_failures` int NOT NULL,
                    `locked_until` datetime DEFAULT NULL,
                    `sign_in_count` int NOT NULL,
                    `username` varchar(64) DEFAULT NULL,
                    `password` varchar(255) DEFAULT NULL,
                    `fingerprint` datetime DEFAULT NULL,
                    `domain` varchar(128) DEFAULT NULL,
                    `title` varchar(24) DEFAULT NULL,
                    `first_names` varchar(64) DEFAULT NULL,
                    `last_names` varchar(64) DEFAULT NULL,
                    `nickname` varchar(64) DEFAULT NULL,
                    `picture` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `code` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `type` varchar(16) DEFAULT NULL,
                    `keywords` varchar(255) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `address` varchar(255) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `verified_on` datetime DEFAULT NULL,
                    `verification_code` varchar(128) DEFAULT NULL,
                    `priority` int DEFAULT NULL,
                    `is_leader` int DEFAULT NULL,
                    `leaders_id` bigint DEFAULT NULL,
                    `latitude` decimal(18,15) DEFAULT NULL,
                    `longitude` decimal(18,15) DEFAULT NULL,
                    `accuracy` int DEFAULT NULL,
                    `offset_latitude` decimal(18,15) DEFAULT NULL,
                    `offset_longitude` decimal(18,15) DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `redirect` varchar(2048) DEFAULT NULL,
                    `languages_id` bigint DEFAULT NULL,
                    `gender` varchar(16) DEFAULT NULL,
                    `birthdate` date DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `comments` mediumtext DEFAULT NULL
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
            sql()->schema()->table('accounts_rights')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(64) NOT NULL,
                    `seo_name` varchar(64) NOT NULL,
                    `description` varchar(2047) DEFAULT NULL
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
            sql()->schema()->table('accounts_roles')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` text DEFAULT NULL,
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
            sql()->schema()->table('accounts_groups')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` text DEFAULT NULL,
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
            sql()->schema()->table('accounts_users_rights')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `users_id` bigint NOT NULL,
                    `rights_id` bigint NOT NULL,
                    `name` varchar(64) NOT NULL,
                    `seo_name` varchar(64) DEFAULT NULL
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
            sql()->schema()->table('accounts_users_roles')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
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
            sql()->schema()->table('accounts_roles_rights')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
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
            sql()->schema()->table('accounts_compromised_passwords')->drop();
            sql()->schema()->table('accounts_old_passwords')->drop();
            sql()->schema()->table('accounts_password_resets')->drop();
            sql()->schema()->table('accounts_authentications')->drop();

            // Create additional user tables.
            sql()->schema()->table('accounts_authentications')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `captcha_required` tinyint(1) NOT NULL,
                    `failed_reason` varchar(127) DEFAULT NULL,
                    `users_id` bigint DEFAULT NULL,
                    `username` varchar(64) NOT NULL,
                    `ip` varchar(46) DEFAULT NULL,
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

            sql()->schema()->table('accounts_password_resets')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
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

            sql()->schema()->table('accounts_old_passwords')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `password` varchar(255) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `password` (`password`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_old_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

            sql()->schema()->table('accounts_compromised_passwords')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `password` varchar(255) NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `password` (`password`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_accounts_banned_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();

        })->addUpdate('0.0.6', function () {
            // Ensure Guest user is available
            GuestUser::new()
                ->save();

            // Create default rights and roles
            $god = Right::new()
                ->setName('God')
                ->setDescription('This right will give the user access to everything, everywhere')
                ->save();

            $admin = Right::new()
                ->setName('Admin')
                ->setDescription('This right will give the user access to the administrative area of the site, but no specific pages yet')
                ->save();

            $developer = Right::new()
                ->setName('Developer')
                ->setDescription('This right will give the user access to the developer area of the site')
                ->save();

            $accounts = Right::new()
                ->setName('Accounts')
                ->setDescription('This right will give the user access to the administrative user accounts management section of the site')
                ->save();

            $security = Right::new()
                ->setName('Security')
                ->setDescription('This right will give the user access to the administrative security pages of the site')
                ->save();

            $phoundation = Right::new()
                ->setName('Phoundation')
                ->setDescription('This right will give the user access to the administrative phoundation system management section of the site')
                ->save();

            $audit = Right::new()
                ->setName('Audit')
                ->setDescription('This right will give the user access to the audit information system of the site')
                ->save();

            // Define basic roles
            Role::new()
                ->setName('God')
                ->setDescription('This role will give the user the "God" right which will give it access to everything, everywhere')
                ->save()
                ->getRights()
                    ->addRight($god);

            Role::new()
                ->setName('Administrator')
                ->setDescription('This role gives access to all the administrative pages except user account management')
                ->save()
                ->getRights()
                    ->addRight($admin)
                    ->addRight($audit)
                    ->addRight($security)
                    ->addRight($phoundation);

            Role::new()
                ->setName('Accounts administrator')
                ->setDescription('This role gives access to only the administrative user account pages')
                ->save()
                ->getRights()
                    ->addRight($admin)
                    ->addRight($accounts);

            Role::new()
                ->setName('Developer')
                ->setDescription('This role will give the user access to the developer pages of the site')
                ->save()
                ->getRights()
                    ->addRight($developer);

            Role::new()
                ->setName('Moderator')
                ->setDescription('This role will give the user basic access to the administrative pages of the site')
                ->save()
                ->getRights()
                    ->addRight($admin);

            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('accounts_signins')->drop();

            sql()->schema()->table('accounts_signins')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `method` varchar(32) NOT NULL,
                    `ip_address_human` varchar(48) NOT NULL,
                    `ip_address` binary(16) NOT NULL,
                    `net_len` int(11) NOT NULL,
                    `user_agent` varchar(2040) NULL,
                    `latitude` decimal(10,7) NULL,
                    `longitude` decimal(10,7) NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
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
            sql()->schema()->table('accounts_authentication_failures')->drop();

            sql()->schema()->table('accounts_authentication_failures')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `method` varchar(32) NOT NULL,
                    `ip_address_human` varchar(48) NOT NULL,
                    `ip_address` binary(16) NOT NULL,
                    `net_len` int(11) NOT NULL,
                    `user_agent` varchar(2040) NULL,
                    `latitude` decimal(10,7) NOT NULL,
                    `longitude` decimal(10,7) NOT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
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
            sql()->schema()->table('accounts_users')->alter()->addColumn('`update_password` datetime DEFAULT NULL', 'AFTER `password`');
        });
    }
}
