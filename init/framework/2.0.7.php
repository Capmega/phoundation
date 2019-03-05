<?php
sql_table_exists('radius_devices', 'DROP TABLE `radius_devices`');

sql_query('CREATE TABLE `radius_devices` (`id`           INT(11)                                        NOT NULL AUTO_INCREMENT,
                                          `createdon`    TIMESTAMP                                      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                          `createdby`    INT(11)                                            NULL,
                                          `users_id`     INT(11)                                        NOT NULL,
                                          `meta_id`      INT(11)                                            NULL,
                                          `status`       VARCHAR(16)                                        NULL,
                                          `type`         ENUM("phone", "projector", "tablet", "laptop") NOT NULL,
                                          `brand`        VARCHAR(32)                                        NULL,
                                          `model`        VARCHAR(32)                                        NULL,
                                          `mac_address`  VARCHAR(17)                                    NOT NULL,
                                          `description`  VARCHAR(255)                                       NULL,

                                          PRIMARY KEY `id`          (`id`),
                                                  KEY `mac_address` (`createdon`),

                                          CONSTRAINT `fk_radius_devices_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT,
                                          CONSTRAINT `fk_radius_devices_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,                                          CONSTRAINT `fk_radius_devices_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users` (`id`) ON DELETE RESTRICT

                                         ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

?>
