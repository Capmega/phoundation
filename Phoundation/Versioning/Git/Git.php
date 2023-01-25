<?php

namespace Phoundation\Versioning\Git;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Processes\Process;
use Phoundation\Versioning\Versioning;



/**
 * Class Git
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Versioning
 */
class Git extends Versioning
{
    /**
     * The path on which this git object is working
     *
     * @var string $path
     */
    protected string $path;

    /**
     * The git process
     *
     * @var Process $git
     */
    protected Process $git;



    /**
     * Git constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setPath($path);
    }



    /**
     * Generates and returns a new Git object
     *
     * @param string $path
     * @return static
     */
    public static function new(string $path): static
    {
        return new static($path);
    }



    /**
     * Set the git path of this object to the specified path
     *
     * @param string $path
     * @return $this
     */
    public function setPath(string $path): static
    {
        $this->path = realpath($path);

        if (!$this->path) {
            if (!file_exists($path)) {
                throw new OutOfBoundsException(tr('The specified path ":path" does not exist', [
                    ':path' => $path
                ]));
            }
        }

        $this->git = Process::new('git')->setExecutionPath($this->path);
        return $this;
    }



    /**
     * Returns the git path of this object
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }



    /**
     * Returns the current git branch for this path
     *
     * @return string
     */
    public function getBranch(): string
    {
        return $this->git
            ->addArgument('branch')
            ->addArgument($this->path)
            ->executeReturnString();
    }



    /**
     * Checks out the specified branch for this git path
     *
     * @param string $branch
     * @return static
     */
    public function checkout(string $branch): static
    {
        $this->git
            ->addArgument('checkout')
            ->addArgument($branch)
            ->executeNoReturn();

        return $this;
    }



    /**
     * Returns a ChangedFiles object containing all the files that have changes according to git
     *
     * @return ChangedFiles
     */
    public function getChanges(): ChangedFiles
    {
        return new ChangedFiles($this->path);
    }



    /**
     * @return bool
     */
    public function hasChanges(): bool
    {
        return (bool) $this->getChanges()->getCount();
    }
}