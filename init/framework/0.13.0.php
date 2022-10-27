<?php
/*
 * Add pages table
 */
sql_query('DROP TABLE IF EXISTS `pages`');

sql_query('CREATE TABLE `pages` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                 `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 `created_by`   INT(11)          NULL,
                                 `modifiedon`  DATETIME         NULL,
                                 `modifiedby`  INT(11)          NULL,
                                 `status`      VARCHAR(16)      NULL,
                                 `name`        VARCHAR(255)     NULL,
                                 `seoname`     VARCHAR(255)     NULL,
                                 `language`    VARCHAR(2)   NOT NULL,
                                 `data`        TEXT         NOT NULL,

                                 INDEX (`createdon`),
                                 INDEX (`created_by`),
                                 INDEX (`modifiedon`),
                                 INDEX (`modifiedby`),
                                 INDEX (`status`),
                                 INDEX (`language`),
                                 INDEX (`seoname`),

                                 CONSTRAINT `fk_pages_created_by`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                 CONSTRAINT `fk_pages_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
