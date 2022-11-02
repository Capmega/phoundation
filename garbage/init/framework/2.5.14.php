<?php
/*
 * Add urlsupport to notifications
 * Add priority support to notifications
 * Add data support to notifications
 * Change `description` to `message`
 *
 * Add notifications support tables
 * notifications_groups
 * notifications_members
 * notifications_groups_links
 * notifications_sent
 */
sql_column_exists('notifications', 'created_by', '!ALTER TABLE `notifications` ADD COLUMN `created_by` INT(11) NULL AFTER `id`');
sql_index_exists ('notifications', 'created_by', '!ALTER TABLE `notifications` ADD KEY    `created_by` (`created_by`)');

sql_column_exists('notifications', 'createdon', '!ALTER TABLE `notifications` ADD COLUMN `createdon` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`');
sql_index_exists ('notifications', 'createdon', '!ALTER TABLE `notifications` ADD KEY    `createdon` (`createdon`)');

sql_column_exists('notifications', 'code', '!ALTER TABLE `notifications` ADD COLUMN `code` VARCHAR(16) NULL AFTER `status`');
sql_index_exists ('notifications', 'code', '!ALTER TABLE `notifications` ADD KEY    `code` (`code`)');

sql_column_exists('notifications', 'priority', '!ALTER TABLE `notifications` ADD COLUMN `priority` INT(11) NOT NULL AFTER `code`');
sql_index_exists ('notifications', 'priority', '!ALTER TABLE `notifications` ADD KEY    `priority` (`priority`)');

sql_column_exists('notifications', 'description',  'ALTER TABLE `notifications` CHANGE COLUMN `description` `message` VARCHAR(4090) NOT NULL');
sql_column_exists('notifications', 'data'       , '!ALTER TABLE `notifications` ADD    COLUMN `data`                  VARCHAR(4090) NULL AFTER `message`');

sql_foreignkey_exists('notifications', 'fk_notifications_created_by', '!ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT;');


sql_query('DROP TABLE IF EXISTS `notifications_sent`');
sql_query('DROP TABLE IF EXISTS `notifications_groups_links`');
sql_query('DROP TABLE IF EXISTS `notifications_methods`');
sql_query('DROP TABLE IF EXISTS `notifications_members`');
sql_query('DROP TABLE IF EXISTS `notifications_groups`');



sql_query('CREATE TABLE `notifications_groups` (`id`        INT(11)      NOT NULL AUTO_INCREMENT,
                                                `createdon` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                `created_by` INT(11)          NULL,
                                                `meta_id`   INT(11)      NOT NULL,
                                                `status`    VARCHAR(16)      NULL,
                                                `name`      VARCHAR(32)      NULL,
                                                `seoname`   VARCHAR(32)      NULL,

                                                PRIMARY KEY `id`        (`id`),
                                                        KEY `meta_id`   (`meta_id`),
                                                        KEY `createdon` (`createdon`),
                                                        KEY `created_by` (`created_by`),
                                                        KEY `status`    (`status`),
                                                UNIQUE  KEY `seoname`   (`seoname`),

                                                CONSTRAINT `fk_notifications_groups_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                                CONSTRAINT `fk_notifications_groups_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                              ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `notifications_members` (`id`        INT(11)     NOT NULL AUTO_INCREMENT,
                                                 `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `created_by` INT(11)         NULL,
                                                 `meta_id`   INT(11)     NOT NULL,
                                                 `status`    VARCHAR(16)     NULL,
                                                 `users_id`  INT(11)         NULL,
                                                 `groups_id` INT(11)         NULL,

                                                 PRIMARY KEY `id`                 (`id`),
                                                         KEY `meta_id`            (`meta_id`),
                                                         KEY `createdon`          (`createdon`),
                                                         KEY `created_by`          (`created_by`),
                                                         KEY `status`             (`status`),
                                                         KEY `users_id`           (`users_id`),
                                                         KEY `groups_id`          (`groups_id`),
                                                 UNIQUE  KEY `groups_id_users_id` (`groups_id`, `users_id`),

                                                 CONSTRAINT `fk_notifications_members_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`                 (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_notifications_members_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`                (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_notifications_members_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users`                (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_notifications_members_groups_id` FOREIGN KEY (`groups_id`) REFERENCES `notifications_groups` (`id`) ON DELETE RESTRICT

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `notifications_methods` (`id`            INT(11)     NOT NULL AUTO_INCREMENT,
                                                 `createdon`     TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `created_by`     INT(11)         NULL,
                                                 `meta_id`       INT(11)     NOT NULL,
                                                 `status`        VARCHAR(16)     NULL,
                                                 `members_id`    INT(11)         NULL,
                                                 `from_priority` INT(11)         NULL,
                                                 `method`        ENUM("email", "sms", "desktop", "push", "pushover", "prowl", "matrix", "whatsapp", "signal", "slack", "telegram", "twitter", "api") NOT NULL,

                                                 PRIMARY KEY `id`                 (`id`),
                                                         KEY `meta_id`            (`meta_id`),
                                                         KEY `createdon`          (`createdon`),
                                                         KEY `created_by`          (`created_by`),
                                                         KEY `status`             (`status`),
                                                         KEY `members_id`         (`members_id`),
                                                         KEY `from_priority`      (`from_priority`),
                                                         KEY `method`             (`method`),

                                                 CONSTRAINT `fk_notifications_methods_meta_id`    FOREIGN KEY (`meta_id`)    REFERENCES `meta`                  (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_notifications_methods_created_by`  FOREIGN KEY (`created_by`)  REFERENCES `users`                 (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_notifications_methods_members_id` FOREIGN KEY (`members_id`) REFERENCES `notifications_members` (`id`) ON DELETE RESTRICT

                                               ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `notifications_groups_links` (`id`               INT(11)     NOT NULL AUTO_INCREMENT,
                                                      `createdon`        TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                      `created_by`        INT(11)         NULL,
                                                      `meta_id`          INT(11)     NOT NULL,
                                                      `status`           VARCHAR(16)     NULL,
                                                      `notifications_id` INT(11)         NULL,
                                                      `groups_id`        INT(11)         NULL,

                                                       PRIMARY KEY `id`                         (`id`),
                                                               KEY `meta_id`                    (`meta_id`),
                                                               KEY `createdon`                  (`createdon`),
                                                               KEY `created_by`                  (`created_by`),
                                                               KEY `status`                     (`status`),
                                                               KEY `notifications_id`           (`notifications_id`),
                                                               KEY `groups_id`                  (`groups_id`),
                                                       UNIQUE  KEY `groups_id_notifications_id` (`groups_id`, `notifications_id`),

                                                      CONSTRAINT `fk_notifications_groups_links_meta_id`          FOREIGN KEY (`meta_id`)          REFERENCES `meta`                 (`id`) ON DELETE RESTRICT,
                                                      CONSTRAINT `fk_notifications_groups_links_created_by`        FOREIGN KEY (`created_by`)        REFERENCES `users`                (`id`) ON DELETE RESTRICT,
                                                      CONSTRAINT `fk_notifications_groups_links_notifications_id` FOREIGN KEY (`notifications_id`) REFERENCES `notifications`        (`id`) ON DELETE RESTRICT,
                                                      CONSTRAINT `fk_notifications_groups_links_groups_id`        FOREIGN KEY (`groups_id`)        REFERENCES `notifications_groups` (`id`) ON DELETE RESTRICT

                                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `notifications_sent` (`id`               INT(11)   NOT NULL AUTO_INCREMENT,
                                              `createdon`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                              `created_by`        INT(11)       NULL,
                                              `notifications_id` INT(11)       NULL,

                                               PRIMARY KEY `id`               (`id`),
                                                       KEY `createdon`        (`createdon`),
                                                       KEY `created_by`        (`created_by`),
                                                       KEY `notifications_id` (`notifications_id`),

                                              CONSTRAINT `fk_notifications_sent_created_by`        FOREIGN KEY (`created_by`)         REFERENCES `users`                (`id`) ON DELETE RESTRICT,
                                              CONSTRAINT `fk_notifications_sent_notifications_id` FOREIGN KEY (`notifications_id`)  REFERENCES `notifications`        (`id`) ON DELETE RESTRICT

                                            ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
