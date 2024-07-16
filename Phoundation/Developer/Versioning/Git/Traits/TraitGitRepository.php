<?php

/**
 * Trait TraitGitRepository
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

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Path;
use Phoundation\Os\Processes\Process;

trait TraitGitRepository
{
    /**
     * The path that will be checked
     *
     * @var string $repository
     */
    protected string $repository;


    /**
     * GitRepository class constructor
     *
     * @param string $repository
     */
    public function __construct(string $repository)
    {
        $this->setRepository($repository);
    }


    /**
     * Returns a new GitRepository object
     *
     * @param string $repository
     *
     * @return static
     */
    public static function new(string $repository): static
    {
        return new static($repository);
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @return string
     */
    public function getRepository(): string
    {
        return $this->repository;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param string $repository
     *
     * @return static
     */
    public function setRepository(string $repository): static
    {
        $this->repository = Path::absolutePath($repository);
        $this->git        = Process::new('git')
                                   ->setExecutionDirectory($this->repository);
        if (!$this->repository) {
            if (!file_exists($repository)) {
                throw new OutOfBoundsException(tr('The specified directory ":directory" does not exist', [
                    ':directory' => $repository,
                ]));
            }
        }

        return $this;
    }
}
