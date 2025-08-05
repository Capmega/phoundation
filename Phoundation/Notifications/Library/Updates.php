<?php

/**
 * Updates class
 *
 * This is the Init class for the Notification library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Notification
 */


declare(strict_types=1);

namespace Phoundation\Notifications\Library;


class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.1.1';
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.6', function () {
            // Add initial tables for the Notification library
            sql()
                ->getSchemaObject()
                ->getTableObject('notifications')
                ->drop()
                ->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NOT NULL,
                    `code` varchar(16) DEFAULT NULL,
                    `mode` varchar(16) DEFAULT NULL,
                    `icon` varchar(32) DEFAULT NULL,
                    `priority` int NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `message` text NOT NULL,
                    `url` varchar(2048) NULL,
                    `file` varchar(2048) DEFAULT NULL,
                    `line` int(11) DEFAULT NULL,
                    `trace` text DEFAULT  NULL,
                    `details` text DEFAULT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `code` (`code`),
                    KEY `mode` (`mode`),
                    KEY `priority` (`priority`),
                    KEY `users_id` (`users_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_notifications_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_notifications_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')
                ->create();

        })->addUpdate('0.1.0', function () {
            sql()->getSchemaObject()
                 ->getTableObject('notifications')
                 ->alter()->modifyColumn('message', 'mediumtext NOT NULL')
                          ->modifyColumn('details', 'mediumtext NOT NULL');

        })->addUpdate('0.1.1', function () {
            // Fix notifications table details column
            sql()->getSchemaObject()->getTableObject('notifications')->alter()->modifyColumn('details', 'mediumtext COLLATE utf8mb4_general_ci NULL DEFAULT NULL,');
        });
    }
}
