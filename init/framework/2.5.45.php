<?php
/*
 * Add support for project parents and priorities
 */
sql_column_exists('projects', 'priority', '!ALTER TABLE `projects` ADD COLUMN `priority` INT(11) NOT NULL AFTER `documents_id`');
sql_index_exists ('projects', 'priority', '!ALTER TABLE `projects` ADD KEY    `priority` (`priority`)');

sql_column_exists('projects', 'parents_id', '!ALTER TABLE `projects` ADD COLUMN `parents_id` INT(11) NULL AFTER `status`');
sql_index_exists ('projects', 'parents_id', '!ALTER TABLE `projects` ADD KEY    `parents_id` (`parents_id`)');

sql_foreignkey_exists('projects', 'fk_projects_parents_id', '!ALTER TABLE `projects` ADD CONSTRAINT `fk_projects_parents_id` FOREIGN KEY (`parents_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;');


/*
 * Add required tables for invoices library
 */
sql_query('DROP TABLE IF EXISTS `payments`');
sql_query('DROP TABLE IF EXISTS `invoices_items`');
sql_query('DROP TABLE IF EXISTS `invoices`');



/*
 * Create invoices table
 */
sql_query('CREATE TABLE `invoices` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                    `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `created_by`    INT(11)           NULL,
                                    `meta_id`      INT(11)       NOT NULL,
                                    `status`       VARCHAR(16)       NULL,
                                    `providers_id` INT(11)           NULL,
                                    `customers_id` INT(11)           NULL,
                                    `due_date`     DATETIME          NULL,
                                    `paid_date`    DATETIME          NULL,
                                    `payment`      DECIMAL(15,4) NOT NULL,
                                    `paid`         DECIMAL(15,4) NOT NULL,
                                    `currency`     VARCHAR(6)    NOT NULL,
                                    `code`         VARCHAR(32)       NULL,
                                    `seocode`      VARCHAR(32)       NULL,
                                    `name`         VARCHAR(32)       NULL,
                                    `description`  VARCHAR(4090)     NULL,

                                    PRIMARY KEY `id`           (`id`),
                                            KEY `meta_id`      (`meta_id`),
                                            KEY `createdon`    (`createdon`),
                                            KEY `created_by`    (`created_by`),
                                            KEY `status`       (`status`),
                                            KEY `customers_id` (`customers_id`),
                                            KEY `providers_id` (`providers_id`),
                                            KEY `seocode`      (`seocode`),
                                            KEY `code`         (`code`),
                                            KEY `paid_date`    (`paid_date`),
                                            KEY `due_date`     (`due_date`),

                                    CONSTRAINT `fk_invoices_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`      (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_invoices_created_by`    FOREIGN KEY (`created_by`)    REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_invoices_customers_id` FOREIGN KEY (`customers_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_invoices_providers_id` FOREIGN KEY (`providers_id`) REFERENCES `providers` (`id`) ON DELETE RESTRICT

                                  ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Create invoices items table
 */
sql_query('CREATE TABLE `invoices_items` (`id`                 INT(11)       NOT NULL AUTO_INCREMENT,
                                         `createdon`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `created_by`           INT(11)           NULL,
                                         `meta_id`             INT(11)       NOT NULL,
                                         `status`              VARCHAR(16)       NULL,
                                         `invoices_id`         INT(11)       NOT NULL,
                                         `projects_id`         INT(11)           NULL,
                                         `sub_projects_id`     INT(11)           NULL,
                                         `documents_id`        INT(11)           NULL,
                                         `type`                ENUM("service", "product") NOT NULL,
                                         `inventories_id`      INT(11)           NULL,
                                         `inventories_item_id` INT(11)           NULL,
                                         `count`               INT(11)           NULL,
                                         `payment`             DECIMAL(15,4) NOT NULL,
                                         `paid`                DECIMAL(15,4) NOT NULL,
                                         `currency`            VARCHAR(6)    NOT NULL,
                                         `name`                VARCHAR(64)   NOT NULL,
                                         `seoname`             VARCHAR(64)   NOT NULL,
                                         `description`         VARCHAR(4090)     NULL,

                                         PRIMARY KEY `id`              (`id`),
                                                 KEY `meta_id`         (`meta_id`),
                                                 KEY `createdon`       (`createdon`),
                                                 KEY `created_by`       (`created_by`),
                                                 KEY `status`          (`status`),
                                                 KEY `invoices_id`     (`invoices_id`),
                                                 KEY `projects_id`     (`projects_id`),
                                                 KEY `sub_projects_id` (`sub_projects_id`),
                                                 KEY `documents_id`    (`documents_id`),
                                                 KEY `inventories_id`  (`inventories_id`),

                                          CONSTRAINT `fk_invoices_items_meta_id`         FOREIGN KEY (`meta_id`)         REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_invoices_items_created_by`       FOREIGN KEY (`created_by`)       REFERENCES `users`             (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_invoices_items_invoices_id`     FOREIGN KEY (`invoices_id`)     REFERENCES `invoices`          (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_invoices_items_projects_id`     FOREIGN KEY (`projects_id`)     REFERENCES `projects`          (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_invoices_items_sub_projects_id` FOREIGN KEY (`sub_projects_id`) REFERENCES `projects`          (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_invoices_items_documents_id`    FOREIGN KEY (`documents_id`)    REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_invoices_items_inventories_id`  FOREIGN KEY (`inventories_id`)  REFERENCES `inventories`       (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');




/*
 * Create payments table
 */
sql_query('CREATE TABLE `payments` (`id`          INT(11)       NOT NULL AUTO_INCREMENT,
                                    `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `created_by`   INT(11)           NULL,
                                    `meta_id`     INT(11)       NOT NULL,
                                    `status`      VARCHAR(16)       NULL,
                                    `items_id`    INT(11)           NULL,
                                    `invoices_id` INT(11)       NOT NULL,
                                    `payment`     DECIMAL(15,4) NOT NULL,
                                    `currency`    VARCHAR(6)    NOT NULL,
                                    `email`       VARCHAR(128)      NULL,
                                    `description` VARCHAR(2040)     NULL,

                                    PRIMARY KEY `id`          (`id`),
                                            KEY `meta_id`     (`meta_id`),
                                            KEY `createdon`   (`createdon`),
                                            KEY `created_by`   (`created_by`),
                                            KEY `status`      (`status`),
                                            KEY `items_id`    (`items_id`),
                                            KEY `invoices_id` (`invoices_id`),

                                    CONSTRAINT `fk_payments_meta_id`     FOREIGN KEY (`meta_id`)     REFERENCES `meta`           (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_payments_created_by`   FOREIGN KEY (`created_by`)   REFERENCES `users`          (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_payments_items_id`    FOREIGN KEY (`items_id`)    REFERENCES `invoices_items` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_payments_invoices_id` FOREIGN KEY (`invoices_id`) REFERENCES `invoices`       (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
