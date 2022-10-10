<?php
/*
 * SSH library
 *
 * This library contains functions to manage SSH accounts
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package ssh
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @return void
 */
function ssh_library_init() {
    try {
        load_config('ssh');
        load_libs('cli');
        file_ensure_path(ROOT.'data/run/ssh');

    }catch(Exception $e) {
        throw new CoreException('ssh_library_init(): Failed', $e);
    }
}



/*
 * Executes the specified commands on the specified domain. Supports passing through multiple SSH proxies
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array  $params
 * @param string $params[domain]
 * @param string $params[port] (1 - 65535) [null]
 * @param string $params[ssh_key] alias for identity_file
 * @param string $params[identity_file]
 * @param string $params[commands]
 * @param array  $params[proxies] [null]
 * @param string $ok_exitcodes Time in seconds after which the program execution will be automatically aborted. 0 for no limit, defaults to $_CONFIG[exec][timeout]
 * @return array The results of the executed SSH commands in an array, each entry containing one line of the output
 */
function ssh_exec($server, $params) {
    global $core, $_CONFIG;
    static $retry = 0;

    try {
        if (!is_array($params)) {
            throw new CoreException(tr('ssh_exec(): Invalid command parameters ":params" specified, should be a parameter array containing a "commands" key', array(':params' => $params)), 'invalid');
        }

        if ($retry > 1) {
            throw new CoreException(tr('ssh_exec(): Command ":command" retried ":retry" times, command failed', array(':command' => isset_get($params['commands']), ':retry' => $retry)), 'failed');
        }

        Arrays::ensure($params, 'domain,port,commands');
        array_default($params, 'output_log'        , (VERBOSE ? ROOT.'data/log/syslog' : '/dev/null'));
        array_default($params, 'include_ssh_errors', true);

        /*
         * If no domain is specified, then don't execute this command on a
         * remote server, just use safe_exec and execute it locally
         */
        if (empty($server) or empty($server['domain'])) {
            $retry = 0;
            return safe_exec($params);
        }

        /*
         * Ensure we have valid login credentials
         */
        if (empty($server['identity_file']) and empty($server['password'])) {
            throw new CoreException(tr('ssh_exec(): No identity file or password specified'), 'invalid');
        }

        /*
         * Build the command line
         * Also detect background mode (Will be set in $params)
         */
        $background           = isset_get($params['background']);
        $params['background'] = false;

        if ($params['commands']) {
            $params['commands'] = cli_build_commands_string($params);

        } else {
            /*
             * No individual commands were specified. This is okay if we're
             * setting up an SSH tunnel
             */
            if (empty($params['tunnel'])) {
                throw new CoreException(tr('ssh_exec(): No shell commands or SSH tunnel parameter specified'), 'not-specified');
            }
        }

        /*
         * Build the SSH command
         */
        $params['commands']   = ssh_build_command($server, $params);
        $params['background'] = $params['background'] or $background;

        /*
         * Background task? Then the SSH command itself must be background too
         * and return its PID
         */
        if ($params['background']) {
            $params['commands'] .= ' >> '.$params['output_log'].' 2>&1 3>&1 & echo $! ';

        } elseif ($params['include_ssh_errors']) {
            /*
             * Output will contain SSH errors
             */
            $params['commands'] .= ' 2>&1 ';
        }

        /*
         * Execute this entire SSH command
         */
        unset($params['domain']);
        $results = safe_exec($params);

        /*
         * Remove SSH warning
         */
        if (is_array($results)) {
            if (preg_match('/Warning: Permanently added \'\[.+?\]:\d{1,5}\' \(\w+\) to the list of known hosts\./', isset_get($results[0]))) {
                /*
                 * Remove known host warning from results
                 */
                array_shift($results);
            }
        }

        if (!empty($params['tunnel'])) {
            if (empty($params['tunnel']['persist'])) {
// :TODO: Add persist timeouts, so that these SSL tunnels can still be closed after X amount of time
                /*
                 * This SSH tunnel must be closed automatically once the script finishes
                 */
                Core::readRegister('shutdown_ssh_close_tunnel', $results);

            } else {

                log_console(tr('Created PERSISTENT SSH tunnel ":source_port::target_domain::target_port" to domain ":domain"', array(':domain' => isset_get($server['domain']), ':source_port' => $params['tunnel']['source_port'], ':target_domain' => $params['tunnel']['target_domain'], ':target_port' => $params['tunnel']['target_port'])));
            }
        }

        $retry = 0;
        return $results;

    }catch(Exception $e) {
        switch ($e->getCode()) {
            case 'not-exists':
                // no-break
            case 'invalid':
                break;

            default:
                /*
                 * Check if access was plainly denied because of missing SSH key
                 */
                $data = $e->getData();

                if ($data) {
                    $data = Arrays::force($data);
                    $data = array_pop($data);
                    $data = strtolower(trim($data));

                    if (str_contains($data, 'permission denied')) {
                        if (strtolower(substr($data, 0, 5)) !== 'bash:') {
                            $e = new CoreException(tr('ssh_exec(): Got access denied when trying to connect to server ":server"', array(':server' => $server['domain'])), $e);
                            $e->setCode('access-denied');
                            throw $e->makeWarning(true);
                        }
                    }

                    if (str_contains($data, 'host key verification failed')) {
                        $known = ssh_host_is_known($server['domain'], $server['port']);

                        if (!$known) {
                            /*
                             * There are no fingerprints availabe in either the
                             * `ssh_fingerprints` table or known_hosts file
                             */
                            $e->setCode('host-verification-failed');
                            throw new CoreException(tr('ssh_exec(): The domain ":domain" has no fingerprints available in neither the known_hosts file nor `ssh_fingerprints`', array(':domain' => $server['domain'])), $e);

                        } elseif (is_numeric($known)) {
                            /*
                             * There are no fingerprints availabe in the known_hosts
                             * file, but they were in the `ssh_fingerprints` table.
                             * The fingerprints have been added to the known_hosts
                             * file so we can retry the command.
                             */
                            log_console(tr('Retrying execution of command ":command"', array(':command' => $params['commands'])), 'yellow');
                            return ssh_exec($server, $params);
                        }
                    }
                }

                try {
                    /*
                     * Check if SSH can connect to the specified server / port
                     */
                    if (empty($not_check_inet) and isset($params['port'])) {
                        try {
                            load_libs('inet');
                            inet_test_host_port(array('host' => $server['domain'],
                                                      'port' => $server['port']));

                        }catch(Exception $f) {
                            throw new CoreException(tr('ssh_exec(): inet_test_host_port() failed with ":e"', array(':e' => $f->getMessage())), $e);
                        }
                    }

                }catch(CoreException $f) {
                    $f = new CoreException(tr('ssh_exec(): Failed to auto resolve ssh_exec() exception ":e"', array(':e' => $e)), $f);
                    notify($f);
                    throw  $f;
                }
        }

        /*
         * Remove "Permanently added host blah" error, even in this exception
         */
        throw new CoreException('ssh_exec(): Failed', $e);
    }
}



/*
 * Returns SSH connection string for the specified SSH connection parameters.
 * Supports multiple SSH proxy servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function ssh_build_command($server, &$params) {
    global $_CONFIG;

    try {
        Arrays::ensure($params);
        array_default($params, 'ssh_command'   , 'ssh');
        array_default($params, 'no_user_server', false);

        /*
         * Validate minimum requirements
         */
        if (empty($server['domain'])) {
            throw new CoreException(tr('ssh_build_command(): No required domain specified'), 'not-specified');
        }

        if (empty($server['username'])) {
            if (!$server['no_user_server']) {
                throw new CoreException(tr('ssh_build_command(): No required username specified'), 'not-specified');
            }
        }

        if (empty($server['identity_file'])) {
            throw new CoreException(tr('ssh_build_command(): No required identity_file specified'), 'not-specified');
        }

        /*
         * Get default SSH arguments and create basic SSH command with options
         */
        $params = array_merge_null($_CONFIG['ssh']['arguments'], $params);

        if (empty($server['password'])) {
            $command = $params['ssh_command'].ssh_build_options(isset_get($params['options']));

        } else {
            $command .= ' SSHPASS="$PASSWORD" sshpass -e '.$params['ssh_command'].ssh_build_options(isset_get($params['options']));
showdie($command);
        }

        /*
         * "tunnel" option requires (and automatically assumes) no_command, background, and remote_connect
         */
        if (!empty($params['tunnel'])) {
//            $params['goto_background'] = true;
            $params['background']      = true;
            $params['no_command']      = true;
            $params['remote_connect']  = true;
        }

        /*
         * Check if ControlMasters are already running for this connection
         */
        if (file_exists(ROOT.'data/run/ssh/'.$server['username'].'@'.$server['domain'].':'.$server['port'].(isset_get($params['tunnel']) ? 'T' : ''))) {
            /*
             * A master is already  running, so this connection should NOT be a
             * master, just reuse the existing one
             */
            $master = 'no';

        } else {
            /*
             * No master is running yet, so if a persistent connection was
             * requested, this has to be a master
             */
            $master = 'yes';
        }

        /*
         * Process command parameters
         */
        foreach ($params as $parameter => &$value) {
            switch ($parameter) {
                case 'options':
                    /*
                     * Options are processed in ssh_get_otions();
                     */
                    break;

                case 'log':
                    if ($value) {
                        if (!is_string($value)) {
                            throw new CoreException(tr('ssh_build_command(): Specified option "log" requires string value containing the path to the identity file, but contains ":value"', array(':value' => $value)), 'invalid');
                        }

                        if (!file_exists($value)) {
                            throw new CoreException(tr('ssh_build_command(): Specified log file directory ":path" does not exist', array(':file' => dirname($value))), 'not-exists');
                        }

                        $command .= ' -E "'.$value.'"';
                    }

                    break;

                case 'goto_background':
                    /*
                     * Run this SSH connection in the background.
                     *
                     * NOTE: This is not the same as shell background! This will
                     * execute SSH with one PID and then have it switch to an
                     * independant process with another PID!
                     *
                     * NOTE: Though in a bash shell, running SSH -fN will run
                     * SSH in the background and immediately return command
                     * prompt, this does NOT work in PHP, it will hang instead!
                     */
                    if ($value) {
                        $command .= ' -f';
                    }

                    break;

                case 'remote_connect':
                    if ($value) {
                        $command .= ' -g';
                    }

                    break;

                //case 'master':
                //    /*
                //     * Setup a reusable master connection
                //     */
                //    if ($value) {
                //        $command .= ' -M';
                //    }
                //
                //    break;

                case 'no_command':
                    /*
                     * Do not execute a command, this is useful to setup SSH
                     * tunnels, for example.
                     */
                    if ($value) {
                        $command .= ' -N';
                    }

                    break;

                case 'tunnel':
                    if (!$value) break;

                    /*
                     * Configure SSH connection to create an SSH tunnel to the
                     * specified server, and on the server connect it to the
                     * specified domain / IP :port
                     */
                    array_ensure ($value, 'source_port,target_port');
                    array_default($value, 'target_domain', 'localhost');
                    array_default($value, 'persist'      , false);

                    /*
                     * Validate variables
                     */
                    if (!$value['persist'] and !empty($server['proxies'])) {
                        throw new CoreException(tr('ssh_build_command(): A non persistent SSH tunnel with proxies was requested, but since SSH proxies will cause another SSH process with unknown PID, we will not be able to close them automatically. Use "persisten" for this tunnel or tunnel without proxies'), 'warning/invalid');
                    }

                    if (!is_natural($value['source_port']) or ($value['source_port'] > 65535)) {
                        if (!$value['source_port']) {
                            throw new CoreException(tr('ssh_build_command(): No source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                        }

                        throw new CoreException(tr('ssh_build_command(): Invalid source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                    }

                    if (!is_natural($value['target_port']) or ($value['target_port'] > 65535)) {
                        if (!$value['target_port']) {
                            throw new CoreException(tr('ssh_build_command(): No source_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                        }

                        throw new CoreException(tr('ssh_build_command(): Invalid target_port specified for parameter "tunnel". Value should be 1-65535'), 'invalid');
                    }

                    if (!is_scalar($value['target_domain']) or (strlen($value['target_domain']) < 1) or (strlen($value['target_domain']) > 253)) {
                        if (!$value['target_domain']) {
                            throw new CoreException(tr('ssh_build_command(): No target_domain specified for parameter "tunnel". Value should be the target hosts FQDN, IP, localhost, or host defined in the /etc/hosts of the target machine'), 'invalid');
                        }

                        throw new CoreException(tr('ssh_build_command(): Invalid target_domain specified for parameter "tunnel". Value should be scalar, and between 1 and 253 characters'), 'invalid');
                    }

                    /*
                     * Build command
                     */
                    $command .= ' -L '.$value['source_port'].':'.$value['target_domain'].':'.$value['target_port'];
                    break;

                case 'force_terminal':
                    if ($value) {
                        $command .= ' -t';
                    }

                    break;

                case 'disable_terminal':
                    if ($value) {
                        if (!empty($server['force_terminal'])) {
                            throw new CoreException(tr('ssh_build_command(): Both "force_terminal" and "disable_terminal" were specified. These options are mutually exclusive, please use only one or the other'), 'invalid');
                        }

                        $command .= ' -T';
                    }

                    break;

                case 'persist':
                    if (!$value) break;
                    $command .= ' -o ControlMaster='.$master.' ';
                    break;

                default:
                    /*
                     * Ignore any known parameter as specified $server list may contain parameters for other functions than the SSH library functions
                     */
            }
        }

        /*
         * Process server specific parameters
         */
        if ($server['port']) {
            if (!is_numeric($server['port']) or ($server['port'] < 1) or ($server['port'] > 65535)) {
                if ($server['port'] !== ':proxy_port') {
                    throw new CoreException(tr('ssh_build_command(): Specified port natural numeric value between 1 - 65535, but ":value" was specified', array(':value' => $server['port'])), 'invalid');
                }
            }

            switch ($params['ssh_command']) {
                case 'ssh':
                    // no-break
                case 'autossh':
                    // no-break
                case 'ssh-copy-id':
                    $command .= ' -p "'.$server['port'].'"';
                    break;

                case 'scp':
                    $command .= ' -P "'.$server['port'].'"';
                    break;

                default:
                    throw new CoreException(tr('ssh_build_command(): Unknown ssh command ":command" specified', array(':command' => $params['ssh_command'])), 'command');
            }
        }

        if ($server['identity_file']) {
            /*
             * Use the specified identity_file for the SSH connection
             */
            if ($server['identity_file']) {
                if (!is_string($server['identity_file'])) {
                    throw new CoreException(tr('ssh_build_command(): Specified option "identity_file" requires string value containing the path to the identity file, but contains ":value"', array(':value' => $server['identity_file'])), 'invalid');
                }

                if (!file_exists($server['identity_file'])) {
                    throw new CoreException(tr('ssh_build_command(): Specified identity file ":file" does not exist', array(':file' => $server['identity_file'])), 'not-exists');
                }

                $command .= ' -i "'.$server['identity_file'].'"';
            }
        }

        if (isset_get($server['proxies'])) {
            /*
             * Configure connection to pass over one or multiple
             * proxies. Each proxy can require a different SSH port
             */
// :TODO: Right now its assumed that every proxy uses the same SSH user and key file, though in practice, they MIGHT have different ones. Add support for each proxy server having its own user and keyfile

            /*
             * $server['proxies'] IS REFERENCED, DO NOT USE IT DIRECTLY HERE!
             */
            $proxies = $server['proxies'];

            /*
             * ssh command line ProxyCommand example: -o ProxyCommand="ssh -p  -o ProxyCommand=\"ssh -p  40220 s1.s.ingiga.com nc s2.s.ingiga.com 40220\"  40220 s2.s.ingiga.com nc s3.s.ingiga.com 40220"
             * To connect to this server, one must pass through a number of SSH proxies
             */
            if ($proxies === ':proxy_template') {
                /*
                 * We're building a proxy_template command, which itself as proxy template has just the string ":proxy_template"
                 */
                $command .= ' :proxy_template';

            } else {
                $template             = $server;
                $template['domain']   = ':proxy_host';
                $template['port']     = ':proxy_port';
                $template['commands'] = 'nc :target_domain :target_port';
                $template['proxies']  = ':proxy_template';

//'ssh '.$server['timeout'].$server['arguments'].' -i '.$identity_file.' -p :proxy_port :proxy_template '.$server['username'].'@:proxy_host nc :target_domain :target_port';

                $escapes        = 0;
                $proxy_template = ' -o ProxyCommand="'.addslashes(ssh_build_command($template)).'" ';
                $proxies_string = ':proxy_template';
                $target_server  = $server['domain'];
                $target_port    = $server['port'];

                foreach ($proxies as $id => $proxy) {
                    $proxy_string = $proxy_template;

                    for($escape = 0; $escape < $escapes; $escape++) {
                        $proxy_string = addcslashes($proxy_string, '"\\');
                    }

                    /*
                     * Next proxy string needs more escapes
                     */
                    $escapes++;

                    /*
                     * Fill in proxy values for this proxy
                     */
                    $proxy_string   = str_replace(':proxy_port'    , $proxy['port']  , $proxy_string);
                    $proxy_string   = str_replace(':proxy_host'    , $proxy['domain'], $proxy_string);
                    $proxy_string   = str_replace(':target_domain' , $target_server  , $proxy_string);
                    $proxy_string   = str_replace(':target_port'   , $target_port    , $proxy_string);
                    $proxies_string = str_replace(':proxy_template', $proxy_string   , $proxies_string);

                    $target_server  = $proxy['domain'];
                    $target_port    = $proxy['port'];

// :TODO: WHy is ssh_add_known_host used here????
                    ssh_add_known_host($proxy['domain'], $proxy['port']);
                }

                /*
                 * No more proxies, remove the template placeholder
                 */
                $command .= str_replace(':proxy_template', '', $proxies_string);
            }
        }

        /*
         * Always check for persistent connections
         */
        $command .= ' -o ControlPersist='.$_CONFIG['ssh']['persist']['timeout'].' -o ControlPath="'.ROOT.'data/run/ssh/'.$server['username'].'@'.$server['domain'].':'.$server['port'].(isset_get($params['tunnel']) ? 'T' : '').'" ';

        /*
         * Add the user@server, if allowed
         */
        if (!$params['no_user_server']) {
            $command .= ' "'.$server['username'].'@'.$server['domain'].'"';
        }

        if (isset_get($params['commands'])) {
            $command .= ' "'.$params['commands'].'"';
        }

        if (isset_get($params['background'])) {
            /*
             * Background commands do STDOUT to /dev/null to ensure PHP won't
             * hang waiting for output even on background
             */
            $command .= ' > /dev/null &';
        }

        return $command;

    }catch(Exception $e) {
        throw new CoreException('ssh_build_command(): Failed', $e);
    }
}



/*
 * Returns SSH options string for the specified SSH options array
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param array $options The SSH options to be used to build the options string
 * @return array The validated parameter data
 */
function ssh_build_options($options = null) {
    global $_CONFIG;

    try {
        /*
         * Get options from  default configuration and specified options
         */
        if ($options) {
            $string  = '';
            $options = array_merge($_CONFIG['ssh']['options'], $options);

        } else {
            $string  = '';
            $options = $_CONFIG['ssh']['options'];
        }

        /*
         * Easy short cut to disable strict host key checks
         */
        if (isset($options['check_hostkey'])) {
            if (!$options['check_hostkey']) {
                $options['check_host_ip']            = false;
                $options['strict_host_key_checking'] = false;
            }

            unset($options['check_hostkey']);
        }

        /*
         * The known_hosts file for this user defaults to ROOT/data/ss/known_hosts
         */
        if (empty($options['user_known_hosts_file'])) {
            $string .= ' -o UserKnownHostsFile="'.ROOT.'data/ssh/known_hosts"';

        } else {
            if ($value) {
                if (!is_string($value)) {
                    throw new CoreException(tr('ssh_get_conect_string(): Specified option "user_known_hosts_file" requires a string value, but ":value" was specified', array(':value' => $value)), 'invalid');
                }

                $string .= ' -o UserKnownHostsFile="'.$value.'"';
            }

            unset($options['user_known_hosts_file']);
        }

        /*
         * Validate and apply each option
         */
        foreach ($options as $option => $value) {
            switch ($option) {
                case 'connect_timeout':
                    if ($value) {
                        if (!is_numeric($value)) {
                            throw new CoreException(tr('ssh_get_conect_string(): Specified option "connect_timeout" requires a numeric value, but ":value" was specified', array(':value' => $value)), 'invalid');
                        }

                        $string .= ' -o ConnectTimeout="'.$value.'"';
                    }

                    break;

                case 'check_host_ip':
                    if (!is_bool($value)) {
                        throw new CoreException(tr('ssh_get_conect_string(): Specified option "check_host_ip" requires a boolean value, but ":value" was specified', array(':value' => $value)), 'invalid');
                    }

                    $string .= ' -o CheckHostIP="'.get_yes_no($value).'"';
                    break;

                case 'strict_host_key_checking':
                    if (!is_bool($value)) {
                        throw new CoreException(tr('ssh_get_conect_string(): Specified option "strict_host_key_checking" requires a boolean value, but ":value" was specified', array(':value' => $value)), 'invalid');
                    }

                    $string .= ' -o StrictHostKeyChecking="'.get_yes_no($value).'"';
                    break;

                default:
                    throw new CoreException(tr('ssh_build_options(): Unknown option ":option" specified', array(':option' => $option)), 'unknown');
            }
        }

        return $string;

    }catch(Exception $e) {
        throw new CoreException('ssh_build_options(): Failed', $e);
    }
}



///*
// *
// *
// * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
// * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package ssh
// *
// * @param
// */
//function ssh_start_control_master($server, $socket = null) {
//    global $_CONFIG;
//
//    try {
//        file_ensure_path(TMP);
//
//        if (!$socket) {
//            $socket = file_temp();
//        }
//
//        if (ssh_get_control_master($socket)) {
//            return $socket;
//        }
//
//        $result = ssh_exec(array('domain'    => $server['domain'],
//                                 'port'      => $_CONFIG['cdn']['port'],
//                                 'username'  => $server['username'],
//                                 'ssh_key'   => ssh_get_key($server['username']),
//                                 'arguments' => '-nNf -o ControlMaster=yes -o ControlPath='.$socket), ' 2>&1 >'.ROOT.'data/log/ssh_master');
//
//        return $socket;
//
//    }catch(Exception $e) {
//        throw new CoreException('ssh_start_control_master(): Failed', $e);
//    }
//}
//
//
//
///*
// *
// *
// * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
// * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package ssh
// *
// * @param
// */
//function ssh_get_control_master($socket = null) {
//    global $_CONFIG;
//
//    try {
//        $result = safe_exec('ps $(pgrep --full '.$socket.') | grep "ssh -nNf" | grep --invert-match pgrep', '0,1');
//        $result = array_pop($result);
//
//        preg_match_all('/^\s*\d+/', $result, $matches);
//
//        $pid = array_pop($matches);
//        $pid = (integer) array_pop($pid);
//
//        return $pid;
//
//    }catch(Exception $e) {
//        throw new CoreException('ssh_get_control_master(): Failed', $e);
//    }
//}
//
//
//
///*
// *
// *
// * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
// * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
// * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
// * @category Function reference
// * @package ssh
// *
// * @param
// */
//function ssh_stop_control_master($socket = null) {
//    global $_CONFIG;
//
//    try {
//        $pid = ssh_get_control_master($socket);
//
//        if (!posix_kill($pid, 15)) {
//            return posix_kill($pid, 9);
//        }
//
//        return true;
//
//    }catch(Exception $e) {
//        throw new CoreException('ssh_stop_control_master(): Failed', $e);
//    }
//}



/*
 * Insert the specified SSH account into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_validate_account()
 * @see ssh_update_account()
 * @version 2.4.60: Added function and documentation
 *
 * @param params $account The ssh account to be added
 * @return array The specified $account, validated and sanitized with the accounts id added
 */
function ssh_insert_account($account) {
    try {
        $account = ssh_validate_account($account);

        $account['seoname'] = seo_string($account['name']);

        sql_query('INSERT INTO `ssh_accounts` (`createdby`, `meta_id`, `name`, `seoname`, `ssh_key`, `description`, `username`)
                   VALUES                     (:createdby , :meta_id , :name , :seoname , :ssh_key , :description , :username )',

                   array(':createdby'   => $_SESSION['user']['id'],
                         ':meta_id'     => meta_action(),
                         ':name'        => $account['name'],
                         ':seoname'     => $account['seoname'],
                         ':ssh_key'     => $account['ssh_key'],
                         ':description' => $account['description'],
                         ':username'    => $account['username']));

        $account['id'] = ssh_insert_id();

        return $account;

    }catch(Exception $e) {
        throw new CoreException('ssh_insert_account(): Failed', $e);
    }
}



/*
 * Update the specified SSH account
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_insert_account()
 * @see ssh_validate_account()
 * @version 2.4.60: Added function and documentation
 *
 * @param params $account The ssh account to be added
 * @return array The specified $account, validated and sanitized
 */
function ssh_update_account($account) {
    try {
        $account = ssh_validate_account($account);

        meta_action($account['id'], 'update');

        sql_query('UPDATE `ssh_accounts`

                   SET    `name`        = :name,
                          `seoname`     = :seoname,
                          `ssh_key`     = :ssh_key,
                          `username`    = :username,
                          `description` = :description

                   WHERE  `id`          = :id',

                   array(':id'          => $ssh['id'],
                         ':name'        => $ssh['name'],
                         ':seoname'     => $ssh['seoname'],
                         ':ssh_key'     => $ssh['ssh_key'],
                         ':username'    => $ssh['username'],
                         ':description' => $ssh['description']));

        return $account;

    }catch(Exception $e) {
        throw new CoreException('template_function(): Failed', $e);
    }
}



/*
 * SSH account validation
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_insert_account()
 * @see ssh_update_account()
 *
 * @param array $account
 * @return array the specified $account array validated and clean
 */
function ssh_validate_account($account) {
    try {
        load_libs('validate,seo');

        $v = new ValidateForm($account, 'name,username,ssh_key,description');
        $v->isNotEmpty ($account['name'], tr('No account name specified'));
        $v->hasMinChars($account['name'], 2, tr('Please ensure the account name has at least 2 characters'));
        $v->hasMaxChars($account['name'], 32, tr('Please ensure the account name has less than 32 characters'));

        $v->isNotEmpty ($account['username'], tr('No user name specified'));
        $v->hasMinChars($account['username'], 2, tr('Please ensure the user name has at least 2 characters'));
        $v->hasMaxChars($account['username'], 32, tr('Please ensure the user name has less than 32 characters'));

        $v->isNotEmpty ($account['ssh_key'], tr('No SSH key specified to the account'));

        $v->isNotEmpty ($account['description'], tr('No description specified'));
        $v->hasMinChars($account['description'], 2, tr('Please ensure the description has at least 2 characters'));

        if (is_numeric(substr($account['name'], 0, 1))) {
            $v->setError(tr('Please ensure that the account name does not start with a number'));
        }

        $v->isValid();

        $exists = sql_get('SELECT `id` FROM `ssh_accounts` WHERE (`name` = :name OR `username` = :username) AND `id` != :id LIMIT 1', true, array(':name' => $account['name'], ':username' => $account['username'], ':id' => isset_get($account['id'], 0)), 'core');

        if ($exists) {
            $v->setError(tr('An SSH account with the username ":username" or name ":name" already exists', array(':name' => $account['name'], ':username' => $account['username'])));
        }

        $v->isValid();

        $account['seoname'] = seo_unique($account['name'], 'ssh_accounts', isset_get($account['id']));

        return $account;

    }catch(Exception $e) {
        throw new CoreException(tr('ssh_validate_account(): Failed'), $e);
    }
}



/*
 * Returns SSH account data for the specified SSH accounts id
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param natural $accounts_id The table ID for the account
 * @return array The account data for the specified $accounts_id
 */
function ssh_get_account($account) {
    try {
        if (!$account) {
            throw new CoreException(tr('ssh_get_account(): No accounts id specified'), 'not-specified');
        }

        if (!is_numeric($account)) {
            if (!is_string($account)) {
                throw new CoreException(tr('ssh_get_account(): Specified account ":account" should be either a numeric accounts id or an accounts name string', array(':account' => $account)), 'invalid');
            }

            $where   = ' WHERE `ssh_accounts`.`seoname`  = :seoname
                         OR    `ssh_accounts`.`username` = :username
                         OR    `ssh_accounts`.`name`     = :name';

            $execute = array(':name'     => $account,
                             ':seoname'  => $account,
                             ':username' => $account);

        } else {
            $where   = ' WHERE `ssh_accounts`.`id` = :id';
            $execute = array(':id' => $account);
        }

        $retval = sql_get('SELECT    `ssh_accounts`.`id`,
                                     `ssh_accounts`.`createdon`,
                                     `ssh_accounts`.`meta_id`,
                                     `ssh_accounts`.`name`,
                                     `ssh_accounts`.`username`,
                                     `ssh_accounts`.`ssh_key`,
                                     `ssh_accounts`.`status`,
                                     `ssh_accounts`.`description`,

                                     `createdby`.`name`   AS `createdby_name`,
                                     `createdby`.`email`  AS `createdby_email`

                           FROM      `ssh_accounts`

                           LEFT JOIN `users` AS `createdby`
                           ON        `ssh_accounts`.`createdby`  = `createdby`.`id`'.$where,

                           $execute);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('ssh_get_account(): Failed', $e);
    }
}



/*
 * Add the fingerprints for the specified domain:port to the `ssh_fingerprints` table and the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_rebuild_known_hosts()
 * @see ssh_remove_known_hosts()
 *
 * @param string $domain
 * @param natural $port
 */
function ssh_add_known_host($domain, $port) {
    try {
        $port         = ssh_get_port($port);
        $fingerprints = ssh_get_fingerprints($domain, $port);
        $count        = 0;

        if (empty($fingerprints)) {
            throw new CoreException(tr('ssh_add_known_host(): ssh-keyscan found no public keys for domain ":domain"', array(':domain' => $domain)), 'not-exists');
        }

        /*
         * Is this a registered server?
         */
        try {
            $server = servers_get($domain, false, false, true);

        }catch(Exception $e) {
            $server = array('id' => null);
        }

        /*
         * Auto register the fingerprints in the ssh_fingerprints tableraid
         */
        $dbfingerprints = sql_list('SELECT `fingerprint`, `algorithm` FROM `ssh_fingerprints` WHERE `domain` = :domain AND `port` = :port', array(':domain' => $domain, ':port' => $port));

        if ($dbfingerprints) {
            /*
             * This host is already registered in the ssh_fingerprints table. We
             * should be able to find all its fingerprints
             */
            foreach ($fingerprints as $fingerprint) {
                $exists = array_key_exists($fingerprint['fingerprint'], $dbfingerprints);

                if (!$exists) {
                    throw new CoreException(tr('ssh_add_known_host(): The domain ":domain" gave fingerprint ":fingerprint", which does not match any of the already registered fingerprints', array(':domain' => $fingerprint['domain'], ':fingerprint' => $fingerprint['fingerprint'])), 'not-exists');
                }

                if ($dbfingerprints[$fingerprint['fingerprint']] != $fingerprint['algorithm']) {
                    throw new CoreException(tr('ssh_add_known_host(): The domain ":domain" gave fingerprint ":fingerprint", which does match an already registered fingerprints, but for the wrong algorithm ":algorithm"', array(':domain' => $fingerprint['domain'], ':fingerprint' => $fingerprint['fingerprint'], ':algorithm' => $fingerprint['algorithm'])), 'not-match');
                }
            }

        } else {
            /*
             * This host is not yet registered in the ssh_fingerprints table.
             * Regiser its fingerprints now.
             */
            $insert = sql_prepare('INSERT INTO `ssh_fingerprints` (`createdby`, `meta_id`, `servers_id`, `domain`, `seodomain`, `port`, `fingerprint`, `algorithm`)
                                   VALUES                         (:createdby , :meta_id , :servers_id , :domain , :seodomain , :port , :fingerprint , :algorithm )');


            foreach ($fingerprints as $fingerprint) {
                $insert->execute(array(':createdby'   => isset_get($_SESSION['user']['id']),
                                       ':meta_id'     => meta_action(),
                                       'servers_id'   => $server['id'],
                                       ':domain'      => $fingerprint['domain'],
                                       ':seodomain'   => seo_unique($fingerprint['domain'], 'ssh_fingerprints', null, 'seodomain'),
                                       ':port'        => $fingerprint['port'],
                                       ':fingerprint' => $fingerprint['fingerprint'],
                                       ':algorithm'   => $fingerprint['algorithm']));
            }

            if ($server['id']) {
                log_console(tr('Added ":count" fingerprints for registered domain ":domain" with servers id ":id"', array(':count' => count($fingerprints), ':domain' => $domain, ':id' => $server['id'])));

            } else {
                log_console(tr('Added ":count" fingerprints for unregistered domain ":domain"', array(':count' => count($fingerprints), ':domain' => $domain)));
            }
        }

        /*
         * Now add them to the known_hosts file
         */
        foreach ($fingerprints as $fingerprint) {
            if (ssh_append_fingerprint($fingerprint)) {
                $count++;
            }
        }

// :TODO: Don't just set status to null!! What if it was deleted?!
        sql_query('UPDATE `servers` SET `status` = NULL WHERE `domain` = :domain', array(':domain' => $domain));
        return $count;

    }catch(Exception $e) {
        throw new CoreException('ssh_add_known_host(): Failed', $e);
    }
}



/*
 * Remove the registered fingerprints for the specified domain:port from the `ssh_fingerprints` table and the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_rebuild_known_hosts()
 * @see ssh_add_known_hosts()
 *
 * @param string $domain
 * @param natural $port
 * @return natural The amount of fingerprints removed
 */
function ssh_remove_known_host($domain, $port = null) {
    try {
        if (empty($domain)) {
            throw new CoreException(tr('ssh_remove_known_host(): No domain specified'), 'not-specified');
        }

        $count = 0;

        if ($port) {
            /*
             * Delete only the specified domain and port combination
             */
            $port = ssh_get_port($port);
            sql_query('DELETE FROM `ssh_fingerprints` WHERE `domain` = :domain AND `port` = :port', array(':domain' => $domain, ':port' => $port));

        } else {
            /*
             * Delete everything for the specified domain
             */
            sql_query('DELETE FROM `ssh_fingerprints` WHERE `domain` = :domain', array(':domain' => $domain));
        }

        file_ensure_file(ROOT.'data/ssh/known_hosts', 0640, 0750);
        file_delete(ROOT.'data/ssh/known_hosts~update', false);

        /*
         * Copy the lines that should not be deleted to the new file
         */
        $f1 = fopen(ROOT.'data/ssh/known_hosts'       , 'r');
        $f2 = fopen(ROOT.'data/ssh/known_hosts~update', 'w+');

        while ($line = fgets($f1)) {
            if ($port) {
                $found = preg_match('/^\['.$domain.'\]\:'.$port.'\s+/', $line);

            } else {
                $found = preg_match('/^\['.$domain.'\]/', $line);
            }

            if (!$found) {
                fputs($f2, $line);

            } else {
                $count++;
            }
        }

        fclose($f1);
        fclose($f2);

        /*
         * Move the new file in place of the old one
         */
        file_delete(ROOT.'data/ssh/known_hosts', false);
        rename(ROOT.'data/ssh/known_hosts~update', ROOT.'data/ssh/known_hosts');

        return $count;

    }catch(Exception $e) {
        /*
         * Close the files
         */
        if (isset($f1)) {
            fclose($f1);
        }

        if (isset($f2)) {
            fclose($f2);
        }

        throw new CoreException('ssh_remove_known_host(): Failed', $e);
    }
}



/*
 * Append the specified fingerprint data to the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_add_known_host()
 * @see ssh_rebuild_known_hosts()
 *
 * @param params $fingerprint
 * @params string domain
 * @params natural port
 * @params natural algorithm
 * @params natural fingerprint
 * @return boolean
 */
function ssh_append_fingerprint($fingerprint) {
    try {
        file_ensure_file(ROOT.'data/ssh/known_hosts', 0640, 0750);

        $exists = safe_exec(array('ok_exitcodes' => '0,1',
                                  'commands'     => array('grep', array('"\['.$fingerprint['domain'].'\]:'.$fingerprint['port'].' '.$fingerprint['algorithm'].' '.$fingerprint['fingerprint'].'"', ROOT.'data/ssh/known_hosts'))));

        if ($exists) {
            log_console(tr('Skipping fingerprint ":fingerprint" for domain ":domain", it already exists in known_hosts', array(':fingerprint' => $fingerprint['fingerprint'], ':domain' => $fingerprint['domain'])), 'VERYVERBOSE');
            return false;
        }

        log_console(tr('Adding fingerprint ":fingerprint" for domain ":domain" to known_hosts', array(':fingerprint' => $fingerprint['fingerprint'], ':domain' => $fingerprint['domain'])), 'VERBOSE');
        file_put_contents(ROOT.'data/ssh/known_hosts', '['.$fingerprint['domain'].']:'.$fingerprint['port'].' '.$fingerprint['algorithm'].' '.$fingerprint['fingerprint']."\n", FILE_APPEND);
        return true;

    }catch(Exception $e) {
        throw new CoreException('ssh_append_fingerprint(): Failed', $e);
    }
}



/*
 * Gets and registers the SSH fingerprints for the specified domain and port
 *
 * The obtained fingerprints are stored in the ssh_fingerprints table and any subsequent call will attempt to match them. If the match fails, an exception will be thrown
 *
 * @Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @exception CoreException not-match Thrown if any of the found fingerprint does not match any of the fingerprints and algorithms registered in the ssh_fingerprints table
 * @see ssh_rebuild_known_hosts()
 *
 * @param string $domain
 * @param natural $port
 * @return array The found fingerprints for the specified domain and port
 */
function ssh_get_fingerprints($domain, $port) {
    try {
        if (empty($domain)) {
            throw new CoreException(tr('ssh_get_fingerprints(): No domain specified'), 'not-specified');
        }

        load_libs('servers,seo');

        $port    = ssh_get_port($port);
        $retval  = array();
        $results = safe_exec(array('commands' => array('ssh-keyscan', array('-p', $port, $domain))));

        foreach ($results as $result) {
            if (substr($result, 0, 1) === '#') continue;

            preg_match_all('/\[(.+?)\](?:\:(\d{1,5}))\s+([a-z0-9-]+)\s+([a-z0-9+\/]+)/i', $result, $matches);

            $entry = array('fingerprint' => $matches[4][0],
                           'domain'      => $matches[1][0],
                           'port'        => $matches[2][0],
                           'algorithm'   => $matches[3][0]);

            /*
             * Validate domain format
             */
            if (!filter_var($entry['domain'], FILTER_VALIDATE_DOMAIN)) {
                if (!filter_var($entry['domain'], FILTER_VALIDATE_IP)) {
                    throw new CoreException(tr('ssh_get_fingerprints(): ssh-keyscan returned invalid domain name ":domain"', array(':domain' => $entry['domain'])), '');
                }
            }

            $retval[] = $entry;
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('ssh_get_fingerprints(): Failed', $e);
    }
}



/*
 * Rebuild the ROOT/data/ssh/known_hosts file, adding all host key fingerprints
 * stored in the ssh_fingerprints table
 *
 * @Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see ssh_get_fingerprints()
 * @see ssh_rebuild_known_hosts()
 * @package ssh
 *
 * @return natural The amount of finger prints added to the known_hosts file
 */
function ssh_rebuild_known_hosts($clear = false) {
    try {
        if ($clear) {
            /*
             * Clear the SSH known hosts file
             */
            log_console(tr('Deleting the known_hosts file "'.ROOT.'data/ssh/known_hosts"'), 'VERBOSE/yellow');
            file_delete(ROOT.'data/ssh/known_hosts', false);
        }

        log_console(tr('Rebuilding known_hosts file'), 'VERBOSE/cyan');

        $count        = 0;
        $fingerprints = sql_query('SELECT `id`, `domain`, `port`, `algorithm`, `fingerprint` FROM `ssh_fingerprints` WHERE `status` IS NULL');

        while ($fingerprint = sql_fetch($fingerprints)) {
            if (ssh_append_fingerprint($fingerprint)) {
                $count++;
            }
        }

        return $count;

    }catch(Exception $e) {
        throw new CoreException('ssh_rebuild_known_hosts(): Failed', $e);
    }
}



/*
 * Returns true if the specified domain:port is registered in the ROOT/data/ssh/known_hosts file
 *
 * @Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see ssh_get_fingerprints()
 * @see ssh_rebuild_known_hosts()
 * @package ssh
 *
 * @params string $domain
 * @params natural $port
 * @params boolean $auto_register If set to true, if the domain is not specified in the ROOT/data/ssh/known_hosts file but is available in the ssh_fingerprints table, then the function will automatically add the fingerprints to the ROOT/data/ssh/known_hosts file
 * @return boolean True if the specified domain:port is registered in the ROOT/data/ssh/known_hosts file
 */
function ssh_host_is_known($domain, $port, $auto_register = true) {
    try {
        file_ensure_file(ROOT.'data/ssh/known_hosts', 0640, 0750);

        $port       = ssh_get_port($port);
        $db_count   = sql_get('SELECT COUNT(`id`) FROM `ssh_fingerprints` WHERE `domain` = :domain AND `port` = :port', true, array('domain' => $domain, ':port' => $port), 'core');
        $file_count = safe_exec(array('commands' => array('grep', array('"\['.$domain.'\]:'.$port.'"', ROOT.'data/ssh/known_hosts', 'connector' => '|'),
                                                          'wc'  , array('-l'))));
        $file_count = array_shift($file_count);

        if ($file_count) {
            /*
             * Fingerprints are avaialble in the known_hosts file
             */
            return true;
        }

        if (!$db_count or !$auto_register) {
            /*
             * No fingerprints available at all, or we cannot auto register
             */
            return false;
        }

        /*
         * Fingerprints are in the ssh_fingerprints table, but not in the
         * known_hosts file, and we can auto register
         */
        log_console(tr('The host ":domain::port" has no SSH key fingerprint in the ROOT/data/ssh/known_hosts file, but the keys were found in the ssh_fingerprints table. Adding fingerprints now.', array(':domain' => $domain, ':port' => $port)), 'yellow');
        return ssh_add_known_host($domain, $port);

    }catch(Exception $e) {
        throw new CoreException('ssh_host_is_known(): Failed', $e);
    }
}



/*
 * Set up an SSH tunnel
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @exception Throws an exception if the ssh command does not return exit code 0
 * @see inet_port_availalbe();
 *
 * @param params $params
 * @params string $domain The domain where SSH should connect to
 * @params string $local_port The port on the local server where SSH should listen to 1-65535
 * @params string $remote_port The port on the remote server where SSH should connect to 1-65535
 * @params array $options The required SSH options
 * @param boolean $reuse If set to true, ssh_tunnel() will first check if a tunnel with the requested configuration already exists. If it does, no new tunnel will be created and the PID for the already existing tunnel will be returned instead
 * @return natural The process id of the created (or reused) SSH tunnel.
 */
function ssh_tunnel($params, $reuse = true) {
    try {
        array_ensure ($params, 'domain,source_port,target_port,target_domain,persist,server');
        array_default($params, 'tunnel'     , 'localhost');
        array_default($params, 'test_tries' , 50);
        array_default($params, 'function'   , 'exec');
        load_libs('inet,linux');

        log_console(tr('Creating SSH tunnel to ":source_port::target_domain::target_port"', array(':source_port' => $params['source_port'], ':target_domain' => not_empty($params['target_domain'], 'localhost'), ':target_port' => $params['target_port'])), 'VERYVERBOSE/cyan');

        /*
         * Is a tunnel with the requested configuration already available? If
         * so, use that, don't make a new one!
         */
        if ($reuse) {
            $exists = ssh_tunnel_exists($params['domain'], $params['target_port'], $params['target_domain'], $params['server']);

            if ($exists) {
                if ($params['source_port'] === $exists['source_port']) {
                    log_console(tr('Found pre-existing SSH tunnel for requested configuration ":source_port::target_domain::target_port" with pid ":pid" on the requested source port, not creating a new one', array(':source_port' => $params['source_port'], ':target_domain' => $params['target_domain'], ':target_port' => $params['target_port'], ':pid' => $exists['pid'])), 'VERBOSE/green');

                } else {
                    log_console(tr('Found pre-existing SSH tunnel for requested configuration ":source_port::target_domain::target_port" with pid ":pid" on different source port ":different_port", not creating a new one', array(':source_port' => $params['source_port'], ':target_domain' => $params['target_domain'], ':target_port' => $params['target_port'], ':pid' => $exists['pid'], ':different_port' => $exists['source_port'])), 'VERBOSE/green');
                }

                return $exists;
            }
        }

        if ($params['source_port']) {
            /*
             * Ensure port is available.
             */
            if (!inet_port_available($params['source_port'], '127.0.0.1', $params['server'])) {
                throw new CoreException(tr('ssh_tunnel(): Source port ":port" is already in use', array(':port' => $params['source_port'])), 'not-available');
            }

        } else {
            /*
             * Ensure Assign a random local port
             */
            $params['source_port'] = inet_get_available_port('127.0.0.1', $params['server']);
        }

        $params['tunnel'] = array('persist'       => $params['persist'],
                                  'source_port'   => $params['source_port'],
                                  'target_domain' => $params['target_domain'],
                                  'target_port'   => $params['target_port']);

        unset($params['persist']);
        unset($params['source_port']);
        unset($params['target_port']);
        unset($params['target_domain']);

        $pid = servers_exec($params['domain'], $params);

        log_console(tr('Created SSH tunnel ":source_port::target_domain::target_port" to domain ":domain"', array(':domain' => isset_get($server['domain']), ':source_port' => $params['tunnel']['source_port'], ':target_domain' => not_empty($params['tunnel']['target_domain'], 'localhost'), ':target_port' => $params['tunnel']['target_port'])), 'green');

        /*
         * Check that the tunnel is being setup correctly in the background
         * This is a blocking section, we will NOT continue until the tunnel is
         * availabe
         */
        if (!$params['test_tries']) {
            log_console(tr('Not confirming SSH tunnel existence due to "tries" set to 0. Waiting 1 second instead to ensure tunnel has had time to be created'), 'warning');
            usleep(1000000);

        } else {
            log_console(tr('Confirming SSH tunnel existence by testing tunnel in ":count" tries', array(':count' => $params['test_tries'])), 'VERBOSE/cyan');
            $tries = $params['test_tries'];

            while (--$tries > 0) {
                usleep(100000);

                /*
                 * Is the tunnel responding?
                 */
                $exists = inet_test_host_port(array('host'   => '127.0.0.1',
                                                    'port'   => $params['tunnel']['source_port'],
                                                    'server' => $params['server']));

                if ($exists) {
                    log_console(tr('SSH tunnel confirmed working'), 'VERBOSE/green');
                    break;
                }
            }

            if ($tries <= 0) {
                /*
                 * Tunnel either failed to build, or no target was listening
                 */
                throw new CoreException(tr('ssh_tunnel(): Failed to confirm SSH tunnel available after ":tries" tries', array(':tries' => $params['test_tries'])), 'failed');
            }
        }

        return array('pid'         => $pid,
                     'source_port' => $params['tunnel']['source_port']);

    }catch(Exception $e) {
        throw new CoreException('ssh_tunnel(): Failed', $e);
    }
}



/*
 * Detect if an SSH tunnel with the specified parameters already exists or not
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $domain
 * @param numeric $target_port
 * @param numeric $target_domain
 * @return array Resulting array either is null, or an arry containing the pid (process id) and source_port of the found tunnel
 */
function ssh_tunnel_exists($domain, $target_port, $target_domain = null, $server = null) {
    global $core;

    try {
        load_libs('linux');

        if (!$target_domain) {
            $target_domain = 'localhost';
        }

        $results   = array();
        $processes = cli_list_processes('ssh,-L');

        foreach ($processes as $pid => $process) {
            if (!preg_match_all('/(\d+)(\:.+?\:\d+)/', $process, $matches)) {
                /*
                 * Failed to identify the tunnel configuration
                 */
                log_console(tr('Failed to identify SSH tunnel configuration for process ":process"', array(':process' => $process)), 'VERBOSE/yellow');
            }

            $process_domain        = Strings::fromReverse($process, ' ');
            $process_domain        = Strings::from($process_domain, '@');
            $process_source_port   = isset_get($matches[1][0]);
            $process_configuration = isset_get($matches[2][0]);

            if ($process_domain === $domain) {
                /*
                 * Target server matches, check tunnel configuration
                 * In case of 127.0.0.1 or localhost, check for both alternatives
                 */
// :TODO: Check if maybe in the future we should check all possible domain names and IP's
                switch ($target_domain) {
                    case 'localhost':
                        $alt_domain = '127.0.0.1';
                        break;

                    case '127.0.0.1':
                        $alt_domain = 'localhost';
                        break;
                }

                if (($process_configuration === (':'.$target_domain.':'.$target_port)) or ($process_configuration === (':'.$alt_domain.':'.$target_port))) {
                    $results[$pid] = $process_source_port;
                }
            }
        }

        switch (count($results)) {
            case 0:
                /*
                 * No tunnel with the requeste configuration found
                 */
                return null;

            case 1:
                /*
                 * Yay! Found a tunnel! Test if it accepts connections and
                 * return its PID
                 */
                load_libs('inet');

                $source_port = current($results);
                $works       = inet_test_host_port(array('host'   => '127.0.0.1',
                                                         'port'   => $source_port,
                                                         'server' => $server));

                if ($works) {
                    /*
                     * This tunnel works, yay!
                     */
                    return array('pid'         => key($results),
                                 'source_port' => $source_port);
                }

                /*
                 * Crap! The tunnel is not accepting connections! This means
                 * either that the tunnel is broken, or that the remote
                 * service is down.
                 *
                 * Assume the issue is with the tunnel. Kill the current
                 * one, make a new one using the SAME source port, then try
                 * again. If this one works, it was the tunnel. If this one
                 * fails too, then we know it is the remote service
                 */
// :TODO: Implement
                break;

            default:
                /*
                 * Apparently there are multiple SSH tunnels with this configuration. Pick a random one
                 */
                log_console(tr('Found multiple SSH tunnels to host ":domain" with configuration "::target_domain::target_port"', array(':domain' => $domain, ':target_port' => $target_port, ':target_domain' => $target_domain)), 'yellow');
                return array_random_value($pids);
        }

    }catch(Exception $e) {
        throw new CoreException('ssh_tunnel_exists(): Failed', $e);
    }
}



/*
 * Close SSH tunnel with the specified PID
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $pid
 * @return void
 */
function ssh_close_tunnel($pid) {
    global $core;

    try {
        /*
         * Ensure that the PID for this tunnel is no longer on the shutdown list
         */
        if (isset($core->register['shutdown_ssh_close_tunnel'])) {
            if (is_array($core->register['shutdown_ssh_close_tunnel'])) {
                foreach ($core->register['shutdown_ssh_close_tunnel'] as $key => $registered_pid) {
                    if ($pid == $registered_pid) {
                        unset($core->register['shutdown_ssh_close_tunnel'][$key]);
                    }
                }
            }
        }

        load_libs('cli');
        cli_kill($pid);

    }catch(Exception $e) {
        throw new CoreException('ssh_close_tunnel(): Failed', $e);
    }
}



/*
 * Return either the specified port (if any) or the dedfault SSH port (if null or equivalent of empty was specified)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param natural $port
 * @return natural If the specified port was not empty, it will be returned. If the specified port was empty, the default port configuration will be returned
 */
function ssh_get_port($port = null) {
    global $_CONFIG;

    try {
        if ($port) {
            if (!is_natural($port) or ($port > 65535)) {
              throw new CoreException(tr('ssh_get_port(): No port specified'), 'not-specified');
          }

          return $port;
        }

        if ($_CONFIG['servers']['ssh']['default_port']) {
            return $_CONFIG['servers']['ssh']['default_port'];
        }

        return 22;

    }catch(Exception $e) {
        throw new CoreException('ssh_get_port(): Failed', $e);
    }
}



/*
 * Return the PID (Process ID) for the specified persistent SSH connection socket
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param string $socket The socket file
 * @return natural the PID for the specified socket file
 */
function ssh_persistent_pid($socket) {
    try {
        $results = ssh_exec(array('commands' => array('ssh', array('-O', 'check', 'foobar', '-o', 'controlpath="'.$socket.'"'))));
        $result  = array_shift($results);
        $result  = Strings::cut($result, '=', ')');

        return $result;

    }catch(Exception $e) {
        throw new CoreException('ssh_persistent_pid(): Failed', $e);
    }
}



/*
 * Return an array with a list of all persistent SSH connections
 *
 * This function will return an array with a list of all available persistent SSH connections in the format PID => SOCKET_FILE
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 * @see ssh_persistent_pid()
 *
 * @return array The list of all persisten SSH connections
 */
function ssh_list_persistent($close = false) {
    try {
        $retval = array();

        foreach (scandir(ROOT.'data/ssh/control') as $socket) {
            $pid          = ssh_persistent_pid($socket);
            $retval[$pid] = $socket;

            if ($close) {
                cli_kill($pid);
            }
        }

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('ssh_list_persistent(): Failed', $e);
    }
}
?>