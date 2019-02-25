<?php
/*
 * Fix devices table issues
 *
 * Add `url_cloaks` table for the url library
 */
sql_index_exists('devices', 'default_type',  'ALTER TABLE `devices` DROP INDEX `default_type`');
sql_index_exists('devices', 'default_type', '!ALTER TABLE `devices` ADD INDEX `default_type` (`type`, `default`)');

sql_query('ALTER TABLE `devices` MODIFY COLUMN `type` ENUM("fingerprint-reader", "document-scanner") NULL DEFAULT NULL');

sql_query('DROP TABLE IF EXISTS `url_cloaks`');

sql_query('CREATE TABLE `url_cloaks` (`id`        INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                      `createdon` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                      `createdby` INT(11)          NULL DEFAULT NULL,
                                      `url`       VARCHAR(140) NOT NULL,
                                      `cloak`     VARCHAR(32)  NOT NULL,

                                           KEY `createdon`     (`createdon`),
                                           KEY `createdby`     (`createdby`),
                                    UNIQUE KEY `url_createdby` (`url`, `createdby`),
                                    UNIQUE KEY `cloak`         (`cloak`),

                                    CONSTRAINT `fk_url_cloaks_createdby` FOREIGN KEY (`createdby`)  REFERENCES `users` (`id`) ON DELETE RESTRICT

                                   ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>