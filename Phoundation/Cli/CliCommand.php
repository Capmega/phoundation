<?php

/**
 * Class CliCommand
 *
 * This is the CLI command handler class. It will execute the commands indicated on the command line
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
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */


declare(strict_types=1);

namespace Phoundation\Cli;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Config\Config;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Cache\Cache;
use Phoundation\Cache\InstanceCache;
use Phoundation\Cli\Exception\CliArgumentsException;
use Phoundation\Cli\Exception\CliAutoCompleteException;
use Phoundation\Cli\Exception\CliCommandException;
use Phoundation\Cli\Exception\CliCommandNotExistsException;
use Phoundation\Cli\Exception\CliCommandNotFoundException;
use Phoundation\Cli\Exception\CliException;
use Phoundation\Cli\Exception\CliNoCommandSpecifiedException;
use Phoundation\Cli\Exception\CliRunTimeExpiredException;
use Phoundation\Content\Media\Audio\Success;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Exception\ProjectException;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Tmp;
use Phoundation\Data\Traits\TraitDataStaticExecuted;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validator;
use Phoundation\Databases\Sql\Exception\SqlNoTimezonesException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Date\PhoDate;
use Phoundation\Date\PhoTime;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\EnvironmentException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\ScriptException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Commands\Databases\MySql;
use Phoundation\Os\Processes\Process;
use Phoundation\Os\Services\Exception\ServiceUnavailableException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
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
     * The command file that is being executed
     *
     * @var string|null $command_file
     */
    protected static ?string $command_file = null;

    /**
     * The original set of commands
     *
     * @var array|null $commands
     */
    protected static ?array $commands = null;

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
     * @var bool $pho_uid_match
     */
    protected static bool $pho_uid_match;

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
     * Tracks whether the CliCommand object has started up or not
     *
     * @var bool $started_up
     */
    protected static bool $started_up = false;

    /**
     * The detected service command
     *
     * @var string|null $service
     */
    protected static ?string $service;

    /**
     * The maximum runtime for this command
     *
     * @var float|null
     */
    protected static ?float $max_runtime = null;


    /**
     * Returns if the default command will be executed if no command was specified
     *
     * @return bool
     */
    public static function getRequireDefault(): bool
    {
        return CliCommand::$require_default;
    }


    /**
     * Returns true if the CliCommand has started up
     *
     * @return bool
     */
    public function hasStartedUp(): bool
    {
        return CliCommand::$started_up;
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
        CliCommand::$require_default = $require_default;
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
     * Returns a PhoFile object containing the file for the command that will be executed
     *
     * @return PhoFileInterface|null
     */
    public function getCommandFile(): ?PhoFileInterface
    {
        if (empty(CliCommand::$command_file)) {
            return null;
        }

        return new PhoFile(CliCommand::$command_file, PhoRestrictions::newRootObject());
    }


    /**
     * Sets the process title
     *
     * @param string|null $title
     *
     * @return void
     */
    public static function setProcessTitle(?string $title = null): void
    {
        $title = ($title ?? 'pho: ' . CliCommand::getCommandsString());

        cli_set_process_title($title);
        file_put_contents('/proc/' . getmypid() . '/comm', $title);
    }


    /**
     * Returns the process title
     *
     * @param bool $short
     *
     * @return string
     */
    public function getProcessTitle(bool $short = true): string
    {
        if ($short) {
            return (trim(file_get_contents('/proc/' . getmypid() . '/comm'), "\r\n"));
        }

        return cli_get_process_title();
    }


    /**
     * Execute a command by the "pho" command
     *
     * @return void
     * @throws Throwable
     */
    #[NoReturn] public static function execute(): void
    {
        try {
            // Get parameters, get the command to execute, get a run file
            $parameters = CliCommand::start();

        } catch (Throwable $e) {
            echo 'CLI startup failed with the following exception:' . PHP_EOL;
            throw $e;
        }

        CliCommand::setCommandOrExecuteDocumentation($parameters);
        CliCommand::$run_file = new CliRunFile(CliCommand::$command_file);

        // TODO Move this to the Request object
        CliCommand::addExecutedPath(CliCommand::$command_file);

        // Should usage or help documentation be executed instead?
        CliCommand::checkUsage();
        CliCommand::checkHelp();
        CliCommand::processServiceCommands();

        // Execute the command and finish execution
        try {
            CliCommand::setProcessTitle();
            Request::setRestrictionsObject(PhoRestrictions::newFilesystemRootObject());
            Request::execute(CliCommand::$command_file . '.php');

        } catch (SqlNoTimezonesException $e) {
            CliCommand::fixMysqlTimezoneException($e);
        }

        // Make sure that the CLI auto-completion is configured for this shell.
        CliAutoComplete::setup();

        if (!stream_isatty(STDIN) and !CliCommand::$stdin_has_been_read) {
            // STDIN might happen with commands executed. Test the input stream if there was any data at all in it
            if (CliCommand::getStdInStream()) {
                Log::warning(ts('Warning: STDIN stream was specified but not used'));
            }
        }

        // The execution process has finished, start the shutdown procedures
        exit();
    }


    /**
     * Processes command line service commands (-S or --service)
     *
     * @return bool
     */
    protected static function processServiceCommands(): bool
    {
        if (CliCommand::$service) {
            if (CliCommand::hasSystemDConfigure()) {
                return true;
            }

            throw ServiceUnavailableException::new(tr('The command ":command" cannot be run as a service', [
                ':command' => CliCommand::getCommandsString(),
            ]))->makeWarning();
        }

        return false;
    }


    /**
     * Returns the service commands given on the command line
     *
     * @return string|null
     */
    public static function getServiceCommands(): ?string
    {
        return CliCommand::$service;
    }


    /**
     * Returns true if the current command file has a SystemDService::configure() call
     *
     * @return bool
     */
    protected static function hasSystemDConfigure(): bool
    {
        $results = PhoFile::new(CliCommand::$command_file . '.php', PhoRestrictions::newFilesystemRootObject())
                          ->grep(['SystemDService::configure('], 100);

        return !empty($results);
    }


    /**
     * Startup the CLI command processor object
     *
     * @return array
     */
    public static function start(): array
    {
        if (CliCommand::$started_up) {
            throw new CliCommandException(tr('Cannot startup the CliCommand class, it has already been started up'));
        }

        // Boot the Core object
        Core::boot();

        // Startup sequence for the command line
        CliCommand::onlyCommandLine();
        CliCommand::initalizeSignalHandlers();
        CliCommand::checkPhoNotWorldExecutable();
        CliCommand::detectProcessUidMatchesPhoundationOwner();
        CliCommand::processSystemArguments();
        CliCommand::ensureProcessUidMatchesPhoundationOwner();
        CliCommand::initializeReadLine();

        // Startup the Core object and return command limits information
        return CliCommand::startupCore();
    }


    /**
     * Defines the readline completion function
     *
     * @return void
     */
    protected static function initializeReadline(): void
    {
        readline_completion_function([
            '\Phoundation\Cli\CliCommand',
            'completeReadline',
        ]);
    }


    /**
     * Initializes the command line signal handers
     *
     * @return void
     */
    protected static function initalizeSignalHandlers(): void
    {
        // Catch and handle process control signals
        pcntl_async_signals(true);

        pcntl_signal(SIGINT, [
            '\Phoundation\Core\ProcessControlSignals',
            'execute',
        ]);

        pcntl_signal(SIGTERM, [
            '\Phoundation\Core\ProcessControlSignals',
            'execute',
        ]);

        pcntl_signal(SIGHUP, [
            '\Phoundation\Core\ProcessControlSignals',
            'execute',
        ]);
    }


    /**
     * Starts up Core and handles Core startup exceptions
     *
     * @return array Command limit information, if any
     */
    protected static function startupCore(): array
    {
        $return = [
            'limit'  => null,
            'reason' => null,
        ];

        try {
            // Startup the system core
            Core::startup();

        } catch (ProjectException|EnvironmentException $e) {
            $return['limit']  = 'system/project/setup';
            $return['reason'] = $e;

        } catch (SqlUnknownDatabaseException $e) {
            $return['limit']  = 'system/project/init';
            $return['reason'] = $e;
        }

        if (Core::getMaintenanceMode()) {
            // We're running in maintenance mode, limit command execution to system/
            $return['limit']  = ['system/', 'project/', 'info'];
            $return['reason'] = tr('system has been placed in maintenance mode by user ":user" and only "./pho project ..." commands are available right now. If maintenance mode is stuck then please run "./pho project modes maintenance disable" to disable maintenance mode. Please note that all web requests are being blocked as well during maintenance mode!', [
                ':user' => Core::getMaintenanceMode(),
            ]);
        }

        Core::setScriptState();
        return $return;
    }


    /**
     * Will throw exception when PHO command is world executable.
     *
     * @return void
     * @todo Implement this method
     */
    protected static function checkPhoNotWorldExecutable(): void
    {
        return;
        throw new CliCommandException(tr('Refusing to startup, the "pho" command is world executable. Please fix this first by running "chmod o-rwx ./pho" in your projects root directory.'));
    }


    /**
     * Detects if the process owner and file owner are the same. If not, will disable file logging and set
     * CliCommand::getUidMatch() to false
     *
     * @return void
     */
    protected static function detectProcessUidMatchesPhoundationOwner(): void
    {
        try {
            CliCommand::$pho_uid = Core::getPhoUid();

        } catch (Throwable $e) {
            // Wut? What happened? Does the pho command exist? If it does, how did we got here? ./pho renamed, perhaps?
            echo 'Failed to get file owner information of "PROJECT_ROOT/pho" command file' . PHP_EOL;
            exit();
        }

        if (Core::getProcessUid() === CliCommand::$pho_uid) {
            // Correct user, yay!
            CliCommand::$pho_uid_match = true;
            return;
        }

        // UID does NOT match, disable logging for now to avoid issues
        CliCommand::$pho_uid_match = false;
    }


    /**
     * Returns true if the PHO command UID matches the UID of the current process
     *
     * @note Returns NULL if the UID match check has not yet been executed
     *
     * @param bool $root_matches
     * @return bool|null
     */
    public static function phoUidMatch(bool $root_matches = false): ?bool
    {
        return CliCommand::$pho_uid_match or !Core::getProcessUid();
    }


    /**
     * Ensures that the process owner and file owner are the same.
     *
     * @param bool $auto_switch
     * @param bool $permit_root
     *
     * @return void
     */
    protected static function ensureProcessUidMatchesPhoundationOwner(bool $auto_switch = true, bool $permit_root = true): void
    {
        if (CliCommand::phoUidMatch()) {
            // Correct user, yay!
            return;
        }

        if (!Core::getProcessUid() and $permit_root) {
            // This command is run as root and the user root is authorized!
            return;
        }

        // UID mismatch, stop logging to file as that likely won't be possible at all. Also stop all file access
        Log::disableFile();
        PhoFile::disable();

        if (!$auto_switch) {
            throw new CliException(tr('The user ":puser" is not allowed to execute these commands, only user ":fuser" can do this. use "sudo -u :fuser COMMANDS instead.', [
                ':puser' => CliCommand::getProcessUser(),
                ':fuser' => get_current_user(),
            ]));
        }

        $user = posix_getpwuid(CliCommand::$pho_uid);

        if ($user) {
            // Restart the process using the correct user
            CliCommand::restartAsUser($user['name']);
        }

        throw new OutOfBoundsException(tr('Failed to fetch user information for user id ":id"', [
            ':id' => CliCommand::$pho_uid,
        ]));
    }


    /**
     * Restarts this command as the specified user
     *
     * @param string $user
     * @return never
     */
    #[NoReturn] public static function restartAsUser(string $user): never
    {
        // From here we will restart the process using SUDO with the correct user
        // Start building the argument list
        $command   = escapeshellcmd(DIRECTORY_ROOT . 'pho');
        $arguments = ArgvValidator::getBackup();

        if (CliAutoComplete::isActive()) {
            // Auto complete requires re-adding the --auto-complete and position and a different argument handling
            $arguments = [
                '--auto-complete',
                '"' . (CliAutoComplete::getPosition() + 1) . ' ' . escapeshellcmd($command) . ' ' . implode(' ', $arguments) . '"'
            ];

        } else {
            // Ensure all arguments are properly escaped
            if ($arguments) {
                foreach ($arguments as &$argument) {
                    $argument = escapeshellarg($argument);
                }
            }
        }

        unset($argument);

        // As what user should we execute this? Build the sudo command to be executed
        $command = 'sudo -Esu ' . escapeshellarg($user) . ' ' . $command . ' ' . Strings::force($arguments, ' ');

        if (!CliAutoComplete::isActive() and Log::getVerbose()) {
            if (Log::getVerbose()) {
                echo 'Re-executing ./pho command as user "' . $user . '" with command:' . $command . PHP_EOL;

            } else {
                echo 'Re-executing ./pho command as user "' . $user . '"' . PHP_EOL;
            }
        }

        // Re-execute this PHO command with sudo as the correct user
        passthru($command, $result_code);

        // We likely won't be able to log here (nor should we), so disable logging
        Core::setScriptState();
        Core::exit($result_code, direct_exit: true);
    }


    /**
     * Restarts the current process as the user "root"
     *
     * @return never
     */
    public static function restartAsRoot(): never
    {
        CliCommand::restartAsUser('root');
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
        // The process was killed by a TERM signal
        if ($sig_kill) {
            echo Strings::ensureEndsWith($exit_message, PHP_EOL);
            exit($exit_code);
        }

        if (!config()->getEnvironment()) {
            // Config class didn't get environment, this means the process died somewhere during startup.
            // We can't log using the Log class, so die with the exit message
            if (PLATFORM_CLI) {
                echo Strings::ensureEndsWith($exit_message, PHP_EOL);
                exit($exit_code);
            }

            // We won't show anything on the web platform
            Log::toAlternateLog($exit_message);
            exit();
        }

        if (is_object($exit_code)) {
            // Specified exit code is an exception, we're in trouble...
            $e         = $exit_code;
            $exit_code = $exit_code->getCode();
        }

        if ($exit_code) {
            CliCommand::setExitCode($exit_code, true);
        }

        // Terminate the run file
        if (isset(CliCommand::$run_file)) {
            CliCommand::$run_file->delete();
        }

        // Did we encounter an exception?
        if (isset($e)) {
            if (($e instanceof PhoException) and $e->isWarning()) {
                $exit_code = $exit_code ?? 1;

                Log::warning($e->getMessage());
                Log::warning(ts('Command ":command" ended with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                    ':command'  => CliCommand::getCommandsString(),
                    ':time'     => PhoTime::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'    => Numbers::getHumanReadableAndPreciseBytes(memory_get_peak_usage()),
                    ':exitcode' => $exit_code,
                ]), 10);

            } else {
                $exit_code = $exit_code ?? 255;

                Log::error($e->getMessage());
                Log::error(ts('Command ":command" ended with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                    ':command'  => CliCommand::getCommandsString(),
                    ':time'     => PhoTime::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'    => Numbers::getHumanReadableAndPreciseBytes(memory_get_peak_usage()),
                    ':exitcode' => $exit_code,
                ]), 10);
            }

        } elseif ($exit_code) {
            if ($exit_code >= 200) {
                if ($exit_message) {
                    Log::warning($exit_message, 8);

                } else {
                    // Script ended with warning
                    Log::warning(ts('Command ":command" ended with exit code ":exitcode" warning in ":time" with ":usage" peak memory usage', [
                        ':command'  => CliCommand::getCommandsString(),
                        ':time'     => PhoTime::difference(STARTTIME, microtime(true), 'auto', 5),
                        ':usage'    => Numbers::getHumanReadableAndPreciseBytes(memory_get_peak_usage()),
                        ':exitcode' => $exit_code,
                    ]), 8);
                }

            } else {
                if ($exit_message) {
                    Log::error($exit_message, 8);

                } else {
                    // Script ended with error
                    Log::error(ts('Command ":command" failed with exit code ":exitcode" in ":time" with ":usage" peak memory usage', [
                        ':command'  => CliCommand::getCommandsString(),
                        ':time'     => PhoTime::difference(STARTTIME, microtime(true), 'auto', 5),
                        ':usage'    => Numbers::getHumanReadableAndPreciseBytes(memory_get_peak_usage()),
                        ':exitcode' => $exit_code,
                    ]), 8);
                }
            }

        } else {
            // Give a "success!" sound for normally executed commands (so NOT auto complete actions!)
            if (!CliAutoComplete::isActive()) {
                Success::new()->playLocal(true);

                if ($exit_message) {
                    Log::success($exit_message, 8);
                }

                // Script ended successfully
                Log::success(ts('Finished command ":command" with PID ":pid" in ":time" with ":usage" peak memory usage', [
                    ':command' => CliCommand::getCommandsString(),
                    ':pid'     => getmypid(),
                    ':time'    => PhoTime::difference(STARTTIME, microtime(true), 'auto', 5),
                    ':usage'   => Numbers::getHumanReadableAndPreciseBytes(memory_get_peak_usage()),
                ]), 8);
            }
        }

        if (!CliAutoComplete::isActive()) {
            InstanceCache::logStatistics();
            Cache::logStatistics();
            Sql::logStatistics();

            echo CliColor::getColorReset();

            // ????????
            system('stty echo');
        }

        // Remove subprocess run directory
        Process::deleteRunDirectory();

        if (Log::syslogIsOpen()) {
            // Close the syslog
            closelog();
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
        if (CliCommand::$commands) {
            return implode(' ', CliCommand::$commands);
        }

        return 'N/A';
    }


    /**
     * Returns the list of commands that came to the command that executed in space separated string format
     *
     * @param bool $strip_service_arguments
     *
     * @return string
     */
    public static function getCommandline(bool $strip_service_arguments = true): string
    {
        $args   = ArgvValidator::new()->getBackup();
        $return = [];

        // Strip the service command arguments
        if ($strip_service_arguments) {
            Arrays::nextValue($args, '-S'       , true);
            Arrays::nextValue($args, '--service', true);
        }

        // Add all arguments escaped
        foreach ($args as $argument) {
            $return[] = escapeshellarg($argument);
        }

        return DIRECTORY_ROOT . 'pho ' . implode(' ', $return);
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
            CliCommand::runOnceLocal();
        }
    }


    /**
     * Ensure that the current command file cannot be run twice
     *
     * This function will ensure that the current command file cannot be run twice. In order to do this, it will create
     * a run file in data/system/run/SCRIPTNAME with the current process id. If, upon starting, the command file already
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
//        try {
//            $run_dir = DIRECTORY_ROOT . 'data/system/run/';
//            $command = $core->register['command'];
//
//            PhoDirectory::ensure(dirname($run_dir . $command));
//
//            if ($close) {
//                if (!$executed) {
//                    // Hey, this command is being closed but was never opened?
//                    Log::warning(ts('The cli_run_once_local() function has been called with close option, but it was already closed or never opened.'));
//                }
//
//                file_delete([
//                    'patterns'     => $run_dir . $command,
//                    'restrictions' => DIRECTORY_ROOT . 'data/system/run/',
//                    'clean_path'   => false,
//                ]);
//
//                $executed = false;
//
//                return;
//            }
//
//            if ($executed) {
//                // Hey, command has already been run before, and its run again without the close option, this should
//                // never happen!
//                throw new CliException(tr('The function has been called twice by command ":command" without $close set to true! This function should be called twice, once without argument, and once with boolean "true"', [
//                    ':command' => $command,
//                ]));
//            }
//
//            $executed = true;
//
//            if (file_exists($run_dir . $command)) {
//                // Run file exists, so either a process is running, or a process was running but crashed before it could
//                // delete the run file. Check if the registered PID exists, and if the process name matches this one
//                $pid = file_get_contents($run_dir . $command);
//                $pid = trim($pid);
//
//                if (!is_numeric($pid) or !is_natural($pid) or ($pid > 65536)) {
//                    Log::warning(ts('The run file ":file" contains invalid information, ignoring', [':file' => $run_dir . $command]));
//
//                } else {
//                    $name = safe_exec([
//                        'commands' => ['ps', ['-p', $pid,
//                                'connector' => '|',
//                            ],
//                            'tail',
//                            [
//                                '-n',
//                                1,
//                            ],
//                        ],
//                    ]);
//                    $name = array_pop($name);
//                    if ($name) {
//                        preg_match_all('/.+?\d{2}:\d{2}:\d{2}\s+(' . str_replace('/', '\/', $command) . ')/', $name, $matches);
//                        if (!empty($matches[1][0])) {
//                            throw new CliException(tr('The command ":command" for this project is already running', [
//                                ':command' => $command,
//                            ]));
//                        }
//                    }
//                }
//                // PhoFile exists, or contains invalid data, but PID either doesn't exist, or is used by a different
//                // process. Remove the PID file
//                Log::warning(ts('cli_run_once_local(): Cleaning up stale run file ":file"', [':file' => $run_dir . $command]));
//                file_delete([
//                    'patterns'     => $run_dir . $command,
//                    'restrictions' => DIRECTORY_ROOT . 'data/system/run/',
//                    'clean_path'   => false,
//                ]);
//            }
//            // No run file exists yet, create one now
//            file_put_contents($run_dir . $command, getmypid());
//            Core::readRegister('shutdown_cli_run_once_local', [true]);
//
//        } catch (Exception $e) {
//            if ($e->getCode() == 'already-running') {
//                // Keep throwing this one
//                throw($e);
//            }
//            throw new CliException('cli_run_once_local(): Failed', $e);
//        }
    }


    /**
     * Either finds the command to execute, or executes documentation
     *
     * @param array $parameters
     * @return void
     */
    protected static function setCommandOrExecuteDocumentation(array $parameters): void
    {
        if (CliAutoComplete::isActive()) {
            CliCommand::$command_file = CliCommand::autoComplete();

        } else {
            try {
                // Get the command file to execute
                CliCommand::$command_file = CliCommand::findCommand();

            } catch (CliNoCommandSpecifiedException) {
                if (CliCommand::$service) {
                    throw ServiceUnavailableException::new(tr('Cannot start pho as a service without a valid command'))
                                                     ->makeWarning();
                }

                // See if the command execution should be stopped for some reason.
                CliCommand::limitCommand(isset_get($parameters['limit']), isset_get($parameters['reason']));

                CliCommand::documentation();
                CliAutoComplete::setup();
                exit();
            }
        }

        // See if the command execution should be stopped for some reason.
        CliCommand::limitCommand(isset_get($parameters['limit']), isset_get($parameters['reason']));
    }


    /**
     * Returns the command to replicate auto complete commands
     *
     * @return string
     */
    public static function getAutoCompleteCommand(): string
    {
        $complete = array_pop($_SERVER['argv']);
        $complete = Strings::quote($complete, '"');

        $_SERVER['argv'][] = $complete;

        return implode(' ', $_SERVER['argv']);
    }


    /**
     * Process auto complete requests
     *
     * @return string|null
     */
    #[NoReturn] protected static function autoComplete(): ?string
    {
        Core::setScriptState();

        Log::action(ts('Executing auto complete with command: :command', [
            ':command' => CliCommand::getAutoCompleteCommand(),
        ]), 7, echo_screen: false);

        try {
            // Get the command file to execute and execute auto complete for within this command, if available
            $command = CliCommand::findCommand();

            // AutoComplete::getPosition() might become -1 if one were to <TAB> right at the end of the last command.
            // If this is the case, we actually have to expand the command, NOT yet the command parameters!
            if ((CliAutoComplete::getPosition() - count(CliCommand::$commands)) < 0) {
                throw CliCommandNotExistsException::new(tr('The specified command ":file" does exist but requires auto complete extension', [
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
            CliAutoComplete::processCommands(CliCommand::$commands, $e->getData());
        }
    }


    /**
     * Find the command to execute from the given arguments
     *
     * @return string
     */
    protected static function findCommand(): string
    {
        $command  = null;
        $position = 0;
        $file     = DIRECTORY_COMMANDS;
        $commands = ArgvValidator::getCommands();

        // Ensure commands cache directory exists
        if (!file_exists($file)) {
            Log::warning(ts('Commands cache directory ":path" does not yet exists, rebuilding commands cache', [
                ':path' => $file,
            ]), 7);

            // Rebuild the command cache
            Libraries::rebuildCommandsCache();
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
            if (!CliCommand::validateCommand($command)) {
                continue;
            }

            CliCommand::$commands[] = $command;

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

                throw CliCommandNotExistsException::new(tr('The specified command ":file" does not exist', [
                    ':file' => $file,
                ]))->makeWarning()
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
            // Does a file with the directory name exist inside? Only check if the NEXT command does not exist as a file
            $file .= '/';
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
        }

        // Here we're still in a directory. If a file exists in that directory with the same name as the directory
        //  itself, then that is the one that will be executed. For example, ./pho project init will execute
        // DIRECTORY_COMMANDS/system/init/init
        if (file_exists($file . $command)) {
            if (!is_dir($file . $command)) {
                // Yup, this is it, guys!
                return $file . $command;
            }
        }

        // Check if there are commands before the current <TAB>
        $test = Strings::from($file, DIRECTORY_COMMANDS);

        // Build a list of previous commands
        if ($test) {
            $previous = scandir(dirname($file));
            $previous = Arrays::replaceValuesWithCallbackReturn($previous, function ($key, $value) { return strip_extension($value); });
            $previous = Arrays::removeMatchingValues($previous, '/^\./', flags: Utils::MATCH_REGEX);

        } else {
            $previous = [];
        }

        $commands = scandir($file);
        $commands = Arrays::replaceValuesWithCallbackReturn($commands, function ($key, $value) { return strip_extension($value); });
        $commands = Arrays::removeMatchingValues($commands, '/^\./', flags: Utils::MATCH_REGEX);

        // We're stuck in a directory still, no command to execute.
        // Add the available files to display to help the user
        throw CliCommandNotFoundException::new(tr('The specified command ":file" does not exist', [
            ':file' => Strings::from($file, DIRECTORY_COMMANDS)
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
        return CliCommand::$commands;
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
            ]))->makeWarning();
        }

        if (str_starts_with($command, '-')) {
            // Commands can only have alphanumeric characters
            throw OutOfBoundsException::new(tr('The specified command ":command" starts with a - character which is not allowed', [
                ':command' => $command,
            ]))->makeWarning();
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
        Libraries::rebuildCommandsCache();
    }


    /**
     * Display documentation
     *
     * @return void
     */
    protected static function documentation(): void
    {
        if (CliCommand::$require_default) {
            CliDocumentation::setUsage('./pho METHODS [ARGUMENTS]
./pho intro
./pho info
./pho find COMMAND
./pho rebuild
./pho accounts users create --help
./pho project update -H
./pho dev patch -H
./pho project modes maintenance disable
./pho <TAB>
./pho sy<TAB>
./pho system <TAB>', false);

            CliDocumentation::setHelp(tr('This is the Phoundation CLI interface command "pho". For more basic information please execute the command ./pho intro which will print an introduction text to Phoundation
            
            
GLOBAL SYSTEM ARGUMENTS
            
            
') . CliCommand::getHelpGlobalArguments(), false);

            Log::cli(ts('This is the Phoundation CLI command "pho". It can be used to execute all internal Phoundation commands. 
For more basic information please execute the command ./pho intro which will print an introduction text to Phoundation
For details about system command line parameters, try executing ./pho -H, or for command specific parameters, try ./pho command [... command] -H
For usage examples, try ./pho -U, or ./pho command [... command] -U'));
        }
    }


    /**
     * Limit execution of commands to the specified limit
     *
     * @param array|string|null     $limits
     * @param Throwable|string|null $reason
     *
     * @return void
     */
    protected static function limitCommand(array|string|null $limits, Throwable|string|null $reason): void
    {
        if ($limits) {
            $test = Strings::from(CliCommand::$command_file, 'commands/');

            foreach (Arrays::force($limits) as $limit) {
                if (str_starts_with($test, $limit)) {
                    return;
                }
            }

            if ($reason instanceof Throwable) {
                throw $reason;
            }

            throw ScriptException::new(tr('Cannot execute command ":command" because :reason. You may need to execute "./pho project setup"', [
                ':command' => $test,
                ':reason'  => $reason,
            ]));
        }
    }


    /**
     * Returns true if the specified command has usage support available
     *
     * @param bool $exception
     *
     * @return bool
     */
    protected static function checkUsage(bool $exception = true): bool
    {
        global $argv;

        if ($argv['usage']) {
            $results = PhoFile::new(CliCommand::$command_file . '.php', PhoRestrictions::newFilesystemRootObject())
                              ->grep(['CliDocumentation::setUsage('], 100);

            if (empty($results)) {
                if ($exception) {
                    throw CliCommandException::new(tr('The command ":command" has no usage information available', [
                        ':command' => CliCommand::getExecutedPath(true),
                    ]))->makeWarning();
                }

                return false;
            }
        }

        return true;
    }


    /**
     * Returns the help contents for the current command
     *
     * @return string|null
     */
    public static function getHelp(): ?string
    {
return 'under construction';
    }


    /**
     * Returns true if the specified command has help support available
     *
     * @param bool $exception
     *
     * @return bool
     */
    protected static function checkHelp(bool $exception = true): bool
    {
        global $argv;

        if ($argv['help']) {
            $results = PhoFile::new(CliCommand::$command_file . '.php', PhoRestrictions::newFilesystemRootObject())
                              ->grep(['CliDocumentation::setHelp('], 100);

            if (empty($results)) {
                if ($exception) {
                    throw CliCommandException::new(tr('The command ":command" has no help information available', [
                        ':command' => CliCommand::getExecutedPath(true),
                    ]))->makeWarning();
                }

                return false;
            }
        }

        return true;
    }


    /**
     * Will attempt to fix the MySQL timezones missing exception
     *
     * @param Throwable $e
     *
     * @return void
     * @todo This has nothing todo with CliCommand, move this to a different class
     */
    protected static function fixMysqlTimezoneException(Throwable $e): void
    {
        $e = PhoException::new($e);

        Log::warning(ts('MySQL does not yet have the required timezones loaded on connector ":connector". Attempting to load them now. If this is not what you want, please configure the configuration path ":config" to false', [
            ':connector' => $e->getDataKey('connector'),
            ':config'    => 'databases.connectors.' . $e->getDataKey('connector') . '.timezones-name',
        ]));

        Log::information(ts('Importing timezone data files in MySQL, this may take a couple of seconds'));
        Log::warning(ts('You may ignore any "Warning: Unable to load \'/usr/share/zoneinfo/........\' as time zone. Skipping it." messages'));
        Log::warning(ts('Please fill in MySQL root password in the following "Enter password:" request'));

        $password = Cli::readPassword('Please specify the MySQL root password');

        if (!$password) {
            throw OutOfBoundsException::new(tr('No MySQL root password specified'))->makeWarning();
        }

        Mysql::new()->importTimezones($password);
    }


    /**
     * Reads and returns the contents from STDIN if available, OR from terminal password input
     *
     * @param string $prompt
     * @param bool   $binary_safe
     *
     * @todo This sort of bypasses standard validation?! Remove the
     *
     * @return string|null
     */
    public static function getStdInStreamOrPassword(string $prompt, bool $binary_safe = true): ?string
    {
        if (!$prompt) {
            throw new OutOfBoundsException(tr('Cannot get password, no prompt specified'));
        }

        if (CliCommand::getStdInStream()) {
            return CliCommand::getStdInStream($binary_safe);
        }

        return Cli::readPassword($prompt);
    }


    /**
     * Reads and returns the contents from STDIN if available, OR from terminal input
     *
     * @param string $prompt
     * @param bool   $binary_safe
     *
     * @return string|null
     */
    public static function getStdInStreamOrInput(string $prompt, bool $binary_safe = true): ?string
    {
        if (CliCommand::getStdInStream()) {
            return CliCommand::getStdInStream($binary_safe);
        }

        return Cli::readInput($prompt);
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
        if (empty(CliCommand::$stdin_data)) {
            if (stream_isatty(STDIN)) {
                // There is no STDIN stream, its a TTY
                return null;
            }

            $return = null;
            $stdin  = PhoFile::new(STDIN);

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

            CliCommand::$stdin_has_been_read = true;
            CliCommand::$stdin_data          = $return;
        }

        return CliCommand::$stdin_data;
    }


    /**
     * Returns the process exit code
     *
     * @return int
     */
    public static function getExitCode(): int
    {
        return CliCommand::$exit_code;
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

        if (!$only_if_null or !CliCommand::$exit_code) {
            CliCommand::$exit_code = $code;
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
        CliCommand::limitCount(1, $global);
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
        CliCommand::$run_file->getCount();
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
        return CliCommand::$stdin_has_been_read;
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
        return CliCommand::$pho_uid_match;
    }


    /**
     * Returns the UID for the ./pho file
     *
     * @return int
     */
    public static function getPhoUid(): int
    {
        return CliCommand::$pho_uid;
    }


    /**
     * Startup for Command Line Interface
     *
     * @return void
     * @todo Refactor this monstrosity into smaller methods
     */
    protected static function processSystemArguments(): void
    {
        global $argv;

        // Hide all command line arguments
        ArgvValidator::hideData($argv);

        // USe global $argv ONLY if CliCommand::PhoUidMatch() is true because if it isn't we're going to restart, and
        // we will need the $argv as-is
        global $argv;

        // Validate system modifier arguments. Ensure that these variables get stored in the global $argv array because
        // they may be used later down the line by (for example) the CliDocumentation class!
        try {
            $argv = ArgvValidator::new()
                                 ->select('-A,--all')->isOptional(false)->isBoolean()
                                 ->select('-C,--no-color')->isOptional(false)->isBoolean()
                                 ->select('-D,--debug')->isOptional(false)->isBoolean()
                                 ->select('-E,--environment', true)->isOptional()->hasMinCharacters(1)->hasMaxCharacters(64)
                                 ->select('-F,--force')->isOptional(false)->isBoolean()
                                 ->select('-G,--prefix')->isOptional(false)->isBoolean()
                                 ->select('-H,--help')->isOptional(false)->isBoolean()
                                 ->select('-I,--json-input', true)->isOptional()->hasMaxCharacters(8192)
                                 ->select('-J,--json-output')->isOptional()->isBoolean()
                                 ->select('-L,--log-level', true)->isOptional()->isInteger()->isBetween(1, 10)
                                 ->select('-M,--timeout', true)->isOptional(false)->isInteger()
                                 ->select('-N,--no-audio')->isOptional(false)->isBoolean()
                                 ->select('-O,--order-by', true)->isOptional()->hasMinCharacters(1)->hasMaxCharacters(128)
                                 ->select('-P,--page', true)->isOptional(1)->isNatural(false)
                                 ->select('-Q,--verbose')->isOptional(false)->isBoolean()
                                 ->select('-R,--rebuild-commands')->isOptional(false)->isBoolean()
                                 ->select('-S,--service', true)->isOptional()->hasMaxcharacters(2048)
                                 ->select('-T,--test')->isOptional(false)->isBoolean()
                                 ->select('-U,--usage')->isOptional(false)->isBoolean()
                                 ->select('-V,--version')->isOptional(false)->isBoolean()
                                 ->select('-W,--no-warnings')->isOptional(false)->isBoolean()
                                 ->select('-X,--ignore-readonly')->isOptional(false)->isBoolean()
                                 ->select('-Y,--clear-tmp')->isOptional(false)->isBoolean()
                                 ->select('-Z,--clear-caches')->isOptional(false)->isBoolean()
                                 ->select('--auto-complete', true)->isOptional()->hasMaxCharacters(1024)
                                 ->select('--deleted')->isOptional(false)->isBoolean()
                                 ->select('--iec')->isOptional(false)->isBoolean()
                                 ->select('--limit', true)->isOptional(0)->isNatural()
                                 ->select('--locale', true)->isOptional()->hasCharacters(5)
                                 ->select('--no-validation')->isOptional(false)->isBoolean()
                                 ->select('--no-password-validation')->isOptional(false)->isBoolean()
                                 ->select('--show-passwords')->isOptional(false)->isBoolean()
                                 ->select('--si')->isOptional(false)->isBoolean()
                                 ->select('--status', true)->isOptional()->hasMinCharacters(1)->hasMaxCharacters(16)
                                 ->select('--sudo')->isOptional(false)->isBoolean()
                                 ->select('--timezone', true)->isOptional()->isString()
                                 ->validate(false);

        } catch (ValidationFailedException $e) {
            Core::setScriptState();
            throw $e;
        }

        try {
            Core::detectProject();

            // DEBUG CODE, uncomment these if manual $argv settings are required
            //        $argv = [
            //            'all'                    => false,
            //            'no_color'               => false,
            //            'debug'                  => false,
            //            'environment'            => null,
            //            'force'                  => false,
            //            'help'                   => false,
            //            'log_level'              => false,
            //            'order_by'               => false,
            //            'page'                   => 1,
            //            'quiet'                  => false,
            //            'very_quiet'             => false,
            //            'prefix'                 => false,
            //            'no_sound'               => false,
            //            'status'                 => false,
            //            'test'                   => false,
            //            'json_input'             => null,
            //            'json_output'            => null,
            //            'usage'                  => false,
            //            'verbose'                => false,
            //            'no_warnings'            => false,
            //            'language'               => false,
            //            'deleted'                => false,
            //            'version'                => false,
            //            'limit'                  => false,
            //            'timezone'               => null,
            //            'auto_complete'          => null,
            //            'show_passwords'         => false,
            //            'no_validation'          => false,
            //            'no_password_validation' => false
            //    ];

            // Parse command line arguments in JSON format
            if ($argv['json_input']) {
                // We received arguments in JSON format
                $argv = CliCommand::applyJsonArguments($argv);
            }

            // Initialize environment
            if ($argv['environment']) {
                // The Environment was manually specified on the command line
                $environment = $argv['environment'];

            }
            else {
                // Get environment variable from the shell environment
                $environment = getenv('PHOUNDATION_ENVIRONMENT_' . PROJECT);
            }

            if (empty($environment)) {
                CliCommand::requireEnvironment((bool)$argv['auto_complete']);
            }

            // Set environment
            Core::setEnvironment($environment);

            // Set session configuration in case session data must be accessed
            Session::initializePhpIni();

            // What units to use for binary numbers?
            if ($argv['iec']) {
                if ($argv['si']) {
                    throw new CliArgumentsException(ts('Cannot use both arguments --si and --iec, these arguments are mutually exclusive'));
                }

                define('UNITS', 'IEC');

            } elseif ($argv['si']) {
                define('UNITS', 'SI');

            } else {
                define('UNITS', config()->getStringUppercase('log.units', 'si'));
            }

            // Define basic platform constants
            define('ADMIN'     , '');
            define('ALL'       , $argv['all']);
            define('DELETED'   , $argv['deleted']);
            define('FORCE'     , $argv['force']);
            define('LIMIT'     , get_null($argv['limit']) ?? config()->getNatural('paging.limit', 50));
            define('NOAUDIO'   , $argv['no_audio'] or $argv['auto_complete']); // auto complete mode disables audio
            define('NOCOLOR'   , $argv['no_color']);
            define('NOWARNINGS', $argv['no_warnings']);
            define('OUTPUT'    , $argv['json_output'] ? 'json' : 'normal');
            define('PAGE'      , $argv['page']);
            define('PROTOCOL'  , config()->get('web.protocol', 'https://'));
            define('PWD'       , Strings::slash(isset_get($_SERVER['PWD'])));
            define('STATUS'    , $argv['status']);
            define('TEST'      , $argv['test']);
            define('VERBOSE'   , $argv['verbose']);

            if ($argv['log_level']) {
                Log::setThreshold($argv['log_level']);
            }

            Log::setVerbose($argv['verbose']);

            // Set requested language
            Core::writeRegister($argv['language'] ?? config()->getString('languages.default', 'en'), 'system', 'language');

            if ($argv['auto_complete']) {
                // We're in auto complete mode. Show only direct output, don't use any color, don't log to screen
                Log::disableScreen();

                $argv['no_color'] = true;
                $argv['auto_complete'] = explode(' ', trim($argv['auto_complete']));

                $location = array_shift($argv['auto_complete']);

                if (!is_numeric_integer($location)) {
                    throw new CliAutoCompleteException(tr('Invalid location specified, must be an integer number'));
                }

                // Reset the $argv array to the auto complete data
                ArgvValidator::hideData($argv['auto_complete']);
                CliAutoComplete::setPosition($location - 1);
                CliAutoComplete::initSystemArguments();
            }

            // Correct $_SERVER['PHP_SELF'], sometimes seems empty
            if (empty($_SERVER['PHP_SELF'])) {
                if (!isset($_SERVER['_'])) {
                    $e = new OutOfBoundsException('No $_SERVER[PHP_SELF] or $_SERVER[_] found');
                }

                $_SERVER['PHP_SELF'] = $_SERVER['_'];
            }

            // Set more system parameters
            if ($argv['debug']) {
                Debug::switch();
            }

            if ($argv['show_passwords']) {
                Cli::showPasswords(true);
            }

            if ($argv['no_validation']) {
                Validator::disable();
            }

            if ($argv['no_password_validation']) {
                Validator::disablePasswords();
            }

            // Set timeout
            if ($argv['timeout']) {
                // User set timeout
                Core::setTimeout((int)$argv['timeout']);

            } else {
                // Use default timeout
                Core::setTimeout();
            }

            if ($argv['prefix']) {
                Log::setEchoPrefix(true);
            }

            if (!CliCommand::phoUidMatch()) {
                // The rest of the options will NOT be set because we'll try to restart soon!
                return;
            }

            // Set security umask
            umask(config()->get('filesystem.umask', 0007));

            // Set required locale.
            // Set language and locale
            Core::setLanguage();
            Core::setLocale($argv['locale'] ?? config()->getString('locale.default', 'en-ca'));

            // Prepare for unicode usage
            if (Response::hasEncoding('UTF-8')) {
                // TODO Fix this godawful mess!
                mb_init(not_empty(config()->get('locale.LC_CTYPE', ''), config()->get('locale.LC_ALL', '')));

                if (function_exists('mb_internal_encoding')) {
                    mb_internal_encoding('UTF-8');
                }
            }

            Core::setTimeZone($argv['timezone']);

            // Process command line system arguments if we have no exception so far
            if ($argv['version']) {
                Log::cli(Core::getProjectVersions(true));
                Core::setScriptState();
                $exit = 0;
            }

            if ($argv['order_by']) {
                define('ORDERBY', ' ORDER BY `' . Strings::until($argv['order_by'], ' ') . '` ' . Strings::from($argv['order_by'], ' ') . ' ');

                $valid = preg_match('/^ ORDER BY `[a-z0-9_]+`(?:\s+(?:DESC|ASC))? $/', ORDERBY);

                if (!$valid) {
                    // The specified column ordering is NOT valid
                    $e = new CoreException(tr('The specified orderby argument ":argument" is invalid', [':argument' => ORDERBY]));
                }
            }

            // Something failed?
            if (isset($e)) {
                echo 'Command line parser failed with "' . $e->getMessage() . '"' . PHP_EOL;
                CliCommand::setExitCode(1);
                exit(1);
            }

            if (isset($exit)) {
                Core::exit($exit);
            }

            // set terminal data
            // TODO REWRITE TERMINAL SIZE DETECTION
            //        CliCommand::$register['cli'] = ['term' => Cli::getTerm()];
            //
            //        if (CliCommand::$register['cli']['term']) {
            //            CliCommand::$register['cli']['columns'] = Cli::getColumns();
            //            CliCommand::$register['cli']['lines']   = Cli::getLines();
            //
            //            if (!CliCommand::$register['cli']['columns']) {
            //                CliCommand::$register['cli']['size'] = 'unknown';
            //
            //            } elseif (CliCommand::$register['cli']['columns'] <= 80) {
            //                CliCommand::$register['cli']['size'] = 'small';
            //
            //            } elseif (CliCommand::$register['cli']['columns'] <= 160) {
            //                CliCommand::$register['cli']['size'] = 'medium';
            //
            //            } else {
            //                CliCommand::$register['cli']['size'] = 'large';
            //            }
            //        }

            // Validate parameters and give some startup messages, if needed
            if (Debug::isEnabled()) {
                if (Debug::isEnabled()) {
                    Log::warning(ts('Running in DEBUG mode, started @ ":datetime"', [
                        ':datetime' => PhoDate::convert(STARTTIME, 'ISO8601'),
                    ]),          8);

                    // TODO Reimplement terminal size detection
                    //                Log::notice(ts('Detected ":size" terminal with ":columns" columns and ":lines" lines', [
                    //                    ':size'    => CliCommand::$register['cli']['size'],
                    //                    ':columns' => CliCommand::$register['cli']['columns'],
                    //                    ':lines'   => CliCommand::$register['cli']['lines'],
                    //                ]));
                }
            }

            if (FORCE) {
                if (TEST) {
                    throw new CoreException(tr('Both FORCE and TEST modes where specified, these modes are mutually exclusive'));
                }

                Log::warning(ts('Running in FORCE mode'));

            }
            elseif (TEST) {
                Log::warning(ts('Running in TEST mode, various modifications may not be executed!'));
            }

            if (!is_natural(PAGE)) {
                throw new CoreException(tr('Specified -P or --page ":page" is not a natural number', [
                    ':page' => PAGE,
                ]));
            }

            if (!is_natural(LIMIT)) {
                throw new CoreException(tr('Specified --limit":limit" is not a natural number', [
                    ':limit' => LIMIT,
                ]));
            }

            if (ALL) {
                if (PAGE > 1) {
                    throw new CoreException(tr('Both -A or --all and -P or --page have been specified, these options are mutually exclusive'));
                }

                if (DELETED) {
                    throw new CoreException(tr('Both -A or --all and -D or --deleted have been specified, these options are mutually exclusive'));
                }

                if (STATUS) {
                    throw new CoreException(tr('Both -A or --all and -S or --status have been specified, these options are mutually exclusive'));
                }
            }

            if ($argv['rebuild_commands']) {
                // Rebuild only the "commands" cache
                Core::enableInitState();
                CliCommand::rebuildCache();
                CliCommand::setRequireDefault(false);
                Core::disableInitState();
            }

            if ($argv['clear_caches']) {
                // Clear all caches
                try {
                    Core::enableInitState();
                    Log::setVerbose(true);
                    Cache::clearAll();
                    Log::setVerbose(VERBOSE);
                    CliCommand::setRequireDefault(false);
                    Core::disableInitState();

                } catch (Throwable $e) {
                    // Something went wrong, reset Log VERBOSE setting to avoid spamming extra lines on top of Exception
                    Log::setVerbose(VERBOSE);
                    throw $e;
                }
            }

            if ($argv['clear_tmp']) {
                // Clear all tmp data
                Core::enableInitState();
                Tmp::clear();
                CliCommand::setRequireDefault(false);
                Core::disableInitState();
            }

            Core::setIgnoreReadonly($argv['ignore_readonly']);

            if ($argv['sudo']) {
                // Try to execute the current command as root
                CliCommand::restartAsRoot();
            }

            // Ensure any extra dashed arguments are "undashed"
            ArgvValidator::unDoubleDash();

            CliCommand::$service = $argv['service'];

        } catch (Throwable $e) {
            throw new CliCommandException(ts('Failed to process system arguments because: ') . $e->getMessage(), $e);
        }
    }


    /**
     * Displays the correct "environment required" message for normal and CLI auto complete mode
     *
     * @param bool $auto_complete
     *
     * @return never
     */
    #[NoReturn] public static function requireEnvironment(bool $auto_complete): never
    {
        $message = 'No required cli environment specified for project "' . PROJECT . '". Use -E ENVIRONMENT or ensure that your ~/.bashrc file contains a line like "export PHOUNDATION_ENVIRONMENT_' . PROJECT . '=ENVIRONMENT" and then execute "source ~/.bashrc"';

        if ($auto_complete) {
            Core::exit(2, str_replace(' ', '-', $message));
        }

        Config::allowNoEnvironment();
        Core::exit(2, $message);
    }


    /**
     * Returns the max runtime for this CLICommand
     *
     * @return float|null
     */
    public static function getMaxRunTime(): ?float
    {
        return static::$max_runtime;
    }


    /**
     * Sets the max runtime for this CLICommand
     *
     * @param float|null $seconds
     *
     * @return void
     */
    public static function setMaxRunTime(?float $seconds): void
    {
        static::$max_runtime = $seconds;
    }


    /**
     * Returns the number of time the current CLICommand has been running for
     *
     * @return float
     */
    public static function getRunTime(): float
    {
        return microtime(true) - STARTTIME;
    }


    /**
     * Compares the current time to the max runtime. If the max runtime is surpassed, return a callback function
     * and/or throw an exception
     *
     * @param callable|null $callback
     *
     * @return void
     */
    public static function checkMaxRunTime(?callable $callback): void
    {
        if (static::getMaxRunTime()) {
            if (static::getRunTime() > static::getMaxRunTime()) {
                if ($callback) {
                    $callback();
                }

                throw new CliRunTimeExpiredException(tr('The maximum runtime of :time was surpassed', [
                    ':time' => static::getMaxRunTime(),
                ]));
            }
        }
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


    /**
     * Applies the JSON arguments in the given argv array
     *
     * @param array $argv
     *
     * @return array
     */
    protected static function applyJsonArguments(array $argv): array
    {
        $json = Json::decode($argv['json']);
        unset($argv['json']);

        // Convert all JSON argument parameters to proper format and add them to the argv, BUT DO NOT OVERWRITE EXISTING
        foreach ($json as $key => $value) {
            $key = str_replace('-', '_', $key);

            if (str_starts_with($key, '__')) {
                $key = substr($key, 2);
            }

            if (!array_key_exists($key, $argv)) {
                $argv[$key] = $value;
            }
        }

        return $argv;
    }


    /**
     * Returns a help file with the global arguments
     *
     * @return string
     */
    public static function getHelpGlobalArguments(): string
    {
        return tr('[-A, --all]                             If set, the system will run in ALL mode, which typically will display normally
                                        hidden information like deleted entries. Only used by specific commands, check
                                        --help on commands to see if and how this flag is used.

[-C, --no-color]                        If set, your log and console output will no longer have color

[-D, --debug]                           If set will run your system in debug mode. Debug commands will now generate and
                                        display output

[-E, --environment ENVIRONMENT]         Sets or overrides the environment with which your pho command will be running.
                                        If no environment was set in the shell environment using the
                                        ":environment" variable, your pho command will refuse to
                                        run unless you specify the environment manually using these flags. The
                                        environment has to exist as a ROOT/config/ENVIRONMENT.yaml file

[-F, --force]                           If specified, will run the CLI command in FORCE mode, which will override certain
                                        restrictions. See --help for information on how specific commands deal with this
                                        flag

[-G, --prefix]                          Will suppress the DATETIME - LOGLEVEL - PROCESS ID - GLOBAL PROCESS ID prefix
                                        that normally begins each log line output

[-H, --help]                            If specified, will display the help page for the typed command

[-I, --json-input]                      Allows argument to be specified in JSON format. The system will decode the 
                                        arguments and add them to the rest of the argument list without overwriting 
                                        arguments that were already specified on the command line

[-J, --json-output]                     Will output all results in JSON format. 
                                        Warning: This is currently still only partially implemented

[-L, --log-level LEVEL]                 If specified, will set the minimum threshold level for log messages to appear.
                                        Any message with a threshold level below the indicated amount will not appear in
                                        the logs. Defaults to 5.

[-M, --timeout SECONDS]                 If specified will automatically timeout the command after the specified number  
                                        of seconds      

[-N, --no-audio]                        If specified will suppress all audio for this command 

[-O, --order-by "COLUMN ASC|DESC"]      If specified, and used by the command (only commands that display tables) will
                                        order the table contents on the specified column in the specified direction.
                                        Defaults to nothing

[-P, --page PAGE]                       If specified, and used by the command (only commands that display tables) will
                                        show the table on the specified page. Defaults to 1

[-Q, --verbose]                         Will print more output during log startup and shutdown

[-R, --rebuild-commands]                If specified will rebuild the cache for all CLI commands         

[-S, --service COMMAND]                 If specified, will convert the specified command into a SystemD service and 
                                        execute the specified systemd systemctl command

[-T, --test]                            Will run the system in test mode. Different commands may change their behaviour
                                        depending on this flag, see their --help output for more information.

                                        NOTE: In this mode, temporary directories will NOT be removed upon shutdown so
                                        that their contents can be used for debugging and testing.

[-U, --usage]                           Prints various command usage examples for the typed command

[-V, --version]                         Will display the current version for your Phoundation installation
                                        
[-W, --no-warnings]                     Will only use "error" type exceptions with backtrace and extra information,
                                        instead of displaying only the main exception message for warnings

[--ignore-readonly]                     If specified will make the system ignore readonly mode that would normally 
                                        prohibit it from writing to disk or database.
                                        WARNING: When the system is in readonly mode, there usually is a good reason for 
                                                 it (For example: The system is in the middle of an update or upgrade), 
                                                 so use this option with care! Use this only when you know what you are 
                                                 doing or when you are prepared to deal with the consequences! 



[-Y, --clear-tmp]                       Will clear all temporary data in ROOT/data/tmp, and memcached

[-Z, --clear-caches]                    Will clear all caches in ROOT/data/cache, and memcached

[--deleted]                             Will show deleted DataEntry records

[--iec]                                 Will display human readable amounts of data in IEC units (International Electrotechnical Commission, 1_024 = 1KB, 
                                        1_048_576 = 1MB, etc) 
                                        NOTE: Cannot be used with --si  

[--limit NUMBER]                        Will limit table output to the number of specified fields

[--no-validation]                       Will not validate any of the data input. 
                                        WARNING: This may result in invalid data in your database!

[--no-password-validation]              Will not validate passwords.
                                        WARNING: This may result in weak and or compromised passwords in your database
                                        
[--show-passwords]                      Will display passwords visibly on the command line. Both typed passwords and
                                        data output will show passwords in the clear!

[--si]                                  Will display human readable amounts of data in SI units (1_000 = 1KB, 1_000_000 = 1MB, etc)
                                        NOTE: Cannot be used with --iec  

[--status STRING]                       If specified the system will only display entries with the specified status

[--sudo]                                If specified will make the system restart using "sudo" command

[--timezone STRING]                     Sets the specified timezone for the command you are executing

', [':environment' => 'PHOUNDATION_' . PROJECT . '_ENVIRONMENT']);
    }
}
