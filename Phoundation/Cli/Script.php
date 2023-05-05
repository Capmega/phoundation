<?php

declare(strict_types=1);

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\Exception\CliException;
use Phoundation\Cli\Exception\MethodNotExistsException;
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
 * @note Modifier arguments start with - or --. - only allows a letter whereas -- allows one or multiple words separated
 *       by a -. Modifier arguments may have or not have values accompanying them.
 * @note Methods are arguments NOT starting with - or --
 * @note As soon as non method arguments start we can no longer discern if a value like "system" is actually a method or
 *       a value linked to an argument. Because of this, as soon as modifier arguments start, methods may no longer be
 *       specified. An exception to this are system modifier arguments because system modifier arguments are filtered
 *       out BEFORE methods are processed.
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
     * The original set of methods
     *
     * @var array|null $methods
     */
    protected static ?array $methods = null;

    /**
     * The methods that were found in the ROOT/scripts path
     *
     * @var array $found_methods
     */
    protected static array $found_methods = [];

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

        // Define the readline completion function
        readline_completion_function(['\Phoundation\Cli\Script', 'completeReadline']);

        // Only allow this to be run by the cli script
        // TODO This should be done before Core::startup() but then the PLATFORM_CLI define would not exist yet. Fix this!
        static::onlyCommandLine();

        if (AutoComplete::isActive()) {
            // We're doing auto complete mode!
            try {
                // Get the script file to execute and execute auto complete for within this script, if available
                $script = static::findScript();

                // AutoComplete::getPosition() might become -1 if one were to <TAB> right at the end of the last method.
                // If this is the case we actually have to expand the method, NOT yet the script parameters!
                if ((AutoComplete::getPosition() - count(self::$found_methods)) === 0) {
                    throw MethodNotExistsException::new(tr('The specified command file ":file" does exist but requires auto complete extension', [
                        ':file' => $script
                    ]))
                        ->makeWarning()
                        ->setData([
                            'position' => AutoComplete::getPosition(),
                            'methods'  => [basename($script)]
                        ]);
                }

                // Check if this script has support for auto complete. If not
                if (!AutoComplete::hasSupport($script)) {
                    // This script has no auto complete support, so if we execute the script it won't go for auto
                    // complete but execute normally which is not what we want. we're done here.
                    self::die();
                }

            } catch (NoMethodSpecifiedException|MethodNotFoundException|MethodNotExistsException $e) {
                // Auto complete the method
                AutoComplete::processMethods(self::$methods, $e->getData());
            }

        } else {
            try {
                // Get the script file to execute
                $script = static::findScript();

            } catch (NoMethodSpecifiedException) {
                Documentation::usage('./pho METHODS [ARGUMENTS]
./pho system info
./pho system accounts users create --help
./pho system <TAB>');

                Documentation::help(tr('This is the Phoundation CLI interface "pho"

With this Command Line Interface script you can manage your Phoundation installation. Almost all web interface 
functionalities are also available on the command line and certain maintenance and development options are ONLY 
available on the CLI

The pho script command line has bash command line auto complete support so with the <TAB> button you can very easily see 
what methods are available to you. Auto complete support is also already enabled for some of the methods so (for 
example) user creation with "pho system accounts user create" can show all available options with <TAB>

The system arguments are ALWAYS available no matter what method is being executed. Some arguments always apply, others 
only apply for the commands that implement and or use them. If a system modifier argument was specified with a command 
that does not support it, it will simply be ignored. See the --help output for each method for more information. 
           
                
                
SYSTEM ARGUMENTS


-A,--all                                If set, the system will run in ALL mode, which typically will display normally 
                                        hidden information like deleted entries. Only used by specific commands, check 
                                        --help on commands to see if and how this flag is used. 

-C,--no-color                           If set, your log and console output will no longer have color

-D,--debug                              If set will run your system in debug mode. Debug commands will now generate and 
                                        display output

-E,--environment ENVIRONMENT            Sets or overrides the environment with which your pho command will be running. 
                                        If no environment was set in the shell environment using the  
                                        ":environment" variable, your pho command will refuse to   
                                        run unless you specify the environment manually using these flags. The   
                                        environment has to exist as a ROOT/config/ENVIRONMENT.yaml file

-F,--force                              If specified will run the CLI command in FORCE mode, which will override certain 
                                        restrictions. See --help for information on how specific commands deal with this 
                                        flag 

-H,--help                               If specified will display the help page for this command

-L,--log-level LEVEL                    If specified will set the minimum threshold level for log messages to appear. 
                                        Any message with a threshold level below the indicated amount will not appear in 
                                        the logs. Defaults to 5.

-O,--order-by "COLUMN ASC|DESC"         If specified and used by the script (only scripts that display tables) will  
                                        order the table contents on the specified column in the specified direction. 
                                        Defaults to nothing

-P,--page PAGE                          If specified and used by the script (only scripts that display tables) will show 
                                        the table on the specified page. Defaults to 1

-Q,--quiet                              Will have the system run in quiet mode, suppressing log startup and shutdown 
                                        messages  

-S,--status STATUS                      If specified will only display DataEntry entries with the specified status                                        

-T,--test                               Will run the system in test mode. Different scripts may change their behaviour 
                                        depending on this flag, see their --help output for more information. 
                                        
                                        NOTE: In this mode, temporary directories will NOT be removed upon shutdown so  
                                        that their contents can be used for debugging and testing.

-U,--usage                              Prints various command usage examples

-V,--verbose                            Will print more output during log startup and shutdown

-W,--no-warnings                        Will only use "error" type exceptions with backtrace and extra information, 
                                        instead of displaying only the main exception message for warnings

--system-language                       Sets the system language for all output

--deleted                               Will show deleted DataEntry records 

--version                               Will display the current version for your Phoundation installation

--limit NUMBER                          Will limit table output to the amount of specified fields

--timezone STRING                       Sets the specified timezone for the command you are executing

--show-passwords                        Will display passwords visibly on the command line. Both typed passwords and 
                                        data output will show passwords in the clear!

--no-validation                         Will not validate any of the data input. 

                                        WARNING: This may result in invalid data in your database!

--no-password-validation                Will not validate passwords.

                                        WARNING: This may result in weak and or compromised passwords in your database
                ', [':environment' => 'PHOUNDATION_' . PROJECT . '_ENVIRONMENT']));
                die();
            }
        }

        static::$script = static::limitScript($script, isset_get($limit), isset_get($reason));

        Log::action(tr('Executing script ":script"', [
            ':script' => static::getCurrent()
        ]), 1);

        // Execute the script
        execute_script(static::$script);
        AutoComplete::ensureAvailable();
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
     * Returns the list of methods that came to the script that executed
     *
     * @return array
     */
    public static function getMethods(): array
    {
        return self::$methods;
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
     *
     * @return int The user id for this process
     */
    public static function getProcessUid(): int
    {
        if (function_exists('posix_getuid')) {
            return posix_getuid();
        }

        return Command::new()->id('u');
    }


    /**
     * Returns the UID for the current process
     *
     * @return string|null The username for this process, or NULL if POSIX libraries are not available to PHP
     */
    public static function getProcessUser(): ?string
    {
        if (function_exists('posix_getpwuid')) {
            return posix_getpwuid(posix_getuid())['name'];
        }

        return null;
    }


    /**
     * Find the script to execute from the given arguments
     *
     * @return string
     */
    protected static function findScript(): string
    {
        if (!ArgvValidator::getMethodCount()) {
            throw NoMethodSpecifiedException::new('No method specified!')
                ->makeWarning()
                ->setData([
                    'position' => 0,
                    'methods'  => Arrays::filterValues(scandir(PATH_ROOT . 'scripts/'), '/^\./', true)
                ]);
        }

        $position      = 0;
        $file          = PATH_ROOT . 'scripts/';
        $methods       = ArgvValidator::getMethods();
        self::$methods = $methods;

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
                throw MethodNotExistsException::new(tr('The specified command file ":file" does not exist', [
                    ':file' => $file
                ]))
                    ->makeWarning()
                    ->setData([
                        'position' => $position,
                        'methods'  => Arrays::filterValues(scandir(dirname($file)), '/^\./', true)
                    ]);
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
            self::$found_methods[] = $method;
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
        throw MethodNotFoundException::new(tr('The specified command file ":file" was not found', [
            ':file' => $file
        ]))
            ->makeWarning()
            ->setData([
                'position' => $position + 1,
                'methods'  => Arrays::filterValues(scandir($file), '/^\./', true)
            ]);
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
    public static function onlyCommandLine(bool $exclusive = false): void
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
     * @return never
     * @todo Add required functionality
     */
    #[NoReturn] public static function die(Throwable|int $exit_code = 0, string $exit_message = null): never
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


    /**
     * Returns all options for readline <TAB> autocomplete
     *
     * @param string $input
     * @param int $index
     * @return array
     */
    protected static function completeReadline(string $input, int $index): array
    {
 showdie($input);
//        // Get info about the current buffer
//        // Figure out what the entire input is
//        $matches    = [];
//        $rl_info    = readline_info();
//        $full_input = substr($rl_info['line_buffer'], 0, $rl_info['end']);
//
//        // Get all matches based on the entire input buffer
//        foreach (phrases_that_begin_with($full_input) as $phrase) {
//            // Only add the end of the input (where this word begins)
//            // to the matches array
//            $matches[] = substr($phrase, $index);
//        }
//
//        return $matches;
    }
}