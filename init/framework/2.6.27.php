<?php
/*
 * Fix employee status
 * Fix missing users table status column index
 * Fix double reference users.employees_id < > employees.users_id by removing users.employees_id, use only employees.users_id
 */
sql_query('UPDATE    `employees`

           LEFT JOIN `users`
           ON        `employees`.`users_id` = `users`.`id`

           SET       `employees`.`status`   = `users`.`status`');

sql_index_exists('users', 'status', '!ALTER TABLE `users` ADD KEY `status` (`status`)');

sql_foreignkey_exists('users', 'fk_users_employees_id', 'ALTER TABLE `users` DROP FOREIGN KEY `fk_users_employees_id`');
sql_index_exists     ('users', 'employees_id'         , 'ALTER TABLE `users` DROP INDEX `employees_id`');
sql_column_exists    ('users', 'employees_id'         , 'ALTER TABLE `users` DROP COLUMN `employees_id`');
?>
