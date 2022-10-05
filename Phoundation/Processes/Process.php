<?php

namespace Phoundation\Processes;

use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Exception\ProcessesException;
use Phoundation\Processes\Exception\ProcessException;
use Phoundation\Processes\Exception\ProcessFailedException;

/**
 * Class Process
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
Class Process
{
    /**
     * The command that will be executed for this process
     *
     * @var string|null $command
     */
    protected ?string $command = null;

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
    protected ?string $log_path = null;

    /**
     * The run path where command output will be written to
     *
     * @var string|null $run_path
     */
    protected ?string $run_path = null;

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
     * Processes constructor.
     */
    public function __construct()
    {
    }



    /**
     * Sets the log path where the process output will be redirected to
     *
     * @return string
     */
    public function getLogPath(): string
    {
        return $this->log_path;
    }



    /**
     * Returns the log file where the process output will be redirected to
     *
     * @param string $path
     * @return Process This process so that multiple methods can be chained
     */
    public function setLogPath(string $path): Process
    {
        if (!$path) {
            // Set the default log path
            $path = ROOT . 'data/log/';
        }

        // Ensure the path ends with a slash and that it is writable
        $path = Strings::slash($path);
        $path = File::ensureWritable($path);
        $this->log_path = $path;

        return $this;
    }



    /**
     * Returns the run path where the process run file will be written
     *
     * @return string
     */
    public function getRunPath(): string
    {
        return $this->run_path;
    }



    /**
     * Sets the run path where the process run file will be written
     *
     * @param string $path
     * @return Process This process so that multiple methods can be chained
     */
    public function setRunPath(string $path): Process
    {
        if (!$path) {
            // Set the default log path
            $path = ROOT . 'data/run/';
        }

        // Ensure the path ends with a slash and that it is writable
        $path = Strings::slash($path);
        $path = File::ensureWritable($path);
        $this->run_path = $path;

        return $this;
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
     * @return Process This process so that multiple methods can be chained
     */
    public function setSudo(bool|string $sudo): Process
    {
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
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array $exit_codes
     * @return Process This process so that multiple methods can be chained
     */
    public function setAcceptedExitCodes(array $exit_codes): Process
    {
        $this->accepted_exit_codes = [];
        return $this->addAcceptedExitCodes($exit_codes);
    }



    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array $exit_codes
     * @return Process This process so that multiple methods can be chained
     */
    public function addAcceptedExitCodes(array $exit_codes): Process
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
     * @return Process This process so that multiple methods can be chained
     */
    public function addAcceptedExitCode(int $exit_code): Process
    {
        if (($exit_code < 0) or ($exit_code > 255)) {
            throw new OutOfBoundsException(tr('The specified $exit_code ":code" is invalid. Please specify a values between 0 and 255', [':code' => $exit_code]));
        }

        $this->accepted_exit_codes[] = $exit_code;

        return $this;
    }


    /**
     * Sets the actual CLI exit code after the process finished its execution
     *
     * This method will check if the specified exit code is accepted and if not, throw a Process exception
     *
     * @param int $exit_code
     * @param string|array $output
     * @return void
     */
    protected function setExitCode(int $exit_code, string|array $output): void
    {
        $this->exit_code = $exit_code;

        if (in_array($exit_code, $this->accepted_exit_codes)) {
            // The command executed correctly, yay!
            return;
        }

        throw new ProcessFailedException('The command ":command" failed with exit code ":code"', [
            'command'   => $this->command,
            'arguments' => $this->arguments,
            'timeout'   => $this->timeout,
            'log_path'  => $this->log_path,
            'run_path'  => $this->run_path,
            'exit_code' => $exit_code,
            'output'    => $output,
        ]);
    }



    /**
     * Set the command to be executed for this process
     *
     * @param string $command
     * @return Process This process so that multiple methods can be chained
     */
    public function setCommand(string $command): Process
    {
        $command = trim($command);

        if (!$command) {
            throw new OutOfBoundsException(tr('No command specified'));
        }

        // Check if the command exist on disk
        if (!file_exists($command)) {
//            $real_command = Commands::which($command);
//            $real_command = $command;
            $real_command = null;

            if (!$real_command) {
                throw new ProcessesException(tr('Specified process command ":command" does not exist', ['command' => $command]));
            }

            $command = $real_command;
        }

        // Apply proper escaping and register the command
        $this->command = escapeshellcmd($command);

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
     * Set the arguments for the command that will be executed
     *
     * @note This will reset the currently existing list of arguments.
     * @param array $arguments
     * @return Process This process so that multiple methods can be chained
     */
    public function setArguments(array $arguments): Process
    {
        $this->arguments = [];
        return $this->addArguments($arguments);
    }



    /**
     * Adds an argument to the existing list of arguments for the command that will be executed
     *
     * @param string $argument
     * @return Process This process so that multiple methods can be chained
     */
    public function addArgument(string $argument): Process
    {
        $this->arguments[] = escapeshellarg($argument);

        return $this;
    }



    /**
     * Adds multiple arguments to the existing list of arguments for the command that will be executed
     *
     * @param array $arguments
     * @return Process This process so that multiple methods can be chained
     */
    public function addArguments(array $arguments): Process
    {
        foreach ($arguments as $argument) {
            $this->addArgument($argument);
        }

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
     * @param int $timeout
     * @return Process
     */
    public function setTimeout(int $timeout): PRocess
    {
        if (!is_natural($timeout,  0)) {
            throw new OutOfBoundsException(tr('The specified timeout ":timeout" is invalid, it must be a natural number 0 or higher', [':timeout' => $timeout]));
        }

        $this->timeout = $timeout;

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
     * Execute the command using the PHP exec() cakk abd return an array
     *
     * @return array The output from the executed command
     */
    public function executeReturnArray(): array
    {
        if (Debug::enabled()) {
            Log::notice('Executing command ":command" using exec to return an array');
        }

        exec($this->getCommandLine(), $output, $exit_code);
        $this->setExitCode($exit_code, $output);
        return $output;
    }



    /**
     * Execute the command using passthru and send the output directly to the client
     *
     * @return bool
     */
    public function executePassthru(): bool
    {
        if (Debug::enabled()) {
            Log::notice('Executing command ":command" using exec to return an array');
        }

        $output = passthru($this->getCommandLine(), $exit_code);
        $this->setExitCode($exit_code, $output);

        // So according to the documentation, for some reason passthru() would return null on success and false on
        // failure. Makes sense, right? Just return true or false, please,
        if ($output === false) {
            return false;
        }

        return true;
    }



    /**
     * Executes the command for this object as a background process
     *
     * @return int The PID (Process ID) of the process running in the background
     */
    public function executeBackground(): int
    {
// TODO IMPLEMENT
    }



    /**
     * Returns if the process has executed or not
     *
     * @return bool
     */
    public function hasExecuted(): bool
    {
        return !($this->exit_code === null);
    }



    /**
     * Builds and returns the command line that will be executed
     *
     * @return string
     * @throws ProcessException
     */
    protected function getCommandLine(): string
    {
        if ($this->cached_command_line) {
            return $this->cached_command_line;
        }

        if (!$this->command) {
            throw new ProcessException(tr('Cannot execute process, no command specified'));
        }

        $this->cached_command_line = $this->command . ' ' . implode(' ', $this->arguments);

        // Add timeout
        if ($this->timeout) {
            $this->cached_command_line = 'timeout ' . escapeshellarg($this->timeout) . ' ' . $this->cached_command_line;
        }

        // Add sudo
        if ($this->timeout) {
            $this->cached_command_line = 'sudo -u ' . escapeshellarg($this->sudo) . ' ' . $this->cached_command_line;
        }

        return $this->cached_command_line;
    }
}