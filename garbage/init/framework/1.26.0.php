<?php
/*
 * Add tables for doc generator library
 */
sql_query('DROP TABLE IF EXISTS `doc_generated`');
sql_query('DROP TABLE IF EXISTS `doc_values`');
sql_query('DROP TABLE IF EXISTS `doc_pages`');
sql_query('DROP TABLE IF EXISTS `doc_projects`');



/*
 *
 */
sql_query('CREATE TABLE `doc_projects` (`id`        INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `created_by` INT(11)         NULL DEFAULT NULL,
                                        `meta_id`   INT(11)     NOT NULL,
                                        `status`    VARCHAR(16)     NULL DEFAULT NULL,
                                        `name`      VARCHAR(32) NOT NULL,
                                        `seoname`   VARCHAR(32) NOT NULL,
                                        `language`  VARCHAR(2)  NOT NULL,

                                               KEY `createdon` (`createdon`),
                                               KEY `created_by` (`created_by`),
                                               KEY `meta_id`   (`meta_id`),
                                               KEY `status`    (`status`),
                                               KEY `name`      (`name`),
                                               KEY `language`  (`language`),
                                        UNIQUE KEY `seoname`   (`seoname`),

                                        CONSTRAINT `fk_doc_projects_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_doc_projects_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT

                                       ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 *
 */
sql_query('CREATE TABLE `doc_pages` (`id`          INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                     `createdon`   TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `created_by`   INT(11)         NULL DEFAULT NULL,
                                     `meta_id`     INT(11)     NOT NULL,
                                     `status`      VARCHAR(16)     NULL DEFAULT NULL,
                                     `projects_id` INT(11)         NULL,
                                     `parents_id`  INT(11)         NULL,
                                     `name`        VARCHAR(64) NOT NULL,
                                     `seoname`     VARCHAR(64) NOT NULL,
                                     `package`     VARCHAR(64)     NULL,
                                     `type`        ENUM("function", "class", "library", "chapter", "page", "webpage", "script", "configuration", "init", "api", "ajax") NOT NULL,

                                            KEY `createdon`  (`createdon`),
                                            KEY `created_by`  (`created_by`),
                                            KEY `meta_id`    (`meta_id`),
                                            KEY `status`     (`status`),
                                            KEY `parents_id` (`parents_id`),
                                            KEY `name`       (`name`),
                                            KEY `package`    (`package`),
                                     UNIQUE KEY `seoname`    (`seoname`),

                                     CONSTRAINT `fk_doc_pages_created_by`   FOREIGN KEY (`created_by`)   REFERENCES `users`        (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_doc_pages_meta_id`     FOREIGN KEY (`meta_id`)     REFERENCES `meta`         (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_doc_pages_parents_id`  FOREIGN KEY (`parents_id`)  REFERENCES `doc_pages`    (`id`) ON DELETE CASCADE,
                                     CONSTRAINT `fk_doc_pages_projects_id` FOREIGN KEY (`projects_id`) REFERENCES `doc_projects` (`id`) ON DELETE CASCADE

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 *
 */
sql_query('CREATE TABLE `doc_values` (`id`        INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                      `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `created_by` INT(11)         NULL DEFAULT NULL,
                                      `meta_id`   INT(11)     NOT NULL,
                                      `status`    VARCHAR(16)     NULL DEFAULT NULL,
                                      `pages_id`  INT(11)     NOT NULL,
                                      `key`       ENUM("category", "title", "paragraph", "author", "copyright", "license", "see", "table", "note", "version", "example", "params", "param", "return", "exception") NOT NULL,
                                      `value`     TEXT            NULL DEFAULT NULL,

                                             KEY `createdon` (`createdon`),
                                             KEY `created_by` (`created_by`),
                                             KEY `meta_id`   (`meta_id`),
                                             KEY `status`    (`status`),
                                             KEY `pages_id`  (`pages_id`),
                                             KEY `key`       (`key`),

                                      CONSTRAINT `fk_doc_values_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`     (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_doc_values_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`      (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_doc_values_pages_id`  FOREIGN KEY (`pages_id`)  REFERENCES `doc_pages` (`id`) ON DELETE CASCADE

                                     ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>