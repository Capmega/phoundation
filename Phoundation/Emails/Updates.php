<?php

declare(strict_types=1);

namespace Phoundation\Emails;


/**
 * Updates class
 *
 * This is the Init class for the Email library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Emails
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
        return tr('The Emails library contains functions to manage, send and receive emails');
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
            sql()->schema()->table('emails_attachments')->drop();
            sql()->schema()->table('emails_cc')->drop();
            sql()->schema()->table('emails')->drop();

            // Add table for emails
            sql()->schema()->table('emails')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `parents_id` bigint DEFAULT NULL,
                    `categories_id` bigint DEFAULT NULL,
                    `code` varchar(64) DEFAULT NULL,
                    `to_users_id` bigint DEFAULT NULL,
                    `to_name` varchar(128) DEFAULT NULL,
                    `to_email` varchar(255) DEFAULT NULL,
                    `from_users_id` bigint DEFAULT NULL,
                    `from_name` varchar(128) DEFAULT NULL,
                    `from_email` varchar(255) DEFAULT NULL,
                    `subject` varchar(255) DEFAULT NULL,
                    `body` text DEFAULT NULL
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `code` (`code`),
                    KEY `to_name` (`to_name`),
                    KEY `to_email` (`to_email`),
                    KEY `from_name` (`from_name`),
                    KEY `from_email` (`from_email`),
                    KEY `categories_id` (`categories_id`),
                    KEY `to_users_id` (`to_users_id`),
                    KEY `from_users_id` (`from_users_id`),
                    KEY `parents_id` (`parents_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_emails_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_to_users_id` FOREIGN KEY (`to_users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_from_users_id` FOREIGN KEY (`from_users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for cc's
            sql()->schema()->table('emails_ccs')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `emails_id` bigint DEFAULT NULL,
                    `cc` varchar(255) DEFAULT NULL,
                    `bcc` tinyint(1) DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `emails_id` (`emails_id`),
                    KEY `cc` (`cc` (32)),
                    KEY `bcc` (`bcc`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_ccs_emails_id` FOREIGN KEY (`emails_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for attachments
            sql()->schema()->table('emails_attachments')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `emails_id` bigint DEFAULT NULL,
                    `filename` varchar(255) DEFAULT NULL,
                    `local_path` varchar(512) DEFAULT NULL,
                    `size` bigint DEFAULT NULL,
                    `mimetype` varchar(127) DEFAULT NULL,
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    KEY `emails_id` (`emails_id`),
                    UNIQUE KEY `local_path` (`local_path`),
                    KEY `filename` (`filename` (16)),
                    KEY `size` (`size`),
                    KEY `mimetype` (`mimetype`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_attachments_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_attachments_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_emails_attachments_emails_id` FOREIGN KEY (`emails_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                ')->create();
        });
    }
}
