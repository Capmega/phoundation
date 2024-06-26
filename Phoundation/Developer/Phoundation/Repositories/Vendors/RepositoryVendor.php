<?php

/**
 * Class RepositoryVendor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */

namespace Phoundation\Developer\Phoundation\Repositories\Vendors;

use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Developer\Project\Vendors\Interfaces\ProjectVendorInterface;
use Phoundation\Developer\Traits\TraitDataRepository;
use Phoundation\Developer\Vendor;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

class RepositoryVendor extends Vendor implements ProjectVendorInterface
{
    use TraitDataRepository;


    /**
     * RepositoryVendor class constructor
     *
     * @param RepositoryInterface|null $repository
     * @param FsDirectoryInterface     $directory
     */
    public function __construct(?RepositoryInterface $repository, FsDirectoryInterface $directory)
    {
        $this->repository = $repository;
        parent::__construct($directory, $repository->getRepositoryType());
    }


    /**
     * Returns a new RepositoryVendors object
     *
     * @param RepositoryInterface|null  $repository
     * @param FsDirectoryInterface|null $directory
     *
     * @return static
     */
    public static function new(?RepositoryInterface $repository, FsDirectoryInterface $directory = null): static
    {
        return new static($repository, $directory);
    }
}
