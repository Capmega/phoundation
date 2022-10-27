<?php
/*
 * Add
 */
sql_query('DROP TABLE IF EXISTS `users_switch`;');

sql_query('CREATE TABLE `users_switch` (`id`        INT(11)     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                        `createdon` TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                        `created_by` INT(11)         NULL,
                                        `status`    VARCHAR(16)     NULL,
                                        `users_id`  INT(11)     NOT NULL,

                                        INDEX (`createdon`),
                                        INDEX (`created_by`),
                                        INDEX (`users_id`),
                                        INDEX (`status`),

                                        CONSTRAINT `fk_users_switch_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                        CONSTRAINT `fk_users_switch_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users` (`id`) ON DELETE RESTRICT

                                        ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');
?>
