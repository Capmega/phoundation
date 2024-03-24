<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Developer\Versioning\Git\Branches;
use Phoundation\Developer\Versioning\Git\RemoteRepositories;
use Phoundation\Developer\Versioning\Git\Stash;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Stringable;


/**
 * Class Git
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
interface GitInterface
{
    /**
     * Returns the path for this ChangedFiles object
     *
     * @return string
     */
    public function getDirectory(): string;

    /**
     * Returns the path for this ChangedFiles object
     *
     * @param string $directory
     * @return static
     */
    public function setDirectory(string $directory): static;

    /**
     * Clone the specified URL to this path
     *
     * @return $this
     */
    public function clone(string $url): static;

    /**
     * Returns the current git branch for this path
     *
     * @return string
     */
    public function getBranch(): string;

    /**
     * Returns the current git branch for this path
     *
     * @param string $branch
     * @return static
     */
    public function setBranch(string $branch): static;

    /**
     * Returns all available git repositories
     *
     * @return RemoteRepositoriesInterface
     */
    public function getRepositoriesObject(): RemoteRepositoriesInterface;

    /**
     * Returns a list of available git branches
     *
     * @return BranchesInterface
     */
    public function getBranchesObject(): BranchesInterface;

    /**
     * Stashes the git changes
     *
     * @return StashInterface
     */
    public function getStashObject(): StashInterface;

    /**
     * Checks out the specified branches or paths for this git path
     *
     * @param array|string $branches_or_directories
     * @return static
     */
    public function checkout(array|string $branches_or_directories): static;

    /**
     * Resets the current branch to the specified revision
     *
     * @param string $revision
     * @param Stringable|array|string|null $files
     * @return static
     */
    public function reset(string $revision, Stringable|array|string|null $files = null): static;

    /**
     * Apply the specified patch to the specified target file
     *
     * @param array|string|null $files
     * @return static
     */
    public function add(array|string|null $files = null): static;

    /**
     * Resets the current branch to the specified revision
     *
     * @param string $message
     * @param bool $signed
     * @return static
     */
    public function commit(string $message, bool $signed = false): static;

    /**
     * Returns a ChangedFiles object containing all the files that have changes according to git
     *
     * @param string|null $directory
     * @return StatusFilesInterface
     */
    public function getStatus(?string $directory = null): StatusFilesInterface;

    /**
     * Returns if this git path has any changes
     *
     * @return bool
     */
    public function hasChanges(): bool;

    /**
     * Get a diff for the specified file
     *
     * @param array|string|null $files
     * @param bool $cached
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
     * @param bool $cached
     * @return string|null
     */
    public function saveDiff(array|string $files, bool $cached = false): ?string;

    /**
     * Apply the specified patch to the specified target file
     *
     * @param string|null $patch_file
     * @return static
     */
    public function apply(?string $patch_file): static;

    /**
     * Push the local changes to the remote repository / branch
     *
     * @param string $repository
     * @param string $branch
     * @return static
     */
    public function push(string $repository, string $branch): static;

    /**
     * Merge the specified branch into this one
     *
     * @param string $branch
     * @return static
     */
    public function merge(string $branch): static;

    /**
     * Rebase the specified branch into this one
     *
     * @param string $branch
     * @return static
     */
    public function rebase(string $branch): static;
}
