<?php
/*
 * MySQL library
 *
 * This library contains various functions to manage mysql databases and servers
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
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

    }catch(Exception $e){
        throw new bException('mysqlr_library_init(): Failed', $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 */
function mysqlr_update_database_replication_status($params, $status){
    try{
        /*
         * Update server and database replication_status
         */
        array_params($params);
        array_default($params, 'database', '');

        if(empty($params['database'])){
            throw new bException(tr('mysql_update_replication_status(): database not specified'), 'not-specified');
        }

        /*
         * Update database
         */
        sql_query('UPDATE `databases`

                   SET    `replication_status` = :replication_status

                   WHERE  `name` = :name',

                   array(':replication_status' => $status,
                         ':name'               => $params['database']));

    }catch(Exception $e){
        throw new bException(tr('mysqlr_update_database_replication_status(): Failed'), $e);
    }
}



/*
 * Current available replication statuses
 * 'enabled','preparing','paused','disabled','error'
 */
function mysqlr_update_replication_status($params, $status){
    try{
        /*
         * Update server and database replication_status
         */
        array_params($params);
        array_default($params, 'database'  , '');
        array_default($params, 'servers_id', '');

        if(empty($params['database'])){
            throw new bException(tr('mysql_update_replication_status(): database not specified'), 'not-specified');
        }

        if(empty($params['servers_id'])){
            throw new bException(tr('mysql_update_replication_status(): servers_id not specified'), 'not-specified');
        }

        /*
         * Update server
         */
        sql_query('UPDATE `servers` SET `replication_status` = :replication_status WHERE id = :id', array(':replication_status' => $status, ':id' => $params['servers_id']));

        /*
         * Update database
         */
        sql_query('UPDATE `databases` SET `replication_status` = :replication_status WHERE name = :name', array(':replication_status' => $status, ':name' => $params['database']));

    }catch(Exception $e){
        throw new bException(tr('mysqlr_update_replication_status(): Failed'), $e);
    }
}



/*
 * This function can setup a master
 * 1) MODIFY MASTER MYSQL CONFIG FILE
 * 2) CREATE REPLICATION USER ON MASTER MYSQL
 * 3) DUMP MYSQL DB
 * 4) ON OTHER SHELL GET MYSQL LOG_FILE AND LOG_POS
 */
function mysqlr_master_replication_setup($params){
    try{
        load_libs('mysql');

        /*
         * Validate params
         */
        array_ensure($params, 'hostname,database');

        /*
         * Get database
         */
        $database       = mysql_get_database($params['database']);
        $database       = array_merge($database, $params);
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';
        mysql_update_replication_status($database, 'preparing');

        load_libs('ssh,servers');

        /*
         * Check for mysqld.cnf file
         */
        log_console(tr('Checking existance of mysql configuration file on remote server'), 'DOT');
        $mysql_cnf = servers_exec($database['hostname'], 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

        /*
         * Mysql conf file does not exist
         */
        if(!$mysql_cnf[0]){
            /*
             * Try with other possible configuration file
             */
            $mysql_cnf_path = '/etc/mysql/my.cnf';
            $mysql_cnf      = servers_exec($database['hostname'], 'test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

            if(!$mysql_cnf[0]){
                throw new bException(tr('mysql_master_replication_setup(): MySQL configuration file :file does not exist on remote server', array(':file' => $mysql_cnf_path)), 'not-exist');
            }
        }

        /*
         * MySQL SETUP
         */
        log_console(tr('Making master setup for MySQL configuration file'), 'DOT');
        servers_exec($database['hostname'], 'sudo sed -i \"s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$database['id'].'/\" '.$mysql_cnf_path);
        servers_exec($database['hostname'], 'sudo sed -i \"s/#log_bin/log_bin/\" '.$mysql_cnf_path);

        /*
         * The next line just have to be added one time!
         * Check if it exists, if not append
         */
        servers_exec($database['hostname'], 'grep -q -F \'binlog_do_db = '.$database['database'].'\' '.$mysql_cnf_path.' || sudo sed -i \"/max_binlog_size[[:space:]]*=[[:space:]]*100M/a binlog_do_db = '.$database['database'].'\" '.$mysql_cnf_path);

        log_console(tr('Restarting remote MySQL service'), 'DOT');
        servers_exec($database['hostname'], 'sudo service mysql restart');

        /*
         * LOCK MySQL database
         * sleep infinity and run in background
         * kill ssh pid after dumping db
         */
        log_console(tr('Making grant replication on remote server and locking tables'), 'DOT');
        $ssh_mysql_pid = servers_exec($database['hostname'], 'mysql \"-u'.$database['root_db_user'].'\" \"-p'.$database['root_db_password'].'\" -e \"GRANT REPLICATION SLAVE ON *.* TO \''.$database['replication_db_user'].'\'@\'localhost\' IDENTIFIED BY \''.$database['replication_db_password'].'\'; FLUSH PRIVILEGES; USE '.$database['database'].'; FLUSH TABLES WITH READ LOCK; DO SLEEP(1000000); \"', null, true);

        /*
         * Dump database
         */
        log_console(tr('Making dump of remote database'), 'DOT');
        servers_exec($database['hostname'], 'sudo rm /tmp/'.$database['database'].'.sql.gz -f;');
        servers_exec($database['hostname'], 'sudo mysqldump \"-u'.$database['root_db_user'].'\" \"-p'.$database['root_db_password'].'\" -K -R -n -e --dump-date --comments -B '.$database['database'].' | gzip | sudo tee /tmp/'.$database['database'].'.sql.gz');

        /*
         * KILL LOCAL SSH process
         * to drop the hanged connection
         */
        log_console(tr('Dump finished, killing background process mysql shell session'), 'DOT');
        shell_exec('kill -9 '.$ssh_mysql_pid[0]);

        log_console(tr('Restarting remote MySQL service'), 'DOT');
        servers_exec($database['hostname'], 'sudo service mysql restart');

        /*
         * Delete posible LOCAL backup
         * SCP dump from server to local
         */
        log_console(tr('Copying remote dump to local'), 'DOT');
        shell_exec('rm /tmp/'.$database['database'].'.sql.gz -f');
        ssh_cp($database, '/tmp/'.$database['database'].'.sql.gz', '/tmp/', true);

        /*
         * Get the log_file and log_pos
         */
        $master_status        = mysql_exec($database['hostname'], 'SHOW MASTER STATUS');
        $master_status        = explode(',', preg_replace('/\s+/', ',', $master_status[1]));
        $database['log_file'] = $master_status[0];
        $database['log_pos']  = $master_status[1];

        return $database;

    }catch(Exception $e){
        mysql_update_replication_status($database, 'disabled');
        throw new bException(tr('mysqlr_master_replication_setup(): Failed'), $e);
    }
}



/*
 * This function can setup a slave
 * 1) MODIFY SLAVE MYSQL CONFIG FILE
 * 2) CREATE A SSH TUNNELING USER ON A SPECIFIC PORT
 * 3) IMPORT MYSQL MASTER DB on SLAVE
 * 4) SETUP SLAVE REPLICATION ON A SPECIFIC PORT AND CHANNEL
 * 5) CHECK FOR SLAVE STATUS
 */
function mysqlr_slave_replication_setup($params){
    try{
        load_libs('mysql');

        /*
         * Get database
         */
        $database       = mysql_get_database($params['database']);
        $database       = array_merge($database, $params);
        $database['id'] = mt_rand() - 1;
        $mysql_cnf_path = '/etc/mysql/mysql.conf.d/mysqld.cnf';

        load_libs('ssh,servers');

        /*
         * Check for mysqld.cnf file
         */
        log_console(tr('Checking existance of mysql configuration file on local server'), 'DOT');
        $mysql_cnf = shell_exec('test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

        /*
         * Mysql conf file does not exist
         */
        if(!$mysql_cnf[0]){
            /*
             * Try with other possible configuration file
             */
            $mysql_cnf_path = '/etc/mysql/my.cnf';
            $mysql_cnf      = shell_exec('test -f '.$mysql_cnf_path.' && echo "1" || echo "0"');

            if(!$mysql_cnf[0]){
                throw new bException(tr('mysql_master_replication_setup(): MySQL configuration file :file does not exist on local server', array(':file' => $mysql_cnf_path)), 'not-exist');
            }
        }

        /*
         * MySQL SETUP
         */
        log_console(tr('Making slave setup for MySQL configuration file'), 'DOT');
        shell_exec('sudo sed -i "s/#server-id[[:space:]]*=[[:space:]]*1/server-id = '.$database['id'].'/" '.$mysql_cnf_path);
        shell_exec('sudo sed -i "s/#log_bin/log_bin/" '.$mysql_cnf_path);

        /*
         * The next lines just have to be added one time!
         * Check if they already exist... if not append them
         */
        shell_exec('grep -q -F \'relay-log = /var/log/mysql/mysql-relay-bin.log\' '.$mysql_cnf_path.' || echo "relay-log = /var/log/mysql/mysql-relay-bin.log" | sudo tee -a '.$mysql_cnf_path);
        shell_exec('grep -q -F \'master-info-repository = table\' '.$mysql_cnf_path.' || echo "master-info-repository = table" | sudo tee -a '.$mysql_cnf_path);
        shell_exec('grep -q -F \'relay-log-info-repository = table\' '.$mysql_cnf_path.' || echo "relay-log-info-repository = table" | sudo tee -a '.$mysql_cnf_path);
        shell_exec('grep -q -F \'binlog_do_db = '.$database['database'].'\' '.$mysql_cnf_path.' || echo "binlog_do_db = '.$database['database'].'" | sudo tee -a '.$mysql_cnf_path);

        /*
         * Close PDO connection before restarting MySQL
         */
        sql_close();
        log_console(tr('Restarting local MySQL service'), 'DOT');
        shell_exec('sudo service mysql restart');
        sql_close();
        sleep(2);

        /*
         * Import LOCAL db
         */
        sql_query('DROP   DATABASE IF EXISTS `'.$database['database'].'`');
        sql_query('CREATE DATABASE `'.$database['database'].'`');
        shell_exec('sudo rm /tmp/'.$database['database'].'.sql -f');
        shell_exec('gzip -d /tmp/'.$database['database'].'.sql.gz');
        shell_exec('sudo mysql "-u'.$database['root_db_user'].'" "-p'.$database['root_db_password'].'" -B '.$database['database'].' < /tmp/'.$database['database'].'.sql');
        shell_exec('sudo rm /tmp/'.$database['database'].'.sql -f');

        /*
         * Check if this server was already replicating
         */
        if($database['servers_replication_status'] == 'enabled'){
            mysql_update_replication_status($database, 'enabled');
            return 0;
        }

        /*
         * This server master was not replicating
         * Enable SSH tunnel
         * Enable SLAVE for this server
         *
         * Create SSH tunneling user
         */
        log_console(tr('Creating ssh tunneling user on local server'), 'DOT');
        ssh_mysql_slave_tunnel($database);

        /*
         * Setup global configurations to support multiple channels
         */
        shell_exec('sudo mysql "-u'.$database['root_db_user'].'" "-p'.$database['root_db_password'].'" -e "SET GLOBAL master_info_repository = \'TABLE\';"');
        shell_exec('sudo mysql "-u'.$database['root_db_user'].'" "-p'.$database['root_db_password'].'" -e "SET GLOBAL relay_log_info_repository = \'TABLE\';"');

        /*
         * Setup slave replication
         */
// :DELETE: Since we are using channels we dont need this
        //$slave_setup  = 'STOP SLAVE; ';
        $slave_setup  = 'CHANGE MASTER TO MASTER_HOST=\'127.0.0.1\', ';
        $slave_setup .= 'MASTER_USER=\''.$database['replication_db_user'].'\', ';
        $slave_setup .= 'MASTER_PASSWORD=\''.$database['replication_db_password'].'\', ';
        $slave_setup .= 'MASTER_PORT='.$database['ssh_port'].', ';
        $slave_setup .= 'MASTER_LOG_FILE=\''.$database['log_file'].'\', ';
        $slave_setup .= 'MASTER_LOG_POS='.$database['log_pos'].' ';
        $slave_setup .= 'FOR CHANNEL \''.$database['hostname'].'\'; ';
        $slave_setup .= 'START SLAVE FOR CHANNEL \''.$database['hostname'].'\';';
        shell_exec('sudo mysql "-u'.$database['root_db_user'].'" "-p'.$database['root_db_password'].'" -e "'.$slave_setup.'"');

        /*
         * Final step check for SLAVE status
         */
        mysql_update_replication_status($database, 'enabled');
        log_console(tr('Finished!!'), 'white');

    }catch(Exception $e){
        mysql_update_replication_status($database, 'disabled');
        throw new bException(tr('mysqlr_slave_replication_setup(): Failed'), $e);
    }
}
?>