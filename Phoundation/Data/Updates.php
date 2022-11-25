<?php

namespace Phoundation\Data;



/**
 * Updates class
 *
 * This is the Init class for the Data library
 *
 * @see \Phoundation\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Updates extends \Phoundation\Libraries\Updates
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
        $this->addUpdate('0.0.2', function () {
            // Create the categories table.
            sql()->schema()->table('categories')->define()
                ->setColumns('`id` int NOT NULL AUTO_INCREMENT,
                                      `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `meta_id` int NOT NULL,
                                      `status` varchar(16) DEFAULT NULL,
                                      `parents_id` int DEFAULT NULL,
                                      `name` varchar(64) DEFAULT NULL,
                                      `seo_name` varchar(64) DEFAULT NULL,
                                      `description` varchar(2047) DEFAULT NULL')
                ->setIndices(' PRIMARY KEY (`id`),
                                      UNIQUE KEY `seo_name` (`seo_name`),
                                      UNIQUE KEY `parent_name` (`parents_id`,`name`),
                                      KEY `meta_id` (`meta_id`),
                                      KEY `parents_id` (`parents_id`),
                                      KEY `created_on` (`created_on`),
                                      KEY `status` (`status`)')
                ->setForeignKeys(' CONSTRAINT `fk_categories_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`),
                                              CONSTRAINT `fk_categories_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `categories` (`id`)')
                ->create();
        })->addUpdate('0.0.5', function () {
            // Modify the categories table.
            sql()->schema()->table('categories')->alter()
                ->addColumn('`created_by` int DEFAULT NULL', 'AFTER `created_on`')
                ->addIndices('KEY `created_by` (`created_by`)')
                ->addForeignKeys('CONSTRAINT `fk_categories_createdby` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)');
        });
    }
}
