<?php
/*
 * Add counts table support to cache large table count query results
 */
sql_query('DROP TABLE IF EXISTS `counts`;');

sql_query('CREATE TABLE `counts` (`id`         INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                  `createdon`  TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                  `created_by`  INT(11)         NULL,
                                  `modifiedon` DATETIME        NULL,
                                  `modifiedby` INT(11)         NULL,
                                  `until`      DATETIME        NULL,
                                  `hash`       VARCHAR(64)     NULL,
                                  `count`      INT(11)     NOT NULL,

                                  INDEX (`createdon`),
                                  INDEX (`created_by`),
                                  INDEX (`modifiedon`),
                                  INDEX (`modifiedby`),
                                  INDEX (`until`),
                                  UNIQUE(`hash`),

                                  CONSTRAINT `fk_counts_created_by`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                  CONSTRAINT `fk_counts_modifiedby` FOREIGN KEY (`modifiedby`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                 ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
