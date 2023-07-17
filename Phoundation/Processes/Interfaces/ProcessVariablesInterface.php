<?php

namespace Phoundation\Processes\Interfaces;

use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Exception\ProcessException;
use Phoundation\Processes\Process;
use Phoundation\Processes\ProcessVariables;
use Stringable;


/**
 * Trait ProcessVariables
 *
 * Manages all process variables
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
interface ProcessVariablesInterface
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
     * @return static
     */
    public function setPostExecution(?ProcessInterface $process): static;

    /**
     * Returns the exact time that a process took to execute
     *
     * @param bool $require_stop
     * @return float|null
     */
    public function getExecutionTime(bool $require_stop = true): ?float;

    /**
     * Increases the amount of times quotes should be escaped
     *
     * @return ProcessVariables
     */
    public function increaseQuoteEscapes(): static;

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
     * @return static This process so that multiple methods can be chained
     */
    public function setRegisterRunfile(bool $register_run_file): static;

    /**
     * Returns if the process will first CD to this directory before continuing
     *
     * @return Path
     */
    public function getExecutionPath(): Path;

    /**
     * Sets if the process will first CD to this directory before continuing
     *
     * @param Path|Stringable|string|null $execution_path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static This process so that multiple methods can be chained
     */
    public function setExecutionPath(Path|Stringable|string|null $execution_path, RestrictionsInterface|array|string|null $restrictions = null): static;

    /**
     * Sets the execution path to private temp dir
     *
     * @param bool $public
     * @return static This process so that multiple methods can be chained
     */
    public function setExecutionPathToTemp(bool $public = false): static;

    /**
     * Sets the log path where the process output will be redirected to
     *
     * @return string
     */
    public function getLogFile(): string;

    /**
     * Returns the run path where the process run file will be written
     *
     * @return string
     */
    public function getRunFile(): string;

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
     * @return static This process so that multiple methods can be chained
     */
    public function setTerm(string $term = null, bool $only_if_empty = false): static;

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
     * @return ?string
     */
    public function getSudo(): ?string;

    /**
     * Sets if the command should be executed as a different user using sudo.
     *
     * If $sudo is NULL or FALSE, the command will not execute with sudo. If a string is specified, the command will
     * execute as that user. If TRUE is specified, the command will execute as root (This is basically just a shortcut)
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setSudo(bool|string $sudo): static;

    /**
     * Returns the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @return array
     */
    public function getAcceptedExitCodes(): array;

    /**
     * Clears the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @return static This process so that multiple methods can be chained
     */
    public function clearAcceptedExitCodes(): static;

    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array $exit_codes
     * @return static This process so that multiple methods can be chained
     */
    public function setAcceptedExitCodes(array $exit_codes): static;

    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param array $exit_codes
     * @return static This process so that multiple methods can be chained
     */
    public function addAcceptedExitCodes(array $exit_codes): static;

    /**
     * Sets the CLI return values that are accepted as "success" and won't cause an exception
     *
     * @param int $exit_code
     * @return static This process so that multiple methods can be chained
     */
    public function addAcceptedExitCode(int $exit_code): static;

    /**
     * Returns the server on which the command should be executed for this process
     *
     * @note NULL means this local server
     * @return Restrictions
     */
    public function getRestrictions(): Restrictions;

    /**
     * Set the server on which the command should be executed for this process
     *
     * @note NULL means this local server
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool $write
     * @param string|null $label
     * @return static
     */
    public function setRestrictions(RestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static;

    /**
     * Set the command to be executed for this process
     *
     * @param string|null $command
     * @param bool $which_command
     * @return static This process so that multiple methods can be chained
     */
    public function setCommand(?string $command, bool $which_command = true): static;

    /**
     * Returns the command to be executed for this process
     *
     * @return string
     */
    public function getCommand(): string;

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
     * @param array $arguments
     * @param bool $escape
     * @return static This process so that multiple methods can be chained
     */
    public function setArguments(array $arguments, bool $escape = true): static;

    /**
     * Adds multiple arguments to the existing list of arguments for the command that will be executed
     *
     * @param array|string $arguments
     * @param bool $escape
     * @return static This process so that multiple methods can be chained
     */
    public function addArguments(array|string $arguments, bool $escape = true): static;

    /**
     * Adds an argument to the existing list of arguments for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     * @param array|string|null $argument
     * @param bool $escape
     * @return static This process so that multiple methods can be chained
     */
    public function addArgument(array|string|null $argument, bool $escape = true): static;

    /**
     * Sets a single argument for the command that will be executed
     *
     * @note All arguments will be automatically escaped, but variable arguments ($variablename$) will NOT be escaped!
     * @param string|null $argument
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
     * @param array $variables
     * @return static This process so that multiple methods can be chained
     */
    public function setVariables(array $variables): static;

    /**
     * Adds a variable to the existing list of Variables for the command that will be executed
     *
     * @param string $key
     * @param string $value
     * @return ProcessVariables This process so that multiple methods can be chained
     */
    public function setVariable(string $key, string $value): static;

    /**
     * Returns the process where the output of this command will be piped to, IF specified
     *
     * @return Process|null
     */
    public function getPipe(): ?Process;

    /**
     * Sets the process where the output of this command will be piped to, IF specified
     *
     * @param Process|null $pipe
     * @return static
     */
    public function setPipe(?Process $pipe): static;

    /**
     * Sets the output redirection for this process
     *
     * @param string|null $redirect
     * @param int $channel
     * @param bool $append
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
     * Sets the input redirection for this process
     *
     * @param string|null $redirect
     * @param int $channel
     * @return static
     */
    public function setInputRedirect(?string $redirect, int $channel = 1): static;

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
     * @return static
     */
    public function setWait(int $wait): static;

    /**
     * Sets the packages that should be installed automatically if the command for this process cannot be found
     *
     * @return string|array
     */
    public function getPackages(): string|array;

    /**
     * Sets the packages that should be installed automatically if the command for this process cannot be found
     *
     * @param string|array $packages
     * @return static
     */
    public function setPackages(string|array $packages): static;

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
     * @return static
     */
    public function setTimeout(int $timeout): static;

    /**
     * Get the process PID file from the run_file and remove the file
     *
     * @return void
     */
    public function setPid(): void;

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
     * @return static
     */
    public function setDebug(bool $debug): static;
}