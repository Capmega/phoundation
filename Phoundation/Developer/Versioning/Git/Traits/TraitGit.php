<?php

/**
 * Trait TraitGit
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
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Stringable;

trait TraitGit
{
    /**
     * The path that will be checked
     *
     * @var FsDirectoryInterface $directory
     */
    protected FsDirectoryInterface $directory;

    /**
     * The git process
     *
     * @var GitInterface $git
     */
    protected GitInterface $git;


    /**
     * GitPath class constructor
     *
     * @param FsDirectoryInterface $directory
     */
    public function __construct(FsDirectoryInterface $directory)
    {
        $this->setDirectory($directory);
    }


    /**
     * Returns a new GitPath object
     *
     * @param string $directory
     *
     * @return static
     */
    public static function new(string $directory): static
    {
        return new static($directory);
    }


    /**
     * Returns the GIT process
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
    public function getDirectory(): FsDirectoryInterface
    {
        return $this->directory;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param FsDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(FsDirectoryInterface $directory): static
    {
        $this->directory = $directory->makeAbsolute()->checkWritable();
        $this->git       = Git::new($this->directory);

        return $this;
    }
}
