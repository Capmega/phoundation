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
     * Creates the specified tag for all repositories
     *
     * @param string      $name            The name for the tag
     * @param string|null $message [NULL]  The optional message for the tag. If specified, will create an annotated tag
     *                                     automatically
     * @param bool        $signed  [FALSE] If true
     * @return static
     */
    public function createTag(string $name, ?string $message = null, bool $signed = false): static;

    /**
     * Creates the specified tag for all repositories
     *
     * @param string      $name          The name for the tag to delete
     * @param string|bool $remote [true] If true or string with value, will delete the branch on the default (for true) or specified remote
     *
     * @return static
     */
    public function deleteTag(string $name, string|bool $remote = true): static;

    /**
     * Executes a "git fetch" on all repositories
     *
     * @param string|null $remote
     * @param bool        $all
     *
     * @return $this
     */
    public function fetch(?string $remote = null, bool $all = true): static;


    /**
     * Executes a "git pull" on all repositories
     *
     * @param string|null $remote
     * @param string|null $branch
     *
     * @return $this
     */
    public function pull(?string $remote = null, ?string $branch = null): static;

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
}
