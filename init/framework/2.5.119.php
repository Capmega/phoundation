<?php
/*
 * Fix missing seoname column in database_accounts table
 */
if(sql_table_exists('database_accounts')){
    sql_column_exists('database_accounts', 'seoname', '!ALTER TABLE `database_accounts` ADD COLUMN     `seoname` VARCHAR(32) NULL AFTER `name`');
    sql_index_exists ('database_accounts', 'seoname', '!ALTER TABLE `database_accounts` ADD UNIQUE KEY `seoname` (`seoname`)');

}else{
    /*
     * Some projects have this table missing, for some reason
     */
    sql_query('CREATE TABLE `database_accounts` (`id`            INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                                 `createdon`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                                 `createdby`     INT(11)       NOT NULL,
                                                 `meta_id`       INT(11)           NULL DEFAULT NULL,
                                                 `status`        VARCHAR(16)       NULL DEFAULT NULL,
                                                 `name`          VARCHAR(32)   NOT NULL,
                                                 `seoname`       VARCHAR(32)   NOT NULL,
                                                 `username`      VARCHAR(32)   NOT NULL,
                                                 `password`      VARCHAR(64)   NOT NULL,
                                                 `root_password` VARCHAR(64)   NOT NULL,
                                                 `description`   VARCHAR(2047) NOT NULL,

                                                 INDEX  `createdon` (`createdon`),
                                                 INDEX  `createdby` (`createdby`),
                                                 INDEX  `meta_id`   (`meta_id`),
                                                 INDEX  `status`    (`status`),
                                                 UNIQUE `name`      (`name`),
                                                 UNIQUE `seoname`   (`seoname`),

                                                 CONSTRAINT `fk_database_accounts_createdby` FOREIGN KEY (`createdby`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                                 CONSTRAINT `fk_database_accounts_meta_id`   FOREIGN KEY (`meta_id`)   REFERENCES `meta`  (`id`) ON DELETE RESTRICT

                                                ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');}
?>
