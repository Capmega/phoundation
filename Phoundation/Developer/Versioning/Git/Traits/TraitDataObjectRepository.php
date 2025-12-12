<?php

/**
 * Trait TraitDataObjectRepository
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;

use Phoundation\Developer\Versioning\Repositories\Interfaces\RepositoryInterface;


trait TraitDataObjectRepository
{
    /**
     * Tracks the Repository object linked to this object
     *
     * @var RepositoryInterface $o_repository
     */
    protected RepositoryInterface $o_repository;


    /**
     * Returns the Repository object linked to this object
     *
     * @return RepositoryInterface
     */
    public function getRepositoryObject(): RepositoryInterface
    {
        return $this->o_repository;
    }


    /**
     * Sets the Repository object linked to this object
     *
     * @param RepositoryInterface $o_repository
     *
     * @return static
     */
    public function setRepositoryObject(RepositoryInterface $o_repository): static
    {
        $this->o_repository = $o_repository;
        return $this;
    }
}
