<?php

declare(strict_types=1);

namespace Phoundation\Developer\Interfaces;

use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;

interface VendorInterface
{
    /**
     * Returns the directory
     *
     * @return PhoDirectoryInterface|null
     */
    public function getDirectory(): ?PhoDirectoryInterface;

    /**
     * Returns the vendor identifier
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Returns the files for this vendor in a PhoFilesInterface object
     *
     * @return PhoFilesInterface
     */
    public function getFiles(): PhoFilesInterface;

    /**
     * Returns the modified files for this vendor in a StatusFilesInterface object
     *
     * @return StatusFilesInterface
     */
    public function getChangedFiles(): StatusFilesInterface;
}
