<?php

declare(strict_types=1);

namespace Phoundation\Core\Libraries\Interfaces;

use Phoundation\Core\Libraries\Updates;

/**
 * Updates class
 *
 * This is the prototype Init class that contains the basic methods for all other Init classes in all other libraries
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */
interface UpdatesInterface
{
    /**
     * Returns the file for this library
     *
     * @return string
     */
    public function getFile(): string;


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
