<?php

declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

interface BranchesInterface extends IteratorInterface
{
    /**
     * Returns the directory for this ChangedFiles object
     *
     * @param FsDirectoryInterface $directory
     *
     * @return static
     */
    public function setDirectory(FsDirectoryInterface $directory): static;


    /**
     * Display the branches on the CLI
     *
     * @return void
     */
    public function displayCliTable(): void;
}
