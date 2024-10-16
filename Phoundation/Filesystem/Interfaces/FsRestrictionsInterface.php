<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use PDOStatement;
use Phoundation\Data\Interfaces\ArraySourceInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\FsRestrictions;
use Stringable;
use Throwable;


interface FsRestrictionsInterface extends ArraySourceInterface
{
    /**
     * Returns a restrictions object with parent directories for all directories in this restrictions object
     *
     * This is useful for the Directory object where one will want to be able to access or create the parent directory
     * of the file that needs to be accessed
     *
     * @return FsRestrictionsInterface
     */
    public function getParent(?int $levels = null): FsRestrictionsInterface;

    /**
     * Returns a restrictions object with the current directory and the specified child directory attached
     *
     * This is useful when we want more strict restrictions
     *
     * @param string|array $child_directories
     * @param bool|null    $write
     *
     * @return FsRestrictionsInterface
     */
    public function getChild(string|array $child_directories, ?bool $write = null): FsRestrictionsInterface;

    /**
     * Clear all directories for this restriction
     *
     * @return static
     */
    public function clearDirectories(): static;


    /**
     * Set all directories for this restriction
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static;

    /**
     * Set all directories for this restriction
     *
     * @param Stringable|array|string|null $directories
     *
     * @return static
     */
    public function addDirectories(Stringable|array|string|null $directories): static;

    /**
     * Add new directory for this restriction
     *
     * @param Stringable|string|null $directory
     * @param bool                   $write
     *
     * @return static
     */
    public function addDirectory(Stringable|string|null $directory, bool $write = false): static;

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
     * @param Stringable|string $pattern
     * @param bool              $write
     * @param Throwable|null    $e
     *
     * @return void
     */
    public function check(Stringable|string $pattern, bool $write, ?Throwable $e = null): void;

    /**
     * Return these restrictions but with write enabled
     *
     * @return FsRestrictionsInterface
     */
    public function makeWritable(): FsRestrictionsInterface;

    /**
     * Adds restrictions from the specified restrictions object to these restrictions
     *
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return static
     */
    public function addRestrictions(?FsRestrictionsInterface $restrictions): static;

    /**
     * Returns true if access to the specified pattern is restricted by this object
     *
     * @param Stringable|string $pattern
     * @param bool              $write
     * @param Throwable|null    $e
     *
     * @return false|string
     */
    public function isRestricted(Stringable|string $pattern, bool $write, ?Throwable $e = null): false|string;
}
