<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Filesystem\FsRestrictions;
use Stringable;
use Throwable;


interface FsRestrictionsInterface
{
    /**
     * Returns system general file access restrictions
     *
     * @return FsRestrictionsInterface
     */
    public static function getSystem(): FsRestrictionsInterface;

    /**
     * Returns a restrictions object with parent directories for all directories in this restrictions object
     *
     * This is useful for the Directory object where one will want to be able to access or create the parent directory
     * of the file that needs to be accessed
     *
     * @return FsRestrictions
     */
    public function getParent(?int $levels = null): FsRestrictions;

    /**
     * Returns a restrictions object with the current directory and the specified child directory attached
     *
     * This is useful when we want more strict restrictions
     *
     * @param string|array $child_directories
     * @param bool|null    $write
     *
     * @return FsRestrictions
     */
    public function getChild(string|array $child_directories, ?bool $write = null): FsRestrictions;

    /**
     * Clear all directories for this restriction
     *
     * @return static
     */
    public function clearDirectories(): static;

    /**
     * Set all directories for this restriction
     *
     * @param Stringable|array|string $directories
     * @param bool                    $write
     *
     * @return static
     */
    public function setSource(Stringable|array|string $directories, bool $write = false): static;

    /**
     * Set all directories for this restriction
     *
     * @param Stringable|array|string $directories
     * @param bool                    $write
     *
     * @return static
     */
    public function addDirectories(Stringable|array|string $directories, bool $write = false): static;

    /**
     * Add new directory for this restriction
     *
     * @param Stringable|string $directory
     * @param bool              $write
     *
     * @return static
     */
    public function addDirectory(Stringable|string $directory, bool $write = false): static;

    /**
     * Returns all directories for this restriction
     *
     * @return array
     */
    public function getSource(): array;

    /**
     * Sets the label for this restriction
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabel(?string $label): static;

    /**
     * Sets the restrictions label only if the specified label is not empty, and this object's label is NULL or "system"
     *
     * @param string|null $label
     *
     * @return static
     */
    public function ensureLabel(?string $label): static;

    /**
     * Returns the label for this restriction
     *
     * @return string
     */
    public function getLabel(): string;


    /**
     * @param Stringable|array|string $patterns
     * @param bool                    $write
     * @param Throwable|null          $e
     *
     * @return void
     */
    public function check(Stringable|array|string &$patterns, bool $write, ?Throwable $e = null): void;

    /**
     * Return these restrictions but with write enabled
     *
     * @return FsRestrictionsInterface
     */
    public function getTheseWritable(): FsRestrictionsInterface;
}
