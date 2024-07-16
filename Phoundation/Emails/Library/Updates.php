<?php

/**
 * Updates class
 *
 * This is the Init class for the Email library
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Emails
 */

declare(strict_types=1);

namespace Phoundation\Emails\Library;



class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.18';
    }


    /**
     * The list of version updates available for this library
     *f
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.16', function () {
            // Drop the tables to be sure we have a clean slate
            sql()->getSchemaObject()->getTableObject('emails_attachments')->drop();
            sql()->getSchemaObject()->getTableObject('emails_addresses_linked')->drop();
            sql()->getSchemaObject()->getTableObject('emails_addresses')->drop();
            sql()->getSchemaObject()->getTableObject('emails_accounts')->drop();
            sql()->getSchemaObject()->getTableObject('emails_labels_links')->drop();
            sql()->getSchemaObject()->getTableObject('emails_labels')->drop();
            sql()->getSchemaObject()->getTableObject('emails')->drop();

            // Add table for emails
            sql()->getSchemaObject()->getTableObject('emails')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NOT NULL,          /* User to which this email belongs */
                    `parents_id` bigint DEFAULT NULL,    /* Parent email, in case these emails form a chain */
                    `main` tinyint DEFAULT NULL,         /* If shows in main  */
                    `read` tinyint DEFAULT NULL,         /* Tracks if email has been read or not */
                    `categories_id` bigint DEFAULT NULL, /* Category for this mail, priority, etc. */
                    `templates_id` bigint DEFAULT NULL,  /* Template used for this email */
                    `code` varchar(64) DEFAULT NULL,     /* Unique email identifier code */
                    `subject` varchar(255) DEFAULT NULL, /* Subject */
                    `body` text DEFAULT NULL             /* Email body */
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `code` (`code`),
                    KEY `parents_id` (`parents_id`),
                    KEY `templates_id` (`templates_id`),
                    KEY `main` (`main`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_emails_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_templates_id` FOREIGN KEY (`templates_id`) REFERENCES `storage_pages` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for emails
            sql()->getSchemaObject()->getTableObject('emails_labels')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NOT NULL,          /* User to which this email label belongs */
                    `parents_id` bigint DEFAULT NULL,    /* Parent email label */
                    `name` varchar(64) DEFAULT NULL,     /* Label name */
                    `seo_name` varchar(64) DEFAULT NULL, /* Label seo name */
                    `description` text DEFAULT NULL      /* Label description */
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_on` (`created_on`),
                    KEY `created_by` (`created_by`),
                    KEY `status` (`status`),
                    KEY `meta_id` (`meta_id`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `name` (`name`),
                    KEY `parents_id` (`parents_id`),
                    KEY `users_id` (`users_id`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_labels_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_labels_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_emails_labels_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `emails_labels` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_labels_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for emails
            sql()->getSchemaObject()->getTableObject('emails_labels_links')->define()
                 ->setColumns('
                    `emails_id` bigint NOT NULL,    /* Email */
                    `labels_id` bigint DEFAULT NULL /* Label */
                ')->setIndices('
                    UNIQUE KEY `labels_id_emails_id` (`labels_id`, `emails_id`),
                    KEY `labels_id` (`labels_id`),
                    KEY `emails_id` (`emails_id`),
                ')->setForeignKeys('
                    CONSTRAINT `emails_labels_links_labels_id` FOREIGN KEY (`labels_id`) REFERENCES `emails_labels` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `emails_labels_links_emails_id` FOREIGN KEY (`emails_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE,
                ')->create();

            // Add table for email accounts
            sql()->getSchemaObject()->getTableObject('emails_accounts')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `users_id` bigint NOT NULL,          /* Optionally, the local user to which this belongs */
                    `send_roles_id` bigint DEFAULT NULL, /* Optionally, required role to send as this account */
                    `smtp_host` varchar(255) NULL,       /* The SMTP host */
                    `smtp_port` int NULL,                /* The SMTP port */
                    `smtp_auth` tinyint NULL,            /* If SMTP requires authentication */
                    `smtp_secure` enum ("tls") NULL,     /* The type of SMTP security to use */
                    `name` varchar(64) NULL,             /* account identification name */
                    `seo_name` varchar(64) NULL,         /* account identification seo name */
                    `username` varchar(255) NULL,        /* user to authenticate */
                    `password` varchar(255) NULL,        /* password to authenticate */
                    `description` text NULL              /* description */
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `users_id` (`users_id`),
                    KEY `send_roles_id` (`send_roles_id`),
                    KEY `smtp_host` (`smtp_host`),
                    KEY `name` (`name`),
                    KEY `seo_name` (`seo_name`),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_accounts_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_accounts_send_roles_id` FOREIGN KEY (`send_roles_id`) REFERENCES `accounts_roles` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for to, cc, bcc, from
            sql()->getSchemaObject()->getTableObject('emails_addresses')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `users_id` bigint DEFAULT NULL,     /* Optionally, the local user to which this address belongs */
                    `accounts_id` bigint DEFAULT NULL,  /* Optionally the email account to which this belongs */
                    `list_roles_id` bigint NOT NULL,    /* Optionally, required role to have this account in list */
                    `email` varchar(128) NOT NULL,      /* The email address */
                    `name` varchar(128) DEFAULT NULL,   /* The real name */
                    `description` text DEFAULT NULL     /* Description */
                ')->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `users_id_email` (`users_id`, `email`),
                    KEY `users_id` (`users_id`),
                    KEY `accounts_id` (`accounts_id`),
                    KEY `list_roles_id` (`list_roles_id`),
                    KEY `email` (`email` (64)),
                    KEY `name` (`name` (64)),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_addresses_users_id` FOREIGN KEY (`users_id`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_addresses_accounts_id` FOREIGN KEY (`accounts_id`) REFERENCES `emails_accounts` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_addresses_list_roles_id` FOREIGN KEY (`list_roles_id`) REFERENCES `accounts_roles` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for to, cc, bcc, from
            sql()->getSchemaObject()->getTableObject('emails_addresses_linked')->define()
                 ->setColumns('
                    `emails_id` bigint NOT NULL AUTO_INCREMENT,
                    `address_id` bigint DEFAULT NULL,                /* The email to which this TO / CC / BCC, FROM entry belongs */
                    `type` enum("to", "cc", "bcc", "from") NOT NULL, /* The type of address, to, cc, bcc, from. */
                    `email` varchar(128) NOT NULL,                   /* The email address */
                    `name` varchar(128) DEFAULT NULL                 /* The real name */
                ')->setIndices('
                    KEY `emails_id` (`emails_id`),
                    KEY `address_id` (`address_id`),
                    KEY `type` (`type`),
                    KEY `email` (`email` (64)),
                    KEY `name` (`name` (64)),
                ')->setForeignKeys('
                    CONSTRAINT `fk_emails_addresses_linked_emails_id` FOREIGN KEY (`emails_id`) REFERENCES `emails` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_emails_addresses_linked_address_id` FOREIGN KEY (`address_id`) REFERENCES `emails_addresses` (`id`) ON DELETE RESTRICT,
                ')->create();

            // Add table for attachments
            sql()->getSchemaObject()->getTableObject('emails_attachments')->define()
                 ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NULL DEFAULT NULL,
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

        })->addUpdate('0.0.17', function () {
            sql()->getSchemaObject()->getTableObject('emails_attachments')
                 ->alter()
                 ->changeColumn('local_path', '`local_directory` varchar(128) NOT NULL')
                 ->dropIndex('local_path')
                 ->addIndex('UNIQUE KEY local_path (`local_directory`)');
        });
    }
}
