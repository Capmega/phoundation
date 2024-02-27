<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Developer\Versioning\Git\Interfaces\GitInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Path;


/**
 * Trait Git
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
trait Git
{
    /**
     * The path that will be checked
     *
     * @var string $directory
     */
    protected string $directory;

    /**
     * The git process
     *
     * @var GitInterface $git
     */
    protected GitInterface $git;


    /**
     * GitPath class constructor
     *
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->setDirectory($directory);
    }


    /**
     * Returns a new GitPath object
     *
     * @param string $directory
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
     * @return string
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }


    /**
     * Returns the path for this ChangedFiles object
     *
     * @param string $directory
     * @return static
     */
    public function setDirectory(string $directory): static
    {
        $this->directory = Path::getAbsolute($directory);
        $this->git  = \Phoundation\Developer\Versioning\Git\Git::new($this->directory);

        if (!$this->directory) {
            if (!file_exists($directory)) {
                throw new OutOfBoundsException(tr('The specified directory ":directory" does not exist', [
                    ':directory' => $directory
                ]));
            }
        }

        return $this;
    }
}
