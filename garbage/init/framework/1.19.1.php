<?php
/*
 * Fix servers_hostnames table
 */
sql_column_exists('servers_hostnames', 'seohostname', '!ALTER TABLE `servers_hostnames` ADD COLUMN `seohostname` VARCHAR(64) NOT NULL DEFAULT "" AFTER `hostname`');
sql_index_exists ('servers_hostnames', 'seohostname', '!ALTER TABLE `servers_hostnames` ADD KEY    `seohostname` (`seohostname`)');

$server_restrictionss = sql_query('SELECT `id`, `hostname`, `ipv4` FROM `servers`');
$insert  = sql_prepare('INSERT INTO `servers_hostnames` (`meta_id`, `servers_id`, `hostname`, `seohostname`)
                        VALUES                          (:meta_id , :servers_id , :hostname , :seohostname )');

load_libs('seo');
log_console(tr('Updating server IPv4\'s and server hostnames in multi hostnames list'));

sql_query('TRUNCATE `servers_hostnames`');

while ($server_restrictions = sql_fetch($server_restrictionss)) {
    if (!$server_restrictions['ipv4']) {
        $server_restrictions['ipv4'] = gethostbynamel($server_restrictions['hostname']);

        if (!$server_restrictions['ipv4']) {
            $server_restrictions['ipv4'] = null;
            log_console(tr('No IPv4 found for hostname ":hostname"', array(':ip' => $server_restrictions['ipv4'], ':hostname' => $server_restrictions['hostname'])), 'yellow');

        } else {
            if (count($server_restrictions['ipv4']) == 1) {
                $server_restrictions['ipv4'] = array_shift($server_restrictions['ipv4']);
                log_console(tr('Set IPv4 ":ip" for hostname ":hostname"', array(':ip' => $server_restrictions['ipv4'], ':hostname' => $server_restrictions['hostname'])));

            } else {
                log_console(tr('Found multiple IPv4 entries for hostname ":hostname", not automatically updating', array(':hostname' => $server_restrictions['hostname'])), 'yellow');
            }
        }

        sql_query('UPDATE `servers` SET `ipv4` = :ipv4 WHERE `id` = :id', array(':id' => $server_restrictions['id'], ':ipv4' => $server_restrictions['ipv4']));
    }

    $server_restrictionss_id = sql_get('SELECT `servers_id` FROM `servers_hostnames` WHERE `hostname` = :hostname', true, array('hostname' => $server_restrictions['hostname']));

    if ($server_restrictionss_id) {
        /*
         * Hostname is registered, $server_restrictionss_id should match $server_restrictions[id]
         */
        if ($server_restrictionss_id != $server_restrictions['id']) {
            log_console(tr('Failed to register main hostname ":hostname", it was already registered for servers_id ":id"', array(':hostname' => $server_restrictions['hostname'], ':servers_id' => $server_restrictionss_id)), 'yellow');
        }

    } else {
        log_console(tr('Adding hostname ":hostname" to servers hostnames table', array(':hostname' => $server_restrictions['hostname'])));
        $insert->execute(array(':meta_id'     => meta_action(),
                               ':servers_id'  => $server_restrictions['id'],
                               ':hostname'    => $server_restrictions['hostname'],
                               ':seohostname' => seo_unique($server_restrictions['hostname'], 'servers_hostnames', null, 'seohostname')));
    }
}

cli_dot(false);
?>