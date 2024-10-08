<?php

/**
 * Class Scripts
 *
 * This is the default Scripts object
 *
 * @note      Modifier arguments start with - or --. - only allows a letter whereas -- allows one or multiple words
 *            separated by a -. Modifier arguments may have or not have values accompanying them.
 * @note      Commands are arguments NOT starting with - or --
 * @note      As soon as non-command arguments start, we can no longer discern if a value like "system" is actually a
 *            command or a value linked to an argument. Because of this, as soon as modifier arguments start, commands
 *            may no longer be specified. An exception to this is system modifier arguments because system modifier
 *            arguments are filtered out BEFORE commands are processed.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */

declare(strict_types=1);

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Audio\Audio;
use Phoundation\Cache\InstanceCache;
use Phoundation\Cli\Exception\CliCommandException;
use Phoundation\Cli\Exception\CliCommandNotExistsException;
use Phoundation\Cli\Exception\CliCommandNotFoundException;
use Phoundation\Cli\Exception\CliException;
use Phoundation\Cli\Exception\CliNoCommandSpecifiedException;
use Phoundation\Cli\Exception\CliStdInException;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\NoProjectException;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataStaticExecuted;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Databases\Sql\Exception\SqlDatabaseDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlNoTimezonesException;
use Phoundation\Date\Time;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\ScriptException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Databases\MySql;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Requests\Request;
use Throwable;

class CliCommand
{
    use TraitDataStaticExecuted;

    /**
     * Management object for the runfile for this command
     *
     * @var CliRunFile $run_file
     */
    protected static CliRunFile $run_file;

    /**
     * The exit code for this process
     *
     * @var int $exit_code
     */
    protected static int $exit_code = 0;

    /**
     * The command that is being executed
     *
     * @var string|null $command
     */
    protected static ?string $command = null;

    /**
     * The original set of commands
     *
     * @var array|null $commands
     */
    protected static ?array $commands = null;

    /**
     * The commands that were found in the command cache path
     *
     * @var array $found_commands
     */
    protected static array $found_commands = [];

    /**
     * Contains the data sent to this command over stdin
     *
     * @var string $stdin_data
     */
    protected static string $stdin_data;

    /**
     * Tracks all input / output streams
     *
     * @var array $streams
     */
    protected static array $streams;

    /**
     * True if STDIN stream has been read
     *
     * @var bool $stdin_has_been_read
     */
    protected static bool $stdin_has_been_read = false;

    /**
     * Tracks if the UID of the process and the pho file match
     *
     * @var bool|null $pho_uid_match
     */
    protected static ?bool $pho_uid_match = null;

    /**
     * Tracks if the ./pho file UID
     *
     * @var int $pho_uid
     */
    protected static int $pho_uid;

    /**
     * If true, and no command was specified, the internal default command will be executed
     *
     * @var bool $require_default
     */
    protected static bool $require_default = true;


    /**
     * Returns if the default command will be executed if no command was specified
     *
     * @return bool
     */
    public static function getRequireDefault(): bool
    {
        return static::$require_default;
    }


    /**
     * Sets if the default command will be executed if no command was specified
     *
     * @param bool $require_default
     *
     * @return void
     */
    public static function setRequireDefault(bool $require_default): void
    {
        static::$require_default = $require_default;
    }


    /**
     * Instructs the Libraries class to clear the commands cache
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Libraries::clearCommandsCache();
    }


    /**
     * Returns true if the libraries command cache has been cleared
     *
     * @return bool
     */
    public static function cacheHasBeenCleared(): bool
    {
        return Libraries::cacheHasBeenCleared();
    }


    /**
     * Returns the command line executed on the CLI
     *
     * @return string
     */
    public static function getRequest(): string
    {
        return 'IMPLEMENT CLICOMMAND::GETREQUEST()';
        //        return $_SERVER[''];
    }


    /**
     * Execute a command by the "pho" command
     *
     * @return void
     * @throws Throwable
     */
    #[NoReturn] public static function execute(): void
    {
        // Get parameters, get the command to execute
        $parameters = static::startup();
        $command    = static::findCommandOrExecuteDocumentation();

        // See if the command execution should be stopped for some reason. If not, setup a run file
        static::$command  = static::limitCommand($command, isset_get($parameters['limit']), isset_get($parameters['reason']));
        static::$run_file = new CliRunFile($command);
        // TODO Move this to the Request object
        static::addExecutedPath(static::$command);

        // Should we execute usage or help documentation instead?
        static::checkUsage();
        static::checkHelp();

        // Execute the command and finish execution
        try {
            Request::setRestrictions(Restrictions::readonly(DIRECTORY_COMMANDS, 'CLI command execution'));
            Request::execute(static::$command . '.php');

        } catch (SqlNoTimezonesException $e) {
            static::fixMysqlTimezoneException($e);
        }

        // Make sure that the CLI auto-completion is configured for this shell.
        CliAutoComplete::ensureAvailable();

        if (!stream_isatty(STDIN) and !static::$stdin_has_been_read) {
            // STDIN might happen with commands executed. Test the input stream if there was any data at all in it
            if (static::getStdInStream()) {
                Log::warning(tr('Warning: STDIN stream was specified but not used'));
            }
        }

        // We're done, start the shut down procedures
        exit();
    }


    /**
     * Startup the CLI command processor object
     *
     * @return array
     */
    protected static function startup(): array
    {
        static::detectProcessUidMatchesPhoundationOwner();

        $return = [
            'limit'  => null,
            'reason' => null,
        ];

        // Startup the system core
        try {
            Core::startup();

        } catch (SqlDatabaseDoesNotExistException) {
            $return['limit']  = 'system/project/init';
            $return['reason'] = tr('Core database not found, please execute "./cli system project setup"');

        } catch (NoProjectException) {
            $return['limit']  = 'system/project/setup';
            $return['reason'] = tr('Project file not found, please execute "./cli system project setup"');
        }

        static::ensureProcessUidMatchesPhoundationOwner();

        if (Core::getMaintenanceMode()) {
            // We're running in maintenance mode, limit command execution to system/
            $return['limit']  = ['system/', 'info'];
            $return['reason'] = tr('system has been placed in maintenance mode by user ":user" and only ./pho system ... commands are available right now. If maintenance mode is stuck then please run "./pho system maintenance disable" to disable maintenance mode. Please note that all web requests are being blocked as well during maintenance mode!', [
                ':user' => Core::getMaintenanceMode(),
            ]);
        }

        // Define the readline completion function
        readline_completion_function([
            '\Phoundation\Cli\CliCommand',
            'completeReadline',
        ]);

        // Only allow this to be run by the command line interface
        // TODO This should be done before Core::startup() but then the PLATFORM_CLI define would not exist yet. Fix this!
        static::onlyCommandLine();

        return $return;
    }


    /**
     * Detects if the process owner and file owner are the same. If not, will disable file logging and set
     * CliCommand::getUidMatch() to false
     *
     * @return void
     */
    protected static function detectProcessUidMatchesPhoundationOwner(): void
    {
        $owner = @fileowner(__DIR__ . '/../../pho');

        if ($owner === false) {
            // Wut? What happened? Does the pho command exist? If it does, how did we got here? ./pho renamed, perhaps?
            echo 'Failed to get file owner information of "PROJECT_ROOT/pho" command file' . PHP_EOL;
            exit();
        }

        static::$pho_uid = $owner;

        Core::getInstance();

        if (Core::getProcessUid() === static::$pho_uid) {
            // Correct user, yay!
            static::$pho_uid_match = true;
            return;
        }

        // UID does NOT match, disable logging for now to avoid issues
        static::$pho_uid_match = false;

        // UID mismatch, stop logging to file as that likely won't be possible at all. Also stop all file access
        Log::disableFile();
        File::disable();
    }


    /**
     * Returns true if the PHO command UID matches the UID of the current process
     *
     * @note Returns NULL if the UID match check has not yet been executed
     *
     * @return bool|null
     */
    public static function phoUidMatch(): ?bool
    {
        return static::$pho_uid_match;
    }


    /**
     * Ensures that the process owner and file owner are the same.
     *
     * @param bool $auto_switch
     * @param bool $permit_root
     *
     * @return void
     */
    protected static function ensureProcessUidMatchesPhoundationOwner(bool $auto_switch = true, bool $permit_root = false): void
    {
        if (static::$pho_uid_match) {
            // Correct user, yay!
            return;
        }

        if (!Core::getProcessUid() and $permit_root) {
            // This command is run as root and the user root is authorized!
            return;
        }

        if (!Config::getBoolean('cli.security.require-same-uid', true)) {
            // According to configuration, we don't need to have the same UID.
            return;
        }

        if (!$auto_switch) {
            throw new CliException(tr('The user ":puser" is not allowed to execute these commands, only user ":fuser" can do this. use "sudo -u :fuser COMMANDS instead.', [
                ':puser' => CliCommand::getProcessUser(),
                ':fuser' => get_current_user(),
            ]));
        }

        // From here we will restart the process using SUDO with the correct user
        // Start building the argument list
        $command   = escapeshellcmd(DIRECTORY_ROOT . 'pho');
        $arguments = ArgvValidator::new()->getSource();

        if (CliAutoComplete::isActive()) {
            // Auto complete requires re-adding the --auto-complete and position and a different argument handling
            $arguments = [
                '--auto-complete',
                '"' . CliAutoComplete::getPosition() . ' ' . implode(' ', $arguments) . '"'
            ];

        } else {
            // Ensure all arguments are properly escaped
            foreach ($arguments as &$argument) {
                $argument = escapeshellarg($argument);
            }
        }

        unset($argument);

        // As what user should we execute this? Build the sudo command to be executed
        $user    = posix_getpwuid(static::$pho_uid);
        $command = 'sudo -Eu ' . escapeshellarg($user['name']) . ' ' . $command . ' ' . implode(' ', $arguments);

        if (!QUIET) {
            if (VERBOSE) {
                echo 'Re-executing ./pho command as user "' . $user['name'] . '" with command "' . $command . '"' . PHP_EOL;

            } else {
                echo 'Re-executing ./pho command as user "' . $user['name'] . '"' . PHP_EOL;
            }
        }

        // Re-execute this PHO command with sudo as the correct user
        passthru($command, $result_code);

        // We likely won't be able to log here (nor should we), so disable logging
        Core::exit($result_code, direct_exit: true);
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
     * Kill this command process
     *
     * @param Throwable|int $exit_code
     * @param string|null   $exit_message
     * @param bool          $sig_kill
     *
     * @return never
     * @todo Add required functionality
     */
    #[NoReturn] public static function exit(Throwable|int $exit_code = 0, ?string $exit_message = null, bool $sig_kill = false): never
    {
        // If something went really, really wrong...
        if ($sig_kill) {
            exit($exit_message);
        }
        if (is_object($exit_code)) {
            // Specified exit code is an exception, we're in trouble...
            $e         = $exit_code;
            $exit_code = $exit_code->getCode();
        }
        if ($exit_code) {
            static::setExitCode($exit_code, true);
        }
        // Terminate the run file
        if (isset(static::$run_file)) {
            static::$run_file->delete();
        }
        // Did we encounter an exception?
        if (isset($e)) {
            if (($e instanceof Exception) and $e->isWarning()) {
                $exit_code = $exit_code ?? 1;
                Log::warning($e->getMessage());
                Log::warning(tr('Command ":command" ended with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                    ':command'  => static::getCommandsString(),
                    ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                    ':exitcode' => $exit_code,
                ]), 10);
            } else {
                $exit_code = $exit_code ?? 255;
                Log::error($e->getMessage());
                Log::error(tr('Command ":command" ended with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                    ':command'  => static::getCommandsString(),
                    ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                    ':exitcode' => $exit_code,
                ]), 10);
            }

        } elseif ($exit_code) {
            if ($exit_code >= 200) {
                if ($exit_message) {
                    Log::warning($exit_message, 8);

                } else {
                    // Script ended with warning
                    Log::warning(tr('Command ":command" ended with exit code ":exitcode" warning in ":time" with ":usage" peak memory usage', [
                        ':command'  => static::getCommandsString(),
                        ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                        ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                        ':exitcode' => $exit_code,
                    ]), 8);
                }

            } else {
                if ($exit_message) {
                    Log::error($exit_message, 8);
                } else {
                    // Script ended with error
                    Log::error(tr('Command ":command" failed with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                        ':command'  => static::getCommandsString(),
                        ':time'     => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                        ':usage'    => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
                        ':exitcode' => $exit_code,
                    ]), 8);
                }
            }

        } else {
            // Give a "success!" sound for normally executed commands (so NOT auto complete actions!)
            if (!CliAutoComplete::isActive()) {
                Audio::new('success.mp3')
                     ->playLocal(true);
            }
            if ($exit_message) {
                Log::success($exit_message, 8);
            }
            // Script ended successfully
            Log::success(tr('Finished command ":command" with PID ":pid" in ":time" with ":usage" peak memory usage', [
                ':command' => static::getCommandsString(),
                ':pid'     => getmypid(),
                ':time'    => Time::difference(STARTTIME, microtime(true), 'auto', 5),
                ':usage'   => Numbers::getHumanReadableBytes(memory_get_peak_usage()),
            ]), 8);
        }

        if (!CliAutoComplete::isActive()) {
            InstanceCache::logStatistics();
            echo CliColor::getColorReset();
            system('stty echo');
        }

        exit($exit_code);
    }


    /**
     * Returns the list of commands that came to the command that executed in space separated string format
     *
     * @return string
     */
    public static function getCommandsString(): string
    {
        if (static::$commands) {
            return implode(' ', static::$commands);
        }

        return 'N/A';
    }


    /**
     * Only allow execution on shell commands
     *
     * @param bool $exclusive
     *
     * @return void
     */
    public static function onlyCommandLine(bool $exclusive = false): void
    {
        if (!PLATFORM_CLI) {
            throw new ScriptException(tr('This can only be done from command line'));
        }
        if ($exclusive) {
            static::runOnceLocal();
        }
    }


    /**
     * Ensure that the current command file cannot be run twice
     *
     * This function will ensure that the current command file cannot be run twice. In order to do this, it will create
     * a run file in data/run/SCRIPTNAME with the current process id. If, upon starting, the command file already
     * exists, it will check if the specified process id is available, and if its process name matches the current
     * command name. If so, then the system can be sure that this command is already running, and the function will
     * throw an exception
     *
     * @param bool $close If set true, the function will stop ensuring that the command won't be run again
     *
     * @return void
     * @example  Have a command run itself recursively, which will be stopped by cli_run_once_local()
     * code
     * log_console('Started test');
     * cli_run_once_local();
     * safe_exec(Core::getExecutedPath());
     * cli_run_once_local(true);
     * /code
     *
     * This would return
     * Started test
     * cli_run_once_local(): The command ":command" for this project is already running
     * /code
     *
     * @category Function reference
     * @version  1.27.1: Added documentation
     */
    public static function runOnceLocal(bool $close = false)
    {
        static $executed = false;
        throw new UnderConstructionException();
        try {
            $run_dir = DIRECTORY_ROOT . 'data/run/';
            $command = $core->register['command'];
            Directory::ensure(dirname($run_dir . $command));
            if ($close) {
                if (!$executed) {
                    // Hey, this command is being closed but was never opened?
                    Log::warning(tr('The cli_run_once_local() function has been called with close option, but it was already closed or never opened.'));
                }
                file_delete([
                    'patterns'     => $run_dir . $command,
                    'restrictions' => DIRECTORY_ROOT . 'data/run/',
                    'clean_path'   => false,
                ]);
                $executed = false;

                return;
            }
            if ($executed) {
                // Hey, command has already been run before, and its run again without the close option, this should
                // never happen!
                throw new CliException(tr('The function has been called twice by command ":command" without $close set to true! This function should be called twice, once without argument, and once with boolean "true"', [
                    ':command' => $command,
                ]));
            }
            $executed = true;
            if (file_exists($run_dir . $command)) {
                // Run file exists, so either a process is running, or a process was running but crashed before it could
                // delete the run file. Check if the registered PID exists, and if the process name matches this one
                $pid = file_get_contents($run_dir . $command);
                $pid = trim($pid);
                if (!is_numeric($pid) or !is_natural($pid) or ($pid > 65536)) {
                    Log::warning(tr('The run file ":file" contains invalid information, ignoring', [':file' => $run_dir . $command]));

                } else {
                    $name = safe_exec([
                        'commands' => [
                            'ps',
                            [
                                '-p',
                                $pid,
                                'connector' => '|',
                            ],
                            'tail',
                            [
                                '-n',
                                1,
                            ],
                        ],
                    ]);
                    $name = array_pop($name);
                    if ($name) {
                        preg_match_all('/.+?\d{2}:\d{2}:\d{2}\s+(' . str_replace('/', '\/', $command) . ')/', $name, $matches);
                        if (!empty($matches[1][0])) {
                            throw new CliException(tr('The command ":command" for this project is already running', [
                                ':command' => $command,
                            ]));
                        }
                    }
                }
                // File exists, or contains invalid data, but PID either doesn't exist, or is used by a different
                // process. Remove the PID file
                Log::warning(tr('cli_run_once_local(): Cleaning up stale run file ":file"', [':file' => $run_dir . $command]));
                file_delete([
                    'patterns'     => $run_dir . $command,
                    'restrictions' => DIRECTORY_ROOT . 'data/run/',
                    'clean_path'   => false,
                ]);
            }
            // No run file exists yet, create one now
            file_put_contents($run_dir . $command, getmypid());
            Core::readRegister('shutdown_cli_run_once_local', [true]);

        } catch (Exception $e) {
            if ($e->getCode() == 'already-running') {
                // Keep throwing this one
                throw($e);
            }
            throw new CliException('cli_run_once_local(): Failed', $e);
        }
    }


    /**
     * Either finds the command to execute, or executes documentation
     *
     * @return string
     */
    protected static function findCommandOrExecuteDocumentation(): string
    {
        if (CliAutoComplete::isActive()) {
            $command = static::autoComplete();

        } else {
            try {
                // Get the command file to execute
                $command = static::findCommand();

            } catch (CliNoCommandSpecifiedException) {
                global $argv;
                $argv['help'] = true;
                static::documentation();
                CliAutoComplete::ensureAvailable();
                exit();
            }
        }

        return $command;
    }


    /**
     * Process auto complete requests
     *
     * @return string|null
     */
    #[NoReturn] protected static function autoComplete(): ?string
    {
        try {
            // Get the command file to execute and execute auto complete for within this command, if available
            $command = static::findCommand();

            // AutoComplete::getPosition() might become -1 if one were to <TAB> right at the end of the last command.
            // If this is the case we actually have to expand the command, NOT yet the command parameters!
            if ((CliAutoComplete::getPosition() - count(static::$found_commands)) === 0) {
                throw CliCommandNotExistsException::new(tr('The specified command file ":file" does exist but requires auto complete extension', [
                    ':file' => $command,
                ]))
                ->makeWarning()
                ->addData([
                    'position' => CliAutoComplete::getPosition(),
                    'commands' => [basename($command)],
                ]);
            }

            // Check if this command has support for auto complete. If not
            if (!CliAutoComplete::hasSupport($command)) {
                // This command has no auto complete support, so if we execute the command it won't go for auto
                // complete but execute normally, which is not what we want. We're done here.
                exit();
            }

            return $command;

        } catch (ValidationFailedException $e) {
            // Whoops, somebody typed something weird or naughty. Either way, ignore it
            Log::warning($e);
            exit(1);

        } catch (CliNoCommandSpecifiedException | CliCommandNotFoundException | CliCommandNotExistsException $e) {
            // Auto complete the command
            CliAutoComplete::processCommands(static::$commands, $e->getData());
        }
    }


    /**
     * Find the command to execute from the given arguments
     *
     * @return string
     */
    protected static function findCommand(): string
    {
        $command          = null;
        $position         = 0;
        $file             = DIRECTORY_COMMANDS;
        $commands         = ArgvValidator::getCommands();
        static::$commands = $commands;

        // Ensure commands cache directory exists
        if (!file_exists($file)) {
            Log::warning(tr('Commands cache directory ":path" does not yet exists, rebuilding commands cache', [
                ':path' => $file,
            ]), 3);
            // Rebuild the command cache
            Libraries::rebuildCommandCache();
        }

        // Is any command specified at all?
        if (!ArgvValidator::getCommandCount()) {
            // Strip slashes and remove hidden commands
            $commands = scandir(DIRECTORY_COMMANDS);
            $commands = Arrays::replaceValuesWithCallbackReturn($commands, function ($key, $value) { return strip_extension($value); });
            $commands = Arrays::removeMatchingValues($commands, '/^\./', flags: Utils::MATCH_REGEX);

            throw CliNoCommandSpecifiedException::new('No command specified!')
                                                ->makeWarning()
                                                ->addData([
                                                    'position' => 0,
                                                    'commands' => $commands,
                                                ]);
        }
        // Process commands
        foreach ($commands as $position => $command) {
            if (!static::validateCommand($command)) {
                continue;
            }
            // Start processing arguments as commands here
            $file .= $command;
            ArgvValidator::removeCommand($command);

            if (!file_exists($file) and !file_exists($file . '.php')) {
                // The specified directory doesn't exist. Does a part exist, perhaps? Get the parent and find out
                try {
                    $files = Arrays::removeMatchingValues(scandir(dirname($file)), '/^\./', flags: Utils::MATCH_REGEX);
                    $files = Arrays::replaceValuesWithCallbackReturn($files, function ($key, $value) { return strip_extension($value); });
                    $files = Arrays::keepMatchingValuesStartingWith($files, basename($file));

                } catch (Throwable) {
                    $files = [];
                }

                $previous = scandir(dirname($file));
                $previous = Arrays::replaceValuesWithCallbackReturn($previous, function ($key, $value) { return strip_extension($value); });
                $previous = Arrays::removeMatchingValues($previous, '/^\./', flags: Utils::MATCH_REGEX);
                throw CliCommandNotExistsException::new(tr('The specified command file ":file" does not exist', [
                    ':file' => $file,
                ]))
                                                  ->makeWarning()
                                                  ->addData([
                                                      'position'          => $position,
                                                      'commands'          => $files,
                                                      'previous_commands' => $previous,
                                                  ]);
            }
            if (file_exists($file . '.php')) {
                // This is a file, should be PHP, found it! Update the arguments to remove all commands from them.
                return $file;
            }
            // This is a directory.
            $file .= '/';
            // Does a file with the directory name exist inside? Only check if the NEXT command does not exist as a file
            $next = isset_get($commands[$position + 1]);
            if (!$next or (!file_exists($file . $next) and !file_exists($file . $next . '.php'))) {
                if (file_exists($file . $command)) {
                    if (!is_dir($file . $command)) {
                        // This is the file! Adjust the CliAutoComplete position if it's active because we'll be one
                        // position ahead of what is expected
                        if (CliAutoComplete::isActive()) {
                            CliAutoComplete::setPosition(CliAutoComplete::getPosition() + 1);
                        }

                        return $file . $command;
                    }
                }
            }
            // Continue scanning
            static::$found_commands[] = $command;
        }
        // Here we're still in a directory. If a file exists in that directory with the same name as the directory
        //  itself, then that is the one that will be executed. For example, ./pho system init will execute
        // DIRECTORY_COMMANDS/system/init/init
        if (file_exists($file . $command)) {
            if (!is_dir($file . $command)) {
                // Yup, this is it guys!
                return $file . $command;
            }
        }
        $previous = scandir(dirname($file));
        $previous = Arrays::replaceValuesWithCallbackReturn($previous, function ($key, $value) { return strip_extension($value); });
        $previous = Arrays::removeMatchingValues($previous, '/^\./', flags: Utils::MATCH_REGEX);
        $commands = scandir($file);
        $commands = Arrays::replaceValuesWithCallbackReturn($commands, function ($key, $value) { return strip_extension($value); });
        $commands = Arrays::removeMatchingValues($commands, '/^\./', flags: Utils::MATCH_REGEX);
        // We're stuck in a directory still, no command to execute.
        // Add the available files to display to help the user
        throw CliCommandNotFoundException::new(tr('The specified command file ":file" was not found', [
            ':file' => $file,
        ]))
                                         ->makeWarning()
                                         ->addData([
                                             'position'          => $position + 1,
                                             'commands'          => $commands,
                                             'previous_commands' => $previous,
                                         ]);
    }


    /**
     * Returns the list of commands that came to the command that executed
     *
     * @return array
     */
    public static function getCommands(): array
    {
        return static::$commands;
    }


    /**
     * Validates the specified command and returns true if the command is valid
     *
     * @note Throws exceptions in case of issues
     *
     * @param string $command
     *
     * @return bool
     * @throws ValidationFailedException, OutOfBoundsException
     */
    protected static function validateCommand(string $command): bool
    {
        // Validate the command
        if (strlen($command) > 32) {
            throw new ValidationFailedException(tr('Specified command ":command" is too long, it should be less than 32 characters', [
                ':command' => $command,
            ]));
        }
        if (str_ends_with($command, '/pho')) {
            // This is the cli command, ignore it
            ArgvValidator::removeCommand($command);

            return false;
        }
        if (!preg_match('/[a-z0-9-]/i', $command)) {
            // Commands can only have alphanumeric characters
            throw OutOfBoundsException::new(tr('The specified command ":command" contains invalid characters. only a-z, 0-9 and - are allowed', [
                ':command' => $command,
            ]))
                                      ->makeWarning();
        }
        if (str_starts_with($command, '-')) {
            // Commands can only have alphanumeric characters
            throw OutOfBoundsException::new(tr('The specified command ":command" starts with a - character which is not allowed', [
                ':command' => $command,
            ]))
                                      ->makeWarning();
        }

        return true;
    }


    /**
     * Returns true if the libraries command cache has been rebuilt
     *
     * @return bool
     */
    public static function cacheHasBeenRebuilt(): bool
    {
        return Libraries::cacheHasBeenRebuilt();
    }


    /**
     * Instructs the Libraries class to have each library rebuild its command cache
     *
     * @return void
     */
    public static function rebuildCache(): void
    {
        Libraries::rebuildCommandCache();
    }


    /**
     * Display documentation
     *
     * @return void
     */
    protected static function documentation(): void
    {
        if (static::$require_default) {
            CliDocumentation::setUsage('./pho METHODS [ARGUMENTS]
./pho intro
./pho info
./pho find COMMAND
./pho rebuild
./pho accounts users create --help
./pho system update -H
./pho dev patch -H
./pho system maintenance disable
./pho <TAB>
./pho sy<TAB>
./pho system <TAB>', false);
            CliDocumentation::setHelp(tr('This is the Phoundation CLI interface command "pho". For more basic information please execute ./pho intro which will print an introduction text to Phoundation
'), false);
        }
    }


    /**
     * Limit execution of commands to the specified limit
     *
     * @param string            $command
     * @param array|string|null $limits
     * @param string|null       $reason
     *
     * @return string
     */
    protected static function limitCommand(string $command, array|string|null $limits, ?string $reason): string
    {
        if ($limits) {
            $test = Strings::from($command, 'commands/');
            foreach (Arrays::force($limits) as $limit) {
                if (str_starts_with($test, $limit)) {
                    return $command;
                }
            }
            throw ScriptException::new(tr('Cannot execute command ":command" because :reason', [
                ':command' => $test,
                ':reason'  => $reason,
            ]))
                                 ->makeWarning();
        }

        return $command;
    }


    /**
     * Returns true if the specified command has usage support available
     *
     * @return void
     */
    protected static function checkUsage(): void
    {
        global $argv;
        if ($argv['usage']) {
            if (
                empty(File::new(static::$command . '.php', DIRECTORY_COMMANDS)
                          ->grep(['CliDocumentation::setUsage('], 100))
            ) {
                throw CliCommandException::new(tr('The command ":command" has no usage information available', [
                    ':command' => static::getExecutedPath(true),
                ]))
                                         ->makeWarning();
            }
        }
    }


    /**
     * Returns true if the specified command has help support available
     *
     * @return void
     */
    protected static function checkHelp(): void
    {
        global $argv;
        if ($argv['help']) {
            if (
                empty(File::new(static::$command . '.php', DIRECTORY_COMMANDS)
                          ->grep(['CliDocumentation::setHelp('], 100))
            ) {
                throw CliCommandException::new(tr('The command ":command" has no help information available', [
                    ':command' => static::getExecutedPath(true),
                ]))
                                         ->makeWarning();
            }
        }
    }


    /**
     * Will attempt to fix the MySQL timezones missing exception
     *
     * @param Throwable $e
     *
     * @return void
     */
    protected static function fixMysqlTimezoneException(Throwable $e): void
    {
        $e = Exception::new($e);
        Log::warning(tr('MySQL does not yet have the required timezones loaded on connector ":connector". Attempting to load them now. If this is not what you want, please configure the configuration path ":config" to false', [
            ':connector' => $e->getDataKey('connector'),
            ':config'    => 'databases.connectors.' . $e->getDataKey('connector') . '.timezones-name',
        ]));
        Log::information(tr('Importing timezone data files in MySQL, this may take a couple of seconds'));
        Log::warning(tr('You may ignore any "Warning: Unable to load \'/usr/share/zoneinfo/........\' as time zone. Skipping it." messages'));
        Log::warning(tr('Please fill in MySQL root password in the following "Enter password:" request'));
        $password = Cli::readPassword('Please specify the MySQL root password');
        if (!$password) {
            throw OutOfBoundsException::new(tr('No MySQL root password specified'))
                                      ->makeWarning();
        }
        Mysql::new()
             ->importTimezones($password);
    }


    /**
     * Reads and returns the contents of the STDIN
     *
     * @param bool $binary_safe
     *
     * @return string|null
     */
    public static function getStdInStream(bool $binary_safe = true): ?string
    {
        if (empty(static::$stdin_data)) {
            if (stream_isatty(STDIN)) {
                // There is no STDIN stream, its a TTY
                return null;
            }

            $return = null;
            $stdin  = File::new(STDIN);

            while (!$stdin->isEof()) {
                if ($binary_safe) {
                    $data = $stdin->read();
                } else {
                    $data = $stdin->readLine();
                }
                if ($data === false) {
                    break;
                }
                $return .= $data;
            }

            static::$stdin_has_been_read = true;
            static::$stdin_data          = $return;
        }

        return static::$stdin_data;
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
     * @param int  $code
     * @param bool $only_if_null
     *
     * @return void
     */
    public static function setExitCode(int $code, bool $only_if_null = false): void
    {
        if (($code < 0) or ($code > 255)) {
            throw new OutOfBoundsException(tr('Invalid exit code ":code" specified, it should be a positive integer value between 0 and 255', [
                ':code' => $code,
            ]));
        }
        if (!$only_if_null or !static::$exit_code) {
            static::$exit_code = $code;
        }
    }


    /**
     * Echos the specified string to the command line
     *
     * @param string|float|int $string
     *
     * @return void
     */
    public static function echo(string|float|int $string): void
    {
        echo $string . PHP_EOL;
    }


    /**
     * This process can only run once at the time
     *
     * @param bool $global
     *
     * @return void
     */
    public static function exclusive(bool $global = false): void
    {
        static::limitCount(1, $global);
    }


    /**
     * Limit the number of processes to the specified amount
     *
     * @param int  $count
     * @param bool $global
     *
     * @return void
     */
    public static function limitCount(int $count, bool $global = false): void
    {
        static::$run_file->getCount();
    }


    /**
     * Returns true if there is a piped or redirected STDIN data stream available
     *
     * @return bool
     */
    public static function hasStdInStream(): bool
    {
        return !stream_isatty(STDIN);
    }


    /**
     * Returns true if the STDIN stream has been read
     *
     * @return bool
     */
    public static function stdInHasBeenRead(): bool
    {
        return static::$stdin_has_been_read;
    }


    /**
     * Requires the user to type YES to confirm, unless -F,--force was specified on command line
     *
     * @param string $message
     * @param string $reply
     *
     * @return void
     */
    public static function requestConfirmation(string $message, string $reply = 'YES'): void
    {
        if (!FORCE) {
            $result = Cli::readInput($message);
            if ($result !== $reply) {
                throw new ValidationFailedException(tr('No ":reply" specified on prompt', [
                    ':reply' => $reply,
                ]));
            }
        }
    }


    /**
     * Returns true if the UID of the process and pho match
     *
     * @return bool
     */
    public static function getPhoUidMatch(): bool
    {
        return static::$pho_uid_match;
    }


    /**
     * Returns the UID for the ./pho file
     *
     * @return int
     */
    public static function getPhoUid(): int
    {
        return static::$pho_uid;
    }


    /**
     * Returns all options for readline <TAB> autocomplete
     *
     * @param string $input
     * @param int    $index
     *
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
