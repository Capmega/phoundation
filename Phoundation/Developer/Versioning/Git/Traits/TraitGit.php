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
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Stringable;


trait TraitGit
{
    /**
     * The path that will be checked
     *
     * @var PhoDirectoryInterface $directory
     */
    protected PhoDirectoryInterface $directory;

    /**
     * The git process
     *
     * @var GitInterface $git
     */
    protected GitInterface $git;


    /**
     * GitPath class constructor
     *
     * @param PhoDirectoryInterface $directory
     */
    public function __construct(PhoDirectoryInterface $directory)
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
     * @return PhoDirectoryInterface
     */
    public function getDirectory(): PhoDirectoryInterface
    {
        return $this->directory;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(PhoDirectoryInterface $directory): static
    {
        $this->directory = $directory->makeAbsolute()->checkWritable();
        $this->git       = Git::new($this->directory);

        return $this;
    }
}
