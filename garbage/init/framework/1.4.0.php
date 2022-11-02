<?php
/*
 * New statistics library
 */
sql_query('DROP TABLE IF EXISTS `statistics`');



sql_query('CREATE TABLE `statistics` (`id`       INT(11)      NOT NULL AUTO_INCREMENT,
                                     `createdon` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `created_by` INT(11)          NULL,
                                     `remote`    VARCHAR(16)      NULL,
                                     `event`     VARCHAR(16)  NOT NULL,
                                     `details`   VARCHAR(255) NOT NULL,

                                     PRIMARY KEY `id`        (`id`),
                                             KEY `createdon` (`createdon`),
                                             KEY `created_by` (`created_by`),
                                             KEY `remote`    (`remote`),
                                             KEY `event`     (`event`),

                                     CONSTRAINT `fk_statistics_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

sql_column_exists('buks', 'createdon', '!ALTER TABLE `buks` ADD COLUMN `createdon` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`');
sql_column_exists('buks', 'created_by', '!ALTER TABLE `buks` ADD COLUMN `created_by` INT(11) NULL AFTER `createdon`');
sql_column_exists('buks', 'name'     , 'ALTER TABLE `buks` CHANGE COLUMN `name` `section` VARCHAR(16) NOT NULL');

sql_index_exists('buks', 'createdon', '!ALTER TABLE `buks` ADD  INDEX `createdon` (`createdon`)');
sql_index_exists('buks', 'created_by', '!ALTER TABLE `buks` ADD  INDEX `created_by` (`created_by`)');
sql_index_exists('buks', 'name'     ,  'ALTER TABLE `buks` DROP INDEX `name`');
sql_index_exists('buks', 'section'  , '!ALTER TABLE `buks` ADD  INDEX `section` (`section`)');
?>