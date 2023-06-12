<?php

declare(strict_types=1);

namespace Phoundation\Core;

use Phoundation\Exception\OutOfBoundsException;


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
class Timer
{
    /**
     * Record the moment when the timer is started
     *
     * @var null|float $start
     */
    protected ?float $start = null;

    /**
     * Record lap
     *
     * @var float|null $lap
     */
    protected ?float $lap = null;

    /**
     *
     *
     * @var null|string $name
     */
    protected ?string $name = null;


    /**
     * Timer constructor
     *
     * @param string $name
     */
    protected function __construct(string $name)
    {
        if (!$name) {
            throw new OutOfBoundsException('No timer name specified');
        }

        $this->name = $name;
        $this->start = microtime(true);

        return Timers::add($this);
    }


    /**
     * Returns the name for this timer
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * Returns the start time for this timer
     *
     * @return float
     */
    public function getStart(): float
    {
        return $this->start;
    }


    /**
     * Returns the passed time for this timer
     *
     * @return float
     */
    public function getPassed(): float
    {
        return microtime(true) - $this->start;
    }


    /**
     * Start a mew timer
     *
     * @param string $name
     * @return Timer
     */
    public static function create(string $name): Timer
    {
        return new Timer($name);
    }


    /**
     * Records a passed lap and returns the time for that lap
     *
     * @return float
     */
    public function startLap(): float
    {
        $time = microtime(true);
        $this->lap = $time;
        return $time;
    }


    /**
     * Stop the specified stopwatch and returns the passed time
     *
     * @return float
     */
    public function stopLap(): float
    {
        // Get the passed time for this lap and calculate the passed time
        $passed = microtime(true) - $this->lap;

        $this->lap = $passed;
        return $passed;
    }
}