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
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Cache;

use Phoundation\Cache\Exception\CacheNotFoundException;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Debug;
use Stringable;


class InstanceCache
{
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
     * Tracks the number of time the cache was hit
     *
     * @var int $cache_hits
     */
    protected static int $cache_hits = 0;

    /**
     * Tracks the number of time the cache was missed
     *
     * @var int $cache_miss
     */
    protected static int $cache_miss = 0;

    /**
     * Tracks the number of time the cache was checked
     *
     * @var int $cache_checks
     */
    protected static int $cache_checks = 0;


    /**
     * Deletes and returns the cached value from the cache for the given key / value
     *
     * @param Stringable|string|int $key
     * @param Stringable|string|int $sub_key
     *
     * @return mixed
     */
    public static function delete(Stringable|string|int $key, Stringable|string|int $sub_key): mixed
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
     * @param Stringable|string|int $key
     * @param Stringable|string|int $sub_key
     * @param callable              $callback
     *
     * @return mixed
     */
    public static function getOrGenerate(Stringable|string|int $key, Stringable|string|int $sub_key, callable $callback): mixed
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
     * @param Stringable|string|int $key
     * @param Stringable|string|int $sub_key
     * @param bool                  $exception
     *
     * @return mixed
     */
    public static function get(Stringable|string|int $key, Stringable|string|int $sub_key, bool $exception = false): mixed
    {
        static::$cache_checks++;

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
     * @param Stringable|string|int $key
     * @param Stringable|string|int $sub_key
     *
     * @return bool
     */
    public static function exists(Stringable|string|int $key, Stringable|string|int $sub_key): bool
    {
        static::$cache_checks++;

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
     * @param mixed                 $value
     * @param Stringable|string|int $key
     * @param Stringable|string|int $sub_key
     *
     * @return mixed
     */
    public static function set(mixed $value, Stringable|string|int $key, Stringable|string|int $sub_key): mixed
    {
        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = [];
        }

        static::$cache[$key][$sub_key] = $value;

        return $value;
    }


    /**
     * Returns the number of cache hits so far
     *
     * @return int
     */
    public static function getCacheHits(): int
    {
        return self::$cache_hits;
    }


    /**
     * Returns the number of cache misses so far
     *
     * @return int
     */
    public static function getCacheMiss(): int
    {
        return self::$cache_miss;
    }


    /**
     * Returns the number of cache checks
     *
     * @return int
     */
    public static function getCacheChecks(): int
    {
        return self::$cache_checks;
    }


    /**
     * Returns the number of objects in cache
     *
     * @return int
     */
    public static function getCacheCount(): int
    {
        return count(self::$cache);
    }


    /**
     * Returns the efficiency of the cache, so far
     *
     * @return float
     */
    public static function getCacheEfficiency(): float
    {
        // Avoid division by 0
        if (static::$cache_checks) {
            return (self::$cache_hits / static::$cache_checks) * 100;
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
        Log::warning(tr('Cleared instance cache'));
        static::$cache = [];
    }


    /**
     * Logs the cache statistics when in debug mode
     *
     * @return void
     */
    public static function logStatistics(): void
    {
        if (Debug::isEnabled() and !QUIET) {
            Log::write(tr('InstanceCache object has ":count" cached object(s) with ":checks/:hits/:percent" effectiveness', [
                ':count'   => InstanceCache::getCacheCount(),
                ':checks'  => InstanceCache::getCacheChecks(),
                ':hits'    => InstanceCache::getCacheHits(),
                ':percent' => number_format(InstanceCache::getCacheEfficiency(), 2) . '%',
            ]), 'debug', 9);
        }
    }
}
