<?php

/**
 * Trait TraitGitProcess
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

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Os\Processes\Interfaces\ProcessInterface;
use Phoundation\Os\Processes\Process;

trait TraitGitProcess
{
    /**
     * The directory that will be checked
     *
     * @var FsDirectoryInterface $directory
     */
    protected FsDirectoryInterface $directory;

    /**
     * The git process
     *
     * @var ProcessInterface $git_process
     */
    protected ProcessInterface $git_process;


    /**
     * TraitGitProcess trait constructor
     *
     * @param FsDirectoryInterface $directory
     */
    public function __construct(FsDirectoryInterface $directory)
    {
        $this->setDirectory($directory);
    }


    /**
     * Returns a new static object that accepts $directory in the constructor
     *
     * @param FsDirectoryInterface $path
     *
     * @return static
     */
    public static function new(FsDirectoryInterface $path): static
    {
        return new static($path);
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface
    {
        return $this->directory;
    }


    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param FsDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(FsDirectoryInterface $directory): static
    {
        $this->directory   = $directory->makeAbsolute()->checkWritable();
        $this->git_process = Process::new('git')
                                    ->setExecutionDirectory($this->directory);

        return $this;
    }
}
