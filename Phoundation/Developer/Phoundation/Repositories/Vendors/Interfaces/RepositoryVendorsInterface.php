<?php

declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Repositories\Vendors\Interfaces;

use Phoundation\Developer\Interfaces\VendorsInterface;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;

interface RepositoryVendorsInterface extends VendorsInterface
{
/**
     * Returns the directory where this projects vendor list may be found
     *
     * @return PhoDirectoryInterface
     */
    public function getDirectory(): PhoDirectoryInterface;

    /**
     * Returns true if this vendors list contains only vendors with changes, or false if it contains all vendors
     *
     * @return bool
     */
    public function getChanged(): bool;

    /**
     * Returns all files for this vendor
     *
     * @return PhoFilesInterface
     */
    public function getFiles(): PhoFilesInterface;

    /**
     * Returns all modified files for this vendor
     *
     * @return StatusFilesInterface
     */
    public function getChangedFiles(): StatusFilesInterface;
}
