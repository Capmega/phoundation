<?php

/**
 * Class Memcached
 *
 * This is the default memcached driver object
 *
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
use Phoundation\Databases\Exception\MemcachedException;
use Phoundation\Databases\Interfaces\MemcachedInterface;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Strings;
use Phoundation\Web\Http\Url;
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
     * @param ConnectorInterface $o_connector
     */
    public function __construct(ConnectorInterface $o_connector)
    {
        // Ensure PHP has memcached support and that the specified connector is a memcached connector
        static::checkDriver();
        $o_connector->checkDriver('memcached');

        // Get instance information and connect to memcached servers
        $this->setConnectorObject($o_connector)
             ->connect();
    }


    /**
     *
     *
     * @param ConnectorInterface|null $o_connector
     * @param string|null             $database
     *
     * @return $this
     */
    public function setConnectorObject(?ConnectorInterface $o_connector = null, ?string $database = null): static {
        $this->__setConnectorObject($o_connector, $database)
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
                Log::warning($this->log(tr('Failed to connect to memcached server ":server" configured in path ":directory"', [
                    ':server'    => $server,
                    ':directory' => 'databases.connectors.' . $this->connector . '.servers.' . $weight,
                ])));

                Log::error($e);
                $failed++;
            }
        }

        if (isset($e) or $failed) {
            // We haven't been able to connect to any memcached server at all!
            Log::warning($this->log(tr('Failed to connect to any memcached server')), 10);

            Incident::new()
                    ->setException(isset_get($e))
                    ->setUrl(Url::new('security/incidents.html')->makeWww())
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
     * Returns the specified data to the specified key (and optionally the specified namespace)
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback An optional callback function for read-through caching
     * @param int                   $flags          Currently supports Memcached::GET_EXTENDED
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.get.php
     */
    public function get(string|float|int|null $key, ?callable $cache_callback = null, int $flags = 0): mixed
    {
        $value = $this->memcached->get($key, $cache_callback, $flags);

        Log::success($this->log(tr('Read ":bytes" bytes to memcached for key ":key"', [
            ':key'   => $key,
            ':bytes' => (is_scalar($value) ? strlen((string) $value) : count($value)),
        ])), 3);

        return get_null($value);
    }


    /**
     * Sets the specified key to the specified value on the memcached server(s)
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(mixed $value, string|float|int|null $key, ?int $expires = null): static
    {
        if (is_bool($value)) {
            throw new MemcachedException($this->log(tr('Cannot set boolean values in memcached for key ":key"', [
                ':key' => $key,
            ])));
        }

        $result = $this->memcached->set($key, $value, $expires ?? $this->configuration['expires']);

        if ($result) {
            Log::success($this->log(tr('Wrote ":bytes" bytes to memcached for key ":key"', [
                ':key'   => $key,
                ':bytes' => (is_scalar($value) ? strlen((string) $value) : count($value)),
            ])), 3);

            return $this;
        }

        throw new MemcachedException($this->log(tr('Setting value for key ":key" failed with code ":code" and message ":message"', [
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
            throw new MemcachedException($this->log(tr('Cannot add boolean values in memcached for key ":key"', [
                ':key' => $key,
            ])));
        }

        $result = $this->memcached->add($key, $value, $expires ?? $this->configuration['expires']);

        if ($result) {
            Log::success($this->log(tr('Wrote ":bytes" bytes to memcached for key ":key"', [
                ':key'   => $key,
                ':bytes' => (is_scalar($value) ? strlen((string) $value) : count($value)),
            ])), 3);

            return $value;
        }

        throw new MemcachedException($this->log(tr('Adding value for key ":key" failed with code ":code" and message ":message"', [
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
            throw new MemcachedException($this->log(tr('Cannot replace keys with boolean values in memcached for key ":key"', [
                ':key' => $key,
            ])));
        }

        $result = $this->memcached->replace($key, $value, $expires ?? $this->configuration['expires']);

        if ($result) {
            Log::success($this->log(tr('Wrote ":bytes" bytes to memcached for key ":key"', [
                ':key'   => $key,
                ':bytes' => (is_scalar($value) ? strlen((string) $value) : count($value)),
            ])), 3);

            return $value;
        }

        throw new MemcachedException($this->log(tr('Replacing value for key ":key" failed with code ":code" and message ":message"', [
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Deletes the specified key from the memcached server(s)
     *
     * @param string|float|int|null $key
     * @param int|null              $time
     *
     * @return void
     */
    public function delete(string|float|int|null $key, ?int $time = null): void
    {
        $result = $this->memcached->delete($key, $time);

        if ($result) {
            Log::success($this->log(tr('Deleted key ":key"', [
                ':key' => $key,
            ])), 3);
        }

        throw new MemcachedException($this->log(tr('Deleting key ":key" failed with code ":code" and message ":message"', [
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
            Log::success($this->log(tr('Flushed all data with delay ":delay"', [
                ':delay' => $delay,
            ])), 3);

            return $this;
        }

        throw new MemcachedException($this->log(tr('Flushing all data failed with code ":code" and message ":message"', [
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
        $result = $this->memcached->increment($key, $offset, $initial_value, $expiry);

        if ($result) {
            Log::success($this->log(tr('Incremented key ":key" by ":offset"', [
                ':key'    => $key,
                ':offset' => $offset,
            ])), 3);

            return $this;
        }

        throw new MemcachedException($this->log(tr('Incrementing value for key ":key" failed with code ":code" and message ":message"', [
            ':code'    => $this->memcached->getResultCode(),
            ':message' => $this->memcached->getResultMessage()
        ])));
    }


    /**
     * Return statistics for this memcached instance
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = $this->memcached->getStats();

        if ($stats === false) {
            throw new MemcachedException($this->log(tr('Failed to return statistics with code ":code" and message ":message"', [
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
            throw new MemcachedException($this->log(tr('Failed to return all keys with code ":code" and message ":message"', [
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
            throw new PhpModuleNotAvailableException(tr('The PHP module "memcached" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php-memcached; sudo phpenmod memcached" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
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
        return '[MC ' . $this->connector . ']' . $message;
    }
}
