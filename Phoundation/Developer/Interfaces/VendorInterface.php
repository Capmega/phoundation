<?php

declare(strict_types=1);

namespace Phoundation\Developer\Interfaces;

use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;

interface VendorInterface
{
    /**
     * Returns the directory
     *
     * @return FsDirectoryInterface|null
     */
    public function getDirectory(): ?FsDirectoryInterface;

    /**
     * Returns the vendor identifier
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Returns the files for this vendor in a FsFilesInterface object
     *
     * @return FsFilesInterface
     */
    public function getFiles(): FsFilesInterface;

    /**
     * Returns the modified files for this vendor in a StatusFilesInterface object
     *
     * @return StatusFilesInterface
     */
    public function getChangedFiles(): StatusFilesInterface;
}
