<?php

declare(strict_types=1);

namespace Phoundation\Core;

use Phoundation\Core\Exception\TimerException;
use Phoundation\Core\Interfaces\TimerInterface;
use Phoundation\Core\Interfaces\TimersInterface;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class Timers
 *
 * This class keeps track of all running Timer classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 * @see Timer
 */
class Timers implements TimersInterface
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
     * @param string $group
     * @param TimerInterface $timer
     * @return TimerInterface
     */
    public static function add(string $group, TimerInterface $timer): TimerInterface
    {
        static::ensureGroup($group);
        static::$timers[$group][] = $timer;
        return $timer;
    }


    /**
     * Returns a new timer
     *
     * @param string $group
     * @param string $label
     * @param bool $start
     * @return TimerInterface
     */
    public static function new(string $group, string $label = '', bool $start = true): TimerInterface
    {
        static::ensureGroup($group);
        return static::$timers[$group][] = Timer::new($label, $start);
    }


    /**
     * Returns all the timers under the specified group
     *
     * @param string $group
     * @param bool $exception
     * @return array
     */
    public static function get(string $group, bool $exception = true): array
    {
        if (array_key_exists($group, static::$timers)) {
            // Return the timers array
            return static::$timers[$group];
        }

        if ($exception) {
            throw new TimerException(tr('Timers for the group ":group" do not exist', [
                ':group' => $group,
            ]));
        }

        return [];
    }


    /**
     * Returns the number of timer groups
     *
     * @return int
     */
    public static function getCount(): int
    {
        return count(static::$timers);
    }


    /**
     * Returns true if the specified timer group exists, false otherwise
     *
     * @param string $group
     * @return bool
     */
    public static function exists(string $group): bool
    {
        return array_key_exists($group, static::$timers);
    }


    /**
     * Sort all internal timers from high to low
     *
     * @param string $group
     * @param bool $exception
     * @return void
     */
    public static function sortHighLow(string $group, bool $exception = true): void
    {
        if (array_key_exists($group, static::$timers)) {
            if ($exception) {
                throw new OutOfBoundsException(tr('Cannot sort specified timers group ":group", it does not exist', [
                    ':group' => $group
                ]));
            }
        }

        uasort(static::$timers[$group], function (Timer $a, Timer $b): int
        {
            if ($a->getTotal() < $b->getTotal()) {
                return 1;
            }

            if ($a->getTotal() > $b->getTotal()) {
                return -1;
            }

            return 0;
        });
    }


    /**
     * Returns all the timers for the specified group and removes them all from the Timers object
     *
     * @param string $group
     * @param bool $exception
     * @return array
     */
    public static function pop(string $group, bool $exception = true): array
    {
        $timer = static::get($group, $exception);

        unset(static::$timers[$group]);
        return $timer;
    }


    /**
     * Returns the specified timer group
     *
     * @param string $group
     * @return array
     */
    public static function delete(string $group): array
    {
        $timers = static::get($group);

        // Remove the timer from the timers list, then return it
        unset(static::$timers[$group]);
        return $timers;
    }


    /**
     * Returns all internal timers
     *
     * @return array
     */
    public static function getAll(): array
    {
        return static::$timers;
    }


    /**
     * Returns the total for all the timer with the specified group
     *
     * @param string $group
     * @return float
     */
    public static function getGroupTotal(string $group): float
    {
        $total = 0;

        if (array_key_exists($group, static::$timers)) {
            foreach (static::$timers[$group] as $timer) {
                $total += $timer->getTotal();
            }

            return $total;
        }

        throw new TimerException(tr('Cannot return total for timer group ":group", the group does not exist', [
            ':group' => $group
        ]));
    }


    /**
     * Returns the total for all the timers
     *
     * @return float
     */
    public static function getTotal(): float
    {
        $total = 0;

        foreach (static::$timers as $group) {
            foreach ($group as $timer) {
                $total += $timer->getTotal();
            }
        }

        return $total;
    }


    /**
     * Ensures that the specified timers group exists
     *
     * @param string $group
     * @return void
     */
    protected static function ensureGroup(string $group): void
    {
        if (!array_key_exists($group, static::$timers)) {
            static::$timers[$group] = [];
        }
    }


    /**
     * Stop all timers
     *
     * @param bool $force
     * @return void
     */
    public static function stop(bool $force = false): void
    {
        foreach (static::$timers as $group) {
            foreach ($group as $timer) {
                $timer->stop($force);
            }
        }
    }
}
