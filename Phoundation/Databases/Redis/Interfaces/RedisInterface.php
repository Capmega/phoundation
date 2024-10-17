<?php

namespace Phoundation\Databases\Redis\Interfaces;

use Phoundation\Databases\Exception\RedisException;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\FsFileInterface;

interface RedisInterface
{
    public function close(): static;


    /**
     * Returns a value for the specified key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key): mixed;


    /**
     * Sets a key as a specific string value
     *
     * @param string|array $value
     * @param mixed        $key
     * @param int|null     $timeout
     *
     * @return static
     */
    public function set(mixed $value, mixed $key, ?int $timeout = null): static;


    public function getDatabase(): int;


    /**
     * Sets the database that this Redis object will interface with
     *
     * @param int $database
     *
     * @return $this
     * @throws OutOfBoundsException|RedisException
     */
    public function setDatabase(int $database): static;


    /**
     * Drop a queue from the Redis database
     *
     * @param string $queue
     *
     * @return Redis
     */
    public function dropQueue(string $queue): static;


    /**
     * Drop a queue from the Redis database
     *
     * @param string $key
     *
     * @return Redis
     */
    public function delValue(string $key): static;


    /**
     * Pushes the specified value to the beginning of the queue (on the left) to the queue
     *
     * @param mixed  $value
     * @param string $queue
     *
     * @return $this
     */
    public function push(mixed $value, string $queue): static;


    /**
     * Pops the last value off the queue (from the right) from the queue and returns it
     *
     * @param string   $queue
     * @param int|null $timeout
     *
     * @return mixed
     */
    public function pop(string $queue, ?int $timeout = null): mixed;


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static;


    /**
     * Check if a queue exists
     *
     * @param string $queue
     *
     * @return bool
     */
    public function queueExists(string $queue): bool;


    /**
     * Check if a key exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function keyExists(string $key): bool;


    /**
     * Clears all queues and keys from database
     *
     * @return static
     */
    public function clearAll(): static;


    /**
     * Return an array which lists all values in the Redis connector stored at the specified queue in the range
     * [start, end]. start and stop are interpreted as indices: 0 the first element, 1 the second ... -1 the last
     * element, -2 the penultimate ...
     *
     * @param string $queue
     * @param int    $start
     * @param int    $end
     *
     * @return array|null
     */
    public function getQueue(string $queue, int $start = 0, int $end = -1): ?array;


    /**
     * Peek at the first (or index-specified) element in a queue without removing it
     *
     * @param string $queue
     * @param int    $index 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @return bool|mixed
     */
    public function queuePeek(string $queue, int $index = 0): mixed;


    /**
     * Takes the queue and clears all values from it
     *
     * @return $this
     */
    public function clearQueue(string $queue): static;


    /**
     * Returns the length of the specified queue
     *
     * @param string $queue
     *
     * @return int
     */
    public function getQueueLength(string $queue): int;


    /**
     * Pings connection
     *
     * @return bool|string
     */
    public function ping(): bool|string;


    /**
     * Import the data dump from the specified file into the current corrected Redis database
     *
     * @param FsFileInterface $file
     *
     * @return $this
     */
    public function import(FsFileInterface $file): static;


    /**
     * Export the current Redis database into a dump file
     *
     * @param FsFileInterface $file
     *
     * @return $this
     */
    public function export(FsFileInterface $file): static;
}