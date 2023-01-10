<?php

namespace Phoundation\Processes;

use Phoundation\Core\Arrays;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Command;
use Phoundation\Processes\Commands\Exception\CommandsException;
use Phoundation\Processes\Commands\SystemCommands;
use Phoundation\Processes\Exception\ProcessesException;
use Phoundation\Processes\Exception\ProcessException;
use Phoundation\Servers\Server;



/**
 * Trait ProcessVariables
 *
 * Manages all process variables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
trait ProcessVariables
{
    /**
     * The command that will be executed for this process
     *
     * @var string|null $command
     */
    protected ?string $command = null;

    /**
     * The command that will be executed for this process
     *
     * @var bool $delayed
     */
    protected bool $delayed = false;

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
     * The log file where command output will be written to
     *
     * @var string|null
     */
    protected ?string $log_file = null;

    /**
     * The run path where command output will be written to
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
     * The maximum amount of time in seconds that a command is allowed to run before it will timeout. Zero to disable,
     * defaults to 30
     *
     * @var int $timeout
     */
    protected int $timeout = 30;

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
     * the command should be exeuted
     *
     * @var string|null $sudo
     */
    protected ?string $sudo = null;

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
     * @var Process|null $pipe
     */
    protected ?Process $pipe = null;

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
    protected array $output_redirect = [2 => '&1'];

    /**
     * Keeps track on which server this command should be executed. NULL means this local server
     *
     * @var Server $server_restrictions
     */
    protected Server $server_restrictions;

    /**
     * Registers where the exit code for this process will be stored
     *
     * @var bool $register_run_file
     */
    protected bool $register_run_file = true;

    /**
     * Variable data that can modify the process command that will be executed
     *
     * @var array|null $variables
     */
    protected ?array $variables = [];

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
     * @var array|string|null
     */
    protected array|string|null $packages = null;

    /**
     * State variable, tracks if the current process has failed or not
     *
     * @var bool $failed
     */
    protected bool $failed = false;



    /**
     * Process class contructor
     *
     * @param Server|Restrictions|array|string|null $server_restrictions
     */
    public function __construct(Server|Restrictions|array|string|null $server_restrictions)
    {
        // Set server filesystem restrictions
        $this->setServerRestrictions($server_restrictions);
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
     * @return static
     */
    public function setClearLogs(bool $clear_logs): static
    {
        $this->clear_logs = $clear_logs;
        return $this;
    }



    /**
     * Returns if this process will register pid information or not
     *
     * @return bool
     */
    public function getRegisterRunfile(): bool
    {
        return $this->register_run_file;
    }



    /**
     * Sets if this process will register pid information or not
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setRegisterRunfile(bool $register_run_file): static
    {
        $this->register_run_file = $register_run_file;
        return $this;
    }



    /**
     * Sets the log path where the process output will be redirected to
     *
     * @return string
     */
    public function getLogFile(): string
    {
        return $this->log_file;
    }



// TODO Document why this was commented out
//    /**
//     * Returns the log file where the process output will be redirected to
//     *
//     * @param string $path
//     * @return static This process so that multiple methods can be chained
//     */
//    public function setLogFile(string $path): static
//    {
//        $this->cached_command_line = null;
//
//        if (!$path) {
//            // Set the default log path
//            $path = PATH_ROOT . 'data/log/';
//        }
//
//        // Ensure the path ends with a slash and that it is writable
//        $path = Strings::slash($path);
//        $path = File::new($path)->ensureWritable();
//        $this->log_file = $path;
//
//        return $this;
//    }



    /**
     * Returns the run path where the process run file will be written
     *
     * @return string
     */
    public function getRunFile(): string
    {
        return $this->run_file;
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

        return getmypid() . '-' . Strings::fromReverse($this->command, '/');
    }



    /**
     * Sets the process identifier
     *
     * @return static This process so that multiple methods can be chained
     * @throws ProcessException
     */
    protected function setIdentifier(): static
    {
        $identifier = $this->getIdentifier();

        $this->log_file = PATH_ROOT . 'data/log/' . $identifier;
        $this->run_file = PATH_ROOT . 'data/run/' . $identifier;

        Log::notice(tr('Set identifier ":identifier"', [':identifier' => $identifier]), 2);

        return $this;
    }



    /**
     * Sets the run path where the process run file will be written
     *
     * @return static
     */
    protected function setRunFile(): static
    {
        $this->run_file = PATH_ROOT . 'data/run/' . $this->getIdentifier();
        return $this;
    }



// TODO Document why this was commented out
//    /**
//     * Sets the run path where the process run file will be written
//     *
//     * @param string $path
//     * @return static This process so that multiple methods can be chained
//     */
//    public function setRunFile(string $path): static
//    {
//        $this->cached_command_line = null;
//
//        if (!$path) {
//            // Set the default log path
//            $path = PATH_ROOT . 'data/run/';
//        }
//
//        // Ensure the path ends with a slash and that it is writable
//        $path = Strings::slash($path);
//        $path = File::new($path)->ensureWritable();
//        $this->run_file = $path;
//
//        return $this;
//    }



    /**
     * Sets the terminal to execute this command
     *
     * @param string|null $term
     * @return static This process so that multiple methods can be chained
     */
    public function setTerm(string $term = null, bool $only_if_empty = false): static
    {
        if (!$this->term or !$only_if_empty) {
            $this->cached_command_line = null;
            $this->term = $term;
        }

        return $this;
    }



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
     * Returns if the command should be executed as a different user using sudo.
     *
     * If this returns NULL, the command will not execute with sudo. If a string is returned, the command will execute
     * as that user.
     *
     * @return ?string
     */
    public function getSudo(): ?string
    {
        return $this->sudo;
    }



    /**
     * Sets if the command should be executed as a different user using sudo.
     *
     * If $sudo is NULL or FALSE, the command will not execute with sudo. If a string is specified, the command will
     * execute as that user. If TRUE is specified, the command will execute as root (This is basically just a shortcut)
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setSudo(bool|string $sudo): static
    {
        $this->cached_command_line = null;

        if (!$sudo) {
            $this->sudo = null;

        } else {
            if ($sudo === true) {
                $sudo = 'root';
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
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array $exit_codes
     * @return static This process so that multiple methods can be chained
     */
    public function setAcceptedExitCodes(array $exit_codes): static
    {
        $this->accepted_exit_codes = [];
        return $this->addAcceptedExitCodes($exit_codes);
    }



    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array $exit_codes
     * @return static This process so that multiple methods can be chained
     */
    public function addAcceptedExitCodes(array $exit_codes): static
    {
        foreach ($exit_codes as $exit_code) {
            $this->addAcceptedExitCode($exit_code);
        }

        return $this;
    }



    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param int $exit_code
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
     * Returns the server on which the command should be executed for this process
     *
     * @note NULL means this local server
     * @return Server
     */
    public function getServerRestrictions(): Server
    {
        return $this->server_restrictions;
    }



    /**
     * Set the server on which the command should be executed for this process
     *
     * @note NULL means this local server
     * @param Server|Restrictions|array|string|null $server_restrictions
     * @return static
     */
    public function setServerRestrictions(Server|Restrictions|array|string|null $server_restrictions = null): static
    {
        $this->server_restrictions = Core::ensureServer($server_restrictions);
        return $this;
    }



    /**
     * Set the command to be executed for this process
     *
     * @param string|null $command
     * @param bool $which_command
     * @return static This process so that multiple methods can be chained
     */
    public function setCommand(?string $command, bool $which_command = false): static
    {
        if ($command) {
            // Make sure we have a clean command
            $command = trim($command);
        }

        $real_command = $command;
        $this->cached_command_line = null;

        if (!$command) {
            // Reset the command
            $this->command      = null;
            $this->real_command = null;

            return $this;
        }

        if ($which_command) {
            $real_command = SystemCommands::new($this->server_restrictions)->which($command);
        } else {
            // Check if the command exist on disk
            if (($command !== 'which') and !file_exists($command)) {
                // The specified command was not found, we'll have to look for it anyway!
                try {
                    $real_command = SystemCommands::new($this->server_restrictions)->which($command);
                } catch (CommandsException) {
                    // The command does not exist, but we have installation packages available!
                    if (!$this->failed and $this->packages and !Command::new()->sudoAvailable('apt-get')) {
                        throw new ProcessesException(tr('Specified process command ":command" does not exist', [
                            ':command' => $command
                        ]));
                    }

                    $this->failed = true;

                    // Proceed to install the packages and retry
                    Log::warning(tr('Failed to find the command ":command", installing required packages', [
                        ':command' => $command
                    ]));

                    SystemCommands::new()->aptGetInstall($this->packages);
                    return $this->setCommand($command, $which_command);
                }
            }
        }

        // Apply proper escaping and register the command
        $this->command      = escapeshellcmd($command);
        $this->real_command = escapeshellcmd($real_command);

        $this->setIdentifier();

        return $this;
    }



    /**
     * Returns the command to be executed for this process
     *
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
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
     * Clears the arguments for the command that will be executed
     *
     * @return static This process so that multiple methods can be chained
     */
    public function clearArguments(): static
    {
        $this->arguments = [];
        return $this;
    }



    /**
     * Sets the arguments for the command that will be executed
     *
     * @note This will reset the currently existing list of arguments.
     * @param array $arguments
     * @return static This process so that multiple methods can be chained
     */
    public function setArguments(array $arguments): static
    {
        $this->arguments = [];
        return $this->addArguments($arguments);
    }



    /**
     * Adds multiple arguments to the existing list of arguments for the command that will be executed
     *
     * @param array|string $arguments
     * @return static This process so that multiple methods can be chained
     */
    public function addArguments(array|string $arguments): static
    {
        $this->cached_command_line = null;

        foreach (Arrays::force($arguments, null) as $argument) {
            if (!$argument) {
                if ($argument !== 0) {
                    // Ignore empty arguments
                    continue;
                }
            }

            $this->addArgument($argument);
        }

        return $this;
    }



    /**
     * Adds an argument to the existing list of arguments for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     * @param array|string $argument
     * @return static This process so that multiple methods can be chained
     */
    public function addArgument(array|string $argument): static
    {
        if (is_array($argument)) {
            return $this->addArguments($argument);
        }

        // Do not escape variables!
        if (!preg_match('/^\$.+?\$$/', $argument)) {
            $arguments = escapeshellarg($argument);
        }

        $this->arguments[] = $argument;

        return $this;
    }



    /**
     * Sets a single argument for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     * @param string $argument
     * @return static This process so that multiple methods can be chained
     */
    public function setArgument(string $argument): static
    {
        return $this->setArguments([$argument]);
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
     * @param array $variables
     * @return static This process so that multiple methods can be chained
     */
    public function setVariables(array $variables): static
    {
        $this->variables = [];

        foreach ($variables as $key => $value) {
            return $this->setVariable($key, $value);
        }

        return $this;
    }



    /**
     * Adds a variable to the existing list of Variables for the command that will be executed
     *
     * @param string $key
     * @param string $value
     * @return ProcessVariables This process so that multiple methods can be chained
     */
    public function setVariable(string $key, string $value): static
    {
        $this->cached_command_line = null;
        $this->variables[$key] = $value;
        return $this;
    }



    /**
     * Sets the process where the output of this command will be piped to, IF specified
     *
     * @param Process|null $pipe
     * @return static
     */
    public function setPipe(?Process $pipe): static
    {
        $this->pipe = $pipe;
        $this->pipe->setTerm();

        return $this;
    }



    /**
     * Returns the process where the output of this command will be piped to, IF specified
     *
     * @return Process|null
     */
    public function getPipe(): ?Process
    {
        return $this->pipe;
    }



    /**
     * Sets the output redirection for this process
     *
     * @param string|null $redirect
     * @param int $channel
     * @param bool $append
     * @return static
     */
    public function setOutputRedirect(?string $redirect, int $channel = 1, bool $append = false): static
    {
        if ($redirect) {
            if ($redirect[0] === '&') {
                // Redirect output to other channel
                if (strlen($redirect) !== 2) {
                    throw new OutOfBoundsException('Specified redirect ":redirect" is invalid. When redirecting to another channel, always specify &N where N is 0-9', [
                        ':redirect' => $redirect
                    ]);
                }
            } else {
                // Redirect output to a file
                File::new($redirect, $this->server_restrictions)->checkWritable('output redirect file', true);
                $this->output_redirect[$channel] = ($append ? '*' : '') . $redirect;
            }

        } else {
            $this->output_redirect[$channel] = null;
        }

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
     * Returns all the output redirections for this process
     *
     * @return array
     */
    public function getOutputRedirects(): array
    {
        return $this->output_redirect;
    }



    /**
     * Sets the input redirection for this process
     *
     * @param string|null $redirect
     * @param int $channel
     * @return static
     */
    public function setInputRedirect(?string $redirect, int $channel = 1): static
    {
        File::new($redirect, $this->server_restrictions)->checkReadable();

        $this->input_redirect[$channel] = get_null($redirect);

        return $this;
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
     * Returns all the input redirections for this process
     *
     * @return array
     */
    public function getInputRedirects(): array
    {
        return $this->input_redirect;
    }


    /**
     * Sets the timeout value for this process.
     *
     * If the process requires more time than the specified timeout value, it will be terminated automatically. Set to
     * 0 seconds  to disable, defaults to 30 seconds
     *
     * @param int $timeout
     * @return static
     */
    public function setTimeout(int $timeout): static
    {
        if (!is_natural($timeout,  0)) {
            throw new OutOfBoundsException(tr('The specified timeout ":timeout" is invalid, it must be a natural number 0 or higher', [':timeout' => $timeout]));
        }

        $this->cached_command_line = null;
        $this->timeout = $timeout;

        return $this;
    }



    /**
     * Returns the time in seconds that a process will wait before executing
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
     * Sets the time in seconds that a process will wait before executing
     *
     * Defaults to 0, the process will NOT wait and start immediately
     *
     * @param int $wait
     * @return static
     */
    public function setWait(int $wait): static
    {
        if (!is_natural($wait,  0)) {
            throw new OutOfBoundsException(tr('The specified wait time ":wait" is invalid, it must be a natural number 0 or higher', [':wait' => $wait]));
        }

        $this->cached_command_line = null;
        $this->wait = $wait;

        return $this;
    }



    /**
     * Sets the packages that should be installed automatically if the command for this process cannot be found
     *
     * @return string|array
     */
    public function getPackages(): string|array
    {
        return $this->packages;
    }



    /**
     * Sets the packages that should be installed automatically if the command for this process cannot be found
     *
     * @param string|array $packages
     * @return static
     */
    public function setPackages(string|array $packages): static
    {
        $this->packages = $packages;
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
     * Get the process PID file from the run_file and remove the file
     *
     * @return void
     */
    public function setPid(): void
    {
        if (!$this->register_run_file) {
            // Don't register PID information
            return;
        }

        // Get PID info from run_file
        if (!$this->run_file) {
            throw new ProcessException(tr('Failed to set process PID, no PID specified and run_file has not been set'));
        }

        // Get the PID and remove the run file
        $file = $this->run_file;
        $pid  = file_get_contents($file);
        $pid  = trim($pid);

        unlink($this->run_file);
        $this->run_file = null;

        if (!$pid) {
            throw new ProcessException(tr('Run file ":file" was empty', [':file' => $file]));
        }

        if (!is_numeric($pid)) {
            throw new ProcessException(tr('Run file ":file" contains invalid data ":data"', [
                ':file' => $file,
                ':data' => $pid
            ]));
        }

        $this->pid = $pid;
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
}