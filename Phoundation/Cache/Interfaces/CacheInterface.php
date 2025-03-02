<?php

namespace Phoundation\Cache\Interfaces;

interface CacheInterface
{
    /**
     * Clears all cache data if it has not yet been done
     *
     * @param bool $force
     *
     * @return bool
     * @todo Implement more
     */
    public function clear(bool $force = false): bool;

    /**
     * Delete the specified page from cache
     *
     * @param string $key
     *
     * @return void
     */
    public function delete(string $key): void;

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
     * @return mixed
     */
    public function get(string $key, ?callable $callback = null): mixed;

    /**
     * Write the specified page to cache
     *
     * @param mixed  $data
     * @param string $key
     *
     * @return static
     */
    public function set(mixed $data, string $key): static;


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
