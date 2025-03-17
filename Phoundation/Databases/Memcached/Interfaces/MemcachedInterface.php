<?php

namespace Phoundation\Databases\Memcached\Interfaces;

use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Interfaces\DatastoreInterface;
use Phoundation\Databases\Memcached\Memcached;
use Stringable;

interface MemcachedInterface extends DatastoreInterface
{
    /**
     *
     *
     * @param ConnectorInterface|null $o_connector
     * @param string|null             $database
     *
     * @return static
     */
    public function setConnectorObject(?ConnectorInterface $o_connector = null, ?string $database = null): static;


    /**
     * Return the active Mc connections
     *
     * @return array
     */
    public function getActiveConnections(): array;


    /**
     * Return the configured Mc connections
     *
     * @return array
     */
    public function getConfiguredConnections(): array;


    /**
     * Return configuration data.
     *
     * If the $key is specified, only the configuration data for that specified key will be returned
     *
     * @param string|int|null $key
     *
     * @return array|string|null
     */
    public function getConfiguration(string|int|null $key = null): array|string|null;


    /**
     * Returns the value for the specified key
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback An optional callback function for read-through caching
     * @param int                   $flags          Currently supports Memcached::GET_EXTENDED
     *
     * @return array|string|float|int|null
     * @see https://www.php.net/manual/en/memcached.get.php
     */
    public function get(string|float|int|null $key, ?callable $cache_callback = null, int $flags = 0): array|string|float|int|null;


    /**
     * Sets the value for the specified key on the memcached server(s)
     *
     * @param array|string|float|int|null      $value
     * @param Stringable|string|float|int|null $key
     * @param int|null                         $expires
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(array|string|float|int|null $value, Stringable|string|float|int|null $key, ?int $expires = null): static;


    /**
     * Adds the specified key to the memcached server(s)
     *
     * Requires the specified key to NOT exist, and will cause an exception if it does
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     *
     * @return Memcached
     */
    public function add(mixed $value, string|float|int|null $key, ?int $expires = null): static;


    /**
     * Replaces the specified key on hte memcached server(s)
     *
     * Requires the specified key to exist, and will cause an exception if it does not
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     *
     * @return Memcached
     */
    public function replace(mixed $value, string|float|int|null $key, ?int $expires = null): static;


    /**
     * Deletes the specified key from the memcached server(s)
     *
     * @param string|float|int|null $key
     * @param int                   $time
     *
     * @return static
     */
    public function delete(string|float|int|null $key, int $time = 0): static;


    /**
     * Flush all cached memcache data
     *
     * @param int $delay
     *
     * @return static
     */
    public function clear(int $delay = 0): static;


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
    public function increment(string|float|int|null $key, int $offset = 1, int $initial_value = 0, int $expiry = 0): static;


    /**
     * Return statistics for this memcached instance
     *
     * @return array
     */
    public function getStatistics(): array;


    /**
     * Return statistics for this memcached instance
     *
     * @return array
     */
    public function getAllKeys(): array;


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static;

    /**
     * Returns true if the specified key exists or not
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback
     * @param int                   $flags
     *
     * @return bool
     */
    public function exists(string|float|int|null $key, ?callable $cache_callback = null, int $flags = 0): bool;
}
