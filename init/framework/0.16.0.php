<?php
/*
 * Add more security tables
 */
sql_query('DROP TABLE IF EXISTS `passwords`');
sql_query('DROP TABLE IF EXISTS `ip_locks`');



sql_query('CREATE TABLE `passwords` (`id`          INT(11)      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                     `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                     `created_by`   INT(11)      NOT NULL,
                                     `users_id`    INT(11)      NOT NULL,
                                     `password`    VARCHAR(64)      NULL,

                                     INDEX (`createdon`),
                                     INDEX (`created_by`),
                                     INDEX (`users_id`),

                                     CONSTRAINT `fk_passwords_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
                                     CONSTRAINT `fk_passwords_users_id`  FOREIGN KEY (`users_id`)  REFERENCES `users` (`id`) ON DELETE CASCADE

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



sql_query('CREATE TABLE `ip_locks` (`id`          INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                                    `createdon`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    `created_by`   INT(11)      NOT NULL,
                                    `ip`          VARCHAR(15)      NULL,

                                     INDEX (`createdon`),
                                     INDEX (`created_by`),
                                     INDEX (`ip`),

                                     CONSTRAINT `fk_iplocks_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT

                                    ) ENGINE=InnoDB AUTO_INCREMENT='.$_CONFIG['db']['core']['autoincrement'].' DEFAULT CHARSET="'.$_CONFIG['db']['core']['charset'].'" COLLATE="'.$_CONFIG['db']['core']['collate'].'";');



/*
 * Fill the passwords history table
 */
$r = sql_query  ('SELECT `id`, `password` FROM `users` WHERE `status`');

$p = sql_prepare('INSERT INTO `passwords` (`created_by`, `users_id`, `password`)
                  VALUES                  (:created_by , :users_id , :password )');

while ($password = sql_fetch($r)) {
    $p->execute(array(':created_by' => $_SESSION['user']['id'],
                      ':users_id'  => $password['id'],
                      ':password'  => $password['password']));
}



/*
 * Add extra standard rights
 */
$p  = sql_prepare('INSERT INTO `rights` (`name`, `description`)
                   VALUES               (:name , :description)

                   ON DUPLICATE KEY UPDATE `name`        = :name,
                                           `description` = :description;');

$rights = array('ip_lock' => 'This right will lock the only allowed signin IP to the IP of this user (only if configured so)');

foreach ($rights as $name => $description) {
    $p->execute(array(':name'        => $name,
                      ':description' => $description));
}
?>
