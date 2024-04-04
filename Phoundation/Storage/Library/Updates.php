<?php

declare(strict_types=1);

namespace Phoundation\Storage\Library;


/**
 * Updates class
 *
 * This is the Init class for the Storage library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Storage
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
        return tr('The Storage library contains functions to create and manage all sorts of documents');
    }


    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.15', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->schema()->table('storage_page_resources')->drop();
            sql()->schema()->table('storage_resources')->drop();
            sql()->schema()->table('storage_files')->drop();
            sql()->schema()->table('storage_file_limits')->drop();
            sql()->schema()->table('storage_key_values')->drop();
            sql()->schema()->table('storage_ratings')->drop();
            sql()->schema()->table('storage_comments')->drop();
            sql()->schema()->table('storage_keywords')->drop();
            sql()->schema()->table('storage_pages')->drop();
            sql()->schema()->table('storage_chapters')->drop();
            sql()->schema()->table('storage_books')->drop();
            sql()->schema()->table('storage_collections')->drop();

            // Add table for storage collections
            sql()->schema()->table('storage_collections')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `view_rights_id` bigint DEFAULT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `description` text DEFAULT NULL
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
                    KEY `view_rights_id` (`view_rights_id`),
                    KEY `parents_id` (`parents_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_collections_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_collections_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_collections_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_collections_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_collections_view_rights_id` FOREIGN KEY (`view_rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage books
            sql()->schema()->table('storage_books')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `view_rights_id` bigint DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
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
                    KEY `view_rights_id` (`view_rights_id`),
                    key `collections_id` (`collections_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_books_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_books_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_books_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_books_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_books_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_books_view_rights_id` FOREIGN KEY (`view_rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage chapters
            sql()->schema()->table('storage_chapters')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `view_rights_id` bigint DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `name` varchar(128) DEFAULT NULL,
                    `seo_name` varchar(128) DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `code` (`code`),
                    KEY `view_rights_id` (`view_rights_id`),
                    KEY `parents_id` (`parents_id`),
                    KEY `categories_id` (`categories_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_chapters_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_chapters_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_chapters_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_chapters_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_chapters_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_chapters_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_chapters_view_rights_id` FOREIGN KEY (`view_rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage pages
            sql()->schema()->table('storage_pages')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `view_rights_id` bigint DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `chapters_id` bigint NOT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `templates_id` bigint DEFAULT NULL,
                    `is_template` tinyint DEFAULT NULL,
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
                    KEY `is_template` (`is_template`),
                    KEY `view_rights_id` (`view_rights_id`),
                    KEY `parents_id` (`parents_id`),
                    KEY `templates_id` (`templates_id`),
                    KEY `categories_id` (`categories_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_pages_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_pages_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_pages_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_pages_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_pages_templates_id` FOREIGN KEY (`templates_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_pages_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_pages_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_pages_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_pages_view_rights_id` FOREIGN KEY (`view_rights_id`) REFERENCES `accounts_rights` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage comments
            sql()->schema()->table('storage_comments')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `chapters_id` bigint NOT NULL,
                    `pages_id` bigint NOT NULL,
                    `content` mediumtext DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                    KEY `pages_id` (`pages_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_comments_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_comments_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_comments_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_comments_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_comments_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_comments_pages_id` FOREIGN KEY (`pages_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage keywords
            sql()->schema()->table('storage_keywords')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `chapters_id` bigint NOT NULL,
                    `pages_id` bigint NOT NULL,
                    `keyword` varchar(64) DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                    KEY `pages_id` (`pages_id`),
                    KEY `keyword` (`keyword`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_keywords_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_keywords_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_keywords_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_keywords_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_keywords_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_keywords_pages_id` FOREIGN KEY (`pages_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage ratings
            sql()->schema()->table('storage_ratings')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `chapters_id` bigint NOT NULL,
                    `pages_id` bigint NOT NULL,
                    `rating` int DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                    KEY `pages_id` (`pages_id`),
                    KEY `rating` (`rating`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_ratings_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_ratings_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_ratings_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_ratings_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_ratings_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_ratings_pages_id` FOREIGN KEY (`pages_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage keywords
            sql()->schema()->table('storage_key_values')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `chapters_id` bigint NOT NULL,
                    `pages_id` bigint NOT NULL,
                    `key` varchar(32) DEFAULT NULL,
                    `seo_key` varchar(32) DEFAULT NULL,
                    `value` varchar(128) DEFAULT NULL,
                    `seo_value` varchar(128) DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                    KEY `pages_id` (`pages_id`),
                    KEY `seo_key` (`seo_key`),
                    KEY `seo_value` (`seo_value`),
                    KEY `pages_id_key` (`pages_id`, `key`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_key_values_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_key_values_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_key_values_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_key_values_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_key_values_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_key_values_pages_id` FOREIGN KEY (`pages_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage keywords
            sql()->schema()->table('storage_file_limits')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `chapters_id` bigint NOT NULL,
                    `pages_id` bigint NOT NULL,
                    `required` tinyint NOT NULL,
                    `type` varchar(16) NOT NULL,
                    `mime1` varchar(8) DEFAULT NULL,
                    `mime2` varchar(8) DEFAULT NULL,
                    `min_size` bigint NOT NULL,
                    `max_size` bigint NOT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                    KEY `pages_id` (`pages_id`),
                    KEY `required` (`required`),
                    KEY `mime1` (`mime1`),
                    KEY `mime2` (`mime2`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_file_limits_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_file_limits_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_file_limits_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_file_limits_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_file_limits_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_file_limits_pages_id` FOREIGN KEY (`pages_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for storage files
            sql()->schema()->table('storage_files')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NULL,
                    `chapters_id` bigint NULL,
                    `pages_id` bigint NULL,
                    `file` varchar(128) NOT NULL,
                    `original` varchar(255) NOT NULL,
                    `hash` varchar(64) NOT NULL,
                    `priority` INT(11) NOT NULL,
                    `type` varchar(16) NOT NULL,
                    `mime1` varchar(8) NOT NULL,
                    `mime2` varchar(8) NOT NULL,
                    `description` varchar(511) NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                    KEY `pages_id` (`pages_id`),
                    KEY `priority` (`priority`),
                    KEY `type` (`type`),
                    KEY `hash` (`hash`),
                    KEY `file` (`file` (16)),
                    KEY `original` (`original` (16)),
                    KEY `mime1` (`mime1`),
                    KEY `mime2` (`mime2`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_files_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_files_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_files_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_files_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_files_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_files_pages_id` FOREIGN KEY (`pages_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for resources
            sql()->schema()->table('storage_resources')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `language` varchar(2) NOT NULL,
                    `description` text NULL,
                    `query` text NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `language` (`language`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_resources_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_resources_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')->create();

            // Add table for page resources
            sql()->schema()->table('storage_page_resources')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `collections_id` bigint NOT NULL,
                    `books_id` bigint NOT NULL,
                    `chapters_id` bigint NOT NULL,
                    `pages_id` bigint NOT NULL,
                    `resources_id` bigint NOT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `collections_id` (`collections_id`),
                    KEY `books_id` (`books_id`),
                    KEY `chapters_id` (`chapters_id`),
                    KEY `pages_id` (`pages_id`),
                    KEY `resources_id` (`resources_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_storage_page_resources_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_page_resources_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_storage_page_resources_collections_id` FOREIGN KEY (`collections_id`) REFERENCES `storage_collections` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_page_resources_books_id` FOREIGN KEY (`books_id`) REFERENCES `storage_books` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_page_resources_chapters_id` FOREIGN KEY (`chapters_id`) REFERENCES `storage_chapters` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_page_resources_pages_id` FOREIGN KEY (`pages_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_storage_page_resources_resources_id` FOREIGN KEY (`resources_id`) REFERENCES `storage_resources` (`id`) ON DELETE RESTRICT,
                ')->create();
        });
    }
}
