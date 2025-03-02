<?php

/**
 * Cache class
 *
 * This class manages cache control
 *
 * This class can read and write to different types cache stores using different database connectors. Currently
 * supported cache stores are:
 *
 * memcached Stores cache in the configured memcached server
 * mongo     Stores cache in the configured mongo server
 * redis     Stores cache in the configured redis server
 * sql       Stores cache in the configured MySQL server in the table "cache"
 * file      Stores cache in files
 * null      Stores cache nowhere. This driver will always return null no matter what has been written
 *
 * Cache is divided into different groups. Each group can have their own different connector, allowing different
 * datasets to be written to different data stores, allowing grouping. This allows, for example, only HTML cache to be
 * flushed, while other cache groups will remain unaffected
 *
 * Currently supported cache groups are:
 * cache-autosuggest
 * cache-dataentries
 * cache-html
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cache
 */


declare(strict_types=1);

namespace Phoundation\Cache;

use Phoundation\Cache\Enums\EnumCacheGroups;
use Phoundation\Cache\Exception\CacheGroupNotExistsException;
use Phoundation\Cache\Interfaces\CacheInterface;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataEnabled;
use Phoundation\Databases\Connectors\Exception\ConnectorNotExistsException;
use Phoundation\Databases\Mc;
use Phoundation\Databases\Mongo;
use Phoundation\Databases\NullDb;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use ValueError;


class Cache implements CacheInterface
{
    use TraitDataConnector {
        setConnector as protected __setConnector;
    }
    use TraitDataEnabled;


    /**
     * Tracks if cache has ben cleared in this process
     *
     * @var bool $has_been_cleared
     */
    protected static bool $has_been_cleared = false;


    /**
     * Cache class constructor
     *
     * @param EnumCacheGroups|string $connector
     */
    public function __construct(EnumCacheGroups|string $connector)
    {
        $this->setEnabled(Core::isReady() and config()->getBoolean('cache.enabled', false))
             ->setConnector($connector);
    }


    /**
     * Returns a static object
     *
     * @param EnumCacheGroups|string $connector
     *
     * @return static
     */
    public static function new(EnumCacheGroups|string $connector): static
    {
        return new static($connector);
    }


    /**
     * Converts the specified cache group into a connector string
     *
     * @param EnumCacheGroups|string|null $group
     *
     * @return string|null
     */
    protected function getConnectorStringFromCacheGroup(EnumCacheGroups|string|null $group): ?string
    {
        if (is_enum($group)) {
            return $group->value;
        }

        return $group;
    }


    /**
     * Sets the cache connector by name
     *
     * @param EnumCacheGroups|string|null $connector
     * @param string|null                 $database
     *
     * @return static
     */
    public function setConnector(EnumCacheGroups|string|null $connector, ?string $database = null): static {
        $connector = $this->getConnectorStringFromCacheGroup($connector);

        try {

            return $this->__setConnector($connector, $database);

        } catch (ConnectorNotExistsException $e) {
            // The requested connector does not exist. If the specified connector is a valid cache connector, then just
            // use the general "cache" connector instead as a default
            if (EnumCacheGroups::tryFrom($connector)) {
                return $this->setConnector('cache', $database);
            }

            // The specified group was not recognized, maybe the "cache-" prefix was missing? Try adding it
            if (EnumCacheGroups::tryFrom('cache-' . $connector)) {
                return $this->setConnector('cache-' . $connector, $database);
            }

            if ($connector === 'cache') {
                throw new CacheGroupNotExistsException(tr('The main cache group ":group" does not exist, please check configuration path "databases.connectors"', [
                    ':group' => $connector
                ]), $e);
            }

            throw new CacheGroupNotExistsException(tr('The specified cache group ":group" does not exist, please check configuration path "databases.connectors"', [
                ':group' => $connector
            ]), $e);
        }
    }


    /**
     * Clears all cache data if it has not yet been done
     *
     * @param bool $force
     *
     * @return static
     */
    public function clear(bool $force = false): static
    {
        $this->driver()?->clear();
        return $this;
    }


    /**
     * Clears all cache groups
     *
     * @param bool $force
     *
     * @return bool
     */
    public static function clearAll(bool $force = false): bool
    {
        Log::action(ts('Clearing all caches'), 3);

        Cache::new(EnumCacheGroups::autosuggest)->clear();
        Cache::new(EnumCacheGroups::dataentries)->clear();
        Cache::new(EnumCacheGroups::html)->clear();
        Cache::new('cache')->clear();

        if (static::$has_been_cleared and !$force) {
            return false;
        }

        // Clear web cache, but rebuild (clear & build) command cache as we will ALWAYS need commands available
        Libraries::rebuildWebCache();
        Libraries::rebuildHooksCache();
        Libraries::rebuildCronCache();
        Libraries::rebuildTestsCache();
        Libraries::rebuildCommandsCache();

        Log::action(ts('Clearing file caches'), 3);

        PhoPath::new(DIRECTORY_SYSTEM . 'cache/files/', PhoRestrictions::newWritableObject(DIRECTORY_SYSTEM . 'cache/files/'))
               ->delete();


        Log::success(ts('Cleared all caches'));

        static::$has_been_cleared = true;

        return true;
    }


    /**
     * Delete the specified page from cache
     *
     * @param string      $key
     *
     * @return void
     */
    public function delete(string $key): void
    {
        $this->driver()?->delete($key);
    }


    /**
     * Selects and returns the correct cache database driver
     *
     * @return Mc|Mongo|Redis|SqlInterface|NullDb|null
     */
    protected function driver(): Mc|Mongo|Redis|SqlInterface|NullDb|null
    {
        if (!config()->get('cache.enabled', false)) {
            return null;
        }

        $driver = config()->get('cache.driver', 'memcached');

        switch ($driver) {
            case 'memcache':
                // no break

            case 'memcached':
                return mc($this->connector);

            case 'mongo':
                return mongo($this->connector);

            case 'redis':
                return redis($this->connector);

            case 'sql':
                return sql($this->connector);

            case 'file':
                return filedb($this->connector);

            case 'none':
                return null($this->connector);

            case '':
                throw new ConfigException(tr('No cache driver configured, please check configuration path "cache.driver" and use one of "memcached", "mongo", "redis", "sql", "file", or "null"'));

            default:
                throw new ConfigException(tr('Unknown cache driver ":driver" configured, please check configuration path "cache.driver" and use one of "memcached", "mongo", "redis", "sql", "file", or "null"', [
                    ':driver' => $driver,
                ]));
        }
    }


    /**
     * Returns true if in this process the cache has been cleared
     *
     * @return bool
     */
    public function hasBeenCleared(): bool
    {
        return static::$has_been_cleared;
    }


    /**
     * Read the specified page from cache.
     *
     * @note: NULL will be returned if the specified hash does not exist in cache
     *
     * @param string        $key
     * @param callable|null $callback
     *
     * @return mixed
     */
    public function get(string $key, ?callable $callback = null): mixed
    {
        if ($this->enabled) {
            $result = $this->driver()?->get($key);

            if (empty($result)) {
                if ($callback) {
                    // Execute the callback for hard retrieval and store the results in cache
                    $result = $callback();
                    $this->set($result, $key);
                }

                // Return the results
                return get_null($result);
            }

            Log::success(ts('Found cache entry for key ":key"', [
                ':key'  => $key,
            ]));

            return $result;
        }

        return null;
    }


    /**
     * Write the specified page to cache
     *
     * @param mixed  $data
     * @param string $key
     *
     * @return static
     */
    public function set(mixed $data, string $key): static
    {
        if ($this->enabled) {
            try {
                $this->driver()?->set($data, $key);

            } catch (ConfigPathDoesNotExistsException $e) {
                Log::warning(ts('Cannot cache because the current driver is not properly configured, see exception information'));
                Log::warning($e);
            }
        }

        return $this;
    }


    /**
     * Try to automatically commit system cache updates to git
     *
     * @param string|null $section
     * @param bool|null   $auto_commit
     * @param bool|null   $signed
     * @param string|null $message
     *
     * @return void
     */
    public function systemAutoGitCommit(?string $section = null, ?bool $auto_commit = null, ?bool $signed = null, ?string $message = null): void
    {
        $auto_commit = $auto_commit ?? config()->getBoolean('cache.system.commit.auto', false);

        if ($auto_commit) {
            // Is there anything to commit?
            $directory = new PhoDirectory(DIRECTORY_SYSTEM . 'cache/system/' . $section, PhoRestrictions::newCache(true));
            $git       = Git::new($directory);

            if ($git->getStatusFilesObject()->getCount()) {
                $signed  = $signed  ?? config()->getBoolean('versioning.git.sign', false);
                $message = $message ?? tr('Rebuilt system cache');

                // Commit the system cache
                $git->add($directory)
                    ->commit($message, config()->getBoolean('cache.system.commit.signed', false) or $signed);

                Log::success(ts('Committed system cache update to git'));
            }
        }
    }
}
