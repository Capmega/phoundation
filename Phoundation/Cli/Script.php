<?php

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Exception\CliException;
use Phoundation\Cli\Exception\MethodNotFoundException;
use Phoundation\Cli\Exception\NoMethodSpecifiedException;
use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\NoProjectException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Numbers;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Date\Time;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\ScriptException;
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
     * The script that is being executed
     *
     * @var string|null $script
     */
    protected static ?string $script = null;



    /**
     * Execute a command by the "cli" script
     *
     * @return void
     * @throws Throwable
     */
    public static function execute(): void
    {
        // All scripts will execute the cli_done() call, register basic script information
        try {
            Core::startup();
        } catch (SqlException $e) {
            $reason = tr('Core database not found, please execute "./cli system project setup"');
            $limit  = 'system/project/init';
        } catch (NoProjectException $e) {
            $reason = tr('Project file not found, please execute "./cli system project setup"');
            $limit  = 'system/project/setup';
        }

        // Only allow this to be run by the cli script
        // TODO This should be done before Core::startup() but then the PLATFORM_CLI define would not exist yet. Fix this!
        static::only();

        // Get the script file to execute
        $script = static::findScript();
        $script = static::limitScript($script, isset_get($limit), isset_get($reason));

        static::$script = $script;

        Log::action(tr('Executing script ":script"', [
            ':script' => static::getCurrent()
        ]), 1);

        // Execute the script
        execute_script($script);
        self::die();
    }



    /**
     * Returns the process exit code
     *
     * @return int
     */
    public static function getExitCode(): int
    {
        return static::$exit_code;
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

        if (!$only_if_null or !static::$exit_code) {
            static::$exit_code = $code;
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

        return Command::new()->id('u');
    }



    /**
     * Find the script to execute from the given arguments
     *
     * @return string
     */
    protected static function findScript(): string
    {
        if (ArgvValidator::count() <= 1) {
            throw NoMethodSpecifiedException::new('No method specified!')->setData(Arrays::filterValues(scandir(PATH_ROOT . 'scripts/'), '.,..'))
                ->makeWarning();
        }

        $file    = PATH_ROOT . 'scripts/';
        $methods = ArgvValidator::getMethods();

        foreach ($methods as $position => $method) {
            if (str_ends_with($method, '/cli')) {
                // This is the cli command, ignore it
                ArgvValidator::removeMethod($method);
                continue;
            }

            if (!preg_match('/[a-z0-9-]/i', $method)) {
                // Methods can only have alphanumeric characters
                throw OutOfBoundsException::new(tr('The specified method ":method" contains invalid characters. only a-z, 0-9 and - are allowed', [
                    ':method' => $method
                ]))->makeWarning();
            }

            if (str_starts_with($method, '-')) {
                // Methods can only have alphanumeric characters
                throw OutOfBoundsException::new(tr('The specified method ":method" starts with a - character which is not allowed', [
                    ':method' => $method
                ]))->makeWarning();
            }

            // Start processing arguments as methods here
            $file .= $method;
            ArgvValidator::removeMethod($method);

            if (!file_exists($file)) {
                // The specified path doesn't exist
                throw MethodNotFoundException::new(tr('The specified method file ":file" was not found', [
                    ':file' => $file
                ]))->makeWarning();
            }

            if (!is_dir($file)) {
                // This is a file, should be PHP, found it! Update the arguments to remove all methods from them.
                return $file;
            }

            // This is a directory.
            $file .= '/';

            // Does a file with the directory name exists inside? Only check if the NEXT method does not exist as a file
            $next = isset_get($methods[$position + 1]);

            if (!$next or !file_exists($file . $next)) {
                if (file_exists($file . $method)) {
                    if (!is_dir($file . $method)) {
                        // This is the file!
                        return $file . $method;
                    }
                }
            }

            // Continue scanning
        }

        // Here we're still in a directory. If a file exists in that directory with the same name as the directory
        // itself then that is the one that will be executed. For example, PATH_ROOT/cli system init will execute
        // PATH_ROOT/scripts/system/init/init
        if (file_exists($file . $method)) {
            if (!is_dir($file . $method)) {
                // Yup, this is it guys!
                return $file . $method;
            }
        }

        // We're stuck in a directory still, no script to execute.
        // Add the available files to display to help the user
        throw MethodNotFoundException::new(tr('The specified method file ":file" was not found', [
            ':file' => $file
        ]))
            ->setData(Arrays::filterValues(scandir($file), '.,..'))
            ->makeWarning();
    }



    /**
     * Limit execution of scripts to the specified limit
     *
     * @param string $script
     * @param string|null $limit
     * @param string|null $reason
     * @return string
     */
    protected static function limitScript(string $script, ?string $limit, ?string $reason): string
    {
        if ($limit) {
            $script = Strings::from($script, 'scripts/');

            if ($script !== $limit) {
                throw new ScriptException(tr('Cannot execute script ":script" because ":reason"', [
                    ':script' => $script,
                    ':reason' => $reason
                ]));
            }
        }

        return $script;
    }



    /**
     * Returns the name of the script that is running
     *
     * @param bool $full
     * @return string
     */
    public static function getCurrent(bool $full = false): string
    {
        if ($full) {
            return Strings::fromReverse(static::$script, PATH_ROOT . 'scripts/');
        }

        return Strings::fromReverse(static::$script, '/');
    }



    /**
     * Returns the name of the script that is running
     *
     * @param string $script
     * @param bool $full
     * @return bool
     */
    public static function isScript(string $script, bool $full = false): bool
    {
        return $script === static::getCurrent($full);
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
            static::runOnceLocal();
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
            // Don't show this in QUIET mode
            return false;
        }

        if (!$each) {
            if ($count) {
                // Only show "Done" if we have shown any dot at all
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
     * @param Throwable|int $exit_code
     * @param string|null $exit_message
     * @return void
     * @todo Add required functionality
     */
    #[NoReturn] public static function die(Throwable|int $exit_code = 0, string $exit_message = null): void
    {
        if (is_object($exit_code)) {
            // Specified exit code is an exception, we're in trouble...
            $e         = $exit_code;
            $exit_code = $exit_code->getCode();
        }

        if ($exit_code) {
            Script::setExitCode($exit_code, true);
        }

        if (isset($e)) {
            if (($e instanceof Exception) and $e->isWarning()) {
                $exit_code = $exit_code ?? 1;

                Log::warning($e->getMessage());
                Log::warning(tr('Script ":script" ended with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                    ':script'   => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                    ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                    ':exitcode' => $exit_code
                ]), 10);
            } else {
                $exit_code = $exit_code ?? 255;

                Log::error($e->getMessage());
                Log::error(tr('Script ":script" ended with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                    ':script'   => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                    ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                    ':exitcode' => $exit_code
                ]), 10);
            }

        } elseif ($exit_code) {
            if ($exit_code > 200) {
                if ($exit_message) {
                    Log::warning($exit_message);
                }

                // Script ended with warning
                Log::warning(tr('Script ":script" ended with exit code ":exitcode" warning in ":time" with ":usage" peak memory usage', [
                    ':script'   => Strings::from(Core::readRegister('system', 'script'), PATH_ROOT),
                    ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
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
                    ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
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
                ':usage'  => Numbers::getHumanReadableBytes(memory_get_peak_usage())
            ]), 8);
        }

        die($exit_code);
    }
}