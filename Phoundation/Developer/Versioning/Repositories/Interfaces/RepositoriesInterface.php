<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Developer\Phoundation\Enums\EnumPhoundationClass;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveBranchSelectedException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveTagException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveVersionSelectedException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesTagExistsException;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use ReturnTypeWillChange;
use Stringable;


interface RepositoriesInterface extends DataIteratorInterface
{
    /**
     * Returns the amount of 'permission denied' items in the result set
     *
     * @return array
     */
    public function getResultsWithPermissionDenied(): array;


    /**
     * Returns an array with the new repositories found after a scan
     *
     * @return array
     */
    public function getNew(): array;


    /**
     * Returns the number of new repositories found after a scan
     *
     * @return int|null
     */
    public function getNewCount(): ?int;


    /**
     * Returns an array with the repositories that were deleted after a scan
     *
     * @return array
     */
    public function getDeleted(): array;


    /**
     * Returns the number of repositories deleted after a scan
     *
     * @return int|null
     */
    public function getDeletedCount(): ?int;


    /**
     * Scans for repositories on the current machine and registers them in the database
     *
     * @param PhoPathInterface $path                        The path from which the scan will start
     * @param bool             $disable_backup_paths [true] If true, will automatically disable repositories when any
     *                                                      directory (including the basename) in their path is a backup
     *                                                      directory (i.e. a directory name that ends with a ~)
     * @param bool             $delete_gone          [true] Will delete repositories from the database if they were not
     *                                                      found during this scan
     *
     * @return static
     * @todo Implement $delete_gone support
     */
    public function scan(PhoPathInterface $path, bool $disable_backup_paths = true, bool $delete_gone = true): static;

    /**
     * Returns true when any of the available repositories has changes
     *
     * @return bool
     */
    public function anyHaveChanges(): bool;

    /**
     * Returns true if the current git branch for this repository is equal to the specified branch
     *
     * @param string $branch
     *
     * @return bool
     */
    public function anyIsOnBranch(string $branch): bool;

    /**
     * Returns the current RepositoryInterface object
     *
     * @return RepositoryInterface|null
     */
    public function current(): ?RepositoryInterface;

    /**
     * Returns the entry with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return RepositoryInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?RepositoryInterface;

    /**
     * Returns a random entry
     *
     * @return RepositoryInterface|null
     */
    public function getRandom(): ?RepositoryInterface;

    /**
     * Creates the specified tag for all repositories
     *
     * @param string      $tag             The name for the tag
     * @param string|null $message [NULL]  The optional message for the tag. If specified, will create an annotated tag
     *                                     automatically
     * @param bool|null   $signed  [FALSE] If true
     * @return static
     */
    public function createTag(string $tag, ?string $message = null, ?bool $signed = false): static;

    /**
     * Creates the specified tag for all repositories
     *
     * @param string      $tag           The name for the tag to delete
     * @param string|bool $remote [true] If true or string with value, will delete the branch on the default (for true) or specified remote
     *
     * @return static
     */
    public function deleteTag(string $tag, string|bool $remote = true): static;

    /**
     * Executes a "git fetch" on all repositories
     *
     * @param string|bool|null $remote [null] The remote to fetch from, null will fetch from the default repository
     * @param bool             $all    [true] Will execute git fetch --all, fetch all remotes, except for the ones that has the remote.
     *
     * @return static
     */
    public function fetch(string|bool|null $remote = null, bool $all = true): static;

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
     * Executes a "git pull" on all repositories
     *
     * @param string|bool|null $remote [null] The remote to pull from, null will pull from the default repository
     * @param string|null      $branch [null] The specific branch to pull, null will pull the current branch
     *
     * @return static
     */
    public function pull(string|bool|null $remote = null, ?string $branch = null): static;

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
     * Sets the current git branch for this repository
     *
     * @param string $branch
     * @param bool $auto_create
     * @param bool $upstream
     *
     * @return static
     */
    public function selectTag(string $branch, bool $auto_create = false, bool $upstream = false): static;

    /**
     * Deletes the specified branch from all repositories
     *
     * @param string $branch The branch to delete from the currently selected branch
     *
     * @return static
     */
    public function deleteBranch(string $branch): static;

    /**
     * Throws a RepositoriesException if not all repositories have the specified branch
     *
     * @param string $branch
     * @param string $action
     *
     * @return static
     */
    public function checkAllHaveBranch(string $branch, string $action): static;

    /**
     * Sets the current git branch for this repository
     *
     * @param string $branch
     * @param bool $auto_create
     * @param bool $upstream
     * @return static
     */
    public function selectBranch(string $branch, bool $auto_create = false, bool $upstream = false): static;

    /**
     * Creates the specified lightweight tag for all repositories
     *
     * @param string $name The name for the tag
     * @return static
     */
    public function createLightweightTag(string $name): static;

    /**
     * Throws a RepositoriesException if not any repositories have the specified branch
     *
     * @param string $branch              The branch that must exist in any repositories
     * @param string $action              The action displayed in the exception, if thrown
     * @param bool   $auto_create [false] If true, will automaticanyy create the branch on each repository where it does
     *                                    not yet exist
     * @return static
     * @throws RepositoriesException
     */
    public function checkNoneHaveBranch(string $branch, string $action, bool $auto_create = false): static;

    /**
     * Returns true if any repository is on the specified branch
     *
     * @param string $branch              The branch that any of the repositories must have
     * @param bool   $auto_create [false] If true, will automatically create the branch on each repository where it does
     * *                                  not yet exist
     * @return bool
     */
    public function allHaveBranch(string $branch, bool $auto_create = false): bool;

    /**
     * Returns true if all repository is on the specified branch
     *
     * @param string $branch
     *
     * @return bool
     */
    public function allAreOnBranch(string $branch): bool;

    /**
     * Returns true if any repository is on the specified tag
     *
     * @param string $tag The tag that any of the repositories must have
     *
     * @return bool
     */
    public function anyHaveTag(string $tag): bool;

    /**
     * Throws a RepositoriesException if not any repositories have the specified tag
     *
     * @param string $tag    The tag that must exist in any repositories
     * @param string $action The action displayed in the exception, if thrown
     *
     * @return static
     * @throws RepositoriesTagExistsException
     */
    public function checkNoneHaveTag(string $tag, string $action): static;

    /**
     * Returns true if any repository is on the specified tag
     *
     * @param string $tag The tag that any of the repositories must have
     *
     * @return bool
     */
    public function allHaveTag(string $tag): bool;

    /**
     * Throws a RepositoriesNotAllHaveTagException if not all repositories have the specified tag
     *
     * @param string $tag                 The tag that must exist in all repositories
     * @param string $action              The action displayed in the exception, if thrown
     *
     * @return static
     * @throws RepositoriesNotAllHaveTagException
     */
    public function checkAllHaveTag(string $tag, string $action): static;

    /**
     * Returns true if all repositories have a tag selected
     *
     * @return bool
     */
    public function allHaveTypeTagSelected(): bool;

    /**
     * Throws a RepositoriesException if any of the available repositories currently has the specified tag selected
     *
     * @param string $action The action that will be executed that requires all repositories to have a tag selected
     *
     * @return static
     */
    public function checkAllHaveTypeTagSelected(string $action): static;

    /**
     * Returns true if all repositories have a branch selected
     *
     * @return bool
     */
    public function allHaveTypeBranchSelected(): bool;

    /**
     * Throws a RepositoriesException if any of the available repositories currently has the specified branch selected
     *
     * @param string $action The action that will be executed that requires all repositories to have a branch selected
     *
     * @return static
     */
    public function checkAllHaveTypeBranchSelected(string $action): static;

    /**
     * Checks if all repositories have the requested suffix or version branch available, and if not, throws a RepositoriesVersionBranchNotExistsException
     *
     * @param string $phoundation_version
     * @param string $project_version
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return bool
     */
    public function allHaveSuffixOrVersionBranch(string $phoundation_version, string $project_version, string $phoundation_branch, string $project_branch): bool;

    /**
     * Throws a RepositoriesNotAllHaveVersionSelectedException not if all repositories have the correct version branch or tag selected
     *
     * @param string $action The action displayed in the exception, if thrown
     *
     * @return static
     * @throws RepositoriesNotAllHaveVersionSelectedException
     */
    public function checkAllHaveCorrectVersionSelected(string $action): static;

    /**
     * Will upgrade the revision part of the version of class repositories by the specified number
     *
     * @param EnumPhoundationClass $class    The class of repository to upgrade, either "phoundation" or "project" or "cdn"
     * @param int|null             $increase [1] The amount to increase the release part of the version by
     *
     * @return $this
     */
    public function releaseRevision(EnumPhoundationClass $class, ?int $increase = 1): static;

    /**
     * Throws a RepositoriesNotAllHaveBranchException if not all repositories have the specified branch
     *
     * @param string $action
     *
     * @return static
     */
    public function checkNoneHaveChanges(string $action): static;

    /**
     * Returns a Repositories object with all the repositories that have changes
     *
     * @return RepositoriesInterface
     */
    public function getRepositoriesWithChanges(): RepositoriesInterface;

    /**
     * Checks if all repositories have the requested suffix or version branch available, and if not, throws a RepositoriesVersionBranchNotExistsException
     *
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return bool
     */
    public function allHaveBranchSelected(string $phoundation_branch, string $project_branch): bool;

    /**
     * Returns an array with all the repositories that do not have the requested project or phoundation branch selected
     *
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return array
     */
    public function getWithWrongBranchSelected(string $phoundation_branch, string $project_branch): array;

    /**
     * Checks if all repositories have the requested project or phoundation branch selected, and if not, throws a RepositoriesNotAllHaveBranchSelectedException
     *
     * @param string $action
     * @param string $phoundation_branch
     * @param string $project_branch
     *
     * @return static
     * @throws RepositoriesNotAllHaveBranchSelectedException
     */
    public function checkAllHaveBranchSelected(string $action, string $phoundation_branch, string $project_branch): static;

    /**
     * Synchronizes all selected branch repositories so they are all on the correct branch
     *
     * @param string|null $suffix             If specified, will select VERSIONBRANCH-SUFFIX instead of VERSIONBRANCH
     * @param bool        $auto_create [true] If true, will automatically create the branch if it does not exist for
     *                                        each repository
     * @return static
     */
    public function selectVersionBranch(?string $suffix, bool $auto_create = true): static;

    /**
     * Checks if all repositories have the requested suffix or version branch available, and if not, throws a RepositoriesVersionBranchNotExistsException
     *
     * @param string|null $suffix                     The optional suffix to use
     * @param string|null $phoundation_version        The version that should exist if this repository is a Phoundation
     *                                                repository
     * @param string|null $project_version            The version that should exist if this repository is a project
     *                                                repository
     * @param string|null $phoundation_branch         The branch that should exist if this repository is a Phoundation
     *                                                repository
     * @param string|null $project_branch             The branch that should exist if this repository is a project
     *                                                repository
     * @param bool        $check_versions      [true] If true will check version and branch. If false, will only check
     *                                                branch
     * @return static
     */
    public function checkAllHaveSuffixOrVersionBranch(?string $suffix, ?string &$phoundation_version = null, ?string &$project_version = null, ?string &$phoundation_branch = null, ?string &$project_branch = null, bool $check_versions = true): static;

    /**
     * Updates the current suffixed version branches, and updates it from the base version in all repositories
     *
     * @param bool $all_version_branches [false] If true, will not only update the current suffix branch, but will
     *                                           update all branches for the same version
     * @return static
     */
    public function updateVersionBranches(bool $all_version_branches = false): static;

    /**
     * Updates all suffixed version branches for the specified version, and update them from the base version, in all repositories
     *
     * @param string $version
     * @return static
     */
    public function updateAllSuffixedVersionBranches(string $version): static;

    /**
     * Returns true if all repositories have the correct version branch or tag selected
     *
     * @param string|null $phoundation_version Will contain the Phoundation version
     * @param string|null $project_version     Will contain the project version
     * @param string|null $phoundation_branch  Will contain the Phoundation branch (version + optional suffix)
     * @param string|null $project_branch      Will contain the project branch (version + optional suffix)
     * @return bool
     */
    public function allHaveCorrectVersionSelected(?string &$phoundation_version = null, ?string &$project_version = null, ?string &$phoundation_branch = null, ?string &$project_branch = null): bool;

    /**
     * Returns the suffix for the project main repository, or NULL if no suffix has been selected
     *
     * @return string|null
     */
    public function detectProjectSuffix(): ?string;

    /**
     * Returns true if the project is on a version branch with suffix
     *
     * @return bool
     */
    public function hasProjectSuffix(): bool;

    /**
     * Throws a RepositoriesException if the curren
     *
     * @param string $action the action that is to be taken if this test passes
     * @return static
     * @throws RepositoriesException
     */
    public function checkHasProjectSuffix(string $action): static;

    /**
     * Returns the currently selected for the project main repository, or NULL if no suffix has been selected
     *
     * @return string
     */
    public function detectProjectBranch(): string;
}
