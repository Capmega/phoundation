<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;


/**
 * Updates class
 *
 * This is the Init class for the Processes library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
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
        return tr('The Processes library facilitates tools to execute other tasks and processes on this server or other servers safely');
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.11', function () {
            sql()->schema()->table('processes_tasks')->drop();

            // Add table for version control itself
            sql()->schema()->table('processes_tasks')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) NOT NULL,
                    `code` varchar(16) NOT NULL,
                    `execute_after` datetime NULL,
                    `executed_on` datetime NULL,
                    `finished_on` datetime NULL,
                    `send_to_id` bigint NULL,
                    `parents_id` bigint NULL,
                    `time_limit` int NULL,
                    `time_spent` int NULL,
                    `parallel` tinyint NOT NULL,
                    `pid` bigint NULL,
                    `verbose` tinyint NOT NULL,
                    `command` varchar(128) NOT NULL,
                    `arguments` blob NOT NULL,
                    `executed_command` blob NOT NULL,
                    `results` longblob NULL,
                    `description` text NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `send_to_id` (`send_to_id`),
                    KEY `parents_id` (`parents_id`),
                    KEY `execute_after` (`execute_after`),
                    KEY `executed_on` (`executed_on`),
                    KEY `finished_on` (`finished_on`),
                    KEY `command` (`command`),
                    UNIQUE `code` (`code`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_processes_tasks_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_processes_tasks_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_processes_tasks_send_to_id` FOREIGN KEY (`send_to_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_processes_tasks_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `processes_tasks` (`id`) ON DELETE RESTRICT,
                ')->create();
        });
    }
}
