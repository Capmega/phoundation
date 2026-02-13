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
     * @var RepositoryInterface|null $_repository
     */
    protected ?RepositoryInterface $_repository = null;


    /**
     * Returns the Repository object linked to this object
     *
     * @return RepositoryInterface|null
     */
    public function getRepositoryObject(): ?RepositoryInterface
    {
        return $this->_repository;
    }


    /**
     * Sets the Repository object linked to this object
     *
     * @param RepositoryInterface|null $_repository
     *
     * @return static
     */
    public function setRepositoryObject(?RepositoryInterface $_repository): static
    {
        $this->_repository = $_repository;
        return $this;
    }
}
