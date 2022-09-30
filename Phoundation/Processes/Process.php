<?php

namespace Phoundation\Processes;

use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Exception\ProcessesException;

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
     * @var ?int $pid
     */
    protected ?int $pid = null;



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
     * Returns the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @return array
     */
    public function getAcceptedExitcodes(): array
    {
        return $this->accepted_exit_codes;
    }


    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array $return_values
     * @return Process This process so that multiple methods can be chained
     */
    public function setAcceptedExitcodes(array $return_values): Process
    {
        foreach ($return_values as $return_value) {
            if (!is_integer($return_value) or ($return_value < 0) or ($return_value > 255)) {
                throw new OutOfBoundsException(tr('The specified return value ":value" is invalid. Please specify a values between 0 and 255', [':value' => $return_values]));
            }
        }

        $this->accepted_exit_codes = $return_values;

        return $this;
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
        $this->exit_code = $exit_code;
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
        $this->exit_code = $exit_code;

        // So according to the documentation, for some reason passthru would return null on success and false on failure
        // Makes sense, right? Just return true or false, please,
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
     */
    protected function getCommandLine(): string
    {
        return  $this->command . ' ' . implode(' ', $this->arguments);
    }
}