<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesException;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesNotAllHaveTagException;
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
     * @param PhoPathInterface $path
     * @param bool             $delete_gone
     *
     * @return static
     */
    public function scan(PhoPathInterface $path, bool $delete_gone = true): static;

    /**
     * Returns true when any of the available repositories has changes
     *
     * @return bool
     */
    public function hasChanges(): bool;

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
     * @return RepositoryInterface
     */
    public function current(): RepositoryInterface;

    /**
     * Returns the entry with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): RepositoryInterface;

    /**
     *
     * @return RepositoryInterface
     */
    public function getRandom(): RepositoryInterface;

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
    public function checkAnyHaveBranch(string $branch, string $action, bool $auto_create = false): static;

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
    public function checkAnyHaveTag(string $tag, string $action): static;

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
}
