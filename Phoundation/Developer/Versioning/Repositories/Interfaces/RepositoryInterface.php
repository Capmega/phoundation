<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\RemotesInterface;
use Phoundation\Developer\Versioning\Repositories\Exception\RepositoriesVersionBranchNotExistsException;
use Phoundation\Developer\Versioning\Repositories\Repository;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\PhoRestrictions;

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
     * @return string
     */
    public function getCurrentBranch(): string;

    /**
     * Returns true if the specified branch exists in this repository
     *
     * @param string $branch
     *
     * @return bool
     */
    public function branchExists(string $branch): bool;

    /**
     * Deletes the specified branch from this repository (and optionally the selected remote as well)
     *
     * @param string       $branch
     * @param string|false $remote_repository
     *
     * @return static
     */
    public function deleteAutoBranch(string $branch, string|false $remote_repository = false): static;

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
    public function checkIsOnBranch(string $branch, string $action): static;

    /**
     * Returns the specified repository, or the configured default
     *
     * @param string|bool|null $repository
     *
     * @return string|null
     */
    public function selectRemoteRepository(string|bool|null $repository = null): ?string;

    /**
     * Checks if this repository has the requested suffix or version branch available, and if not, throws a RepositoriesHaveChangesException
     *
     * @param string $version
     * @param string $branch
     *
     * @return static
     * @throws RepositoriesVersionBranchNotExistsException
     */
    public function checkHasSuffixOrVersionBranch(string $version, string $branch): static;

    /**
     * Returns true if this repository has the requested suffix or version branch available
     *
     * @param string $version
     * @param string $branch
     *
     * @return bool
     */
    public function hasBranchOrVersionBranch(string $version, string $branch): bool;

    /**
     * Will push the changes on the specified branch (or all if none specified) to the specified, or default remote repository
     *
     * @param string|null $repository
     * @param string|null $branch
     * @param bool        $set_upstreams
     *
     * @return static
     */
    public function push(?string $repository = null, ?string $branch = null, bool $set_upstreams = false): static;

    /**
     * Will pull the changes for the current branch from the specified, or default remote repository
     *
     * @param string|null $remote
     * @param string|null $branch
     *
     * @return static
     */
    public function pull(?string $remote = null, ?string $branch = null): static;

    /**
     * Will fetch the changes for the current branch from the specified, or default remote repository
     *
     * @param string|null $remote
     *
     * @return static
     */
    public function fetch(?string $remote = null): static;

    /**
     * Returns true if the specified tag exists in this repository
     *
     * @param string $tag The tag to test for existence
     *
     * @return bool
     */
    public function tagExists(string $tag): bool;
}
