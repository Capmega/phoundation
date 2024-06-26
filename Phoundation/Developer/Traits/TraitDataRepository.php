<?php

/**
 * Trait TraitDataRepository
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */

namespace Phoundation\Developer\Traits;

use Phoundation\Developer\Phoundation\Repositories\Interfaces\RepositoryInterface;

trait TraitDataRepository
{
    /**
     * The repository these vendors belong to
     *
     * @var RepositoryInterface|null
     */
    protected ?RepositoryInterface $repository = null;


    /**
     * Returns the repository for this vendor list
     *
     * @return RepositoryInterface|null
     */
    public function getRepository(): ?RepositoryInterface
    {
        return $this->repository;
    }
}
