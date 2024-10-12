<?php

/**
 * Trait ProcessVariables
 *
 * Manages all process variables
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataLogLevel;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Date\Time;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Os\Packages\Interfaces\PackagesInterface;
use Phoundation\Os\Packages\Packages;
use Phoundation\Os\Processes\Commands\Command;
use Phoundation\Os\Processes\Commands\Exception\CommandNotFoundException;
use Phoundation\Os\Processes\Commands\Exception\CommandsException;
use Phoundation\Os\Processes\Commands\Which;
use Phoundation\Os\Processes\Enum\EnumIoNiceClass;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Os\Processes\Exception\ProcessException;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Servers\Traits\TraitDataServer;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;


trait ProcessVariables
{
    use TraitDataLogLevel;
    use TraitDataServer;
    use TraitDataRestrictions {
        setRestrictions as protected ___setRestrictions;
    }


    /**
     * The run path where command output will be written to
     *
     * @var string|null $run_directory
     */
    protected static ?string $run_directory = null;

    /**
     * The command that will be executed for this process
     *
     * @var string|null $command
     */
    protected ?string $command = null;

    /**
     * The actual command that was specified
     *
     * @var string|null $real_command
     */
    protected ?string $real_command = null;

    /**
     * The arguments for the command that will be executed for this process
     *
     * @var array $arguments
     */
    protected array $arguments = [];

    /**
     * Sets environment variables before the command execution
     *
     * @var array $environment_variables
     */
    protected array $environment_variables = [];

    /**
     * The log file where command output will be written to
     *
     * @var string|null
     */
    protected ?string $log_file = null;

    /**
     * Sets if run files should be used or not
     *
     * @var bool $use_run_file
     */
    protected bool $use_run_file = true;

    /**
     * The run file where command output will be written to
     *
     * @var string|null $run_file
     */
    protected ?string $run_file = null;

    /**
     * The process exit codes that is accepted for this process
     *
     * @var array
     */
    protected array $accepted_exit_codes = [0];

    /**
     * The process exit code once it has executed
     *
     * @var int|null
     */
    protected ?int $exit_code = null;

    /**
     * The maximum number of time in seconds that a command is allowed to run before it will time out. Zero to disable,
     * defaults to 30
     *
     * @var int $timeout
     */
    protected int $timeout = 30;

    /**
     * The signal that the timeout will give to the process
     *
     * @var int $signal
     */
    protected int $signal = 15;

    /**
     * The time the process should wait before starting
     *
     * @var int $wait
     */
    protected int $wait = 0;

    /**
     * The process id of the process running in the background. Will only be set when running processes in background
     *
     * @var int|null $pid
     */
    protected ?int $pid = null;

    /**
     * Sets whether the command should be executed with sudo or not. If not NULL, it should contain the user as which
     * the command should be executed
     *
     * @var string|bool $sudo
     */
    protected string|bool $sudo = false;

    /**
     * Sets whether the command should be executed with nocache or not. If not NULL, it should contain the user as which
     * the command should be executed.
     *
     * @note This may require nocache to be installed first!
     *
     * @var int|bool $nocache = false
     */
    protected int|bool $nocache = false;

    /**
     * Sets the nice level for this process
     *
     * @var int $nice
     */
    protected int $nice = 0;

    /**
     * Sets the ionice class for this process
     *
     * @var EnumIoNiceClass $ionice_class
     */
    protected EnumIoNiceClass $ionice_class = EnumIoNiceClass::none;

    /**
     * Sets the ionice level for this process
     *
     * @var int $ionice_level
     */
    protected int $ionice_level = 7;

    /**
     * A cached version of the command line
     *
     * @var string|null
     */
    protected ?string $cached_command_line = null;

    /**
     * Contains the terminal that will be used to execute the command
     *
     * @var string|null $term
     */
    protected ?string $term = null;

    /**
     * If specified, output from this command will be piped to the next command
     *
     * @var ProcessInterface|FsFileInterface|string|null $pipe
     */
    protected ProcessInterface|FsFileInterface|string|null $pipe = null;

    /**
     * If specified, will pipe the string or process output into this command
     *
     * @var ProcessInterface|FsFileInterface|string|null $pipe
     */
    protected ProcessInterface|FsFileInterface|string|null $pipe_from = null;

    /**
     * Stores the data on where to redirect input channels
     *
     * @var array $input_redirect
     */
    protected array $input_redirect = [];

    /**
     * Stores the data on where to redirect output channels
     *
     * @var array $output_redirect
     */
    protected array $output_redirect = [2 => '>&1'];

    /**
     * Registers where the exit code for this process will be stored
     *
     * @var bool $register_run_file
     */
    protected bool $register_run_file = true;

    /**
     * Variable data that can modify the process command that will be executed
     *
     * @var array $variables
     */
    protected array $variables = [];

    /**
     * If set true, the log output files will be deleted as soon as this object is destroyed.
     *
     * @var bool $clear_logs
     */
    protected bool $clear_logs = false;

    /**
     * If specified, these packages will be automatically installed if the specified command for this process does not
     * exist
     *
     * @var PackagesInterface $packages
     */
    protected PackagesInterface $packages;

    /**
     * State variable, tracks if the current process has failed or not
     *
     * @var bool $failed
     */
    protected bool $failed = false;

    /**
     * If set, the process will first CD to this directory before continuing
     *
     * @var FsDirectoryInterface|null $execution_directory
     */
    protected ?FsDirectoryInterface $execution_directory = null;

    /**
     * If true, commands will be printed and logged
     *
     * @var bool $debug
     */
    protected bool $debug = false;

    /**
     * Tracks how many times quotes should be escaped
     *
     * @var int $escape_quotes
     */
    protected int $escape_quotes = 1;

    /**
     * The time the process started execution
     *
     * @var float|null $start
     */
    protected ?float $start = null;

    /**
     * The time the process stopped execution
     *
     * @var float|null $stop
     */
    protected ?float $stop = null;

    /**
     * If specified, this command will be executed before the main command
     *
     * @var Process|null $pre_exec
     */
    protected ?Process $pre_exec = null;

    /**
     * If specified, this command will be executed after the main command
     *
     * @var Process|null $post_exec
     */
    protected ?Process $post_exec = null;

    /**
     * The method used to execute this process
     *
     * @var EnumExecuteMethod|null $method
     */
    protected ?EnumExecuteMethod $method = null;

    /**
     * The output of the process
     *
     * @var array|string|null $output
     */
    protected array|string|null $output = null;

    /**
     * Tracks when this command executed
     *
     * @var DateTime|null $executed_on
     */
    protected ?DateTime $executed_on = null;

    /**
     * Tracks if this process runs as a stand-alone service or not.
     * If true, requires background to be true as well.
     *
     * @var bool $service
     */
    protected bool $service = false;


    /**
     * Process class constructor
     *
     * @param FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory_or_restrictions
     */
    public function __construct(FsRestrictionsInterface|FsDirectoryInterface|null $execution_directory_or_restrictions = null)
    {
        // Ensure that the run files directory is available
        // Set server filesystem restrictions
        $this->setUseRunFile(FsDirectory::getWriteEnabled());

        if ($execution_directory_or_restrictions) {
            if ($execution_directory_or_restrictions instanceof FsRestrictions) {
                $this->setRestrictions($execution_directory_or_restrictions);

            } else {
                $this->setExecutionDirectory($execution_directory_or_restrictions);
            }
        }

        $this->packages  = new Packages();
        $this->log_level = 2;
    }


    /**
     * Set the server on which the command should be executed for this process
     *
     * @note NULL means this local server
     *
     * @param FsRestrictionsInterface|array|string|null $restrictions
     * @param bool                                      $write
     * @param string|null                               $label
     *
     * @return static
     */
    public function setRestrictions(FsRestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static
    {
        $this->cached_command_line = null;
        return $this->___setRestrictions($restrictions, $write, $label);
    }


    /**
     * Process class destructor
     */
    public function __destruct()
    {
        // Delete the log file?
        if ($this->clear_logs) {
            unlink($this->log_file);
        }
    }


    /**
     * Returns the execution method for this process
     *
     * @return EnumExecuteMethod|null
     */
    public function getExecutionMethod(): ?EnumExecuteMethod
    {
        return $this->method;
    }


    /**
     * Returns the exit code from the executed process, NULL if the process has not yet been executed
     *
     * @return ?int
     */
    public function getExitCode(): ?int
    {
        return $this->exit_code;
    }


    /**
     * Returns the exact time that execution started
     *
     * @return float|null
     */
    public function getExecutionStartTime(): ?float
    {
        return $this->start;
    }


    /**
     * Returns the exact time that execution stopped
     *
     * @return float|null
     */
    public function getExecutionStopTime(): ?float
    {
        return $this->stop;
    }


    /**
     * Returns  if this process runs as a service
     *
     * @return bool
     */
    public function getService(): bool
    {
        return $this->service;
    }


    /**
     * Sets if this process runs as a service
     *
     * If set to true, this will require $background set true on execution as well, or will cause an exception
     *
     * @param bool $service
     *
     * @return static
     */
    public function setService(bool $service): static
    {
        $this->service = $service;

        return $this;
    }


    /**
     * Returns the exact time that execution started
     *
     * @return ProcessInterface|null
     */
    public function getPreExecution(): ?ProcessInterface
    {
        return $this->pre_exec;
    }


    /**
     * Sets the process to execute before the main process
     *
     * @param ProcessInterface|null $process
     *
     * @return static
     */
    public function setPreExecution(?ProcessInterface $process): static
    {
        $this->pre_exec = $process;

        return $this;
    }


    /**
     * Returns the process to execute after the main process
     *
     * @return ProcessInterface|null
     */
    public function getPostExecution(): ?ProcessInterface
    {
        return $this->post_exec;
    }


    /**
     * Sets the process to execute after the main process
     *
     * @param ProcessInterface|null $process
     *
     * @return static
     */
    public function setPostExecution(?ProcessInterface $process): static
    {
        $this->post_exec = $process;

        return $this;
    }


    /**
     * Returns the exact time that a process took to execute
     *
     * @param bool $require_stop
     *
     * @return float|null
     */
    public function getExecutionTime(bool $require_stop = true): ?float
    {
        return $this->getStopTime($require_stop) - $this->start;
    }


    /**
     * Returns the stop time for this process
     *
     * @param bool $require_stop
     *
     * @return float
     */
    protected function getStopTime(bool $require_stop = true): float
    {
        if (!$this->start) {
            throw new OutOfBoundsException(tr('Cannot measure execution time, the process has not yet started'));
        }

        if ($this->stop) {
            return $this->stop;
        }

        if ($require_stop) {
            throw new OutOfBoundsException(tr('Cannot measure execution time, the process is still running'));
        }

        return microtime(true);
    }


    /**
     * Returns the time spent on executing this process in a human-readable form
     *
     * @param bool $require_stop
     * @param int  $decimals
     *
     * @return string
     */
    public function getExecutionTimeHumanReadable(bool $require_stop = true, int $decimals = 5): string
    {
        return Time::difference($this->start, $this->getStopTime($require_stop), 'auto', $decimals);
    }


    /**
     * Returns the output of the process
     *
     * If requested before process execution, will return NULL
     *
     * @return array|null
     */
    public function getOutput(): array|null
    {
        return $this->output;
    }


    /**
     * Returns the output of the process
     *
     * If requested before process execution, will return NULL
     *
     * @param string $separator
     *
     * @return string|null
     */
    public function getStringOutput(string $separator = PHP_EOL): string|null
    {
        if ($this->output) {
            return implode($separator, $this->output);
        }

        return null;
    }


    /**
     * Returns if  the log files will be cleared after this object is destroyed or not
     *
     * @return bool
     */
    public function getClearLogs(): bool
    {
        return $this->clear_logs;
    }


    /**
     * Sets if  the log files will be cleared after this object is destroyed or not
     *
     * @param bool $clear_logs
     *
     * @return static
     */
    public function setClearLogs(bool $clear_logs): static
    {
        $this->clear_logs = $clear_logs;

        return $this;
    }


    /**
     * Returns the nice level for this process
     *
     * @return EnumIoNiceClass
     */
    public function getIoNiceClass(): EnumIoNiceClass
    {
        return $this->ionice_class;
    }


    /**
     * Sets the ionice class for this process
     *
     * @param EnumIoNiceClass|int|null $ionice_class
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setIoNiceClass(EnumIoNiceClass|int|null $ionice_class): static
    {
        if (is_null($ionice_class)) {
            $ionice_class = EnumIoNiceClass::none;

        } elseif (is_int($ionice_class)) {
            $ionice_class = EnumIoNiceClass::from($ionice_class);
        }

        $this->ionice_class = $ionice_class;

        return $this;
    }


    /**
     * Returns the nice level for this process
     *
     * @return int
     */
    public function getIoNiceLevel(): int
    {
        return $this->ionice_level;
    }


    /**
     * Sets the ionice level for this process
     *
     * @param int|null $ionice_level
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setIoNiceLevel(?int $ionice_level): static
    {
        if ($ionice_level) {
            switch ($this->ionice_class) {
                case EnumIoNiceClass::realtime:
                    // no break
                case EnumIoNiceClass::best_effort:
                    break;

                default:
                    throw new OutOfBoundsException(tr('Cannot set IO nice level ":level", the IO nice class ":class" is not one of "EnumIoNiceClass::realtime, EnumIoNiceClass::best_effort"', [
                        ':level' => $ionice_level,
                        ':class' => $this->ionice_class->value,
                    ]));
            }
        }

        $this->ionice_level = (int) $ionice_level;

        return $this;
    }


    /**
     * Returns the nice level for this process
     *
     * @return int
     */
    public function getNice(): int
    {
        return $this->nice;
    }


    /**
     * Sets the nice level for this process
     *
     * @param int|null $nice
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setNice(?int $nice): static
    {
        $this->nice = (int) $nice;

        return $this;
    }


    /**
     * Returns the nocache option for this process
     *
     * @return int|bool
     */
    public function getNoCache(): int|bool
    {
        return $this->nocache;
    }


    /**
     * Sets the nocache option for this process
     *
     * @param int|bool|null $nocache
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setNoCache(int|bool|null $nocache): static
    {
        $this->nocache = $nocache ?? false;

        return $this;
    }


    /**
     * Returns if this process registers pid information or not
     *
     * @return bool
     */
    public function getRegisterRunfile(): bool
    {
        return $this->register_run_file;
    }


    /**
     * Sets if this process registers pid information or not
     *
     * @param bool $register_run_file
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setRegisterRunfile(bool $register_run_file): static
    {
        $this->register_run_file = $register_run_file;

        return $this;
    }


    /**
     * Returns if the process will first CD to this directory before continuing
     *
     * @return FsDirectoryInterface
     */
    public function getExecutionDirectory(): FsDirectoryInterface
    {
        return $this->execution_directory;
    }


    /**
     * Sets if the process will first CD to this directory before continuing
     *
     * @param FsDirectoryInterface|null $execution_directory
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setExecutionDirectory(FsDirectoryInterface|null $execution_directory): static
    {
        $this->cached_command_line = null;
        $this->execution_directory = $execution_directory;
        $this->restrictions        = $execution_directory?->getRestrictions() ?? FsRestrictions::new();

        return $this;
    }


    /**
     * Sets the execution path to private temp dir
     *
     * @param bool $public
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setExecutionDirectoryToTemp(bool $public = false): static
    {
        return $this->setExecutionDirectory(FsDirectory::newTemporaryObject($public));
    }


    /**
     * Sets the log path where the process output will be redirected to
     *
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        if (Log::getFileEnabled()) {
            return $this->log_file;
        }

        return null;
    }


    /**
     * Returns the run directory where the process run files will be written
     *
     * @return string|null
     */
    public function getRunDirectory(): ?string
    {
        if ($this->use_run_file) {
            return static::$run_directory;
        }

        return null;
    }


    /**
     * Sets the run directory where process run files will be written
     *
     * @param string|null $run_directory
     *
     * @return static
     */
    protected function setRunDirectory(?string $run_directory): static
    {
        $identifier            = $this->setIdentifier();
        $this->log_file        = DIRECTORY_DATA . 'log/' . $identifier;
        static::$run_directory = $run_directory . $identifier . '/';

        if ($this->use_run_file) {
            // Make sure the run file directory exists
            FsDirectory::new(static::$run_directory, FsRestrictions::newSystem(true))->ensure();
        }

        return $this;
    }


    /**
     * Deletes the run directory for all subprocesses, if it exists
     *
     * @return void
     */
    public static function deleteRunDirectory(): void
    {
        if (static::$run_directory) {
            FsDirectory::new(static::$run_directory, FsRestrictions::newSystem(true))->delete(false, use_run_file: false);
        }
    }


    /**
     * Returns the run file path
     *
     * @return string|null
     */
    public function getRunFile(): ?string
    {
        if ($this->use_run_file and FsFile::getWriteEnabled()) {
            return $this->run_file;
        }

        return null;
    }


    /**
     * Sets the run path where the process run file will be written
     *
     * @return static
     */
    protected function setRunFile(): static
    {
        $this->setRunDirectory(DIRECTORY_SYSTEM . 'run/pids/');

        $this->cached_command_line = null;
        $this->run_file            = static::$run_directory ? static::$run_directory . basename($this->command) : null;

        return $this;
    }


    /**
     * Sets if a runfile will be used
     *
     * @return bool
     */
    public function getUseRunFile(): bool
    {
        return $this->use_run_file;
    }


    /**
     * Sets if a runfile should be used
     *
     * @param bool $use_run_file
     *
     * @return static This process so that multiple methods can be chained
     * @throws ProcessException
     */
    public function setUseRunFile(bool $use_run_file): static
    {
        $this->use_run_file = $use_run_file;

        return $this;
    }


// TODO Document why this was commented out
//    /**
//     * Returns the log file where the process output will be redirected to
//     *
//     * @param string $directory
//     * @return static This process so that multiple methods can be chained
//     */
//    public function setLogFile(string $directory): static
//    {
//        $this->cached_command_line = null;
//
//        if (!$directory) {
//            // Set the default log path
//            $directory = DIRECTORY_DATA . 'log/';
//        }
//
//        // Ensure the path ends with a slash and that it is writable
//        $directory = Strings::slash($directory);
//        $directory = FsFile::new($directory)->ensureWritable();
//        $this->log_file = $directory;
//
//        return $this;
//    }


    /**
     * Return the terminal to execute this command
     *
     * @return string|null
     */
    public function getTerm(): ?string
    {
        return $this->term;
    }


    /**
     * Sets the terminal to execute this command
     *
     * @param string|null $term
     * @param bool        $only_if_empty
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setTerm(?string $term = null, bool $only_if_empty = false): static
    {
        if (!$this->term or !$only_if_empty) {
            $this->cached_command_line = null;
            $this->term                = $term;
        }

        return $this;
    }


// TODO Document why this was commented out
//    /**
//     * Sets the run path where the process run file will be written
//     *
//     * @param string $directory
//     * @return static This process so that multiple methods can be chained
//     */
//    public function setRunFile(string $directory): static
//    {
//        $this->cached_command_line = null;
//
//        if (!$directory) {
//            // Set the default log path
//            $directory = DIRECTORY_SYSTEM . 'run/';
//        }
//
//        // Ensure the path ends with a slash and that it is writable
//        $directory = Strings::slash($directory);
//        $directory = FsFile::new($directory)->ensureWritable();
//        $this->run_file = $directory;
//
//        return $this;
//    }
    /**
     * Returns if the command should be executed as a different user using sudo.
     *
     * If this returns NULL, the command will not execute with sudo. If a string is returned, the command will execute
     * as that user.
     *
     * @return string|bool
     */
    public function getSudo(): string|bool
    {
        return $this->sudo;
    }


    /**
     * Sets if the command should be executed as a different user using sudo.
     *
     * If $sudo is NULL or FALSE, the command will not execute with sudo. If a string is specified, the command will
     * execute as that user. If TRUE is specified, the command will execute as root (This is basically just a shortcut)
     *
     * @param string|bool|null $sudo
     * @param string|null      $user
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setSudo(string|bool|null $sudo, ?string $user = null): static
    {
        $this->cached_command_line = null;

        if (!$sudo) {
            $this->sudo = false;

        } else {
            if ($sudo === true) {
                $sudo = 'sudo -Es';

                if ($user) {
                    // Sudo specifically to a non root user
                    $sudo .= 'u ' . escapeshellarg($user);
                }
            }

// TODO Validate that $sudo contains ONLY alphanumeric characters!
            $this->sudo = $sudo;
        }

        return $this;
    }


    /**
     * Returns the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @return array
     */
    public function getAcceptedExitCodes(): array
    {
        return $this->accepted_exit_codes;
    }


    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array|int|null $exit_codes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setAcceptedExitCodes(array|int|null $exit_codes): static
    {
        $this->cached_command_line = null;
        $this->accepted_exit_codes = [];

        return $this->addAcceptedExitCodes($exit_codes);
    }


    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array|int|null $exit_codes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addAcceptedExitCodes(array|int|null $exit_codes): static
    {
        if ($exit_codes) {
            foreach (Arrays::force($exit_codes) as $exit_code) {
                $this->addAcceptedExitCode($exit_code);
            }
        }

        return $this;
    }


    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param int $exit_code
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addAcceptedExitCode(int $exit_code): static
    {
        if (($exit_code < 0) or ($exit_code > 255)) {
            throw new OutOfBoundsException(tr('The specified $exit_code ":code" is invalid. Please specify a values between 0 and 255', [':code' => $exit_code]));
        }

        $this->accepted_exit_codes[] = $exit_code;

        return $this;
    }


    /**
     * Clears the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @return static This process so that multiple methods can be chained
     */
    public function clearAcceptedExitCodes(): static
    {
        $this->accepted_exit_codes = [];

        return $this;
    }


    /**
     * Returns the command to be executed for this process
     *
     * @return string|null
     */
    public function getCommand(): ?string
    {
        return $this->command;
    }


    /**
     * Set the command to be executed for this process
     *
     * @param string|null $command
     * @param bool        $which_command
     * @param bool        $clear_arguments
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setCommand(?string $command, bool $which_command = true, bool $clear_arguments = true): static
    {
        if ($command) {
            // Make sure we have a clean command
            $command = trim($command);
        }

        $real_command              = $command;
        $this->cached_command_line = null;

        if (!$command) {
            // Reset the command
            $this->command      = null;
            $this->real_command = null;

            return $this;
        }

        if ($which_command) {
            // Get the real location for the command to ensure it exists. Do NOT use this for shell internal commands!
            try {
                $real_command = Which::new($this->execution_directory)->which($command);

            } catch (CommandNotFoundException) {
                // Check if the command exist on disk
                if (($command !== 'which') and !file_exists($command)) {
                    // The specified command was not found, we'll have to look for it anyway!
                    try {
                        $real_command = Which::new($this->execution_directory)->which($command);

                    } catch (CommandsException) {
                        // The command does not exist, but maybe we can auto install?
                        if (!$this->failed) {
                            if ($this->packages?->keyExists($command) and !in_array($command, $this->packages)) {
                                throw new ProcessesException(tr('Specified command ":command" does not exist, and auto install is denied by the package filter list', [
                                    ':command' => $command,
                                ]));
                            }

                            if (!Command::checkSudoAvailable('apt-get', FsRestrictions::new('/bin,/usr/bin,/sbin,/usr/sbin'))) {
                                throw new ProcessesException(tr('Specified command ":command" does not exist and this process does not have sudo access to apt-get', [
                                    ':command' => $command,
                                ]));
                            }
                        }

                        $this->failed = true;

                        throw new CommandNotFoundException(tr('Specified command ":command" does not exist', [
                            ':command' => $command,
                        ]));

                        // Proceed to install the packages and retry
                        Log::warning(tr('Failed to find the command ":command", installing required packages', [
                            ':command' => $command,
                        ]));

// TODO Implement this! Have apt-file actually search for the command, match /s?bin/COMMAND or /usr/s?bin/COMMAND
//                    AptGet::new()->install($this->packages);
//                    return $this->setInternalCommand($command, $which_command);
                    }
                }
            }
        }

        // Apply proper escaping and register the command
        $this->command      = escapeshellcmd($command);
        $this->real_command = escapeshellcmd($real_command);

        if ($clear_arguments) {
            $this->clearArguments();
        }

        return $this;
    }


    /**
     * Return the process identifier
     *
     * @return string
     * @throws ProcessException
     */
    public function getIdentifier(): string
    {
        if (!$this->command) {
            throw new ProcessException(tr('Cannot generate process identifier, no command has been specified yet'));
        }

        return getmypid() . '-' . Core::getLocalId();
    }


    /**
     * Sets the process identifier
     *
     * @return string
     * @throws ProcessException
     */
    protected function setIdentifier(): string
    {
        $identifier                = $this->getIdentifier();
        $this->log_file            = DIRECTORY_DATA . 'log/' . $identifier;
        $this->cached_command_line = null;

        Log::notice(tr('Set process identifier ":identifier"', [':identifier' => $identifier]), 2);

        return $identifier;
    }


    /**
     * Clears all cache and arguments
     *
     * @return static This process so that multiple methods can be chained
     */
    public function clearArguments(): static
    {
        $this->cached_command_line = null;
        $this->arguments           = [];

        return $this;
    }


    /**
     * Returns the arguments for the command that will be executed
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }


    /**
     * Sets the arguments for the command that will be executed
     *
     * @note This will reset the currently existing list of arguments.
     *
     * @param array|null $arguments
     * @param bool       $escape_arguments
     * @param bool       $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setArguments(?array $arguments, bool $escape_arguments = true, bool $escape_quotes = true): static
    {
        $this->arguments = [];

        return $this->addArguments($arguments, $escape_arguments, $escape_quotes);
    }


    /**
     * Adds multiple arguments to the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|int|float|null $arguments
     * @param bool                                   $escape_arguments
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addArguments(Stringable|array|string|int|float|null $arguments, bool $escape_arguments = true, bool $escape_quotes = true): static
    {
        $this->cached_command_line = null;

        if ($arguments) {
            if (is_array($arguments)) {
                foreach (Arrays::force($arguments, null) as $argument) {
                    if (!$argument) {
                        if ($argument !== 0) {
                            // Ignore empty arguments
                            continue;
                        }
                    }

                    // Add multiple arguments
                    $this->addArguments($argument, $escape_arguments, $escape_quotes);
                }

            } else {
                // Add a single argument
                $this->addArgument($arguments, $escape_arguments, $escape_quotes);
            }
        }

        return $this;
    }


    /**
     * Adds multiple arguments to the beginning of the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|int|float|null $arguments
     * @param bool                                   $escape_arguments
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function prependArguments(Stringable|array|string|int|float|null $arguments, bool $escape_arguments = true, bool $escape_quotes = true): static
    {
        $this->cached_command_line = null;

        if ($arguments) {
            if (is_array($arguments)) {
                // Since we are prepending, reverse the array!
                $arguments = array_reverse($arguments);

                foreach ($arguments as $argument) {
                    if (!$argument) {
                        if ($argument !== 0) {
                            // Ignore empty arguments
                            continue;
                        }
                    }

                    // Add multiple arguments
                    $this->prependArguments($argument, $escape_arguments, $escape_quotes);
                }

            } else {
                // Add a single argument
                $this->prependArgument($arguments, $escape_arguments, $escape_quotes);
            }
        }

        return $this;
    }


    /**
     * Adds an argument to the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|float|int|null $argument
     * @param bool                                   $escape_argument
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addArgument(Stringable|array|string|float|int|null $argument, bool $escape_argument = true, bool $escape_quotes = true): static
    {
        if ($argument !== null) {
            if (is_array($argument)) {
                return $this->addArguments($argument, $escape_argument, $escape_quotes);
            }

            $this->cached_command_line = null;
            $this->arguments[]         = [
                'escape_argument' => $escape_argument,
                'escape_quotes'   => $escape_quotes,
                'argument'        => (string) $argument,
            ];
        }

        return $this;
    }


    /**
     * Adds an argument to the beginning of the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|float|int|null $argument
     * @param bool                                   $escape_argument
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function prependArgument(Stringable|array|string|float|int|null $argument, bool $escape_argument = true, bool $escape_quotes = true): static
    {
        if ($argument !== null) {
            if (is_array($argument)) {
                return $this->prependArguments($argument, $escape_argument, $escape_quotes);
            }

            $this->cached_command_line = null;

            array_unshift($this->arguments,  [
                'escape_argument' => $escape_argument,
                'escape_quotes'   => $escape_quotes,
                'argument'        => (string) $argument,
            ]);
        }

        return $this;
    }


    /**
     * Sets a single argument for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     *
     * @param string|null $argument
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setArgument(?string $argument): static
    {
        return $this->setArguments([$argument]);
    }


    /**
     * Returns the environment_variables for the command that will be executed
     *
     * @return array
     */
    public function getEnvironmentVariables(): array
    {
        return $this->environment_variables;
    }


    /**
     * Sets the environment_variables for the command that will be executed
     *
     * @note This will reset the currently existing list of environment_variables.
     *
     * @param array|string|null $environment_variables
     * @param bool              $escape
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setEnvironmentVariables(array|string|null $environment_variables, bool $escape = true): static
    {
        $this->environment_variables = [];

        return $this->addEnvironmentVariables($environment_variables, $escape);
    }


    /**
     * Clears all cache and environment_variables
     *
     * @return static This process so that multiple methods can be chained
     */
    public function clearEnvironmentVariables(): static
    {
        $this->cached_command_line   = null;
        $this->environment_variables = [];

        return $this;
    }


    /**
     * Adds multiple environment_variables to the existing list of environment_variables for the command that will be
     * executed
     *
     * @param array|string|null $environment_variables
     * @param bool              $escape
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addEnvironmentVariables(array|string|null $environment_variables, bool $escape = true): static
    {
        $this->cached_command_line = null;
        if ($environment_variables) {
            foreach (Arrays::force($environment_variables, null) as $key => $value) {
                $this->addEnvironmentVariable($value, $key, $escape);
            }
        }

        return $this;
    }


    /**
     * Adds an environment_variable to the existing list of environment_variables for the command that will be executed
     *
     * @note All environment_variables will be automatically escaped, but variable environment_variables
     *       ($variablename$) will NOT be escaped!
     *
     * @param Stringable|string|null $value
     * @param Stringable|string|null $key
     * @param bool                   $escape
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addEnvironmentVariable(Stringable|string|null $value, Stringable|string|null $key, bool $escape = true): static
    {
        $key   = (string) $key;
        $value = (string) $value;

        // Do not escape variables!
        if (!preg_match('/^\$.+?\$$/', $key) and $escape) {
            $key = escapeshellarg($key);
        }

        // Do not escape variables!
        if (!preg_match('/^\$.+?\$$/', $value) and $escape) {
            $value = escapeshellarg($value);
        }

        $this->cached_command_line         = null;
        $this->environment_variables[$key] = $value;

        return $this;
    }


    /**
     * Sets a single argument for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     *
     * @param Stringable|string|null $value
     * @param Stringable|string|null $key
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setEnvironmentVariable(Stringable|string|null $value, Stringable|string|null $key): static
    {
        return $this->setEnvironmentVariables([$key => $value]);
    }


    /**
     * Returns the Variables for the command that will be executed
     *
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }


    /**
     * Sets the variables for the command that will be executed
     *
     * @note This will reset the currently existing list of variables.
     *
     * @param array|null $variables
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setVariables(?array $variables): static
    {
        $this->variables = [];

        if ($variables) {
            foreach ($variables as $key => $value) {
                return $this->setVariable($key, $value);
            }
        }

        return $this;
    }


    /**
     * Adds a variable to the existing list of Variables for the command that will be executed
     *
     * @param string           $key
     * @param string|float|int $value
     *
     * @return static
     */
    public function setVariable(string $key, string|float|int $value): static
    {
        $this->cached_command_line = null;
        $this->variables[$key]     = $value;

        return $this;
    }


    /**
     * Returns the process command line for the pipe
     *
     * @return Process|null
     */
    protected function getPipeCommandLine(): ?string
    {
        if (empty($this->pipe)) {
            return null;
        }

        if (is_string($this->pipe)) {
            return str_replace('"', '\\"', $this->pipe);
        }

        return str_replace('"', '\\"', $this->pipe->getFullCommandLine());
    }


    /**
     * Returns the process command line for the pipe
     *
     * @return Process|null
     */
    protected function getPipeIntoCommandLine(): ?string
    {
        if (empty($this->pipe_from)) {
            return null;
        }

        if (is_string($this->pipe_from)) {
            return 'echo -n ' . escapeshellarg($this->pipe_from);
        }

        if ($this->pipe_from instanceof FsFileInterface) {
            return 'cat ' . escapeshellarg($this->pipe_from);
        }

        return $this->pipe_from->getFullCommandLine();
    }


    /**
     * Returns the process where the output of this command will be piped to, IF specified
     *
     * @return ProcessInterface|FsFileInterface|string|null
     */
    public function getPipe(): ProcessInterface|FsFileInterface|string|null
    {
        return $this->pipe;
    }


    /**
     * Sets the process where the output of this command will be piped to, IF specified
     *
     * @param ProcessInterface|FsFileInterface|string|null $pipe
     *
     * @return static
     */
    public function setPipe(ProcessInterface|FsFileInterface|string|null $pipe): static
    {
        $this->cached_command_line = null;
        $this->pipe                = $pipe;

        if (is_object($pipe)) {
            $this->pipe->increaseQuoteEscapes();
            $this->pipe->setTerm();
        }

        return $this;
    }


    /**
     * Returns the process or string that will be piped into this process
     *
     * @return ProcessInterface|FsFileInterface|string|null
     */
    public function getPipeFrom(): ProcessInterface|FsFileInterface|string|null
    {
        return $this->pipe_from;
    }


    /**
     * Sets the process or string that will be piped into this process*
     *
     * @param ProcessInterface|FsFileInterface|string|null $pipe
     *
     * @return static
     */
    public function setPipeFrom(ProcessInterface|FsFileInterface|string|null $pipe): static
    {
        $this->cached_command_line = null;
        $this->pipe_from           = $pipe;

        if (is_object($pipe)) {
            $pipe->setPipe($this);
        }

        return $this;
    }


    /**
     * Increases the number of times quotes should be escaped
     *
     * @return ProcessVariables
     */
    public function increaseQuoteEscapes(): static
    {
        $this->escape_quotes++;

        return $this;
    }


    /**
     * Returns the output redirection for the specified channel this process
     *
     * @return array|null
     */
    public function getOutputRedirect(int $channel): ?string
    {
        return $this->output_redirect[$channel];
    }


    /**
     * Sets the output redirection for this process
     *
     * @param string|null $redirect
     * @param int         $channel
     * @param bool        $append
     *
     * @return static
     */
    public function setOutputRedirect(?string $redirect, int $channel = 1, bool $append = false): static
    {
        $this->validateStream($channel, 'output');

        if ($redirect) {
            if ($redirect[0] === '&') {
                // Redirect output to another channel
                if (strlen($redirect) !== 2) {
                    throw new OutOfBoundsException(tr('Specified redirect ":redirect" is invalid. When redirecting to another channel, always specify &N where N is 0-9', [
                        ':redirect' => $redirect,
                    ]));
                }

            } else {
                // Redirect output to a file
                FsDirectory::new(dirname($redirect), $this->restrictions->getParent())
                         ->ensure('output redirect file');
                $this->output_redirect[$channel] = ($append ? '>>' : '> ') . $redirect;
            }

        } else {
            unset($this->output_redirect[$channel]);
        }

        $this->cached_command_line = null;

        return $this;
    }


    /**
     * Validates the specified stream
     *
     * @param int    $stream
     * @param string $type
     *
     * @return void
     * @todo Implement
     */
    protected function validateStream(int $stream, string $type): void
    {
        if (($stream < 1) or ($stream > 10)) {
            throw new OutOfBoundsException(tr('Invalid ":type" stream ":stream" specified', [
                ':type'   => $type,
                ':stream' => $stream,
            ]));
        }
    }


    /**
     * Returns all the output redirections for this process
     *
     * @return array
     */
    public function getOutputRedirects(): array
    {
        return $this->output_redirect;
    }


    /**
     * Returns the input redirection for the specified channel this process
     *
     * @return array|null
     */
    public function getInputRedirect(int $channel): ?string
    {
        return $this->input_redirect[$channel];
    }


    /**
     * Sets the input redirection for this process
     *
     * @param Stringable|string|null $redirect
     * @param int                    $channel
     *
     * @return static
     */
    public function setInputRedirect(Stringable|string|null $redirect, int $channel = 1): static
    {
        $redirect                  = get_null($redirect);
        $this->cached_command_line = null;

        if ($redirect) {
            FsFile::new($redirect, $this->restrictions)->checkReadable();
            $this->input_redirect[$channel] = $redirect;
        }

        return $this;
    }


    /**
     * Returns all the input redirections for this process
     *
     * @return array
     */
    public function getInputRedirects(): array
    {
        return $this->input_redirect;
    }


    /**
     * Returns the time in milliseconds that a process will wait before executing
     *
     * Defaults to 0, the process will NOT wait and start immediately
     *
     * @return int
     */
    public function getWait(): int
    {
        return $this->wait;
    }


    /**
     * Sets the time in milliseconds that a process will wait before executing
     *
     * Defaults to 0, the process will NOT wait and start immediately
     *
     * @param int|null $wait
     *
     * @return static
     */
    public function setWait(?int $wait): static
    {
        if (!is_natural($wait, 0)) {
            if ($wait) {
                throw new OutOfBoundsException(tr('The specified wait time ":wait" is invalid, it must be a natural number 0 or higher', [
                    ':wait' => $wait,
                ]));
            }

            $wait = 0;
        }

        $this->cached_command_line = null;
        $this->wait                = $wait;

        return $this;
    }


    /**
     * Returns the time in milliseconds that a process will signal before executing
     *
     * Defaults to 0, the process will NOT signal and start immediately
     *
     * @return int
     */
    public function getSignal(): int
    {
        return $this->signal;
    }


    /**
     * Sets the time in milliseconds that a process will signal before executing
     *
     * Defaults to 0, the process will NOT signal and start immediately
     *
     * @param int $signal
     *
     * @return static
     */
    public function setSignal(int $signal): static
    {
        if (!is_natural($signal, 1)) {
            throw new OutOfBoundsException(tr('The specified signal time ":signal" is invalid, it must be a natural number 0 or higher', [
                ':signal' => $signal,
            ]));
        }

        $this->cached_command_line = null;
        $this->signal              = Signals::check($signal);

        return $this;
    }


    /**
     * Returns the packages that should be installed automatically if the command for this process cannot be found
     *
     * @return PackagesInterface
     */
    public function getPackages(): PackagesInterface
    {
        return $this->packages;
    }


    /**
     * Sets the packages that should be installed automatically if the command for this process cannot be found
     *
     * @param Stringable|string              $operating_system
     * @param IteratorInterface|array|string $packages
     *
     * @return static
     */
    public function setPackages(Stringable|string $operating_system, IteratorInterface|array|string $packages): static
    {
        $this->cached_command_line = null;
        $this->packages->addForOperatingSystem($operating_system, $packages);

        return $this;
    }


    /**
     * Returns the timeout value for this process.
     *
     * If the process requires more time than the specified timeout value, it will be terminated automatically. Set to
     * 0 seconds  to disable, defaults to 30 seconds
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }


    /**
     * Sets the timeout value for this process.
     *
     * If the process requires more time than the specified timeout value, it will be terminated automatically. Set to
     * 0 seconds  to disable, defaults to 30 seconds
     *
     * @param int|null $timeout
     *
     * @return static
     */
    public function setTimeout(?int $timeout): static
    {
        if (!is_natural($timeout, 0)) {
            if ($timeout) {
                throw new OutOfBoundsException(tr('The specified timeout ":timeout" is invalid, it must be a natural number 0 or higher', [
                    ':timeout' => $timeout,
                ]));
            }

            $timeout = 0;
        }

        $this->cached_command_line = null;
        $this->timeout             = $timeout;

        return $this;
    }


    /**
     * Returns the pid value for this process when it is running in the background.
     *
     * @note Will return NULL if the process is not running in the background.
     *
     * @return ?int
     */
    public function getPid(): ?int
    {
        return $this->pid;
    }


    /**
     * Sets the process PID file from the run_file and remove the file, or from specified output if command was run in
     * background
     *
     * @param string|array|null $output
     *
     * @return void
     */
    protected function setPid(string|array|null $output = null): void
    {
        if (!$this->register_run_file) {
            // Don't register PID information
            return;
        }

        // Get the PID and remove the run file
        if ($this->use_run_file) {
            // Get PID info from run_file
            if (!$this->run_file) {
                throw new ProcessException(tr('Failed to set process PID, no PID specified and run_file has not been set'));
            }

            $file = $this->run_file;
            $pid  = file_get_contents($file);
            $pid  = trim($pid);

            if (!$pid) {
                throw new ProcessException(tr('Run file ":file" for command ":command" was empty', [
                    ':file'    => $file,
                    ':command' => $this->command
                ]));
            }

            if (!is_numeric($pid)) {
                throw new ProcessException(tr('Run file ":file" for command ":command" contains invalid data ":data"', [
                    ':file'    => $file,
                    ':data'    => $pid,
                    ':command' => $this->command
                ]));
            }

            // Delete the run file but don't clean up as when the process terminates, cleanup will happen automatically
            FsFile::new($this->run_file, FsRestrictions::new(DIRECTORY_SYSTEM . 'run/pids/', true))
                  ->delete(false, use_run_file: false);

        } elseif ($this->wasExecutedInBackground()) {
            // The command was executed in the background, PID was returned as output
            $pid = Strings::force($output);

            if (empty($pid)) {
                throw new ProcessException(tr('Background executed command ":command" output returned no PID', [
                    ':command' => $this->command
                ]));
            }

            if (!is_numeric($pid)) {
                throw new ProcessException(tr('Background executed command ":command" output returned invalid PID ":pid"', [
                    ':command' => $this->command,
                    ':pid'     => $pid
                ]));
            }

        } else {
            // Run files were disabled for normal execution. We can't know the PID
            $pid = -1;
        }

        $this->run_file = null;
        $this->pid      = (int) $pid;
    }


    /**
     * Returns if debug is enabled or not
     *
     * @return bool
     */
    public function getDebug(): bool
    {
        return $this->debug;
    }


    /**
     * Sets debug mode on or off
     *
     * @param bool $debug
     *
     * @return static
     */
    public function setDebug(bool $debug): static
    {
        $this->debug = $debug;

        return $this;
    }


    /**
     * Sets the execution method for this process
     *
     * @note Will set the execution method only once. When already set, it will ignore the next specified method
     *
     * @param EnumExecuteMethod|null $method
     *
     * @return static
     */
    protected function setExecutionMethod(?EnumExecuteMethod $method): static
    {
        if ($this->method) {
            return $this;
        }

        $this->executed_on = DateTime::new();
        $this->method      = $method;
        return $this;
    }


    /**
     * Returns the datetime when this command executed, or null if it has not yet executed
     *
     * @return DateTimeInterface|null
     */
    public function getExecutedOn(): ?DateTimeInterface
    {
        return $this->executed_on;
    }


    /**
     * Returns true if this command has executed
     *
     * @return bool
     */
    public function hasBeenExecuted(): bool
    {
        return (bool) $this->executed_on;
    }


    /**
     * Returns true if this command was executed in the background
     *
     * @return bool
     */
    public function wasExecutedInBackground(): bool
    {
        return $this->method === EnumExecuteMethod::background;
    }
}
