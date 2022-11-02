<?php
/*
 * Fix notifications_methods
 * Add support for notification priorities
 */
sql_query('ALTER TABLE `notifications_methods` MODIFY `method` ENUM("email", "sms", "desktop", "hangouts", "irc", "jabber", "push", "pushover", "prowl", "matrix", "whatsapp", "signal", "skype", "slack", "telegram", "twitter", "api") NOT NULL');

sql_column_exists('notifications', 'priority', '!ALTER TABLE `notifications` ADD COLUMN `priority` INT(11) NOT NULL AFTER `code`');
sql_index_exists ('notifications', 'priority', '!ALTER TABLE `notifications` ADD KEY    `priority` (`priority`)');

/*
 * Add required tables for calendars library
 */
sql_query('DROP TABLE IF EXISTS `calendars_events_notifications`');
sql_query('DROP TABLE IF EXISTS `calendars_events_participants`');
sql_query('DROP TABLE IF EXISTS `calendars_events`');
sql_query('DROP TABLE IF EXISTS `calendars`');



/*
 * Create calendars table
 */
sql_query('CREATE TABLE `calendars` (`id`          INT(11)       NOT NULL AUTO_INCREMENT,
                                     `createdon`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `created_by`   INT(11)           NULL,
                                     `meta_id`     INT(11)       NOT NULL,
                                     `status`      VARCHAR(16)       NULL,
                                     `name`        VARCHAR(32)       NULL,
                                     `seoname`     VARCHAR(32)       NULL,
                                     `description` VARCHAR(4090)     NULL,

                                     PRIMARY KEY `id`        (`id`),
                                             KEY `meta_id`   (`meta_id`),
                                             KEY `createdon` (`createdon`),
                                             KEY `created_by` (`created_by`),
                                             KEY `status`    (`status`),

                                     CONSTRAINT `fk_calendars_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`       (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_calendars_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`      (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Create events table
 */
sql_query('CREATE TABLE `calendars_events` (`id`              INT(11)       NOT NULL AUTO_INCREMENT,
                                            `createdon`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                            `created_by`       INT(11)           NULL,
                                            `meta_id`         INT(11)       NOT NULL,
                                            `status`          VARCHAR(16)       NULL,
                                            `calendars_id`    INT(11)           NULL,
                                            `documents_id`    INT(11)           NULL,
                                            `projects_id`     INT(11)           NULL,
                                            `sub_projects_id` INT(11)           NULL,
                                            `name`            VARCHAR(32)   NOT NULL,
                                            `seoname`         VARCHAR(32)   NOT NULL,
                                            `description`     VARCHAR(4090)     NULL,

                                            PRIMARY KEY `id`              (`id`),
                                                    KEY `meta_id`         (`meta_id`),
                                                    KEY `createdon`       (`createdon`),
                                                    KEY `created_by`       (`created_by`),
                                                    KEY `status`          (`status`),
                                                    KEY `calendars_id`    (`calendars_id`),
                                                    KEY `documents_id`    (`documents_id`),
                                                    KEY `projects_id`     (`projects_id`),
                                                    KEY `sub_projects_id` (`sub_projects_id`),

                                            CONSTRAINT `fk_calendars_events_meta_id`         FOREIGN KEY (`meta_id`)         REFERENCES `meta`              (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_calendars_events_created_by`       FOREIGN KEY (`created_by`)       REFERENCES `users`             (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_calendars_events_calendars_id`    FOREIGN KEY (`calendars_id`)    REFERENCES `calendars`         (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_calendars_events_documents_id`    FOREIGN KEY (`documents_id`)    REFERENCES `storage_documents` (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_calendars_events_projects_id`     FOREIGN KEY (`projects_id`)     REFERENCES `projects`          (`id`) ON DELETE RESTRICT,
                                            CONSTRAINT `fk_calendars_events_sub_projects_id` FOREIGN KEY (`sub_projects_id`) REFERENCES `projects`          (`id`) ON DELETE RESTRICT

                                           ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Create events participants table
 */
sql_query('CREATE TABLE `calendars_events_participants` (`id`              INT(11)       NOT NULL AUTO_INCREMENT,
                                                         `createdon`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                         `created_by`       INT(11)           NULL,
                                                         `meta_id`         INT(11)       NOT NULL,
                                                         `status`          VARCHAR(16)       NULL,
                                                         `events_id`       INT(11)       NOT NULL,
                                                         `calendars_id`    INT(11)           NULL,
                                                         `participants_id` INT(11)           NULL,
                                                         `email`           VARCHAR(128)      NULL,
                                                         `description`     VARCHAR(2040)     NULL,

                                                         PRIMARY KEY `id`              (`id`),
                                                                 KEY `meta_id`         (`meta_id`),
                                                                 KEY `createdon`       (`createdon`),
                                                                 KEY `created_by`       (`created_by`),
                                                                 KEY `status`          (`status`),
                                                                 KEY `events_id`       (`events_id`),
                                                                 KEY `participants_id` (`participants_id`),
                                                                 KEY `calendars_id`    (`calendars_id`),

                                                         CONSTRAINT `fk_calendars_events_participants_meta_id`         FOREIGN KEY (`meta_id`)         REFERENCES `meta`             (`id`) ON DELETE RESTRICT,
                                                         CONSTRAINT `fk_calendars_events_participants_created_by`       FOREIGN KEY (`created_by`)       REFERENCES `users`            (`id`) ON DELETE RESTRICT,
                                                         CONSTRAINT `fk_calendars_events_participants_events_id`       FOREIGN KEY (`events_id`)       REFERENCES `calendars_events` (`id`) ON DELETE RESTRICT,
                                                         CONSTRAINT `fk_calendars_events_participants_calendars_id`    FOREIGN KEY (`calendars_id`)    REFERENCES `calendars`        (`id`) ON DELETE RESTRICT,
                                                         CONSTRAINT `fk_calendars_events_participants_participants_id` FOREIGN KEY (`participants_id`) REFERENCES `users`            (`id`) ON DELETE RESTRICT

                                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Create events table
 */
sql_query('CREATE TABLE `calendars_events_notifications` (`id`        INT(11)       NOT NULL AUTO_INCREMENT,
                                                          `createdon` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                          `created_by` INT(11)           NULL,
                                                          `meta_id`   INT(11)       NOT NULL,
                                                          `status`    VARCHAR(16)       NULL,
                                                          `events_id` INT(11)       NOT NULL,
                                                          `method`    ENUM("email", "sms", "desktop", "hangouts", "irc", "jabber", "push", "pushover", "prowl", "matrix", "whatsapp", "signal", "skype", "slack", "telegram", "twitter", "api") NOT NULL,
                                                          `before`    INT(11)       NOT NULL,

                                                          PRIMARY KEY `id`        (`id`),
                                                                  KEY `meta_id`   (`meta_id`),
                                                                  KEY `createdon` (`createdon`),
                                                                  KEY `created_by` (`created_by`),
                                                                  KEY `status`    (`status`),
                                                                  KEY `events_id` (`events_id`),
                                                                  KEY `method`    (`method`),
                                                                  KEY `before`    (`before`),

                                                          CONSTRAINT `fk_calendars_events_notifications_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`             (`id`) ON DELETE RESTRICT,
                                                          CONSTRAINT `fk_calendars_events_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`            (`id`) ON DELETE RESTRICT,
                                                          CONSTRAINT `fk_calendars_events_notifications_events_id` FOREIGN KEY (`events_id`) REFERENCES `calendars_events` (`id`) ON DELETE RESTRICT

                                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
