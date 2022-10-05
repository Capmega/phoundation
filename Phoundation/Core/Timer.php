<?php

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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Record laps
     *
     * @var array $laps
     */
    protected array $laps = [];

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
     * @param string $key
     * @return float
     */
    public function startLap(string $key): float
    {
        $time = microtime(true);
        $this->laps[$key] = $time;
        return $time;
    }



    /**
     * Stop the specified stopwatch and returns the passed time
     *
     * @param string $key
     * @return float
     */
    public function stopLap(string $key): float
    {
        // Get the passed time for this lap and calculate the passed time
        $passed = microtime(true) - $this->laps[$key];

        $this->laps[$key] = $passed;
        return $passed;
    }
}