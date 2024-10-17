<?php

namespace Phoundation\Databases\Redis\Interfaces;

interface RedisQueueInterface
{
    /**
     * Pushes the specified value to the end of the Redis queue
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function push(mixed $value): static;


    /**
     * Pops a variable from the beginning of the queue
     *
     * @return mixed
     */
    public function pop(): mixed;

    /**
     * Returns the queue name
     *
     * @return string
     */
    public function getName(): string;

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
    public function getQueue(?int $start = 0, ?int $end = -1): ?array;

    /**
     * Peek at the first (or index-specified) element in a list without removing it
     *
     * @param int|null $index 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @return bool|mixed
     */
    public function peek(?int $index = 0): mixed;

    /**
     * Returns the count of the current list
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Takes the queue and clears all values from it
     *
     * @return $this
     */
    public function clear(): static;

    /**
     * Drops the current queue
     *
     * @return $this
     */
    public function drop(): static;

    /**
     * Get the authentication information on the connection, if any.
     *
     * @return bool|string
     */
    public function ping(): bool|string;
}