<?php

/**
 * Class Vendors
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\IteratorCore;
use Phoundation\Developer\Enums\EnumRepositoryType;
use Phoundation\Developer\Exception\VendorDoesNotExistException;
use Phoundation\Developer\Interfaces\VendorInterface;
use Phoundation\Developer\Interfaces\VendorsInterface;
use Phoundation\Developer\Traits\TraitDataProject;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Developer\Versioning\Git\StatusFiles;
use Phoundation\Filesystem\FsFiles;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;


class Vendors extends IteratorCore implements VendorsInterface
{
    use TraitDataProject;


    /**
     * Tracks if this vendors list contains only vendors with changes or not
     *
     * @var bool $changed
     */
    protected bool $changed;

    /**
     * Tracks the main directory for all the vendors in this object
     *
     * @var FsDirectoryInterface
     */
    protected FsDirectoryInterface $directory;

    /**
     * The type of repository files
     *
     * @var EnumRepositoryType|null $type
     */
    protected ?EnumRepositoryType $type;


    /**
     * Vendors class constructor
     *
     * @param bool   $changed
     */
    public function __construct(bool $changed = false)
    {
        $this->changed = $changed;

        $this->setAcceptedDataTypes(VendorInterface::class);

        if (isset($this->directory)) {
            $this->load();
        }
    }


    /**
     * Returns the directory where this projects vendor list may be found
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface
    {
        return $this->directory;
    }


    /**
     * Returns true if this vendors list contains only vendors with changes, or false if it contains all vendors
     *
     * @return bool
     */
    public function getChanged(): bool
    {
        return $this->changed;
    }


    /**
     * Returns all files for this vendor
     *
     * @return FsFilesInterface
     */
    public function getFiles(): FsFilesInterface
    {
        $return = new FsFiles();

        foreach ($this->source as $vendor) {
            $return->addSource($vendor->getFiles());
        }

        return $return;
    }


    /**
     * Returns all modified files for this vendor
     *
     * @return StatusFilesInterface
     */
    public function getChangedFiles(): StatusFilesInterface
    {
        $return = new StatusFiles($this->getDirectory());

        foreach ($this->source as $vendor) {
            $return->addSource($vendor->getChangedFiles());
        }

        return $return;
    }


    /**
     * Get vendors list by identifier
     *
     * @return IteratorInterface
     */
    public function getIdentifiers(): IteratorInterface
    {
        $return = [];

        foreach ($this->source as $vendor) {
            $return[$vendor->getIdentifier()] = $vendor;
        }

        return new Iterator($return);
    }


    /**
     * Returns a vendor by the specified identifier
     *
     * @param string $identifier
     * @param bool   $exception
     *
     * @return VendorInterface|null
     */
    public function getByIdentifier(string $identifier, bool $exception = true): ?VendorInterface
    {
        foreach ($this->source as $vendor) {
            if ($vendor->getIdentifier() == $identifier) {
                return $vendor;
            }
        }

        if ($exception) {
            throw new VendorDoesNotExistException(tr('The specified vendor identifier ":vendor" does not exist', [
                ':vendor' => $identifier
            ]));
        }

        return null;
    }
}
