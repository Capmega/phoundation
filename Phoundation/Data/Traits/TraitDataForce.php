<?php

/**
 * Trait TraitDataForce
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openforc.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataForce
{
    /**
     * Sets if force should be used
     *
     * @var bool $force
     */
    protected bool $force = false;


    /**
     * Returns if force should be used
     *
     * @return bool
     */
    public function getForce(): bool
    {
        return $this->force;
    }


    /**
     * Sets if force should be used
     *
     * @param bool $force
     *
     * @return static
     */
    public function setForce(bool $force): static
    {
        $this->force = $force;

        return $this;
    }
}