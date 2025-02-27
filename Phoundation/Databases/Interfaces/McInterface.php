<?php

namespace Phoundation\Databases\Interfaces;

use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Mc;

interface McInterface extends DatastoreInterface
{
    /**
     *
     *
     * @param ConnectorInterface|null $o_connector
     * @param string|null             $database
     *
     * @return $this
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
     * Returns the specified data to the specified key (and optionally the specified namespace)
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback An optional callback function for read-through caching
     * @param int                   $flags          Currently supports Memcached::GET_EXTENDED
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.get.php
     */
    public function get(string|float|int|null $key, ?callable $cache_callback = null, int $flags = 0): mixed;


    /**
     * Sets the specified key to the specified value on the memcached server(s)
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     * @param int                   $udf_flags
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(mixed $value, string|float|int|null $key, ?int $expires = null, int $udf_flags = 0): static;


    /**
     * Adds the specified key to the memcached server(s)
     *
     * Requires the specified key to NOT exist, and will cause an exception if it does
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     * @param int                   $udf_flags
     *
     * @return Mc
     */
    public function add(mixed $value, string|float|int|null $key, ?int $expires = null, int $udf_flags = 0): static;


    /**
     * Replaces the specified key on hte memcached server(s)
     *
     * Requires the specified key to exist, and will cause an exception if it does not
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param int|null              $expires
     * @param int                   $udf_flags
     *
     * @return Mc
     */
    public function replace(mixed $value, string|float|int|null $key, ?int $expires = null, int $udf_flags = 0): static;


    /**
     * Deletes the specified key from the memcached server(s)
     *
     * @param string|float|int|null $key
     * @param int|null              $time
     *
     * @return void
     */
    public function delete(string|float|int|null $key, ?int $time = null): void;


    /**
     * Flush all cached memcache data
     *
     * @param int $delay
     *
     * @return static
     */
    public function flush(int $delay = 0): static;


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
}
