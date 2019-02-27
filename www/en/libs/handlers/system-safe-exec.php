<?php
global $core, $_CONFIG;

try{
    if(!$core->register['ready']){
        throw new BException(tr('safe_exec(): Startup has not yet finished and base is not ready to start working properly. safe_exec() may not be called until configuration is fully loaded and available'), 'invalid');
    }

    /*
     * Join all commands together
     */
    if(is_array($commands)){
        /*
         * Auto escape all arguments
         */
        foreach($commands as &$command){
            if(empty($first)){
                $first   = true;
                $command = mb_trim($command);

                if($timeout){
                    $command = 'timeout '.$timeout.' '.$command;
                }

                continue;
            }

            $command = escapeshellarg($command);
        }

        unset($command);
        $command = implode(' ', $commands);

    }else{
        $command = mb_trim($commands);
    }

    /*
     * Add $PATH
     */
    if($_CONFIG['exec']['path']){
        $command = 'export PATH="'.$_CONFIG['exec']['path'].'"; '.$command;
    }

    /*
     * Setup commands for background execution if required so
     */
    if(substr($command, -1, 1) == '&'){
        $command    = substr($command, 0, -1);
        $background = true;
        log_console(tr('Executing background command ":command" using function ":function"', array(':command' => $command, ':function' => $function)), (PLATFORM_HTTP ? 'cyan' : 'VERBOSE/cyan'));

    }else{
        $background = false;
        log_console(tr('Executing command ":command" using function ":function"', array(':command' => $command, ':function' => $function)), (PLATFORM_HTTP ? 'cyan' : 'VERBOSE/cyan'));
    }

    /*
     * Execute the command
     */
    switch($function){
        case 'exec':
            if($background){
                /*
                 *
                 */
                $lastline = exec(substr($command, 0, -1).' > /dev/null 2>&1 3>&1 & echo $!', $output, $exitcode);
                $output   = array_shift($output);

            }else{
                $lastline = exec($command.($route_errors ? ' 2>&1 3>&1' : ''), $output, $exitcode);
            }

            break;

        case 'shell_exec':
            if($background){
                throw new BException(tr('safe_exec(): The specified command ":command" requires background execution (because of the & at the end) which is not supported by the requested PHP exec function shell_exec()', array(':command' => $command)), 'not-supported');
            }

            $exitcode = null;
            $lastline = '';
            $output   = array(shell_exec($command));
            break;

        case 'passthru':
            $output   = array();
            $lastline = '';

            passthru($command, $exitcode);
            break;

        case 'system':
            $output = array();

            if($background){
                /*
                 * Background commands cannot use "exec()" because that one will always wait for the exit code
                 */
                $lastline = system(substr($command, 0, -1).' > /dev/null 2>&1 3>&1 & echo $!', $exitcode);
                $output   = array_shift($output);

            }else{
                $lastline = system($command.($route_errors ? ' 2>&1 3>&1' : ''), $exitcode);
            }

            break;

        case 'pcntl_exec':
under_construction();
            break;

        default:
            throw new BException(tr('safe_exec(): Unknown exec function ":function" specified, please use exec, passthru, or system', array(':function' => $function)), 'not-specified');
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
        if(!in_array($exitcode, array_force($ok_exitcodes))){
            log_file(tr('Command ":command" failed with exit code ":exitcode", see output below for more information', array(':command' => $command, ':exitcode' => $exitcode)), 'safe_exec', 'error');

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

            throw new BException(tr('safe_exec(): Command ":command" failed with exit code ":exitcode", see attached data for output', array(':command' => $command, ':exitcode' => $exitcode)), $exitcode, $output);
        }
    }

    return $output;

}catch(Exception $e){
    if(!isset($output)){
        $output = '*** COMMAND HAS NOT YET BEEN EXECUTED ***';
    }

    $e->setData($output);

    throw new BException('safe_exec(): Failed', $e);
}
?>
