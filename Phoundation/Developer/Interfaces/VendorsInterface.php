<?php

declare(strict_types=1);

namespace Phoundation\Developer\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;

interface VendorsInterface extends IteratorInterface
{
    /**
     * Returns the directory where this projects vendor list may be found
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface;

    /**
     * Returns true if this vendors list contains only vendors with changes, or false if it contains all vendors
     *
     * @return bool
     */
    public function getChanged(): bool;

    /**
     * Returns all files for this vendor
     *
     * @return FsFilesInterface
     */
    public function getFiles(): FsFilesInterface;

    /**
     * Returns all modified files for this vendor
     *
     * @return StatusFilesInterface
     */
    public function getChangedFiles(): StatusFilesInterface;
}
