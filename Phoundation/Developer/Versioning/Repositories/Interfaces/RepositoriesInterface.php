<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
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
     * Returns true when any of the known repositories has changes
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
     * Deletes the specified branch from all known repositories
     *
     * @param string $branch
     * @param bool   $remote
     *
     * @return static
     */
    public function deleteBranch(string $branch, bool $remote = true): static;

    /**
     * Deletes the specified tag from all known repositories
     *
     * @param string $tag
     * @param bool   $remote
     *
     * @return static
     */
    public function deleteTag(string $tag, bool $remote = true): static;
}
