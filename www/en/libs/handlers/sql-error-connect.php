<?php
try{
    global $_CONFIG, $core;

    if($e->getMessage() == 'could not find driver'){
        throw new BException(tr('sql_connect(): Failed to connect with ":driver" driver, it looks like its not available', array(':driver' => $connector['driver'])), 'driverfail');
    }

    log_console(tr('Encountered exception ":e" while connecting to database server, attempting to resolve', array(':e' => $e->getMessage())), 'yellow');

    /*
     * Check that all connector values have been set!
     */
    foreach(array('driver', 'host', 'user', 'pass') as $key){
        if(empty($connector[$key])){
            if($_CONFIG['production']){
                throw new BException(tr('sql_connect(): The database configuration has key ":key" missing, check your database configuration in :rootconfig/production.php', array(':key' => $key, ':root' => ROOT)), 'configuration');
            }

            throw new BException(tr('sql_connect(): The database configuration has key ":key" missing, probably check your database connector configuration, possibly in :rootconfig/production.php and / or :rootconfig/:environment.php', array(':key' => $key, ':root' => ROOT, ':environment' => ENVIRONMENT), false), 'configuration');
        }
    }

    switch($e->getCode()){
        case 1049:
            /*
             * Database not found!
             */
            $core->register['no-db'] = true;

            if(!((PLATFORM_CLI) and ($core->register['script'] == 'init') and ($core->register['script'] == 'sync'))){
                throw $e;
            }

            log_console(tr('Database base server conntection failed because database ":db" does not exist. Attempting to connect without using a database to correct issue', array(':db' => $connector['db'])), 'yellow');

            /*
             * We're running the init script, so go ahead and create the DB already!
             */
            $db  = $connector['db'];
            unset($connector['db']);
            $pdo = sql_connect($connector);

            log_console(tr('Successfully connected to database server. Attempting to create database ":db"', array(':db' => $db)), 'yellow');

            $pdo->query('CREATE DATABASE `'.$db.'`');

            log_console(tr('Reconnecting to database server with database ":db"', array(':db' => $db)), 'yellow');

            $connector['db'] = $db;
            return sql_connect($connector);

        case 2002:
            /*
             * Connection refused
             */
            if(empty($connector['ssh_tunnel']['required'])){
                throw new BException(tr('sql_connect(): Connection refused for host ":hostname::port"', array(':hostname' => $connector['host'], ':port' => $connector['port'])), $e);
            }

            /*
             * This connection requires an SSH tunnel. Check if the tunnel process still exists
             */
            load_libs('cli,servers');

            if(!cli_pidgrep($tunnel['pid'])){
                $server     = servers_get($connector['ssh_tunnel']['domain']);
                $registered = ssh_host_is_known($server['hostname'], $server['port']);

                if($registered === false){
                    throw new BException(tr('sql_connect(): Connection refused for host ":hostname" because the tunnel process was canceled due to missing server fingerprints in the ROOT/data/ssh/known_hosts file and `ssh_fingerprints` table. Please register the server first', array(':hostname' => $connector['ssh_tunnel']['domain'])), $e);
                }

                if($registered === true){
                    throw new BException(tr('sql_connect(): Connection refused for host ":hostname" on local port ":port" because the tunnel process either started too late or already died. The server has its SSH fingerprints registered in the ROOT/data/ssh/known_hosts file.', array(':hostname' => $connector['ssh_tunnel']['domain'], ':port' => $connector['port'])), $e);
                }

                /*
                 * The server was not registerd in the ROOT/data/ssh/known_hosts file, but was registered in the ssh_fingerprints table, and automatically updated. Retry to connect
                 */
                return sql_connect($connector, $use_database);
            }

//:TODO: SSH to the server and check if the msyql process is up!
            throw new BException(tr('sql_connect(): Connection refused for SSH tunnel requiring host ":hostname::port". The tunnel process is available, maybe the MySQL on the target server is down?', array(':hostname' => $connector['host'], ':port' => $connector['port'])), $e);

        case 2006:
            /*
             * MySQL server went away
             *
             * Check if tunnel PID is still there
             * Check if target server supports TCP forwarding.
             * Check if the tunnel is still responding to TCP requests
             */
            if(empty($connector['ssh_tunnel']['required'])){
                /*
                 * No SSH tunnel was required for this connector
                 */
                throw $e;
            }

            load_libs('servers,linux');

            $server  = servers_get($connector['ssh_tunnel']['domain']);
            $allowed = linux_get_ssh_tcp_forwarding($server);

            if(!$allowed){
                /*
                 * SSH tunnel is required for this connector, but tcp fowarding
                 * is not allowed. Allow it and retry
                 */
                if(!$server['allow_sshd_modification']){
                    throw new BException(tr('sql_connect(): Connector ":connector" requires SSH tunnel to server, but that server does not allow TCP fowarding, nor does it allow auto modification of its SSH server configuration', array(':connector' => $connector)), 'configuration');
                }

                log_console(tr('Connector ":connector" requires SSH tunnel to server ":server", but that server does not allow TCP fowarding. Server allows SSH server configuration modification, attempting to resolve issue', array(':server' => $connector['ssh_tunnel']['domain'])), 'yellow');

                /*
                 * Now enable TCP forwarding on the server, and retry connection
                 */
                linux_set_ssh_tcp_forwarding($server, true);
                log_console(tr('Enabled TCP fowarding for server ":server", trying to reconnect to MySQL database', array(':server' => $connector['ssh_tunnel']['domain'])), 'yellow');

                if($connector['ssh_tunnel']['pid']){
                    log_console(tr('Closing previously opened SSH tunnel to server ":server"', array(':server' => $connector['ssh_tunnel']['domain'])), 'yellow');
                    ssh_close_tunnel($connector['ssh_tunnel']['pid']);
                }

                return sql_connect($connector);
            }

            /*
             * Check if the tunnel process is still up and about
             */
            if(!cli_pid($connector['ssh_tunnel']['pid'])){
                throw new BException(tr('sql_connect(): SSH tunnel process ":pid" is gone', array(':pid' => $connector['ssh_tunnel']['pid'])), 'failed');
            }

            /*
             * Check if we can connect over the tunnel to the remote SSH
             */
            $results = inet_telnet(array('host' => '127.0.0.1',
                                         'port' => $connector['ssh_tunnel']['source_port']));

// :TODO: Implement further error handling.. From here on, appearently inet_telnet() did NOT cause an exception, so we have a result.. We can check the result for mysql server data and with that confirm that it is working, but what would.. well, cause a problem, because if everything worked we would not be here...

      default:
            throw new BException('sql_connect(): Failed to create PDO SQL object', $e);
    }

}catch(Exception $e){
    throw new BException(tr('sql_connect(): Failed'), $e);
}
?>
