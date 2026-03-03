<?php

declare(strict_types=1);

namespace Phoundation\Core\Hooks\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
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
     * Returns the PhoFileInterface file object for the specified hook
     *
     * @param string|null $hook
     *
     * @return PhoFileInterface
     */
    public function getFileObject(?string $hook = null): PhoFileInterface;


    /**
     * Attempts to execute the specified hooks
     *
     * Reminders:
     * * ROOT is the root directory of this project
     * * All hooks classes start in ROOT/hooks (symlink to ROOT/data/system/cache/hooks)
     * * A hook class is a partial path, so it may contain /
     * * A hook class may (for example) be accounts/users
     * * A hook itself is a filename may only contain letters, numbers, and dashes and may (optionally, and not recommended for clarity) end with .php
     * * The hook "notify" in the class "accounts/users" will execute the hook ROOT/data/system/cache/hooks/accounts/users/notify.php IF the file exists
     *
     * @param string|null $hook             The hook filename to execute. To execute, the filename must be in the directory for the specified class.
     * @param array|null  $arguments [null] The arguments to pass along to the hook, if it exists
     *
     * @return mixed
     */
    public function execute(?string $hook, ?array $arguments = null): mixed;

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
