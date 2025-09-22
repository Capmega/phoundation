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

use Phoundation\Cache\Exception\CacheInvalidException;
use Phoundation\Cache\Exception\CacheKeysInvalidException;
use Phoundation\Cache\Exception\CacheNotFoundException;
use Phoundation\Cache\Traits\TraitCacheStatistics;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Stringable;


class LocalCache
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
    public static function delete(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key = null): mixed
    {
        if (!LocalCache::checkKeys($key, $sub_key)) {
            return null;
        }

        if ($sub_key === null) {
            $value = &static::$cache[$sub_key];
            unset(static::$cache[$sub_key]);

            Log::warning(ts('Local cache delete key ":key"', [':key' => $key]), 2);
            return $value;
        }

        if (array_key_exists($key, static::$cache)) {
            $section = &static::$cache[$key];

            if (array_key_exists($sub_key, $section)) {
                $value = $section[$sub_key];
                unset($section[$sub_key]);

                Log::warning(ts('Local cache delete key ":key / :sub_key"', [':key' => $key, ':sub_key' => $sub_key]), 2);
                return $value;
            }
        }

        // The specified key / subkey doesn't exist
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
    public static function getOrGenerate(callable $callback, Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key = null): mixed
    {
        $return = null;

        if (Cache::getEnabled()) {
            $return = static::get($key, $sub_key);
        }

        if ($return === null) {
            // Entry doesn't exist, generate it now
            return static::set($callback($key, $sub_key), $key, $sub_key);
        }

        return $return;
    }


    /**
     * Returns the cached value from the cache IF it exists, or will execute the callback, store that as cache and
     * return its value
     *
     * @note Make sure that used key and sub_key values are unique. Typically, key would be used to indicate a group, and the sub_key would be used to indicate
     *       a specific identifier. If one code section uses the key "users" to cache a list of all users with a specific role, and another code section will
     *       use "users" to store literally ALL users, this would result in a case collision. Because of this, make sure that key is very descriptive. In the
     *       case of the callback that stores users for a specific role, a better key name would be "users-with-role-NAME", whereas the callback that would load
     *       ALL users could be called "users-all", for example.
     *
     * @param callable                         $callback
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     * @param bool                             $exception
     * @param string|null                      $datatype
     *
     * @return mixed
     * @todo Consider if this method should check datatype of already cached data because if the same key is used with a different datatype in another location, a collision will happen
     */
    public static function getOrGenerateFromList(callable $callback, Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key, bool $exception = false, ?string $datatype = null): mixed
    {
        $return = null;

        if (Cache::getEnabled()) {
            $return = static::get($key, $sub_key);
        }

        if ($return === null) {
            // Entry doesn't exist, generate the list now, the entry SHOULD be in there somewhere
            $return = $callback($datatype);

            // Ensure the callback return datatype matches the required datatype
            if (!has_class_or_datatype($return, $datatype)) {
                throw new OutOfBoundsException(tr('InstanceCache callback value datatype ":datatype" does not match required datatype ":required"', [
                    ':datatype' => get_class_or_datatype($return),
                    ':required' => $datatype,
                ]));
            }

            // Ensure the list has an array structure
            if ($return instanceof IteratorInterface) {
                $return = $return->getSource();
            }

            // Place the entire list in cache, then use InstanceCache::get() to return value.
            if (Cache::getEnabled()) {
                static::setList($return, $key);
                return static::get($key, $sub_key, $exception);
            }

            return array_get($return, $sub_key);
        }

        return $return;
    }


    /**
     * Returns the cached value from the cache IF it exists
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     * @param bool                             $exception
     * @param string|null                      $datatype
     *
     * @return mixed
     */
    public static function get(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key = null, bool $exception = false, ?string $datatype = null): mixed
    {
        if (!Cache::getEnabled()) {
            return null;
        }

        static::$cache_lookups++;

        $return = null;
        $key    = (string) $key;

        if (!LocalCache::checkKeys($key, $sub_key)) {
            return null;
        }

        if (array_key_exists($key, static::$cache)) {
            if ($sub_key === null) {
                $return = array_get(static::$cache, $key);

            } else {
                $sub_key = (string) $sub_key;

                if (!is_array(static::$cache[$key])) {
                    throw CacheInvalidException::new(tr('Cannot access LocalCache keys ":key, :sub_key" as key ":key" is a non array value by itself', [
                        ':key'     => $key,
                        ':sub_key' => $sub_key
                    ]))->setData([
                        'key'     => $key,
                        'sub_key' => $sub_key,
                        'value'   => static::$cache[$key]
                    ]);
                }

                if (array_key_exists($sub_key, static::$cache[$key])) {
                    $return = static::$cache[$key][$sub_key];
                }
            }

            if ($return) {
                if ($datatype) {
                    // Validate datatype
                    if (gettype($return) !== $datatype) {
                        throw new CacheInvalidException(tr('Cannot access LocalCache keys ":key, :sub_key" because the datatype is invalid, expected ":datatype" but got ":type"', [
                            ':key'      => $key,
                            ':sub_key'  => $sub_key,
                            ':datatype' => $datatype,
                            ':type'     => get_class_or_datatype($return)
                        ]));
                    }
                }

                static::$cache_hits++;

                if (Log::isReady()) {
                    Log::success(ts('Local cache hit (get) for key ":key / :sub_key"', [':key' => $key, ':sub_key' => $sub_key]), 2);
                }

                return $return;
            }
        }

        static::$cache_miss++;

        if ($exception) {
            throw new CacheNotFoundException(tr('Specified cache keys ":key" and ":sub_key" do not exist', [
                ':key'     => $key,
                ':sub_key' => $sub_key
            ]));
        }

        if (Log::isReady()) {
            Log::warning(ts('Local cache miss for key ":key / :sub_key"', [':key' => $key, ':sub_key' => $sub_key]), 2);
        }

        return null;
    }


    /**
     * Validates keys and returns true if a lookup should be done for these keys
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     *
     * @return bool
     */
    protected static function checkKeys(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key): bool
    {
        if ($key === null) {
            if ($sub_key === null) {
                return false;
            }

            throw new OutOfBoundsException(tr('Invalid NULL key with sub_key ":sub_key" specified', [
                ':sub_key' => $sub_key
            ]));
        }

        return true;
    }


    /**
     * Returns true if there is a cache for the specified key / sub_key
     *
     * @param Stringable|string|float|int|null $key
     * @param Stringable|string|float|int|null $sub_key
     *
     * @return bool
     */
    public static function exists(Stringable|string|float|int|null $key, Stringable|string|float|int|null $sub_key = null): bool
    {
        if (!Cache::getEnabled()) {
            return false;
        }

        static::$cache_lookups++;

        if (!LocalCache::checkKeys($key, $sub_key)) {
            return false;
        }

        if (array_key_exists($key, static::$cache)) {
            $section = &static::$cache[$key];

            if ($sub_key === null) {
                if (Log::isReady()) {
                    Log::success(ts('Local cache hit (exists) for key ":key"', [':key' => $key]), 2);
                }

                return true;
            }

            $sub_key = (string) $sub_key;

            if (!is_array($section)) {
                throw CacheInvalidException::new(tr('Cannot access LocalCache keys ":key, :sub_key" as key ":key" is a non array value by itself', [
                    ':key'     => $key,
                    ':sub_key' => $sub_key
                ]))->setData([
                    'key'     => $key,
                    'sub_key' => $sub_key,
                    'value'   => $section
                ]);
            }

            if (array_key_exists($sub_key, $section)) {
                // Store the $last_checked for faster access
                static::$last_checked = &$section[$sub_key];
                static::$cache_hits++;

                if (Log::isReady()) {
                    Log::success(ts('Local cache (exists) hit for key ":key / :sub_key"', [':key' => $key, ':sub_key' => $sub_key]), 2);
                }

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
        if (!Cache::getEnabled()) {
            return null;
        }

        return static::$last_checked;
    }


    /**
     * Stores the specified value in the cache with the given key / sub_key and will return the value
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     * @param Stringable|string|float|int $sub_key
     *
     * @return mixed
     */
    public static function set(mixed $value, Stringable|string|float|int $key, Stringable|string|float|int $sub_key): mixed
    {
        $key = (string) $key;

        if (empty($sub_key)) {
            throw CacheKeysInvalidException::new(tr('Cannot set LocalCache data with key ":key", the required subkey was empty', [
                ':key' => $key
            ]))->setData([
                'key'   => $key,
                'value' => $value
            ]);
        }

        if (!array_key_exists($key, static::$cache)) {
            static::$cache[$key] = [];
        }

        static::$cache[$key][(string) $sub_key] = $value;
        return $value;
    }


    /**
     * Stores the specified value in the cache with the given key / sub_key and will return the value
     *
     * @param mixed                       $value
     * @param Stringable|string|float|int $key
     *
     * @return mixed
     */
    protected static function setList(mixed $value, Stringable|string|float|int $key): mixed
    {
        static::$cache[(string) $key] = $value;
        return $value;
    }


    /**
     * Returns the number of all object sections in cache
     *
     * @param Stringable|string|float|int|null $key
     *
     * @return int
     */
    public static function getSectionSubCount(Stringable|string|float|int|null $key): int
    {
        if (!Cache::getEnabled()) {
            return -1;
        }

        if (array_key_exists($key, static::$cache)) {
            return count(static::$cache[$key]);
        }

        throw new CacheNotFoundException(tr('Specified cache key ":key" does not exist', [
            ':key' => $key
        ]));
    }


    /**
     * Returns the number of all object sections in cache
     *
     * @return int
     */
    public static function getSectionCount(): int
    {
        if (!Cache::getEnabled()) {
            return -1;
        }

        return count(static::$cache);
    }


    /**
     * Returns the number of all objects in cache
     *
     * @return int
     */
    public static function getCount(): int
    {
        if (!Cache::getEnabled()) {
            return -1;
        }

        $count = 0;

        foreach (static::$cache as $value) {
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
        if (!Cache::getEnabled()) {
            return -1;
        }

        // Avoid division by 0
        if (static::$cache_lookups) {
            return (static::$cache_hits / static::$cache_lookups) * 100;
        }

        return 0;
    }


    /**
     * Clears the local cache
     *
     * @return void
     */
    public static function clear(): void
    {
        Log::warning(ts('Cleared local cache'), 4);
        static::$cache = [];
    }


    /**
     * Returns the keys that are currently available in the local cache
     *
     * @return array
     */
    public function getKeys(): array
    {
        if (!Cache::getEnabled()) {
            return [];
        }

        return array_keys(static::$cache);
    }


    /**
     * Returns the sub keys for the specified key that are currently available in the local cache
     *
     * @param Stringable|string|float|int|null $key
     * @param bool                             $exception
     *
     * @return array|null
     */
    public function getSubKeys(Stringable|string|float|int|null $key, bool $exception = false): ?array
    {
        if (!Cache::getEnabled()) {
            return null;
        }

        if (!array_key_exists($key, static::$cache)) {
            return array_keys(static::$cache);
        }

        if ($exception) {
            throw new OutOfBoundsException(tr('Cannot get local cache sub keys for key ":key", the key does not exist', [
                ':key' => $key
            ]));
        }

        return null;
    }
}
