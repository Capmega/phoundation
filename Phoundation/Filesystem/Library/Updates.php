<?php

/**
 * Updates class
 *
 * This is the Init class for the Accounts library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Library;

use Phoundation\Filesystem\Mimetypes\PhoMimetypesInit;
use Phoundation\Utils\Seo;

class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.8.2';
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
            sql()->getSchemaObject()->getTableObject('filesystem_mounts')->getDefineObject()
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
            sql()->getSchemaObject()->getTableObject('filesystem_requirements')->drop()->getDefineObject()
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
            sql()->getSchemaObject()->getTableObject('filesystem_mounts')->getAlterObject()
                ->addColumn('`timeout` int NULL DEFAULT NULL', 'AFTER `auto_unmount`');

        })->addUpdate('0.0.30', function () {
            // Create the filesystem_requirements table.
            sql()->getSchemaObject()->getTableObject('filesystem_mimetypes')->drop()->getDefineObject()
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

            PhoMimetypesInit::init();

        })->addUpdate('0.4.0', function () {
            // Create the filesystem_user_files table.
            sql()->getSchemaObject()->getTableObject('filesystem_user_files')->drop()->getDefineObject()
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

        })->addUpdate('0.8.0', function () {
            // Add support for modified_on and modified_by
            $this->ensureModifiedColumns([
                'filesystem_mounts',
                'filesystem_requirements',
                'filesystem_mimetypes',
                'filesystem_user_files',
            ]);

        })->addUpdate('0.8.1', function () {
            // Add missing mimetype
            if (empty(sql()->getColumn('SELECT `id` FROM `filesystem_mimetypes` WHERE `extension` = ".nfo" AND `name` = "text/x-nfo" AND `mimetype` = "text/xml"'))) {
                sql()->insert('filesystem_mimetypes', [
                    'name'           => 'text/x-nfo',
                    'seo_name'       => Seo::unique('text-x-nfo', 'filesystem_mimetypes'),
                    'extension'      => '.nfo',
                    'mimetype'       => 'text/xml',
                    'primary_part'   => 'text',
                    'secondary_part' => 'x-nfo',
                    'priority'       => 0,
                ]);
            }

            if (empty(sql()->getColumn('SELECT `id` FROM `filesystem_mimetypes` WHERE `extension` = ".nfo" AND `name` = "text/x-nfo" AND `mimetype` = "text/x-nfo"'))) {
                // Add missing mimetype
                sql()->insert('filesystem_mimetypes', [
                    'name'           => 'text/x-nfo',
                    'seo_name'       => Seo::unique('text-x-nfo', 'filesystem_mimetypes'),
                    'extension'      => '.nfo',
                    'mimetype'       => 'text/x-nfo',
                    'primary_part'   => 'text',
                    'secondary_part' => 'x-nfo',
                    'priority'       => 1,
                ]);
            }

        })->addUpdate('0.8.2', function () {
            // Fix indices to include `status` for filesystem_requirements, filesystem_mimetypes, filesystem_mounts
            $tables = [
                'filesystem_requirements',
                'filesystem_mounts',
                'filesystem_mimetypes'
            ];

            foreach ($tables as $table) {
                $_table  = sql()->getSchemaObject()->getTableObject($table);
                $indices = [
                    'name',
                ];

                foreach ($indices as $index) {
                    $_table->getAlterObject()->dropIndex($index, true);
                }

                $_table->getAlterObject()->dropIndex('name_status', true)
                                ->addIndex('UNIQUE KEY `name_status` (`name`, `status`)');
            }

            // Fix indices to include `status` for filesystem_user_files
            $tables = [
                'filesystem_user_files'
            ];

            foreach ($tables as $table) {
                $_table  = sql()->getSchemaObject()->getTableObject($table);
                $indices = [
                    'file',
                ];

                foreach ($indices as $index) {
                    $_table->getAlterObject()->dropIndex($index, true);
                }

                $_table->getAlterObject()->dropIndex('file_status', true)
                                ->addIndex('UNIQUE KEY `file_status` (`file` (128), `status`)');
            }
        });
    }
}
