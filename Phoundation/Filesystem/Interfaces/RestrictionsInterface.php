<?php

namespace Phoundation\Filesystem\Interfaces;


use Phoundation\Filesystem\Restrictions;
use Stringable;

/**
 * Restrictions class
 *
 * This class manages file access restrictions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
interface RestrictionsInterface
{
    /**
     * Returns a restrictions object with parent paths for all paths in this restrictions object
     *
     * This is useful for the Path object where one will want to be able to access or create the parent path of the file
     * that needs to be accessed
     *
     * @return Restrictions
     */
    public function getParent(): Restrictions;

    /**
     * Returns a restrictions object with the current path and the specified child path attached
     *
     * This is useful when we want more strict restrictions
     *
     * @param string|array $child_paths
     * @param bool|null $write
     * @return Restrictions
     */
    public function getChild(string|array $child_paths, ?bool $write = null): Restrictions;

    /**
     * Clear all paths for this restriction
     *
     * @return static
     */
    public function clearPaths(): static;

    /**
     * Set all paths for this restriction
     *
     * @param Stringable|array|string $paths
     * @param bool $write
     * @return static
     */
    public function setPaths(Stringable|array|string $paths, bool $write = false): static;

    /**
     * Set all paths for this restriction
     *
     * @param Stringable|array|string $paths
     * @param bool $write
     * @return static
     */
    public function addPaths(Stringable|array|string $paths, bool $write = false): static;

    /**
     * Add new path for this restriction
     *
     * @param Stringable|string $path
     * @param bool $write
     * @return static
     */
    public function addPath(Stringable|string $path, bool $write = false): static;

    /**
     * Returns all paths for this restriction
     *
     * @return array
     */
    public function getPaths(): array;

    /**
     * Sets the label for this restriction
     *
     * @param string|null $label
     * @return static
     */
    public function setLabel(?string $label): static;

    /**
     * Sets the restrictions label only if the specified label is not empty, and this object's label is NULL or "system"
     *
     * @param string|null $label
     * @return $this
     */
    public function ensureLabel(?string $label): static;

    /**
     * Returns the label for this restriction
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * @param string|array $patterns
     * @param bool $write
     * @return void
     */
    public function check(string|array &$patterns, bool $write): void;

    /**
     * Returns system general file access restrictions
     *
     * @return RestrictionsInterface
     */
    public static function getSystem(): RestrictionsInterface;
}
