<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationClass;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationType;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Versioning\Git\Branches\Interfaces\BranchesInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\RemotesInterface;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesHaveChangesException;
use Phoundation\Developer\Versioning\Repositories\Repository;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Utils\Strings;

interface RepositoryInterface extends DataEntryInterface
{
    /**
     * Returns the "required" property for this object
     *
     * @return string|null
     */
    public function getRequired(): ?string;


    /**
     * Sets the 'required' property for this object
     *
     * @param int|bool $required
     *
     * @return static
     */
    public function setRequired(int|bool $required): static;


    /**
     * Sets the path for this object
     *
     * @param string|null $path
     *
     * @return static
     */
    public function setPath(string|null $path): static;


    /**
     * Returns the Remotes class object for this Repository
     *
     * @return RemotesInterface
     */
    public function getRemotesObject(): RemotesInterface;

    /**
     * Returns the path for this ChangedFiles object
     *
     * @return GitInterface
     */
    public function getGitObject(): GitInterface;

    /**
     * Returns the path for this ChangedFiles object
     *
     * @param GitInterface $o_git
     *
     * @return static
     */
    public function setGitObject(GitInterface $o_git): static;

    /**
     * Returns the server restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface;

    /**
     * Sets the server and filesystem restrictions for this object
     *
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions The file restrictions to apply to this object
     * @param bool                                       $write          If $restrictions is not specified as a
     *                                                                   FsRestrictions class, but as a path string, or
     *                                                                   array of path strings, then this method will
     *                                                                   convert that into a FsRestrictions object and
     *                                                                   this is the $write modifier for that object
     * @param string|null                                $label          If $restrictions is not specified as a
     *                                                                   FsRestrictions class, but as a path string, or
     *                                                                   array of path strings, then this method will
     *                                                                   convert that into a FsRestrictions object and
     *                                                                   this is the $label modifier for that object
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
     * Returns the path for this object
     *
     * @return PhoPathInterface|null
     */
    public function getPath(): ?string;

    /**
     * Returns the path for this object
     *
     * @return PhoPathInterface|null
     */
    public function getPathObject(): ?PhoPathInterface;

    /**
     * Sets the path for this object
     *
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $o_path): static;

    /**
     * Returns the current git branch for this repository
     *
     * @param bool $return_if_detached If true, will return the selected branch, even if it is not a branch
     *
     * @return string|null
     */
    public function getSelectedBranch(bool $return_if_detached = false): ?string;

    /**
     * Returns true if the requested branch exists for this repository
     *
     * @param string $branch                 The branch to search for
     * @param bool   $check_tags_too         If true will search for the branch name in the tags list as well
     * @param bool   $auto_create    [false] If true, will automatically create the branch on each repository where it
     *                                       does not yet exist
     *
     * @return bool
     */
    public function branchExists(string $branch, bool $check_tags_too = true, bool $auto_create = false): bool;

    /**
     * Deletes the specified branch from this repository (and optionally the selected remote as well)
     *
     * @param string       $branch
     * @param string|false $remote
     *
     * @return static
     */
    public function deleteBranch(string $branch, string|false $remote = false): static;

    /**
     * Returns true if the current git branch for this repository is equal to the specified branch
     *
     * @param string $branch
     *
     * @return bool
     */
    public function isOnBranch(string $branch): bool;

    /**
     * Throws a RepositoriesException if the repository is using the specified branch
     *
     * @param string $branch
     * @param string $action
     *
     * @return Repository
     */
    public function checkIsNotOnBranch(string $branch, string $action): static;

    /**
     * Returns the specified repository, or the configured default
     *
     * @param string|bool|null $repository
     *
     * @return string|null
     */
    public function selectRemoteRepository(string|bool|null $repository = null): ?string;

    /**
     * Checks if this repository has the requested suffix or version branch available, and if not, throws a
     * RepositoriesHaveChangesException
     *
     * @param string|null $version
     * @param string      $branch
     * @param bool        $check_tags_too [false]
     * @param bool        $check_all      [false] If true will also check remote repositories
     * @return static
     * @throws RepositoriesHaveChangesException
     */
    public function checkHasBranchOrVersionBranch(?string $version, string $branch, bool $check_tags_too = true, bool $check_all = false): static;

    /**
     * Returns true if this repository has the requested suffix or version branch available
     *
     * @param string|null $version                The version branch that will be checked if it exists. If NULL, will
     *                                            not check for this version
     * @param string      $branch                 The branch that will be checked if it exists.
     * @param bool        $check_tags_too [false] If true will also check in the tags list
     * @param bool        $check_all      [false] If true will also check remote repositories
     *
     * @return bool
     */
    public function hasBranchOrVersionBranch(?string $version, string $branch, bool $check_tags_too = false, bool $check_all = false): bool;

    /**
     * Will push the changes on the specified branch (or all if none specified) to the specified, or default remote repository
     *
     * @param string|bool|null $remote       [null]  The remote to push to, null will push to the default repository
     * @param string|null      $branch       [null]  The specific branch to push to, null will push all branches
     * @param bool             $set_upstream [false]
     *
     * @return static
     */
    public function push(string|bool|null $remote = null, ?string $branch = null, bool $set_upstream = false): static;

    /**
     * Will pull the changes for the current branch from the specified, or default remote repository
     *
     * @param string|bool|null $remote [null]  The remote to pull from, null will pull from the default repository
     * @param string|null      $branch [null] The specific branch to pull, null will pull the current branch
     *
     * @return static
     */
    public function pull(string|bool|null $remote = null, ?string $branch = null): static;

    /**
     * Will fetch the changes for the current branch from the specified, or default remote repository
     *
     * @param string|bool|null $remote [null] The remote to fetch from, null will fetch from the default repository
     * @param bool             $all    [true] Will execute git fetch --all, fetch all remotes, except for the ones that has the remote.
     *
     * @return static
     */
    public function fetch(string|bool|null $remote = null, bool $all = true): static;

    /**
     * Returns true if the specified tag exists in this repository
     *
     * @param string $tag                        The tag to test for existence
     * @param bool   $check_branches_too [false] If true will check if the tag exists as a branch name as well
     *
     * @return bool
     */
    public function tagExists(string $tag, bool $check_branches_too = false): bool;

    /**
     * Creates the specified new branch in this repository
     *
     * @param string      $branch
     * @param bool        $reset
     * @param string|null $remote
     * @param bool        $set_upstream
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false, ?string $remote = null, bool $set_upstream = false): static;

    /**
     * Creates the specified lightweight tag for all repositories
     *
     * @param string $name The name for the tag
     * @return static
     */
    public function createLightweightTag(string $name): static;

    /**
     * Returns the size of the repository working tree in bytes
     *
     * @return int
     */
    public function getWorkingTreeSize(): int;

    /**
     * Creates the specified tag for this repository
     *
     * @param string      $tag             The name for the tag
     * @param string|null $message [NULL]  The optional message for the tag. If specified, will create an annotated tag
     *                                     automatically
     * @param bool|null   $signed  [FALSE] If true
     * @return static
     */
    public function createTag(string $tag, ?string $message = null, ?bool $signed = false): static;

    /**
     * Checks if this repository has the requested suffix or version tag available, and if not, throws a RepositoriesHaveChangesException
     *
     * @param string $version
     * @param string $tag
     * @param bool   $check_tags_too [false] If true will also check in the tags list
     * @return static
     */
    public function checkHasSuffixOrVersionTag(string $version, string $tag, bool $check_tags_too = true): static;

    /**
     * Returns the current git branch for this repository
     *
     * @return string|null
     */
    public function getSelectedTag(): ?string;

    /**
     * Returns the platform for this repository
     *
     * @return EnumPhoundationClass
     */
    public function detectClass(): EnumPhoundationClass;

    /**
     * Returns true if the type for this object is the same as the specified type
     *
     * @param EnumPhoundationType|string $type
     *
     * @return bool
     */
    public function isType(EnumPhoundationType|string $type): bool;

    /**
     * Returns true if the platform for this object is the same as the specified platform
     *
     * @param EnumPhoundationClass|string $class
     *
     * @return bool
     */
    public function isClass(EnumPhoundationClass|string $class): bool;

    /**
     * @param int $increase [1] The number to increase the release part of the version
     *
     * @return $this
     */
    public function upgradeRevision(int $increase = 1): static;

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
     * Returns true if this repository has changes on the working tree
     *
     * @return bool
     */
    public function hasChanges(): bool;

    /**
     * Throws a RepositoriesSomeHaveChangesException if not all repositories have the specified branch
     *
     * @param string $action
     *
     * @return static
     */
    public function checkHasNoChanges(string $action): static;

    /**
     * Returns true if this repository has the specified branch selected
     *
     * @param string $branch The branch that should be selected for this repository
     *
     * @return bool
     */
    public function hasBranchSelected(string $branch): bool;

    /**
     * Returns true if this repository has the specified tag selected
     *
     * @param string $tag The tag that should be selected for this repository
     *
     * @return bool
     */
    public function hasTagSelected(string $tag): bool;

    /**
     * Update the version suffix branch from its version base branch
     *
     * @return static
     */
    public function updateVersionBranch(): static;

    /**
     * Returns true if the DataEntry object has the specified type
     *
     * @param string $type          The type to compare against
     * @param bool   $strict [true] If true, will do a strict comparison (===), weak comparison (==) if false
     * @return bool
     */
    public function hasType(string $type, bool $strict = true): bool;

    /**
     * Returns the type for this object
     *
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * Sets the type for this object
     *
     * @param string|null $type
     *
     * @return static
     */
    public function setType(?string $type): static;

    /**
     * Returns true if this repository is currently on a version branch
     *
     * @param bool $short_version [true] If true, will require a short version (MAJOR.MINOR) instead of a full version (MAJOR.MINOR.REVISION)
     *
     * @return bool
     */
    public function isOnVersionBranch(bool $short_version = true): bool
;

    /**
     * Returns true if this repository is currently on a version branch
     *
     * @return bool
     */
    public function isOnCorrectVersionBranch(): bool;

    /**
     * Returns true if this repository is currently on a version branch
     *
     * @return static
     */
    public function checkIsOnVersionBranch(): static;

    /**
     * Returns true if this repository is currently on a version branch
     *
     * @return static
     */
    public function checkIsOnCorrectVersionBranch(): static;

    /**
     * Returns the suffix for this repository version branch, if any. Will return NULL if on a suffix less branch
     *
     * If the current branch is not a version branch, an  will be thrown
     *
     * @param bool $require_correct_version
     * @return string|null
     */
    public function getSelectedVersionSuffix(bool $require_correct_version = false): ?string;

    /**
     * Returns true if this repository is currently on a version branch that has a suffix
     *
     * @param bool $short_version [true] If true, will require a short version (MAJOR.MINOR) instead of a full version (MAJOR.MINOR.REVISION)
     *
     * @return bool
     */
    public function isOnVersionSuffixBranch(bool $short_version = true): bool;

    /**
     * Returns the version (without the suffix) for this repository version branch, if any.
     *
     * If the current branch is not a version branch, NULL will be returned
     *
     * @return string|null
     */
    public function getSelectedVersion(): ?string;

    /**
     * Marks this repository as disabled so that it will no longer be used for any action
     *
     * @return static
     */
    public function disable(): static;

    /**
     * Marks this repository as enabled so that it can be used again for any action
     *
     * @return static
     */
    public function enable(): static;

    /**
     * Returns true if this repository is a Phoundation compatible git repository
     *
     * @return bool
     */
    public function isPhoundation(): bool;

    /**
     * Returns true if this repository is currently on a version branch
     *
     * @param bool $short_version [true] If true, will require a short version (MAJOR.MINOR) instead of a full version (MAJOR.MINOR.REVISION)
     *
     * @return bool
     */
    public function isOnVersionOnlyBranch(bool $short_version = true): bool;

    /**
     * Returns the Branches object for this Repository
     *
     * @param bool $only_version
     * @param bool $only_suffix
     *
     * @return BranchesInterface
     */
    public function getBranchObject(bool $only_version = false, bool $only_suffix = false): BranchesInterface;

    /**
     * Returns an array with only version branches for this repository
     *
     * @return array
     */
    public function getVersionBranches(): array;

    /**
     * Returns an array with only version suffix branches for this repository
     *
     * @return array
     */
    public function getVersionSuffixBranches(): array;

    /**
     * Sets the selected branch for this repository
     *
     * @param string $branch              The branch name to select
     * @param bool   $auto_create [false] If true, and the branch does not exist, will automatically create the branch
     * @param bool   $upstream    [false] If true, and the branch was created, will automatically push the branch upstream
     *
     * @return static
     */
    public function selectBranch(string $branch, bool $auto_create = false, bool $upstream = false): static;

    /**
     * Merges the specified version suffix branches into the current version suffix branch
     *
     * @param array|string $suffixes a (space separated, if string) list of version suffix branches that will be merged into the current version suffix branch
     *                               for each repository
     *
     * @return static
     */
    public function mergeVersionSuffixes(array|string $suffixes): static;
}
