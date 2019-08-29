<?php
/*
 * Add support for reverse domains taken from the IP's for the static routes to have a better idea where these calls came from
 */
sql_column_exists('routes_static', 'reverse', '!ALTER TABLE `routes_static` ADD COLUMN `reverse` VARCHAR(255) NULL');
