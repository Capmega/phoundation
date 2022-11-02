<?php
/*
 * Add support for special IP rules
 */
sql_query('DROP TABLE IF EXISTS `routes_static`');



sql_query('CREATE TABLE `routes_static` (`id`        INT(11)      NOT NULL AUTO_INCREMENT,
                                         `createdon` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                         `expiredon` DATETIME         NULL,
                                         `meta_id`   INT(11)      NOT NULL,
                                         `status`    VARCHAR(16)      NULL,
                                         `ip`        VARCHAR(64)  NOT NULL,
                                         `uri`       VARCHAR(255) NOT NULL,
                                         `regex`     VARCHAR(255) NOT NULL,
                                         `target`    VARCHAR(255) NOT NULL,
                                         `flags`     VARCHAR(16)  NOT NULL,

                                         PRIMARY KEY `id`        (`id`),
                                                 KEY `expiredon` (`expiredon`),
                                                 KEY `meta_id`   (`meta_id`),
                                                 KEY `status`    (`status`),
                                                 KEY `ip`        (`ip`),

                                         CONSTRAINT `fk_routes_static_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE RESTRICT

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
