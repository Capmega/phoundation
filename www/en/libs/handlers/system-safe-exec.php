<?php
global $core, $_CONFIG;

try{
    if(!$core->register['ready']){
        throw new BException(tr('safe_exec(): Startup has not yet finished and base is not ready to start working properly. safe_exec() may not be called until configuration is fully loaded and available'), 'not-ready');
    }

// :COMPATIBILITY: Remove the following code after 2019/06
if(is_string($params)){
$params = array('commands' => $params);
}

    if(!is_array($params)){
        throw new BException(tr('safe_exec(): Specified $params is invalid, should be an array but is an ":type"', array(':type' => gettype($params))), 'invalid');
    }

    array_default($params, 'path'        , $_CONFIG['exec']['path']);
    array_default($params, 'domain'      , null);
    array_default($params, 'function'    , 'exec');
    array_default($params, 'ok_exitcodes', 0);
    array_default($params, 'background'  , false);
    array_default($params, 'log'         , true);
    array_default($params, 'output_log'  , (VERBOSE ? ROOT.'data/log/syslog' : '/dev/null'));

    if($params['domain']){
        /*
         * Execute this command on the specified domain instead
         */
        $domain = $params['domain'];
        unset($params['domain']);

        load_libs('servers');
        return servers_exec($domain, $params);
    }

    /*
     * Validate command structure
     */
    if(empty($params['commands'])){
        throw new BException(tr('safe_exec(): No commands specified'), 'invalid');
    }

    if(is_array($params['commands'])){
        /*
         * Build commands from the specified commands array safely
         */
        load_libs('cli');
        $params['commands'] = cli_build_commands_string($params);
    }

    /*
     * Add $PATH
     */
    if(!empty($params['path'])){
        $params['commands'] = 'export PATH="'.$_CONFIG['exec']['path'].'"; '.$params['commands'];
    }

    log_console(tr('Executing command ":commands" using PHP function ":function"', array(':commands' => $params['commands'], ':function' => $params['function'])), (PLATFORM_HTTP ? 'cyan' : ($params['log'] ? '' : 'VERY').'VERBOSE/cyan'));

    /*
     * Execute the command
     */
    switch($params['function']){
        case 'exec':
            $lastline = exec($params['commands'], $output, $exitcode);

            if($params['background']){
                /*
                 * Last command section was executed as background process,
                 * return PID
                 */
                $output = array_shift($output);
            }

            break;

        case 'shell_exec':
            if($params['background']){
                throw new BException(tr('safe_exec(): The specified command ":command" requires background execution (because of the & at the end) which is not supported by the shell_exec()', array(':command' => $params['commands'])), 'not-supported');
            }

            $exitcode = null;
            $lastline = '';
            $output   = array(shell_exec($params['commands']));
            break;

        case 'passthru':
            if($params['background']){
                throw new BException(tr('safe_exec(): The specified command ":command" requires background execution which is not supported by PHP passthru()', array(':command' => $params['commands'])), 'not-supported');
            }

            $output   = array();
            $lastline = '';

            passthru($params['commands'], $exitcode);
            $output = $exitcode;
            break;

        case 'system':
            $output   = array();
            $lastline = system($params['commands'], $exitcode);

            if($params['background']){
                /*
                 * Background commands cannot use "exec()" because that one will always wait for the exit code
                 */
                $output = array_shift($output);
            }

            break;

        case 'pcntl_exec':
under_construction();
            break;

        default:
            throw new BException(tr('safe_exec(): Unknown exec function ":function" specified, please use exec, passthru, system, shell_exec, or pcntl_exec', array(':function' => $params['function'])), 'not-specified');
            break;
    }

    /*
     * In VERYVERBOSE we also log the command output
     */
    if(VERYVERBOSE){
        log_console('Command output:', 'purple');
        log_console($output);
    }

    /*
     *
     */
    if($exitcode){
        if(!in_array($exitcode, array_force($params['ok_exitcodes']))){
            log_console(tr('Command ":command" failed with exit code ":exitcode", see output below for more information', array(':command' => $params['commands'], ':exitcode' => $exitcode)), 'error');

// :DELETE: Since the exception will already log all the information, there is no need to log it separately
            //if($output){
            //    log_file($output, 'safe_exec', 'error');
            //
            //}elseif(empty($lasline)){
            //    log_file(tr('Command has no output'), 'safe_exec', 'error');
            //
            //}else{
            //    log_file(tr('Command only had last line (shown below)'), 'safe_exec', 'error');
            //    log_file($lasline, 'safe_exec', 'error');
            //}

            if($exitcode === 124){
                throw new BException(tr('safe_exec(): Received exitcode 124 from scanner program, which very likely is a timeout'), 124);
            }

            throw new BException(tr('safe_exec(): Command ":command" failed with exit code ":exitcode", see attached data for output', array(':command' => $params['commands'], ':exitcode' => $exitcode)), $exitcode, $output);
        }
    }

    return $output;

}catch(Exception $e){
    if(!isset($output)){
        $output = '*** COMMAND HAS NOT YET BEEN EXECUTED ***';
    }

    /*
     * Store the output in the data property of the exception
     */
    $e->setData($output);

    if($e->getRealCode() === 124){
        throw new BException(tr('safe_exec(): Command appears to have been terminated by timeout'), $e);
    }

    throw new BException(tr('safe_exec(): Failed'), $e);
}
?>
