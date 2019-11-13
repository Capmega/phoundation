<?php
/*
 * Add "applied" support for static rules, where we can see how many times a static rule has been applied
 */
sql_column_exists('routes_static', 'applied', '!ALTER TABLE `routes_static` ADD COLUMN `applied` INT(11) NOT NULL DEFAULT 0 AFTER `status`');
