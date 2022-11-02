<?php
/*
 * Add whitelabel tables for whitelabel support
 */
sql_query('DROP TABLE IF EXISTS `whitelabels`;');

sql_query('CREATE TABLE `whitelabels` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                       `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                       `created_by`   INT(11)          NULL,
                                       `modifiedon`  DATETIME         NULL,
                                       `modifiedby`  INT(11)          NULL,
                                       `status`      VARCHAR(16)      NULL,
                                       `users_id`    INT(11)      NOT NULL,
                                       `name`        VARCHAR(64)  NOT NULL,
                                       `seoname`     VARCHAR(64)  NOT NULL,
                                       `domain`      VARCHAR(128) NOT NULL,

                                       INDEX (`createdon`),
                                       INDEX (`created_by`),
                                       INDEX (`modifiedon`),
                                       INDEX (`modifiedby`),
                                       INDEX (`status`),
                                       UNIQUE(`seoname`),
                                       UNIQUE(`domain`),
                                       UNIQUE(`users_id`),

                                       CONSTRAINT `fk_whitelabels_created_by`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_whitelabels_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                       CONSTRAINT `fk_whitelabels_users_id`   FOREIGN KEY (`users_id`)   REFERENCES `users` (`id`) ON DELETE RESTRICT

                                      ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
