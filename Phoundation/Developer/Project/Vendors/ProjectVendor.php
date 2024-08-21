<?php

/**
 * Class ProjectVendor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Project\Vendors;

use Phoundation\Developer\Enums\EnumRepositoryType;
use Phoundation\Developer\Project\Interfaces\ProjectInterface;
use Phoundation\Developer\Project\Vendors\Interfaces\ProjectVendorInterface;
use Phoundation\Developer\Traits\TraitDataProject;
use Phoundation\Developer\Vendor;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;


class ProjectVendor extends Vendor implements ProjectVendorInterface
{
    use TraitDataProject;


    /**
     * ProjectVendor class constructor
     *
     * @param ProjectInterface|null $project
     * @param FsDirectoryInterface  $directory
     * @param EnumRepositoryType    $type
     */
    public function __construct(?ProjectInterface $project, EnumRepositoryType $type, FsDirectoryInterface $directory)
    {
        $this->project = $project;
        parent::__construct($directory, $type);
    }


    /**
     * Returns a new ProjectVendor object
     *
     * @param ProjectInterface|null $project
     * @param FsDirectoryInterface  $directory
     * @param EnumRepositoryType    $type
     *
     * @return static
     */
    public static function new(?ProjectInterface $project, EnumRepositoryType $type, FsDirectoryInterface $directory): static
    {
        return new static($project, $type, $directory);
    }
}
