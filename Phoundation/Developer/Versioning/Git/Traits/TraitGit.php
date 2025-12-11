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
     * @var PhoDirectoryInterface $o_directory
     */
    protected PhoDirectoryInterface $o_directory;

    /**
     * The git process
     *
     * @var GitInterface $o_git
     */
    protected GitInterface $o_git;


    /**
     * GitPath class constructor
     *
     * @param PhoDirectoryInterface $o_directory
     */
    public function __construct(PhoDirectoryInterface $o_directory)
    {
        $this->setDirectoryObject($o_directory);
    }


    /**
     * Returns a new GitPath object
     *
     * @param PhoDirectoryInterface $o_directory
     *
     * @return static
     */
    public static function new(PhoDirectoryInterface $o_directory): static
    {
        return new static($o_directory);
    }


    /**
     * Returns the GIT process
     *
     * @return GitInterface
     */
    public function getGitObject(): GitInterface
    {
        return $this->o_git;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param string|null $sub_directory
     * @return PhoDirectoryInterface
     */
    public function getDirectoryObject(?string $sub_directory = null): PhoDirectoryInterface
    {
        return $this->o_directory->addDirectory($sub_directory);
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param PhoDirectoryInterface $o_directory
     *
     * @return static
     */
    public function setDirectoryObject(PhoDirectoryInterface $o_directory): static
    {
        $this->o_directory = $o_directory->makeAbsolute()->checkWritable();
        $this->o_git       = Git::new($this->o_directory);

        return $this;
    }
}
