<?php

declare(strict_types=1);

namespace Phoundation\Databases\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoFileInterface;


interface DatabaseInterface extends DatastoreInterface
{
    /**
     * Returns true if this database interface is connected to a database server
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Connects to this database and executes a test query
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function import(PhoFileInterface $file): static;


    /**
     * Connects to this database and executes a test query
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function export(PhoFileInterface $file): static;

    /**
     * Tests the database connection
     *
     * @return static
     */
    public function test(): static;

    /**
     * Returns the specified data to the specified key (and optionally the specified namespace)
     *
     * @param string|float|int|null $key
     * @param callable|null         $cache_callback An optional callback function for read-through caching
     *
     * @return mixed
     */
    public function get(string|float|int|null $key, ?callable $cache_callback): mixed;

    /**
     * Sets the specified key to the specified value on the memcached server(s)
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     *
     * @return mixed
     * @see https://www.php.net/manual/en/memcached.set.php
     */
    public function set(mixed $value, string|float|int|null $key): static;
}
