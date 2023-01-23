<?php

namespace Phoundation\Accounts;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Roles\Role;



/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see \Phoundation\System\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class Updates extends \Phoundation\System\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.5';
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
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `last_sign_in` datetime DEFAULT NULL,
                    `authentication_failures` int NOT NULL,
                    `locked_until` datetime DEFAULT NULL,
                    `sign_in_count` int NOT NULL,
                    `username` varchar(64) DEFAULT NULL,
                    `password` varchar(255) NOT NULL,
                    `fingerprint` datetime DEFAULT NULL,
                    `domain` varchar(128) DEFAULT NULL,
                    `title` varchar(24) DEFAULT NULL,
                    `first_names` varchar(127) DEFAULT NULL,
                    `last_names` varchar(127) DEFAULT NULL,
                    `nickname` varchar(64) DEFAULT NULL,
                    `avatar` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `code` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `type` varchar(16) DEFAULT NULL,
                    `keywords` varchar(255) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `address` varchar(255) DEFAULT NULL,
                    `zipcode` varchar(8) DEFAULT NULL,
                    `verification_code` varchar(128) DEFAULT NULL,
                    `verified_on` datetime DEFAULT NULL,
                    `priority` int DEFAULT NULL,
                    `is_leader` int DEFAULT NULL,
                    `leaders_id` int DEFAULT NULL,
                    `latitude` decimal(18,15) DEFAULT NULL,
                    `longitude` decimal(18,15) DEFAULT NULL,
                    `accuracy` int DEFAULT NULL,
                    `offset_latitude` decimal(18,15) DEFAULT NULL,
                    `offset_longitude` decimal(18,15) DEFAULT NULL,
                    `cities_id` int DEFAULT NULL,
                    `states_id` int DEFAULT NULL,
                    `countries_id` int DEFAULT NULL,
                    `redirect` varchar(255) DEFAULT NULL,
                    `languages_id` int DEFAULT NULL,
                    `gender` varchar(16) DEFAULT NULL,
                    `birthday` date DEFAULT NULL,
                    `url` varchar(2048) DEFAULT NULL,
                    `timezone` varchar(32) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `comments` mediumtext DEFAULT NULL
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `verification_code` (`verification_code`),
                    UNIQUE KEY `domain-email` (`domain`, `email`),
                    KEY `email` (`email`),
                    KEY `verified_on` (`verified_on`),
                    KEY `languages_id` (`languages_id`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `birthday` (`birthday`),
                    KEY `code` (`code`),
                    KEY `type` (`type`),
                    KEY `phones` (`phones`),
                    KEY `is_leader` (`is_leader`),
                    KEY `leaders_id` (`leaders_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `nickname` (`nickname`),
                    KEY `priority` (`priority`),
                    KEY `fingerprint` (`fingerprint`),
                    KEY `meta_id` (`meta_id`),
                    KEY `cities_id` (`cities_id`),
                    KEY `states_id` (`states_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `status` (`status`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_leaders_id` FOREIGN KEY (`leaders_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT
                ')
                ->create();

            // Create the users_rights table.
            sql()->schema()->table('accounts_rights')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) NOT NULL,
                    `seo_name` varchar(32) NOT NULL,
                    `description` varchar(2047) NOT NULL
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_rights_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_rights_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')
                ->create();

            // Create the users_roles table.
            sql()->schema()->table('accounts_roles')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) DEFAULT NULL,
                    `seo_name` varchar(32) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL,
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_roles_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_roles_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')
                ->create();

            // Create the users_groups table.
            sql()->schema()->table('accounts_groups')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) DEFAULT NULL,
                    `seo_name` varchar(32) DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL,
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `name` (`name`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_groups_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_groups_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')
                ->create();

            // Create the users_rights_links table.
            sql()->schema()->table('accounts_users_rights')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `users_id` int NOT NULL,
                    `rights_id` int NOT NULL,
                    `name` varchar(32) NOT NULL,
                    `seo_name` varchar(32) DEFAULT NULL
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_rights` (`users_id`,`rights_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `users_id` (`users_id`),
                    KEY `rights_id` (`rights_id`),
                    KEY `name` (`name`),
                    KEY `seo_name` (`seo_name`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_users_rights_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_rights_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_users_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE CASCADE,
                ')
                ->create();

            // Create the users_roles_links table.
            sql()->schema()->table('accounts_users_roles')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `users_id` int NOT NULL,
                    `roles_id` int NOT NULL,
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_roles` (`users_id`,`roles_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `roles_id` (`roles_id`),
                    KEY `users_id` (`users_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_users_roles_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_users_roles_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_users_roles_roles_id` FOREIGN KEY (`roles_id`) REFERENCES `accounts_roles` (`id`) ON DELETE CASCADE
                ')
                ->create();

            // Create the users_roles_rights_links table.
            sql()->schema()->table('accounts_roles_rights')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `roles_id` int NOT NULL,
                    `rights_id` int NOT NULL,
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `roles_rights` (`roles_id`,`rights_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `roles_id` (`roles_id`),
                    KEY `rights_id` (`rights_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_roles_rights_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_roles_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_accounts_roles_rights_roles_id` FOREIGN KEY (`roles_id`) REFERENCES `accounts_roles` (`id`) ON DELETE CASCADE
                ')
                ->create();
        })->addUpdate('0.0.5', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('accounts_compromised_passwords')->drop();
            sql()->schema()->table('accounts_old_passwords')->drop();
            sql()->schema()->table('accounts_password_resets')->drop();
            sql()->schema()->table('accounts_authentications')->drop();

            // Create additional user tables.
            sql()->schema()->table('accounts_authentications')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `captcha_required` tinyint(1) NOT NULL,
                    `failed_reason` varchar(127) DEFAULT NULL,
                    `users_id` int DEFAULT NULL,
                    `username` varchar(64) NOT NULL,
                    `ip` varchar(46) DEFAULT NULL,
                    `action` enum("authentication", "signin") CHARACTER SET latin1 NOT NULL DEFAULT "authentication",
                    `method` enum("password", "google", "facebook") CHARACTER SET latin1 NOT NULL DEFAULT "password",
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `users_id` (`users_id`),
                    KEY `ip` (`ip`),
                    KEY `action` (`action`),
                    KEY `method` (`method`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_authentications_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_accounts_authentications_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE
                ')
                ->create();

            sql()->schema()->table('accounts_password_resets')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `code` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `date_requested` int DEFAULT "0",
                    `date_used` int DEFAULT "0",
                    `ip` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_password_resets_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE
                ')
                ->create();

            sql()->schema()->table('accounts_old_passwords')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int NOT NULL,
                    `password` varchar(255) NOT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `password` (`password`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_old_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE
                ')
                ->create();

            sql()->schema()->table('accounts_compromised_passwords')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int NOT NULL,
                    `password` varchar(255) NOT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `password` (`password`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_accounts_banned_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE
                ')
                ->create();

            // Create default rights and roles
            $god = Right::new()
                ->setName('God')
                ->setDescription('This right will give the user access to everything, everywhere')
                ->save();

            $admin = Right::new()
                ->setName('Admin')
                ->setDescription('This right will give the user access to the administrative area of the site, but no specific pages yet')
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

            // Define basic roles
            Role::new()
                ->setName('God')
                ->setDescription('This role will give the user the "God" right which will give it access to everything, everywhere')
                ->save()
                ->rights()->add($god);

            Role::new()
                ->setName('Administrator')
                ->setDescription('This role gives access to all the administrative pages except user account management')
                ->save()
                ->rights()
                ->add($admin)
                ->add($security)
                ->add($phoundation);

            Role::new()
                ->setName('Accounts administrator')
                ->setDescription('This role gives access to only the administrative user account pages')
                ->save()
                ->rights()
                ->add($admin)
                ->add($accounts);

            Role::new()
                ->setName('Moderator')
                ->setDescription('This role will give the user basic access to the administrative pages of the site')
                ->save()
                ->rights()
                ->add($admin);
        });
    }
}
