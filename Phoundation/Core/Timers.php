<?php

namespace Phoundation\Core;


use Phoundation\Exception\OutOfBoundsException;

/**
 * Class Timers
 *
 * This class keeps track of all running Timer classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 * @see Timer
 */
class Timers
{
    /**
     * All running timer objects are stored here
     *
     * @var array $timers
     */
    protected static array $timers = [];


    /**
     * Add the specified timer object to the timer register
     *
     * @param Timer $timer
     * @return Timer
     */
    public static function add(Timer $timer): Timer
    {
        if (array_key_exists($timer->getName(), static::$timers)) {
            throw new OutOfBoundsException('The specified timer ":name" already exists', [':name' => $timer->getName()]);
        }

        static::$timers[$timer->getName()] = $timer;

        return $timer;
    }


    /**
     * Returns the specified timer
     *
     * @param string $name
     * @return Timer
     */
    public static function get(string $name): Timer
    {
        if (!array_key_exists($name, static::$timers)) {
            // Create the timer
            return Timer::create($name);
        }

        return static::$timers[$name];
    }


    /**
     * Returns the specified timer
     *
     * @param string $name
     * @return Timer
     */
    public static function delete(string $name): Timer
    {
        if (!array_key_exists($name, static::$timers)) {
            throw new OutOfBoundsException('The specified timer ":name" does not exist', [':name' => $name]);
        }

        // Find the timer and remove it from the timers list, then return it
        $timer = static::$timers[$name];
        unset(static::$timers[$name]);

        return $timer;
    }
}