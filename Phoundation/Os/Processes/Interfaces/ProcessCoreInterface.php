<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Servers\Interfaces\ServerInterface;
use Stringable;

interface ProcessCoreInterface extends Stringable
{
    /**
     * Sets the server on which this command should be executed
     *
     * @return ServerInterface|null
     */
    public function getServer(): ?ServerInterface;


    /**
     * Sets the server on which this command should be executed
     *
     * @param ServerInterface|string|null $server
     *
     * @return $this
     */
    public function setServer(ServerInterface|string|null $server): static;


    /**
     * ExecuteExecuteInterface the command using the PHP exec() call and return an array
     *
     * @return array The output from the executed command
     */
    public function executeReturnArray(): array;


    /**
     * ExecuteExecuteInterface the command using the PHP exec() call and return an IteratorInterface
     *
     * @return IteratorInterface The output from the executed command
     */
    public function executeReturnIterator(): IteratorInterface;


    /**
     * ExecuteExecuteInterface the command using the PHP exec() call and return a string
     *
     * @return string The output from the executed command
     */
    public function executeReturnString(): string;


    /**
     * ExecuteExecuteInterface the command using the PHP exec() call and return a string
     *
     * @return static
     */
    public function executeNoReturn(): static;


    /**
     * ExecuteExecuteInterface the command and depending on specified method, return or log output
     *
     * @param EnumExecuteMethod $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function execute(EnumExecuteMethod $method): IteratorInterface|array|string|int|bool|null;


    /**
     * ExecuteExecuteInterface the command using passthru and send the output directly to the client
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
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string;
}
