<?php

namespace Phoundation\Notifications;



/**
 * Updates class
 *
 * This is the Init class for the Notification library
 *
 * @see \Phoundation\System\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notification
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
        return '0.0.6';
    }



    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('The notifications library manages all system notifications to users, administrators, developers, etc');
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
            sql()->schema()->table('notifications')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_by` int DEFAULT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `meta_id` int NOT NULL,
                    `status` varchar(16) DEFAULT NULL,
                    `code` varchar(16) DEFAULT NULL,
                    `type` varchar(16) DEFAULT NULL,
                    `priority` int NOT NULL,
                    `title` varchar(255) NOT NULL,
                    `message` text NOT NULL,
                    `file` varchar(255) NOT NULL,
                    `line` int(11) NOT NULL,
                    `trace` text NOT NULL,
                    `details` text DEFAULT NULL,')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    KEY `code` (`code`),
                    KEY `type` (`type`),
                    KEY `priority` (`priority`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_notifications_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),')
                ->create();

            sql()->schema()->table('notifications_groups')->define()
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_by` int DEFAULT NULL,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `notifications_id` int(11) NOT NULL,
                    `groups_id` int(11) DEFAULT NULL,')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `notifications_id` (`notifications_id`),
                    KEY `groups_id` (`groups_id`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_notifications_groups_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`),
                    CONSTRAINT `fk_notifications_groups_notifications_id` FOREIGN KEY (`notifications_id`) REFERENCES `notifications` (`id`),
                    CONSTRAINT `fk_notifications_groups_groups_id` FOREIGN KEY (`groups_id`) REFERENCES `accounts_groups` (`id`),')
                ->create();
        });
    }
}
