<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
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
     * Returns the entry with the specified identifier
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return RepositoryInterface|null
     */
    public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?RepositoryInterface;


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
     * Creates the specified branch for all repositories
     *
     * @param string $branch The branch to create from the currently selected branch
     * @param bool   $reset  If true, will first reset the repository before creating the new branch
     *
     * @return static
     */
    public function createBranch(string $branch, bool $reset = false): static;

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
}
