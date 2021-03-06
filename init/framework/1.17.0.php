<?php
/*
 * Add tables for new inventories library
 * Add tables for new companies library
 *
 * Update storage_documents table to have process / process steps capabilities
 * Update customers table to use meta_id, drop modifiedon / modifiedby
 * Update customers table add support for phone, email
 *
 * Fix servers and databases tables
 */
sql_query('DROP TABLE IF EXISTS `inventories`');
sql_query('DROP TABLE IF EXISTS `inventories_items`');
sql_query('DROP TABLE IF EXISTS `employees`');
sql_query('DROP TABLE IF EXISTS `departments`');
sql_query('DROP TABLE IF EXISTS `branches`');
sql_query('DROP TABLE IF EXISTS `companies`');



sql_query('CREATE TABLE `companies` (`id`            INT(11)       NOT NULL AUTO_INCREMENT,
                                     `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `createdby`     INT(11)           NULL DEFAULT NULL,
                                     `meta_id`       INT(11)       NOT NULL,
                                     `status`        VARCHAR(16)       NULL DEFAULT NULL,
                                     `categories_id` INT(11)           NULL DEFAULT NULL,
                                     `name`          VARCHAR(64)       NULL DEFAULT NULL,
                                     `seoname`       VARCHAR(64)       NULL DEFAULT NULL,
                                     `description`   VARCHAR(2047)     NULL DEFAULT NULL,

                                     PRIMARY KEY `id`              (`id`),
                                             KEY `meta_id`         (`meta_id`),
                                             KEY `categories_id`   (`categories_id`),
                                             KEY `createdon`       (`createdon`),
                                             KEY `createdby`       (`createdby`),
                                             KEY `status`          (`status`),
                                     UNIQUE  KEY `seoname`         (`seoname`),
                                     UNIQUE  KEY `categories_name` (`categories_id`, `name`),

                                     CONSTRAINT `fk_companies_meta_id`       FOREIGN KEY (`meta_id`)       REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_companies_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_companies_createdby`     FOREIGN KEY (`createdby`)     REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `branches` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                    `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `createdby`    INT(11)           NULL DEFAULT NULL,
                                    `meta_id`      INT(11)       NOT NULL,
                                    `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                    `companies_id` INT(11)       NOT NULL,
                                    `name`         VARCHAR(64)       NULL DEFAULT NULL,
                                    `seoname`      VARCHAR(64)       NULL DEFAULT NULL,
                                    `description`  VARCHAR(2047)     NULL DEFAULT NULL,

                                    PRIMARY KEY `id`           (`id`),
                                            KEY `meta_id`      (`meta_id`),
                                            KEY `companies_id` (`companies_id`),
                                            KEY `createdon`    (`createdon`),
                                            KEY `createdby`    (`createdby`),
                                            KEY `status`       (`status`),
                                    UNIQUE  KEY `seoname`      (`seoname`),
                                    UNIQUE  KEY `company_name` (`companies_id`, `name`),

                                    CONSTRAINT `fk_branches_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`      (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_branches_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
                                    CONSTRAINT `fk_branches_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `departments` (`id`           INT(11)       NOT NULL AUTO_INCREMENT,
                                       `createdon`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `createdby`    INT(11)           NULL DEFAULT NULL,
                                       `meta_id`      INT(11)       NOT NULL,
                                       `status`       VARCHAR(16)       NULL DEFAULT NULL,
                                       `companies_id` INT(11)       NOT NULL,
                                       `branches_id`  INT(11)           NULL DEFAULT NULL,
                                       `name`         VARCHAR(64)       NULL DEFAULT NULL,
                                       `seoname`      VARCHAR(64)       NULL DEFAULT NULL,
                                       `description`  VARCHAR(2047)     NULL DEFAULT NULL,

                                       PRIMARY KEY `id`                  (`id`),
                                               KEY `meta_id`             (`meta_id`),
                                               KEY `companies_id`        (`companies_id`),
                                               KEY `createdon`           (`createdon`),
                                               KEY `createdby`           (`createdby`),
                                               KEY `status`              (`status`),
                                       UNIQUE  KEY `seoname`             (`seoname`),
                                       UNIQUE  KEY `company_branch_name` (`companies_id`, `branches_id`, `name`),

                                       CONSTRAINT `fk_departments_meta_id`      FOREIGN KEY (`meta_id`)      REFERENCES `meta`      (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_departments_companies_id` FOREIGN KEY (`companies_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_departments_branches_id`  FOREIGN KEY (`branches_id`)  REFERENCES `branches`  (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_departments_createdby`    FOREIGN KEY (`createdby`)    REFERENCES `users`     (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `employees` (`id`             INT(11)       NOT NULL AUTO_INCREMENT,
                                     `createdon`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `createdby`      INT(11)           NULL DEFAULT NULL,
                                     `meta_id`        INT(11)       NOT NULL,
                                     `status`         VARCHAR(16)       NULL DEFAULT NULL,
                                     `companies_id`   INT(11)       NOT NULL,
                                     `branches_id`    INT(11)       NOT NULL,
                                     `departments_id` INT(11)       NOT NULL,
                                     `users_id`       INT(11)           NULL DEFAULT NULL,
                                     `name`           VARCHAR(64)       NULL DEFAULT NULL,
                                     `seoname`        VARCHAR(64)       NULL DEFAULT NULL,
                                     `description`    VARCHAR(2047)     NULL DEFAULT NULL,

                                     PRIMARY KEY `id`             (`id`),
                                             KEY `meta_id`        (`meta_id`),
                                             KEY `companies_id`   (`companies_id`),
                                             KEY `branches_id`    (`branches_id`),
                                             KEY `departments_id` (`departments_id`),
                                             KEY `createdon`      (`createdon`),
                                             KEY `createdby`      (`createdby`),
                                             KEY `status`         (`status`),
                                     UNIQUE  KEY `seoname`        (`seoname`),

                                     CONSTRAINT `fk_employees_meta_id`        FOREIGN KEY (`meta_id`)        REFERENCES `meta`        (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_employees_companies_id`   FOREIGN KEY (`companies_id`)   REFERENCES `companies`   (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_employees_branches_id`    FOREIGN KEY (`branches_id`)    REFERENCES `branches`    (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_employees_departments_id` FOREIGN KEY (`departments_id`) REFERENCES `departments` (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_employees_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`       (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `inventories_items` (`id`            INT(11)       NOT NULL AUTO_INCREMENT,
                                             `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                             `createdby`     INT(11)           NULL DEFAULT NULL,
                                             `meta_id`       INT(11)       NOT NULL,
                                             `categories_id` INT(11)           NULL DEFAULT NULL,
                                             `providers_id`  INT(11)           NULL DEFAULT NULL,
                                             `documents_id`  INT(11)           NULL DEFAULT NULL,
                                             `status`        VARCHAR(16)       NULL DEFAULT NULL,
                                             `code`          VARCHAR(32)       NULL DEFAULT NULL,
                                             `brand`         VARCHAR(64)       NULL DEFAULT NULL,
                                             `seobrand`      VARCHAR(64)       NULL DEFAULT NULL,
                                             `model`         VARCHAR(64)       NULL DEFAULT NULL,
                                             `seomodel`      VARCHAR(64)       NULL DEFAULT NULL,
                                             `description`   VARCHAR(2047)     NULL DEFAULT NULL,

                                              PRIMARY KEY `id`            (`id`),
                                                      KEY `meta_id`       (`meta_id`),
                                                      KEY `categories_id` (`categories_id`),
                                                      KEY `providers_id`  (`providers_id`),
                                                      KEY `createdon`     (`createdon`),
                                                      KEY `createdby`     (`createdby`),
                                                      KEY `status`        (`status`),
                                                      KEY `brand`         (`brand`),
                                                      KEY `model`         (`model`),
                                             UNIQUE   KEY `seobrand`      (`seobrand`),
                                             UNIQUE   KEY `brand_model`   (`brand`, `model`),

                                             CONSTRAINT `fk_inventories_items_meta_id`       FOREIGN KEY (`meta_id`)       REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_inventories_items_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories`        (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_inventories_items_providers_id`  FOREIGN KEY (`providers_id`)  REFERENCES `providers`         (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_inventories_items_documents_id`  FOREIGN KEY (`documents_id`)  REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                             CONSTRAINT `fk_inventories_items_createdby`     FOREIGN KEY (`createdby`)     REFERENCES `users`             (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `inventories` (`id`             INT(11)       NOT NULL AUTO_INCREMENT,
                                       `createdon`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `createdby`      INT(11)           NULL DEFAULT NULL,
                                       `meta_id`        INT(11)       NOT NULL,
                                       `status`         VARCHAR(16)       NULL DEFAULT NULL,
                                       `companies_id`   INT(11)       NOT NULL,
                                       `branches_id`    INT(11)           NULL DEFAULT NULL,
                                       `departments_id` INT(11)           NULL DEFAULT NULL,
                                       `employees_id`   INT(11)           NULL DEFAULT NULL,
                                       `categories_id`  INT(11)           NULL DEFAULT NULL,
                                       `customers_id`   INT(11)           NULL DEFAULT NULL,
                                       `projects_id`    INT(11)           NULL DEFAULT NULL,
                                       `items_id`       INT(11)       NOT NULL,
                                       `code`           VARCHAR(32)       NULL DEFAULT NULL,
                                       `description`    VARCHAR(2047)     NULL DEFAULT NULL,

                                       PRIMARY KEY `id`             (`id`),
                                               KEY `meta_id`        (`meta_id`),
                                               KEY `companies_id`   (`companies_id`),
                                               KEY `branches_id`    (`branches_id`),
                                               KEY `departments_id` (`departments_id`),
                                               KEY `employees_id`   (`employees_id`),
                                               KEY `categories_id`  (`categories_id`),
                                               KEY `items_id`       (`items_id`),
                                               KEY `customers_id`   (`customers_id`),
                                               KEY `projects_id`    (`projects_id`),
                                               KEY `createdon`      (`createdon`),
                                               KEY `createdby`      (`createdby`),
                                               KEY `status`         (`status`),
                                       UNIQUE  KEY `companies_code` (`companies_id`, `code`),

                                       CONSTRAINT `fk_inventories_meta_id`        FOREIGN KEY (`meta_id`)        REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_companies_id`   FOREIGN KEY (`companies_id`)   REFERENCES `companies`         (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_branches_id`    FOREIGN KEY (`branches_id`)    REFERENCES `branches`          (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_departments_id` FOREIGN KEY (`departments_id`) REFERENCES `departments`       (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_employees_id`   FOREIGN KEY (`employees_id`)   REFERENCES `employees`         (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_categories_id`  FOREIGN KEY (`categories_id`)  REFERENCES `categories`        (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_items_id`       FOREIGN KEY (`items_id`)       REFERENCES `inventories_items` (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_customers_id`   FOREIGN KEY (`customers_id`)   REFERENCES `customers`         (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_projects_id`    FOREIGN KEY (`projects_id`)    REFERENCES `projects`          (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_inventories_createdby`      FOREIGN KEY (`createdby`)      REFERENCES `users`             (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_column_exists    ('providers', 'meta_id'             , '!ALTER TABLE `providers` ADD COLUMN     `meta_id` INT(11) NULL DEFAULT NULL AFTER `createdby`');
sql_index_exists     ('providers', 'meta_id'             , '!ALTER TABLE `providers` ADD KEY        `meta_id` (`meta_id`)');
sql_foreignkey_exists('providers', 'fk_providers_meta_id', '!ALTER TABLE `providers` ADD CONSTRAINT `fk_providers_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT;');

sql_index_exists ('providers', 'modifiedon', 'ALTER TABLE `providers` DROP KEY    `modifiedon`');
sql_column_exists('providers', 'modifiedon', 'ALTER TABLE `providers` DROP COLUMN `modifiedon`');

sql_column_exists('providers', 'email' , '!ALTER TABLE `providers` ADD COLUMN `email`  VARCHAR(128) AFTER `seoname`');
sql_column_exists('providers', 'phones', '!ALTER TABLE `providers` ADD COLUMN `phones` VARCHAR(36)  AFTER `email`');
sql_column_exists('providers', 'code'  , '!ALTER TABLE `providers` ADD COLUMN `code`   VARCHAR(64)  AFTER `phones`');

sql_query('ALTER TABLE `providers` MODIFY COLUMN `description` VARCHAR(2047) NULL DEFAULT NULL');

sql_column_exists    ('providers', 'categories_id'             , '!ALTER TABLE `providers` ADD COLUMN     `categories_id` INT(11) NULL DEFAULT NULL AFTER `status`');
sql_index_exists     ('providers', 'categories_id'             , '!ALTER TABLE `providers` ADD KEY        `categories_id` (`categories_id`)');
sql_foreignkey_exists('providers', 'fk_providers_categories_id', '!ALTER TABLE `providers` ADD CONSTRAINT `fk_providers_categories_id` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT;');
?>