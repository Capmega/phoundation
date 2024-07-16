<?php

declare(strict_types=1);

namespace Phoundation\Core\Libraries\Interfaces;

use Phoundation\Core\Libraries\Updates;
use Phoundation\Filesystem\Interfaces\FsFileInterface;

interface UpdatesInterface
{
    /**
     * Returns the file for this library
     *
     * @return FsFileInterface
     */
    public function getFile(): FsFileInterface;


    /**
     * Returns the current code version for this library
     *
     * @return string
     */
    public function getCodeVersion(): string;


    /**
     * Returns the current database version for this library
     *
     * @return string|null
     */
    public function getDatabaseVersion(): ?string;


    /**
     * Returns if the specified database version exists for this library, or not
     *
     * @param string|int $version
     *
     * @return bool
     */
    public function databaseVersionExists(string|int $version): bool;


    /**
     * Returns the next version available for execution, if any
     *
     * @param string|null $version
     *
     * @return string|null The next version available for init execution, or NULL if none.
     */
    public function getNextInitVersion(?string $version = null): ?string;


    /**
     * Registers the specified version and the function containing all tasks that should be executed to get to that
     * version
     *
     * @param string   $version
     * @param callable $function
     *
     * @return Updates
     */
    public function addUpdate(string $version, callable $function): Updates;


    /**
     * Update to the specified version
     *
     * @param string|null $comments
     *
     * @return string|null The next version available for this init, or NULL if none are available
     */
    public function init(?string $comments = null): ?string;


    /**
     * Execute the post init files
     *
     * @param string|null $comments
     *
     * @return bool True if any post_* files were executed
     */
    public function initPost(?string $comments = null): bool;


    /**
     * Returns the library version
     *
     * @return string
     */
    public function version(): string;


    /**
     * Adds the list of updates
     *
     * @return void
     */
    public function updates(): void;
}
