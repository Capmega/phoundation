<?php
/*
 * Add the "no server" option to the servers table
 */
load_libs('servers');

try{
    $exists = servers_get('');

}catch(Exception $e) {
        sql_query('INSERT INTO `servers` (`domain`, `seodomain`)
                   VALUES                (`domain`, `seodomain`)');
}
?>