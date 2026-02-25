<?php

/**
 * Trait TraitGit
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;


trait TraitGit
{
    /**
     * The path that will be checked
     *
     * @var PhoDirectoryInterface $_directory
     */
    protected PhoDirectoryInterface $_directory;

    /**
     * The git process
     *
     * @var GitInterface $_git
     */
    protected GitInterface $_git;


    /**
     * GitPath class constructor
     *
     * @param PhoDirectoryInterface $_directory
     */
    public function __construct(PhoDirectoryInterface $_directory)
    {
        $this->setDirectoryObject($_directory);
    }


    /**
     * Returns a new GitPath object
     *
     * @param PhoDirectoryInterface $_directory
     *
     * @return static
     */
    public static function new(PhoDirectoryInterface $_directory): static
    {
        return new static($_directory);
    }


    /**
     * Returns the GIT process
     *
     * @return GitInterface
     */
    public function getGitObject(): GitInterface
    {
        return $this->_git;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param string|null $sub_directory
     * @return PhoDirectoryInterface
     */
    public function getDirectoryObject(?string $sub_directory = null): PhoDirectoryInterface
    {
        return $this->_directory->addDirectory($sub_directory);
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $_directory
     *
     * @return static
     */
    public function setDirectoryObject(PhoDirectoryInterface $_directory): static
    {
        $this->_directory = $_directory->makeAbsolute()->checkWritable();
        $this->_git       = Git::new($this->_directory);

        return $this;
    }
}
