<?php

namespace Phoundation\Core;

use Phoundation\Exception\OutOfBoundsException;



/**
 * Class StopWatch
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Stopwatch
{
    /**
     * Keeps track of all the timers
     *
     * @var array $timers
     */
    protected static array $timers = [];



    /**
     * Start a new stopwatch
     *
     * @param string $name
     * @return float
     */
    public static function start(string $name): float
    {
        if (!array_key_exists($name, self::$timers)) {
            throw new OutOfBoundsException('The specified stopwatch ":name" already exists', [':name' => $name]);
        }

        return self::$timers[$name] = microtime(true);
    }



    /**
     * Check how much time has passed on the specified timer
     *
     * @param string $name
     * @return float
     */
    public static function check(string $name): float
    {
        if (!array_key_exists($name, self::$timers)) {
            throw new OutOfBoundsException('The specified stopwatch ":name" does not exist', [':name' => $name]);
        }

        return microtime(true) - self::$timers[$name];
    }



    /**
     * Stop the specified stopwatch and returns the passed time
     *
     * @param string $name
     * @return float
     */
    public static function stop(string $name): float
    {
        if (!array_key_exists($name, self::$timers)) {
            throw new OutOfBoundsException('The specified stopwatch ":name" does not exist', [':name' => $name]);
        }

        // Get the passed time, remove the stopwatch and return the passed time
        $passed = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);

        return $passed;
    }
}