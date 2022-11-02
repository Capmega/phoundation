<?php
/*
 * Add timer library tables
 */
sql_query('DROP TABLE IF EXISTS `timers`');

sql_query('CREATE TABLE `timers` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                  `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `created_by`     INT(11)       NOT NULL,
                                  `start`         DATETIME          NULL,
                                  `stop`          DATETIME          NULL,
                                  `time`          INT(11)           NULL,
                                  `process`       VARCHAR(32)       NULL,

                                  INDEX (`createdon`),
                                  INDEX (`created_by`),
                                  INDEX (`start`),
                                  INDEX (`time`),
                                  INDEX (`process`),

                                  CONSTRAINT `fk_timers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE

                                 ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
