<?php

/**
 * Cache class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cache
 */


declare(strict_types=1);

namespace Phoundation\Cache;

use Phoundation\Cache\Interfaces\CacheInterface;
use Phoundation\Core\Core;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Data\Traits\TraitDataEnabled;
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


class Cache implements CacheInterface
{
    use TraitDataConnector;
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
     * @param string $connector
     */
    public function __construct(string $connector)
    {
        $this->setEnabled(!Core::isReady());

        $this->setConnector($connector);
    }


    /**
     * Returns a static object
     *
     * @param string $connector
     *
     * @return static
     */
    public static function new(string $connector): static
    {
        return new static($connector);
    }


    /**
     * Clears all cache data if it has not yet been done
     *
     * @param bool $force
     *
     * @return bool
     * @todo Implement more
     */
    public function clear(bool $force = false): bool
    {
        Log::action(ts('Clearing all caches'), 3);

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

        $this->driver()?->clear();

        Log::success(ts('Cleared all caches'));

        static::$has_been_cleared = true;

        return true;
    }


    /**
     * Delete the specified page from cache
     *
     * @param string      $key
     * @param string|null $namespace
     *
     * @return void
     */
    public function delete(string $key, ?string $namespace = null): void
    {
        $this->driver()?->delete($key, $namespace);
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
     * @return string|null
     */
    public function get(string $key, ?callable $callback = null): ?string
    {
        if ($this->enabled) {
            $result = $this->driver()?->get($key);

            if (!$result) {
                if ($callback) {
                    // Execute the callback for hard retrieval and store the results in cache
                    $result = $callback();
                    $this->set($result, $key);
                }

                // Return the results
                return get_null($result);
            }

            Log::success(ts('Found ":size" bytes cache entry for key ":key"', [
                ':key'  => $key,
                ':size' => strlen($result),
            ]));

            return $result;
        }

        return null;
    }


    /**
     * Write the specified page to cache
     *
     * @param array|string $data
     * @param string       $key
     * @param string|null  $namespace
     *
     * @return static
     */
    public function set(array|string $data, string $key, ?string $namespace = null): static
    {
        if ($this->enabled) {
            try {
                $this->driver()?->set($data, $key, $namespace);

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
