<?php

namespace Phoundation\Cache;

use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Log\Log;
use Phoundation\Databases\Mc;
use Phoundation\Databases\Mongo;
use Phoundation\Databases\NullDb;
use Phoundation\Databases\Redis;
use Phoundation\Databases\Sql\Sql;


/**
 * Cache class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cache
 */
class Cache
{
    /**
     * Clear all cache data
     *
     * @return void
     */
    public static function clear(): void
    {
        // TODO Implement
        static::driver()->clear();
        Log::success(tr('Cleared cache'));
    }



    /**
     * Write the specified page to cache
     *
     * @param array|string $data
     * @param string $key
     * @param string|null $namespace
     * @return void
     */
    public static function write(array|string $data, string $key, ?string $namespace = null): void
    {
        static::driver()->set($data, $key, $namespace);
    }



    /**
     * Read the specified page from cache.
     *
     * @note: NULL will be returned if the specified hash does not exist in cache
     * @param string $key
     * @return string|null
     */
    public static function read(string $key, ?string $namespace = null): ?string
    {
        if (!Core::stateIs('script')) {
            // When core is NOT in script state, cache will be disabled
            return null;
        }

        return null;

        $result = static::driver()->get($key, $namespace);

        if (!$result) {
            return null;
        }

        Log::success(tr('Found ":size" bytes cache entry for key ":key"', [
            ':key'  => $key,
            ':size' => strlen($result)
        ]));

        return $result;
    }



    /**
     * Delete the specified page from cache
     *
     * @param string $key
     * @param string|null $namespace
     * @return void
     */
    public static function delete(string $key, ?string $namespace = null): void
    {
        static::driver()->delete($key, $namespace);
    }



    /**
     * Selects and returns the correct cache database driver
     *
     * @return Mc|Mongo|Redis|Sql|NullDb
     */
    protected static function driver(): Mc|Mongo|Redis|Sql|NullDb
    {
        $driver = Config::get('cache.driver', 'memcached');

        switch ($driver) {
            case 'memcache':
                // no-break
            case 'memcached':
                return mc();

            case 'mongo':
                return mongo();

            case 'redis':
                return redis();

            case 'sql':
                return sql();

            case 'none':
                return null();

            case '':
                throw new ConfigException(tr('No cache driver configured, please check configuration path "cache.driver" and use one of "memcached", "mongo", "redis" or "sql"'));

            default:
                throw new ConfigException(tr('Unknown cache driver ":driver" configured, please check configuration path "cache.driver" and use one of "memcached", "mongo", "redis" or "sql"', [
                    ':driver' => $driver
                ]));
        }
    }
}