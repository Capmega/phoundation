<?php

/**
 * Class ProjectVendors
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
use Phoundation\Developer\Project\Vendors\Interfaces\ProjectVendorsInterface;
use Phoundation\Developer\Traits\TraitDataProject;
use Phoundation\Developer\Vendors;


class ProjectVendors extends Vendors implements ProjectVendorsInterface
{
    use TraitDataProject;


    /**
     * ProjectVendors class constructor
     *
     * @param ProjectInterface|null $project
     * @param EnumRepositoryType    $type
     * @param bool                  $changed
     */
    public function __construct(?ProjectInterface $project, EnumRepositoryType $type, bool $changed = false)
    {
        $this->type      = $type;
        $this->project   = $project;
        $this->directory = $project?->getDirectory()->addDirectory($this->type->getDirectorySuffix());

        parent::__construct($changed);
    }


    /**
     * Returns a new ProjectVendors object
     *
     * @param ProjectInterface|null $project
     * @param EnumRepositoryType    $type
     * @param bool                  $changed
     *
     * @return static
     */
    public static function new(?ProjectInterface $project, EnumRepositoryType $type, bool $changed = false): static
    {
        return new static($project, $type, $changed);
    }


    /**
     * Loads the vendors into memory
     *
     * @return void
     */
    protected function load(): void
    {
        foreach ($this->getDirectory()->scan(glob_flags: GLOB_MARK | GLOB_ONLYDIR) as $directory => $o_directory) {
            if (!str_ends_with($directory, 'Exception/')) {
                $this->add(new ProjectVendor($this->project, $this->type, $o_directory), $directory);
            }
        }
    }
}
