<?php
/*
 * Handler code for script_exec() function
 */
$script = ROOT.'scripts/'.$script;

if(!file_exists($script)){
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
}

return safe_exec(escapeshellcmd($script).' '.implode(' ', $arguments), $ok_exitcodes);
?>
