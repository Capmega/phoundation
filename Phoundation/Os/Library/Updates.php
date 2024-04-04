<?php

declare(strict_types=1);

namespace Phoundation\Os\Library;


/**
 * Updates class
 *
 * This is the Init class for the Os library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
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
        return '0.1.3';
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
        $this->addUpdate('0.1.0', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('os_tasks')->drop();
            sql()->schema()->table('processes_tasks')->drop();

            // Create the users_roles table.
            sql()->schema()->table('os_tasks')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `code` varchar(36) DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `parents_id` int DEFAULT NULL,
                    `execute_after` datetime DEFAULT NULL,
                    `start` datetime DEFAULT NULL,
                    `stop` datetime DEFAULT NULL,
                    `spent` float DEFAULT NULL,
                    `servers_id` bigint DEFAULT NULL,
                    `send_to_id` bigint DEFAULT NULL,
                    `roles_id` bigint DEFAULT NULL,
                    `execution_directory` varchar(510) DEFAULT NULL,
                    `command` varchar(64) DEFAULT NULL,
                    `executed_command` text DEFAULT NULL,
                    `arguments` text DEFAULT NULL,
                    `variables` text DEFAULT NULL,
                    `environment_variables` text DEFAULT NULL,
                    `accepted_exit_codes` varchar(64) DEFAULT NULL,
                    `nocache` int DEFAULT NULL,
                    `ionice` int DEFAULT NULL,
                    `ionice_level` int DEFAULT NULL,
                    `nice` int DEFAULT NULL,
                    `timeout` int DEFAULT NULL,
                    `wait` int DEFAULT NULL,
                    `clear_logs` tinyint NOT NULL,
                    `escape_quotes` tinyint NOT NULL,
                    `log_file` varchar(512) DEFAULT NULL,
                    `pid_file` varchar(512) DEFAULT NULL,
                    `sudo` varchar(32) DEFAULT NULL,
                    `term` varchar(32) DEFAULT NULL,
                    `pipe` varchar(510) DEFAULT NULL,
                    `input_redirect` varchar(64) DEFAULT NULL,
                    `output_redirect` varchar(510) DEFAULT NULL,
                    `restrictions` varchar(510) DEFAULT NULL,
                    `packages` varchar(510) DEFAULT NULL,
                    `pre_exec` varchar(64) DEFAULT NULL,
                    `post_exec` varchar(64) DEFAULT NULL,
                    `comments` text DEFAULT NULL,
                    `key` varchar(32) DEFAULT NULL,
                    `values` text DEFAULT NULL,
                    `minimum_workers` int DEFAULT NULL,
                    `maximum_workers` int DEFAULT NULL,
                    `pid` int DEFAULT NULL,
                    `exit_code` int DEFAULT NULL,
                    `results` mediumtext DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `code` (`code`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `parents_id` (`parents_id`),
                    KEY `servers_id` (`servers_id`),
                    KEY `send_to_id` (`send_to_id`),
                    KEY `start` (`start`),
                    KEY `stop` (`stop`),
                    KEY `spent` (`spent`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_os_tasks_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_os_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_os_tasks_parents_id` FOREIGN KEY (`created_by`) REFERENCES `os_tasks` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_os_tasks_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_os_tasks_send_to_id` FOREIGN KEY (`send_to_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ')->create();
        });
    }
}
