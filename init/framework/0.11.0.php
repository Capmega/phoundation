<?php
/*
 * Add contactus table
 */
sql_query('DROP TABLE IF EXISTS `contactus`');



sql_query('CREATE TABLE `contactus` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                     `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `created_by`   INT(11)          NULL,
                                     `status`      VARCHAR(16)      NULL,
                                     `name`        VARCHAR(64)      NULL,
                                     `email`       VARCHAR(255)     NULL,
                                     `message`     TEXT             NULL,

                                     INDEX (`createdon`),
                                     INDEX (`created_by`),
                                     INDEX (`status`),
                                     INDEX (`name`),
                                     INDEX (`email`),

                                     CONSTRAINT `fk_contactus_created_by`  FOREIGN KEY (`created_by`)  REFERENCES `users`  (`id`) ON DELETE CASCADE

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Add multilingual support to blogs table
 */
sql_column_exists('blogs_posts', 'language', '!ALTER TABLE `blogs_posts` ADD COLUMN `language` VARCHAR(2) NULL AFTER `comments`');
sql_index_exists ('blogs_posts', 'language', '!ALTER TABLE `blogs_posts` ADD INDEX(`language`)');



/*
 * Fix "views" column
 */
sql_query('ALTER TABLE `blogs_posts` CHANGE COLUMN `views` `views` INT(11) NOT NULL');
?>
