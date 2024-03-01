<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Files;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use ReturnTypeWillChange;
use Stringable;


/**
 * Interface FilesInterface
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
interface FilesInterface extends IteratorInterface
{
    /**
     * Returns the server restrictions
     *
     * @return RestrictionsInterface
     */
    public function getRestrictions(): RestrictionsInterface;

    /**
     * Sets the server and filesystem restrictions for this File object
     *
     * @param RestrictionsInterface|array|string|null $restrictions  The file restrictions to apply to this object
     * @param bool $write                                   If $restrictions is not specified as a Restrictions class,
     *                                                      but as a path string, or array of path strings, then this
     *                                                      method will convert that into a Restrictions object and this
     *                                                      is the $write modifier for that object
     * @param string|null $label                            If $restrictions is not specified as a Restrictions class,
     *                                                      but as a path string, or array of path strings, then this
     *                                                      method will convert that into a Restrictions object and this
     *                                                      is the $label modifier for that object
     */
    public function setRestrictions(RestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static;

    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param RestrictionsInterface|null $restrictions
     * @return RestrictionsInterface
     */
    public function ensureRestrictions(?RestrictionsInterface $restrictions): RestrictionsInterface;

    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @return PathInterface|null
     */
    public function getParent(): ?PathInterface;

    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @param PathInterface|null $parent
     * @return Files
     */
    public function setParent(?PathInterface $parent): static;

    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     * @return $this
     */
    public function move(Stringable|string $target, ?RestrictionsInterface $restrictions = null): static;

    /**
     * Copy all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     * @param Stringable|string $target
     * @param RestrictionsInterface|null $restrictions
     * @param callable|null $callback
     * @return $this
     */
    public function copy(Stringable|string $target, ?RestrictionsInterface $restrictions = null, ?callable $callback = null): static;

    /**
     * Returns the current file
     *
     * @return PathInterface
     */
    #[ReturnTypeWillChange] public function current(): PathInterface;

    /**
     * Returns if the current pointer is valid or not
     *
     * Since Files classes skip the "." and ".." directories, valid will ensure these get skipped too
     *
     * @return bool
     */
    public function valid(): bool;
}
