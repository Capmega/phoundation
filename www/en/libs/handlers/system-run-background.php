<?php
try{
    $args = str_from ($cmd, ' ');
    $cmd  = str_until($cmd, ' ');
    $path = dirname($cmd);
    $path = slash($path);
    $cmd  = basename($cmd);

    load_libs('process');

    if($path == './'){
        $path = ROOT.'scripts/';

    }elseif(str_starts_not($path, '/') == 'base/'){
        $path = ROOT.'scripts/base/';
    }

    if($single and process_runs($cmd)){
        log_file(tr('Specified command ":cmd" is already running and has "single" option specified, not running again', array(':cmd' => str_replace(ROOT, '', $path).$cmd)), 'run_background', 'warning');
        return false;
    }

    if(!file_exists($path.$cmd)){
        throw new BException(tr('run_background(): Specified command ":cmd" does not exists', array(':cmd' => $path.$cmd)), 'not-exists');
    }

    if(!is_file($path.$cmd)){
        throw new BException(tr('run_background(): Specified command ":cmd" is not a file', array(':cmd' => $path.$cmd)), 'notfile');
    }

    if(!is_executable($path.$cmd)){
        throw new BException(tr('run_background(): Specified command ":cmd" is not executable', array(':cmd' => $path.$cmd)), 'notexecutable');
    }

    if($log === true){
        $log = str_replace('/', '-', $cmd);
    }

    file_ensure_path(ROOT.'data/run-background');

    if(!strstr($args, '--env') and !strstr($args, '-E')){
        /*
         * Command doesn't have environment specified. Specify it using the current environment
         */
        $args .= ' --env '.ENVIRONMENT;
    }

    if($log){
        if(substr($log, 0, 3) === 'tmp'){
            /*
             * Log output to the TMP directory instead of the normal log output
             */
            $log = TMP.str_starts_not(substr($log, 3), '/');

        }else{
            $log = ROOT.'data/log/'.$log;
        }

        file_ensure_path(dirname($log));

        if(file_exists($log) and is_dir($log)){
            /*
             * Oops, the log file already exists, but as a directory! That is a
             * problem, get rid of it or it will stop background execution all
             * together!
             */
            log_file(tr('Found specified log file ":path" to exist as a directory. Deleting the directory to avoid run_background() not working correctly', array(':path' => $log)), 'run_background');
            file_delete($log);
        }

        $command = sprintf('export TERM='.$term.'; (nohup %s >> %s 2>&1 & echo $! >&3) 3> %s', $path.$cmd.' '.$args, $log, ROOT.'data/run-background/'.$cmd);
        exec($command);

    }else{
        $command = sprintf('export TERM='.$term.'; (nohup %s > /dev/null 2>&1 & echo $! >&3) 3> %s', $path.$cmd.' '.$args, ROOT.'data/run-background/'.$cmd);
        exec($command);
    }

// :DEBUG: Leave the next line around, it will be useful..
//showdie($command);

    $pid = exec(sprintf('cat %s; rm %s', ROOT.'data/run-background/'.$cmd.' ', ROOT.'data/run-background/'.$cmd));
    log_file('PID:'.$pid.' '.$command, 'VERBOSE/run-background');

    return $pid;

}catch(Exception $e){
    throw new BException('run_background(): Failed', $e);
}
?>