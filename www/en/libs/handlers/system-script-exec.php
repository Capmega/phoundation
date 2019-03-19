<?php
/*
 * Handler code for script_exec() function
 * @note This script will NOT test the command or its arguments because everything is passed directly to safe_exec() which already validate and sanitize the commands and arguments
 */
try{
    global $core;

    array_ensure($params, 'script,arguments,function,ok_exitcodes,background,timeout');

    /*
     * Validate the requested commands, ensure that script_exec() is only used
     * when the system is ready to go
     */
    if(!$core->register['ready']){
        throw new BException(tr('script_exec(): Startup has not yet finished and base is not ready to start working properly. safe_exec() may not be called until configuration is fully loaded and available'), 'not-ready');
    }

    if(!$params['commands']){
        throw new BException(tr('script_exec(): No commands specified'), 'not-specified');
    }

    if(!is_array($params['commands'])){
        throw new BException(tr('script_exec(): Invalid commands specified'), 'invalid');
    }

    /*
     * Ensure that all scripts are executed from ROOT/scripts/
     *
     * Ensure that all arguments contain the environment specification
     */
    $count = 0;
    foreach($params['commands'] as &$item){
        if(fmod(++$count, 2)){
            /*
             * This must be arguments
             */
            $item = ROOT.'scripts/'.$item;

        }else{
            /*
             * These must be a command
             */
            $item[] = '-E';
            $item[] = ENVIRONMENT;
        }
    }

    /*
     * Execute the script using safe_exec
     */
    return safe_exec($params);

}catch(Exception $e){
    throw new bException(tr('script_exec(): Failed to execute script ":script"', array(':script' => $params['script'])), $e);
}
?>