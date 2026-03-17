<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Os\Packages\Interfaces\PackagesInterface;
use Phoundation\Os\Processes\Exception\ProcessException;
use Stringable;

interface ProcessVariablesInterface extends Stringable
{
    /**
     * Returns the exact time that execution started
     *
     * @return float|null
     */
    public function getExecutionStartTime(): ?float;


    /**
     * Returns the exact time that execution stopped
     *
     * @return float|null
     */
    public function getExecutionStopTime(): ?float;


    /**
     * Returns the exact time that execution started
     *
     * @return ProcessInterface|null
     */
    public function getPreExecution(): ?ProcessInterface;


    /**
     * Sets the process to execute before the main process
     *
     * @param ProcessInterface|null $process
     *
     * @return static
     */
    public function setPreExecution(?ProcessInterface $process): static;


    /**
     * Returns the process to execute after the main process
     *
     * @return ProcessInterface|null
     */
    public function getPostExecution(): ?ProcessInterface;


    /**
     * Sets the process to execute after the main process
     *
     * @param ProcessInterface|null $process
     *
     * @return static
     */
    public function setPostExecution(?ProcessInterface $process): static;


    /**
     * Returns the exact time that a process took to execute
     *
     * @param bool $require_stop
     *
     * @return float|null
     */
    public function getExecutionTime(bool $require_stop = true): ?float;


    /**
     * Sets if this command line is escaped on render or not
     *
     * @return bool
     */
    public function getEscape(): bool;


    /**
     * Sets if this command line is escaped on render or not
     *
     * @param bool $escape
     *
     * @return static
     */
    public function setEscape(bool $escape): static;


    /**
     * Returns if  the log files will be cleared after this object is destroyed or not
     *
     * @return bool
     */
    public function getClearLogs(): bool;


    /**
     * Sets if  the log files will be cleared after this object is destroyed or not
     *
     * @param bool $clear_logs
     *
     * @return static
     */
    public function setClearLogs(bool $clear_logs): static;


    /**
     * Returns if this process will register pid information or not
     *
     * @return bool
     */
    public function getRegisterRunfile(): bool;


    /**
     * Sets if this process will register pid information or not
     *
     * @param bool $register_run_file
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setRegisterRunfile(bool $register_run_file): static;


    /**
     * Returns if the process will first CD to this directory before continuing
     *
     * @return PhoDirectoryInterface
     */
    public function getExecutionDirectory(): PhoDirectoryInterface;


    /**
     * Sets if the process will first CD to this directory before continuing
     *
     * @param PhoDirectoryInterface|null $execution_directory
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setExecutionDirectory(PhoDirectoryInterface|null $execution_directory): static;


    /**
     * Sets the execution path to private temp dir
     *
     * @param bool $public
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setExecutionDirectoryToTemp(bool $public = false): static;


    /**
     * Sets the log path where the process output will be redirected to
     *
     * @return string|null
     */
    public function getLogFile(): ?string;


    /**
     * Returns the run path where the process run file will be written
     *
     * @return string|null
     */
    public function getRunFile(): ?string;


    /**
     * Sets if a runfile will be used
     *
     * @return bool
     */
    public function getUseRunFile(): bool;


    /**
     * Sets if a runfile should be used
     *
     * @param bool $use_run_file
     *
     * @return static This process so that multiple methods can be chained
     * @throws ProcessException
     */
    public function setUseRunFile(bool $use_run_file): static;


    /**
     * Return the process identifier
     *
     * @return string
     * @throws ProcessException
     */
    public function getIdentifier(): string;


    /**
     * Sets the terminal to execute this command
     *
     * @param string|null $term
     * @param bool        $only_if_empty
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setTerm(?string $term = null, bool $only_if_empty = false): static;


    /**
     * Return the terminal to execute this command
     *
     * @return string|null
     */
    public function getTerm(): ?string;


    /**
     * Returns if the command should be executed as a different user using sudo.
     *
     * If this returns NULL, the command will not execute with sudo. If a string is returned, the command will execute
     * as that user.
     *
     * @return string|bool
     */
    public function getSudo(): string|bool;


    /**
     * Sets if the command should be executed as a different user using sudo.
     *
     * If $sudo is NULL or FALSE, the command will not execute with sudo. If a string is specified, the command will
     * execute as that user. If TRUE is specified, the command will execute as root (This is basically just a shortcut)
     *
     * @param string|bool|null $sudo                         If false, no sudo will be used. If true, the default "sudo -s" will be used. If a string, it should
     *                                                       contain the required sudo command
     * @param string|null      $user                 [null]  If specified, will sudo not to root, but to the specified user instead
     * @param bool             $preserve_environment [false] If true, will add the -E flag to the sudo command
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setSudo(string|bool|null $sudo, ?string $user = null, bool $preserve_environment = false): static;


    /**
     * Returns the CLI return values that are accepted as "success" and will not cause an exception
     *
     * @return array
     */
    public function getAcceptedExitCodes(): array;


    /**
     * Clears the CLI return values that are accepted as "success" and will not cause an exception
     *
     * @return static This process so that multiple methods can be chained
     */
    public function clearAcceptedExitCodes(): static;


    /**
     * Sets the CLI return values that are accepted as "success" and will not cause an exception
     *
     * @param array $exit_codes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setAcceptedExitCodes(array $exit_codes): static;


    /**
     * Sets the CLI return values that are accepted as "success" and will not cause an exception
     *
     * @param array|string|int|null $exit_codes The exit codes that are accepted as a "successful" termination of the executed process
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addAcceptedExitCodes(array|string|int|null $exit_codes): static;


    /**
     * Sets the CLI return values that are accepted as "success" and will not cause an exception
     *
     * @param string|int $exit_code
     *
     * @return static This process so that multiple methods can be chained
     */
    public function addAcceptedExitCode(string|int $exit_code): static;


    /**
     * Returns the server on which the command should be executed for this process
     *
     * @note NULL means this local server
     * @return PhoRestrictionsInterface
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface;


    /**
     * Set the server on which the command should be executed for this process
     *
     * @note NULL means this local server
     *
     * @param PhoRestrictionsInterface|array|string|null $_restrictions
     * @param bool                                       $write
     * @param string|null                                $label
     *
     * @return static
     */
    public function setRestrictionsObject(PhoRestrictionsInterface|array|string|null $_restrictions = null, bool $write = false, ?string $label = null): static;


    /**
     * Returns the command to be executed for this process
     *
     * @return string|null
     */
    public function getCommand(): ?string;


    /**
     * Returns the arguments for the command that will be executed
     *
     * @return array
     */
    public function getArguments(): array;


    /**
     * Clears all cache and arguments
     *
     * @return static This process so that multiple methods can be chained
     */
    public function clearArguments(): static;


    /**
     * Sets the arguments for the command that will be executed
     *
     * @note This will reset the currently existing list of arguments.
     *
     * @param array|null $arguments
     * @param bool       $escape_arguments
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setArguments(?array $arguments, bool $escape_arguments = true): static;


    /**
     * Adds multiple arguments to the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|int|float|null $arguments
     * @param bool                                   $escape_arguments
     *
     * @return static This process so that multiple methods can be chained
     */
    public function appendArguments(Stringable|array|string|int|float|null $arguments, bool $escape_arguments = true): static;


    /**
     * Adds an argument to the existing list of arguments for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     *
     * @param Stringable|array|string|float|int|null $argument
     * @param bool                                   $escape_argument
     *
     * @return static This process so that multiple methods can be chained
     */
    public function appendArgument(Stringable|array|string|float|int|null $argument, bool $escape_argument = true): static;


    /**
     * Sets a single argument for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     *
     * @param string|null $argument
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setArgument(?string $argument): static;


    /**
     * Returns the Variables for the command that will be executed
     *
     * @return array
     */
    public function getVariables(): array;


    /**
     * Sets the variables for the command that will be executed
     *
     * @note This will reset the currently existing list of variables.
     *
     * @param array $variables
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setVariables(array $variables): static;


    /**
     * Adds a variable to the existing list of Variables for the command that will be executed
     *
     * @param string           $key
     * @param string|float|int $value
     *
     * @return static
     */
    public function setVariable(string $key, string|float|int $value): static;


    /**
     * Returns the process where the output of this command will be piped to, IF specified
     *
     * @return ProcessInterface|PhoFileInterface|string|null
     */
    public function getPipe(): ProcessInterface|PhoFileInterface|string|null;


    /**
     * Sets the process where the output of this command will be piped to, IF specified
     *
     * @param ProcessInterface|PhoFileInterface|string|null $pipe
     *
     * @return static
     */
    public function setPipe(ProcessInterface|PhoFileInterface|string|null $pipe): static;


    /**
     * Sets the output redirection for this process
     *
     * @param string|null $redirect
     * @param int         $channel
     * @param bool        $append
     *
     * @return static
     */
    public function setOutputRedirect(?string $redirect, int $channel = 1, bool $append = false): static;


    /**
     * Returns the output redirection for the specified channel this process
     *
     * @return array|null
     */
    public function getOutputRedirect(int $channel): ?string;


    /**
     * Returns all the output redirections for this process
     *
     * @return array
     */
    public function getOutputRedirects(): array;


    /**
     * Returns the output of the process
     *
     * If requested before process execution, will return NULL
     *
     * @return array|null
     */
    public function getOutput(): array|null;

    /**
     * Returns the output of the process
     *
     * If requested before process execution, will return NULL
     *
     * @param string $separator
     *
     * @return string|null
     */
    public function getStringOutput(string $separator = PHP_EOL): string|null;

    /**
     * Sets the input redirection for this process
     *
     * @param Stringable|string|null $redirect
     * @param int                    $channel
     *
     * @return static
     */
    public function setInputRedirect(Stringable|string|null $redirect, int $channel = 1): static;


    /**
     * Returns the input redirection for the specified channel this process
     *
     * @return array|null
     */
    public function getInputRedirect(int $channel): ?string;


    /**
     * Returns all the input redirections for this process
     *
     * @return array
     */
    public function getInputRedirects(): array;


    /**
     * Returns the time in milliseconds that a process will wait before executing
     *
     * Defaults to 0, the process will NOT wait and start immediately
     *
     * @return int
     */
    public function getWait(): int;


    /**
     * Sets the time in milliseconds that a process will wait before executing
     *
     * Defaults to 0, the process will NOT wait and start immediately
     *
     * @param int $wait
     *
     * @return static
     */
    public function setWait(int $wait): static;


    /**
     * Sets the packages that should be installed automatically if the command for this process cannot be found
     *
     * @return PackagesInterface
     */
    public function getPackages(): PackagesInterface;


    /**
     * Sets the packages that should be installed automatically if the command for this process cannot be found
     *
     * @param Stringable|string $operating_system
     * @param array|string      $packages
     *
     * @return static
     */
    public function setPackages(Stringable|string $operating_system, array|string $packages): static;


    /**
     * Returns the timeout value for this process.
     *
     * If the process requires more time than the specified timeout value, it will be terminated automatically. Set to
     * 0 seconds  to disable, defaults to 30 seconds
     *
     * @return int
     */
    public function getTimeout(): int;


    /**
     * Sets the timeout value for this process.
     *
     * If the process requires more time than the specified timeout value, it will be terminated automatically. Set to
     * 0 seconds  to disable, defaults to 30 seconds
     *
     * @param int $timeout
     *
     * @return static
     */
    public function setTimeout(int $timeout): static;


    /**
     * Returns the pid value for this process when it is running in the background.
     *
     * @note Will return NULL if the process is not running in the background.
     *
     * @return ?int
     */
    public function getPid(): ?int;


    /**
     * Returns if debug is enabled or not
     *
     * @return bool
     */
    public function getDebug(): bool;


    /**
     * Sets debug mode on or off
     *
     * @param bool $debug
     *
     * @return static
     */
    public function setDebug(bool $debug): static;
}
