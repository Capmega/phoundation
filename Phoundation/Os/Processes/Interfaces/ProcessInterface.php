<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Servers\Interfaces\ServerInterface;
use Phoundation\Utils\Arrays;
use Stringable;


interface ProcessInterface extends ProcessVariablesInterface
{
    /**
     * Sets the server on which this command should be executed
     *
     * @return ServerInterface|null
     */
    public function getServerObject(): ?ServerInterface;


    /**
     * Sets the server on which this command should be executed
     *
     * @param ServerInterface|string|null $o_server
     *
     * @return static
     */
    public function setServerObject(ServerInterface|string|null $o_server): static;


    /**
     * Execute the command using the PHP exec() call and return an array
     *
     * @return array The output from the executed command
     */
    public function executeReturnArray(): array;


    /**
     * Execute the command using the PHP exec() call and return an IteratorInterface
     *
     * @return IteratorInterface The output from the executed command
     */
    public function executeReturnIterator(): IteratorInterface;


    /**
     * Execute the command using the PHP exec() call and return a string
     *
     * @return string The output from the executed command
     */
    public function executeReturnString(): string;


    /**
     * Execute the command using the PHP exec() call and return a string
     *
     * @return static
     */
    public function executeNoReturn(): static;


    /**
     * Execute the command and depending on specified method, return or log output
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function execute(EnumExecuteMethod $method): IteratorInterface|array|string|int|bool|null;


    /**
     * Execute the command using passthru and send the output directly to the client
     *
     * @return bool
     */
    public function executePassthru(): bool;


    /**
     * Executes the command for this object as a background process
     *
     * @return int The PID (Process ID) of the process running in the background
     */
    public function executeBackground(): int;


    /**
     * Returns if the process has executed or not
     *
     * @return bool
     */
    public function isFinished(): bool;


    /**
     * Returns if the process is currently executing
     *
     * @return bool
     */
    public function isExecuting(): bool;


    /**
     * Kill this (backgroun) process
     *
     * @param int $signal
     *
     * @return void
     */
    public function kill(int $signal = 15): void;


    /**
     * Builds and returns the command line that will be executed
     *
     * @param bool $background
     * @param bool $pipe
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false, bool $pipe = false): string;

    /**
     * Adds multiple arguments to the beginning of the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|int|float|null $arguments
     * @param bool                                   $escape_arguments
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function prependArguments(Stringable|array|string|int|float|null $arguments, bool $escape_arguments = true, bool $escape_quotes = true): static;

    /**
     * Adds an argument to the beginning of the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|float|int|null $argument
     * @param bool                                   $escape_argument
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function prependArgument(Stringable|array|string|float|int|null $argument, bool $escape_argument = true, bool $escape_quotes = true): static;

    /**
     * Set the command to be executed for this process
     *
     * @param string|null $command
     * @param bool        $which_command
     * @param bool        $clear_arguments
     *
     * @return static This process so that multiple methods can be chained
     */
    public function setCommand(?string $command, bool $which_command = true, bool $clear_arguments = true): static;

    /**
     * Returns true if the process can execute the current command with sudo privileges
     *
     * @param bool   $exception
     *
     * @return bool
     * @todo Find a better option than "--version" which may not be available for everything. What about shell commands
     *       like "true", or "which", etc?
     */
    public function hasSudoAvailable(bool $exception = false): bool;

    /**
     * Returns the exception handler for when processes in this object fail, if one exists for this object
     *
     * Normally, when a process fails, a ProcessFailedException will be thrown. If this handler is set up, the exception
     * will be generated but instead of throwing it, the exception will be passed to this handler, which then has the
     * responsibility of throwing the exception
     *
     * @return callable|null
     */
    public function getProcessFailedHandler(): ?callable;

    /**
     * Set an exception handler for when processes in this object fail
     *
     * Normally, when a process fails, a ProcessFailedException will be thrown. If this handler is set up, the exception
     * will be generated but instead of throwing it, the exception will be passed to this handler, which then has the
     * responsibility of throwing the exception.
     *
     * An example of exception handling would be:
     *
     * $_process = Process::new()->setProcessFailedHandler(function (Throwable $e) {
     *     if ($e->dataContains('test')) {
     *         // This exception may be ignored, just return
     *         return;
     *     }
     *
     *     throw $e;
     * });
     *
     * @param callable|null $handler The handler callback function that will be executed when a process fails. Please
     *                               note that this handler will then have the responsibility of throwing (or not) the
     *                               exception
     * @return $this
     */
    public function setProcessFailedHandler(?callable $handler): static;
}

