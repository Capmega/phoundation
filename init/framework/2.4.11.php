<?php
/*
 * Add the "no server" option to the servers table
 */
load_libs('servers');

$exists = servers_get('');

if(!$exists){
    sql_query('INSERT INTO `servers` (`domain`, `seodomain`)
               VALUES                (`domain`, `seodomain`)');
}
?>