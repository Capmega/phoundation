<?php
/*
 * Add redirect library tables
 */
sql_query('DROP TABLE IF EXISTS `redirects`');

sql_query('CREATE TABLE `redirects` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                     `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `created_by`     INT(11)       NOT NULL,
                                     `code`          VARCHAR(16)       NULL,
                                     `url`           VARCHAR(255)      NULL,

                                     INDEX (`createdon`),
                                     INDEX (`created_by`),
                                     INDEX (`code`)

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');

/*
 * Fix missing foreign key
 */
sql_foreignkey_exists('timers', 'fk_timers_created_by' , '!ALTER TABLE `timers` ADD CONSTRAINT `fk_timers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;');

?>
