<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\FsFiles;
use Phoundation\Filesystem\FsRestrictions;
use ReturnTypeWillChange;
use Stringable;

/**
 * Interface FsFilesInterface
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */
interface FsFilesInterface extends IteratorInterface
{
    /**
     * Returns the server restrictions
     *
     * @return FsRestrictionsInterface
     */
    public function getRestrictions(): FsRestrictionsInterface;


    /**
     * Sets the server and filesystem restrictions for this FsFileFileInterface object
     *
     * @param FsRestrictionsInterface|array|string|null $restrictions The file restrictions to apply to this object
     * @param bool                                      $write        If $restrictions is not specified as a FsRestrictions
     *                                                              class, but as a path string, or array of path
     *                                                              strings, then this method will convert that into a
     *                                                              FsRestrictions object and this is the $write modifier
     *                                                              for that object
     * @param string|null                               $label        If $restrictions is not specified as a FsRestrictions
     *                                                              class, but as a path string, or array of path
     *                                                              strings, then this method will convert that into a
     *                                                              FsRestrictions object and this is the $label modifier
     *                                                              for that object
     */
    public function setRestrictions(FsRestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static;


    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return FsRestrictionsInterface
     */
    public function ensureRestrictions(?FsRestrictionsInterface $restrictions): FsRestrictionsInterface;


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @return FsPathInterface|null
     */
    public function getParent(): ?FsPathInterface;


    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @param FsPathInterface|null $parent
     *
     * @return FsFiles
     */
    public function setParent(?FsPathInterface $parent): static;


    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param Stringable|string   $target
     * @param FsRestrictions|null $restrictions
     *
     * @return $this
     */
    public function move(Stringable|string $target, ?FsRestrictionsInterface $restrictions = null): static;


    /**
     * Copy all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param Stringable|string            $target
     * @param FsRestrictionsInterface|null $restrictions
     * @param callable|null                $callback
     *
     * @return $this
     */
    public function copy(Stringable|string $target, ?FsRestrictionsInterface $restrictions = null, ?callable $callback = null, mixed $context = null): static;


    /**
     * Returns the current file
     *
     * @return FsPathInterface
     */
    #[ReturnTypeWillChange] public function current(): FsPathInterface;


    /**
     * Returns if the current pointer is valid or not
     *
     * Since FsFiles classes skip the "." and ".." directories, valid will ensure these get skipped too
     *
     * @return bool
     */
    public function valid(): bool;
}