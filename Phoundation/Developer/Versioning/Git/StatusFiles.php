<?php

namespace Phoundation\Developer\Versioning\Git;

use Iterator;
use Phoundation\Core\Strings;
use Phoundation\Processes\Process;
use Phoundation\Utils\Json;
use function Phoundation\Versioning\Git\count;
use function Phoundation\Versioning\Git\str_contains;


/**
 * Class StatusFiles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Versioning
 */
class StatusFiles implements Iterator
{
    /**
     * The path that will be checked
     *
     * @var string $path
     */
    protected string $path;

    /**
     * The files with their status found in the path
     *
     * @var array
     */
    protected array $status;

    /**
     * The git process
     *
     * @var Process $git
     */
    protected Process $git;



    /**
     * Changes class constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->git  = Process::new('git')->setExecutionPath($this->path);

        $this->scanChanges();
    }



    /**
     * Export this object to a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return Json::encode($this->status);
    }



    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->status = [];

        $files = $this->git
            ->addArgument('status')
            ->addArgument($this->path)
            ->addArgument('--porcelain')
            ->executeReturnArray();

        // Parse output
        foreach ($files as $file) {
            $status = substr($file, 0, 2);
            $file   = substr($file, 3);
            $target = '';

            if (str_contains($file, ' -> ')) {
                $target = Strings::from($file, ' -> ');
                $file   = Strings::until($file, ' -> ');
            }

            $this->status[$file] = StatusFile::new($status, $file, $target);
        }

        return $this;
    }



    /**
     * Returns the path for this ChangedFiles object
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }



    /**
     * Returns the amount of files that have changes
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->status);
    }



    /**
     * Removes the specified file from this list
     *
     * @return static
     */
    public function remove(string $file): static
    {
        unset($this->status[$file]);
        return $this;
    }



    /**
     * Returns the current file
     *
     * @return StatusFile
     */
    public function current(): StatusFile
    {
        return current($this->status);
    }



    /**
     * Progresses the internal pointer to the next file
     *
     * @return void
     */
    public function next(): void
    {
        next($this->status);
    }



    /**
     * Returns the current key for the current file
     *
     * @return string
     */
    public function key(): string
    {
        return key($this->status);
    }



    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->status[key($this->status)]);
    }



    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void
    {
        reset($this->status);
    }
}