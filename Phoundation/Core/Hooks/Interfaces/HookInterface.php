<?php

declare(strict_types=1);

namespace Phoundation\Core\Hooks\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Stringable;


interface HookInterface extends ArrayableInterface, Stringable
{
    /**
     * Returns true if the specified hook exists, false otherwise
     *
     * @param string $hook
     *
     * @return mixed
     */
    public function exists(string $hook): bool;

    /**
     * Returns the hook class
     *
     * @return string
     */
    public function getClass(): string;


    /**
     * Returns the FsFileInterface file object for the specified hook
     *
     * @param string|null $hook
     *
     * @return mixed
     */
    public function getFile(?string $hook = null): FsFileInterface;

    /**
     * Attempts to execute the specified hooks
     *
     * @param string     $hook
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function execute(string $hook, ?array $arguments = []): mixed;

    /**
     * Returns the specified parameters key, or exception if it does not exist
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Returns all source argumentS
     *
     * @return mixed
     */
    public function getArguments(): array;

    /**
     * Returns the requested source argument
     *
     * @param string|int $key
     *
     * @return mixed
     */
    public function getArgument(string|int $key): mixed;
}
