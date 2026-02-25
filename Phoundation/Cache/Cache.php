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

use Phoundation\Accounts\Config\Exception\ConfigException;
use Phoundation\Accounts\Config\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Cache\Enums\EnumCacheGroups;
use Phoundation\Cache\Exception\CacheGroupNotExistsException;
use Phoundation\Cache\Interfaces\CacheInterface;
use Phoundation\Cache\Traits\TraitCacheStatistics;
use Phoundation\Core\Core;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Enums\EnumPoadTypes;
use Phoundation\Data\Interfaces\PoadInterface;
use Phoundation\Data\Poad\Poad;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataStaticBooleanEnabled;
use Phoundation\Data\Traits\TraitDataStaticBooleanEnabledCli;
use Phoundation\Data\Traits\TraitDataStaticBooleanEnabledGlobal;
use Phoundation\Data\Traits\TraitDataStaticBooleanEnabledLocal;
use Phoundation\Data\Traits\TraitDataStaticBooleanEnabledWeb;
use Phoundation\Databases\Connectors\Exception\ConnectorNotExistsException;
use Phoundation\Databases\Database;
use Phoundation\Databases\FileDb\FileDb;
use Phoundation\Databases\Memcached\Memcached;
use Phoundation\Databases\MongoDb\MongoDb;
use Phoundation\Databases\NullDb\NullDb;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Requests\Response;
use Stringable;


class Cache extends Database implements CacheInterface
{
    use TraitCacheStatistics;
    use TraitDataConnector {
        setConnector as protected __setConnector;
    }
    use TraitDataStaticBooleanEnabled {
        getEnabled as protected __getEnabled;
    }
    use TraitDataStaticBooleanEnabledGlobal {
        getEnabledGlobal as protected __getEnabledGlobal;
    }
    use TraitDataStaticBooleanEnabledLocal {
        getEnabledLocal as protected __getEnabledLocal;
    }
    use TraitDataStaticBooleanEnabledWeb {
        getEnabledWeb as protected __getEnabledWeb;
    }
    use TraitDataStaticBooleanEnabledCli {
        getEnabledCli as protected __getEnabledCli;
    }


    /**
     * Tracks if cache has ben cleared in this process
     *
     * @var bool $has_been_cleared
     */
    protected static bool $has_been_cleared = false;


    /**
     * Cache class constructor
     *
     * @param EnumCacheGroups|string|null $connector
     * @param string|null                 $database
     * @param bool                        $allow_alternate_connector
     */
    public function __construct(EnumCacheGroups|string|null $connector, ?string $database = null, bool $allow_alternate_connector = true)
    {
        try {
            $this->setEnabled(Cache::getEnabled());
            $this->setConnector($connector, $database, $allow_alternate_connector);

        } catch (CacheGroupNotExistsException $e) {
            if ($allow_alternate_connector) {
                throw $e;
            }

            // Alternate connector  is not allowed, continue with cache disabled
            Cache::setEnabled(false);
            Incident::new($e)->save();
        }
    }


    /**
     * Returns a new static object
     *
     * @param EnumCacheGroups|string|null $connector
     * @param string|null                 $database
     * @param bool                        $allow_alternate_connector
     *
     * @return static
     */
    public static function new(EnumCacheGroups|string|null $connector, ?string $database = null, bool $allow_alternate_connector = true): static
    {
        return new static($connector, $database, $allow_alternate_connector);
    }


    /**
     * Returns if caching is enabled or not
     *
     * @return bool
     */
    public static function getEnabled(): bool
    {
        if (static::$enabled === null) {
            if (!Core::getReady()) {
                return false;
            }

            static::$enabled = config()->getBoolean('cache.enabled', false);
        }

        return static::$enabled;
    }


    /**
     * Returns if caching is enabled or not
     *
     * @return bool
     */
    public static function getEnabledWeb(): bool
    {
        if (static::$enabled_web === null) {
            if (!Core::getReady()) {
                return false;
            }

            static::$enabled_web = (config()->getBoolean('cache.web.enabled', false) and Cache::getEnabled());
        }

        return static::$enabled_web;
    }


    /**
     * Returns if caching is enabled or not
     *
     * @return bool
     */
    public static function getEnabledCli(): bool
    {
        if (static::$enabled_cli === null) {
            if (!Core::getReady()) {
                return false;
            }

            static::$enabled_cli = (config()->getBoolean('cache.cli.enabled', false) and Cache::getEnabled());
        }

        return static::$enabled_cli;
    }


    /**
     * Returns if caching is enabled or not
     *
     * @return bool
     */
    public static function getEnabledLocal(): bool
    {
        if (static::$enabled_local === null) {
            if (!Core::getReady()) {
                return false;
            }

            static::$enabled_local = (config()->getBoolean('cache.local.enabled', false) and Cache::getEnabled());
        }

        return static::$enabled_local;
    }


    /**
     * Returns if caching is enabled or not
     *
     * @return bool
     */
    public static function getEnabledGlobal(): bool
    {
        if (static::$enabled_global === null) {
            if (!Core::getReady()) {
                return false;
            }

            static::$enabled_global = (config()->getBoolean('cache.global.enabled', false) and Cache::getEnabled());
        }

        return static::$enabled_global;
    }


    /**
     * Converts the specified cache group into a connector string
     *
     * @param EnumCacheGroups|string $group
     *
     * @return string
     */
    protected function getConnectorStringFromCacheGroup(EnumCacheGroups|string $group): string
    {
        if (is_enum($group)) {
            return $group->value;
        }

        return $group;
    }


    /**
     * Return statistics from the cache driver instance
     *
     * @return array
     */
    public function getStatistics(): array
    {
        if (Cache::getEnabled()) {
            return $this->getDriver()?->getStatistics();
        }

        return [];
    }


    /**
     * Return statistics from the cache driver instance
     *
     * @param string $key
     *
     * @return array
     */
    public function getStatistic(string $key): array
    {
        if (Cache::getEnabled()) {
            $return  = [];
            $servers = $this->getStatistics();

            foreach ($servers as $server) {
                if (array_key_exists($key, $server)) {
                    $return[$key] = $server[$key];
                    continue;
                }

                throw new OutOfBoundsException(tr('Cannot return statistics for key ":key", the key does not exist', [
                    ':key' => $key,
                ]));
            }

            return $return;
        }

        return [];
    }


    /**
     * Returns the number of all object sections in cache
     *
     * @return int
     */
    public static function geSectionCount(): int
    {
        $total  = Arrays::sum(cache('autosuggest')->getStatistic('curr_items'));
        $total += Arrays::sum(cache('dataentries')->getStatistic('curr_items'));
        $total += Arrays::sum(cache('html')->getStatistic('curr_items'));
        $total += Arrays::sum(cache('objects')->getStatistic('curr_items'));
        $total += Arrays::sum(cache('values')->getStatistic('curr_items'));
        $total += Arrays::sum(cache('cache')->getStatistic('curr_items'));

        return $total;
    }


    /**
     * Sets the cache connector by name
     *
     * @param EnumCacheGroups|string|null $connector
     * @param string|null                 $database
     * @param bool                        $allow_alternate
     *
     * @return static
     */
    public function setConnector(EnumCacheGroups|string|null $connector, ?string $database = null, bool $allow_alternate = true): static
    {
        if (Cache::getEnabled()) {
            $connector = $this->getConnectorStringFromCacheGroup($connector);

            try {
                return $this->__setConnector($connector, $database);

            } catch (ConnectorNotExistsException $e) {
                // The requested cache connector does not exist, try the default "cache" connector instead
                try {
                    // The requested connector does not exist. If the specified connector is a valid cache connector, then just
                    // use the general "cache" connector instead as a default
                    if ($allow_alternate) {
                        if (EnumCacheGroups::tryFrom($connector)) {
                            return $this->setConnector('cache', $database, $allow_alternate);
                        }
                    }

                    // The specified group was not recognized, maybe the "cache-" prefix was missing? Try adding it
                    if (EnumCacheGroups::tryFrom('cache-' . $connector)) {
                        return $this->setConnector('cache-' . $connector, $database, $allow_alternate);
                    }

                    if ($connector === 'cache') {
                        throw new ConnectorNotExistsException(tr('The main cache group ":group" does not exist, please check configuration path "databases.connectors"', [
                            ':group' => $connector
                        ]), $e);
                    }

                    throw new CacheGroupNotExistsException(tr('The specified cache group ":group" does not exist, please check configuration path "databases.connectors"', [
                        ':group' => $connector
                    ]), $e);

                } catch (ConnectorNotExistsException $e) {
                    // So the "cache" connector has not been configured either, we cannot use cache, disable it and
                    // register an incident about this
                    Cache::setEnabled(false);

                    Incident::new()
                            ->setException($e)
                            ->setSeverity(EnumSeverity::medium)
                            ->setType('configuration')
                            ->setTitle(tr('Cache connector not configured'))
                            ->setBody(tr('Cache connector not configured, cache will be disabled until it has been configured'))
                            ->save();
                }
            }
        }

        return $this;
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
        if (Cache::getEnabled()) {
            $this->getDriver()?->clear();
        }

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
        Cache::new(EnumCacheGroups::objects)->clear();
        Cache::new(EnumCacheGroups::values)->clear();
        Cache::new(EnumCacheGroups::html)->clear();
        Cache::new('cache')->clear();

        Log::action(ts('Clearing all file caches'), 3);

        PhoPath::new(DIRECTORY_SYSTEM . 'cache/files/', PhoRestrictions::newWritableObject(DIRECTORY_SYSTEM . 'cache/files/'))
               ->delete();

        Log::success(ts('Cleared all caches'));

        if (static::$has_been_cleared and !$force) {
            return false;
        }

        // Clear web cache, but rebuild (clear & build) command cache as we will ALWAYS need commands available
        Log::action(ts('Rebuilding system caches'), 3);

        Libraries::rebuildWebCache();
        Libraries::rebuildHooksCache();
        Libraries::rebuildCronCache();
        Libraries::rebuildTestsCache();
        Libraries::rebuildCommandsCache();
        Libraries::rebuildConfigCache();
        Libraries::rebuildDataCache();

        static::$has_been_cleared = true;

        return true;
    }


    /**
     * Delete the specified page from cache
     *
     * @param Stringable|string|float|int|null $key
     *
     * @return static
     */
    public function delete(Stringable|string|float|int|null $key): static
    {
        if (Cache::getEnabled()) {
            $this->getDriver()?->delete($key);
        }

        return $this;
    }


    /**
     * Selects and returns the correct cache database driver
     *
     * @return Memcached|MongoDb|Redis|SqlInterface|NullDb|null
     */
    protected function getDriver(): Memcached|MongoDb|Redis|SqlInterface|FileDb|NullDb|null
    {
        if (!config()->getBoolean('cache.enabled', false)) {
            return null;
        }

        $driver = config()->getString('cache.driver', 'memcached');

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
     * Return statistics for this memcached instance
     *
     * @return array
     */
    public function getAllKeys(): array
    {
        if (Cache::getEnabled()) {
            return $this->getDriver()?->getAllKeys();
        }

        return [];
    }


    /**
     * Returns true if the specified key exists or not
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback
     * @param int                   $flags
     *
     * @return bool
     */
    public function exists(string|float|int|null $key, ?callable $cache_callback = null, int $flags = 0): bool
    {
        return $this->getDriver()?->exists($key, $cache_callback, $flags);
    }


    /**
     * Read the specified object from the cache.
     *
     * @note: NULL is returned if the specified hash does not exist in cache
     *
     * @param Stringable|string|float|int|null $key
     * @param callable|null                    $callback
     * @param bool                             $process_headers_footers
     *
     * @return PoadInterface|array|string|float|int|null
     */
    public function getOrGenerate(Stringable|string|float|int|null $key, ?callable $callback = null, bool $process_headers_footers = true): PoadInterface|array|string|float|int|null
    {
        static::$cache_lookups++;

        if ($key === null) {
            // NULL keys always ignore cache
            return $callback ? $callback() : null;
        }

        if (Cache::getEnabled()) {
            $return = $this->getDriver()?->get($key);

            if ($return === null) {
                static::$cache_miss++;

                if ($callback) {
                    // Execute the callback, save, and return the return value
                    return $this->getFromCallback($key, $callback);
                }

                // There is no cache value, there is no callback either, so return nothing
                return null;
            }

            Log::success($this->log(ts('Found cache entry for key ":key"', [
                ':key' => $key,
            ])), 3);

            static::$cache_hits++;
            return Poad::new($return)->getObject($process_headers_footers);
        }

        // Cache is disabled, execute the callback if it is specified
        return $callback ? $callback() : null;
    }


    /**
     * Execute the callback to generate the object, store it in cache, and return it
     *
     * @param Stringable|string|float|int|null $key
     * @param callable                         $callback
     *
     * @return PoadInterface|array|string|float|int|null
     */

    protected function getFromCallback(Stringable|string|float|int|null $key, callable $callback): PoadInterface|array|string|float|int|null
    {
        // Execute the callback for hard retrieval and store the results in cache
        $count   = Response::getPageHeadersFootersCount();
        $headers = Response::getPageHeadersCount();
        $footers = Response::getPageFootersCount();
        $result  = $callback();

        if ($count === Response::getPageHeadersFootersCount()) {
            $this->set($result, $key);

        } else {
            // The callback modified page headers or footers. Include these changes in the cache object
            $this->set(Poad::generateArray($result, static::class, EnumPoadTypes::compound, [
                'headers'  => Response::getLastAmountOfPageHeaders(Response::getPageHeadersCount() - $headers),
                'footers'  => Response::getLastAmountOfPageFooters(Response::getPageFootersCount() - $footers),
            ]), $key);
        }

        return $result;
    }


    /**
     * Write the specified page to cache
     *
     * @param PoadInterface|array|string|float|int|null $value
     * @param Stringable|string|float|int|null          $key
     *
     * @return static
     */
    public function set(PoadInterface|array|string|float|int|null $value, Stringable|string|float|int|null $key): static
    {
        if (Cache::getEnabled()) {
            if ($key === null) {
                // NULL key will never store anything
                return $this;
            }

            if ($value === null) {
                // NULL value? Delete the key instead
                $this->delete($key);

            } else {
                if ($value instanceof ArrayableInterface) {
                    // Prefix the JSON string from this object with PAODJSON to indicate that this is a
                    // Phoundation Object Array Data (POAD) string with JSON encoding that, upon Memcached::get() can be
                    // converted back into an object
                    $value = $value->getPoadArray();
                }

                try {
                    $this->getDriver()?->set($value, $key);

                } catch (ConfigPathDoesNotExistsException $e) {
                    Log::warning($this->log(ts('Cannot cache because the current driver is not properly configured, see exception information')));
                    Log::warning($e);
                }
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
    public static function systemAutoGitCommit(?string $section = null, ?bool $auto_commit = null, ?bool $signed = null, ?string $message = null): void
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


    /**
     * Prepares and returns the specified log message
     *
     * @param string $message
     *
     * @return string
     */
    protected function log(string $message): string
    {
        return '[CACHE: ' . $this->connector . '] ' . $message;
    }
}
