<?php

/**
 * Class RepositoryVendors
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Repositories\Vendors;

use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoryInterface;
use Phoundation\Developer\Phoundation\Repositories\Vendors\Interfaces\RepositoryVendorsInterface;
use Phoundation\Developer\Traits\TraitDataRepository;
use Phoundation\Developer\Vendors;
use Phoundation\Filesystem\FsDirectory;


class RepositoryVendors extends Vendors implements RepositoryVendorsInterface
{
    use TraitDataRepository;


    /**
     * RepositoryVendors class constructor
     *
     * @param RepositoryInterface|null $repository
     * @param bool                     $changed
     */
    public function __construct(?RepositoryInterface $repository, bool $changed = false)
    {
        $this->repository = $repository;
        $this->type       = $repository?->getRepositoryType();

        if ($repository) {
            $this->directory = new FsDirectory($repository);
        }

        parent::__construct($changed);
    }


    /**
     * Returns a new RepositoryVendors object
     *
     * @param RepositoryInterface|null $repository
     * @param bool                     $changed
     *
     * @return static
     */
    public static function new(?RepositoryInterface $repository, bool $changed = false): static
    {
        return new static($repository, $changed);
    }


    /**
     * Loads the vendors into memory
     *
     * @return void
     */
    protected function load(): void
    {
        foreach ($this->directory->scan(glob_flags: GLOB_MARK | GLOB_ONLYDIR) as $directory => $o_directory) {
            if (!str_ends_with($directory, 'Exception/')) {
                $this->add(new RepositoryVendor($this->repository, $o_directory), $directory);
            }
        }
    }
}
