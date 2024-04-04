<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Servers\Interfaces\ServerInterface;


/**
 * Class ProcessCore
 *
 * This class embodies a process that will be executed
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 * @uses      \Phoundation\Os\Processes\ProcessVariables
 */
interface ProcessCoreInterface
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
     * @return void
     */
    public function executeNoReturn(): void;

    /**
     * Execute the command and depending on specified method, return or log output
     *
     * @param EnumExecuteMethodInterface $method
     *
     * @return IteratorInterface|array|string|int|bool|null
     */
    public function execute(EnumExecuteMethodInterface $method): IteratorInterface|array|string|int|bool|null;

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
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string;
}
