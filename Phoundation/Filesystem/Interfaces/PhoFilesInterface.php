<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\PhoFiles;
use Phoundation\Filesystem\PhoRestrictions;
use ReturnTypeWillChange;
use Stringable;

interface PhoFilesInterface extends IteratorInterface
{
    /**
     * Returns the server restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface;

    /**
     * Sets the server and filesystem restrictions for this PhoFile object
     *
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions The file restrictions to apply to this object
     * @param bool                                       $write          If $restrictions is not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and
     *                                                                this is the $write modifier for that object
     * @param string|null                                $label          If $restrictions is not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and
     *                                                                this is the $label modifier for that object
     */
    public function setRestrictionsObject(PhoRestrictionsInterface|array|string|null $o_restrictions = null, bool $write = false, ?string $label = null): static;

    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param PhoRestrictionsInterface|null $o_restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function ensureRestrictionsObject(?PhoRestrictionsInterface $o_restrictions): PhoRestrictionsInterface;

    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @return PhoDirectoryInterface|null
     */
    public function getParentDirectoryObject(): ?PhoDirectoryInterface;

    /**
     * Returns the parent Path (if available) that contains these files
     *
     * @param PhoDirectoryInterface|null $o_parent_directory
     *
     * @return PhoFiles
     */
    public function setParentDirectoryObject(?PhoDirectoryInterface $o_parent_directory): static;

    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param PhoDirectoryInterface         $o_target
     * @param PhoRestrictionsInterface|null $o_restrictions
     *
     * @return static
     */
    public function move(PhoDirectoryInterface $o_target, ?PhoRestrictionsInterface $o_restrictions = null): static;


    /**
     * Copy all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     *
     * @param PhoPathInterface|string       $target
     * @param PhoRestrictionsInterface|null $o_restrictions
     * @param callable|null                 $callback
     * @param mixed|null                    $context
     *
     * @return static
     */
    public function copy(PhoPathInterface|string $target, ?PhoRestrictionsInterface $o_restrictions = null, ?callable $callback = null, mixed $context = null): static;

    /**
     * Returns the current file
     *
     * @return PhoPathInterface|null
     */
    #[ReturnTypeWillChange] public function current(): ?PhoPathInterface;

    /**
     * Returns if the current pointer is valid or not
     *
     * Since PhoFiles classes skip the "." and ".." directories, valid will ensure these get skipped too
     *
     * @return bool
     */
    public function valid(): bool;


    /**
     * Returns all files that match the specified mimetype
     *
     * @param string $mimetype
     * @param bool   $remove
     *
     * @return static
     */
    public function getFilesWithMimetype(string $mimetype, bool $remove = false): static;

    /**
     * Will delete all files in this PhoFiles object
     *
     * @note This will remove the files from this PhoFiles object
     *
     * @return static
     */
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static;

    /**
     * @param int  $passes
     * @param bool $simultaneously
     * @param bool $randomized
     * @param int  $block_size
     *
     * @return static
     *
     * @todo Implement support for $simultaneously
     */
    public function shred(int $passes = 3, bool $simultaneously = false, bool $randomized = false, int $block_size = 4096): static;

    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?PhoPathInterface;

    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function getFirstValue(): ?PhoPathInterface;

    /**
     * @inheritDoc
     */
    #[ReturnTypeWillChange] public function getLastValue(): ?PhoPathInterface;

    /**
     * Adds an empty entry to the files list
     *
     * @return static
     */
    public function addEmpty(): static;
}
