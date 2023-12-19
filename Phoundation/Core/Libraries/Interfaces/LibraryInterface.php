<?php

namespace Phoundation\Core\Libraries\Interfaces;


/**
 * Library class
 *
 * This library can initialize all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
interface LibraryInterface
{
    /**
     * Initialize this library
     *
     * @param string|null $comments
     * @return bool True if the library had updates applied
     */
    public function init(?string $comments): bool;

    /**
     * Executes POST init files for this library
     *
     * @param string|null $comments
     * @return bool True if the library had updates applied
     */
    public function initPost(?string $comments): bool;

    /**
     * Returns the type of library; system or plugin
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Returns true if the library is a system library
     *
     * @return bool
     */
    public function isSystem(): bool;

    /**
     * Returns true if the library is a plugin library
     *
     * @return bool
     */
    public function isPlugin(): bool;

    /**
     * Returns true if the library is a template library
     *
     * @return bool
     */
    public function isTemplate(): bool;

    /**
     * Returns the library path
     *
     * @return string
     */
    public function getDirectory(): string;

    /**
     * Returns the library name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns the code version for this library
     *
     * @return string|null
     */
    public function getCodeVersion(): ?string;

    /**
     * Returns the database version for this library
     *
     * @return string|null
     */
    public function getDatabaseVersion(): ?string;

    /**
     * Returns the database version for this library
     *
     * @return string|null
     */
    public function getNextInitVersion(): ?string;

    /**
     * Returns the size of all files in this library in bytes
     *
     * @return int
     */
    public function getSize(): int;

    /**
     * Returns the version for this library
     *
     * @return string|null
     */
    public function getVersion(): ?string;

    /**
     * Returns the description for this library
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Returns the PhpStatistics object for this library
     *
     * @return array
     */
    public function getPhpStatistics(): array;

    /**
     * Update the version registration for this version to be the specified version
     *
     * @param string $version
     * @param string|null $comments
     * @return void
     */
    public function setVersion(string $version, ?string $comments = null): void;

}
