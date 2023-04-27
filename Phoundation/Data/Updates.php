<?php

declare(strict_types=1);

namespace Phoundation\Data;

/**
 * Updates class
 *
 * This is the Init class for the Data library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
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
        return '0.0.5';
    }


    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('This library manages all general data management functionalities');
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.3', function () {
            // Create the categories table.
            sql()->schema()->table('categories')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `name` varchar(64) DEFAULT NULL,
                    `seo_name` varchar(64) DEFAULT NULL,
                    `description` text DEFAULT NULL
                ')->setIndices(' 
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `parent_name` (`parents_id`,`name`),
                    KEY `parents_id` (`parents_id`),
                ')->setForeignKeys(' 
                    CONSTRAINT `fk_categories_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_categories_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
                ')->create();
        })->addUpdate('0.0.5', function () {
            // Modify the categories table.
            sql()->schema()->table('categories')->alter()
                ->addColumn('
                    `created_by` bigint DEFAULT NULL', 'AFTER `created_on`
                ')
                ->addIndices('
                    KEY `created_by` (`created_by`)
                ')
                ->addForeignKeys('
                    CONSTRAINT `fk_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ');
        });
    }
}
