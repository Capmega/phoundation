<?php

/**
 * Class Memcached
 *
 * This is the default memcached driver object
 *
 * @see       https://www.adayinthelifeof.nl/2011/02/06/memcache-internals/
 * @see       https://www.php.net/manual/en/class.memcached.php
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Memcached;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Memcached\Exception\MemcachedException;
use Phoundation\Databases\Memcached\Interfaces\MemcachedInterface;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Http\Url;
use Stringable;
use Throwable;


class Memcached implements MemcachedInterface
{
    use TraitDataConnector {
        setConnectorObject as protected __setConnectorObject;
    }


    /**
     * PHP Memcached driver
     *
     * @var \Memcached|null $memcached
     */
    protected ?\Memcached $memcached = null;

    /**
     * Memcached configuration
     *
     * @var array|null $configuration
     */
    protected ?array $configuration = null;

    /**
     * Active memcached connections for this instance
     *
     * @var array $servers
     */
    protected array $servers = [];


    /**
     * Initialize the class object through the constructor.
     *
     * MC constructor.
     *
     * @note Instance always defaults to "system" if not specified
     *
     * @param ConnectorInterface $_connector
     */
    public function __construct(ConnectorInterface $_connector)
    {
        // Ensure PHP has memcached support and that the specified connector is a memcached connector
        $_connector->checkDriver('memcached');

        if (static::getEnabled()) {
            // Get instance information and connect to memcached servers
            $this->setConnectorObject($_connector)->connect();
        }
    }


    /**
     * Returns true if memcached is enabled and the driver is available
     *
     * @return bool
     */
    public static function getEnabled(): bool
    {
        static $enabled;

        if ($enabled) {
            // Cached
            return true;
        }

        if (config()->getBoolean('databases.memcached.enabled', true)) {
            static::checkDriver();
            return $enabled = true;
        }

        return false;
    }


    /**
     * Sets the database connector object
     *
     * @param ConnectorInterface|null $_connector
     * @param string|int|null         $database
     *
     * @return static
     */
    public function setConnectorObject(?ConnectorInterface $_connector, string|int|null $database = null): static
    {
        $this->__setConnectorObject($_connector, $database)
             ->configuration = $this->getConnectorObject()->getMemcachedConfiguration();

        return $this;
    }


    /**
     * Connect to the memcached servers
     *
     * @return void
     */
    protected function connect(): void
    {
        $this->memcached = new \Memcached();
        $failed = 0;

        // Connect to all memcached servers, but only if no servers were added yet (this should normally be the case)
        foreach ($this->configuration['servers'] as $weight => $server) {
            try {
                $host = trim(Strings::until($server, ':'));
                $port = (int) trim(Strings::from($server, ':')) ?: 11211;

                $this->memcached->addServer($host, $port, $weight);
                $this->servers[] = $server;

            } catch (Throwable $e) {
                Log::warning($this->log(ts('Failed to connect to memcached server ":server" configured in path ":directory"', [
                    ':server'    => $server,
                    ':directory' => 'databases.connectors.' . $this->connector . '.servers.' . $weight,
                ])));

                Log::error($e);
                $failed++;
            }
        }

        if (isset($e) or $failed) {
            // We have not been able to connect to any memcached server at all!
            Log::warning($this->log(ts('Failed to connect to any memcached server')), 10);

            Incident::new()
                    ->setException(isset_get($e))
                    ->setUrl(Url::new('reports/security/incidents.html')->makeWww())
                    ->setNotifyRoles('developer')
                    ->setTitle(tr('Memcached server not available'))
                    ->setBody(tr('Failed to connect to all ":count" memcached servers', [
                        ':server' => count($this->configuration['servers']
                    )]))
                    ->save();
        }
    }


    /**
     * Return the active Mc connections
     *
     * @return array
     */
    public function getActiveConnections(): array
    {
        return $this->servers;
    }


    /**
     * Return the configured Mc connections
     *
     * @return array
     */
    public function getConfiguredConnections(): array
    {
        return $this->configuration['servers'];
    }


    /**
     * Return configuration data.
     *
     * If the $key is specified, only the configuration data for that specified key will be returned
     *
     * @param string|int|null $key
     *
     * @return array|string|null
     */
    public function getConfiguration(string|int|null $key = null): array|string|null
    {
        if ($key) {
            return isset_get($this->configuration[$key]);
        }

        return $this->configuration;
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
        return !($this->memcached->get($this->getSafeKey($key), $cache_callback, $flags) === false);
    }


    /**
     * Returns the specified data to the specified key (and optionally the specified namespace)
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback An optional callback function for read-through caching
     * @param int                   $flags          Currently supports Memcached::GET_EXTENDED
     *
     * @return array|string|float|int|null
     * @see https://www.php.net/manual/en/memcached.get.php
     */
    public function get(string|float|int|null $key, ?callable $cache_callback = null, int $flags = 0): array|string|float|int|null
    {
        if ($key === null) {
            // NULL key always returns null
            return null;
        }

        $return = $this->memcached->get($this->getSafeKey($key), $cache_callback, $flags);

        if ($return === false) {
            // Nothing found for this key
            return null;
        }

        Log::success($this->log(ts('Found key ":key" through memcached connector ":connector"', [
            ':connector' => $this->connector,
            ':key'       => $key,
        ])), 2);

        return $return;
    }


    /**
     * Sets the specified key to the specified value on the memcached server(s)
     *
     * @param array|string|float|int|null      $value
     * @param Stringable|string|float|int|null $key
     * @param int|null                         $expires
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(array|string|float|int|null $value, Stringable|string|float|int|null $key, ?int $expires = null): static
    {
        if ($key === null) {
            // NULL key will never store anything
            return $this;
        }

        try {
            $result = $this->memcached->set($this->getSafeKey($key), $value, $expires ?? $this->configuration['expires']);

        } catch (Throwable $e) {
            if ($this->memcached->getResultCode() === -1001) {
                throw MemcachedException::new(tr('Failed to set key ":key" through memcached connector ":connector" due to value containing unserialized objects. Memcached only supports storing array, string, or numeric values.', [
                    ':key'       => $key,
                    ':connector' => $this->connector,
                ]), $e)->setData([
                    'code'    => $this->memcached->getResultCode(),
                    'message' => $this->memcached->getResultMessage(),
                    'value'   => Json::encode($value)
                ]);
            }

            throw MemcachedException::new(tr('Failed to set key ":key" through memcached connector ":connector"', [
                ':key'       => $key,
                ':connector' => $this->connector,
            ]), $e)->setData([
                'code'    => $this->memcached->getResultCode(),
                'message' => $this->memcached->getResultMessage(),
                'value'   => Json::encode($value)
            ]);
        }

        if ($result) {
            Log::success($this->log(ts('Set key ":key" through memcached connector ":connector"', [
                ':connector' => $this->connector,
                ':key'       => $key,
            ])), 2);

            return $this;
        }

        throw new MemcachedException($this->log(ts('Setting value for key ":key" failed with code ":code" and message ":message"', [
            ':key'     => $key,
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Adds the specified key to the memcached server(s)
     *
     * Requires the specified key to NOT exist, and will cause an exception if it does
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     *
     * @return static
     */
    public function add(mixed $value, string|float|int|null $key, ?int $expires = null): static
    {
        if (is_bool($value)) {
            throw new MemcachedException($this->log(ts('Cannot add boolean values in memcached for key ":key"', [
                ':key' => $key,
            ])));
        }

        $result = $this->memcached->add($this->getSafeKey($key), $value, $expires ?? $this->configuration['expires']);

        if ($result) {
            Log::success($this->log(ts('Wrote ":bytes" bytes to memcached for key ":key"', [
                ':key'   => $key,
                ':bytes' => (is_scalar($value) ? strlen((string) $value) : count($value)),
            ])), 2);

            return $value;
        }

        throw new MemcachedException($this->log(ts('Adding value for key ":key" failed with code ":code" and message ":message"', [
            ':key'     => $key,
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Replaces the specified key on hte memcached server(s)
     *
     * Requires the specified key to exist, and will cause an exception if it does not
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     *
     * @return static
     */
    public function replace(mixed $value, string|float|int|null $key, ?int $expires = null): static
    {
        if (is_bool($value)) {
            throw new MemcachedException($this->log(ts('Cannot replace keys with boolean values in memcached for key ":key"', [
                ':key' => $key,
            ])));
        }

        $result = $this->memcached->replace($this->getSafeKey($key), $value, $expires ?? $this->configuration['expires']);

        if ($result) {
            Log::success($this->log(ts('Wrote ":bytes" bytes to memcached for key ":key"', [
                ':key'   => $key,
                ':bytes' => (is_scalar($value) ? strlen((string) $value) : count($value)),
            ])), 2);

            return $value;
        }

        throw new MemcachedException($this->log(ts('Replacing value for key ":key" failed with code ":code" and message ":message"', [
            ':key'     => $key,
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Deletes the specified key from the memcached server(s)
     *
     * @param string|float|int|null $key
     * @param int                   $time
     *
     * @return static
     */
    public function delete(string|float|int|null $key, int $time = 0): static
    {
        $result = $this->memcached->delete($this->getSafeKey($key), $time);

        if ($result) {
            Log::success($this->log(ts('Deleted key ":key"', [
                ':key' => $key,
            ])), 2);

            return $this;
        }

        if ($this->memcached->getResultCode() === 16) {
            // This means the deleted key did not exist in the first place, that is fine
            return $this;
        }

        throw new MemcachedException($this->log(ts('Deleting key ":key" failed with code ":code" and message ":message"', [
            ':key'     => $key,
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Flush all cached memcache data
     *
     * @param int $delay
     *
     * @return static
     */
    public function clear(int $delay = 0): static
    {
        $result = $this->memcached->flush($delay);

        if ($result) {
            Log::success($this->log(ts('Flushed all data with delay ":delay"', [
                ':delay' => $delay,
            ])), 2);

            return $this;
        }

        throw new MemcachedException($this->log(ts('Flushing all data failed with code ":code" and message ":message"', [
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Increment the value of the specified key
     *
     * @param string|float|int|null $key
     * @param int                   $offset
     * @param int                   $initial_value
     * @param int                   $expiry
     *
     * @return static
     */
    public function increment(string|float|int|null $key, int $offset = 1, int $initial_value = 0, int $expiry = 0): static
    {
        $result = $this->memcached->increment($this->getSafeKey($key), $offset, $initial_value, $expiry);

        if ($result) {
            Log::success($this->log(ts('Incremented key ":key" by ":offset"', [
                ':key'    => $key,
                ':offset' => $offset,
            ])), 2);

            return $this;
        }

        throw new MemcachedException($this->log(ts('Incrementing value for key ":key" failed with code ":code" and message ":message"', [
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Return statistics for this memcached instance
     *
     * @return array
     *
     * Information on output:
     *
     * @see http://www.pal-blog.de/entwicklung/perl/memcached-statistics-stats-command.html
     *
     * Field                 Sample     Description
     *
     * accepting_conns       1          The Memcached server is currently accepting new connections.
     * auth_cmds             0          Number of authentication commands processed by the server - if you use
     *                                  authentication within your installation. The default is IP (routing) level
     *                                  security which speeds up the actual Memcached usage by removing the
     *                                  authentication requirement.
     * auth_errors           0          Number of failed authentication tries of clients.
     * bytes                 6775829    Number of bytes currently used for caching items, this server currently uses
     *                                  ~6 MB of it is maximum allowed (limit_maxbytes) 1 GB cache size.
     * bytes_read            880545081  Total number of bytes received from the network by this server.
     * bytes_written         3607442137 Total number of bytes send to the network by this server.
     * cas_badval            0          The "cas" command is some kind of Memcached's way to avoid locking. 'cas' calls
     *                                  with bad identifier are counted in this stats key.
     * cas_hits              0          Number of successful 'cas' commands.
     * cas_misses            0          'cas' calls fail if the value has been changed since it was requested from the
     *                                  cache. We are currently not using "cas" at all, so all three cas values are zero.
     * cmd_flush             0          The "flush_all" command clears the whole cache and should not be used during
     *                                  normal operation.
     * cmd_get               1626823    Number of 'get' commands received since server startup not counting if they were
     *                                  successful or not.
     * cmd_set               2279784    Number of 'set' commands serviced since startup.
     * connection_structures 42         Number of internal connection handles currently held by the server. May be used
     *                                  as some kind of 'maximum parallel connection count' but the server may destroy
     *                                  connection structures (do not know if he ever does) or prepare some without
     *                                  having actual connections for them (also do not know if he does). 42 maximum
     *                                  connections and 34 current connections (curr_connections) sounds reasonable, the
     *                                  live servers also have about 10% more connection_structures than
     *                                  curr_connections.
     * conn_yields           1          Memcached has a configurable maximum number of requests per event (-R command
     *                                  line argument), this counter shows the number of times any client hit this
     *                                  limit.
     * curr_connections      34         Number of open connections to this Memcached server, should be the same value on
     *                                  all servers during normal operation. This is something like the count of mySQL's
     *                                  "SHOW PROCESSLIST" result rows.
     * curr_items            30345      Number of items currently in this server's cache. The production system of this
     *                                  development environment holds more than 8 million items.
     * decr_hits             0          The 'decr' command decreases a stored (integer) value by 1. A 'hit' is a 'decr'
     *                                  call to an existing key.
     * decr_misses           0          'decr' command calls to undefined keys.
     * delete_hits           138707     Stored keys may be deleted using the 'delete' command, this system does not
     *                                  delete cached data itself, but it is using the Memcached to avoid recaching-races
     *                                  and the race keys are deleted once the race is over and fresh content has been
     *                                  cached.
     * delete_misses         107095     Number of 'delete' commands for keys not existing within the cache. These 107k
     *                                  failed deletes are deletions of non existent race keys (see above).
     * evictions             0          Number of objects removed from the cache to free up memory for new items because
     *                                  Memcached reached it is maximum memory setting (limit_maxbytes).
     * get_hits              391283     Number of successful "get" commands (cache hits) since startup, divide them by
     *                                  the "cmd_get" value to get the cache hitrate: This server was able to serve 24%
     *                                  of it is get requests from the cache, the live servers of this installation
     *                                  usually have more than 98% hits.
     * get_misses            1235540    Number of failed 'get' requests because nothing was cached for this key or the
     *                                  cached value was too old.
     * incr_hits             0          Number of successful 'incr' commands processed. 'incr' is a replace adding 1 to
     *                                  the stored value and failing if no value is stored. This specific installation
     *                                  (currently) does not use incr/decr commands, so all their values are zero.
     * incr_misses           0          Number of failed "incr" commands (see incr_hits).
     * limit_maxbytes        1073741824 Maximum configured cache size (set on the command line while starting the
     *                                  memcached server), look at the "bytes" value for the actual usage.
     * listen_disabled_num   0          Number of denied connection attempts because memcached reached it is configured
     *                                  connection limit ('-c' command line argument).
     * pid                   24040      Current process ID of the Memcached task.
     * pointer_size          64         Number of bits of the hostsystem, may show '32' instead of '64' if the running
     *                                  Memcached binary was compiled for 32 bit environments and is running on a 64 bit
     *                                  system.
     * reclaimed             14740      Number of times a write command to the cached used memory from another expired
     *                                  key. These are not storage operations deleting old items due to a full cache.
     * rusage_system         310.030000 Number of system time seconds for this server process.
     * rusage_user           103.230000 Number of user time seconds for this server process.
     * threads               4          Number of threads used by the current Memcached server process.
     * time                  1323008181 Current unix timestamp of the Memcached's server.
     * total_connections     27384      Number of successful connect attempts to this server since it has been started.
     *                                  Roughly
     *                     $number_of_connections_per_task * $number_of_webserver_tasks * $number_of_webserver_restarts.
     * total_items           323615     Number of items stored ever stored on this server. This is no "maximum item
     *                                  count" value but a counted increased by every new item stored in the cache.
     * uptime                1145873    Number of seconds the Memcached server has been running since last restart.
     *                                  1145873 / (60 * 60 * 24) = ~13 days since this server has been restarted
     * version               1.4.5      Version number of the server
     */
    public function getStatistics(): array
    {
        $stats = $this->memcached->getStats();

        if ($stats === false) {
            throw new MemcachedException($this->log(ts('Failed to return statistics with code ":code" and message ":message"', [
                ':code'    => $this->memcached->getResultCode(),
                ':message' => $this->memcached->getResultMessage()
            ])));
        }

        return $stats;
    }


    /**
     * Return statistics for this memcached instance
     *
     * @return array
     */
    public function getAllKeys(): array
    {
        $return = $this->memcached->getAllKeys();

        if ($return === false) {
            throw new MemcachedException($this->log(ts('Failed to return all keys with code ":code" and message ":message"', [
                ':code'    => $this->memcached->getResultCode(),
                ':message' => $this->memcached->getResultMessage()
            ])));
        }

        return $return;
    }


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static
    {
        throw new UnderConstructionException();
        return $this;
    }


    /**
     * Ensures that the PHP memcached driver is loaded and available
     *
     * @return void
     */
    public static function checkDriver(): void
    {
        if (!class_exists('Memcached')) {
            throw new PhpModuleNotAvailableException(tr('The PHP module "memcached" appears not to be installed. Please install the module first. On Debian and alike, use "sudo sudo apt-get -y install php-memcached; sudo phpenmod memcached" to install and enable the module., on Redhat and alike use "sudo yum -y install php-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }
    }


    /**
     * Returns a key that is safe for use with memcached
     *
     * @param string|float|int|null $key
     *
     * @return string
     */
    protected function getSafeKey(string|float|int|null $key): string
    {
        $key = (string) $key;

        if (strlen($key) > 250) {
            $sha = sha1($key) . ' - this key was too long and was converted to sha1';

            // Invalid key!
            Log::warning(ts('Detected memcached key ":key" with length ":count" characters, which is longer than 250 characters, which is not allowed by memcached. Converting to SHA1 ":sha"', [
                ':key'   => $key,
                ':sha'   => $sha,
                ':count' => strlen($key),
            ]), 3);

            $key = $sha;
        }

        $key = str_replace(["\0", "\r", "\n", "\t", ' '], '-', $key);
        $key = str_replace('--', '-', $key);

        return $key;
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
        return '[MC: ' . $this->connector . '] ' . $message;
    }
}
