<?php
/*
 * Handler code for script_exec() function
 * @note This script will NOT test the command or its arguments because everything is passed directly to safe_exec() which already validate and sanitize the commands and arguments
 */
try{
    global $core;

    /*
     * We can only execute script when the system is ready to go
     */
    if(!$core->register['ready']){
        throw new BException(tr('safe_exec(): Startup has not yet finished and base is not ready to start working properly. safe_exec() may not be called until configuration is fully loaded and available'), 'not-ready');
    }

    $script_file = ROOT.'scripts/'.$script;

    if(!file_exists($script_file)){
        throw new bException(tr('script_exec(): Specified script ":script" does not exist', array(':script' => $script)), 'not-exists');
    }

    /*
     * Ensure valid arguments and ensure we have ENVIRONMENT specified
     */
    if(!$arguments){
        $arguments = array();
    }

    if(!is_array($arguments)){
        throw new bException(tr('script_exec(): Invalid arguments ":arguments" specified, must be an array or null', array(':arguments' => $arguments)), 'invalid');
    }

    $arguments[] = '-E';
    $arguments[] = ENVIRONMENT;

     /*
      * Execute the script using safe_exec
      */
    return safe_exec(array('timeout'      => $timeout,
                           'ok_exitcodes' => $ok_exitcodes,
                           'function'     => ($function ? $function : 'passthru'),
                           'commands'     => array($script_file, $arguments)));

}catch(Exception $e){
    throw new bException(tr('script_exec(): Failed to execute script ":script"', array(':script' => $script)), $e);
}
?>