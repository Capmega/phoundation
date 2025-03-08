<?php

namespace Phoundation\Cache\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Interfaces\PoaInterface;
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
     * @return PoaInterface|array|string|float|int|null
     */
    public function get(string $key, ?callable $callback = null): PoaInterface|array|string|float|int|null;

    /**
     * Write the specified page to cache
     *
     * @param PoaInterface|array|string|float|int|null $value
     * @param Stringable|string|float|int|null         $key
     *
     * @return static
     */
    public function set(PoaInterface|array|string|float|int|null $value, Stringable|string|float|int|null $key): static;


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
    public function systemAutoGitCommit(?string $section = null, ?bool $auto_commit = null, ?bool $signed = null, ?string $message = null): void;
}
