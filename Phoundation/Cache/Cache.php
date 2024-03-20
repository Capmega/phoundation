<?php

declare(strict_types=1);

namespace Phoundation\Cache;

use Phoundation\Cli\CliCommand;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Databases\Mc;
use Phoundation\Databases\Mongo;
use Phoundation\Databases\NullDb;
use Phoundation\Databases\Redis;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Web\Requests\Interfaces\RequestInterface;
use Phoundation\Web\Web;


/**
 * Cache class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Cache
 */
class Cache
{
    /**
     * Tracks if cache has ben cleared in this process
     *
     * @var bool $has_been_cleared
     */
    protected static bool $has_been_cleared = false;


    /**
     * Clears all cache data if it has not yet been done
     *
     * @param bool $force
     * @return bool
     * @todo Implement more
     */
    public static function clear(bool $force = false): bool
    {
        Log::action(tr('Clearing all caches'), 3);

        if (static::$has_been_cleared and !$force) {
            return false;
        }

        // Clear web cache, but rebuild (clear & build) command cache as we will ALWAYS need commands available
        Web::clearCache();
        CliCommand::rebuildCache();

        Log::action(tr('Clearing file caches'), 3);
        Path::new(DIRECTORY_DATA . 'cache/', Restrictions::writable(DIRECTORY_DATA . 'cache/'))->delete();
        static::driver()?->clear();

        Log::success(tr('Cleared all caches'));
        static::$has_been_cleared = true;
        return true;
    }


    /**
     * Returns true if in this process the cache has been cleared
     *
     * @return bool
     */
    public static function hasBeenCleared(): bool
    {
        return static::$has_been_cleared;
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
        try {
            static::driver()?->set($data, $key, $namespace);

        } catch (ConfigPathDoesNotExistsException $e) {
            Log::warning(tr('Cannot cache because the current driver is not properly configured, see exception information'));
            Log::warning($e);
        }
    }


    /**
     * Read the specified page from cache.
     *
     * @note: NULL will be returned if the specified hash does not exist in cache
     * @param string $key
     * @param string|null $namespace
     * @return string|null
     */
    public static function read(string $key, ?string $namespace = null): ?string
    {
        if (!Core::isState('script')) {
            // When core is NOT in script state, cache will be disabled
            return null;
        }

        return null;

        $result = static::driver()?->get($key, $namespace);

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
        static::driver()?->dataEntryDelete($key, $namespace);
    }


    /**
     * Selects and returns the correct cache database driver
     *
     * @return Mc|Mongo|Redis|SqlInterface|NullDb|null
     */
    protected static function driver(): Mc|Mongo|Redis|SqlInterface|NullDb|null
    {
        if (!Config::get('cache.enabled', false)) {
            return null;
        }

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
