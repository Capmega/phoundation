<?php

namespace Phoundation\Users;



/**
 * Init class
 *
 * This is the Init class for the Users library
 *
 * @see \Phoundation\Initialize\Init
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Users
 */
class Init extends \Phoundation\Initialize\Init
{
    public function __construct()
    {
        parent::__construct('0.0.5');

        $this->addUpdate('0.0.4', function () {
            // Create the users table.
            sql()->schema()->table('users')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_by` int DEFAULT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `modified_by` int DEFAULT NULL,
                    `modified_on` datetime DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `last_signin` datetime DEFAULT NULL,
                    `auth_fails` int NOT NULL,
                    `locked_until` datetime DEFAULT NULL,
                    `signin_count` int NOT NULL,
                    `username` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `password` varchar(255) NOT NULL,
                    `fingerprint` datetime DEFAULT NULL,
                    `domain` varchar(128) DEFAULT NULL,
                    `title` varchar(24) DEFAULT NULL,
                    `name` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `nickname` varchar(64) DEFAULT NULL,
                    `avatar` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `email` varchar(128) DEFAULT NULL,
                    `code` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `type` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `keywords` varchar(255) DEFAULT NULL,
                    `phones` varchar(64) CHARACTER SET latin1 NOT NULL,
                    `address` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
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
                    `location` varchar(64) DEFAULT NULL,
                    `language` char(2) CHARACTER SET latin1 DEFAULT NULL,
                    `gender` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `birthday` date DEFAULT NULL,
                    `country` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `commentary` mediumtext DEFAULT NULL,
                    `website` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `timezone` varchar(32) DEFAULT NULL')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `verify_code` (`verify_code`),
                    UNIQUE KEY `domain-email` (`domain`, `email`),
                    KEY `email` (`email`),
                    KEY `validated` (`verify_code`),
                    KEY `language` (`language`),
                    KEY `country` (`country`),
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
                    KEY `modified_by` (`modified_by`),
                    KEY `modified_on` (`modified_on`),
                    KEY `nickname` (`nickname`),
                    KEY `priority` (`priority`),
                    KEY `fingerprint` (`fingerprint`),
                    KEY `meta_id` (`meta_id`),
                    KEY `cities_id` (`cities_id`),
                    KEY `states_id` (`states_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `status` (`status`)')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_leaders_id` FOREIGN KEY (`leaders_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT')
                ->create();

            // Create the users_rights table.
            sql()->schema()->table('users_rights')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `modified_by` int DEFAULT NULL,
                    `modified_on` datetime DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) NOT NULL,
                    `description` varchar(2047) NOT NULL')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `modified_by` (`modified_by`),
                    KEY `modified_on` (`modified_on`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`)')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_rights_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_rights_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_rights_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,')
                ->create();

            // Create the users_roles table.
            sql()->schema()->table('users_roles')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `modified_by` int DEFAULT NULL,
                    `modified_on` datetime DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_on` (`modified_on`),
                    KEY `modified_by` (`modified_by`),
                    KEY `status` (`status`),
                    KEY `name` (`name`),
                    KEY `meta_id` (`meta_id`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_roles_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_roles_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_roles_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT')
                ->create();

            // Create the users_groups table.
            sql()->schema()->table('users_groups')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `modified_by` int DEFAULT NULL,
                    `modified_on` datetime DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                    `description` varchar(2047) DEFAULT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `modified_on` (`modified_on`),
                    KEY `modified_by` (`modified_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `name` (`name`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_groups_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_groups_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_groups_modified_by` FOREIGN KEY (`modified_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT')
                ->create();

            // Create the users_rights_links table.
            sql()->schema()->table('users_rights_links')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_by` int DEFAULT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `users_id` int NOT NULL,
                    `rights_id` int NOT NULL,
                    `name` varchar(32) NOT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_rights` (`users_id`,`rights_id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `users_id` (`users_id`),
                    KEY `rights_id` (`rights_id`),
                    KEY `name` (`name`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_rights_links_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_rights_links_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_users_rights_links_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `users_rights` (`id`) ON DELETE CASCADE,')
                ->create();

            // Create the users_roles_links table.
            sql()->schema()->table('users_roles_links')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_by` int DEFAULT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `users_id` int NOT NULL,
                    `roles_id` int NOT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    UNIQUE KEY `users_roles` (`users_id`,`roles_id`),
                    KEY `roles_id` (`roles_id`),
                    KEY `users_id` (`users_id`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_roles_links_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_roles_links_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_users_roles_links_roles_id` FOREIGN KEY (`roles_id`) REFERENCES `users_roles` (`id`) ON DELETE CASCADE')
                ->create();

            // Create the users_roles_rights_links table.
            sql()->schema()->table('users_roles_rights_links')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_by` int DEFAULT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `roles_id` int NOT NULL,
                    `rights_id` int NOT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    UNIQUE KEY `roles_id_2` (`roles_id`,`rights_id`),
                    KEY `roles_id` (`roles_id`),
                    KEY `rights_id` (`rights_id`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_roles_rights_links_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_users_roles_rights_links_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `users_rights` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_users_roles_right_links_roles_id` FOREIGN KEY (`roles_id`) REFERENCES `users_roles` (`id`) ON DELETE CASCADE')
                ->create();
        })->addUpdate('0.0.5', function () {
            // Create additional user tables.
            sql()->schema()->table('users_authentications')
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
                    `method` enum("password", "google", "facebook") CHARACTER SET latin1 NOT NULL DEFAULT "password",')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `users_id` (`users_id`),
                    KEY `ip` (`ip`),
                    KEY `action` (`action`),
                    KEY `method` (`method`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_authentications_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_authentications_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE')
                ->create();

            sql()->schema()->table('users_password_resets')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int DEFAULT NULL,
                    `code` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `date_requested` int DEFAULT "0",
                    `date_used` int DEFAULT "0",
                    `ip` varchar(32) CHARACTER SET latin1 DEFAULT NULL,')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_password_resets_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE')
                ->create();

            sql()->schema()->table('users_old_passwords')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_by` int NOT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `password` varchar(255) NOT NULL,')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `password` (`password`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_old_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE')
                ->create();
        });
    }
}
