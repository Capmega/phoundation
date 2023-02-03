<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Developer\Versioning\Git\Traits\Path;
use Phoundation\Developer\Versioning\Versioning;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Processes\Process;


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
    use Path {
        setPath as protected setTraitPath;
    }



    /**
     * A cache for the changed files
     *
     * @var StatusFiles|null $changed_files
     */
    protected ?StatusFiles $changed_files = null;



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
        $this->changed_files = null;
        return $this->setTraitPath($path);
    }



    /**
     * Clone the specified URL to this path
     *
     * @return $this
     */
    public function clone(string $url): static
    {
        $this->git
            ->clearArguments()
            ->addArgument('clone')
            ->addArgument($this->url)
            ->addArgument($url)
            ->executeNoReturn();
    }



    /**
     * Returns the current git branch for this path
     *
     * @return string
     */
    public function getBranch(): string
    {
        return $this->git
            ->clearArguments()
            ->addArgument('branch')
            ->addArgument($this->path)
            ->executeReturnString();
    }



    /**
     * Returns a list of available git branches
     *
     * @return Branches
     */
    public function getBranches(): Branches
    {
        return Branches::new($this->path);
    }



    /**
     * Stashes the git changes
     *
     * @return Stash
     */
    public function stash(): Stash
    {
        return Stash::new($this->path);
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
            ->clearArguments()
            ->addArgument('checkout')
            ->addArgument($branch)
            ->executeNoReturn();

        return $this;
    }



    /**
     * Resets the current branch to the specified revision
     *
     * @param string $revision
     * @param string|null $file
     * @return static
     */
    public function reset(string $revision, ?string $file = null): static
    {
        $this->git
            ->clearArguments()
            ->addArgument('reset')
            ->addArgument($revision)
            ->addArgument($file)
            ->executeNoReturn();

        return $this;
    }



    /**
     * Resets the current branch to the specified revision
     *
     * @param string $message
     * @param bool $signed
     * @return static
     */
    public function commit(string $message, bool $signed = false): static
    {
        $this->git
            ->clearArguments()
            ->addArgument('commit')
            ->addArgument('-m')
            ->addArgument($message)
            ->addArgument($signed ? '-s' : null)
            ->executeNoReturn();

        return $this;
    }



    /**
     * Returns a ChangedFiles object containing all the files that have changes according to git
     *
     * @param string|null $path
     * @return StatusFiles
     */
    public function getStatus(?string $path = null): StatusFiles
    {
        if (!$this->changed_files) {
            $this->changed_files = new StatusFiles($path ?? $this->path);
        }

        return $this->changed_files;
    }



    /**
     * Returns if this git path has any changes
     *
     * @return bool
     */
    public function hasChanges(): bool
    {
        return (bool) $this->getStatus()->getCount();
    }



    /**
     * Get a diff for the specified file
     *
     * @param string|null $file
     * @return string
     */
    public function getDiff(?string $file = null): string
    {
        return $this->git
            ->clearArguments()
            ->addArgument('diff')
            ->addArgument('--no-color')
            ->addArgument('--')
            ->addArgument($file ? $this->path . $file : null)
            ->executeReturnString();
    }



    /**
     * Save the diff for the specified file to the specified target
     *
     * @param string $file
     * @return string
     */
    public function saveDiff(string $file): string
    {
        $diff = $this->getDiff($file);
        $file = \Phoundation\Filesystem\Path::getTemporary() . $file . '-' . sha1($file) . '.patch';

        file_put_contents($file, $diff);
        return $file;
    }



    /**
     * Apply the specified patch to the specified target file
     *
     * @param string $patch_file
     * @return void
     */
    public function apply(string $patch_file): void
    {
        $this->git
            ->clearArguments()
            ->addArgument('apply')
            ->addArgument($patch_file)
            ->executeReturnString();
    }
}