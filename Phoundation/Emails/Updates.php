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
                    `users_id` bigint DEFAULT NULL,      // User to which this email belongs
                    `parents_id` bigint DEFAULT NULL,    // Parent email, in case these emails form a chain
                    `box_id` bigint DEFAULT NULL,        // Mail box where which email is stored
                    `read` tinyint DEFAULT NULL,         // Tracks if email has been read or not
                    `categories_id` bigint DEFAULT NULL, // Category for this mail, priority, etc.
                    `templates_id` bigint DEFAULT NULL,  // Template used for this email
                    `code` varchar(64) DEFAULT NULL,     // Unique email identifier code
                    `subject` varchar(255) DEFAULT NULL, // Subject
                    `body` text DEFAULT NULL             // Email body
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `code` (`code`),
                    KEY `parents_id` (`parents_id`),
                    KEY `templates_id` (`templates_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_emails_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_templates_id` FOREIGN KEY (`templates_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for to, cc, bcc, from
            sql()->schema()->table('emails_tos')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `emails_id` bigint DEFAULT NULL,                 // The email to which this TO / CC / BCC, FROM entry belongs
                    `users_id` bigint NOT NULL,                      // Optionally, the local user to which this belongs
                    `accounts_id` bigint DEFAULT NULL,               // Optionally the email account to which this belongs
                    `email` varchar(128) NOT NULL,                   // The email address
                    `name` varchar(128) DEFAULT NULL,                // The real name
                    `type` enum("to", "cc", "bcc", "from") NOT NULL, // The type of address, to, cc, bcc, from.
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `users_id` (`users_id`),
                    KEY `emails_id` (`emails_id`),
                    KEY `accounts_id` (`accounts_id`),
                    KEY `cc` (`cc` (32)),
                    KEY `bcc` (`bcc`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_tos_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_tos_emails_id` FOREIGN KEY (`emails_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_tos_accounts_id` FOREIGN KEY (`accounts_id`) REFERENCES `email_accounts` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for email accounts
            sql()->schema()->table('emails_accounts')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NOT NULL,      // Optionally, the local user to which this belongs
                    `view_roles_id` bigint NOT NULL, // Optionally, required role to see this account
                    `send_roles_id` bigint NOT NULL, // Optionally, required role to send as this account
                    `send_roles_id` bigint NOT NULL, // Optionally, required role to send as this account
                    `smtp_host` varchar(255) NULL,   // The SMTP host
                    `smtp_port` int NULL,            // The SMTP port
                    `smtp_auth` tinyint NULL,        // If SMTP requires authentication
                    `smtp_secure` enum ("tls") NULL, // The type of SMTP security to use
                    `user` varchar(255) NULL,        // user to authenticate
                    `password` varchar(255) NULL,    // password to authenticate
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `users_id` (`users_id`),
                    KEY `emails_id` (`emails_id`),
                    KEY `accounts_id` (`accounts_id`),
                    KEY `cc` (`cc` (32)),
                    KEY `bcc` (`bcc`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_tos_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_tos_emails_id` FOREIGN KEY (`emails_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_tos_accounts_id` FOREIGN KEY (`accounts_id`) REFERENCES `email_accounts` (`id`) ON DELETE RESTRICT,
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
