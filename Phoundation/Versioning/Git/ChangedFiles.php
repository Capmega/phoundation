<?php

namespace Phoundation\Versioning\Git;

use Iterator;
use Phoundation\Processes\Process;



/**
 * Class ChangedFiles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Versioning
 */
class ChangedFiles implements Iterator
{
    /**
     * The path that will be checked
     *
     * @var string $path
     */
    protected string $path;

    /**
     * The changes found in the path
     *
     * @var array
     */
    protected array $changes;



    /**
     * Changes class constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->scanChanges();
    }



    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->changes = [];

        $files = Process::new('git')
            ->addArgument('status')
            ->addArgument($this->path)
            ->executeReturnArray();

        // Parse output
        foreach ($files as $file => $changes) {
            $this->changes[$file] = ChangedFile::new($file, $changes);
        }

        return $this;
    }



    /**
     * Returns the amount of files that have changes
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->changes);
    }



    /**
     * Returns the current file
     *
     * @return ChangedFile
     */
    public function current(): ChangedFile
    {
        return current($this->changes);
    }



    /**
     * Progresses the internal pointer to the next file
     *
     * @return void
     */
    public function next(): void
    {
        next($this->changes);
    }



    /**
     * Returns the current key for the current file
     *
     * @return string
     */
    public function key(): string
    {
        return key($this->changes);
    }



    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->changes[key($this->changes)]);
    }



    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void
    {
        reset($this->changes);
    }
}