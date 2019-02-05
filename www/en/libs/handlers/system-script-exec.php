<?php
try{
    /*
     * Handler code for script_exec() function
     */
    $script_file = ROOT.'scripts/'.$script;

    if(!file_exists($script_file)){
        throw new bException(tr('script_exec(): Specified script ":script" does not exist', array(':script' => $script)), 'not-exist');
    }

    if($arguments){
        if(!is_array($arguments)){
            throw new bException(tr('script_exec(): Invalid arguments ":arguments" specified, must be an array or null', array(':arguments' => $arguments)), 'invalid');
        }

        foreach($arguments as &$argument){
            $argument = escapeshellarg($argument);
        }

        unset($argument);

        $arguments[] = '-E';
        $arguments[] = ENVIRONMENT;
        $arguments   = implode(' ', $arguments);
    }

    return safe_exec(escapeshellcmd($script_file).' '.$arguments, $ok_exitcodes, true, 'passthru');

}catch(Exception $e){
    throw new bException(tr('script_exec(): Failed to execute script ":script"', array(':script' => $script)), $e);
}
?>