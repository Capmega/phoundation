<?php
/*
 * (Re?) add projects table, if needed
 * Add tables for new categories library
 * Add tables for new projects library
 * Add tables for new progress library
 *
 * Update storage_documents table to have process / process steps capabilities
 * Update customers table to use meta_id, drop modifiedon / modifiedby
 * Update customers table add support for phone, email
 *
 * Fix servers and databases tables
 */
sql_foreignkey_exists('inventories'      , 'fk_inventories_projects_id'        , 'ALTER TABLE `inventories`       DROP FOREIGN KEY `fk_inventories_projects_id`');
sql_foreignkey_exists('inventories'      , 'fk_inventories_categories_id'      , 'ALTER TABLE `inventories`       DROP FOREIGN KEY `fk_inventories_categories_id`');
sql_foreignkey_exists('inventories_items', 'fk_inventories_items_categories_id', 'ALTER TABLE `inventories_items` DROP FOREIGN KEY `fk_inventories_items_categories_id`');

sql_foreignkey_exists('companies', 'fk_companies_categories_id', 'ALTER TABLE `companies` DROP FOREIGN KEY `fk_companies_categories_id`');

sql_foreignkey_exists('customers', 'fk_customers_categories_id', 'ALTER TABLE `customers` DROP FOREIGN KEY `fk_customers_categories_id`');
sql_foreignkey_exists('customers', 'fk_customers_documents_id' , 'ALTER TABLE `customers` DROP FOREIGN KEY `fk_customers_documents_id`');

sql_foreignkey_exists('providers', 'fk_providers_categories_id', 'ALTER TABLE `providers` DROP FOREIGN KEY `fk_providers_categories_id`');

sql_foreignkey_exists('storage_documents', 'fk_storage_documents_processes_id', 'ALTER TABLE `storage_documents` DROP FOREIGN KEY `fk_storage_documents_processes_id`');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_steps_id'    , 'ALTER TABLE `storage_documents` DROP FOREIGN KEY `fk_storage_documents_steps_id`');

sql_foreignkey_exists('api_accounts', 'fk_api_accounts_servers_id', 'ALTER TABLE `api_accounts` DROP FOREIGN KEY `fk_api_accounts_servers_id`');

sql_foreignkey_exists('domains_servers', 'fk_domains_servers_servers_id', 'ALTER TABLE `domains_servers` DROP FOREIGN KEY `fk_domains_servers_servers_id`');

sql_foreignkey_exists('email_domains', 'fk_email_domains_servers_id', 'ALTER TABLE `email_domains` DROP FOREIGN KEY `fk_email_domains_servers_id`');

sql_foreignkey_exists('email_servers', 'fk_email_servers_servers_id', 'ALTER TABLE `email_servers` DROP FOREIGN KEY `fk_email_servers_servers_id`');

sql_foreignkey_exists('databases', 'fk_databases_projects_id', 'ALTER TABLE `databases` DROP FOREIGN KEY `fk_databases_projects_id`');

sql_foreignkey_exists('messages_users', 'fk_messages_users_servers_id', 'ALTER TABLE `messages_users` DROP FOREIGN KEY `fk_messages_users_servers_id`');

sql_foreignkey_exists('forwardings', 'fk_forwardings_servers_id', 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwardings_servers_id`');

sql_foreignkey_exists('forwardings', 'fk_forwards_created_by' , 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwards_created_by`');
sql_foreignkey_exists('forwardings', 'fk_forwards_meta_id'   , 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwards_meta_id`');
sql_foreignkey_exists('forwardings', 'fk_forwards_servers_id', 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwards_servers_id`');
sql_foreignkey_exists('forwardings', 'fk_forwards_source_id' , 'ALTER TABLE `forwardings` DROP FOREIGN KEY `fk_forwards_source_id`');

sql_foreignkey_exists('servers_ssh_proxies', 'fk_servers_ssh_proxies_servers_id' , 'ALTER TABLE `servers_ssh_proxies` DROP FOREIGN KEY `fk_servers_ssh_proxies_servers_id`');
sql_foreignkey_exists('servers_ssh_proxies', 'fk_servers_ssh_proxies_proxies_id' , 'ALTER TABLE `servers_ssh_proxies` DROP FOREIGN KEY `fk_servers_ssh_proxies_proxies_id`');



sql_query('DROP TABLE IF EXISTS `progress_steps`');
sql_query('DROP TABLE IF EXISTS `progress_processes`');
sql_query('DROP TABLE IF EXISTS `categories`');
sql_query('DROP TABLE IF EXISTS `databases`');
sql_query('DROP TABLE IF EXISTS `servers`');
sql_query('DROP TABLE IF EXISTS `projects`');
sql_query('DROP TABLE IF EXISTS `database_accounts`');



sql_query('CREATE TABLE `database_accounts` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                             `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                             `created_by`     INT(11)       NOT NULL,
                                             `meta_id`       INT(11)           NULL DEFAULT NULL,
                                             `status`        VARCHAR(16)       NULL DEFAULT NULL,
                                             `name`          VARCHAR(32)   NOT NULL,
                                             `username`      VARCHAR(32)   NOT NULL,
                                             `password`      VARCHAR(64)   NOT NULL,
                                             `root_password` VARCHAR(64)   NOT NULL,
                                             `description`   VARCHAR(2047) NOT NULL,

                                             INDEX  `createdon` (`createdon`),
                                             INDEX  `created_by` (`created_by`),
                                             INDEX  `meta_id`   (`meta_id`),
                                             INDEX  `status`    (`status`),
                                             UNIQUE `name`      (`name`),

                                             CONSTRAINT `fk_database_accounts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_database_accounts_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `categories` (`id`          INT(11)       NOT NULL AUTO_INCREMENT,
                                      `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `created_by`   INT(11)           NULL,
                                      `meta_id`     INT(11)       NOT NULL,
                                      `status`      VARCHAR(16)       NULL,
                                      `parents_id`  INT(11)           NULL,
                                      `name`        VARCHAR(64)       NULL,
                                      `seoname`     VARCHAR(64)       NULL,
                                      `description` VARCHAR(2047)     NULL,

                                      PRIMARY KEY `id`          (`id`),
                                              KEY `meta_id`     (`meta_id`),
                                              KEY `parents_id`  (`parents_id`),
                                              KEY `createdon`   (`createdon`),
                                              KEY `created_by`   (`created_by`),
                                              KEY `status`      (`status`),
                                      UNIQUE  KEY `seoname`     (`seoname`),
                                      UNIQUE  KEY `parent_name` (`parents_id`, `name`),

                                      CONSTRAINT `fk_categories_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_categories_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_categories_created_by`  FOREIGN KEY (`created_by`)  REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `progress_processes` (`id`            INT(11)       NOT NULL AUTO_INCREMENT,
                                              `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `created_by`     INT(11)           NULL,
                                              `meta_id`       INT(11)       NOT NULL,
                                              `status`        VARCHAR(16)       NULL,
                                              `categories_id` INT(11)           NULL,
                                              `name`          VARCHAR(64)       NULL,
                                              `seoname`       VARCHAR(64)       NULL,
                                              `description`   VARCHAR(2047)     NULL,

                                              PRIMARY KEY `id`               (`id`),
                                                      KEY `meta_id`          (`meta_id`),
                                                      KEY `categories_id`    (`categories_id`),
                                                      KEY `createdon`        (`createdon`),
                                                      KEY `created_by`        (`created_by`),
                                                      KEY `status`           (`status`),
                                              UNIQUE  KEY `category_seoname` (`categories_id`, `seoname`),

                                              CONSTRAINT `fk_progress_processes_meta_id`       FOREIGN KEY (`meta_id`)       REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_progress_processes_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_progress_processes_created_by`     FOREIGN KEY (`created_by`)     REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `progress_steps` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                          `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `created_by`    INT(11)           NULL,
                                          `meta_id`      INT(11)       NOT NULL,
                                          `status`       VARCHAR(16)       NULL,
                                          `processes_id` INT(11)       NOT NULL,
                                          `parents_id`   INT(11)           NULL,
                                          `name`         VARCHAR(64)       NULL,
                                          `seoname`      VARCHAR(64)       NULL,
                                          `url`          VARCHAR(255)      NULL,
                                          `description`  VARCHAR(2047)     NULL,

                                          PRIMARY KEY `id`              (`id`),
                                                  KEY `meta_id`         (`meta_id`),
                                                  KEY `createdon`       (`createdon`),
                                                  KEY `created_by`       (`created_by`),
                                                  KEY `status`          (`status`),
                                                  KEY `processes_id`    (`processes_id`),
                                                  KEY `parents_id`      (`parents_id`),
                                          UNIQUE  KEY `process_seoname` (`processes_id`, `seoname`),

                                          CONSTRAINT `fk_progress_steps_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`               (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_progress_steps_created_by`    FOREIGN KEY (`created_by`)    REFERENCES `users`              (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_progress_steps_processes_id` FOREIGN KEY (`processes_id`) REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_progress_steps_parents_id`   FOREIGN KEY (`parents_id`)   REFERENCES `progress_steps`     (`id`) ON DELETE CASCADE

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Due to design problems with the servers table, drop everything and rebuild (ONLY if not in use!)
 */
sql_query('CREATE TABLE `servers` (`id`                   INT(11)       NOT NULL AUTO_INCREMENT,
                                   `createdon`            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                   `created_by`            INT(11)       NOT NULL,
                                   `modifiedon`           DATETIME          NULL DEFAULT NULL,
                                   `modifiedby`           INT(11)           NULL DEFAULT NULL,
                                   `status`               VARCHAR(16)       NULL DEFAULT NULL,
                                   `hostname`             VARCHAR(64)   NOT NULL,
                                   `seohostname`          VARCHAR(64)   NOT NULL,
                                   `port`                 INT(11)       NOT NULL,
                                   `cost`                 DOUBLE(15,5)      NULL DEFAULT NULL,
                                   `bill_duedate`         DATETIME          NULL DEFAULT NULL,
                                   `interval`             ENUM("hourly","daily","weekly","monthly","bimonthly","quarterly","semiannual","anually") NULL DEFAULT NULL,
                                   `providers_id`         INT(11)           NULL DEFAULT NULL,
                                   `customers_id`         INT(11)           NULL DEFAULT NULL,
                                   `ssh_accounts_id`      INT(11)           NULL DEFAULT NULL,
                                   `database_accounts_id` INT(11)           NULL DEFAULT NULL,
                                   `description`          VARCHAR(2047) NOT NULL,
                                   `web`                  TINYINT(4)    NOT NULL,
                                   `mail`                 TINYINT(4)    NOT NULL,
                                   `database`             TINYINT(4)    NOT NULL,
                                   `ipv4`                 VARCHAR(15)       NULL DEFAULT NULL,
                                   `ipv6`                 VARCHAR(39)       NULL DEFAULT NULL,
                                   `os_type`              ENUM("linux","windows","freesd","macos")                NULL DEFAULT NULL,
                                   `os_group`             ENUM("debian","ubuntu","redhat","gentoo","slackware")   NULL DEFAULT NULL,
                                   `os_version`           VARCHAR(6)        NULL DEFAULT NULL,
                                   `os_name`              ENUM("ubuntu","lubuntu","kubuntu","edubuntu","xubuntu","mint","redhat","fedora","centos") NULL DEFAULT NULL,
                                   `ssh_proxy_id`         INT(11)           NULL DEFAULT NULL,
                                   `ssh_port`             VARCHAR(7)        NULL DEFAULT NULL,
                                   `replication_status`   ENUM("enabled","preparing","paused","disabled","error") NULL DEFAULT "disabled",

                                   PRIMARY KEY                        (`id`),
                                   UNIQUE  KEY `hostname`             (`hostname`),
                                   UNIQUE  KEY `seohostname`          (`seohostname`),
                                           KEY `createdon`            (`createdon`),
                                           KEY `created_by`            (`created_by`),
                                           KEY `modifiedon`           (`modifiedon`),
                                           KEY `modifiedby`           (`modifiedby`),
                                           KEY `status`               (`status`),
                                           KEY `providers_id`         (`providers_id`),
                                           KEY `customers_id`         (`customers_id`),
                                           KEY `bill_duedate`         (`bill_duedate`),
                                           KEY `web`                  (`web`),
                                           KEY `mail`                 (`mail`),
                                           KEY `database`             (`database`),
                                           KEY `ssh_accounts_id`      (`ssh_accounts_id`),
                                           KEY `database_accounts_id` (`database_accounts_id`),
                                           KEY `ssh_proxy_id`         (`ssh_proxy_id`),

                                   CONSTRAINT `fk_servers_created_by`            FOREIGN KEY (`created_by`)            REFERENCES `users`             (`id`),
                                   CONSTRAINT `fk_servers_customers_id`         FOREIGN KEY (`customers_id`)         REFERENCES `customers`         (`id`),
                                   CONSTRAINT `fk_servers_database_accounts_id` FOREIGN KEY (`database_accounts_id`) REFERENCES `database_accounts` (`id`),
                                   CONSTRAINT `fk_servers_modifiedby`           FOREIGN KEY (`modifiedby`)           REFERENCES `users`             (`id`),
                                   CONSTRAINT `fk_servers_providers_id`         FOREIGN KEY (`providers_id`)         REFERENCES `providers`         (`id`),
                                   CONSTRAINT `fk_servers_ssh_accounts_id`      FOREIGN KEY (`ssh_accounts_id`)      REFERENCES `ssh_accounts`      (`id`),
                                   CONSTRAINT `fk_servers_ssh_proxy_id`         FOREIGN KEY (`ssh_proxy_id`)         REFERENCES `servers`           (`id`)

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `projects` (`id`            INT(11)       NOT NULL AUTO_INCREMENT,
                                    `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `created_by`     INT(11)           NULL DEFAULT NULL,
                                    `meta_id`       INT(11)       NOT NULL,
                                    `status`        VARCHAR(16)       NULL DEFAULT NULL,
                                    `parents_id`    INT(11)           NULL DEFAULT NULL,
                                    `categories_id` INT(11)           NULL DEFAULT NULL,
                                    `customers_id`  INT(11)           NULL DEFAULT NULL,
                                    `leaders_id`    INT(11)           NULL DEFAULT NULL,
                                    `processes_id`  INT(11)           NULL DEFAULT NULL,
                                    `steps_id`      INT(11)           NULL DEFAULT NULL,
                                    `documents_id`  INT(11)           NULL DEFAULT NULL,
                                    `priority`      INT(11)       NOT NULL,
                                    `name`          VARCHAR(64)       NULL DEFAULT NULL,
                                    `seoname`       VARCHAR(64)       NULL DEFAULT NULL,
                                    `code`          VARCHAR(32)       NULL DEFAULT NULL,
                                    `api_key`       VARCHAR(64)       NULL DEFAULT NULL,
                                    `fcm_api_key`   VARCHAR(511)      NULL DEFAULT NULL,
                                    `last_login`    TIMESTAMP         NULL DEFAULT NULL,
                                    `description`   VARCHAR(2047)     NULL DEFAULT NULL,

                                    PRIMARY KEY                 (`id`),
                                    UNIQUE  KEY `seoname`       (`seoname`),
                                    UNIQUE  KEY `code`          (`code`),
                                    UNIQUE  KEY `api_key`       (`api_key`),
                                            KEY `meta_id`       (`meta_id`),
                                            KEY `createdon`     (`createdon`),
                                            KEY `created_by`     (`created_by`),
                                            KEY `status`        (`status`),
                                            KEY `categories_id` (`categories_id`),
                                            KEY `customers_id`  (`customers_id`),
                                            KEY `documents_id`  (`documents_id`),
                                            KEY `processes_id`  (`processes_id`),
                                            KEY `steps_id`      (`steps_id`),
                                            KEY `leaders_id`    (`leaders_id`),
                                            KEY `priority`      (`priority`),
                                            KEY `parents_id`    (`parents_id`),

                                    CONSTRAINT `fk_projects_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories`         (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_created_by`     FOREIGN KEY (`created_by`)     REFERENCES `users`              (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_customers_id`  FOREIGN KEY (`customers_id`)  REFERENCES `customers`          (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_documents_id`  FOREIGN KEY (`documents_id`)  REFERENCES `storage_documents`  (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_meta_id`       FOREIGN KEY (`meta_id`)       REFERENCES `meta`               (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_parents_id`    FOREIGN KEY (`parents_id`)    REFERENCES `projects`           (`id`) ON DELETE CASCADE,
                                    CONSTRAINT `fk_projects_processes_id`  FOREIGN KEY (`processes_id`)  REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_projects_steps_id`      FOREIGN KEY (`steps_id`)      REFERENCES `progress_steps`     (`id`) ON DELETE CASCADE

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `databases` (`id`                 INT(11)       NOT NULL AUTO_INCREMENT,
                                     `createdon`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `created_by`          INT(11)       NOT NULL,
                                     `meta_id`            INT(11)           NULL DEFAULT NULL,
                                     `status`             VARCHAR(16)       NULL DEFAULT NULL,
                                     `servers_id`         INT(11)       NOT NULL,
                                     `projects_id`        INT(11)           NULL DEFAULT NULL,
                                     `replication_status` ENUM("enabled","preparing","paused","disabled","error") NULL DEFAULT "disabled",
                                     `name`               VARCHAR(64)   NOT NULL,
                                     `description`        VARCHAR(2047)     NULL DEFAULT NULL,
                                     `error`              VARCHAR(2047)     NULL DEFAULT NULL,

                                     PRIMARY KEY                      (`id`),
                                     UNIQUE  KEY `servers_id_name`    (`servers_id`,`name`),
                                             KEY `createdon`          (`createdon`),
                                             KEY `created_by`          (`created_by`),
                                             KEY `meta_id`            (`meta_id`),
                                             KEY `status`             (`status`),
                                             KEY `servers_id`         (`servers_id`),
                                             KEY `projects_id`        (`projects_id`),
                                             KEY `replication_status` (`replication_status`),
                                             KEY `name`               (`name`),

                                     CONSTRAINT `fk_databases_created_by`   FOREIGN KEY (`created_by`)   REFERENCES `users`    (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_databases_meta_id`     FOREIGN KEY (`meta_id`)     REFERENCES `meta`     (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_databases_projects_id` FOREIGN KEY (`projects_id`) REFERENCES `projects` (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_databases_servers_id`  FOREIGN KEY (`servers_id`)  REFERENCES `servers`  (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('ALTER TABLE `customers` MODIFY `name`    VARCHAR(64) NULL DEFAULT NULL');
sql_query('ALTER TABLE `customers` MODIFY `seoname` VARCHAR(64) NULL DEFAULT NULL');

sql_column_exists    ('customers', 'meta_id'             , '!ALTER TABLE `customers` ADD COLUMN     `meta_id` INT(11) NULL DEFAULT NULL AFTER `created_by`');
sql_index_exists     ('customers', 'meta_id'             , '!ALTER TABLE `customers` ADD KEY        `meta_id` (`meta_id`)');
sql_foreignkey_exists('customers', 'fk_customers_meta_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');

sql_index_exists ('customers', 'modifiedon', 'ALTER TABLE `customers` DROP KEY    `modifiedon`');
sql_column_exists('customers', 'modifiedon', 'ALTER TABLE `customers` DROP COLUMN `modifiedon`');

sql_foreignkey_exists('customers', 'fk_customers_modifiedby', 'ALTER TABLE `customers` DROP FOREIGN KEY `fk_customers_modifiedby`');
sql_index_exists ('customers', 'modifiedby', 'ALTER TABLE `customers` DROP KEY    `modifiedby`');
sql_column_exists('customers', 'modifiedby', 'ALTER TABLE `customers` DROP COLUMN `modifiedby`');

sql_column_exists('customers', 'email' , '!ALTER TABLE `customers` ADD COLUMN `email` VARCHAR(96) NULL DEFAULT NULL AFTER `company`');
sql_index_exists ('customers', 'email' , '!ALTER TABLE `customers` ADD KEY    `email` (`email`)');

sql_column_exists('customers', 'phones', '!ALTER TABLE `customers` ADD COLUMN `phones` VARCHAR(36) NULL DEFAULT NULL AFTER `email`');
sql_index_exists ('customers', 'phones', '!ALTER TABLE `customers` ADD KEY    `phones` (`phones`)');

sql_column_exists    ('customers', 'documents_id'             , '!ALTER TABLE `customers` ADD COLUMN     `documents_id` INT(11) NULL DEFAULT NULL AFTER `phones`');
sql_index_exists     ('customers', 'documents_id'             , '!ALTER TABLE `customers` ADD KEY        `documents_id` (`documents_id`)');
sql_foreignkey_exists('customers', 'fk_customers_documents_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_documents_id` FOREIGN KEY (`documents_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;');

sql_column_exists    ('customers', 'categories_id'             , '!ALTER TABLE `customers` ADD COLUMN     `categories_id` INT(11) NULL DEFAULT NULL AFTER `documents_id`');
sql_index_exists     ('customers', 'categories_id'             , '!ALTER TABLE `customers` ADD KEY        `categories_id` (`categories_id`)');
sql_foreignkey_exists('customers', 'fk_customers_categories_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;');

sql_column_exists    ('storage_documents', 'processes_id'                     , '!ALTER TABLE `storage_documents` ADD COLUMN     `processes_id` INT(11) NULL DEFAULT NULL AFTER `customers_id`');
sql_index_exists     ('storage_documents', 'processes_id'                     , '!ALTER TABLE `storage_documents` ADD KEY        `processes_id` (`processes_id`)');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_processes_id', '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_processes_id` FOREIGN KEY (`processes_id`) REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT;');

sql_column_exists    ('storage_documents', 'steps_id'                     , '!ALTER TABLE `storage_documents` ADD COLUMN     `steps_id` INT(11) NULL DEFAULT NULL AFTER `processes_id`');
sql_index_exists     ('storage_documents', 'steps_id'                     , '!ALTER TABLE `storage_documents` ADD KEY        `steps_id` (`steps_id`)');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_steps_id', '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_steps_id` FOREIGN KEY (`steps_id`) REFERENCES `progress_steps` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('databases', 'fk_databases_projects_id', '!ALTER TABLE `databases` ADD CONSTRAINT `fk_databases_projects_id` FOREIGN KEY (`projects_id`) REFERENCES `projects` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('customers', 'fk_customers_categories_id', '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories`        (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('customers', 'fk_customers_documents_id' , '!ALTER TABLE `customers` ADD CONSTRAINT `fk_customers_documents_id`  FOREIGN KEY (`documents_id`)  REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('projects', 'fk_projects_processes_id' , '!ALTER TABLE `projects` ADD CONSTRAINT `fk_projects_processes_id`  FOREIGN KEY (`processes_id`)  REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('projects', 'fk_projects_steps_id'     , '!ALTER TABLE `projects` ADD CONSTRAINT `fk_projects_steps_id`      FOREIGN KEY (`steps_id`)      REFERENCES `progress_steps`     (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('projects', 'fk_projects_categories_id', '!ALTER TABLE `projects` ADD CONSTRAINT `fk_projects_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories`         (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('storage_documents', 'fk_storage_documents_processes_id', '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_processes_id` FOREIGN KEY (`processes_id`) REFERENCES `progress_processes` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('storage_documents', 'fk_storage_documents_steps_id'    , '!ALTER TABLE `storage_documents` ADD CONSTRAINT `fk_storage_documents_steps_id`     FOREIGN KEY (`steps_id`)     REFERENCES `progress_steps`     (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('api_accounts', 'fk_api_accounts_servers_id', '!ALTER TABLE `api_accounts` ADD CONSTRAINT `fk_api_accounts_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('domains_servers', 'fk_domains_servers_servers_id', '!ALTER TABLE `domains_servers` ADD CONSTRAINT `fk_domains_servers_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('email_domains', 'fk_email_domains_servers_id', '!ALTER TABLE `email_domains` ADD CONSTRAINT `fk_email_domains_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('email_servers', 'fk_email_servers_servers_id', '!ALTER TABLE `email_servers` ADD CONSTRAINT `fk_email_servers_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('forwardings', 'fk_forwardings_created_by' , '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_created_by`  FOREIGN KEY (`servers_id`) REFERENCES `users`   (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('forwardings', 'fk_forwardings_meta_id'   , '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_meta_id`    FOREIGN KEY (`servers_id`) REFERENCES `meta`    (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('forwardings', 'fk_forwardings_servers_id', '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('forwardings', 'fk_forwardings_source_id' , '!ALTER TABLE `forwardings` ADD CONSTRAINT `fk_forwardings_source_id`  FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');

sql_foreignkey_exists('servers_ssh_proxies', 'fk_servers_ssh_proxies_servers_id', '!ALTER TABLE `servers_ssh_proxies` ADD CONSTRAINT `fk_servers_ssh_proxies_servers_id` FOREIGN KEY (`servers_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');
sql_foreignkey_exists('servers_ssh_proxies', 'fk_servers_ssh_proxies_proxies_id', '!ALTER TABLE `servers_ssh_proxies` ADD CONSTRAINT `fk_servers_ssh_proxies_proxies_id` FOREIGN KEY (`proxies_id`) REFERENCES `servers` (`id`) ON DELETE RESTRICT;');
?>
