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
     * @param string $timer_name
     * @return float
     */
    public static function start(string $timer_name): float
    {
        self::$timers[$timer_name] = microtime(true);
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
}