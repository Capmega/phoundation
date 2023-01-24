<?php

namespace Phoundation\Developer;



/**
 * Updates class
 *
 * This is the Init class for the Developer library
 *
 * @see \Phoundation\System\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
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
        return '0.0.11';
    }



    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('This library manages developer functionalities. It tracks incidents and slow www pages as well.');
    }



    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.10', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('developer_slow')->drop();
            sql()->schema()->table('developer_incidents')->drop();

            // Create the users table.
            sql()->schema()->table('developer_incidents')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `data` mediumtext DEFAULT NULL
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_developer_incidents_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_developer_incidents_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                ')
                ->create();

            // Create the developer_slow table.
            sql()->schema()->table('developer_slow')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `incidents_id` bigint NOT NULL,
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `incidents_id` (`incidents_id`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_developers_slow_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_developers_slow_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_developers_slow_incidents_id` FOREIGN KEY (`incidents_id`) REFERENCES `developer_incidents` (`id`) ON DELETE RESTRICT
                ')
                ->create();
        });
    }
}
