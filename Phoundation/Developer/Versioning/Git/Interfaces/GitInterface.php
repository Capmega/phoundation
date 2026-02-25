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

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Developer\Versioning\Git\Enums\EnumGitSelected;
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
     * @param PhoDirectoryInterface $_directory
     *
     * @return static
     */
    public function setDirectoryObject(PhoDirectoryInterface $_directory): static;

    /**
     * Clone the specified URL to this path
     *
     * @param string $url
     * @return static
     */
    public function clone(string $url): static;

    /**
     * Returns the current git branch for this directory
     *
     * @param bool $return_if_detached [false] If true will return the current branch if HEAD is detached
     * @return string|null
     */
    public function getSelectedBranch(bool $return_if_detached = false): ?string;

    /**
     * Returns the current git branch for this path
     *
     * @param string $branch
     *
     * @return static
     */
    public function selectBranch(string $branch): static;

    /**
     * Returns a list of available git branches
     *
     * @param bool        $all      [false] If true, will return all branches, including the ones that have not been checked out locally
     * @param string|null $contains [null]  If specified, will filter branches that contain the specified revision id
     *
     * @return array
     */
    public function getBranches(bool $all = false, ?string $contains = null): array;

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
     * @param string    $message
     * @param bool|null $signed
     *
     * @return static
     */
    public function commit(string $message, ?bool $signed = false): static;

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
     * @param string|null $repository   [null]  The remote repository to push to. If null, will push to the default repository
     * @param string|null $branch       [null]  If specified will push only this branch
     * @param bool        $push_tags    [true]  If true, will push the tags as well
     * @param bool        $set_upstream [false] If true, will add the -u modifier to the git push command, automatically setting the target as the upstream
     *                                  branch
     *
     * @return static
     */
    public function push(?string $repository = null, ?string $branch = null, bool $push_tags = true, bool $set_upstream = false): static;

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
    public function branchExists(string $branch): bool;

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
    public function remoteExists(string $remote): bool;

    /**
     * Throws an exception if the specified remote does not exist for this GIT repository
     *
     * @param string $remote
     *
     * @return static
     */
    public function checkRemoteExists(string $remote): static;

    /**
     * Deletes the specified GIT branch for this directory
     *
     * @param string $branch
     * @param string $remote
     *
     * @return static
     */
    public function deleteBranchRemote(string $branch, string $remote): static;

    /**
     * Returns a list of available git tags
     *
     * @return array
     */
    public function getTags(): array;

    /**
     * Creates the specified tag for this GIT repository
     *
     * @param string      $tag             The name for the tag
     * @param string|null $message [NULL]  The optional message for the tag. If specified, will create an annotated tag
     *                                     automatically
     * @param bool|null   $signed  [FALSE] If true
     * @return static
     */
    public function createTag(string $tag, ?string $message = null, ?bool $signed = false): static;

    /**
     * Pull the remote changes from the remote repository / branch
     *
     * @param string|null $repository        The repository to pull from. If not specified, the "origin" default will be used, unless an upstream was specified
     *                                       for the current branch
     * @param bool        $all        [true] Will execute git fetch --all, fetch all remotes, except for the ones that has the remote.
     *
     * @return static
     */
    public function fetch(?string $repository, bool $all = true): static;

    /**
     * Creates the specified lightweight tag for this git repository
     *
     * @param string $tag The name for the tag
     * @return static
     */
    public function createLightweightTag(string $tag): static;

    /**
     * Pops the last changes from the git stash stashes over the working tree
     *
     * @return static
     */
    public function stashPop(): static;

    /**
     * Returns an array containing all the changes in the last available git stash
     *
     * @return array
     */
    public function stashShow(): array;

    /**
     * Returns the current git tag for this directory
     *
     * @param string $tag
     *
     * @return bool
     */
    public function tagExists(string $tag): bool;

    /**
     * Returns the current git branch for this directory
     *
     * @return string|null
     */
    public function getSelectedTag(): ?string;

    /**
     * Returns true if this repository has a branch selected
     *
     * @return bool
     */
    public function hasTypeBranchSelected(): bool;

    /**
     * Returns true if this repository has a tag selected
     *
     * @return bool
     */
    public function hasTypeTagSelected(): bool;

    /**
     * Returns the git selected type (branch, tag, detached)
     *
     * @return EnumGitSelected
     */
    public function getSelectedType(): EnumGitSelected;

    /**
     * Returns true if the selected type for this repository matches the specified type
     *
     * @param EnumGitSelected $selected
     *
     * @return bool
     */
    public function hasSelectedType(EnumGitSelected $selected): bool;

    /**
     * Moves or renames the specified source file to the target
     *
     * @param PhoFileInterface $source
     * @param PhoFileInterface $target
     *
     * @return static
     */
    public function mv(PhoFileInterface $source, PhoFileInterface $target): static;

    /**
     * Moves or renames the specified source file to the target
     *
     * @param PhoFileInterface $source
     * @param PhoFileInterface $target
     *
     * @return static
     */
    public function move(PhoFileInterface $source, PhoFileInterface $target): static;

    /**
     * Returns true if the current git repository has the specified branch selected
     *
     * @param string $branch
     *
     * @return bool
     */
    public function hasBranchSelected(string $branch): bool;

    /**
     * Returns true if the current git repository has the specified tag selected
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasTagSelected(string $tag): bool;

    /**
     * Returns the commit objects in reverse chronological order
     *
     * @return array
     */
    public function listRevisions(): array;

    /**
     * Searches the entire git history for the specified keyword
     *
     * @param string $keyword        The keyword to search for
     * @param bool   $grouped [true] If true, will return the results grouped by revision and file. If false, will return the results directly from GIT
     *
     * @return IteratorInterface
     */
    public function grep(string $keyword, bool $grouped = true): IteratorInterface;

    /**
     * Returns the repositories where the specified revision is a member of
     *
     * @param string $revision
     *
     * @return array
     */
    public function getBranchesContainingRevision(string $revision): array;
}
