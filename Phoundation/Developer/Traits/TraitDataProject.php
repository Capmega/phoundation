<?php

/**
 * Trait TraitDataProject
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */

namespace Phoundation\Developer\Traits;

use Phoundation\Developer\Project\Interfaces\ProjectInterface;


trait TraitDataProject
{
    /**
     * The project these vendors belong to
     *
     * @var ProjectInterface|null
     */
    protected ?ProjectInterface $project = null;


    /**
     * Returns the project for this vendor list
     *
     * @return ProjectInterface|null
     */
    public function getProject(): ?ProjectInterface
    {
        return $this->project;
    }
}
