<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Library;


/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
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
        return '0.0.20';
    }


    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('This library manages all filesystem functionalities. It contains File and Directory objects that represent real world files and objects and each contain a huge array of methods to manipulate them');
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.20', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('filesystem_mounts')->drop();


            // Create the users table.
            sql()->schema()->table('filesystem_mounts')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `source` varchar(255) DEFAULT NULL,
                    `target` varchar(255) DEFAULT NULL,
                    `options` varchar(255) DEFAULT NULL,
                    `auto_mount` tinyint(1) NOT NULL DEFAULT 0,
                    `comments` mediumtext DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `source` (`target`),
                    KEY `source` (`target`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_filesystem_mounts_created_by` FOREIGN KEY (`created_by`) REFERENCES `filesystem_mounts` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_filesystem_mounts_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();
        });
    }
}
