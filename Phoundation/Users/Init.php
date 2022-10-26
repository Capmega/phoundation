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
        parent::__construct('0.0.1');

        $this->addUpdate('0.0.1', function (){
            // Create the users table.
            sql()->schema()->table('users')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `createdby` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `createdon` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `modifiedby` int DEFAULT NULL,
                    `modifiedon` datetime DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `last_signin` datetime DEFAULT NULL,
                    `auth_fails` int NOT NULL,
                    `locked_until` datetime DEFAULT NULL,
                    `signin_count` int NOT NULL,
                    `username` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `password` varchar(140) NOT NULL,
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
                    `verify_code` varchar(128) DEFAULT NULL,
                    `verifiedon` datetime DEFAULT NULL,
                    `role` varchar(32) CHARACTER SET latin1 DEFAULT NULL,
                    `roles_id` int DEFAULT NULL,
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
                    `commentary` varchar(2047) DEFAULT NULL,
                    `description` mediumtext,
                    `website` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `credits` double(7,2) DEFAULT NULL,
                    `timezone` varchar(32) DEFAULT NULL,
                    `webpush` varchar(511) DEFAULT NULL')
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
                    KEY `createdby` (`createdby`),
                    KEY `createdon` (`createdon`),
                    KEY `modifiedby` (`modifiedby`),
                    KEY `modifiedon` (`modifiedon`),
                    KEY `have1099` (`have1099`),
                    KEY `nickname` (`nickname`),
                    KEY `priority` (`priority`),
                    KEY `fingerprint` (`fingerprint`),
                    KEY `meta_id` (`meta_id`),
                    KEY `cities_id` (`cities_id`),
                    KEY `states_id` (`states_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `status` (`status`)')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`),
                    CONSTRAINT `fk_users_leaders_id` FOREIGN KEY (`leaders_id`) REFERENCES `users` (`id`),
                    CONSTRAINT `fk_users_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                    CONSTRAINT `fk_users_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`),
                    CONSTRAINT `fk_users_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`),
                    CONSTRAINT `fk_users_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`)')
                ->create();

            // Create the rights table.
            sql()->schema()->table('rights')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `createdby` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `createdon` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `status` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
                    `name` varchar(32) NOT NULL,
                    `description` varchar(2048) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    KEY `createdby` (`createdby`),
                    KEY `createdon` (`createdon`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`)')
                ->setForeignKeys('
                    CONSTRAINT `fk_rights_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)')
                ->create();

            // Create the roles table.
            sql()->schema()->table('roles')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `createdon` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `createdby` int DEFAULT NULL,
                    `meta_id` int DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
                    `name` varchar(32) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
                    `description` varchar(2047) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `createdon` (`createdon`),
                    KEY `createdby` (`createdby`),
                    KEY `status` (`status`),
                    KEY `name` (`name`),
                    KEY `meta_id` (`meta_id`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_roles_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`),
                    CONSTRAINT `fk_roles_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`)')
                ->create();

            // Create the users_rights table.
            sql()->schema()->table('users_rights')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `users_id` int NOT NULL,
                    `rights_id` int NOT NULL,
                    `name` varchar(32) NOT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_id_2` (`users_id`,`rights_id`),
                    KEY `users_id` (`users_id`),
                    KEY `rights_id` (`rights_id`),
                    KEY `name` (`name`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_users_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_users_rights_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE')
                ->create();

            // Create the roles_rights table.
            sql()->schema()->table('roles_rights')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `roles_id` int NOT NULL,
                    `rights_id` int NOT NULL,')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `roles_id_2` (`roles_id`,`rights_id`),
                    KEY `roles_id` (`roles_id`),
                    KEY `rights_id` (`rights_id`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_roles_rights_rights_id` FOREIGN KEY (`rights_id`) REFERENCES `rights` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_roles_rights_roles_id` FOREIGN KEY (`roles_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE')
                ->create();
        });
    }
}
