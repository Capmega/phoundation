<?php

declare(strict_types=1);

namespace Phoundation\Core\Interfaces;


/**
 * Class Timer
 *
 * This is a standard timer object to measure passed time using PHP microtime()
 *
 * Once a timer is created it is automatically added to the Timers class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 * @see Timers
 */
interface TimerInterface
{
    /**
     * Returns the sub key for this timer
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Returns the start time for this timer
     *
     * @return float|null
     */
    public function getStart(): ?float;

    /**
     * Returns the stop time for this timer
     *
     * @return float|null
     */
    public function getStop(): ?float;

    /**
     * Returns the passed time for this timer
     *
     * @return float
     */
    public function getPassed(): float;

    /**
     * Returns the passed time for this timer
     *
     * @return float
     */
    public function getTotal(): float;

    /**
     * Returns all the passed laps for this timer
     *
     * @return array
     */
    public function getLaps(): array;

    /**
     * Starts the timer
     *
     * @return static
     */
    public function start(): static;

    /**
     * Records a passed lap and returns the time for that lap
     *
     * @return static
     */
    public function lap(): static;

    /**
     * Stop the specified stopwatch and returns the passed time
     *
     * @return static
     */
    public function stop(): static;
}
