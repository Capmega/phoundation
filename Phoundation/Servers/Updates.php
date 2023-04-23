<?php

namespace Phoundation\Servers;

/**
 * Updates class
 *
 * This is the Init class for the Servers library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
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
        return tr('This library manages servers, and how to access them over SSH');
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.11', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('ssh_accounts')->drop();
            sql()->schema()->table('servers')->drop();

            // Create the sshaccounts table.
            sql()->schema()->table('ssh_accounts')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `username` varchar(64) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `ssh_key` text DEFAULT NULL,
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_ssh_accounts_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_ssh_accounts_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

            // Create the servers table.
            sql()->schema()->table('servers')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `code` varchar(16) NOT NULL,
                    `hostname` varchar(128) NOT NULL,
                    `seo_hostname` varchar(128) NOT NULL,
                    `port` int DEFAULT NULL,
                    `cost` double(15,5) DEFAULT NULL,
                    `bill_due_date` datetime DEFAULT NULL,
                    `interval` enum("hourly","daily","weekly","monthly","bimonthly","quarterly","semiannual","annually","none") DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `providers_id` bigint DEFAULT NULL,
                    `customers_id` bigint DEFAULT NULL,
                    `ssh_accounts_id` bigint DEFAULT NULL,
                    `countries_id` bigint DEFAULT NULL,
                    `states_id` bigint DEFAULT NULL,
                    `cities_id` bigint DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `os_name` enum("debian","ubuntu","redhat","gentoo","slackware","linux","windows","freebsd","macos","other") DEFAULT NULL,
                    `os_version` varchar(16) DEFAULT NULL,
                    `web_services` tinyint NOT NULL DEFAULT "0",
                    `mail_services` tinyint NOT NULL DEFAULT "0",
                    `database_services` tinyint NOT NULL DEFAULT "0",
                    `allow_sshd_modification` tinyint NOT NULL DEFAULT "0",
                ')->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `code` (`code`),
                    UNIQUE KEY `hostname` (`hostname`),
                    UNIQUE KEY `seo_hostname` (`seo_hostname`),
                    KEY `providers_id` (`providers_id`),
                    KEY `customers_id` (`customers_id`),
                    KEY `categories_id` (`categories_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `states_id` (`states_id`),
                    KEY `cities_id` (`cities_id`),
                    KEY `ssh_accounts_id` (`ssh_accounts_id`),
                    KEY `bill_due_date` (`bill_due_date`),
                    KEY `web_services` (`web_services`),
                    KEY `mail_services` (`mail_services`),
                    KEY `database_services` (`database_services`),
                    KEY `os_name` (`os_name`),
                    KEY `os_version` (`os_version`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_servers_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_servers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_servers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_servers_ssh_accounts_id` FOREIGN KEY (`ssh_accounts_id`) REFERENCES `ssh_accounts` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_servers_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `business_providers` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_servers_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `business_customers` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_servers_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_servers_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_servers_cities_id` FOREIGN KEY (`cities_id`) REFERENCES `geo_cities` (`id`) ON DELETE RESTRICT,
                ')->create();
        });
    }
}
