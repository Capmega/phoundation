<?php

/**
 * Trait TraitDataGitRepository
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

trait TraitDataGitRepository
{
    /**
     * The path that will be checked
     *
     * @var FsDirectoryInterface $repository
     */
    protected FsDirectoryInterface $repository;

    /**
     * The git command interface for this repository
     *
     * @var GitInterface $git
     */
    protected GitInterface $git;


    /**
     * GitRepository class constructor
     *
     * @param FsDirectoryInterface $repository
     */
    public function __construct(FsDirectoryInterface $repository)
    {
        $this->setRepository($repository);
    }


    /**
     * Returns a new GitRepository object
     *
     * @param FsDirectoryInterface $repository
     *
     * @return static
     */
    public static function new(FsDirectoryInterface $repository): static
    {
        return new static($repository);
    }


    /**
     * Returns the git command interface for this repository
     *
     * @return GitInterface
     */
    public function getGit(): GitInterface
    {
        return $this->git;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @return FsDirectoryInterface
     */
    public function getRepository(): FsDirectoryInterface
    {
        return $this->repository;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param FsDirectoryInterface $repository
     *
     * @return static
     */
    public function setRepository(FsDirectoryInterface $repository): static
    {
        $this->repository = $repository->makeAbsolute();

        if (!$repository->exists()) {
            throw new OutOfBoundsException(tr('The specified repository directory ":directory" does not exist', [
                ':directory' => $repository,
            ]));
        }

        $this->git = Git::new($this->repository);

        return $this;
    }
}
