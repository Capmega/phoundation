<?php

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Exception\CliException;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Numbers;
use Phoundation\Core\Strings;
use Phoundation\Date\Time;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Path;
use Phoundation\Processes\Commands\Command;
use Throwable;



/**
 * Class Scripts
 *
 * This is the default Scripts object
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cli
 */
class Script
{
    /**
     * The exit code for this process
     *
     * @var int $exit_code
     */
    protected static int $exit_code = 0;

    /**
     * The arguments given to the executed script
     *
     * @var array|null $arguments
     */
    protected static ?array $arguments = null;

    /**
     * The command line arguments
     *
     * @var array|null $argv
     */
    protected static ?array $argv = null;



    /**
     * Execute a command by the "cli" script
     *
     * @return void
     * @throws Throwable
     */
    public static function execute(): void
    {
        // Backup the command line arguments
        self::$argv      =  $GLOBALS['argv'];
        self::$arguments = &$GLOBALS['argv'];

        // All scripts will execute the cli_done() call, register basic script information
        Core::startup();
        Core::registerShutdown(['\Phoundation\Cli\Script', 'shutdown']);

        // Only allow this to be run by the cli script
        // TODO This should be done before Core::startup() but then the PLATFORM_CLI define would not exist yet. Fix this!
        self::only();

        if (count(self::$argv) <= 1) {
            throw Exceptions::OutOfBoundsException('No method specified!')->makeWarning();
        }

        // Get the script file to execute
        $file = self::findScript();

        Core::writeRegister($file, 'system', 'script');
        Core::writeRegister(Strings::fromReverse($file, '/'), 'script');

        // Copy argv arguments back
        // TODO This should be done later AFTER all ddata has been validated!
        $GLOBALS['argv'] = self::$arguments;

        // Execute the script
        execute_script($file, self::$arguments);
    }



    /**
     * Script execution has finished
     *
     * @param int|null $exit_code
     * @param string|null $exit_message
     * @return void
     */
    #[NoReturn] public static function shutdown(?int $exit_code = null, ?string $exit_message = null): void
    {
        if ($exit_code) {
            Script::setExitCode($exit_code, true);
        }

        if (!QUIET) {
            if ($exit_code) {
                if ($exit_code > 200) {
                    if ($exit_message) {
                        Log::warning($exit_message);
                    }

                    // Script ended with warning
                    Log::warning(tr('Script ":script" ended with exit code ":exitcode" warning in ":time" with ":usage" peak memory usage', [
                        ':script'   => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                        ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                        ':usage'    => Numbers::bytes(memory_get_peak_usage()),
                        ':exitcode' => $exit_code
                    ]), 8);

                } else {
                    if ($exit_message) {
                        Log::error($exit_message);
                    }

                    // Script ended with error
                    Log::error(tr('Script ":script" failed with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                        ':script'   => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                        ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                        ':usage'    => Numbers::bytes(memory_get_peak_usage()),
                        ':exitcode' => $exit_code
                    ]), 8);
                }

            } else {
                if ($exit_message) {
                    Log::success($exit_message);
                }

                // Script ended successfully
                Log::success(tr('Finished ":script" script in ":time" with ":usage" peak memory usage', [
                    ':script' => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                    ':time'   => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'  => Numbers::bytes(memory_get_peak_usage())
                ]), 8);
            }
        }

        die($exit_code);
    }



    /**
     * Returns the process exit code
     *
     * @return int
     */
    public static function getExitCode(): int
    {
        return self::$exit_code;
    }



    /**
     * Sets the process exit code
     *
     * @param int $code
     * @param bool $only_if_null
     * @return void
     */
    public static function setExitCode(int $code, bool $only_if_null = false): void
    {
        if (($code < 0) or ($code > 255)) {
            throw new OutOfBoundsException(tr('Invalid exit code ":code" specified, it should be a positive integer value between 0 and 255', [':code' => $code]));
        }

        if (!$only_if_null or !self::$exit_code) {
            self::$exit_code = $code;
        }
    }



    /**
     * Returns the UID for the current process
     */
    public static function getProcessUid(): int
    {
        if (function_exists('posix_getuid')) {
            return posix_getuid();
        }

        return Command::local()->id('u');
    }



    /**
     * Ensures that no other command line arguments are left.
     *
     * If arguments were still found, an appropriate exceptoin will be thrown
     *
     * @return void
     */
    public static function noArgumentsLeft(): void
    {
        global $argv;

        if (empty($argv)) {
            return;
        }

        throw Exceptions::CliInvalidArgumentsException(tr('Invalid arguments ":arguments" encountered', [':arguments' => Strings::force($argv, ', ')]))->makeWarning();
    }



    /**
     * Find the script to execute from the given arguments
     *
     * @return string
     */
    protected static function findScript(): string
    {
        $file     = PATH_ROOT . 'scripts/';
        $argument = null;

        foreach (self::$arguments as $position => $argument) {
            if (str_ends_with($argument, '/cli')) {
                // This is the cli command, ignore it
                unset(self::$arguments[$position]);
                continue;
            }

            if (!preg_match('/[a-z0-9-]/i', $argument)) {
                // Methods can only have alphanumeric characters
                throw Exceptions::OutOfBoundsException(tr('The specified method ":method" contains invalid characters. only a-z, 0-9 and - are allowed', [
                    ':method' => $argument
                ]))->makeWarning();
            }

            if (str_starts_with($argument, '-')) {
                // Methods can only have alphanumeric characters
                throw Exceptions::OutOfBoundsException(tr('The specified method ":method" starts with a - character which is not allowed', [
                    ':method' => $argument
                ]))->makeWarning();
            }

            // Start processing arguments as methods here
            $file .= $argument;
            unset(self::$arguments[$position]);

            if (!file_exists($file)) {
                // The specified path doesn't exist
                throw Exceptions::MethodNotFoundException(tr('The specified method file ":file" was not found', [
                    ':file' => $file
                ]))->makeWarning();
            }

            if (!is_dir($file)) {
                // This is a file, should be PHP, found it! Update the arguments to remove all methods from them.
                return $file;
            }

            // This is a directory.
            $file .= '/';

            // Does a file with the directory name exists inside?
            if (file_exists($file . $argument)) {
                if (!is_dir($file . $argument)) {
                    // This is the file!
                    return $file . $argument;
                }
            }

            // Continue scanning
        }

        // Here we're still in a directory. If a file exists in that directory with the same name as the directory
        // itself then that is the one that will be executed. For example, PATH_ROOT/cli system init will execute
        // PATH_ROOT/scripts/system/init/init
        if (file_exists($file . $argument)) {
            if (!is_dir($file . $argument)) {
                // Yup, this is it guys!
                return $file . $argument;
            }
        }

        // We're stuck in a directory still, no script to execute
        throw Exceptions::MethodNotFoundException(tr('The specified method file ":file" was not found', [
            ':file' => $file
        ]))->makeWarning();
    }


    /**
     * Only allow execution on shell scripts
     *
     * @param bool $exclusive
     * @throws CliException
     */
    public static function only(bool $exclusive = false): void
    {
        if (!PLATFORM_CLI) {
            throw new CliException(tr('This can only be done from command line'));
        }

        if ($exclusive) {
            self::runOnceLocal();
        }
    }



    /**
     * Ensure that the current script file cannot be run twice
     *
     * This function will ensure that the current script file cannot be run twice. In order to do this, it will create a run file in data/run/SCRIPTNAME with the current process id. If, upon starting, the script file already exists, it will check if the specified process id is available, and if its process name matches the current script name. If so, then the system can be sure that this script is already running, and the function will throw an exception
     *
     * @category Function reference
     * @version 1.27.1: Added documentation
     * @example Have a script run itself recursively, which will be stopped by cli_run_once_local()
     * code
     * log_console('Started test');
     * cli_run_once_local();
     * safe_exec(Core::readRegister('system', 'script'));
     * cli_run_once_local(true);
     * /code
     *
     * This would return
     * Started test
     * cli_run_once_local(): The script ":script" for this project is already running
     * /code
     *
     * @param bool $close If set true, the function will stop ensuring that the script won't be run again
     * @return void
     */
    public static function runOnceLocal(bool $close = false)
    {
        static $executed = false;

        throw new UnderConstructionException();
        try {
            $run_dir = PATH_ROOT.'data/run/';
            $script  = $core->register['script'];

            Path::ensure(dirname($run_dir.$script));

            if ($close) {
                if (!$executed) {
                    // Hey, this script is being closed but was never opened?
                    Log::warning(tr('The cli_run_once_local() function has been called with close option, but it was already closed or never opened.'));
                }

                file_delete(array('patterns'     => $run_dir.$script,
                    'restrictions' => PATH_ROOT.'data/run/',
                    'clean_path'   => false));
                $executed = false;
                return;
            }

            if ($executed) {
                // Hey, script has already been run before, and its run again without the close option, this should
                // never happen!
                throw new CliException(tr('The function has been called twice by script ":script" without $close set to true! This function should be called twice, once without argument, and once with boolean "true"', [
                    ':script' => $script
                ]));
            }

            $executed = true;

            if (file_exists($run_dir.$script)) {
                // Run file exists, so either a process is running, or a process was running but crashed before it could
                // delete the run file. Check if the registered PID exists, and if the process name matches this one
                $pid = file_get_contents($run_dir.$script);
                $pid = trim($pid);

                if (!is_numeric($pid) or !is_natural($pid) or ($pid > 65536)) {
                    Log::warning(tr('The run file ":file" contains invalid information, ignoring', [':file' => $run_dir.$script]));

                } else {
                    $name = safe_exec(array('commands' => array('ps'  , array('-p', $pid, 'connector' => '|'),
                        'tail', array('-n', 1))));
                    $name = array_pop($name);

                    if ($name) {
                        preg_match_all('/.+?\d{2}:\d{2}:\d{2}\s+('.str_replace('/', '\/', $script).')/', $name, $matches);

                        if (!empty($matches[1][0])) {
                            throw new CliException(tr('cli_run_once_local(): The script ":script" for this project is already running', array(':script' => $script)), 'already-running');
                        }
                    }
                }

                // File exists, or contains invalid data, but PID either doesn't exist, or is used by a different
                // process. Remove the PID file
                Log::warning(tr('cli_run_once_local(): Cleaning up stale run file ":file"', [':file' => $run_dir.$script]));
                file_delete(array('patterns'     => $run_dir.$script,
                    'restrictions' => PATH_ROOT.'data/run/',
                    'clean_path'   => false));
            }

            // No run file exists yet, create one now
            file_put_contents($run_dir.$script, getmypid());
            Core::readRegister('shutdown_cli_run_once_local', array(true));

        }catch(Exception $e) {
            if ($e->getCode() == 'already-running') {
                /*
                * Just keep throwing this one
                */
                throw($e);
            }

            throw new CliException('cli_run_once_local(): Failed', $e);
        }
    }



    /**
     * Find the specified method, basically any argument without - or --
     *
     * The result will be removed from $argv, but will remain stored in a static
     * variable which will return the same result every subsequent function call
     *
     * @param int|null    $index   The method number that is requested. 0 (default) is the first method, 1 the second,
     *                             etc.
     * @param string|null $default The value to be returned if no method was found
     * @return string              The results of the executed SSH commands in an array, each entry containing one line
     *                             of the output
     *
     * @see cli_arguments()
     * @see Script::argument()
     */
    function method(?int $index = null, ?string $default = null): string
    {
        global $argv;
        static $method = [];

        if (isset($method[$index])) {
            $reappeared = array_search($method[$index], $argv);

            if (is_numeric($reappeared)) {
                // The argument has been re-added to $argv. This is very likely happened by safe_exec() that included
                // the specified script into itself, and had to reset the arguments array
                unset($argv[$reappeared]);
            }

            return $method[$index];
        }

        foreach ($argv as $key => $value) {
            if (!str_starts_with($value, '-')) {
                unset($argv[$key]);
                $method[$index] = $value;
                return $value;
            }
        }

        return $default;
    }



    /**
     * Safe and simple way to get arguments from CLI
     *
     * This function will REMOVE and then return the argument when its found
     * If the argument is not found, $default will be returned
     *
     * @param $keys (NOTE: See $next for what will be returned) If set to a numeric value, the value from $argv[$key]
     *              will be selected. If set as a string value, the $argv key where the value is equal to $key will be
     *              selected. If set specified as an array, all entries in the specified array will be selected.
     * @param $next .When set to true, it REQUIRES that the specified key contains a next argument, and this will be
     *              returned. If set to "all", it will return all following arguments. If set to "optional",
     *              a next argument will be returned, if available.
     * @param string|null $default
     * @return mixed If $next is null, it will return a boolean value, true if the specified key exists, false if not.
     *               If $next is true or "optional", the next value will be returned as a string. However, if "optional"
     *               was used, and the next value was not specified, boolean FALSE will be returned instead. If $next
     *               is specified as all, all subsequent values will be returned in an array
     */
    public static function argument(int|string|null $keys = null, string|bool $next = false, ?string $default = null): mixed
    {
        global $argv;

        if (is_integer($keys)) {
            // Get arguments by index
            if ($next === 'all') {
                foreach ($argv as $argv_key => $argv_value) {
                    if ($argv_key < $keys) {
                        continue;
                    }

                    if ($argv_key == $keys) {
                        unset($argv[$keys]);
                        continue;
                    }

                    if (str_starts_with($argv_value, '-')) {
                        // Encountered a new option, stop!
                        break;
                    }

                    // Add this argument to the list
                    $value[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return isset_get($value);
            }

            if (isset($argv[$keys++])) {
                $argument = $argv[$keys - 1];
                unset($argv[$keys - 1]);
                return $argument;
            }

            // No arguments found (except perhaps for test or force)
            return $default;
        }

        if ($keys === null) {
            // Get the next argument
            $value = array_shift($argv);
            $value = Strings::startsNotWith((string) $value, '-');
            return $value;
        }

        //Detect multiple key options for the same command, but ensure only one is specified
        if (is_array($keys) || (is_string($keys) && str_contains($keys, ','))) {
            $keys = Arrays::force($keys);
            $results = array();

            foreach ($keys as $key) {
                if ($next === 'all') {
                    //We're requesting all values for all specified keys.
                    //It will return null in case the specified key does not exist
                    $value = static::argument($key, 'all', null);

                    if (is_array($value)) {
                        $found = true;
                        $results = array_merge($results, $value);
                    }
                } else {
                    $value = static::argument($key, $next, null);

                    if ($value) {
                        $results[$key] = $value;
                    }
                }
            }

            if (($next === 'all') && isset($found)) {
                return $results;
            }

            switch (count($results)) {
                case 0:
                    return $default;

                case 1:
                    return current($results);

                default:
                    //Multiple command line options were specified, this is not allowed!
                    throw new CliException('Multiple command line arguments ":results" for the same option specified. Please specify only one', [
                        ':results' => $results
                    ]);
            }
        }

        if (($key = array_search($keys, $argv)) === false) {
            // Specified argument not found
            return $default;
        }

        if ($next) {
            if ($next === 'all') {
                // Return all following arguments, if available, until the next option
                $value = array();

                foreach ($argv as $argv_key => $argv_value) {
                    if (empty($start)) {
                        if ($argv_value == $keys) {
                            $start = true;
                            unset($argv[$argv_key]);
                        }

                        continue;
                    }

                    if (str_starts_with($argv_value, '-')) {
                        // Encountered a new option, stop!
                        break;
                    }

                    //Add this argument to the list
                    $value[] = $argv_value;
                    unset($argv[$argv_key]);
                }

                return $value;
            }

            // Return next argument, if available
            $value = null;

            try {
                $value = Arrays::nextValue($argv, $keys, true);
            } catch (OutOfBoundsException $e) {
                if ($e->getCode() == 'invalid') {
                    if ($next !== 'optional') {
                        // This argument requires another parameter
                        throw $e->setCode('missing-arguments');
                    }

                    $value = false;
                }
            }

            if (str_starts_with($value, '-')) {
                throw new OutOfBoundsException(tr('Argument ":keys" has no assigned value. It is immediately followed by argument ":value"', [
                    ':keys' => $keys,
                    ':value' => $value
                ]), ['keys' => $keys]);
            }

            return $value;
        }

        unset($argv[$key]);
        return true;
    }



    /**
     * Returns true if the specified key exists
     *
     * @param int|string|null $keys
     * @param bool $default
     * @return bool
     */
    public static function boolArgument(int|string|null $keys = null, bool $default = false): bool
    {
        return (bool) self::argument($keys, false, $default);
    }



    /**
     * Returns the value for the specified key and ensures it is an integer number
     *
     * @param int|string|null $keys
     * @param bool $default
     * @return int
     */
    public static function integerArgument(int|string|null $keys = null, bool $default = false): int
    {
        $value = self::argument($keys, true, $default);

        if (!is_numeric($value) and ((integer) $value != $value)) {
            throw new OutOfBoundsException(tr('Value for key ":keys" should be an integer number', [
                ':keys' => $keys
            ]));
        }

        return $value;
    }



    /**
     * Returns the value for the specified key and ensures it is a natural number
     *
     * @param int|string|null $keys
     * @param bool $default
     * @return int
     */
    public static function naturalArgument(int|string|null $keys = null, bool $default = false): int
    {
        $value = self::argument($keys, true, $default);

        if (!is_natural($value)) {
            throw new OutOfBoundsException(tr('Value for key ":keys" should be a natural number', [
                ':keys' => $keys
            ]));
        }

        return $value;
    }



    /**
     * Returns the value for the specified key and ensures it is a float number
     *
     * @param int|string|null $keys
     * @param bool $default
     * @return float
     */
    public static function floatArgument(int|string|null $keys = null, bool $default = false): float
    {
        $value = self::argument($keys, true, $default);

        // TODO Test this following line, float casting may have slightly different results
        if (!is_numeric($value) and ((float) $value != $value)) {
            throw new OutOfBoundsException(tr('Value for key ":keys" should be a float number', [
                ':keys' => $keys
            ]));
        }

        return $value;
    }



    /**
     * Show a dot on the console each $each call if $each is false, "DONE" will be printed, with next line. Internal counter will reset if a different $each is received.
     *
     * @note While log_console() will log towards the PATH_ROOT/data/log/ log files, cli_dot() will only log one single dot even though on the command line multiple dots may be shown
     * @see log_console()
     * @example
     * code
     * for($i=0; $i < 100; $i++) {
     *     cli_dot();
     * }
     * /code
     *
     * This will return something like
     *
     * code
     * ..........
     * /code
     *
     * @param int $each
     * @param string $color
     * @param string $dot
     * @param boolean $quiet
     * @return boolean True if a dot was printed, false if not
     */
    public static function dot(int $each = 10, string $color = 'green', string $dot = '.', bool $quiet = false): bool
    {
        static $count = 0,
        $l_each = 0;

        if (!PLATFORM_CLI) {
            return false;
        }

        if ($quiet and QUIET) {
            /*
             * Don't show this in QUIET mode
             */
            return false;
        }

        if ($each === false) {
            if ($count) {
                /*
                 * Only show "Done" if we have shown any dot at all
                 */
                Log::write(tr('Done'), $color, 10, false, false);
            }

            $l_each = 0;
            $count = 0;
            return true;
        }

        $count++;

        if ($l_each != $each) {
            $l_each = $each;
            $count = 0;
        }

        if ($count >= $l_each) {
            $count = 0;
            Log::write($dot, $color, 10, false, false);
            return true;
        }

        return false;
    }



    /**
     * Kill this script process
     *
     * @todo Add required functionality
     * @return void
     */
    #[NoReturn] public static function die(): void
    {
        // Do we need to run other shutdown functions?
        die();
    }}