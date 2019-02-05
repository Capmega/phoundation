<?php
/*
 * This library contains CommandLineInterface functions
 *
 * It will be automatically loaded when running on command line
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package cli
 */



/*
 * Initialize the library. Automatically executed by libs_load(). Will automatically load the ssh library configuration
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 *
 * @return void
 */
function cli_library_init(){
    global $core;

    try{
        $core->register['posix'] = true;

        ensure_installed(array('name'      => 'cli',
                               'callback'  => 'cli_install',
                               'functions' => 'posix_getuid'));

    }catch(Exception $e){
        throw new bException('cli_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the base58 library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @see cli_init_library()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the cli_library_init() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params
 * @return void
 */
function cli_install($params){
    try{
        safe_exec('sudo phpenmod posix');

    }catch(Exception $e){
        throw new bException('cli_install(): Failed', $e);
    }
}



/*
 * Return the specified string without color information
 */
function cli_strip_color($string){
    try{
        return preg_replace('/\x1B\[([0-9]{1,2}(;[0-9]{1,2})?)?[mGK]/', '',  $string);
// :DELETE:
//        return preg_replace('/\[([0-9]{1,2}(;[0-9]{1,2})?)?[mGK]/', '',  $string);
//        return preg_replace('/\033\[([0-9]{1,2}(;[0-9]{1,2})?)?[mGK]/', '',  $string);

    }catch(Exception $e){
        throw new bException('cli_strip_color(): Failed', $e);
    }
}



/*
 * Only allow execution on shell scripts
 */
function cli_only($exclusive = false){
    try{
        if(!PLATFORM_CLI){
            throw new bException('cli_only(): This can only be done from command line', 'clionly');
        }

        if($exclusive){
            cli_run_once_local();
        }

    }catch(Exception $e){
        throw new bException('cli_only(): Failed', $e);
    }
}



// :OBSOLETE: Now use cli_done();
/*
 * Die correctly on commandline
 *
 * ALWAYS USE return cli_die(); in case script_exec() was used!
 */
function cli_die($exitcode, $message = '', $color = ''){
    try{
        log_console($message, ($exitcode ? 'red' : $color));

        /*
         * Make sure we're not in a script_exec(), where die should NOT happen!
         */
        foreach(debug_backtrace() as $trace){
            if($trace['function'] == 'script_exec'){
                /*
                 * Do NOT die!!
                 */
                if($exitcode){
                    throw new bException(tr('cli_die(): Script failed with exit code ":code"', array(':code' => str_log($exitcode))), $exitcode);
                }

                return $exitcode;
            }
        }

        die($exitcode);

    }catch(Exception $e){
        throw new bException('cli_die(): Failed', $e);
    }
}



/*
 * ?
 */
function cli_code_back($count){
    try{
        $retval = '';

        for($i = 1; $i <= $count; $i++){
            $retval .= "\033[D";
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('cli_code_back(): Failed', $e);
    }
}



/*
 * Returns the shell console to return the cursor to the beginning of the line
 */
function cli_code_begin($echo = false){
    try{
        if(!$echo){
            return "\033[D";
        }

        echo "\033[D";

    }catch(Exception $e){
        throw new bException('cli_code_begin(): Failed', $e);
    }
}



/*
 * Hide anything printed to screen
 */
function cli_hide($echo = false){
    try{
        if(!$echo){
            return "\033[30;40m\e[?25l";
        }

        echo "\033[30;40m\e[?25l";

    }catch(Exception $e){
        throw new bException('cli_hide(): Failed', $e);
    }
}



/*
 * Restore screen printing
 */
function cli_restore($echo = false){
    try{
        if(!$echo){
            return "\033[0m\e[?25h";
        }

        echo "\033[0m\e[?25h";

    }catch(Exception $e){
        throw new bException('cli_restore(): Failed', $e);
    }
}



/*
 * Read input from the command line
 */
// :TODO: Implement support for answer coloring
function cli_readline($prompt = '', $hidden = false, $question_fore_color = null, $question_back_color = null, $answer_fore_color = null, $answer_back_color = null){
    try{
        if($prompt) echo cli_color($prompt, $question_fore_color, $question_back_color);

        if($hidden){
            echo cli_hide();
        }

        echo cli_color('', $answer_fore_color, $answer_back_color, false, false);
        $retval = rtrim(fgets(STDIN), "\n");
        echo cli_reset_color();

        if($hidden){
            echo cli_restore();
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('cli_readline(): Failed', $e);
    }
}



/*
 * Returns the current script that is running. script_exec() allows other
 * scripts to be run from the first, original, $core->register['script'], this function returns the
 * script currently running from script_exec()
 */
function cli_current_script(){
    global $core;

    try{
        if(empty($core->register['scripts'])){
            return $core->register['script'];
        }

        return str_rfrom(end($core->register['scripts']), '/');

    }catch(Exception $e){
        throw new bException('cli_current_script(): Failed', $e);
    }
}



/*
 * Ensure that the current script file cannot be run twice
 *
 * This function will ensure that the current script file cannot be run twice. In order to do this, it will create a run file in data/run/SCRIPTNAME with the current process id. If, upon starting, the script file already exists, it will check if the specified process id is available, and if its process name matches the current script name. If so, then the system can be sure that this script is already running, and the function will throw an exception
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 1.27.1: Added documentation
 * @example Have a script run itself recursively, which will be stopped by cli_run_once_local()
 * code
 * log_console('Started test');
 * cli_run_once_local();
 * safe_exec($core->register('script'));
 * cli_run_once_local(true);
 * /code
 *
 * This would return
 * Started test
 * cli_run_once_local(): The script ":script" for this project is already running
 * /code
 *
 * @param boolean $close If set true, the function will stop ensuring that the script won't be run again
 * @return void
 */
function cli_run_once_local($close = false){
    global $core;
    static $executed = false;

    try{
        $run_dir = ROOT.'data/run/';
        $script  = cli_current_script();

        file_ensure_path($run_dir);

        if($close){
            if(!$executed){
                /*
                 * Hey, this script is being closed but was never opened?
                 */
                throw new bException(tr('cli_run_once_local(): The function has been called with close option, but it was never opened'), 'invalid');
            }

            file_delete($run_dir.$script);
            $executed = false;
        }

        if($executed){
            /*
             * Hey, script has already been run before, and its run again
             * without the close option, this should never happen!
             */
            throw new bException(tr('cli_run_once_local(): The function has been called twice by script ":script" without $close set to true! This function should be called twice, once without argument, and once with boolean "true"', array(':script' => $script)), 'invalid');
        }

        $executed = true;

        if(file_exists($run_dir.$script)){
            /*
             * Run file exists, so either a process is running, or a process was
             * running but crashed before it could delete the run file. Check if
             * the registered PID exists, and if the process name matches this
             * one
             */
            $pid = file_get_contents($run_dir.$script);
            $pid = trim($pid);

            if(!is_numeric($pid) or !is_natural($pid) or ($pid > 65536)){
                log_console(tr('cli_run_once_local(): The run file ":file" contains invalid information, ignoring', array(':file' => $run_dir.$script)), 'yellow');

            }else{
                $name = safe_exec('ps -p '.$pid.' | tail -n 1');
                $name = array_pop($name);

                if($name){
                    preg_match_all('/.+?\d{2}:\d{2}:\d{2}\s+('.$script.')/', $name, $matches);

                    if(!empty($matches[1][0])){
                        throw new bException(tr('cli_run_once_local(): The script ":script" for this project is already running', array(':script' => $script)), 'already-running');
                    }
                }
            }

            /*
             * File exists, or contains invalid data, but PID either doesn't
             * exist, or is used by a different process. Remove the PID file
             */
            log_console(tr('cli_run_once_local(): Cleaning up stale run file ":file"', array(':file' => $run_dir.$script)), 'VERBOSE/yellow');
            file_delete($run_dir.$script);
        }

        /*
         * No run file exists yet, create one now
         */
        file_put_contents($run_dir.$script, getmypid());
        $core->register('shutdown_cli_run_once_local', array(true));

    }catch(Exception $e){
        if($e->getCode() == 'already-running'){
            /*
            * Just keep throwing this one
            */
            throw($e);
        }

        throw new bException('cli_run_once_local(): Failed', $e);
    }
}



/*
 * Returns true if the startup script is already running
 */
function cli_run_max_local($processes){
    static $executed = false;
under_construction();
    try{
        $run_dir = ROOT.'data/run/';
        $script  = cli_current_script();

        file_ensure_path($run_dir);

        if($processes === false){
            if(!$executed){
                /*
                 * Hey, this script is being closed but was never opened?
                 */
                throw new bException(tr('cli_run_max_local(): The cli_run_max_local() has been called with close option, but it was never opened'), 'invalid');
            }

            file_delete($run_dir.$script);
            $executed = false;
            return true;
        }

        if($executed){
            /*
             * Hey, script has already been run before, and its run again
             * without the close option, this should never happen!
             */
            throw new bException(tr('cli_run_max_local(): The cli_run_max_local() has been called twice by script ":script" without $processes set to false! This function should be called twice, once without argument, and once with boolean "true"', array(':script' => $script)), 'invalid');
        }

        $executed = true;

        if(file_exists($run_dir.$script)){
            /*
             * Run file exists, so either a process is running, or a process was
             * running but crashed before it could delete the run file. Check if
             * the registered PID exists, and if the process name matches this
             * one
             */
            $pid = file_get_contents($run_dir.$script);
            $pid = trim($pid);

            if(!is_numeric($pid) or !is_natural($pid) or ($pid > 65536)){
                log_console(tr('cli_run_max_local(): The run file ":file" contains invalid information, ignoring', array(':file' => $run_dir.$script)), 'yellow');

            }else{
                $name = safe_exec('ps -p '.$pid.' | tail -n 1');
                $name = array_pop($name);

                if($name){
                    preg_match_all('/.+?\d{2}:\d{2}:\d{2}\s+('.$script.')/', $name, $matches);

                    if(!empty($matches[1][0])){
                        throw new bException(tr('cli_run_max_local(): The script ":script" for this project is already running', array(':script' => $script)), 'already-running');
                    }
                }
            }

            /*
             * File exists, or contains invalid data, but PID either doesn't
             * exist, or is used by a different process. Remove the PID file
             */
            log_console(tr('cli_run_max_local(): Cleaning up stale run file ":file"', array(':file' => $run_dir.$script)), 'yellow');
            file_delete($run_dir.$script);
        }

        /*
         * No run file exists yet, create one now
         */
        file_put_contents($run_dir.$script, getmypid());
        return true;

    }catch(Exception $e){
        if($e->getCode() == 'max-running'){
            /*
            * Just keep throwing this one
            */
            throw($e);
        }

        throw new bException('cli_run_max_local(): Failed', $e);
    }
}



/*
 * Returns true if the startup script is already running
 */
function cli_run_once($action = 'exception', $force = false){
    global $core;

    try{
        if(!PLATFORM_CLI){
            throw new bException('cli_run_once(): This function does not work for platform "'.PLATFORM.'", it is only for "shell" usage');
        }

        exec('ps -eF | grep php | grep -v grep', $output);

        /*
        * This scan will a possible other script AND our own, so we can only know
        * if it is already running if we count two or more of these scripts.
        */
        $count = 0;

        foreach($output as $line){
            $line = preg_match('/\d+:\d+:\d+ .*? (.*?)$/', $line, $matches);

            if(empty($matches[1])){
                continue;
            }

            $process = str_until(str_rfrom($matches[1], '/'), ' ');

            if($process == $core->register['script']){
                if(++$count >= 2){
                    switch($action){
                        case 'exception':
                            throw new bException('cli_run_once(): This script is already running', 'already-running');

                        case 'kill':
                            $thispid = getmypid();

                            foreach($output as $line){
                                if(!preg_match('/^\s*?\w+?\s+?(\d+)/', trim($line), $matches) or empty($matches[1])){
                                    /*
                                     * This entry does not contain valid process id information
                                     */
                                    continue;
                                }

                                $pid = $matches[1];

                                if($pid == $thispid){
                                    /*
                                     * We're not going to suicide!
                                     */
                                    continue;
                                }

                                cli_kill($pid, ($force ? 9 : 15));
                            }

                            return false;

                        default:
                            throw new bException('cli_run_once(): Unknown action "'.str_log($action).'" specified', 'unknown');
                    }

                    return true;
                }
            }
        }

        return false;

    }catch(Exception $e){
        if($e->getCode() == 'already-running'){
            /*
            * Just keep throwing this one
            */
            throw($e);
        }

        throw new bException('cli_run_once(): Failed', $e);
    }
}



/*
 * Find the specified method, basically any argument without - or --
 *
 * The result will be removed from $argv, but will remain stored in a static
 * variable which will return the same result every subsequent function call
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @see cli_argument()
 * @see cli_arguments()
 *
 * @param natural $index The method number that is requested. 0 (default) is the first method, 1 the second, etc.
 * @param mixed $default The value to be returned if no method was found
 * @return array The results of the executed SSH commands in an array, each entry containing one line of the output
 */
function cli_method($index = null, $default = null){
    global $argv;
    static $method = array();

    try{
        if($default === false){
            $method[$index] = null;
        }

        if(isset($method[$index])){
            $reappeared = array_search($method[$index], $argv);

            if(is_numeric($reappeared)){
                /*
                 * The argument has been readded to $argv. This is very likely
                 * happened by safe_exec() that included the specified script
                 * into itself, and had to reset the arguments array
                 */
                unset($argv[$reappeared]);
            }

            return $method[$index];
        }

        foreach($argv as $key => $value){
            if(substr($value, 0, 1) !== '-'){
                unset($argv[$key]);
                $method[$index] = $value;
                return $value;
            }
        }

        return $default;

    }catch(Exception $e){
        throw new bException('cli_method(): Failed', $e);
    }
}



/*
 * Safe and simple way to get arguments
 *
 * This function will REMOVE and then return the argument when its found
 * If the argument is not found, $default will be returned
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 *
 * @param mixed $keys (NOTE: See $next for what will be returned) If set to a numeric value, the value from $argv[$key] will be selected. If set as a string value, the $argv key where the value is equal to $key will be selected. If set specified as an array, all entries in the specified array will be selected.
 * @params mixed $next. If set to true, cli_argument() REQUIRES that the specified key contains a next argument, and this will be returned. If set to "all", it will return all following arguments. If set to "optional", a next argument will be retuned, if available.
 * @return mixed If $next is null, cli_argument() will return a boolean value, true if the specified key exists, false if not. If $next is true or "optional", the next value will be returned as a string. However, if "optional" was used, and the next value was not specified, boolean FALSE will be returned instead. If $next is specified as all, all subsequent values will be returned in an array
 */
function cli_argument($keys = null, $next = null, $default = null){
    global $argv;

    try{
        if(is_integer($keys)){
            $count = count($argv) - 1;

            if($next === 'all'){
// :TODO: This could be optimized using a for() starting at $keys instead of a foreach() over all entries
                foreach($argv as $argv_key => $argv_value){
                    if($argv_key < $keys){
                        continue;
                    }

                    if($argv_key == $keys){
                        unset($argv[$keys]);
                        continue;
                    }

                    if(substr($argv_value, 0, 1) == '-'){
                        /*
                         * Encountered a new option, stop!
                         */
                        break;

                    }

                    /*
                     * Add this argument to the list
                     */
                    $retval[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return isset_get($retval);
            }

            if(!empty($argv[$keys++])){
                $argument = $argv[$keys - 1];
                unset($argv[$keys - 1]);
                return $argument;
            }

            /*
             * No arguments found (except perhaps for test or force)
             */
            return $default;
        }

        if($keys === null){
            $retval = array_shift($argv);
            $retval = str_starts_not($retval, '-');
            return $retval;
        }

        /*
         * Detect multiple key options for the same command, but ensure only one
         * is specified
         */
        if(is_array($keys) or (is_string($keys) and strstr($keys, ','))){
            $keys    = array_force($keys);
            $results = array();

            foreach($keys as $key){
                if($next === 'all'){
                    /*
                     * We're requesting all values for all specified keys
                     * cli_argument will return null in case the specified key
                     * does not exist
                     */
                    $value = cli_argument($key, 'all', null);

                    if(is_array($value)){
                        $found   = true;
                        $results = array_merge($results, $value);
                    }

                }else{
                    $value = cli_argument($key, $next, null);

                    if($value){
                        $results[$key] = $value;
                    }
                }
            }

            if(($next === 'all') and isset($found)){
                return $results;
            }

            switch(count($results)){
                case 0:
                    return $default;

                case 1:
                    return current($results);

                default:
                    /*
                     * Multiple command line options were specified, this is not
                     * allowed!
                     */
                    throw new bException(sprintf('cli_argument(): Multiple command line arguments "%s" for the same option specified. Please specify only one', implode(', ', array_keys($results))), 'warning/multiple');
            }
        }

        if(($key = array_search($keys, $argv)) === false){
            /*
             * Specified argument not found
             */
            return $default;
        }

        if($next){
            if($next === 'all'){
                /*
                 * Return all following arguments, if available, until the next option
                 */
                $retval = array();

                foreach($argv as $argv_key => $argv_value){
                    if(empty($start)){
                        if($argv_value == $keys){
                            $start = true;
                            unset($argv[$argv_key]);
                        }

                        continue;
                    }

                    if(substr($argv_value, 0, 1) == '-'){
                        /*
                         * Encountered a new option, stop!
                         */
                        break;
                    }

                    /*
                     * Add this argument to the list
                     */
                    $retval[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return $retval;
            }

            /*
             * Return next argument, if available
             */
            try{
                $retval = array_next_value($argv, $keys, true);

            }catch(Exception $e){
                if($e->getCode() == 'invalid'){
                    if($next !== 'optional'){
                        /*
                         * This argument requires another parameter
                         */
                        throw $e->setCode('missing-arguments');
                    }

                    $retval = false;
                }
            }

            if(substr($retval, 0, 1) == '-'){
                throw new bException(tr('cli_argument(): Argument ":argument1" has no assigned value, it is immediately followed by argument ":argument2"', array(':argument1' => $keys, ':argument2' => $retval)), 'invalid');
            }

            return $retval;
        }

        unset($argv[$key]);
        return true;

    }catch(Exception $e){
        throw new bException(tr('cli_argument(): Failed'), $e);
    }
}



/*
 *
 */
function cli_arguments($arguments = null){
    global $argv;

    try{
        if(!$arguments){
            $retval = $argv;
            $argv   = array();
            return $retval;
        }

        $retval = array();

        foreach(array_force($arguments) as $argument){
            if(is_numeric($argument)){
                /*
                 * If the key would be numeric, argument() would get into an endless loop
                 */
                throw new bException(tr('cli_arguments(): The specified argument ":argument" is numeric, and as such, invalid. cli_arguments() can only check for key-value pairs, where the keys can not be numeric', array(':argument' => $argument)), 'invalid');
            }

            if($value = cli_argument($argument, true)){
                $retval[str_replace('-', '', $argument)] = $value;
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new bException(tr('cli_arguments(): Failed'), $e);
    }
}



/*
 * Ensures that no other command line arguments are left.
 * If arguments were still found, an appropriate error will be thrown
 */
function cli_no_arguments_left(){
    global $argv;

    if(!$argv){
        return true;
    }

    throw new bException(tr('cli_no_arguments_left(): Unknown arguments ":arguments" encountered', array(':arguments' => str_force($argv, ', '))), 'invalid-arguments');
}



/*
 * Mark the specified keywords in the specified string with the specified color
 */
function cli_highlight($string, $keywords, $fore_color, $back_color = null){
    static $color;

    try{
        if(!$color){
            $color = new Colors();
        }

        foreach(array_force($keywords) as $keyword){
            $string = str_replace($keyword, $color->getColoredString($string, $fore_color, $back_color), $string);
        }

        return $string;

    }catch(Exception $e){
        throw new bException('cli_highlight(): Failed', $e);
    }
}



/*
 * Show error on screen with usage
 */
function cli_error($e = null){
    global $usage;

    switch($e->getCode()){
        case 'already-running':
            break;

        default:
            if(!empty($usage)){
                echo "\n";
                cli_show_usage($usage, 'white');
            }
    }
}



/*
 *
 */
function cli_show_usage($usage, $color){
    try{
        if(!$usage){
            log_console(tr('Sorry, this script has no usage description defined yet'), 'yellow');

        }else{
            $usage = array_force(trim($usage), "\n");

            if(count($usage) == 1){
                log_console(tr('Usage:')       , $color);
                log_console(array_shift($usage), $color);

            }else{
                log_console(tr('Usage:'), $color);

                foreach(array_force($usage, "\n") as $line){
                    log_console($line, $color);
                }

                log_console();
            }
        }

    }catch(Exception $e){
        throw new bException('cli_show_usage(): Failed', $e);
    }
}



/*
 * Ensures that the UID of the user executing this script is the same as the UID of this libraries' owner
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 *
 * @param boolean $auto_switch If set to true, the script
 * @return void
 */
function cli_process_uid_matches($auto_switch = false){
    global $core;

    try{
        if(cli_get_process_uid() !== getmyuid()){
            if(!$auto_switch){
                throw new bException(tr('cli_process_uid_matches(): The user ":puser" is not allowed to execute these scripts, only user ":fuser" can do this. use "sudo -u :fuser COMMANDS instead.', array(':puser' => get_current_user(), ':fuser' => cli_get_process_user())), 'not-authorized');
            }

            /*
             * Re-execute this command as the specified user
             */
            log_console(tr('Current user ":user" is not authorized to execute this script, reexecuting script as user ":reuser"', array(':user' => cli_get_process_uid(), ':reuser' => cli_get_process_user())), 'yellow', true, false, false);
            passthru(cli_sudo('sudo -Eu "'.get_current_user().'" '.ROOT.'scripts/'.str_rfrom($core->register['script_command'], 'scripts/')));
            die();
        }

    }catch(Exception $e){
        throw new bException('cli_process_uid_matches(): Failed', $e);
    }
}



/*
 * Returns true if the user executing this process has sudo rights without password requirements
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
function cli_process_user_has_free_sudo(){
    try{
        $result = safe_exec('timeout 0.1 sudo -v');
        $result = array_pop($result);

        if($result){
            return false;
        }

        return true;

    }catch(Exception $e){
        throw new bException('cli_process_user_has_free_sudo(): Failed', $e);
    }
}



/*
 * Returns the UID for the current process
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
function cli_get_process_uid(){
    global $core;

    try{
        if($core->register['posix']){
            return posix_getuid();
        }

        $result = safe_exec('id -u');
        $result = array_pop($result);

        return $result;

    }catch(Exception $e){
        throw new bException('cli_get_process_uid(): Failed', $e);
    }
}



/*
 * Returns the UID for the current process
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
function cli_get_process_user(){
    global $core;

    try{
        if($core->register['posix']){
            return posix_getpwuid(posix_geteuid())['name'];;
        }

        $result = safe_exec('id -un');
        $result = array_pop($result);

        return $result;

    }catch(Exception $e){
        throw new bException('cli_get_process_user(): Failed', $e);
    }
}



/*
 * Throws exception if this script is not being run as root user
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
function cli_is_root(){
    try{
        return cli_get_process_uid() === 0;

    }catch(Exception $e){
        throw new bException('cli_is_root(): Failed', $e);
    }
}



/*
 * Throws exception if this script is not being run as root user
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
function cli_root_only(){
    try{
        if(!cli_is_root()){
            throw new bException('cli_root_only(): This script can ONLY be executed by the root user', 'not-allowed');
        }

        return true;

    }catch(Exception $e){
        throw new bException('cli_root_only(): Failed', $e);
    }
}



/*
 * Throws exception if this script is being run as root user
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
function cli_not_root(){
    try{
        if(cli_is_root()){
            throw new bException('cli_not_root(): This script can NOT be executed by the root user', 'not-allowed');
        }

        return true;

    }catch(Exception $e){
        throw new bException('cli_not_root(): Failed', $e);
    }
}



/*
 * Throws exception if the user of this process has no free sudo rights
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 */
function cli_sudo($command){
    try{
        if(!cli_process_user_has_free_sudo()){
            throw new bException(tr('cli_sudo(): This script requires sudo privileges but the current user ":user" does not have these', array(':user' => cli_get_process_user())), 'no-sudo');
        }

        return $command;

    }catch(Exception $e){
        throw new bException('cli_sudo(): Failed', $e);
    }
}



/*
 *
 */
function cli_arguments_none_left(){
    return cli_no_arguments_left();
}



/*
 *
 */
function cli_done(){
    global $core;

    try{
        if(!isset($core)){
            echo "\033[1;31mCommand line terminated before \$core created\033[0m\n";
            die(1);
        }

        if($core === false){
            /*
             * Core wasn't created yet, but uncaught exception handler basically
             * is saying that's okay, just warning stuff
             */
            die(1);
        }

        $exit_code = isset_get($core->register['exit_code'], 0);

        /*
         * Execute all shutdown functions
         */
        shutdown();

        if(!QUIET){
            load_libs('time,numbers');

            if($exit_code and is_numeric($exit_code)){
                if($exit_code > 200){
                    /*
                     * Script ended with warning
                     */
                    log_console(tr('Script ":script" ended with warning in :time with ":usage" peak memory usage', array(':script' => $core->register['script'], ':time' => time_difference(STARTTIME, microtime(true), 'auto', 2), ':usage' => bytes(memory_get_peak_usage()))), 'yellow');

                }else{
                    log_console(tr('Script ":script" failed in :time with ":usage" peak memory usage', array(':script' => $core->register['script'], ':time' => time_difference(STARTTIME, microtime(true), 'auto', 2), ':usage' => bytes(memory_get_peak_usage()))), 'red');
                }

            }else{
                log_console(tr('Finished ":script" script in :time with ":usage" peak memory usage', array(':script' => $core->register['script'], ':time' => time_difference(STARTTIME, microtime(true), 'auto', 2), ':usage' => bytes(memory_get_peak_usage()))), 'green');
            }
        }

        die($exit_code);

    }catch(Exception $e){
        throw new bException('cli_done(): Failed', $e);
    }
}



/*
 * Returns the PID list for the specified process name, if exists
 */
function cli_pgrep($name){
    try{
        return safe_exec('pgrep '.$name, 1);

    }catch(Exception $e){
        throw new bException('cli_pgrep(): Failed', $e);
    }
}



/*
 * Returns the process name for the specified PID
 */
function cli_pidgrep($pid){
    try{
        $results = safe_exec('ps '.$pid.' | grep -v "PID TTY      STAT   TIME COMMAND"', '0,1');
        $result  = array_pop($results);
        $result  = substr($result, 27);

        return $result;

    }catch(Exception $e){
        throw new bException('cli_pgrep(): Failed', $e);
    }
}



/*
 * Send signal to the specified PID. By default, signal KILL (15) will be sent, and up to $vakudate validations will be executed ensuring the PID has closed. If $verify is negative, and after all validations have passed, the PID is still there, a SIGKILL (9) will be sent and the function terminates.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 *
 * @param numeric $pid
 * @return void
 */
function cli_kill($pid, $signal = 15, $verify = -20, $sudo = false){
    try{
        if(!$signal){
            $signal = 15;
        }

        /*
         * pkill returns 1 if process wasn't found, we can ignore that
         */
        log_console(tr('Killing PID ":pid" with signal ":signal"', array(':pid' => $pid, ':signal' => $signal)), 'VERBOSE/cyan');
        safe_exec(($sudo ? 'sudo ' : '').'kill -'.$signal.' '.$pid, 1);

        if($verify){
            $sigkill = ($verify < 0);
            $verify  = abs($verify);

            while(--$verify >= 0){
                usleep(100000);

                /*
                 * Ensure that the progress is gone
                 */
                if(!cli_pidgrep($pid)){
                    /*
                     * Killed it softly
                     */
                    return true;
                }
                log_console(tr('Waiting for PID ":pid" to die...', array(':pid' => $pid)), 'cyan');
                usleep(100000);
            }

            if($sigkill){
                /*
                 * Sigkill it!
                 */
                log_console(tr('Killing PID ":pid" with signal ":signal"', array(':pid' => $pid, ':signal' => 9)), 'cyan');
                $result = cli_kill($pid, 9, 0, $sudo);

                if($result){
                    /*
                     * Killed it the hard way!
                     */
                    return true;
                }
            }

            throw new bException(tr('cli_kill(): Failed to kill PID ":pid"', array(':pid' => $pid)), 'failed');
        }

    }catch(Exception $e){
        throw new bException('cli_kill(): Failed', $e);
    }
}



/*
 * Send a signal to the specified process. S
 */
function cli_pkill($process, $signal = null, $sudo = false, $verify = 3, $sigkill = true){
    try{
        if(!$signal){
            $signal = 15;
        }

        /*
         * pkill returns 1 if process wasn't found, we can ignore that
         */
        safe_exec(($sudo ? 'sudo ' : '').'pkill -'.$signal.' '.$process, 1);

        if($verify){
            while(--$verify >= 0){
                sleep(0.5);

                /*
                 * Ensure that the progress is gone
                 */
                $results = cli_pgrep($process);

                if(!$results){
                    /*
                     * Killed it softly
                     */
                    return true;
                }

                sleep(0.5);
            }

            if($sigkill){
                /*
                 * Sigkill it!
                 */
                $result = cli_pkill($process, 9, $sudo, $verify, false);

                if($result){
                    /*
                     * Killed it the hard way!
                     */
                    return true;
                }
            }

            throw new bException(tr('cli_pkill(): Failed to kill process ":process"', array(':process' => $process)), 'failed');
        }

    }catch(Exception $e){
        throw new bException('cli_pkill(): Failed', $e);
    }
}



/*
 *
 */
function cli_get_term(){
    try{
        $term = exec('echo $TERM');
        return $term;

    }catch(Exception $e){
        throw new bException('cli_get_term(): Failed', $e);
    }
}



/*
 *
 */
function cli_get_columns(){
    try{
        $cols = exec('tput cols');
        return $cols;

    }catch(Exception $e){
        throw new bException('cli_get_columns(): Failed', $e);
    }
}


/*
 *
 */
function cli_get_lines(){
    try{
        $rows = exec('tput lines');
        return $rows;

    }catch(Exception $e){
        throw new bException('cli_get_lines(): Failed', $e);
    }
}



/*
 * Run a process and run callbacks over the output
 */
function cli_run_process($command, $callback){
    try{
//$p = popen('executable_file_or_script', 'r');
//while(!feof($p)) {
//    echo fgets($p);
//    ob_flush();
//    flush();
//}
//pclose($p);

    }catch(Exception $e){
        throw new bException('cli_run_process(): Failed', $e);
    }
}



/*
 * Show a X% width pogress bar on the current line
 * See https://github.com/guiguiboy/PHP-CLI-Progress-Bar/blob/master/ProgressBar/Manager.php for inspiration
 */
function cli_progress_bar($width, $percentage, $color){
    try{

    }catch(Exception $e){
        throw new bException('cli_progress_bar(): Failed', $e);
    }
}



/*
 *
 */
function cli_status_color($status){
    try{
        $status = status($status);

        switch(trim(strtolower($status))){
            case 'ok':
                // FALLTHROUGH
            case 'completed':
                return cli_color($status, 'green');

            case 'processing':
                return cli_color($status, 'light_blue');

            case 'failed':
                return cli_color($status, 'red');

            case 'deleted':
                return cli_color($status, 'yellow');

            default:

        }

        return $status;

    }catch(Exception $e){
        throw new bException('cli_status_color(): Failed', $e);
    }
}



/*
 * Check if the specified PID is available
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package linux
 *
 * @param natural $pid The PID to be tested
 * @return boolean True if the specified PID is available on the specified server, false otherwise
 */
function cli_pid($pid){
    try{
        return file_exists('/proc/'.$pid);

    }catch(Exception $e){
        throw new bException('cli_pid(): Failed', $e);
    }
}



/*
 * Check if the specified CLI command exists or not
 *
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 *
 * @param string $command the CLI command to be tested
 * @return boolean true if the specified command exists, false if not.
 */
function cli_command_exists($command){
    try{
        $exists = safe_exec('which "'.$command.'"');
        return (boolean) $exists;

    }catch(Exception $e){
        throw new bException(tr('cli_command_exists(): Failed'), $e);
    }
}



/*
 * Return all system processes that match the specified filters
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package ssh
 *
 * @param numeric $source_port
 * @param numeric $hostname
 * @param numeric $target_port
 * @param numeric $target_hostname
 * @return numeric PID of the found tunnel with the specified parameters, null if no tunnel was found
 */
function cli_list_processes($filters){
    try{
        $filters = array_force($filters);

        foreach($filters as &$filter){
            $filter = trim($filter);

            if($filter[0] == '-'){
                $filter = '\\\\'.$filter;
            }

            $filter = '"'.$filter.'"';
        }

        unset($filter);

        $filters = implode(' | grep --color=never ', $filters);
        $command = 'ps ax | grep --color=never '.$filters;
        $results = safe_exec($command, '0,1');
        $retval  = array();

        foreach($results as $key => $result){
            if(strstr($result, $command)){
                unset($results[$key]);
                continue;
            }

            $result       = trim($result);
            $pid          = str_until($result, ' ');
            $retval[$pid] = substr($result, 27);
        }

        return $retval;

    }catch(Exception $e){
        throw new bException('cli_list_processes(): Failed', $e);
    }
}



/*
 * Unzip the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package cli
 * @version 2.0.5: Added function and documentation
 *
 * @param string $file The file to be unzipped
 * @return string The path of the specified file
 */
function cli_unzip($file){
    try{
        $path = dirname($file);
        safe_exec('cd '.$path.'; unzip "'.$file.'"');

// :TODO: ENSURE THAT $PATH HAS A TERMINATING SLASH!
showdie($path);
        return $path;

    }catch(Exception $e){
        if(!file_exists($file)){
            throw new bException(tr('cli_unzip(): The specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
        }

        throw new bException('cli_unzip(): Failed', $e);
    }
}



/*
 * WARNING! BELOW HERE BE OBSOLETE FUNCTIONS AND OBSOLETE-BUT-WE-WANT-TO-BE-BACKWARD-COMPATIBLE WRAPPERS
 */
function this_script_already_runs($action = 'exception', $force = false){
    return cli_run_once($action, $force);
}

function cli_exclusive($action = 'exception', $force = false){
    return cli_run_once($action, $force);
}
?>
