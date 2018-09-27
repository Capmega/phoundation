<?php
/*
 * SSH library library
 *
 * This library contains functions to manage SSH accounts
 *
 * Copyright (c) 2018 Capmega
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @auhthor Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @return void
 */
function ssh_library_init(){
    try{
        load_config('ssh');

    }catch(Exception $e){
        throw new bException('ssh_library_init(): Failed', $e);
    }
}



/*
 * Executes the specified commands on the specified hostname. Supports passing through multiple SSH proxies
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $server
 * @params string hostname
 * @params string port (1 - 65535) [null]
 * @params string ssh_key alias for identity_file
 * @params string identity_file
 * @params string commands
 * @params string background
 * @params array proxies [null]
 * @param string $commands
 * @param boolean $background
 * @param string $function
 * @return array
 */
function ssh_exec($server, $commands = null, $background = false, $function = 'exec', $ok_exitcodes = 0){
    global $core;

    try{
        array_default($server, 'hostname'     , null);
        array_default($server, 'hostname'     , null);
        array_default($server, 'ssh_key'      , null);
        array_default($server, 'identity_file', $server['ssh_key']);
        array_default($server, 'commands'     , $commands);
        array_default($server, 'background'   , $background);
        array_default($server, 'proxies'      , null);

        /*
         * Validate that for hostnames we have a username and identity_file available
         */
        if(!empty($server['hostname'])){
            if(empty($server['username'])){
                throw new bException(tr('ssh_exec(): No username specified'), 'not-specified');
            }

            if(empty($server['identity_file'])){
                throw new bException(tr('ssh_exec(): No "ssh_key" or "identity_file" specified'), 'not-specified');
            }
        }

        /*
         * If no hostname is specified, then don't execute this command on a
         * remote server, just use safe_exec and execute it locally
         */
        if(!$server['hostname']){
            return safe_exec($server['commands'].($server['background'] ? ' &' : ''), $ok_exitcodes, true, $function);
        }

        /*
         * Build the SSH command
         * Execute the command
         */
        $command = ssh_build_command($server);
        $results = safe_exec($command, $ok_exitcodes, true, $function);

        /*
         * Remove SSH warning
         */
        if(!$server['background']){
            if(preg_match('/Warning: Permanently added \'\[.+?\]:\d{1,5}\' \(\w+\) to the list of known hosts\./', isset_get($results[0]))){
                /*
                 * Remove known host warning from results
                 */
                array_shift($results);
            }
        }

        if(!empty($server['tunnel'])){
            if(empty($server['tunnel']['persist'])){
                /*
                 * This SSH tunnel must be closed automatically once the script finishes
                 */
                log_file(tr('Created SSH tunnel ":source_port::target_hostname::target_port" to hostname ":hostname"', array(':hostname' => $server['hostname'], ':source_port' => $server['tunnel']['source_port'], ':target_hostname' => $server['tunnel']['target_hostname'], ':target_port' => $server['tunnel']['target_port'])));
                $core->register('shutdown_ssh_close_tunnel', $results);

            }else{
                log_file(tr('Created PERSISTENT SSH tunnel ":source_port::target_hostname::target_port" to hostname ":hostname"', array(':hostname' => $server['hostname'], ':source_port' => $server['tunnel']['source_port'], ':target_hostname' => $server['tunnel']['target_hostname'], ':target_port' => $server['tunnel']['target_port'])));
            }
        }

        return $results;

    }catch(Exception $e){
        /*
         * Remove "Permanently added host blah" error, even in this exception
         */
        $data = $e->getData();

        if(!empty($data[0])){
            if(preg_match('/Warning: Permanently added \'\[.+?\]:\d{1,5}\' \(\w+\) to the list of known hosts\./', $data[0])){
                /*
                 * Remove known host warning from results
                 */
                array_shift($data);
            }
        }

        unset($data);
        notify($e);

        /*
         * Try deleting the keyfile anyway!
         */
        try{
            if(!empty($key_file)){
                chmod($key_file, 0600);
                file_delete($key_file);
            }

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify($e);
        }

        throw new bException('ssh_exec(): Failed', $e);
    }
}



/*
 * Returns SSH connection string for the specified SSH connection parameters. Supports multiple SSH proxy servers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $server The server parameters required to build the SSH connection string
 * @params numeric port [1 - 65535] The port number for the remote host to connect to
 * @params string log [filename]
 * @params boolean no_command
 * @params boolean background
 * @params boolean remote_connect
 * @params string tunnel [1 - 65535]>[1 - 65535]
 * @params string identity_file [filename]
 * @params array options
 * @return string The connection string
 */
function ssh_build_command(&$server = null, $ssh_command = 'ssh'){
    global $_CONFIG;

    try{
        if(empty($server['hostname'])){
            throw new bException(tr('ssh_build_command(): No hostname specified'), 'not-specified');
        }

        /*
         * Get default SSH arguments and create basic SSH command with options
         */
        $server  = array_merge($_CONFIG['ssh']['arguments'], $server);
        $command = $ssh_command.ssh_build_options(isset_get($server['options']));

        /*
         * "tunnel" option requires (and automatically assumes) no_command, background, and remote_connect
         */
        if(!empty($server['tunnel'])){
            $server['background']      = true;
            $server['no_command']      = true;
            $server['remote_connect']  = true;
        }

        foreach($server as $parameter => &$value){
            switch($parameter){
                case 'options':
                    /*
                     * Options are processed in ssh_get_otions();
                     */
                    break;

                case 'port':
                    if($value){
                        if(!is_numeric($value) or ($value < 1) or ($value > 65535)){
                            if($value !== ':proxy_port'){
                                throw new bException(tr('ssh_build_command(): Specified port natural numeric value between 1 - 65535, but ":value" was specified', array(':value' => $value)), 'invalid');
                            }
                        }

                        switch($ssh_command){
                            case 'ssh':
                                // FALLTHROUGH
                            case 'autossh':
                                // FALLTHROUGH
                            case 'ssh-copy-id':
                                $command .= ' -p "'.$value.'"';
                                break;

                            case 'scp':
                                $command .= ' -P "'.$value.'"';
                                break;

                            default:
                                throw new bException(tr('ssh_build_command(): Unknown ssh command ":command" specified', array(':command' => $ssh_command)), 'command');
                        }
                    }

                    break;

                case 'log':
                    if($value){
                        if(!is_string($value)){
                            throw new bException(tr('ssh_build_command(): Specified option "log" requires string value containing the path to the identity file, but contains ":value"', array(':value' => $value)), 'invalid');
                        }

                        if(!file_exists($value)){
                            throw new bException(tr('ssh_build_command(): Specified log file directory ":path" does not exist', array(':file' => dirname($value))), 'not-exist');
                        }

                        $command .= ' -E "'.$value.'"';
                    }

                    break;

                case 'goto_background':
                    /*
                     * NOTE: This is not the same as shell background! This will execute SSH with one PID and then have it switch to an independant process with another PID!
                     */
                    if($value){
                        $command .= ' -f';
                    }

                    break;

                case 'remote_connect':
                    if($value){
                        $command .= ' -g';
                    }

                    break;

                case 'master':
                    if($value){
                        $command .= ' -M';
                    }

                    break;

                case 'no_command':
                    if($value){
                        $command .= ' -N';
                    }

                    break;

                case 'tunnel':
                    array_ensure ($value, 'source_port,target_port');
                    array_default($value, 'target_hostname', 'localhost');
                    array_default($value, 'persist'        , false);

                    if(!$value['persist'] and !empty($server['proxies'])){
                        throw new bException(tr('ssh_build_command(): A non persistent SSH tunnel with proxies was requested, but since SSH proxies will cause another SSH process with unknown PID, we will not be able to close them automatically. Use "persisten" for this tunnel or tunnel without proxies'), 'warning/invalid');
                    }

                    if(!is_natural($value['source_port']) or ($value['source_port'] > 65535)){
                        if(!$value['source_port']){
                            throw new bException(tr('ssh_build_command(): No source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                        }

                        throw new bException(tr('ssh_build_command(): Invalid source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                    }

                    if(!is_natural($value['target_port']) or ($value['target_port'] > 65535)){
                        if(!$value['target_port']){
                            throw new bException(tr('ssh_build_command(): No source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                        }

                        throw new bException(tr('ssh_build_command(): Invalid target_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                    }

                    if(!is_scalar($value['target_hostname']) or (strlen($value['target_hostname']) < 1) or (strlen($value['target_hostname']) > 253)){
                        if(!$value['target_hostname']){
                            throw new bException(tr('ssh_build_command(): No target_hostname specified for parameter "tunnel". Value should be the target hosts FQDN, IP, localhost, or host defined in the /etc/hosts of the target machine'), 'invalid');
                        }

                        throw new bException(tr('ssh_build_command(): Invalid target_hostname specified for parameter "tunnel". Value should be scalar, and >= 1 and <= 253 characters'), 'invalid');
                    }

                    $command .= ' -L '.$value['source_port'].':'.$value['target_hostname'].':'.$value['target_port'];
                    break;

                case 'identity_file':
                    if($value){
                        if(!is_string($value)){
                            throw new bException(tr('ssh_build_command(): Specified option "identity_file" requires string value containing the path to the identity file, but contains ":value"', array(':value' => $value)), 'invalid');
                        }

                        if(!file_exists($value)){
                            throw new bException(tr('ssh_build_command(): Specified identity file ":file" does not exist', array(':file' => $value)), 'not-exist');
                        }

                        $command .= ' -i "'.$value.'"';
                    }

                    break;

                case 'proxies':
// :TODO: Right now its assumed that every proxy uses the same SSH user and key file, though in practice, they MIGHT have different ones. Add support for each proxy server having its own user and keyfile
                    if(!$value){
                        break;
                    }

                    /*
                     * $value IS REFERENCED, DO NOT USE IT DIRECTLY HERE!
                     */
                    $proxies = $value;

                    /*
                     * ssh command line ProxyCommand example: -o ProxyCommand="ssh -p  -o ProxyCommand=\"ssh -p  40220 s1.s.ingiga.com nc s2.s.ingiga.com 40220\"  40220 s2.s.ingiga.com nc s3.s.ingiga.com 40220"
                     * To connect to this server, one must pass through a number of SSH proxies
                     */
                    if($proxies === ':proxy_template'){
                        /*
                         * We're building a proxy_template command, which itself as proxy template has just the string ":proxy_template"
                         */
                        $command .= ' :proxy_template';

                    }else{
                        $template             = $server;
                        $template['hostname'] = ':proxy_host';
                        $template['port']     = ':proxy_port';
                        $template['commands'] = 'nc :target_hostname :target_port';
                        $template['proxies']  = ':proxy_template';

//'ssh '.$server['timeout'].$server['arguments'].' -i '.$key_file.' -p :proxy_port :proxy_template '.$server['username'].'@:proxy_host nc :target_hostname :target_port';

                        $escapes        = 0;
                        $proxy_template = ' -o ProxyCommand="'.addslashes(ssh_build_command($template)).'" ';
                        $proxies_string = ':proxy_template';
                        $target_server  = $server['hostname'];
                        $target_port    = $server['port'];

                        foreach($proxies as $id => $proxy){
                            $proxy_string = $proxy_template;

                            for($escape = 0; $escape < $escapes; $escape++){
                                $proxy_string = addcslashes($proxy_string, '"\\');
                            }

                            /*
                             * Next proxy string needs more escapes
                             */
                            $escapes++;

                            /*
                             * Fill in proxy values for this proxy
                             */
                            $proxy_string   = str_replace(':proxy_port'     , $proxy['port']    , $proxy_string);
                            $proxy_string   = str_replace(':proxy_host'     , $proxy['hostname'], $proxy_string);
                            $proxy_string   = str_replace(':target_hostname', $target_server    , $proxy_string);
                            $proxy_string   = str_replace(':target_port'    , $target_port      , $proxy_string);
                            $proxies_string = str_replace(':proxy_template' , $proxy_string     , $proxies_string);

                            $target_server  = $proxy['hostname'];
                            $target_port    = $proxy['port'];

                            ssh_add_known_host($proxy['hostname'], $proxy['port']);
                        }

                        /*
                         * No more proxies, remove the template placeholder
                         */
                        $command .= str_replace(':proxy_template', '', $proxies_string);
                    }

                    break;

                case 'force_terminal':
                    if($value){
                        $command .= ' -t';
                    }

                case 'disable_terminal':
                    if(!empty($server['force_terminal'])){
                        throw new bException(tr('ssh_build_command(): Both "force_terminal" and "disable_terminal" were specified. These options are mutually exclusive, please use only one or the other'), 'invalid');
                    }

                    if($value){
                        $command .= ' -T';
                    }

                default:
                    /*
                     * Ignore any known parameter as specified $server list may contain parameters for other functions than the SSH library functions
                     */
            }

        }

        /*
         * Add the target server
         */
        $command .= ' "'.$server['hostname'].'"';

        if(isset_get($server['commands'])){
            $command .= ' "'.$server['commands'].'"';
        }

        if(isset_get($server['background'])){
            $command .= ' &';
        }

        return $command;

    }catch(Exception $e){
        throw new bException('ssh_build_command(): Failed', $e);
    }
}



/*
 * Returns SSH options string for the specified SSH options array
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $options The SSH options to be used to build the options string
 * @return array The validated parameter data
 */
function ssh_build_options($options = null){
    global $_CONFIG;

    try{
        /*
         * Get options from  default configuration and specified options
         */
        $string  = '';
        $options = array_merge($_CONFIG['ssh']['options'], $options);

        /*
         * Easy short cut to disable strict host key checks
         */
        if(isset($options['check_hostkey'])){
            if(!$options['check_hostkey']){
                $options['check_host_ip']            = false;
                $options['strict_host_key_checking'] = false;
            }

            unset($options['check_hostkey']);
        }

        /*
         * The known_hosts file for this user defaults to ROOT/data/ss/known_hosts
         */
        if(empty($options['user_known_hosts_file'])){
            $string .= ' -o UserKnownHostsFile="'.ROOT.'data/ssh/known_hosts"';

        }else{
            if($value){
                if(!is_string($value)){
                    throw new bException(tr('ssh_get_conect_string(): Specified option "user_known_hosts_file" requires a string value, but ":value" was specified', array(':value' => $value)), 'invalid');
                }

                $string .= ' -o UserKnownHostsFile="'.$value.'"';
            }

            unset($options['user_known_hosts_file']);
        }

        /*
         * Validate and apply each option
         */
        foreach($options as $option => $value){
            switch($option){
                case 'connect_timeout':
                    if($value){
                        if(!is_numeric($value)){
                            throw new bException(tr('ssh_get_conect_string(): Specified option "connect_timeout" requires a numeric value, but ":value" was specified', array(':value' => $value)), 'invalid');
                        }

                        $string .= ' -o ConnectTimeout="'.$value.'"';
                    }

                    break;

                case 'check_host_ip':
                    if(!is_bool($value)){
                        throw new bException(tr('ssh_get_conect_string(): Specified option "check_host_ip" requires a boolean value, but ":value" was specified', array(':value' => $value)), 'invalid');
                    }

                    $string .= ' -o CheckHostIP="'.get_yes_no($value).'"';
                    break;

                case 'strict_host_key_checking':
                    if(!is_bool($value)){
                        throw new bException(tr('ssh_get_conect_string(): Specified option "strict_host_key_checking" requires a boolean value, but ":value" was specified', array(':value' => $value)), 'invalid');
                    }

                    $string .= ' -o StrictHostKeyChecking="'.get_yes_no($value).'"';
                    break;

                default:
                    throw new bException(tr('ssh_build_options(): Unknown option ":option" specified', array(':option' => $option)), 'unknown');
            }
        }

        return $string;

    }catch(Exception $e){
        throw new bException('ssh_build_options(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_start_control_master($server, $socket = null){
    global $_CONFIG;

    try{
        load_libs('file');
        file_ensure_path(TMP);

        if(!$socket){
            $socket = file_temp();
        }

        if(ssh_get_control_master($socket)){
            return $socket;
        }

        $result = ssh_exec(array('hostname'  => $server['domain'],
                                 'port'      => $_CONFIG['cdn']['port'],
                                 'username'  => $server['username'],
                                 'ssh_key'   => ssh_get_key($server['username']),
                                 'arguments' => '-nNf -o ControlMaster=yes -o ControlPath='.$socket), ' 2>&1 >'.ROOT.'data/log/ssh_master');

        return $socket;

    }catch(Exception $e){
        throw new bException('ssh_start_control_master(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_get_control_master($socket = null){
    global $_CONFIG;

    try{
        $result = safe_exec('ps $(pgrep --full '.$socket.') | grep "ssh -nNf" | grep --invert-match pgrep', '0,1');
        $result = array_pop($result);

        preg_match_all('/^\s*\d+/', $result, $matches);

        $pid = array_pop($matches);
        $pid = (integer) array_pop($pid);

        return $pid;

    }catch(Exception $e){
        throw new bException('ssh_get_control_master(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_stop_control_master($socket = null){
    global $_CONFIG;

    try{
        $pid = ssh_get_control_master($socket);

        if(!posix_kill($pid, 15)){
            return posix_kill($pid, 9);
        }

        return true;

    }catch(Exception $e){
        throw new bException('ssh_stop_control_master(): Failed', $e);
    }
}



/*
 * SSH account validation
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $ssh
 * @return array the specified $ssh array validated and clean
 */
function ssh_validate_account($ssh){
    try{
        load_libs('validate');

        $v = new validate_form($ssh, 'name,username,ssh_key,description');
        $v->isNotEmpty ($ssh['name'], tr('No account name specified'));
        $v->hasMinChars($ssh['name'], 2, tr('Please ensure the account name has at least 2 characters'));
        $v->hasMaxChars($ssh['name'], 32, tr('Please ensure the account name has less than 32 characters'));

        $v->isNotEmpty ($ssh['username'], tr('No user name specified'));
        $v->hasMinChars($ssh['username'], 2, tr('Please ensure the user name has at least 2 characters'));
        $v->hasMaxChars($ssh['username'], 32, tr('Please ensure the user name has less than 32 characters'));

        $v->isNotEmpty ($ssh['ssh_key'], tr('No SSH key specified to the account'));

        $v->isNotEmpty ($ssh['description'], tr('No description specified'));
        $v->hasMinChars($ssh['description'], 2, tr('Please ensure the description has at least 2 characters'));

        if(is_numeric(substr($ssh['name'], 0, 1))){
            $v->setError(tr('Please ensure that the account name does not start with a number'));
        }

        $v->isValid();

        return $ssh;

    }catch(Exception $e){
        throw new bException(tr('ssh_validate_account(): Failed'), $e);
    }
}



/*
 * Returns SSH account data for the specified SSH accounts id
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $accounts_id The table ID for the account
 * @return array The account data for the specified $accounts_id
 */
function ssh_get_account($accounts_id){
    try{
        if(!$accounts_id){
            throw new bException(tr('ssh_get_account(): No accounts id specified'), 'not-specified');
        }

        if(!is_numeric($accounts_id)){
            throw new bException(tr('ssh_get_account(): Specified accounts id ":id" is not numeric', array(':id' => $accounts_id)), 'invalid');
        }

        $retval = sql_get('SELECT    `ssh_accounts`.`id`,
                                     `ssh_accounts`.`createdon`,
                                     `ssh_accounts`.`modifiedon`,
                                     `ssh_accounts`.`name`,
                                     `ssh_accounts`.`username`,
                                     `ssh_accounts`.`ssh_key`,
                                     `ssh_accounts`.`status`,
                                     `ssh_accounts`.`description`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`,
                                     `modifiedby`.`name`  AS `modifiedby_name`,
                                     `modifiedby`.`email` AS `modifiedby_email`

                           FROM      `ssh_accounts`

                           LEFT JOIN `users` AS `createdby`
                           ON        `ssh_accounts`.`createdby`  = `createdby`.`id`

                           LEFT JOIN `users` AS `modifiedby`
                           ON        `ssh_accounts`.`modifiedby` = `modifiedby`.`id`

                           WHERE     `ssh_accounts`.`id`         = :id',

                           array(':id' => $accounts_id));

        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_get_account(): Failed', $e);
    }
}



/*
 * Returns an SSH key for the specified username, if available
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $username The SSH username for which an SSH key must be returned
 * @return string The SSH key for the specified username
 */
function ssh_get_key($username){
    try{
        return sql_get('SELECT `ssh_key` FROM `ssh_accounts` WHERE `username` = :username', 'ssh_key', array(':username' => $username));

    }catch(Exception $e){
        throw new bException('ssh_get_key(): Failed', $e);
    }
}



/*
 * Create a safe SSH keyfile containing the specified SSH key
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $ssh_key The SSH key that must be placed in a keyfile
 * return string $key_file The created keyfile
 */
function ssh_create_key_file($ssh_key){
    try{
        /*
         * Ensure that ssh/keys directory exists and that its safe
         */
        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys', 0750);
        chmod(ROOT.'data/ssh', 0750);

        /*
         * Safely create SSH key file
         */
        $key_file = ROOT.'data/ssh/keys/'.str_random(8);

        touch($key_file);
        chmod($key_file, 0600);
        file_put_contents($key_file, $ssh_key, FILE_APPEND);
        chmod($key_file, 0400);

        return substr($key_file, -8, 8);

    }catch(Exception $e){
        throw new bException('ssh_create_key_file(): Failed', $e);
    }
}



/*
 * Delete the specified SSH key
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $key_file The SSH key file that must be deleted
 * return boolean True if the specified keyfile was deleted, false if no keyfile was specified
 */
function ssh_remove_key_file($key_file, $background = false){
    try{
        if(!$key_file){
            return false;
        }

        $key_file = ROOT.'data/ssh/keys/'.$key_file;

        if($background){
            safe_exec('{ sleep 5; sudo chmod 0600 '.$key_file.' ; sudo rm -rf '.$key_file.' ; } &');

        }else{
            chmod($key_file, 0600);
            file_delete($key_file);
        }

        return true;

    }catch(Exception $e){
        throw new bException('ssh_remove_key_file(): Failed', $e);
    }
}



/*
 *
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_add_known_host($hostname, $port){
    try{
        if(empty($hostname)){
            throw new bException(tr('ssh_add_known_host(): No hostname specified'), 'not-specified');
        }

        if(empty($port)){
            throw new bException(tr('ssh_add_known_host(): No port specified'), 'not-specified');
        }

        load_libs('file');
        file_ensure_path(ROOT.'data/ssh/keys/', 0750);

        $public_keys = safe_exec('ssh-keyscan -p '.$port.' -H '.$hostname);

        if(empty($public_keys)){
            throw new bException(tr('ssh_add_known_host(): ssh-keyscan found no public keys for hostname ":hostname"', array(':hostname' => $hostname)), 'not-found');
        }

        foreach($public_keys as $public_key){
            if(substr($public_key, 0, 1) != '#'){
                file_put_contents(ROOT.'data/ssh/known_hosts', $public_key."\n", FILE_APPEND);
            }
        }

        return count($public_keys);

    }catch(Exception $e){
        throw new bException('ssh_add_known_host(): Failed', $e);
    }
}



/*
 *
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_read_server_config($hostname){
    try{
        $retval = array();
        $config = servers_exec($hostname, 'cat /etc/ssh/sshd_config');

        foreach($config as $line){
            $key    = str_until($line, ' ');
            $values = str_from($line, ' ');

            $retval[$key] = $config;
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('ssh_read_server_config(): Failed', $e);
    }
}



/*
 *
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param
 */
function ssh_write_server_config($hostname, $config){
    try{
        foreach($config as $key => $value){
            $data = $key.' '.$value."\n";
        }

        servers_exec($hostname, 'sudo cat > /etc/ssh/sshd_config << EOF '.$data);

    }catch(Exception $e){
        throw new bException('ssh_write_server_config(): Failed', $e);
    }
}



/*
 * Validate sshd_config data
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $entries
 * @return array The validated data
 */
function ssh_validate_server_config($entries){
    try{
// :TODO: Implement

        return $entries;

    }catch(Exception $e){
        throw new bException('ssh_validate_server_config(): Failed', $e);
    }
}



/*
 *
 *
 * @Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $hostname
 * @param array $config
 */
function ssh_update_config($hostname, $config){
    try{
        $config        = ssh_validate_server_config($config);
        $server_config = ssh_read_server_config($hostname);

        foreach($config as $key => $values){
// :TODO: Just WTF was this in the first place?
            //$comments = '';
            //
            //if(isset($values['description'])){
            //    $comments = '#'.$values['description']."\n";
            //}
            //
            //$server_config[$key] = preg_replace('/'.$key.'\s+(\d+|\w+)|#'.$key.'\s+(\d+|\w+)/', $comments.$key." ".$values, $values);
            $server_config[$key] = $values;
        }

        ssh_write_server_config($hostname, $server_config);

        return $server_config;

    }catch(Exception $e){
        throw new bException('ssh_update_config(): Failed', $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $source
 * @param array $destnation
 * @return
 */
function ssh_cp($source, $target, $options = null){
    try{
under_construction();
        /*
         * If server was specified by just name, then lookup the server data in
         * the database
         */
        if(is_string($source)){
            /*
             * This source is a server specified by string with the source path in there.
             */
// :TODO: This may fail with files containing :
            if(strstr(':', $source)){
                $path   = str_from ($source, ':');
                $source = str_until($source, ':');
                $server = sql_get('SELECT    `ssh_accounts`.`username`,
                                             `ssh_accounts`.`ssh_key`,
                                             `servers`.`id`,
                                             `servers`.`hostname`,
                                             `servers`.`port`

                                   FROM      `servers`

                                   LEFT JOIN `ssh_accounts`
                                   ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                   WHERE     `servers`.`hostname` = :hostname',

                                   array(':hostname' => $source));

                if(!$server){
                    throw new bException(tr('ssh_cp(): Specified server ":server" does not exist', array(':server' => $source)), 'not-exist');
                }

                $source         = $server;
                $target['path'] = $path;
            }

        }else{
            /*
             * This source is a server
             */
            array_ensure($source, 'server,hostname,ssh_key,port,check_hostkey,arguments,path');
        }

        if(is_string($target)){
// :TODO: This may fail with files containing :
            if(strstr(':', $target)){
                if(is_array($source)){
                    throw new bException(tr('ssh_cp(): Specified source ":source" and target ":target" are both servers. This function can only copy from local to server or server to local', array(':source' => $source, ':target' => $target, )), 'invalid');
                }

                $path   = str_from ($target, ':');
                $target = str_until($target, ':');
                $server = sql_get('SELECT    `ssh_accounts`.`username`,
                                               `ssh_accounts`.`ssh_key`,
                                               `servers`.`id`,
                                               `servers`.`hostname`,
                                               `servers`.`port`

                                     FROM      `servers`

                                     LEFT JOIN `ssh_accounts`
                                     ON        `ssh_accounts`.`id`  = `servers`.`ssh_accounts_id`

                                     WHERE     `servers`.`hostname` = :hostname',

                                     array(':hostname' => $target));

                if(!$server){
                    throw new bException(tr('ssh_cp(): Specified target server ":server" does not exist', array(':server' => $target)), 'not-exist');
                }

                $target         = $server;
                $target['path'] = $path;
            }

        }else{
            array_ensure($target, 'server,hostname,ssh_key,port,check_hostkey,arguments');
        }

        $server = array('options' => $options);
        ssh_build_command($server, 'scp');

        if($options){

        }

        if(!$server['check_hostkey']){
            $server['arguments'] .= ' -o StrictHostKeyChecking=no -o UserKnownHostsFile='.ROOT.'data/ssh/known_hosts ';
        }

        /*
         * Safely create SSH key file from the server ssh key
         */
        $key_file = ssh_create_key_file($server['ssh_key']);

        /*
         * ????
         */
        if($from_server){
            $command = $server['username'].'@'.$server['hostname'].':'.$source.' '.$destnation;

        }else{
            $command = $source.' '.$server['username'].'@'.$server['hostname'].':'.$destnation;
        }

        /*
         * Execute command
         */
        $result = safe_exec('scp '.$server['arguments'].' -P '.$server['port'].' -i '.$key_file.' '.$command.'');
        ssh_remove_key_file($key_file);

        return $result;

    }catch(Exception $e){
        notify($e);

        /*
         * Try deleting the keyfile anyway!
         */
        try{
            ssh_remove_key_file(isset_get($key_file));

        }catch(Exception $e){
            /*
             * Cannot be deleted, just ignore and notify
             */
            notify($e);
        }

        throw new bException(tr('ssh_cp(): Failed'), $e);
    }
}



/*
 * Set up an SSH tunnel
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @exception Throws an exception if the ssh command does not return exit code 0
 *
 * @param array $params
 * @params string $hostname The hostname where SSH should connect to
 * @params string $local_port The port on the local server where SSH should listen to 1-65535
 * @params string $remote_port The port on the remote server where SSH should connect to 1-65535
 * @params array $options The required SSH options
 * @return void
 */
function ssh_tunnel($params){
    try{
        array_ensure ($params, 'hostname,source_port,target_port');
        array_default($params, 'tunnel', 'localhost');

        $params['tunnel'] = array('source_port'     => $params['source_port'],
                                  'target_hostname' => $params['target_hostname'],
                                  'target_port'     => $params['target_port']);

        return ssh_exec($params);

    }catch(Exception $e){
        throw new bException('ssh_tunnel(): Failed', $e);
    }
}



/*
 * Close SSH tunnel with the specified PID
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $pid
 * @return void
 */
function ssh_close_tunnel($pid){
    try{
        load_libs('cli');
        cli_kill($pid);

    }catch(Exception $e){
        throw new bException('ssh_close_tunnel(): Failed', $e);
    }
}
?>