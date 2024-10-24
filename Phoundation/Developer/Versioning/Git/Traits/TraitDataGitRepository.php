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
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;


trait TraitDataGitRepository
{
    /**
     * The path that will be checked
     *
     * @var PhoDirectoryInterface $repository
     */
    protected PhoDirectoryInterface $repository;

    /**
     * The git command interface for this repository
     *
     * @var GitInterface $git
     */
    protected GitInterface $git;


    /**
     * GitRepository class constructor
     *
     * @param PhoDirectoryInterface $repository
     */
    public function __construct(PhoDirectoryInterface $repository)
    {
        $this->setRepository($repository);
    }


    /**
     * Returns a new GitRepository object
     *
     * @param PhoDirectoryInterface $repository
     *
     * @return static
     */
    public static function new(PhoDirectoryInterface $repository): static
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
     * @return PhoDirectoryInterface
     */
    public function getRepository(): PhoDirectoryInterface
    {
        return $this->repository;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $repository
     *
     * @return static
     */
    public function setRepository(PhoDirectoryInterface $repository): static
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
