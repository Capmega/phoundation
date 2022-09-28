<?php
/*
 * MySQL library
 *
 * This library contains various functions to manage mysql databases and servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @copyright Ismael Haro <support@capmega.com>
 *
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @return void
 */
function mysqlr_library_init(){
    global $_CONFIG;

    try{
        load_config('mysqlr');
        load_libs('linux,cli,rsync');

    }catch(Exception $e){
        throw new CoreException('mysqlr_library_init(): Failed', $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_update_server_replication_status($params, $status){
    try{
        /*
         * Update server replication_status
         */
        array_ensure($params);
        array_default($params, 'servers_id' , '');

        if(empty($params['servers_id'])){
            throw new CoreException(tr('mysqlr_update_server_replication_status(): No servers_id specified'), 'not-specified');
        }

        if(empty($status)){
            throw new CoreException(tr('mysqlr_update_server_replication_status(): No status specified'), 'not-specified');
        }

        /*
         * Update server replication_lock
         */
        switch($status){
            case 'preparing':
                sql_query('UPDATE `servers` SET `replication_lock` = :replication_lock WHERE `id` = :id', array(':replication_lock' => 1, ':id' => $params['servers_id']));
                break;

            case 'error':
                // FALLTHROUGH
            case 'disabled_lock':
                // FALLTHROUGH
            case 'enabled':
                sql_query('UPDATE `servers` SET `replication_lock`   = :replication_lock   WHERE `id` = :id', array(':replication_lock'   => 0      , ':id' => $params['servers_id']));
                sql_query('UPDATE `servers` SET `replication_status` = :replication_status WHERE `id` = :id', array(':replication_status' => $status, ':id' => $params['servers_id']));
                break;

            case 'disabled':
                /*
                 * No action
                 */
                sql_query('UPDATE `servers` SET `replication_lock`   = :replication_lock   WHERE `id` = :id', array(':replication_lock'   => 0      , ':id' => $params['servers_id']));
                sql_query('UPDATE `servers` SET `replication_status` = :replication_status WHERE `id` = :id', array(':replication_status' => $status, ':id' => $params['servers_id']));
                break;

            default:
                throw new CoreException(tr('Unknown status ":status"', array(':status' => $status)), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_update_server_replication_status(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_update_replication_status($params, $status){
    try{
        /*
         * Update server and database replication_status
         */
        array_ensure($params);
        array_default($params, 'databases_id', '');
        array_default($params, 'servers_id' , '');

        if(empty($params['databases_id'])){
            throw new CoreException(tr('mysqlr_update_replication_status(): No database specified'), 'not-specified');
        }

        if(empty($params['servers_id'])){
            throw new CoreException(tr('mysqlr_update_replication_status(): No servers_id specified'), 'not-specified');
        }

        if(empty($status)){
            throw new CoreException(tr('mysqlr_update_replication_status(): No status specified'), 'not-specified');
        }

        /*
         * Update server replication_lock
         */
        switch($status){
            case 'disabling':
                // FALLTHROUGH
            case 'resuming':
                // FALLTHROUGH
            case 'pausing':
                // FALLTHROUGH
            case 'preparing':
                sql_query('UPDATE `servers` SET `replication_lock` = :replication_lock WHERE `id` = :id', array(':replication_lock' => 1, ':id' => $params['servers_id']));
                break;

            case 'paused':
                // FALLTHROUGH
            case 'disabled':
                // FALLTHROUGH
            case 'error':
                // FALLTHROUGH
            case 'enabled':
                sql_query('UPDATE `servers` SET `replication_lock` = :replication_lock WHERE `id` = :id', array(':replication_lock' => 0, ':id' => $params['servers_id']));
                break;

            default:
                throw new CoreException(tr('Unknown status ":status"', array(':status' => $status)));
        }

        /*
         * Update database
         */
        sql_query('UPDATE `databases` SET `replication_status` = :replication_status WHERE `id` = :id', array(':replication_status' => $status, ':id' => $params['databases_id']));

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_update_replication_status(): Failed'), $e);
    }
}



/*
 * This function can setup a master
 * 1) MODIFY MASTER MYSQL CONFIG FILE
 * 2) CREATE REPLICATION USER ON MASTER MYSQL
 * 3) DUMP MYSQL DB
 * 4) ON OTHER SHELL GET MYSQL LOG_FILE AND LOG_POS
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_master_replication_setup($params){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Validate params
         */
        array_ensure($params, 'domain,database');

        /*
         * Check Slave domain
         */
        $slave = $_CONFIG['mysqlr']['domain'];

        if(empty($slave)){
            throw new CoreException(tr('mysqlr_master_replication_setup(): MySQL configuration for replicator domain is not set'), 'not-specified');
        }

        /*
         * Get database
         */
        $database = mysql_get_database($params['database']);
        $database = array_merge($database, $params);
        mysqlr_update_replication_status($database, 'preparing');

        /*
         * Get MySQL configuration path
         */
        $mysql_cnf_path = mysqlr_check_configuration_path($database['domain']);

        /*
         * MySQL SETUP
         */
        log_console(tr('Making master setup for MySQL configuration file'), 'VERBOSEDOT');

        file_sed(array('domain' => $database['domain'],
                       'regex'  => 's/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$database['id'].'/',
                       'source' => $mysql_cnf_path));

        file_sed(array('domain' => $database['domain'],
                       'regex'  => 's/#log_bin/log_bin/',
                       'source' => $mysql_cnf_path));

        /*
         * The next line just have to be added one time!
         * Check if it exists, if not append
         */
        safe_exec(array('domain'   => $database['domain'],
                        'commands' => array('grep', array('-q', '-F', 'binlog_do_db="'.$database['database_name'].'"', $mysql_cnf_path, 'connector' => '||'),
                                            'sed' , array('sudo' => true, '-i', '"/max_binlog_size[[:space:]]*=[[:space:]]*100M/a binlog_do_db = '.$database['database_name'].'"', $mysql_cnf_path))));

        log_console(tr('Restarting remote MySQL service'), 'VERBOSEDOT');
        linux_service($database['domain'], 'mysql', 'restart');

        /*
         * LOCK MySQL database
         * sleep infinity and run in background
         * kill ssh pid after dumping db
         */
        log_console(tr('Making grant replication on remote server and locking tables'), 'VERBOSEDOT');
// :FIX: There is an issue with mysql exec not executing as root
        //$ssh_mysql_pid = mysql_exec($database['domain'], 'GRANT REPLICATION SLAVE ON *.* TO "'.$database['replication_db_user'].'"@"localhost" IDENTIFIED BY "'.$database['replication_db_password'].'"; FLUSH PRIVILEGES; USE '.$database['database'].'; FLUSH TABLES WITH READ LOCK; DO SLEEP(1000000);', true);
        $ssh_mysql_pid = servers_exec($database['domain'], array('commands' => array('mysql', array('-u'.$database['root_db_user'], '-p'.$database['root_db_password'], '-e "GRANT REPLICATION SLAVE ON *.* TO \''.$database['replication_db_user'].'\'@\'localhost\' IDENTIFIED BY \''.$database['replication_db_password'].'\'; FLUSH PRIVILEGES; USE '.$database['database_name'].'; FLUSH TABLES WITH READ LOCK; DO SLEEP(1000000); "', 'background' => true))));

        /*
         * Dump database
         */
        log_console(tr('Making dump of remote database'), 'VERBOSEDOT');
        linux_delete($database['domain'], '/tmp/'.$database['database_name'].'.sql.gz', true);
        servers_exec($database['domain'], array('commands' => array('mysqldump', array('sudo' => true, '-u'.$database['root_db_user'], '-p'.$database['root_db_password'], '-K', '-R', '-n', '-e', '--dump-date', '--comments', '-B', $database['database_name'], 'connector' => '|'),
                                                                    'gzip'     , array('connector' => '|'),
                                                                    'tee'      , array('sudo' => true, '/tmp/'.$database['database_name'].'.sql.gz'))));

        /*
         * Kill local SSH process to drop the hung connection
         */
        log_console(tr('Dump finished, killing background process mysql shell session'), 'VERBOSEDOT');
        cli_kill($ssh_mysql_pid[0], 9);

        log_console(tr('Restarting remote MySQL service'), 'VERBOSEDOT');
        linux_service($database['domain'], 'mysql', 'restart');

        /*
         * Delete posible LOCAL backup
         * SCP dump from master server to local
         */
        log_console(tr('Copying remote dump to SLAVE'), 'VERBOSEDOT');
        file_delete('/tmp/'.$database['database_name'].'.sql.gz', '/tmp');
        mysqlr_scp_database($database, '/tmp/'.$database['database_name'].'.sql.gz', '/tmp/', true);

        /*
         * Copy from local to slave server
         */
        linux_delete($slave, '/tmp/'.$database['database_name'].'.sql.gz', true);
        mysqlr_scp_database(array('domain' => $slave), '/tmp/'.$database['database_name'].'.sql.gz', '/tmp/');
        file_delete('/tmp/'.$database['database_name'].'.sql.gz', '/tmp');

        /*
         * Get the log_file and log_pos
         */
        $master_status        = mysql_exec($database['domain'], 'SHOW MASTER STATUS');
        $master_status        = explode(',', preg_replace('/\s+/', ',', $master_status[1]));
        $database['log_file'] = $master_status[0];
        $database['log_pos']  = $master_status[1];

        return $database;

    }catch(Exception $e){
        mysqlr_update_server_replication_status($database, 'disabled_lock');
        mysqlr_update_replication_status($database, 'disabled');
        throw new CoreException(tr('mysqlr_master_replication_setup(): Failed'), $e);
    }
}



/*
 * This function can setup a slave
 * 1) MODIFY SLAVE MYSQL CONFIG FILE
 * 2) CREATE A SSH TUNNELING USER ON A SPECIFIC PORT
 * 3) IMPORT MYSQL MASTER DB on SLAVE
 * 4) SETUP SLAVE REPLICATION ON A SPECIFIC PORT AND CHANNEL
 * 5) CHECK FOR SLAVE STATUS
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_slave_replication_setup($params){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Check Slave domain
         */
        $slave = $_CONFIG['mysqlr']['domain'];

        if(empty($slave)){
            throw new CoreException(tr('mysqlr_slave_replication_setup(): MySQL configuration for replicator domain is not set'), 'not-specified');
        }

        /*
         * Get database and prepare info
         */
        $database       = mysql_get_database($params['database']);
        $database       = array_merge($database, $params);
        $database['id'] = mt_rand() - 1;

        /*
         * Get MySQL configuration path
         */
        $mysql_cnf_path = mysqlr_check_configuration_path($slave);

        /*
         * MySQL SETUP
         */
        log_console(tr('Making slave setup for MySQL configuration file'), 'VERBOSEDOT');

        file_sed(array('domain' => $slave,
                       'sudo'   => true,
                       'regex'  => 's/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$database['id'].'/',
                       'source' => $mysql_cnf_path));

        file_sed(array('domain' => $slave,
                       'sudo'   => true,
                       'regex'  => 's/#log_bin/log_bin/',
                       'source' => $mysql_cnf_path));

        /*
         * The next lines just have to be added one time!
         * Check if they already exist... if not append them
         */
        servers_exec($slave, 'grep -q -F \'relay-log = /var/log/mysql/mysql-relay-bin.log\' '.$mysql_cnf_path.' || echo "relay-log = /var/log/mysql/mysql-relay-bin.log" | sudo tee -a '.$mysql_cnf_path);
        servers_exec($slave, 'grep -q -F \'master-info-repository = table\' '.$mysql_cnf_path.' || echo "master-info-repository = table" | sudo tee -a '.$mysql_cnf_path);
        servers_exec($slave, 'grep -q -F \'relay-log-info-repository = table\' '.$mysql_cnf_path.' || echo "relay-log-info-repository = table" | sudo tee -a '.$mysql_cnf_path);
        servers_exec($slave, 'grep -q -F \'binlog_do_db = '.$database['database_name'].'\' '.$mysql_cnf_path.' || echo "binlog_do_db = '.$database['database_name'].'" | sudo tee -a '.$mysql_cnf_path);

        /*
         * Close PDO connection before restarting MySQL
         */
        log_console(tr('Restarting Slave MySQL service'), 'VERBOSEDOT');
        linux_service($slave, 'mysql', 'restart');
        sleep(2);

        /*
         * Import LOCAL db
         */
        mysql_exec    ($slave, 'DROP   DATABASE IF EXISTS '.$database['database_name']);
        mysql_exec    ($slave, 'CREATE DATABASE '.$database['database_name']);
        linux_delete  ($slave, '/tmp/'.$database['database_name'].'.sql', true);
        compress_unzip(array('domain' => $slave,
                             'source' => '/tmp/'.$database['database_name'].'.sql.gz'));
        servers_exec  ($slave, 'sudo mysql "-u'.$database['root_db_user'].'" "-p'.$database['root_db_password'].'" -B '.$database['database_name'].' < /tmp/'.$database['database_name'].'.sql');
        linux_delete  ($slave, '/tmp/'.$database['database_name'].'.sql', true);

        /*
         * Check if this server was already replicating
         */
        if($database['servers_replication_status'] == 'enabled' and empty($params['force_channel'])){
            mysqlr_update_replication_status($database, 'enabled');
            return 0;
        }

        /*
         * This server master was not replicating
         * Enable SSH tunnel
         * Enable SLAVE for this server
         *
         * Create SSH tunneling user
         */
        log_console(tr('Creating ssh tunneling user on local server'), 'VERBOSEDOT');
        mysqlr_slave_ssh_tunnel($database, $slave);

        /*
         * Setup global configurations to support multiple channels
         */
        mysql_exec($slave, 'SET GLOBAL master_info_repository = \"TABLE\"');
        mysql_exec($slave, 'SET GLOBAL relay_log_info_repository = \"TABLE\"');

        /*
         * Setup slave replication
         */
        $slave_setup  = 'STOP SLAVE; ';
        $slave_setup .= 'CHANGE MASTER TO MASTER_HOST=\"127.0.0.1\", ';
        $slave_setup .= 'MASTER_USER=\"'.$database['replication_db_user'].'\", ';
        $slave_setup .= 'MASTER_PASSWORD=\"'.$database['replication_db_password'].'\", ';
        $slave_setup .= 'MASTER_PORT='.$database['port'].', ';
        $slave_setup .= 'MASTER_LOG_FILE=\"'.$database['log_file'].'\", ';
        $slave_setup .= 'MASTER_LOG_POS='.$database['log_pos'].' ';
        $slave_setup .= 'FOR CHANNEL \"'.$database['domain'].'\"; ';
        $slave_setup .= 'START SLAVE FOR CHANNEL \"'.$database['domain'].'\";';
        mysql_exec($slave, $slave_setup);

        /*
         * Final step check for SLAVE status
         */
        mysqlr_update_replication_status($database, 'enabled');
        mysqlr_update_server_replication_status($database, 'enabled');
        log_console(tr('MySQL replication setup finished!'), 'white');

    }catch(Exception $e){
        mysqlr_update_server_replication_status($database, 'disabled_lock');
        mysqlr_update_replication_status($database, 'disabled');
        throw new CoreException(tr('mysqlr_slave_replication_setup(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_pause_replication($db, $restart_mysql = true){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Check Slave domain
         */
        $slave = $_CONFIG['mysqlr']['domain'];

        if(empty($slave)){
            throw new CoreException(tr('mysqlr_pause_replication(): MySQL Configuration for replicator domain is not set'), 'not-specified');
        }

        /*
         * Check if this server exist
         */
        $database = mysql_get_database($db);

        if(empty($database)){
            throw new CoreException(tr('mysqlr_pause_replication(): The specified database :database does not exist', array(':database' => $database)), 'not-exists');
        }

        mysqlr_update_replication_status($database, 'pausing');

        /*
         * Get MySQL configuration path
         */
        $mysql_cnf_path = mysqlr_check_configuration_path($slave);

        /*
         * Enable replicate ignore
         */
        servers_exec($slave, 'grep -q -F \'replicate-ignore-db='.$database['database_name'].'\' '.$mysql_cnf_path.' || echo "replicate-ignore-db='.$database['database_name'].'" | sudo tee -a '.$mysql_cnf_path);

        /*
         * Close PDO connection before restarting MySQL
         */
        if($restart_mysql){
            log_console(tr('Restarting Slave MySQL service'), 'VERBOSEDOT');
            linux_service($slave, 'mysql', 'restart');
        }

        mysqlr_update_replication_status($database, 'paused');
        log_console(tr('Paused replication for database :database', array(':database' => $database['database_name'])), 'VERBOSEDOT');

        return 0;

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_pause_replication(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_resume_replication($db, $restart_mysql = true){
    global $_CONFIG;

    try{
        load_libs('mysql');

        /*
         * Check Slave domain
         */
        $slave = $_CONFIG['mysqlr']['domain'];

        if(empty($slave)){
            throw new CoreException(tr('mysqlr_resume_replication(): MySQL Configuration for replicator domain is not set'), 'not-specified');
        }

        /*
         * Check if this server exist
         */
        $database = mysql_get_database($db);

        if(empty($database)){
            throw new CoreException(tr('mysqlr_resume_replication(): The specified database :database does not exist', array(':database' => $database)), 'not-exists');
        }

        mysqlr_update_replication_status($database, 'resuming');

        $mysql_cnf_path = mysqlr_check_configuration_path($slave);

        /*
         * Comment the database for replication
         */
        file_sed(array('domain' => $slave,
                       'sudo'   => true,
                       'regex'  => 's/replicate-ignore-db='.$database['database_name'].'//',
                       'source' => $mysql_cnf_path));

        /*
         * Close PDO connection before restarting MySQL
         */
        if($restart_mysql){
            log_console(tr('Restarting Slave MySQL service'), 'VERBOSEDOT');
            linux_service($slave, 'mysql', 'restart');
        }

        mysqlr_update_replication_status($database, 'enabled');
        log_console(tr('Resumed replication for database :database', array(':database' => $database['database_name'])), 'VERBOSEDOT');

        return 0;

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_resume_replication(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_check_configuration_path($server_target){
    try{
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';

        /*
         * Check for mysqld.cnf file
         */
        log_console(tr('Checking existance of mysql configuration file'), 'VERBOSEDOT');
        $mysql_cnf = servers_exec($server_target, 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

        /*
         * Mysql conf file does not exist
         */
        if(!$mysql_cnf[0]){
            /*
             * Try with other possible configuration file
             */
            $mysql_cnf_path = '/etc/mysql/my.cnf';
            $mysql_cnf      = servers_exec($server_target, 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

            if(!$mysql_cnf[0]){
                throw new CoreException(tr('mysqlr_check_configuration_path(): MySQL configuration file :file does not exist on server :server', array(':file' => $mysql_cnf_path, ':server' => $server_target)), 'not-exists');
            }
        }

        return $mysql_cnf_path;

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_check_configuration_path(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_slave_ssh_tunnel($server, $slave){
    global $_CONFIG;

    try{
        array_ensure($server);
        array_default($server, 'server'       , '');
        array_default($server, 'domain'       , '');
        array_default($server, 'ssh_key'      , '');
        array_default($server, 'port'         , 22);
        array_default($server, 'arguments'    , '');
        array_default($server, 'hostkey_check', true);

        /*
         * If server was specified by just name, then lookup the server data in
         * the database
         */
        if($server['domain']){
            $dbserver = sql_get('SELECT    `ssh_accounts`.`username`,
                                           `ssh_accounts`.`ssh_key`,
                                           `servers`.`id`,
                                           `servers`.`domain`,
                                           `servers`.`port`

                                 FROM      `servers`

                                 LEFT JOIN `ssh_accounts`
                                 ON        `ssh_accounts`.`id` = `servers`.`ssh_accounts_id`

                                 WHERE     `servers`.`domain` = :domain', array(':domain' => $server['domain']));

            if(!$dbserver){
                throw new CoreException(tr('ssh_mysql_slave_tunnel(): Specified server ":server" does not exist', array(':server' => $server['server'])), 'not-exists');
            }

            $server = sql_merge($server, $dbserver);
        }

        if(!$server['hostkey_check']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile='.ROOT.'data/ssh/known_hosts ';
        }

        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        file_ensure_path(ROOT.'data/ssh/keys');
        chmod(ROOT.'data/ssh', 0770);

        /*
         * Safely create SSH key file
         */
        $keyname = str_random(8);
        $keyfile = ROOT.'data/ssh/keys/'.$keyname;

        touch($keyfile);
        chmod($keyfile, 0600);
        file_put_contents($keyfile, $server['ssh_key'], FILE_APPEND);
        chmod($keyfile, 0400);

        /*
         * Copy key file
         * and execute autossh
         */
        safe_exec('scp '.$server['arguments'].' -P '.$_CONFIG['mysqlr']['port'].' -i '.$keyfile.' '.$keyfile.' '.$server['username'].'@'.$slave.':/data/ssh/keys/');
        servers_exec($slave, 'autossh -p '.$server['port'].' -i /data/ssh/keys/'.$keyname.' -L '.$server['port'].':localhost:3306 '.$server['username'].'@'.$server['domain'].' -f -N');

        /*
         * Delete local file key
         */
        chmod($keyfile, 0600);
        file_delete($keyfile, ROOT.'data/ssh/keys');

    }catch(Exception $e){
        /*
         * Try deleting the keyfile anyway!
         */
        try{
            if(!empty($keyfile)){
                safe_exec(chmod($keyfile, 0600));
                file_delete($keyfile, ROOT.'data/ssh/keys');
            }

        }catch(Exception $f){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify(tr('mysqlr_slave_ssh_tunnel(): cannot delete key'), $f, 'developers');
        }

        throw new CoreException(tr('mysqlr_slave_ssh_tunnel(): Failed'), $e);
    }
}



/*
 * .............
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 */
function mysqlr_full_backup(){
    global $_CONFIG;

    try{
        /*
         * Get all servers replicating
         */
        $slave   = $_CONFIG['mysqlr']['domain'];
        $servers = sql_query('SELECT `id`,
                                     `domain`,
                                     `seodomain`

                              FROM   `servers`

                              WHERE  `replication_status` = "enabled"');

        if(!$servers->rowCount()){
            /*
             * There are no servers in replication status
             */
            return false;
        }

        /*
         * Make a directory on the replication server
         */
        $backup_path = '/data/backups/databases';
        servers_exec($slave, 'sudo mkdir -p '.$backup_path);

        /*
         * For each server get the databases replicating
         */
        while($server = sql_fetch($servers)){
            $databases = sql_list('SELECT `id`,
                                           `name`

                                   FROM   `databases`

                                   WHERE  `replication_status` = "enabled"
                                   AND    `servers_id`         = :servers_id',

                                   array(':servers_id' => $server['id']));

            if(!count($databases)){
                /*
                 * There are no databases replicating at this time
                 * Skip to next server
                 */
                continue;
            }

            log_console(tr('Making backups of server :server', array(':server' => $server['domain'])), 'VERBOSEDOT');

            /*
             * Disable replication of each database
             */
            foreach($databases as $id => $name){
                log_console(tr('Disabling replication of database :database', array(':database' => $name)), 'VERBOSEDOT');
                mysqlr_pause_replication($id, false);
            }

            /*
             * Restart mysql service on slave to disable replication on selected databases
             */
            linux_service($slave, 'mysql', 'restart');

            /*
             * Create a directory for the current server inside the backup directory
             */
            $server_backup_path = $backup_path.'/'.$server['domain'];
            servers_exec($slave, 'sudo mkdir '.$server_backup_path);

            foreach($databases as $id => $name){
                $db                 = mysql_get_database($id);
                $db['root_db_user'] = 'root';

                log_console(tr('Making backup of database :database', array(':database' => $db['database_name'])), 'VERBOSEDOT');

                /*
                 * Make a dump and save it on the backups server backup directory
                 * And resume replication on this database
                 */
                mysql_dump(array('server'   => $slave,
                                 'database' => $db['database_name'],
                                 'gzip'     => '',
                                 'redirect' => ' | sudo tee',
                                 'file'     => $server_backup_path.'/'.$db['database_name'].'.sql'));
// :DELETE: the below code is deprecated since we are using mysql_dump function
                //servers_exec($slave, 'sudo mysqldump \"-u'.$db['root_db_user'].'\" \"-p'.$db['root_db_password'].'\" -K -R -n -e --dump-date --comments -B '.$db['database_name'].' | gzip | sudo tee '.$server_backup_path.'/'.$db['database_name'].'.sql.gz');
                mysqlr_resume_replication($id, false);
            }

            /*
             * Restart mysql service on slave to enable replication again on selected databases
             */
            linux_service($slave, 'mysql', 'restart');
        }

        /*
         * rsync to backup server
         */
        rsync(array($slave, 'rsync -avze \"ssh -p '.$_CONFIG['mysqlr']['backup']['port'].'\" '.$backup_path.'/*'.' '.$_CONFIG['mysqlr']['backup']['domain'].':'.$_CONFIG['mysqlr']['backup']['path']);

        /*
         * Delete replicate backup for today
         */
        linux_delete($slave, $backup_path, true);
        log_console(tr('mysqlr_full_backup(): Finished backups'), 'VERBOSEDOT');

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_full_backup(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_scp_database($server, $source, $destnation, $from_server = false){
    try{
obsolete('mysqlr_scp_database() NEEDS TO BE REIMPLEMENTED FROM THE GROUND UP USING THE NEW AVAILABLE FUNCTIONS');
        array_ensure($server);
        array_default($server, 'server'       , '');
        array_default($server, 'domain'       , '');
        array_default($server, 'ssh_key'      , '');
        array_default($server, 'port'         , 22);
        array_default($server, 'hostkey_check', false);
        array_default($server, 'arguments'    , '');

        /*
         * If server was specified by just name, then lookup the server data in
         * the database
         */
        if($server['domain']){
            $dbserver = sql_get('SELECT    `ssh_accounts`.`username`,
                                           `ssh_accounts`.`ssh_key`,
                                           `servers`.`id`,
                                           `servers`.`domain`,
                                           `servers`.`port`

                                 FROM      `servers`

                                 LEFT JOIN `ssh_accounts`
                                 ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                 WHERE     `servers`.`domain`   = :domain',

                                 array(':domain' => $server['domain']));

            if(!$dbserver){
                throw new CoreException(tr('mysqlr_scp_database(): Specified server ":server" does not exist', array(':server' => $server['server'])), 'not-exists');
            }

            $server = sql_merge($server, $dbserver);
        }

        if(!$server['hostkey_check']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile='.ROOT.'data/ssh/known_hosts ';
        }

        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        file_ensure_path(ROOT.'data/ssh/keys');
        chmod(ROOT.'data/ssh', 0770);

        /*
         * Safely create SSH key file
         */
        $keyfile = ROOT.'data/ssh/keys/'.str_random(8);

        touch($keyfile);
        chmod($keyfile, 0600);
        file_put_contents($keyfile, $server['ssh_key'], FILE_APPEND);
        chmod($keyfile, 0400);

        if($from_server){
            $command = $server['username'].'@'.$server['domain'].':'.$source.' '.$destnation;

        }else{
            $command = $source.' '.$server['username'].'@'.$server['domain'].':'.$destnation;
        }

        /*
         * Execute command
         */
        $result = safe_exec(array('commands' => array('scp', array($server['arguments'], '-P', $server['port'], '-i', $keyfile, $command))));
        chmod($keyfile, 0600);
        file_delete($keyfile, ROOT.'data/ssh/keys');

        return $result;

    }catch(Exception $e){
        notify(tr('mysqlr_scp_database() exception'), $e, 'developers');

        /*
         * Try deleting the keyfile anyway!
         */
        try{
            if(!empty($keyfile)){
                chmod($keyfile, 0600);
                file_delete($keyfile, ROOT.'data/ssh/keys');
            }

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify(tr('mysqlr_scp_database() cannot delete key'), $e, 'developers');
        }

        throw new CoreException(tr('mysqlr_scp_database(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_add_log($params){
    try{
        /*
         * Validate
         */
        array_ensure($params);
        array_default($params, 'databases_id', '');
        array_default($params, 'type'        , '');
        array_default($params, 'message'     , '');

        if(empty($params['databases_id'])){
            throw new CoreException(tr('No database specified'), 'not-specified');
        }

        if(empty($params['type'])){
            throw new CoreException(tr('No type specified'), 'not-specified');
        }

        /*
         * Validate log type
         */
        switch($params['type']){
            case 'mysql_issue':
                // FALLTHROUGH
            case 'ssh_tunnel':
                // FALLTHROUGH
            case 'table_issue':
                // FALLTHROUGH
            case 'misconfiguration':
                // FALLTHROUGH
            case 'other':
                /*
                 * Do nothing
                 */
                break;

            default:
                throw new CoreException(tr('Specified type is not valid'), 'not-valid');
        }

        if(empty($params['message'])){
            throw new CoreException(tr('No message specified'), 'not-specified');
        }

        /*
         * Get database
         * This function will throw an error is this database does not exist
         */
        $database = mysql_get_database($params['databases_id']);

        /*
         * Update database
         */
        sql_query('INSERT INTO `replicator_logs` (`type`, `projects_id`, `servers_id`, `databases_id`, `message`)
                   VALUES                        (:type , :projects_id , :servers_id , :databases_id , :message )',

                   array(':type'         => $params['type'],
                         ':projects_id'  => $database['projects_id'],
                         ':servers_id'   => $database['servers_id'],
                         ':databases_id' => $database['databases_id'],
                         ':message'      => $params['message']));

        /*
         * Notify
         */
        notify(array('classes'     => 'developers',
                     'title'       => tr('mysqlr monitoring failed with code ":code"', array(':code' => $params['type'])),
                     'description' => $params['message']));

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_add_log(): Failed'), $e);
    }
}


/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_get_logs($database, $limit = 50){
    try{
        load_libs('mysql');

        /*
         * Validate data
         */
        if(empty($database)){
            throw new CoreException(tr('No database specified'), 'not-specified');
        }

        /*
         * Get database
         * This function will throw an error is this database does not exist
         */
        $database = mysql_get_database($database);

        /*
         * Get logs
         */
        $replicator_logs = sql_list('SELECT    `replicator_logs`.`id`,
                                               `replicator_logs`.`createdon`,
                                               `replicator_logs`.`status`,
                                               `replicator_logs`.`type`,
                                               `replicator_logs`.`projects_id`,
                                               `replicator_logs`.`servers_id`,
                                               `replicator_logs`.`databases_id`,
                                               `replicator_logs`.`message`,

                                               `servers`.`domain`,
                                               `servers`.`seodomain`,

                                               `projects`.`name`

                                     FROM      `replicator_logs`

                                     LEFT JOIN `projects`
                                     ON        `replicator_logs`.`projects_id`  = `projects`.`id`

                                     LEFT JOIN `servers`
                                     ON        `replicator_logs`.`servers_id`   = `servers`.`id`

                                     WHERE     `replicator_logs`.`databases_id` = :databases_id
                                     AND       `replicator_logs`.`status`       IS NULL

                                     ORDER BY  `replicator_logs`.`createdon` DESC

                                     LIMIT     '.$limit,

                                     array(':databases_id' => $database['id']));

        return $replicator_logs;

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_add_log(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_monitor_database($database){
    global $_CONFIG;

    try{
        $slave = $_CONFIG['mysqlr']['domain'];

        /*
         * Validate data
         */
        if(empty($database)){
            throw new CoreException(tr('No database specified'), 'not-specified');
        }

        /*
         * Get database
         * This function will throw an error is this database does not exist
         */
        $database = mysql_get_database($database['databases_id']);
        log_console(tr('Checking database :database from server :server', array(':database' => $database['database_name'], ':server' => $database['domain'])), 'white');

        /*
         * Check if this db can replicate
         */
        if(!mysqlr_db_can_replicate($database['database_name'])){
            log_console(tr('This database can not replicate due to more databases with the same name, skipping'), 'yellow');
            return false;
        }

        if($database['server_replication_lock']){
            /*
             * Server is currently making another replication
             * do not monitor this, next time
             */
            log_console(tr('This database can not be monitored at this time, the server is locked, wait until next round'), 'yellow');
            return false;
        }

        /*
         * Check if MySQL configuration still has this database
         */
        $mysql_cnf_path = mysqlr_check_configuration_path($database['domain']);
        $result         = servers_exec(array('domain'   => $database['domain'],
                                             'commands' => array('grep', array('-q', '-F', 'binlog_do_db = '.$database['database_name'], $mysql_cnf_path, 'connector' => ' && echo "1" || echo "0"'))));

        if(!$result[0]){
            /*
             * Database is not in binlog then it is disabled
             */
            mysqlr_add_log(array('databases_id' => $database['id'],
                                 'type'         => 'misconfiguration',
                                 'message'      => tr('mysqlr_monitor_database(): The mysql configuration file does not contain this database, meaning that this database is not replicating')));

            mysqlr_update_replication_status($database, 'disabled');
            return false;
        }

        /*
         * Check channel for the server database on the Replicator server
         */
        $result = sql_get('SHOW SLAVE STATUS FOR CHANNEL :channel', array(':channel' => $database['domain']), null, 'replicator');

        if(empty($result)){
            /*
             * No slave channel for this server
             */
            mysqlr_add_log(array('databases_id' => $database['id'],
                                 'type'         => 'mysql_issue',
                                 'message'      => tr('mysqlr_monitor_database(): The mysql channel for this database does not exist, check the configuration for this slave')));
            mysqlr_update_replication_status($database, 'disabled');
            return false;
        }

        if(strtolower($result['Slave_IO_Running']) != 'yes' or strtolower($result['Slave_SQL_Running']) != 'yes'){
            /*
             * Fix possible MYSQL Slave issues
             */
            switch($result['Last_IO_Errno']){
                case 1236:
                    /*
                     * Got fatal error 1236 from master when reading data from binary log:
                     * 'Could not find first log file name in binary log index file'
                     *
                     * Try by replicating again
                     */
                    load_libs('tasks');
                    log_console(tr('Replication add master for database :database of server :server', array(':database' => $database['database_name'], ':server' => $database['domain'])), 'white');
                    mysqlr_update_replication_status($database, 'preparing');
                    $task = tasks_insert(array('command'     => 'base/mysql',
                                            'time_limit'  => 1200,
                                            'status'      => 'new',
                                            'description' => tr('Add replication master of database ":database" on server ":server"', array(':database' => $database['database_name'], ':server' => $database['domain'])),
                                            'data'        => array('method'          => 'replication add-master',
                                                                   '--env'           => ENVIRONMENT,
                                                                   '--domain'        => $database['domain'],
                                                                   '--database'      => $database['databases_id'],
                                                                   '--force-channel' => true)));
                    mysqlr_update_replication_status($database, 'error');
                    break;

                case 0:
                    /*
                     * Do nothing
                     */
                    break;

                default:
                    /*
                     * Unkown issue
                     * Try restarting the ssh tunnel
                     */
                    mysqlr_slave_ssh_tunnel($database, $slave);
                    mysqlr_update_replication_status($database, 'error');
            }

            /*
             * Fix possible MYSQL Slave issues
             */
            switch($result['Last_Errno']){
                case 1146:
                    /*
                     *
                     * Try by replicating again
                     */
                    load_libs('tasks');
                    log_console(tr('Replication add master for database :database of server :server', array(':database' => $database['database_name'], ':server' => $database['domain'])), 'white');
                    mysqlr_update_replication_status($database, 'preparing');
                    $task = tasks_insert(array('command'     => 'base/mysql',
                                            'time_limit'  => 1200,
                                            'status'      => 'new',
                                            'description' => tr('Add replication master of database ":database" on server ":server"', array(':database' => $database['database_name'], ':server' => $database['domain'])),
                                            'data'        => array('method'          => 'replication add-master',
                                                                   '--env'           => ENVIRONMENT,
                                                                   '--domain'        => $database['domain'],
                                                                   '--database'      => $database['databases_id'],
                                                                   '--force-channel' => true)));
                    mysqlr_update_replication_status($database, 'error');
                    break;

                case 0:
                    /*
                     * Do nothing
                     */
                    break;

                default:
                    /*
                     * Unkown issue
                     * Try restarting the ssh tunnel
                     */
                    mysqlr_slave_ssh_tunnel($database, $slave);
            }

            if($result['Last_Errno'] == 0 and $result['Last_IO_Errno'] == 0){
                /*
                 * The Slave is not running on this channel
                 * Just try restarting the mysql server
                 */
                linux_service($slave, 'mysql', 'restart');
            }

            /*
             * Add log
             */
            mysqlr_add_log(array('databases_id' => $database['id'],
                                 'type'         => 'mysql_issue',
                                 'message'      => tr('mysqlr_monitor_database(): There is an error with the Slave, restarting ssh tunnel, Last_IO_Errno ":Last_IO_Errno", Last_Errno ":Last_Errno", Last_Error ":Last_Error" Last_IO_Error ":Last_IO_Error"', array(':Last_IO_Errno' => $result['Last_IO_Errno'], ':Last_Errno' => $result['Last_Errno'], ':Last_Error' => $result['Last_Error'], ':Last_IO_Error' => $result['Last_IO_Error']))));
            return false;
        }

        /*
         * Everything is okay
         */
        mysqlr_update_replication_status($database, 'enabled');
        return true;

    }catch(Exception $e){
        if(strstr($e->getMessage(), 'MySQL server has gone away')){
            /*
             * Close the current connector so the next monitoring cycle can
             * generate a new one
             */
            sql_close('replicator');

        }else{
            throw new CoreException(tr('mysqlr_monitor_database(): Failed'), $e);
        }
    }
}



/*
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_log_type_human($type){
    try{
        $retval = '';
        switch($type){
            case 'mysql_issue':
                $retval = 'MySQL Issue';
                break;

            case 'ssh_tunnel':
                $retval = 'SSH Tunnel Issue';
                break;

            case 'table_issue':
                $retval = 'Database Table Issue';
                break;

            case 'misconfiguration':
                $retval = 'Misconfiguration';
                break;

            case 'other':
                $retval = 'Other';
                break;

            default:
                throw new CoreException(tr('Specified type is not valid'), 'not-valid');
        }

        return $retval;

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_log_html_tag_type(): Failed'), $e);
    }
}



/*
 *
 * @author Ismael Haro <isma@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package mysqlr
 *
 * @param
 * @return
 */
function mysqlr_db_can_replicate($database_name){
    try{
        /*
         * Check if there is duplicate database names on other servers
         */
        $duplicates = sql_query('SELECT `id`,`name` FROM `databases` WHERE `name` = :name', array(':name' => $database_name));

        if($duplicates->rowCount() > 1){
            return false;
        }

        return true;

    }catch(Exception $e){
        throw new CoreException(tr('mysqlr_log_html_tag_type(): Failed'), $e);
    }
}
?>