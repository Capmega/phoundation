<?php

declare(strict_types=1);

namespace Phoundation\Templates;


/**
 * Updates class
 *
 * This is the Init class for the Templates library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Templates
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
        return '0.0.15';
    }


    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('The Templates library contains functions to manage and use templates for emails, webpages, etc');
    }


    /**
     * The list of version updates available for this library
     *f
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.15', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('templates_pages')->drop();

            // Add table for template pages
            sql()->schema()->table('templates_pages')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `content` mediumtext DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `code` (`code`),
                    KEY `categories_id` (`categories_id`),
                    KEY `parents_id` (`parents_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_templates_pages_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_templates_pages_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_templates_pages_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_templates_pages_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                ')->create();
        });
    }
}
