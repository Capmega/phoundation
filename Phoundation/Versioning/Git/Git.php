<?php

namespace Phoundation\Versioning\Git;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Path;
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
        $this->path = Filesystem::absolute($path);

        if (!$this->path) {
            if (!file_exists($path)) {
                throw new OutOfBoundsException(tr('The specified path ":path" does not exist', [
                    ':path' => $path
                ]));
            }
        }

        $this->git           = Process::new('git')->setExecutionPath($this->path);
        $this->changed_files = null;
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
            ->addArgument('.')
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
     * Stashes the git changes
     *
     * @return static
     */
    public function stash(): static
    {
        $this->git
            ->clearArguments()
            ->addArgument('stash')
            ->executeNoReturn();

        return $this;
    }



    /**
     * Unstashes the git changes
     *
     * @return static
     */
    public function stashPop(): static
    {
        $this->git
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('pop')
            ->executeNoReturn();

        return $this;
    }



    /**
     * Lists the available stashes in the git repository
     *
     * @return array
     */
    public function getStashList(): array
    {
        $return  = [];
        $results = $this->git
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('list')
            ->executeReturnArray();

        return $return;
    }



    /**
     * Lists the changes available in the top most stash in the git repository
     *
     * @return array
     */
    public function getStashShow(): array
    {
        return $this->git
            ->clearArguments()
            ->addArgument('stash')
            ->addArgument('show')
            ->executeReturnArray();
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
     * @param ?string $revision
     * @return static
     */
    public function reset(?string $revision): static
    {
        $this->git
            ->clearArguments()
            ->addArgument('reset')
            ->addArgument($revision)
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
            ->addArgument('reset')
            ->addArgument('-m')
            ->addArgument($message)
            ->addArgument($signed ?? null)
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
        if (!$path) {
            $path = $this->path;
        }

        if (!$this->changed_files) {
            $this->changed_files = new StatusFiles($path);
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
     * @param string $file
     * @return string
     */
    public function getDiff(string $file): string
    {
        return $this->git
            ->clearArguments()
            ->addArgument('diff')
            ->addArgument('--no-color')
            ->addArgument('--')
            ->addArgument($this->path . $file)
            ->executeReturnString();
    }



    /**
     * Save the diff for the specified file to the specified target
     *
     * @param string $file
     * @param string|null $storage_path
     * @return string
     */
    public function saveDiff(string $file, ?string $storage_path = null): string
    {
        $diff = $this->getDiff($file);
        $file = Path::getTemporary() . $file . '-' . sha1($file) . '.patch';

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