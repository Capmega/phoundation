<?php
/*
 * Handler code for script_exec() function
 * @note This script will NOT test the command or its arguments because everything is passed directly to safe_exec() which already validate and sanitize the commands and arguments
 */
try {
    global $core;

    Arrays::ensure($params, 'script,arguments,function,ok_exitcodes,background,timeout,delay');

    /*
     * Validate the requested commands, ensure that script_exec() is only used
     * when the system is ready to go
     */
    if (!$core->register['ready']) {
        throw new CoreException(tr('script_exec(): Startup has not yet finished and base is not ready to start working properly. script_exec() may not be called until configuration is fully loaded and available'), 'not-ready');
    }

    if (!$params['commands']) {
        throw new CoreException(tr('script_exec(): No commands specified'), 'not-specified');
    }

    if (!is_array($params['commands'])) {
        throw new CoreException(tr('script_exec(): Invalid commands specified'), 'invalid');
    }

    /*
     * Ensure that all scripts are executed from ROOT/scripts/
     *
     * Ensure that all arguments contain the environment specification
     */
    $count = 0;

    foreach ($params['commands'] as $id => &$item) {
        if (fmod(++$count, 2)) {
            /*
             * This must be a command
             */
            if (is_array($item)) {
                throw new CoreException(tr('script_exec(): Invalid commands structure specified, entry ":id" is an ":datatype" while it should be a string. Please ensure that $params[commands] is an array containing values with datatype string, array, string, array, etc', array(':id' => $id, ':datatype' => gettype($item))), 'invalid');
            }

            $item = ROOT.'scripts/'.$item;

        } else {
            /*
             * These must be arguments
             */
            if (!is_array($item)) {
                throw new CoreException(tr('script_exec(): Invalid commands structure specified, entry ":id" is a ":datatype" while it should be an array. Please ensure that $params[commands] is an array containing values with datatype string, array, string, array, etc', array(':id' => $id, ':datatype' => gettype($item))), 'invalid');
            }

            /*
             * Detect if environment has been specified. If so, avoid specifying
             * it again
             */
            $environment = false;

            foreach ($item as $key => $value) {
                switch ($value) {
                    case '-E';
                        // FALLTHROUGH
                    case '--env';
                        $environment = true;
                        break 2;
                }
            }

            if (!$environment) {
                $item[] = '-E';
                $item[] = ENVIRONMENT;
                $environment = true;
            }
        }
    }

    // Ensure that environment is available, in case of a command without any arguments
    if (empty($environment)) {
        $params['commands'][] = ['-E', ENVIRONMENT];
    }

    /*
     * Execute the script using safe_exec
     */
    return safe_exec($params);

}catch(Exception $e) {
    throw new CoreException(tr('script_exec(): Failed'), $e);
}
