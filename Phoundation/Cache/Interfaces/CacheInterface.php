<?php

namespace Phoundation\Cache\Interfaces;

use Phoundation\Data\Interfaces\PoadInterface;
use Stringable;

interface CacheInterface
{
    /**
     * Clears all cache data if it has not yet been done
     *
     * @param bool $force
     *
     * @return static
     */
    public function clear(bool $force = false): static;

    /**
     * Delete the specified page from cache
     *
     * @param string $key
     *
     * @return static
     */
    public function delete(string $key): static;

    /**
     * Returns true if in this process the cache has been cleared
     *
     * @return bool
     */
    public function hasBeenCleared(): bool;


    /**
     * Read the specified page from cache.
     *
     * @note: NULL will be returned if the specified hash does not exist in cache
     *
     * @param string        $key
     * @param callable|null $callback
     *
     * @return PoadInterface|array|string|float|int|null
     */
    public function getOrGenerate(string $key, ?callable $callback = null): PoadInterface|array|string|float|int|null;


    /**
     * Write the specified page to cache
     *
     * @param PoadInterface|array|string|float|int|null $value
     * @param Stringable|string|float|int|null          $key
     *
     * @return static
     */
    public function set(PoadInterface|array|string|float|int|null $value, Stringable|string|float|int|null $key): static;
}
