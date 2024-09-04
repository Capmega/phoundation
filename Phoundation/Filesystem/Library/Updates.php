<?php

/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Library;

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Mimetypes\FsMimetype;
use Phoundation\Filesystem\Mimetypes\FsMimetypesInit;
use Phoundation\Utils\Arrays;


class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.4.0';
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
            sql()->getSchemaObject()->getTableObject('filesystem_mounts')->drop();

            // Create the filesystem_mounts table.
            sql()->getSchemaObject()->getTableObject('filesystem_mounts')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `source_path` varchar(255) DEFAULT NULL,
                    `target_path` varchar(255) DEFAULT NULL,
                    `filesystem` varchar(32) DEFAULT NULL,
                    `options` varchar(508) DEFAULT NULL,
                    `auto_mount` tinyint(1) NOT NULL DEFAULT 0,
                    `auto_unmount` tinyint(1) NOT NULL DEFAULT 0,
                    `description` mediumtext DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `filesystem` (`filesystem`),
                    KEY `auto_mount` (`auto_mount`),
                    KEY `auto_unmount` (`auto_unmount`),
                    KEY `source_path` (`source_path`),
                    KEY `target_path` (`target_path`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_filesystem_mounts_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_filesystem_mounts_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

        })->addUpdate('0.0.21', function () {
            // Create the filesystem_requirements table.
            sql()->getSchemaObject()->getTableObject('filesystem_requirements')->drop()->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `path` varchar(255) DEFAULT NULL,
                    `filesystem` varchar(16) NOT NULL,
                    `file_type` varchar(16) NOT NULL,
                    `description` mediumtext DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `filesystem` (`filesystem`),
                    KEY `file_type` (`file_type`),
                    KEY `path` (`path`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_filesystem_requirements_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_filesystem_requirements_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

        })->addUpdate('0.0.24', function () {
            sql()->getSchemaObject()->getTableObject('filesystem_mounts')->alter()
                ->addColumn('`timeout` int NULL DEFAULT NULL', 'AFTER `auto_unmount`');

        })->addUpdate('0.0.30', function () {
            // Create the filesystem_requirements table.
            sql()->getSchemaObject()->getTableObject('filesystem_mimetypes')->drop()->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `extension` varchar(16) DEFAULT NULL,
                    `primary_part` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                    `secondary_part` varchar(96) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                    `mimetype` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                    `priority` int NOT NULL DEFAULT 0,
                    `description` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `extension` (`extension`),
                    KEY `mimetype` (`mimetype`),
                    KEY `primary_part` (`primary_part`),
                    KEY `secondary_part` (`secondary_part`),                    
                    KEY `priority` (`priority`),                    
                    UNIQUE KEY `extension_mimetype` (`extension`, `mimetype`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_filesystem_mimetypes_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_filesystem_mimetypes_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

            FsMimetypesInit::init();

        })->addUpdate('0.4.0', function () {
            // Create the filesystem_user_files table.
            sql()->getSchemaObject()->getTableObject('filesystem_user_files')->drop()->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `uploads_id` bigint NULL DEFAULT NULL,
                    `users_id` bigint NULL DEFAULT NULL,
                    `shared_from_id` bigint NULL DEFAULT NULL,
                    `file` varchar(2048) DEFAULT NULL,
                    `seo_file` varchar(2048) DEFAULT NULL,
                    `extension` varchar(16) DEFAULT NULL,
                    `primary_part` varchar(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                    `secondary_part` varchar(96) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                    `mimetype` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                    `hash` varchar(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                    `size` bigint NOT NULL DEFAULT 0,
                    `sections` int NOT NULL DEFAULT 0,
                    `description` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `file` (`file` (128)),
                    UNIQUE KEY `seo_file` (`seo_file` (128)),
                    KEY `size` (`size`),
                    KEY `sections` (`sections`),
                    KEY `extension` (`extension`),
                    KEY `mimetype` (`mimetype`),
                    KEY `primary_part` (`primary_part`),
                    KEY `secondary_part` (`secondary_part`),                    
                    UNIQUE KEY `extension_mimetype` (`extension`, `mimetype`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_filesystem_user_files_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_filesystem_user_files_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_filesystem_user_files_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_filesystem_user_files_uploads_id` FOREIGN KEY (`uploads_id`) REFERENCES `web_uploads` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_filesystem_user_files_shared_from_id` FOREIGN KEY (`shared_from_id`) REFERENCES `filesystem_user_files` (`id`) ON DELETE CASCADE,
                ')->create();
        });
    }
}
