<?php

namespace Phoundation\Developer\Versioning\Repositories\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\RemotesInterface;
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
}
