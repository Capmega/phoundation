<?php
/*
 * Add support for radius library
 * Add radius_devices table which is a front-end to the `devices` table for the freeradius system
 * Add radius_nas table which is a front-end to the `nas` table for the freeradius system
 *
 * @example INSERT INTO nas (nasname, shortname, type, secret) VALUES ('127.0.0.1', 'test-3', 'other', 'admin');
 */
sql_table_exists('radius_nas'    , 'DROP TABLE `radius_nas`');
sql_table_exists('radius_devices', 'DROP TABLE `radius_devices`');

sql_query('CREATE TABLE `radius_devices` (`id`           INT(11)                                        NOT NULL AUTO_INCREMENT,
                                          `createdon`    TIMESTAMP                                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `created_by`    INT(11)                                            NULL,
                                          `users_id`     INT(11)                                        NOT NULL,
                                          `meta_id`      INT(11)                                            NULL,
                                          `status`       VARCHAR(16)                                        NULL,
                                          `type`         ENUM("phone", "projector", "tablet", "laptop") NOT NULL,
                                          `brand`        VARCHAR(32)                                        NULL,
                                          `model`        VARCHAR(32)                                        NULL,
                                          `mac_address`  VARCHAR(17)                                    NOT NULL,
                                          `description`  VARCHAR(255)                                       NULL,

                                          PRIMARY KEY `id`          (`id`),
                                                  KEY `createdon`   (`createdon`),
                                                  KEY `created_by`   (`created_by`),
                                                  KEY `status`      (`status`),
                                                  KEY `meta_id`     (`meta_id`),
                                                  KEY `users_id`    (`users_id`),
                                          UNIQUE  KEY `mac_address` (`mac_address`),
                                                  KEY `type`        (`type`),
                                                  KEY `brand`       (`brand`),
                                                  KEY `model`       (`model`),

                                          CONSTRAINT `fk_radius_devices_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_radius_devices_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_radius_devices_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users` (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `radius_nas` (`id`           INT(11)                        NOT NULL AUTO_INCREMENT,
                                      `createdon`    TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `created_by`    INT(11)                            NULL,
                                      `meta_id`      INT(11)                            NULL,
                                      `status`       VARCHAR(16)                        NULL,
                                      `type`         ENUM("access point", "others") NOT NULL,
                                      `brand`        VARCHAR(32)                        NULL,
                                      `model`        VARCHAR(32)                        NULL,
                                      `mac_address`  VARCHAR(17)                    NOT NULL,
                                      `ip`           VARCHAR(17)                    NOT NULL,
                                      `name`         VARCHAR(17)                    NOT NULL,
                                      `password`     VARCHAR(17)                    NOT NULL,
                                      `description`  VARCHAR(255)                       NULL,

                                      PRIMARY KEY `id`          (`id`),
                                              KEY `createdon`   (`createdon`),
                                              KEY `created_by`   (`created_by`),
                                              KEY `status`      (`status`),
                                              KEY `meta_id`     (`meta_id`),
                                              KEY `type`        (`type`),
                                      UNIQUE  KEY `mac_address` (`mac_address`),
                                              KEY `brand`       (`brand`),
                                              KEY `model`       (`model`),
                                              KEY `ip`          (`ip`),

                                      CONSTRAINT `fk_radius_nas_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                      CONSTRAINT `fk_radius_nas_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                     ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
