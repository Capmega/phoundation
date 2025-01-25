<?php

/**
 * Class RedisQueue
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Redis;

use Phoundation\Databases\Redis\Interfaces\RedisInterface;
use Phoundation\Databases\Redis\Interfaces\RedisQueueInterface;


class RedisQueueCore implements RedisQueueInterface
{
    /**
     * Phoundation Redis interface
     *
     * @var RedisInterface $redis
     */
    protected RedisInterface $redis;

    /**
     * The Redis list used by this queue
     *
     * @var string $queue
     */
    protected string $queue;


    /**
     * Pushes the specified value to the end of the Redis queue
     *
     * @param mixed $value
     *
     * @return static
     */
    public function push(mixed $value): static
    {
        $this->redis->push($value, $this->queue);
        return $this;
    }


    /**
     * Pops a variable from the beginning of the queue
     *
     * @return mixed
     */
    public function pop(): mixed
    {
        return $this->redis->pop($this->queue);
    }


    /**
     * Returns the queue name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->queue;
    }


    /**
     * Return an array which lists all values in the Redis connector stored at the specified list in the range
     * [start, end]. start and stop are interpreted as indices: 0 the first element, 1 the second ... -1 the last
     * element, -2 the penultimate ...
     *
     * @param int|null $start
     * @param int|null $end
     *
     * @return array|null
     */
    public function getQueue(?int $start = 0, ?int $end = -1): ?array
    {
        return $this->redis->getQueue($this->queue, $start, $end);
    }


    /**
     * Peek at the first (or index-specified) element in a list without removing it
     *
     * @param int|null $index 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @return bool|mixed
     */
    public function peek(?int $index = 0): mixed
    {
        return $this->redis->queuePeek($this->queue, $index);
    }


    /**
     * Returns the count of the current list
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->redis->getQueueCount($this->queue);
    }


    /**
     * Takes the queue and clears all values from it
     *
     * @return static
     */
    public function clear(): static
    {
        $this->redis->clearQueue($this->queue);
        return $this;
    }


    /**
     * Drops the current queue
     *
     * @return static
     */
    public function drop(): static
    {
        $this->redis->dropQueue($this->queue);
        return $this;
    }


    /**
     * Get the authentication information on the connection, if any.
     *
     * @return bool|string
     */
    public function ping(): bool|string
    {
        return $this->redis->ping();
    }


    /**
     * Returns the database of this RedisQueue's redis object
     *
     * @return int
     */
    public function showDatabase(): int
    {
        return $this->redis->getDatabase();
    }
}
