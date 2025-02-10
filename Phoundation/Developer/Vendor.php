<?php

/**
 * Class Vendor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer;

use Phoundation\Data\Traits\TraitDataDirectoryReadonly;
use Phoundation\Data\Traits\TraitDataName;
use Phoundation\Data\Traits\TraitDataPath;
use Phoundation\Developer\Enums\EnumRepositoryType;
use Phoundation\Developer\Interfaces\VendorInterface;
use Phoundation\Developer\Traits\TraitDataRepositoryType;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Developer\Versioning\Git\Interfaces\StatusFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Utils\Arrays;


class Vendor implements VendorInterface
{
    use TraitDataName;
    use TraitDataDirectoryReadonly;
    use TraitDataRepositoryType;


    /**
     * ProjectVendor class constructor
     *
     * @param PhoDirectoryInterface $directory
     * @param EnumRepositoryType    $type
     */
    public function __construct(PhoDirectoryInterface $directory, EnumRepositoryType $type)
    {
        $this->type      = $type;
        $this->o_directory = $directory;
        $this->name      = $this->o_directory->getBasename();
    }


    /**
     * Returns the vendor identifier
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->type->value . '/' . $this->getName();
    }


    /**
     * Returns the files for this vendor in a PhoFilesInterface object
     *
     * @return PhoFilesInterface
     */
    public function getFiles(): PhoFilesInterface
    {
        return $this->o_directory->find()
                               ->setType('f')
                               ->getFiles();
    }


    /**
     * Returns the modified files for this vendor in a StatusFilesInterface object
     *
     * @return StatusFilesInterface
     */
    public function getChangedFiles(): StatusFilesInterface
    {
        return Git::new($this->o_directory)->getStatusFilesObject();
    }
}
