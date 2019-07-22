<?php
global $core, $_CONFIG;

try{
    if(!$core->register['ready']){
        throw new BException(tr('safe_exec(): Startup has not yet finished and base is not ready to start working properly. safe_exec() may not be called until configuration is fully loaded and available'), 'not-ready');
    }

    if(!is_array($params)){
        throw new BException(tr('safe_exec(): Specified $params ":params" is a ":datatype" but should be a params array', array(':params' => $params, ':datatype' => gettype($params))), 'invalid');
    }

    array_default($params, 'path'            , $_CONFIG['exec']['path']);
    array_default($params, 'domain'          , null);
    array_default($params, 'function'        , 'exec');
    array_default($params, 'ok_exitcodes'    , 0);
    array_default($params, 'background'      , false);
    array_default($params, 'log'             , true);
    array_default($params, 'debug'           , false);
    array_default($params, 'include_exitcode', false);
    array_default($params, 'output_log'      , ((VERBOSE or $params['debug']) ? ROOT.'data/log/syslog' : '/dev/null'));

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

//    $params['commands'] = 'set -uo pipefail; '.$params['commands'];
    $params['commands'] = 'set -u; '.$params['commands'];
    $params['commands'] = trim($params['commands']);

    /*
     * Log and debug display options
     */
    if($params['debug']){
        $color = 'cyan';
        show($params['commands']);

    }else{
        $color = (PLATFORM_HTTP ? '' : ($params['log'] ? '' : 'VERY')).'VERBOSE/cyan';
    }

    /*
     * Execute the command
     */
    switch($params['function']){
        case 'exec':
            log_console(tr('Executing command ":commands" using PHP function ":function"', array(':commands' => $params['commands'], ':function' => $params['function'])), $color);
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
            log_console(tr('Executing command ":commands" using PHP function ":function"', array(':commands' => $params['commands'], ':function' => $params['function'])), $color);

            if($params['background']){
                throw new BException(tr('safe_exec(): The specified command ":command" requires background execution (because of the & at the end) which is not supported by the shell_exec()', array(':command' => $params['commands'])), 'not-supported');
            }

            $exitcode = null;
            $lastline = '';
            $output   = array(shell_exec($params['commands']));
            break;

        case 'passthru':
            /*
             * Execute the file and dump the raw output directly to the client
             * This method will modify the command to be executed slightly so
             * that all output is routed through tee to a temp file, and the
             * exit code will be stored in a temp file as well. The contents of
             * these temp files will then be used to proceed equal to exec()
             */
            $exitcode_file = file_temp();
            $output_file   = file_temp();

            $params['commands'] = trim($params['commands']);
            $params['commands'] = str_ends_not($params['commands'], ';');
            $params['commands'] = 'bash -c "set -o pipefail; '.$params['commands'].' | tee '.$output_file.'"; echo $? > '.$exitcode_file;

            log_console(tr('Executing command ":commands" using PHP function ":function"', array(':commands' => $params['commands'], ':function' => $params['function'])), $color);

            if($params['background']){
                throw new BException(tr('safe_exec(): The specified command ":command" requires background execution which is not supported by PHP passthru()', array(':command' => $params['commands'])), 'not-supported');
            }

            $output   = array();
            $lastline = '';

            passthru($params['commands'], $exitcode);

            /*
             * Get output and exitcode from temp files
             * NOTE: In case of errors, these output files may NOT exist!
             */
            if(file_exists($exitcode_file)){
                $exitcode = trim(file_get_contents($exitcode_file));
            }

            if(file_exists($output_file)){
                $output = file($output_file);

            }else{
                $output = '';
            }

            /*
             * Delete the temp files
             */
            file_delete($output_file);
            file_delete($exitcode_file);
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
            log_console(tr('Executing command ":commands" using PHP function ":function"', array(':commands' => $params['commands'], ':function' => $params['function'])), $color);
under_construction();
            break;

        default:
            throw new BException(tr('safe_exec(): Unknown exec function ":function" specified, please use exec, passthru, system, shell_exec, or pcntl_exec', array(':function' => $params['function'])), 'not-specified');
            break;
    }

    /*
     * In VERYVERBOSE we also log the command output
     */
    if(VERYVERBOSE or $params['debug']){
        log_console('Command output:', 'purple');
        log_console($output);
    }

    /*
     * Include the exitcode on to the output array.
     *
     * Since background already returns the PID, and only the PID, and we cannot
     * get an exit code from a background process, do not add the exit code in
     * that case
     */
    if($params['include_exitcode'] and !$params['background']){
        $output[] = $exitcode;
    }

    /*
     * Shell command reported something went wrong (possibly)
     */
    if($exitcode){
        if(!in_array($exitcode, array_force($params['ok_exitcodes']))){
            log_console(tr('Command ":command" stopped with exit code ":exitcode". This may be a problem, or no problem at all. See output below for more information', array(':command' => $params['commands'], ':exitcode' => $exitcode)), 'VERBOSE/warning');

            if(!isset($output)){
                $output = '*** NO OUTPUT AVAILABLE, COMMAND PROBABLY HAS NOT YET BEEN EXECUTED ***';
            }

            if($exitcode === 124){
                throw new BException(tr('safe_exec(): Received exitcode 124 from executed program, which very likely is a timeout'), 124, $output);
            }

            throw new BException(tr('safe_exec(): Command ":command" stopped with exit code ":exitcode". This may be a problem, or no problem at all. See attached data for output', array(':command' => $params['commands'], ':exitcode' => $exitcode)), $exitcode, $output);
        }
    }

    return $output;

}catch(Exception $e){
    if(!is_string($params)){
        /*
         * Store the output in the data property of the exception
         */
        if($params['function'] === 'passthru'){
            /*
             * passthru method creates temp files. Ensure they are deleted
             */
            if(isset($exitcode_file)){
                file_delete($exitcode_file);
            }

            if(isset($exitcode_file)){
                file_delete($exitcode_file);
            }
        }

        if($e->getRealCode() === 124){
            throw new BException(tr('safe_exec(): Command appears to have been terminated by timeout'), $e);
        }
    }

    throw new BException(tr('safe_exec(): Failed'), $e);
}
