<?php
/*
 * Company branches and departments are not obligatory for employees by design
 */
sql_query('ALTER TABLE `employees` MODIFY COLUMN `branches_id`    INT(11) NULL');
sql_query('ALTER TABLE `employees` MODIFY COLUMN `departments_id` INT(11) NULL');
