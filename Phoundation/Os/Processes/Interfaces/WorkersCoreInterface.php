<?php

namespace Phoundation\Os\Processes\Interfaces;


/**
 * Class Workers
 *
 * This class can manage worker processes running in the background
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 * @uses ProcessVariables
 */
interface WorkersCoreInterface extends ProcessCoreInterface
{
    /**
     * Returns if this process waits for the workers to finish before returning
     *
     * @return bool
     */
    public function getWaitWorkerFinish(): bool;

    /**
     * Sets if this process will wait for the workers to finish before returning
     *
     * @param bool $wait_worker_finish
     * @return static
     */
    public function setWaitWorkerFinish(bool $wait_worker_finish): static;

    /**
     * Returns the minimum number of workers required
     *
     * @return int
     */
    public function getMinimumWorkers(): int;

    /**
     * Sets the minimum number of workers required
     *
     * @param int|null $minimum
     * @return static
     */
    public function setMinimumWorkers(?int $minimum): static;

    /**
     * Returns the maximum number of workers required
     *
     * @return int
     */
    public function getMaximumWorkers(): int;

    /**
     * Sets the maximum number of workers required
     *
     * @param int|null $maximum
     * @return static
     */
    public function setMaximumWorkers(?int $maximum): static;

    /**
     * Returns number of time in milliseconds that the process cycle should sleep before retrying to start workers
     *
     * @return int
     */
    public function getWaitSleep(): int;

    /**
     * Sets Amount of time in milliseconds that the process cycle should sleep before retrying to start workers
     *
     * @param int $wait_sleep
     * @return static
     */
    public function setWaitSleep(int $wait_sleep): static;

    /**
     * Returns number of time in milliseconds that the process cycle should sleep each cycle while checking alive workers
     *
     * @return int
     */
    public function getCycleSleep(): int;

    /**
     * Sets number of time in milliseconds that the process cycle should sleep each cycle while checking alive workers
     *
     * @param int $cycle_sleep
     * @return static
     */
    public function setCycleSleep(int $cycle_sleep): static;

    /**
     * Returns the variable values list that this master worker will process
     *
     * @return string|null
     */
    public function getKey(): ?string;

    /**
     * Sets the variable values list that this master worker will process
     *
     * @param string $key
     * @return static
     */
    public function setKey(string $key): static;

    /**
     * Returns the variable values list that this master worker will process
     *
     * @return array|null
     */
    public function getValues(): ?array;

    /**
     * Sets the variable values list that this master worker will process
     *
     * @param array $values
     * @return static
     */
    public function setValues(array $values): static;

    /**
     * Returns the current number of workers running
     *
     * @return int
     */
    public function getCurrent(): int;

    /**
     * Start running the workers as background processes
     *
     * @return void
     */
    public function start(): void;

    /**
     * Stop all background-running workers
     *
     * @return void
     */
    public function stop(): void;
}