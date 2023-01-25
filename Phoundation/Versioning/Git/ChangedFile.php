<?php

namespace Phoundation\Versioning\Git;

use Phoundation\Processes\Process;



/**
 * Class ChangedFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Versioning
 */
class ChangedFile
{
    /**
     * The file that has a change
     *
     * @var string
     */
    protected string $file;

    /**
     * The changes for this file
     *
     * @var string $changes
     */
    protected string $changes;



    /**
     * ChangedFile class constructor
     */
    public function __construct(string $file, string $changes)
    {
        $this->file    = $file;
        $this->changes = $changes;
    }



    /**
     * Returns a new Change object
     *
     * @param string $file
     * @param string $changes
     * @return static
     */
    public static function new(string $file, string $changes): static
    {
        return new static($file, $changes);
    }



    /**
     * Returns a diff containing the changes for this file
     *
     * @return string
     */
    public function getDiff(): string
    {
        return Process::new('git')
            ->addArgument('diff')
            ->addArgument($this->file)
            ->executeReturnString();
    }



    /**
     * Returns a patch containing the changes for this file
     *
     * @return string
     */
    public function getPatch(): string
    {
        return Process::new('git')
            ->addArgument('patch')
            ->addArgument($this->file)
            ->executeReturnString();
    }



    /**
     * Applies the patch for this file on the specified target file
     *
     * @param string $target_file
     * @return string
     */
    public function applyPatch(string $target_file): string
    {

    }
}