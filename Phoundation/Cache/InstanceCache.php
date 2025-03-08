<?php

/**
 * Class InstanceCache
 *
 * This is a basic caching class that stores values (always) with a key / sub_key set in memory. After the instance
 * process terminates, this cache will be lost, and it should only be used for variables that may be dropped once the
 * instance terminates
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Cache;

use Phoundation\Cache\Exception\CacheNotFoundException;
use Phoundation\Cache\Traits\TraitCacheStatistics;
use Phoundation\Core\Log\Log;
use Stringable;


class InstanceCache
{
    use TraitCacheStatistics;


    /**
     * The cache storage
     *
     * @var array $cache
     */
    protected static array $cache = [];

    /**
     * Cache store for the last key / sub_key value checked
     *
     * @var mixed $last_checked
     */
    protected static mixed $last_checked = null;


    /**
     * Deletes and returns the cached value from the cache for the given key / value
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     *
     * @return mixed
     */
    public static function delete(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key): mixed
    {
        if (array_key_exists($key, static::$cache)) {
            $section = &static::$cache[$key];

            if (array_key_exists($sub_key, $section)) {
                $value = $section[$sub_key];
                unset($section[$sub_key]);
                return $value;
            }
        }

        return null;
    }


    /**
     * Returns the cached value from the cache IF it exists, or will execute the callback, store that as cache and
     * return its value
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     * @param callable                         $callback
     *
     * @return mixed
     */
    public static function getOrGenerate(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key, callable $callback): mixed
    {
        $return = static::get($key, $sub_key);

        if ($return) {
            return $return;
        }

        $return = $callback();

        return static::set($return, $key, $sub_key);
    }


    /**
     * Returns the cached value from the cache IF it exists
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     * @param bool                             $exception
     *
     * @return mixed
     */
    public static function get(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key, bool $exception = false): mixed
    {
        static::$cache_lookups++;

        if (($key === null) or ($sub_key === null)) {
            return null;
        }

        if (array_key_exists($key, static::$cache)) {
            $section = &static::$cache[$key];

            if (array_key_exists($sub_key, $section)) {
                static::$cache_hits++;
                return $section[$sub_key];
            }
        }

        static::$cache_miss++;

        if ($exception) {
            throw new CacheNotFoundException(tr('Specified cache keys ":key" and ":sub_key" do not exist', [
                ':key'    => $key,
                ':sub_key' => $sub_key
            ]));
        }

        return null;
    }


    /**
     * Returns true if there is a cache for the specified key / sub_key
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     *
     * @return bool
     */
    public static function exists(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key): bool
    {
        static::$cache_lookups++;

        if (($key === null) or ($sub_key === null)) {
            return false;
        }

        if (array_key_exists($key, static::$cache)) {
            $section = &static::$cache[$key];

            if (array_key_exists($sub_key, $section)) {
                // Store the $last_checked for faster access
                static::$last_checked = &$section[$sub_key];
                static::$cache_hits++;
                return true;
            }
        }

        static::$cache_miss++;
        return false;
    }


    /**
     * Returns the value last checked to avoid having to do the same hash table lookups again
     *
     * @return mixed
     */
    public static function getLastChecked(): mixed
    {
        return static::$last_checked;
    }


    /**
     * Stores the specified value in the cache with the given key / sub_key and will return the value
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     *
     * @return mixed
     */
    public static function set(mixed $value, Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key): mixed
    {
        if (($key === null) or ($sub_key === null)) {
            return false;
        }

        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = [];
        }

        static::$cache[$key][$sub_key] = $value;

        return $value;
    }


    /**
     * Returns the number of all object sections in cache
     *
     * @return int
     */
    public static function geSectionCount(): int
    {
        return count(self::$cache);
    }


    /**
     * Returns the number of all objects in cache
     *
     * @return int
     */
    public static function getCount(): int
    {
        $count = 0;

        foreach (self::$cache as $value) {
            $count += count($value);
        }

        return $count;
    }


    /**
     * Returns the efficiency of the cache, so far
     *
     * @return float
     */
    public static function getEfficiency(): float
    {
        // Avoid division by 0
        if (static::$cache_lookups) {
            return (self::$cache_hits / static::$cache_lookups) * 100;
        }

        return 0;
    }


    /**
     * Clears the instance cache
     *
     * @return void
     */
    public static function clear(): void
    {
        Log::warning(ts('Cleared instance cache'));
        static::$cache = [];
    }
}
