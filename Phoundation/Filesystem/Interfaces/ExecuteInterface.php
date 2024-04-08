<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Exception\OutOfBoundsException;

/**
 * interface ExecuteInterface
 *
 * This library contains various filesystem file related functions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */
interface ExecuteInterface extends DirectoryInterface
{
    /**
     * Returns the extensions that are blacklisted
     *
     * @return array
     */
    public function getBlacklistExtensions(): array;


    /**
     * Sets the extensions that are blacklisted
     *
     * @param string|array|null $blacklist_extensions
     *
     * @return static
     */
    public function setBlacklistExtensions(array|string|null $blacklist_extensions): static;


    /**
     * Returns the extensions that are whitelisted
     *
     * @return array
     */
    public function getWhitelistExtensions(): array;


    /**
     * Sets the extensions that are whitelisted
     *
     * @param string|array|null $whitelist_extensions
     *
     * @return static
     */
    public function setWhitelistExtensions(array|string|null $whitelist_extensions): static;


    /**
     * Returns the path mode that will be set for each path
     *
     * @return string|int|null
     */
    public function getMode(): string|int|null;


    /**
     * Sets the path mode that will be set for each path
     *
     * @param string|int|null $mode
     *
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setMode(string|int|null $mode): static;


    /**
     * Returns if exceptions will be ignored during the processing of multiple files
     *
     * @return bool
     */
    public function getIgnoreExceptions(): bool;


    /**
     * Sets if exceptions will be ignored during the processing of multiple files
     *
     * @param bool $ignore_exceptions
     *
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setIgnoreExceptions(bool $ignore_exceptions): static;


    /**
     * Returns if symlinks should be processed
     *
     * @return bool
     */
    public function getFollowSymlinks(): bool;


    /**
     * Sets if symlinks should be processed
     *
     * @param bool $follow_symlinks
     *
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setFollowSymlinks(bool $follow_symlinks): static;


    /**
     * Returns if hidden file should be processed
     *
     * @return bool
     */
    public function getFollowHidden(): bool;


    /**
     * Sets if hidden file should be processed
     *
     * @param bool $follow_hidden
     *
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setFollowHidden(bool $follow_hidden): static;


    /**
     * Returns the path that will be skipped
     *
     * @return array
     */
    public function getSkipDirectories(): array;


    /**
     * Clears the paths that will be skipped
     *
     * @return static
     */
    public function clearSkipDirectories(): static;


    /**
     * Sets the paths that will be skipped
     *
     * @param string|array $directories
     *
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function setSkipDirectories(string|array $directories): static;


    /**
     * Adds the paths that will be skipped
     *
     * @param string|array $directories
     *
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipDirectories(string|array $directories): static;


    /**
     * Sets the path that will be skipped
     *
     * @param string $directory
     *
     * @return static
     * @throws OutOfBoundsException if the specified threshold is invalid.
     */
    public function addSkipDirectory(string $directory): static;


    /**
     * Returns if the object will recurse or not
     *
     * @return bool
     */
    public function getRecurse(): bool;


    /**
     * Returns if the object will recurse or not
     *
     * @param bool $recurse
     *
     * @return static
     */
    public function setRecurse(bool $recurse): static;


    /**
     * Execute the callback function on each file in the specified path
     *
     * @param callable $callback
     *
     * @return void
     */
    public function onDirectoryOnly(callable $callback): void;


    /**
     * Execute the callback function on each file in the specified path
     *
     * @param callable $callback
     *
     * @return int
     */
    public function onFiles(callable $callback): int;
}
