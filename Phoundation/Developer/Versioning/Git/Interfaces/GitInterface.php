<?php

/**
 * Class Git
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Stringable;

interface GitInterface
{
    /**
     * Returns the path for this git repository
     *
     * @return PhoDirectoryInterface
     */
    public function getDirectoryObject(): PhoDirectoryInterface;

    /**
     * Returns the path for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $o_directory
     *
     * @return static
     */
    public function setDirectoryObject(PhoDirectoryInterface $o_directory): static;

    /**
     * Clone the specified URL to this path
     *
     * @return static
     */
    public function clone(string $url): static;

    /**
     * Returns the current git branch for this path
     *
     * @return string
     */
    public function getCurrentBranch(): string;

    /**
     * Returns the current git branch for this path
     *
     * @param string $branch
     *
     * @return static
     */
    public function setCurrentBranch(string $branch): static;

    /**
     * Returns a list of available git branches
     *
     * @param bool $all
     *
     * @return array
     */
    public function getBranches(bool $all = false): array;

    /**
     * Stashes the git changes
     *
     * @return array
     */
    public function getStashList(): array;

    /**
     * Checks out the specified branches or directories for this git directory
     *
     * @param Stringable|array|string $branches_or_directories
     *
     * @return static
     */
    public function checkout(Stringable|array|string $branches_or_directories): static;

    /**
     * Resets the current branch to the specified revision
     *
     * @param string                       $revision
     * @param Stringable|array|string|null $files
     *
     * @return static
     */
    public function reset(string $revision, Stringable|array|string|null $files = null): static;

    /**
     * Apply the specified patch to the specified target file
     *
     * @param array|string|null $files
     *
     * @return static
     */
    public function add(array|string|null $files = null): static;

    /**
     * Resets the current branch to the specified revision
     *
     * @param string $message
     * @param bool   $signed
     *
     * @return static
     */
    public function commit(string $message, bool $signed = false): static;

    /**
     * Returns a ChangedFiles object containing all the files that have changes according to git
     *
     * @param PhoPathInterface|null $path
     *
     * @return StatusFilesInterface
     */
    public function getStatusFilesObject(?PhoPathInterface $path = null): StatusFilesInterface;

    /**
     * Returns if this git directory has any changes
     *
     * @param PhoDirectoryInterface|null $directory
     *
     * @return bool
     */
    public function hasChanges(?PhoDirectoryInterface $directory = null): bool;

    /**
     * Get a diff for the specified file
     *
     * @param array|string|null $files
     * @param bool              $cached
     *
     * @return string
     */
    public function getDiff(array|string|null $files = null, bool $cached = false): string;

    /**
     * Save the diff for the specified file to the specified target
     *
     * @note Returns NULL if the specified file has no diff
     *
     *
     * @param array|string $files
     * @param bool         $cached
     *
     * @return PhoFileInterface|null
     */
    public function saveDiff(array|string $files, bool $cached = false): ?PhoFileInterface;

    /**
     * Apply the specified patch to the specified target file
     *
     * @param PhoFileInterface|null $patch_file
     *
     * @return static
     */
    public function apply(?PhoFileInterface $patch_file): static;


    /**
     * Push the local changes to the remote repository / branch
     *
     * @param string $repository
     * @param string $branch
     * @param bool   $set_upstream
     *
     * @return static
     */
    public function push(string $repository, string $branch, bool $set_upstream = false): static;

    /**
     * Merge the specified branch into this one
     *
     * @param string $branch
     *
     * @return static
     */
    public function merge(string $branch): static;

    /**
     * Rebase the specified branch into this one
     *
     * @param string $branch
     *
     * @return static
     */
    public function rebase(string $branch): static;

    /**
     * Creates the specified GIT branch for this directory
     *
     * @param string $branch
     * @param bool   $reset
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false): static;

    /**
     * Returns the current git branch for this directory
     *
     * @param string $branch
     *
     * @return bool
     */
    public function hasBranch(string $branch): bool;

    /**
     * Deletes the specified GIT branch for this directory
     *
     * @param string $branch
     * @param bool   $force
     *
     * @return static
     */
    public function deleteBranch(string $branch, bool $force = false): static;

    /**
     * Returns true if the specified remote exists for this repository
     *
     * @param string $remote
     *
     * @return bool
     */
    public function hasRemote(string $remote): bool;

    /**
     * Throws an exception if the specified remote does not exist for this GIT repository
     *
     * @param string $remote
     *
     * @return static
     */
    public function checkRemote(string $remote): static;

    /**
     * Deletes the specified GIT branch for this directory
     *
     * @param string $branch
     * @param string $remote
     *
     * @return static
     */
    public function deleteBranchRemote(string $branch, string $remote): static;
}
